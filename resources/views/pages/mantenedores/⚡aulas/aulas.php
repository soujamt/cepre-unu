<?php

use App\Enums\EstadoCatalogo;
use App\Enums\LugarAula;
use App\Enums\PisoAula;
use App\Models\Aula;
use App\Traits\AuthorizesPermisos;
use Illuminate\Database\Eloquent\Builder;
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
#[Title('AULAS')]
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
     * @var array{nombre: string, piso: string, lugar: string, orden: string}
     */
    public array $aulaForm = [
        'nombre' => '',
        'piso' => '',
        'lugar' => '',
        'orden' => '',
    ];

    public string $tituloModal = 'Nueva aula';

    public bool $isEdit = false;

    public ?int $editingId = null;

    public function mount(): void
    {
        $this->autorizarPermiso('aula', 'indice');
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
            'aulaForm.nombre' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $nombre = $this->normalizarTexto((string) $value) ?? '';

                    if ($nombre === '') {
                        $fail('El nombre es obligatorio.');

                        return;
                    }

                    $exists = Aula::query()
                        ->withTrashed()
                        ->whereRaw('UPPER(nombre_aul) = ?', [mb_strtoupper($nombre, 'UTF-8')])
                        ->when($this->editingId, fn (Builder $query) => $query->where('id_aul', '!=', $this->editingId))
                        ->exists();

                    if ($exists) {
                        $fail('Ya existe un aula con ese nombre.');
                    }
                },
            ],
            'aulaForm.piso' => ['required', 'string', 'in:'.collect(PisoAula::cases())->pluck('value')->implode(',')],
            'aulaForm.lugar' => ['required', 'string', 'in:'.collect(LugarAula::cases())->pluck('value')->implode(',')],
            'aulaForm.orden' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'aulaForm.nombre.required' => 'El nombre es obligatorio.',
            'aulaForm.piso.required' => 'El piso es obligatorio.',
            'aulaForm.piso.in' => 'Selecciona un piso valido.',
            'aulaForm.lugar.required' => 'El lugar es obligatorio.',
            'aulaForm.lugar.in' => 'Selecciona un lugar valido.',
            'aulaForm.orden.required' => 'El orden es obligatorio.',
            'aulaForm.orden.integer' => 'El orden debe ser un numero entero.',
            'aulaForm.orden.min' => 'El orden debe ser mayor o igual a 1.',
        ];
    }

    public function estados(): array
    {
        return EstadoCatalogo::cases();
    }

    public function pisos(): array
    {
        return PisoAula::cases();
    }

    public function lugares(): array
    {
        return LugarAula::cases();
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

    public function limpiarFiltroEstado(): void
    {
        $this->filtroEstado = [];
    }

    public function cargarAula(?int $id = null): void
    {
        $this->autorizarPermiso('aula', $id === null ? 'crear' : 'editar');
        $this->resetValidation();

        if ($id === null) {
            $this->resetForm();
            $this->tituloModal = 'Nueva aula';
            $this->isEdit = false;
            $this->modal('modal-aula')->show();

            return;
        }

        $aula = Aula::query()->findOrFail($id);

        $this->editingId = $aula->id_aul;
        $this->aulaForm = [
            'nombre' => $aula->nombre_aul,
            'piso' => $aula->piso_aul->value,
            'lugar' => $aula->lugar_aul->value,
            'orden' => (string) $aula->orden_aul,
        ];
        $this->tituloModal = 'Editar aula';
        $this->isEdit = true;

        $this->modal('modal-aula')->show();
    }

    public function guardar(): void
    {
        $this->autorizarPermiso('aula', $this->isEdit ? 'editar' : 'crear');
        $data = $this->validate();

        try {
            DB::transaction(function () use ($data): void {
                $payload = [
                    'nombre_aul' => mb_strtoupper($this->normalizarTexto($data['aulaForm']['nombre']) ?? '', 'UTF-8'),
                    'piso_aul' => $data['aulaForm']['piso'],
                    'lugar_aul' => $data['aulaForm']['lugar'],
                    'orden_aul' => (int) $data['aulaForm']['orden'],
                ];

                if ($this->editingId !== null) {
                    Aula::query()->findOrFail($this->editingId)->update($payload);

                    return;
                }

                Aula::query()->create([
                    ...$payload,
                    'estado_aul' => EstadoCatalogo::Habilitado->value,
                ]);
            });
        } catch (Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo guardar el aula. Intente nuevamente.', type: 'error');

            return;
        }

        $this->dispatch(
            'toast',
            message: $this->isEdit ? 'Aula actualizada correctamente.' : 'Aula registrada correctamente.',
            type: 'success'
        );

        unset($this->aulas);
        $this->modal('modal-aula')->close();
        $this->resetForm();
    }

    #[On('aula-cambiar-estado')]
    public function cambiarEstado(?int $id_aula = null): void
    {
        $this->autorizarPermiso('aula', 'estado');

        if ($id_aula === null) {
            $this->dispatch('toast', message: 'ID de aula no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_aula): void {
                $aula = Aula::query()->findOrFail($id_aula);

                $aula->update([
                    'estado_aul' => $aula->estado_aul === EstadoCatalogo::Habilitado
                        ? EstadoCatalogo::Deshabilitado->value
                        : EstadoCatalogo::Habilitado->value,
                ]);
            });
        } catch (Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo cambiar el estado del aula.', type: 'error');

            return;
        }

        unset($this->aulas);
        $this->dispatch('toast', message: 'Estado del aula actualizado.', type: 'success');
    }

    #[On('aula-eliminar')]
    public function eliminar(?int $id_aula = null): void
    {
        $this->autorizarPermiso('aula', 'eliminar');

        if ($id_aula === null) {
            $this->dispatch('toast', message: 'ID de aula no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_aula): void {
                $aula = Aula::query()
                    ->withCount('procesoAulas')
                    ->findOrFail($id_aula);

                if ($aula->proceso_aulas_count > 0) {
                    throw new RuntimeException('El aula tiene asignaciones por proceso y no puede eliminarse.');
                }

                $aula->delete();
            });
        } catch (Throwable $throwable) {
            report_if(! $throwable instanceof RuntimeException, $throwable);
            $this->dispatch(
                'toast',
                message: $throwable->getMessage() ?: 'No se pudo eliminar el aula.',
                type: 'error'
            );

            return;
        }

        if ($this->editingId === $id_aula) {
            $this->resetForm();
            $this->modal('modal-aula')->close();
        }

        unset($this->aulas);
        $this->dispatch('toast', message: 'Aula eliminada correctamente.', type: 'success');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'isEdit']);
        $this->tituloModal = 'Nueva aula';
        $this->aulaForm = [
            'nombre' => '',
            'piso' => '',
            'lugar' => '',
            'orden' => '',
        ];
        $this->resetValidation();
    }

    #[Computed]
    public function aulas(): LengthAwarePaginator
    {
        return Aula::query()
            ->withCount('procesoAulas')
            ->when(filled($this->buscar), function (Builder $query): void {
                $search = $this->normalizarTexto($this->buscar);

                $query->where(function (Builder $innerQuery) use ($search): void {
                    $innerQuery->where('nombre_aul', 'like', "%{$search}%")
                        ->orWhere('piso_aul', 'like', "%{$search}%")
                        ->orWhere('lugar_aul', 'like', "%{$search}%");
                });
            })
            ->when($this->estadosSeleccionados !== [], function (Builder $query): void {
                $query->whereIn('estado_aul', $this->estadosSeleccionados);
            })
            ->orderBy('orden_aul')
            ->orderBy('nombre_aul')
            ->paginate(10);
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
