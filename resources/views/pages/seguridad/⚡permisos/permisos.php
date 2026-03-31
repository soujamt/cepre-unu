<?php

use App\Enums\EstadoCatalogo;
use App\Models\Accion;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('layouts.app')]
#[Title('PERMISOS')]
class extends Component
{
    use WithPagination;

    public string $buscar = '';

    /**
     * @var array<string, bool>
     */
    public array $filtroEstado = [];

    /**
     * @var array{nombre: string, acciones: array<int, int|string>}
     */
    public array $permisoForm = [
        'nombre' => '',
        'acciones' => [],
    ];

    public string $tituloModal = 'Nuevo permiso';

    public bool $isEdit = false;

    public ?int $editingId = null;

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
            'permisoForm.nombre' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $codigo = $this->generarClave((string) $value);

                    if ($codigo === '') {
                        $fail('El nombre es obligatorio.');

                        return;
                    }

                    $exists = Permiso::query()
                        ->where('clave_permiso', $codigo)
                        ->when($this->editingId, fn (Builder $query) => $query->where('id_permiso', '!=', $this->editingId))
                        ->exists();

                    if ($exists) {
                        $fail('Ya existe un permiso con ese nombre.');
                    }
                },
            ],
            'permisoForm.acciones' => ['required', 'array', 'min:1'],
            'permisoForm.acciones.*' => ['integer', 'exists:tbl_accion,id_acc'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'permisoForm.nombre.required' => 'El nombre es obligatorio.',
            'permisoForm.acciones.required' => 'Debe seleccionar al menos una acción.',
            'permisoForm.acciones.min' => 'Debe seleccionar al menos una acción.',
        ];
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

    public function limpiarFiltroEstado(): void
    {
        $this->filtroEstado = [];
    }

    public function cargarPermiso(?int $id = null): void
    {
        $this->resetValidation();

        if ($id === null) {
            $this->resetForm();
            $this->tituloModal = 'Nuevo permiso';
            $this->isEdit = false;

            $this->modal('modal-permiso')->show();

            return;
        }

        $permiso = Permiso::query()->findOrFail($id);

        $this->editingId = $permiso->id_permiso;
        $this->permisoForm = [
            'nombre' => $permiso->nombre_permiso,
            'acciones' => $permiso->permisoAcciones()
                ->where('estado_peracc', EstadoCatalogo::Habilitado->value)
                ->pluck('id_accion')
                ->map(fn (int $idAccion): string => (string) $idAccion)
                ->all(),
        ];
        $this->tituloModal = 'Editar permiso';
        $this->isEdit = true;

        $this->modal('modal-permiso')->show();
    }

    public function guardar(): void
    {
        $data = $this->validate();

        try {
            DB::transaction(function () use ($data): void {
                $nombre = $this->normalizarTexto($data['permisoForm']['nombre']);
                $accionesSeleccionadas = collect($data['permisoForm']['acciones'])
                    ->map(fn (mixed $id): int => (int) $id)
                    ->values()
                    ->all();
                $permisoActual = $this->editingId ? Permiso::query()->findOrFail($this->editingId) : null;

                $permiso = $this->guardarPermiso($permisoActual, $nombre);

                $this->sincronizarAcciones($permiso, $accionesSeleccionadas);
            });
        } catch (\Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo guardar el permiso. Intente nuevamente.', type: 'error');

            return;
        }

        $this->dispatch(
            'toast',
            message: $this->isEdit ? 'Permiso actualizado correctamente.' : 'Permiso registrado correctamente.',
            type: 'success'
        );

        unset($this->permisos);
        $this->modal('modal-permiso')->close();
        $this->resetForm();
    }

    #[On('permiso-cambiar-estado')]
    public function cambiarEstado(?int $id_permiso = null): void
    {
        if ($id_permiso === null) {
            $this->dispatch('toast', message: 'ID de permiso no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_permiso): void {
                $permiso = Permiso::query()->findOrFail($id_permiso);

                $permiso->update([
                    'estado_permiso' => $permiso->estado_permiso === EstadoCatalogo::Habilitado
                        ? EstadoCatalogo::Deshabilitado->value
                        : EstadoCatalogo::Habilitado->value,
                ]);
            });
        } catch (\Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo cambiar el estado del permiso.', type: 'error');

            return;
        }

        unset($this->permisos);
        $this->dispatch('toast', message: 'Estado del permiso actualizado.', type: 'success');
    }

    #[On('permiso-eliminar')]
    public function eliminar(?int $id_permiso = null): void
    {
        if ($id_permiso === null) {
            $this->dispatch('toast', message: 'ID de permiso no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_permiso): void {
                Permiso::query()->findOrFail($id_permiso)->delete();
            });
        } catch (\Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo eliminar el permiso.', type: 'error');

            return;
        }

        if ($this->editingId === $id_permiso) {
            $this->resetForm();
            $this->modal('modal-permiso')->close();
        }

        unset($this->permisos);
        $this->dispatch('toast', message: 'Permiso eliminado correctamente.', type: 'success');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'isEdit']);
        $this->tituloModal = 'Nuevo permiso';
        $this->permisoForm = [
            'nombre' => '',
            'acciones' => [],
        ];
        $this->resetValidation();
    }

    #[Computed]
    public function accionesDisponibles(): Collection
    {
        return Accion::query()
            ->habilitadas()
            ->get();
    }

    #[Computed]
    public function permisos(): LengthAwarePaginator
    {
        return Permiso::query()
            ->with([
                'permisoAcciones' => fn ($query) => $query
                    ->where('estado_peracc', EstadoCatalogo::Habilitado->value)
                    ->with('accion'),
            ])
            ->withCount([
                'permisoAcciones as acciones_habilitadas_count' => fn (Builder $query) => $query
                    ->where('estado_peracc', EstadoCatalogo::Habilitado->value),
            ])
            ->buscar($this->normalizarTexto($this->buscar))
            ->conEstados($this->estadosSeleccionados)
            ->orderBy('nombre_permiso')
            ->paginate(10);
    }

    private function generarClave(string $nombre): string
    {
        return Str::replace('-', '_', Str::slug($this->normalizarTexto($nombre) ?? ''));
    }

    private function guardarPermiso(?Permiso $permisoActual, string $nombre): Permiso
    {
        $nombreMayuscula = Str::upper($nombre);

        $payload = [
            'clave_permiso' => $this->generarClave($nombre),
            'nombre_permiso' => $nombreMayuscula,
            'estado_permiso' => $permisoActual
                ? $permisoActual->estado_permiso->value
                : EstadoCatalogo::Habilitado->value,
        ];

        if ($permisoActual) {
            $permisoActual->update($payload);

            return $permisoActual->refresh();
        }

        return Permiso::query()->create($payload);
    }

    /**
     * @param  array<int, int>  $accionesSeleccionadas
     */
    private function sincronizarAcciones(Permiso $permiso, array $accionesSeleccionadas): void
    {
        $acciones = Accion::query()
            ->whereIn('id_acc', $accionesSeleccionadas)
            ->get()
            ->keyBy('id_acc');

        $permisoAccionesExistentes = $permiso->permisoAcciones()
            ->withTrashed()
            ->get()
            ->keyBy('id_accion');

        foreach ($accionesSeleccionadas as $idAccion) {
            $accion = $acciones->get($idAccion);

            if (! $accion instanceof Accion) {
                continue;
            }

            $permisoAccion = $permisoAccionesExistentes->get($idAccion);
            $payload = [
                'clave_peracc' => "{$permiso->clave_permiso}_{$accion->clave_accion}",
                'estado_peracc' => EstadoCatalogo::Habilitado->value,
                'eliminado_en' => null,
            ];

            if ($permisoAccion instanceof PermisoAccion) {
                $permisoAccion->update($payload);

                continue;
            }

            $permiso->permisoAcciones()->create([
                'id_accion' => $accion->id_acc,
                ...$payload,
            ]);
        }

        $permiso->permisoAcciones()
            ->whereNotIn('id_accion', $accionesSeleccionadas)
            ->update([
                'estado_peracc' => EstadoCatalogo::Deshabilitado->value,
            ]);
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
