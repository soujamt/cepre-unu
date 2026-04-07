<?php

use App\Enums\EstadoCatalogo;
use App\Enums\EstadoProceso;
use App\Models\OfertaBase;
use App\Models\Proceso;
use App\Models\ProcesoOferta;
use App\Models\Turno;
use App\Traits\AuthorizesPermisos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('layouts.app')]
#[Title('OFERTAS')]
class extends Component
{
    use AuthorizesPermisos;
    use WithPagination;

    public string $buscar = '';

    /**
     * @var array<string, bool>
     */
    public array $filtroEstado = [];

    /**
     * @var array{id_proceso: string, id_oferta_base: string, id_turno: string}
     */
    public array $ofertaForm = [
        'id_proceso' => '',
        'id_oferta_base' => '',
        'id_turno' => '',
    ];

    public string $tituloModal = 'Editar oferta';

    public bool $isEdit = false;

    public ?int $editingId = null;

    public function mount(): void
    {
        $this->autorizarPermiso('oferta', 'indice');
    }

    public function updatedBuscar(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroEstado(): void
    {
        $this->resetPage();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'ofertaForm.id_proceso' => ['required', 'integer', 'exists:tbl_proceso,id_pro'],
            'ofertaForm.id_oferta_base' => ['required', 'integer', 'exists:tbl_oferta_base,id_ofb'],
            'ofertaForm.id_turno' => [
                'required',
                'integer',
                'exists:tbl_turno,id_tur',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (
                        blank($this->ofertaForm['id_proceso'])
                        || blank($this->ofertaForm['id_oferta_base'])
                        || blank($value)
                    ) {
                        return;
                    }

                    $exists = ProcesoOferta::query()
                        ->withTrashed()
                        ->where('id_proceso', (int) $this->ofertaForm['id_proceso'])
                        ->where('id_oferta_base', (int) $this->ofertaForm['id_oferta_base'])
                        ->where('id_turno', (int) $value)
                        ->when($this->editingId, fn (Builder $query) => $query->where('id_pof', '!=', $this->editingId))
                        ->exists();

                    if ($exists) {
                        $fail('La oferta seleccionada ya esta registrada para el proceso y turno indicados.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'ofertaForm.id_proceso.required' => 'El proceso es obligatorio.',
            'ofertaForm.id_proceso.exists' => 'El proceso seleccionado no es valido.',
            'ofertaForm.id_oferta_base.required' => 'La oferta base es obligatoria.',
            'ofertaForm.id_oferta_base.exists' => 'La oferta base seleccionada no es valida.',
            'ofertaForm.id_turno.required' => 'El turno es obligatorio.',
            'ofertaForm.id_turno.exists' => 'El turno seleccionado no es valido.',
        ];
    }

    public function estados(): array
    {
        return EstadoProceso::cases();
    }

    #[Computed]
    public function estadosSeleccionados(): array
    {
        return collect($this->filtroEstado)
            ->filter(fn (bool $selected): bool => $selected)
            ->keys()
            ->values()
            ->all();
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
    public function ofertasBaseDisponibles(): Collection
    {
        return OfertaBase::query()
            ->with([
                'modalidadLugarInscripcion.modalidadAdmision',
                'modalidadLugarInscripcion.lugarInscripcion.sede',
                'sedeCarrera.sede',
                'sedeCarrera.carrera',
            ])
            ->where('estado_ofb', EstadoCatalogo::Habilitado->value)
            ->orderBy('codigo_externo_ofb')
            ->get();
    }

    #[Computed]
    public function turnosDisponibles(): Collection
    {
        return Turno::query()
            ->where('estado_tur', EstadoCatalogo::Habilitado->value)
            ->orderBy('nombre_tur')
            ->get();
    }

    public function limpiarFiltroEstado(): void
    {
        $this->filtroEstado = [];
    }

    public function cargarOferta(?int $id = null): void
    {
        $this->autorizarPermiso('oferta', 'editar');
        $this->resetValidation();

        $oferta = ProcesoOferta::query()->findOrFail($id);

        $this->editingId = $oferta->id_pof;
        $this->ofertaForm = [
            'id_proceso' => (string) $oferta->id_proceso,
            'id_oferta_base' => (string) $oferta->id_oferta_base,
            'id_turno' => (string) $oferta->id_turno,
        ];
        $this->tituloModal = 'Editar oferta';
        $this->isEdit = true;

        $this->modal('modal-oferta')->show();
    }

    public function guardar(): void
    {
        $this->autorizarPermiso('oferta', 'editar');
        $data = $this->validate();

        try {
            DB::transaction(function () use ($data): void {
                $payload = [
                    'id_proceso' => (int) $data['ofertaForm']['id_proceso'],
                    'id_oferta_base' => (int) $data['ofertaForm']['id_oferta_base'],
                    'id_turno' => (int) $data['ofertaForm']['id_turno'],
                ];

                ProcesoOferta::query()->findOrFail($this->editingId)->update($payload);
            });
        } catch (\Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo guardar la oferta. Intente nuevamente.', type: 'error');

            return;
        }

        $this->dispatch(
            'toast',
            message: 'Oferta actualizada correctamente.',
            type: 'success'
        );

        unset($this->ofertas);
        $this->modal('modal-oferta')->close();
        $this->resetForm();
    }

    #[On('oferta-cambiar-estado')]
    public function cambiarEstado(?int $id_oferta = null): void
    {
        $this->autorizarPermiso('oferta', 'estado');

        if ($id_oferta === null) {
            $this->dispatch('toast', message: 'ID de oferta no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_oferta): void {
                $oferta = ProcesoOferta::query()->findOrFail($id_oferta);

                $nuevoEstado = match ($oferta->estado_pof) {
                    EstadoProceso::Habilitado => EstadoProceso::Deshabilitado->value,
                    EstadoProceso::Deshabilitado, EstadoProceso::Cerrado => EstadoProceso::Habilitado->value,
                };

                $oferta->update([
                    'estado_pof' => $nuevoEstado,
                ]);
            });
        } catch (\Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo cambiar el estado de la oferta.', type: 'error');

            return;
        }

        unset($this->ofertas);
        $this->dispatch('toast', message: 'Estado de la oferta actualizado.', type: 'success');
    }

    #[On('oferta-eliminar')]
    public function eliminar(?int $id_oferta = null): void
    {
        $this->autorizarPermiso('oferta', 'eliminar');

        if ($id_oferta === null) {
            $this->dispatch('toast', message: 'ID de oferta no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_oferta): void {
                $oferta = ProcesoOferta::query()
                    ->withCount('inscripciones')
                    ->findOrFail($id_oferta);

                if ($oferta->inscripciones_count > 0) {
                    throw new \RuntimeException('La oferta tiene inscripciones relacionadas y no puede eliminarse.');
                }

                $oferta->delete();
            });
        } catch (\Throwable $throwable) {
            report_if(! $throwable instanceof \RuntimeException, $throwable);
            $this->dispatch(
                'toast',
                message: $throwable->getMessage() ?: 'No se pudo eliminar la oferta.',
                type: 'error'
            );

            return;
        }

        if ($this->editingId === $id_oferta) {
            $this->resetForm();
            $this->modal('modal-oferta')->close();
        }

        unset($this->ofertas);
        $this->dispatch('toast', message: 'Oferta eliminada correctamente.', type: 'success');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'isEdit']);
        $this->tituloModal = 'Editar oferta';
        $this->ofertaForm = [
            'id_proceso' => '',
            'id_oferta_base' => '',
            'id_turno' => '',
        ];
        $this->resetValidation();
    }

    #[Computed]
    public function ofertas(): LengthAwarePaginator
    {
        return ProcesoOferta::query()
            ->with([
                'proceso.semestre',
                'ofertaBase.modalidadLugarInscripcion.modalidadAdmision',
                'ofertaBase.modalidadLugarInscripcion.lugarInscripcion.sede',
                'ofertaBase.sedeCarrera.sede',
                'ofertaBase.sedeCarrera.carrera',
                'turno',
            ])
            ->withCount('inscripciones')
            ->when(filled($this->buscar), function (Builder $query): void {
                $search = $this->normalizarTexto($this->buscar);

                $query->where(function (Builder $innerQuery) use ($search): void {
                    $innerQuery->whereHas('proceso', function (Builder $procesoQuery) use ($search): void {
                        $procesoQuery->where('nombre_pro', 'like', "%{$search}%")
                            ->orWhere('codigo_pro', 'like', "%{$search}%")
                            ->orWhereHas('semestre', fn (Builder $semestreQuery) => $semestreQuery
                                ->where('nombre_sem', 'like', "%{$search}%"));
                    })->orWhereHas('ofertaBase', function (Builder $ofertaBaseQuery) use ($search): void {
                        $ofertaBaseQuery->where('codigo_externo_ofb', 'like', "%{$search}%")
                            ->orWhereHas('modalidadLugarInscripcion.modalidadAdmision', fn (Builder $modalidadQuery) => $modalidadQuery
                                ->where('nombre_mad', 'like', "%{$search}%"))
                            ->orWhereHas('modalidadLugarInscripcion.lugarInscripcion', fn (Builder $lugarQuery) => $lugarQuery
                                ->where('nombre_lin', 'like', "%{$search}%"))
                            ->orWhereHas('sedeCarrera.sede', fn (Builder $sedeQuery) => $sedeQuery
                                ->where('nombre_sed', 'like', "%{$search}%"))
                            ->orWhereHas('sedeCarrera.carrera', fn (Builder $carreraQuery) => $carreraQuery
                                ->where('nombre_car', 'like', "%{$search}%"));
                    })->orWhereHas('turno', fn (Builder $turnoQuery) => $turnoQuery
                        ->where('nombre_tur', 'like', "%{$search}%"));
                });
            })
            ->when($this->estadosSeleccionados !== [], function (Builder $query): void {
                $query->whereIn('estado_pof', $this->estadosSeleccionados);
            })
            ->orderByDesc('id_pof')
            ->paginate(10);
    }

    public function descripcionOfertaBase(?OfertaBase $ofertaBase): string
    {
        if ($ofertaBase === null) {
            return 'SIN OFERTA BASE';
        }

        $modalidad = $ofertaBase->modalidadLugarInscripcion?->modalidadAdmision?->nombre_mad ?? 'SIN MODALIDAD';
        $carrera = $ofertaBase->sedeCarrera?->carrera?->nombre_car ?? 'SIN CARRERA';
        $sede = $ofertaBase->sedeCarrera?->sede?->nombre_sed ?? 'SIN SEDE';
        $lugar = $ofertaBase->modalidadLugarInscripcion?->lugarInscripcion?->nombre_lin ?? 'SIN LUGAR';

        return "{$modalidad} | {$carrera} | {$sede} | {$lugar}";
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
