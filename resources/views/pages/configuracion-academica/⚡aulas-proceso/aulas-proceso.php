<?php

use App\Enums\EstadoCatalogo;
use App\Models\Proceso;
use App\Models\ProcesoAula;
use App\Models\Semestre;
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
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('layouts.app')]
#[Title('AULAS POR PROCESO')]
class extends Component
{
    use AuthorizesPermisos;
    use WithPagination;

    #[Url(as: 'q')]
    public string $buscar = '';

    #[Url(as: 'proceso')]
    public string $idProceso = '';

    #[Url(as: 'semestre')]
    public string $idSemestre = '';

    #[Url(as: 'turno')]
    public string $idTurno = '';

    /**
     * @var array<string, bool>
     */
    #[Url(as: 'estado')]
    public array $filtroEstado = [];

    public ?int $editingId = null;

    /**
     * @var array{capacidad_pau: string}
     */
    public array $aulaForm = [
        'capacidad_pau' => '',
    ];

    public function mount(): void
    {
        $this->autorizarPermiso('aula_proceso', 'indice');
    }

    public function updatedBuscar(): void
    {
        $this->resetPage();
    }

    public function updatedIdProceso(): void
    {
        $this->resetPage();
    }

    public function updatedIdSemestre(): void
    {
        $this->idProceso = '';
        $this->idTurno = '';
        $this->resetPage();
    }

    public function updatedIdTurno(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroEstado(): void
    {
        $this->resetPage();
    }

    public function limpiarFiltrosAcademicos(): void
    {
        $this->idSemestre = '';
        $this->idProceso = '';
        $this->idTurno = '';
        $this->resetPage();
    }

    public function limpiarFiltroEstado(): void
    {
        $this->filtroEstado = [];
    }

    public function estados(): array
    {
        return EstadoCatalogo::cases();
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
    public function semestresDisponibles(): Collection
    {
        return Semestre::query()
            ->whereHas('procesos')
            ->orderByDesc('fecha_inicio_inscripcion_sem')
            ->orderByDesc('id_sem')
            ->get();
    }

    #[Computed]
    public function procesosDisponibles(): Collection
    {
        return Proceso::query()
            ->with('semestre')
            ->when(filled($this->idSemestre), fn (Builder $query) => $query->where('id_semestre', (int) $this->idSemestre))
            ->orderByDesc('id_pro')
            ->get();
    }

    #[Computed]
    public function turnosDisponibles(): Collection
    {
        return Turno::query()
            ->whereHas('procesoAulas', function (Builder $query): void {
                if (filled($this->idProceso)) {
                    $query->where('id_proceso', (int) $this->idProceso);

                    return;
                }

                if (filled($this->idSemestre)) {
                    $query->whereHas('proceso', fn (Builder $procesoQuery) => $procesoQuery->where('id_semestre', (int) $this->idSemestre));
                }
            })
            ->where('estado_tur', EstadoCatalogo::Habilitado->value)
            ->orderBy('nombre_tur')
            ->get();
    }

    #[Computed]
    public function asignaciones(): LengthAwarePaginator
    {
        return ProcesoAula::query()
            ->with(['proceso.semestre', 'aula', 'turno'])
            ->withCount('inscripciones')
            ->when(filled($this->buscar), function (Builder $query): void {
                $search = $this->normalizarTexto($this->buscar);

                $query->where(function (Builder $innerQuery) use ($search): void {
                    $innerQuery->whereHas('aula', function (Builder $aulaQuery) use ($search): void {
                        $aulaQuery->where('nombre_aul', 'like', "%{$search}%")
                            ->orWhere('lugar_aul', 'like', "%{$search}%")
                            ->orWhere('piso_aul', 'like', "%{$search}%");
                    })->orWhereHas('proceso', function (Builder $procesoQuery) use ($search): void {
                        $procesoQuery->where('nombre_pro', 'like', "%{$search}%")
                            ->orWhere('codigo_pro', 'like', "%{$search}%")
                            ->orWhereHas('semestre', fn (Builder $semestreQuery) => $semestreQuery
                                ->where('nombre_sem', 'like', "%{$search}%"));
                    });
                });
            })
            ->when(filled($this->idSemestre), function (Builder $query): void {
                $query->whereHas('proceso', fn (Builder $procesoQuery) => $procesoQuery
                    ->where('id_semestre', (int) $this->idSemestre));
            })
            ->when(filled($this->idProceso), fn (Builder $query) => $query->where('id_proceso', (int) $this->idProceso))
            ->when(filled($this->idTurno), fn (Builder $query) => $query->where('id_turno', (int) $this->idTurno))
            ->when($this->estadosSeleccionados !== [], function (Builder $query): void {
                $query->whereIn('estado_pau', $this->estadosSeleccionados);
            })
            ->orderByDesc('id_pau')
            ->paginate(10);
    }

    public function cargarAsignacion(int $id): void
    {
        $this->autorizarPermiso('aula_proceso', 'editar');
        $this->resetValidation();

        $asignacion = ProcesoAula::query()->findOrFail($id);
        $this->editingId = $asignacion->id_pau;
        $this->aulaForm['capacidad_pau'] = (string) $asignacion->capacidad_pau;

        $this->modal('modal-asignacion')->show();
    }

    public function guardar(): void
    {
        $this->autorizarPermiso('aula_proceso', 'editar');
        $this->validate([
            'aulaForm.capacidad_pau' => ['required', 'integer', 'min:1'],
        ], [
            'aulaForm.capacidad_pau.required' => 'La capacidad es requerida.',
            'aulaForm.capacidad_pau.integer' => 'La capacidad debe ser un numero entero.',
            'aulaForm.capacidad_pau.min' => 'La capacidad debe ser al menos 1.',
        ]);

        $asignacion = ProcesoAula::query()->findOrFail($this->editingId);
        $asignacion->update([
            'capacidad_pau' => (int) $this->aulaForm['capacidad_pau'],
        ]);

        $this->dispatch('toast', message: 'Capacidad actualizada correctamente.', type: 'success');
        $this->modal('modal-asignacion')->close();
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'aulaForm']);
        $this->resetValidation();
    }

    #[On('asignacion-cambiar-estado')]
    public function cambiarEstado(?int $id_asignacion = null): void
    {
        $this->autorizarPermiso('aula_proceso', 'estado');

        if ($id_asignacion === null) {
            $this->dispatch('toast', message: 'ID no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_asignacion): void {
                $asignacion = ProcesoAula::query()->findOrFail($id_asignacion);
                $nuevoEstado = match ($asignacion->estado_pau) {
                    EstadoCatalogo::Habilitado => EstadoCatalogo::Deshabilitado->value,
                    EstadoCatalogo::Deshabilitado => EstadoCatalogo::Habilitado->value,
                };
                $asignacion->update(['estado_pau' => $nuevoEstado]);
            });
        } catch (Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'Error al cambiar estado.', type: 'error');

            return;
        }

        unset($this->asignaciones);
        $this->dispatch('toast', message: 'Estado actualizado.', type: 'success');
    }

    #[On('asignacion-eliminar')]
    public function eliminar(?int $id_asignacion = null): void
    {
        $this->autorizarPermiso('aula_proceso', 'eliminar');

        if ($id_asignacion === null) {
            $this->dispatch('toast', message: 'ID no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_asignacion): void {
                $asignacion = ProcesoAula::query()->withCount('inscripciones')->findOrFail($id_asignacion);
                if ($asignacion->inscripciones_count > 0) {
                    throw new RuntimeException('No se puede eliminar porque existen inscripciones en esta aula.');
                }

                $asignacion->delete();
            });
        } catch (Throwable $throwable) {
            report_if(! $throwable instanceof RuntimeException, $throwable);
            $this->dispatch('toast', message: $throwable->getMessage() ?: 'Error al eliminar asignación.', type: 'error');

            return;
        }

        unset($this->asignaciones);
        $this->dispatch('toast', message: 'Asignación eliminada correctamente.', type: 'success');
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
