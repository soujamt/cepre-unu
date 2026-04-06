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
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('layouts.app')]
#[Title('ASIGNAR OFERTAS')]
class extends Component
{
    use AuthorizesPermisos;
    use WithPagination;

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

    public function updatedBuscar(): void
    {
        $this->resetPage();
    }

    public function updatedIdProceso(): void
    {
        $this->seleccionadas = [];
        $this->resetPage();
    }

    public function updatedIdTurno(): void
    {
        $this->seleccionadas = [];
    }

    public function updatedIdModalidad(): void
    {
        $this->resetPage();
    }

    public function updatedIdLugarInscripcion(): void
    {
        $this->resetPage();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'idProceso' => ['required', 'integer', 'exists:tbl_proceso,id_pro'],
            'idTurno' => ['required', 'integer', 'exists:tbl_turno,id_tur'],
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
            'seleccionadas.required' => 'Debe seleccionar al menos una oferta base.',
            'seleccionadas.min' => 'Debe seleccionar al menos una oferta base.',
        ];
    }

    #[Computed]
    public function procesosDisponibles(): \Illuminate\Database\Eloquent\Collection
    {
        return Proceso::query()
            ->with('semestre')
            ->where('estado_pro', EstadoProceso::Habilitado->value)
            ->orderByDesc('id_pro')
            ->get();
    }

    #[Computed]
    public function turnosDisponibles(): \Illuminate\Database\Eloquent\Collection
    {
        return Turno::query()
            ->where('estado_tur', EstadoCatalogo::Habilitado->value)
            ->orderBy('id_tur')
            ->get();
    }

    #[Computed]
    public function modalidadesDisponibles(): \Illuminate\Database\Eloquent\Collection
    {
        return ModalidadAdmision::query()
            ->where('estado_mad', EstadoCatalogo::Habilitado->value)
            ->orderBy('nombre_mad')
            ->get();
    }

    #[Computed]
    public function lugaresInscripcionDisponibles(): \Illuminate\Database\Eloquent\Collection
    {
        return LugarInscripcion::query()
            ->with('sede')
            ->where('estado_lin', EstadoCatalogo::Habilitado->value)
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
    public function ofertasBase(): LengthAwarePaginator
    {
        return OfertaBase::query()
            ->with([
                'modalidadAdmision',
                'lugarInscripcion.sede',
                'sedeCarrera.sede',
                'sedeCarrera.carrera',
            ])
            ->withCount([
                'procesosOferta as asignaciones_proceso_count' => fn (Builder $query) => $query
                    ->when(filled($this->idProceso), fn (Builder $builder) => $builder->where('id_proceso', (int) $this->idProceso)),
            ])
            ->where('estado_ofb', EstadoCatalogo::Habilitado->value)
            ->when(filled($this->idModalidad), function (Builder $query): void {
                $query->where('id_modalidad_admision', (int) $this->idModalidad);
            })
            ->when(filled($this->idLugarInscripcion), function (Builder $query): void {
                $query->where('id_lugar_inscripcion', (int) $this->idLugarInscripcion);
            })
            ->when(filled($this->buscar), function (Builder $query): void {
                $search = $this->normalizarTexto($this->buscar);

                $query->where(function (Builder $innerQuery) use ($search): void {
                    $innerQuery->where('codigo_externo_ofb', 'like', "%{$search}%")
                        ->orWhereHas('modalidadAdmision', fn (Builder $modalidadQuery) => $modalidadQuery
                            ->where('nombre_mad', 'like', "%{$search}%"))
                        ->orWhereHas('lugarInscripcion', fn (Builder $lugarQuery) => $lugarQuery
                            ->where('nombre_lin', 'like', "%{$search}%"))
                        ->orWhereHas('sedeCarrera.sede', fn (Builder $sedeQuery) => $sedeQuery
                            ->where('nombre_sed', 'like', "%{$search}%"))
                        ->orWhereHas('sedeCarrera.carrera', fn (Builder $carreraQuery) => $carreraQuery
                            ->where('nombre_car', 'like', "%{$search}%"));
                });
            })
            ->orderBy('codigo_externo_ofb')
            ->paginate(15);
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
        } catch (\Throwable $throwable) {
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
    }

    public function descripcionOfertaBase(OfertaBase $ofertaBase): string
    {
        $modalidad = $ofertaBase->modalidadAdmision?->nombre_mad ?? 'SIN MODALIDAD';
        $carrera = $ofertaBase->sedeCarrera?->carrera?->nombre_car ?? 'SIN CARRERA';
        $sede = $ofertaBase->sedeCarrera?->sede?->nombre_sed ?? 'SIN SEDE';
        $lugar = $ofertaBase->lugarInscripcion?->nombre_lin ?? 'SIN LUGAR';

        return "{$modalidad} | {$carrera} | {$sede} | {$lugar}";
    }

    public function ofertaYaAsignada(int $idOfertaBase): bool
    {
        if (blank($this->idProceso) || blank($this->idTurno)) {
            return false;
        }

        return ProcesoOferta::query()
            ->where('id_proceso', (int) $this->idProceso)
            ->where('id_oferta_base', $idOfertaBase)
            ->where('id_turno', (int) $this->idTurno)
            ->exists();
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
