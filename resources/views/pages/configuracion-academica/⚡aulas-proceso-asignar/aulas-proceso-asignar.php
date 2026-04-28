<?php

use App\Enums\EstadoCatalogo;
use App\Models\Aula;
use App\Models\Proceso;
use App\Models\ProcesoAula;
use App\Models\Semestre;
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
#[Title('ASIGNAR AULAS')]
class extends Component
{
    use AuthorizesPermisos;

    public string $buscar = '';

    public string $idProceso = '';

    public string $idSemestre = '';

    /**
     * @var array<int, bool>
     */
    public array $seleccionadas = [];

    /**
     * @var array<int, string>
     */
    public array $capacidades = [];

    public function mount(): void
    {
        $this->autorizarPermiso('aula_proceso', 'crear');
    }

    public function updatedBuscar(): void
    {
    }

    public function updatedIdSemestre(): void
    {
        $this->idProceso = '';
        $this->limpiarSeleccionadasVisibles();
    }

    public function updatedIdProceso(): void
    {
        $this->limpiarSeleccionadasVisibles();
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
    public function procesoSeleccionado(): ?Proceso
    {
        if (blank($this->idProceso)) {
            return null;
        }

        return Proceso::query()
            ->with('semestre')
            ->find((int) $this->idProceso);
    }

    #[Computed]
    public function aulas(): Collection
    {
        return Aula::query()
            ->when(filled($this->buscar), function (Builder $query): void {
                $search = $this->normalizarTexto($this->buscar);

                $query->where('nombre_aul', 'like', "%{$search}%")
                    ->orWhere('lugar_aul', 'like', "%{$search}%")
                    ->orWhere('piso_aul', 'like', "%{$search}%");
            })
            ->where('estado_aul', EstadoCatalogo::Habilitado->value)
            ->orderBy('lugar_aul')
            ->orderBy('piso_aul')
            ->orderBy('orden_aul')
            ->get();
    }

    public function aulaYaAsignada(int $id_aula): bool
    {
        if (blank($this->idProceso)) {
            return false;
        }

        return ProcesoAula::query()
            ->where('id_proceso', (int) $this->idProceso)
            ->where('id_aula', $id_aula)
            ->exists();
    }

    #[Computed]
    public function idsAulasSeleccionables(): array
    {
        if (blank($this->idProceso)) {
            return [];
        }

        return $this->aulas
            ->filter(fn (Aula $aula) => ! $this->aulaYaAsignada($aula->id_aul))
            ->pluck('id_aul')
            ->all();
    }

    public function seleccionarTodasLasAulasVisibles(): void
    {
        foreach ($this->idsAulasSeleccionables as $id_aula) {
            $this->seleccionadas[$id_aula] = true;
            if (! isset($this->capacidades[$id_aula]) || blank($this->capacidades[$id_aula])) {
                $this->capacidades[$id_aula] = '40';
            }
        }
    }

    public function limpiarSeleccionadasVisibles(): void
    {
        $this->seleccionadas = [];
        $this->capacidades = [];
    }

    public function updatedSeleccionadas(): void
    {
        foreach ($this->seleccionadas as $id_aula => $seleccionada) {
            if ($seleccionada && (! isset($this->capacidades[$id_aula]) || blank($this->capacidades[$id_aula]))) {
                $this->capacidades[$id_aula] = '40';
            }
        }
    }

    public function guardar(): void
    {
        $this->autorizarPermiso('aula_proceso', 'crear');
        $this->resetValidation();

        if (blank($this->idProceso)) {
            $this->addError('idProceso', 'Debe seleccionar un proceso.');

            return;
        }

        $aulasSeleccionadas = collect($this->seleccionadas)
            ->filter(fn ($selected) => $selected)
            ->keys()
            ->all();

        if (count($aulasSeleccionadas) === 0) {
            $this->addError('seleccionadas', 'Debe seleccionar al menos un aula.');

            return;
        }

        foreach ($aulasSeleccionadas as $id_aula) {
            if (! isset($this->capacidades[$id_aula]) || blank($this->capacidades[$id_aula]) || (int) $this->capacidades[$id_aula] < 1) {
                $this->addError('seleccionadas', 'Todas las aulas seleccionadas deben tener una capacidad válida (mayor a 0).');

                return;
            }
        }

        try {
            DB::transaction(function () use ($aulasSeleccionadas): void {
                foreach ($aulasSeleccionadas as $id_aula) {
                    if ($this->aulaYaAsignada($id_aula)) {
                        continue;
                    }

                    ProcesoAula::query()->create([
                        'id_proceso' => (int) $this->idProceso,
                        'id_aula' => (int) $id_aula,
                        'capacidad_pau' => (int) $this->capacidades[$id_aula],
                        'estado_pau' => EstadoCatalogo::Habilitado->value,
                    ]);
                }
            });
        } catch (\Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudieron asignar las aulas. Intente nuevamente.', type: 'error');

            return;
        }

        $this->dispatch('toast', message: 'Aulas asignadas correctamente.', type: 'success');
        $this->limpiarSeleccionadasVisibles();
        unset($this->aulas);
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
