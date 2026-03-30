<?php

use App\Enums\EstadoCatalogo;
use App\Models\Accion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
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
     * @var array<string, string>
     */
    public array $permisoForm = [
        'nombre' => '',
        'descripcion' => '',
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
                    $codigo = $this->generarCodigo((string) $value);

                    if ($codigo === '') {
                        $fail('El nombre es obligatorio.');

                        return;
                    }

                    $exists = Accion::query()
                        ->where('codigo_acc', $codigo)
                        ->when($this->editingId, fn (Builder $query) => $query->where('id_acc', '!=', $this->editingId))
                        ->exists();

                    if ($exists) {
                        $fail('Ya existe un permiso con ese nombre.');
                    }
                },
            ],
            'permisoForm.descripcion' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'permisoForm.nombre.required' => 'El nombre es obligatorio.',
            'permisoForm.nombre.unique' => 'Ya existe un permiso con ese nombre.',
        ];
    }

    public function estados(): array
    {
        return EstadoCatalogo::cases();
    }

    public function getEstadosSeleccionadosProperty(): array
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

        $permiso = Accion::query()->findOrFail($id);

        $this->editingId = $permiso->id_acc;
        $this->permisoForm = [
            'nombre' => $permiso->nombre_acc,
            'descripcion' => $permiso->descripcion_acc ?? '',
        ];
        $this->tituloModal = 'Editar permiso';
        $this->isEdit = true;

        $this->modal('modal-permiso')->show();
    }

    public function guardar(): void
    {
        $data = $this->validate();
        $nombre = $this->normalizarTexto($data['permisoForm']['nombre']);
        $descripcion = $this->normalizarTexto($data['permisoForm']['descripcion'] ?? null);
        $permisoActual = $this->editingId ? Accion::query()->findOrFail($this->editingId) : null;

        $payload = [
            'codigo_acc' => $this->generarCodigo($nombre),
            'nombre_acc' => $nombre,
            'modulo_acc' => $this->generarModulo($nombre),
            'descripcion_acc' => $descripcion,
            'estado_acc' => $permisoActual
                ? $permisoActual->estado_acc->value
                : EstadoCatalogo::Habilitado->value,
        ];

        if ($permisoActual) {
            $permisoActual->update($payload);
            $this->dispatch('toast', message: 'Permiso actualizado correctamente.', type: 'success');
        } else {
            Accion::query()->create($payload);
            $this->dispatch('toast', message: 'Permiso registrado correctamente.', type: 'success');
        }

        $this->modal('modal-permiso')->close();
        $this->resetForm();
    }

    #[On('permiso-cambiar-estado')]
    public function cambiarEstado(array $detail): void
    {
        $id = (int) ($detail['id_acc'] ?? 0);
        $permiso = Accion::query()->findOrFail($id);

        $permiso->update([
            'estado_acc' => $permiso->estado_acc === EstadoCatalogo::Habilitado
                ? EstadoCatalogo::Deshabilitado->value
                : EstadoCatalogo::Habilitado->value,
        ]);

        $this->dispatch('toast', message: 'Estado del permiso actualizado.', type: 'success');
    }

    #[On('permiso-eliminar')]
    public function eliminar(array $detail): void
    {
        $id = (int) ($detail['id_acc'] ?? 0);

        Accion::query()->findOrFail($id)->delete();

        if ($this->editingId === $id) {
            $this->resetForm();
            $this->modal('modal-permiso')->close();
        }

        $this->dispatch('toast', message: 'Permiso eliminado correctamente.', type: 'success');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'isEdit']);
        $this->tituloModal = 'Nuevo permiso';
        $this->permisoForm = [
            'nombre' => '',
            'descripcion' => '',
        ];
        $this->resetValidation();
    }

    public function getPermisosProperty()
    {
        return Accion::query()
            ->when($this->buscar !== '', function (Builder $query): void {
                $search = $this->normalizarTexto($this->buscar) ?? '';

                $query->where(function (Builder $innerQuery) use ($search): void {
                    $innerQuery
                        ->where('codigo_acc', 'like', "%{$search}%")
                        ->orWhere('nombre_acc', 'like', "%{$search}%")
                        ->orWhere('modulo_acc', 'like', "%{$search}%")
                        ->orWhere('descripcion_acc', 'like', "%{$search}%");
                });
            })
            ->when($this->estadosSeleccionados !== [], function (Builder $query): void {
                $query->whereIn('estado_acc', $this->estadosSeleccionados);
            })
            ->orderBy('nombre_acc')
            ->paginate(10);
    }

    private function generarCodigo(string $nombre): string
    {
        return Str::upper(str_replace('-', '_', $this->generarModulo($nombre)));
    }

    private function generarModulo(string $nombre): string
    {
        return Str::slug($this->normalizarTexto($nombre) ?? '');
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
