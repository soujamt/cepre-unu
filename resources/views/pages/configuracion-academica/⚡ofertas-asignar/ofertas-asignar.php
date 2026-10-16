<?php

use App\Enums\EstadoCatalogo;
use App\Enums\EstadoProceso;
use App\Models\LugarInscripcion;
use App\Models\ModalidadAdmision;
use App\Models\OfertaBase;
use App\Models\Proceso;
use App\Models\ProcesoOferta;
use App\Models\Turno;
use App\Traits\AuthorizesPermisos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('ASIGNAR OFERTAS')]
class extends Component
{
    use AuthorizesPermisos;

    public string $buscar = '';

    public string $idProceso = '';

    public string $idTurno = '';

    public string $idModalidad = '';

    public string $idLugarInscripcion = '';

    /**
     * @var array<int, int|string>
     */
    public array $seleccionadas = [];

    public function mount(): void
    {
        $this->autorizarPermiso('oferta', 'crear');
    }

    public function updatedBuscar(): void {}

    public function updatedIdProceso(): void
    {
        $this->seleccionadas = [];
    }

    public function updatedIdTurno(): void
    {
        $this->seleccionadas = [];
    }

    public function updatedIdModalidad(): void
    {
        $this->idLugarInscripcion = '';
        $this->seleccionadas = [];
    }

    public function updatedIdLugarInscripcion(): void
    {
        $this->seleccionadas = [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'idProceso' => ['required', 'integer', 'exists:tbl_proceso,id_pro'],
            'idTurno' => ['required', 'integer', 'exists:tbl_turno,id_tur'],
            'idModalidad' => ['required', 'integer', 'exists:tbl_modalidad_admision,id_mad'],
            'idLugarInscripcion' => ['required', 'integer', 'exists:tbl_lugar_inscripcion,id_lin'],
            'seleccionadas' => ['required', 'array', 'min:1'],
            'seleccionadas.*' => ['integer', 'exists:tbl_oferta_base,id_ofb'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'idProceso.required' => 'El proceso es obligatorio.',
            'idTurno.required' => 'El turno es obligatorio.',
            'idModalidad.required' => 'La modalidad es obligatoria.',
            'idLugarInscripcion.required' => 'El lugar de inscripcion es obligatorio.',
            'seleccionadas.required' => 'Debe seleccionar al menos una oferta base.',
            'seleccionadas.min' => 'Debe seleccionar al menos una oferta base.',
        ];
    }

    #[Computed]
    public function procesosDisponibles(): Collection
    {
        return Proceso::query()
            ->with('semestre')
            ->where('estado_pro', EstadoProceso::Habilitado->value)
            ->orderByDesc('id_pro')
            ->get();
    }

    #[Computed]
    public function turnosDisponibles(): Collection
    {
        return Turno::query()
            ->where('estado_tur', EstadoCatalogo::Habilitado->value)
            ->orderBy('id_tur')
            ->get();
    }

    #[Computed]
    public function modalidadesDisponibles(): Collection
    {
        return ModalidadAdmision::query()
            ->where('estado_mad', EstadoCatalogo::Habilitado->value)
            ->orderBy('nombre_mad')
            ->get();
    }

    #[Computed]
    public function lugaresInscripcionDisponibles(): Collection
    {
        return LugarInscripcion::query()
            ->with('sede')
            ->where('estado_lin', EstadoCatalogo::Habilitado->value)
            ->when(filled($this->idModalidad), function (Builder $query): void {
                $query->whereHas('modalidadesLugarInscripcion', function (Builder $builder): void {
                    $builder->where('id_modalidad_admision', (int) $this->idModalidad)
                        ->where('estado_mli', EstadoCatalogo::Habilitado->value);
                });
            })
            ->orderBy('id_lin')
            ->get();
    }

    #[Computed]
    public function procesoSeleccionado(): ?Proceso
    {
        if (blank($this->idProceso)) {
            return null;
        }

        return Proceso::query()
            ->with('semestre')
            ->find($this->idProceso);
    }

    #[Computed]
    public function turnoSeleccionado(): ?Turno
    {
        if (blank($this->idTurno)) {
            return null;
        }

        return Turno::query()->find($this->idTurno);
    }

    #[Computed]
    public function filtrosAplicados(): bool
    {
        return filled($this->idModalidad) && filled($this->idLugarInscripcion);
    }

    /**
     * @return array<int, int>
     */
    #[Computed]
    public function idsOfertasYaAsignadas(): array
    {
        if (blank($this->idProceso) || blank($this->idTurno) || ! $this->filtrosAplicados()) {
            return [];
        }

        $idsOfertasBase = $this->ofertasBase
            ->pluck('id_ofb')
            ->map(fn (int|string $id): int => (int) $id)
            ->values();

        if ($idsOfertasBase->isEmpty()) {
            return [];
        }

        return ProcesoOferta::query()
            ->where('id_proceso', (int) $this->idProceso)
            ->where('id_turno', (int) $this->idTurno)
            ->whereIn('id_oferta_base', $idsOfertasBase)
            ->pluck('id_oferta_base')
            ->map(fn (int|string $id): int => (int) $id)
            ->all();
    }

    /**
     * @return array<int, int>
     */
    #[Computed]
    public function idsOfertasSeleccionables(): array
    {
        $idsAsignadas = $this->idsOfertasYaAsignadas();

        return $this->ofertasBase
            ->pluck('id_ofb')
            ->map(fn (int|string $id): int => (int) $id)
            ->reject(fn (int $id): bool => in_array($id, $idsAsignadas, true))
            ->values()
            ->all();
    }

    #[Computed]
    public function todasLasOfertasVisiblesSeleccionadas(): bool
    {
        if ($this->idsOfertasSeleccionables() === []) {
            return false;
        }

        $idsSeleccionadas = collect($this->seleccionadas)
            ->map(fn (int|string $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        return collect($this->idsOfertasSeleccionables())
            ->diff($idsSeleccionadas)
            ->isEmpty();
    }

    #[Computed]
    public function ofertasBase(): Collection
    {
        if (! $this->filtrosAplicados()) {
            return OfertaBase::newCollection();
        }

        return OfertaBase::query()
            ->with([
                'modalidadLugarInscripcion.modalidadAdmision',
                'modalidadLugarInscripcion.lugarInscripcion.sede',
                'sedeCarrera.sede',
                'sedeCarrera.carrera',
            ])
            ->withCount([
                'procesosOferta as asignaciones_proceso_count' => fn (Builder $query) => $query
                    ->when(filled($this->idProceso), fn (Builder $builder) => $builder->where('id_proceso', (int) $this->idProceso)),
            ])
            ->where('estado_ofb', EstadoCatalogo::Habilitado->value)
            ->when(filled($this->idModalidad), function (Builder $query): void {
                $query->whereHas('modalidadLugarInscripcion', function (Builder $builder): void {
                    $builder->where('id_modalidad_admision', (int) $this->idModalidad);
                });
            })
            ->when(filled($this->idLugarInscripcion), function (Builder $query): void {
                $query->whereHas('modalidadLugarInscripcion', function (Builder $builder): void {
                    $builder->where('id_lugar_inscripcion', (int) $this->idLugarInscripcion);
                });
            })
            ->when(filled($this->buscar), function (Builder $query): void {
                $search = $this->normalizarTexto($this->buscar);

                $query->where(function (Builder $innerQuery) use ($search): void {
                    $innerQuery->where('codigo_externo_ofb', 'like', "%{$search}%")
                        ->orWhereHas('modalidadLugarInscripcion.modalidadAdmision', fn (Builder $modalidadQuery) => $modalidadQuery
                            ->where('nombre_mad', 'like', "%{$search}%"))
                        ->orWhereHas('modalidadLugarInscripcion.lugarInscripcion', fn (Builder $lugarQuery) => $lugarQuery
                            ->where('nombre_lin', 'like', "%{$search}%"))
                        ->orWhereHas('sedeCarrera.sede', fn (Builder $sedeQuery) => $sedeQuery
                            ->where('nombre_sed', 'like', "%{$search}%"))
                        ->orWhereHas('sedeCarrera.carrera', fn (Builder $carreraQuery) => $carreraQuery
                            ->where('nombre_car', 'like', "%{$search}%"));
                });
            })
            ->orderBy('codigo_externo_ofb')
            ->get();
    }

    public function guardar(): void
    {
        $this->validate();

        $idsSeleccionados = collect($this->seleccionadas)
            ->map(fn (int|string $id): int => (int) $id)
            ->unique()
            ->values();

        $idsExistentes = ProcesoOferta::query()
            ->withTrashed()
            ->where('id_proceso', (int) $this->idProceso)
            ->where('id_turno', (int) $this->idTurno)
            ->whereIn('id_oferta_base', $idsSeleccionados)
            ->pluck('id_oferta_base');

        $idsRegistrar = $idsSeleccionados
            ->diff($idsExistentes)
            ->values();

        if ($idsRegistrar->isEmpty()) {
            $this->dispatch(
                'toast',
                message: 'Las ofertas seleccionadas ya estaban asignadas para ese proceso y turno.',
                type: 'error'
            );

            return;
        }

        try {
            DB::transaction(function () use ($idsRegistrar): void {
                $idsRegistrar->each(function (int $idOfertaBase): void {
                    ProcesoOferta::query()->create([
                        'id_proceso' => (int) $this->idProceso,
                        'id_oferta_base' => $idOfertaBase,
                        'id_turno' => (int) $this->idTurno,
                        'estado_pof' => EstadoProceso::Habilitado->value,
                    ]);
                });
            });
        } catch (Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudieron asignar las ofertas. Intente nuevamente.', type: 'error');

            return;
        }

        $mensaje = $idsExistentes->isNotEmpty()
            ? "Se registraron {$idsRegistrar->count()} ofertas. Se omitieron {$idsExistentes->count()} duplicadas."
            : "Se registraron {$idsRegistrar->count()} ofertas correctamente.";

        $this->seleccionadas = [];
        unset($this->ofertasBase);

        $this->dispatch('toast', message: $mensaje, type: 'success');

        $this->redirect(route('ofertas.index'), navigate: true);
    }

    public function seleccionarTodasLasOfertasVisibles(): void
    {
        $this->seleccionadas = array_map(
            static fn (int $id): string => (string) $id,
            $this->idsOfertasSeleccionables()
        );
    }

    public function limpiarSeleccionadasVisibles(): void
    {
        $idsVisibles = $this->ofertasBase
            ->pluck('id_ofb')
            ->map(fn (int|string $id): int => (int) $id)
            ->all();

        $this->seleccionadas = collect($this->seleccionadas)
            ->reject(fn (int|string $id): bool => in_array((int) $id, $idsVisibles, true))
            ->map(fn (int|string $id): string => (string) $id)
            ->values()
            ->all();
    }

    public function descripcionOfertaBase(OfertaBase $ofertaBase): string
    {
        $modalidad = $ofertaBase->modalidadLugarInscripcion?->modalidadAdmision?->nombre_mad ?? 'SIN MODALIDAD';
        $carrera = $ofertaBase->sedeCarrera?->carrera?->nombre_car ?? 'SIN CARRERA';
        $sede = $ofertaBase->sedeCarrera?->sede?->nombre_sed ?? 'SIN SEDE';
        $lugar = $ofertaBase->modalidadLugarInscripcion?->lugarInscripcion?->nombre_lin ?? 'SIN LUGAR';

        return "{$modalidad} | {$carrera} | {$sede} | {$lugar}";
    }

    public function ofertaYaAsignada(int $idOfertaBase): bool
    {
        return in_array($idOfertaBase, $this->idsOfertasYaAsignadas(), true);
    }

    private function normalizarTexto(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $normalizado = preg_replace('/\s+/u', ' ', trim($valor));

        return filled($normalizado) ? $normalizado : null;
    }
};
