<?php

use App\Enums\EstadoCatalogo;
use App\Models\Rol;
use App\Traits\AuthorizesPermisos;
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
#[Title('ROLES')]
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
     * @var array{nombre: string, descripcion: string}
     */
    public array $rolForm = [
        'nombre' => '',
        'descripcion' => '',
    ];

    public string $tituloModal = 'Nuevo rol';

    public bool $isEdit = false;

    public ?int $editingId = null;

    public bool $isAdministradorEdit = false;

    public function mount(): void
    {
        $this->autorizarPermiso('roles', 'indice');
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
            'rolForm.nombre' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $nombre = Str::upper($this->normalizarTexto((string) $value) ?? '');

                    if ($nombre === '') {
                        $fail('El nombre es obligatorio.');

                        return;
                    }

                    $exists = Rol::query()
                        ->where('nombre_rol', $nombre)
                        ->when($this->editingId, fn (Builder $query) => $query->where('id_rol', '!=', $this->editingId))
                        ->exists();

                    if ($exists) {
                        $fail('Ya existe un rol con ese nombre.');
                    }
                },
            ],
            'rolForm.descripcion' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'rolForm.nombre.required' => 'El nombre es obligatorio.',
            'rolForm.descripcion.max' => 'La descripción no debe exceder los 500 caracteres.',
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

    public function cargarRol(?int $id = null): void
    {
        $this->autorizarPermiso('roles', $id === null ? 'crear' : 'editar');
        $this->resetValidation();

        if ($id === null) {
            $this->resetForm();
            $this->tituloModal = 'Nuevo rol';
            $this->isEdit = false;
            $this->modal('modal-rol')->show();

            return;
        }

        $rol = Rol::query()->findOrFail($id);

        $this->editingId = $rol->id_rol;
        $this->isAdministradorEdit = $rol->nombre_rol === 'ADMINISTRADOR';
        $this->rolForm = [
            'nombre' => $rol->nombre_rol,
            'descripcion' => $rol->descripcion_rol ?? '',
        ];
        $this->tituloModal = 'Editar rol';
        $this->isEdit = true;

        $this->modal('modal-rol')->show();
    }

    public function guardar(): void
    {
        $this->autorizarPermiso('roles', $this->isEdit ? 'editar' : 'crear');
        $data = $this->validate();

        try {
            DB::transaction(function () use ($data): void {
                $nombre = Str::upper($this->normalizarTexto($data['rolForm']['nombre']) ?? '');
                $descripcion = $this->normalizarTexto($data['rolForm']['descripcion']);
                $payload = [
                    'nombre_rol' => $nombre,
                    'descripcion_rol' => $descripcion,
                ];

                if ($this->editingId) {
                    $rol = Rol::query()->findOrFail($this->editingId);

                    if ($rol->nombre_rol === 'ADMINISTRADOR') {
                        $payload['nombre_rol'] = $rol->nombre_rol;
                    }

                    $rol->update($payload);

                    return;
                }

                Rol::query()->create([
                    ...$payload,
                    'estado_rol' => EstadoCatalogo::Habilitado->value,
                ]);
            });
        } catch (\Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo guardar el rol. Intente nuevamente.', type: 'error');

            return;
        }

        $this->dispatch(
            'toast',
            message: $this->isEdit ? 'Rol actualizado correctamente.' : 'Rol registrado correctamente.',
            type: 'success'
        );

        unset($this->roles);
        $this->modal('modal-rol')->close();
        $this->resetForm();
    }

    #[On('rol-cambiar-estado')]
    public function cambiarEstado(?int $id_rol = null): void
    {
        $this->autorizarPermiso('roles', 'estado');

        if ($id_rol === null) {
            $this->dispatch('toast', message: 'ID de rol no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_rol): void {
                $rol = Rol::query()->findOrFail($id_rol);

                if ($rol->nombre_rol === 'ADMINISTRADOR') {
                    throw new \RuntimeException('El rol ADMINISTRADOR no puede cambiar de estado.');
                }

                $rol->update([
                    'estado_rol' => $rol->estado_rol === EstadoCatalogo::Habilitado
                        ? EstadoCatalogo::Deshabilitado->value
                        : EstadoCatalogo::Habilitado->value,
                ]);
            });
        } catch (\Throwable $throwable) {
            report_if(! $throwable instanceof \RuntimeException, $throwable);
            $this->dispatch('toast', message: $throwable->getMessage() ?: 'No se pudo cambiar el estado del rol.', type: 'error');

            return;
        }

        unset($this->roles);
        $this->dispatch('toast', message: 'Estado del rol actualizado.', type: 'success');
    }

    #[On('rol-eliminar')]
    public function eliminar(?int $id_rol = null): void
    {
        $this->autorizarPermiso('roles', 'eliminar');

        if ($id_rol === null) {
            $this->dispatch('toast', message: 'ID de rol no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_rol): void {
                $rol = Rol::query()->findOrFail($id_rol);

                if ($rol->nombre_rol === 'ADMINISTRADOR') {
                    throw new \RuntimeException('El rol ADMINISTRADOR no puede eliminarse.');
                }

                $rol->delete();
            });
        } catch (\Throwable $throwable) {
            report_if(! $throwable instanceof \RuntimeException, $throwable);
            $this->dispatch('toast', message: $throwable->getMessage() ?: 'No se pudo eliminar el rol.', type: 'error');

            return;
        }

        if ($this->editingId === $id_rol) {
            $this->resetForm();
            $this->modal('modal-rol')->close();
        }

        unset($this->roles);
        $this->dispatch('toast', message: 'Rol eliminado correctamente.', type: 'success');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'isEdit', 'isAdministradorEdit']);
        $this->tituloModal = 'Nuevo rol';
        $this->rolForm = [
            'nombre' => '',
            'descripcion' => '',
        ];
        $this->resetValidation();
    }

    #[Computed]
    public function roles(): LengthAwarePaginator
    {
        return Rol::query()
            ->withCount([
                'usuarios',
                'rolPermisoAcciones as acciones_asignadas_count' => fn (Builder $query) => $query
                    ->whereHas('permisoAccion', fn (Builder $permissionActionQuery) => $permissionActionQuery
                        ->where('estado_peracc', EstadoCatalogo::Habilitado->value)
                        ->whereHas('permiso', fn (Builder $permissionQuery) => $permissionQuery->where('estado_permiso', EstadoCatalogo::Habilitado->value))),
            ])
            ->when(filled($this->buscar), function (Builder $query): void {
                $search = $this->normalizarTexto($this->buscar);

                $query->where(function (Builder $innerQuery) use ($search): void {
                    $innerQuery->where('nombre_rol', 'like', "%{$search}%")
                        ->orWhere('descripcion_rol', 'like', "%{$search}%");
                });
            })
            ->when($this->estadosSeleccionados !== [], function (Builder $query): void {
                $query->whereIn('estado_rol', $this->estadosSeleccionados);
            })
            ->orderByDesc('id_rol')
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
