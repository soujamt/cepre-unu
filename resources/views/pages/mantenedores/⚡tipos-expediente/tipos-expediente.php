<?php

use App\Enums\EstadoCatalogo;
use App\Models\TipoExpediente;
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
#[Title('TIPOS DE EXPEDIENTE')]
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
     * @var array{codigo: string, nombre: string, descripcion: string, obligatorio: bool}
     */
    public array $tipoExpedienteForm = [
        'codigo' => '',
        'nombre' => '',
        'descripcion' => '',
        'obligatorio' => true,
    ];

    public string $tituloModal = 'Nuevo tipo de expediente';

    public bool $isEdit = false;

    public ?int $editingId = null;

    public function mount(): void
    {
        $this->autorizarPermiso('tipo_expediente', 'indice');
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
            'tipoExpedienteForm.codigo' => [
                'required',
                'string',
                'max:50',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $codigo = $this->normalizarCodigo((string) $value);

                    if ($codigo === '') {
                        $fail('El codigo es obligatorio.');

                        return;
                    }

                    $exists = TipoExpediente::query()
                        ->withTrashed()
                        ->where('codigo_tex', $codigo)
                        ->when($this->editingId, fn (Builder $query) => $query->where('id_tex', '!=', $this->editingId))
                        ->exists();

                    if ($exists) {
                        $fail('Ya existe un tipo de expediente con ese codigo.');
                    }
                },
            ],
            'tipoExpedienteForm.nombre' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $nombre = $this->mayusculas($this->normalizarTexto((string) $value) ?? '');

                    if ($nombre === '') {
                        $fail('El nombre es obligatorio.');

                        return;
                    }

                    $exists = TipoExpediente::query()
                        ->withTrashed()
                        ->where('nombre_tex', $nombre)
                        ->when($this->editingId, fn (Builder $query) => $query->where('id_tex', '!=', $this->editingId))
                        ->exists();

                    if ($exists) {
                        $fail('Ya existe un tipo de expediente con ese nombre.');
                    }
                },
            ],
            'tipoExpedienteForm.descripcion' => ['nullable', 'string', 'max:1000'],
            'tipoExpedienteForm.obligatorio' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'tipoExpedienteForm.codigo.required' => 'El codigo es obligatorio.',
            'tipoExpedienteForm.nombre.required' => 'El nombre es obligatorio.',
            'tipoExpedienteForm.descripcion.max' => 'La descripcion no debe exceder los 1000 caracteres.',
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

    public function cargarTipoExpediente(?int $id = null): void
    {
        $this->autorizarPermiso('tipo_expediente', $id === null ? 'crear' : 'editar');
        $this->resetValidation();

        if ($id === null) {
            $this->resetForm();
            $this->tituloModal = 'Nuevo tipo de expediente';
            $this->isEdit = false;
            $this->modal('modal-tipo-expediente')->show();

            return;
        }

        $tipoExpediente = TipoExpediente::query()->findOrFail($id);

        $this->editingId = $tipoExpediente->id_tex;
        $this->tipoExpedienteForm = [
            'codigo' => $tipoExpediente->codigo_tex,
            'nombre' => $tipoExpediente->nombre_tex,
            'descripcion' => $tipoExpediente->descripcion_tex ?? '',
            'obligatorio' => $tipoExpediente->obligatorio_tex,
        ];
        $this->tituloModal = 'Editar tipo de expediente';
        $this->isEdit = true;

        $this->modal('modal-tipo-expediente')->show();
    }

    public function guardar(): void
    {
        $this->autorizarPermiso('tipo_expediente', $this->isEdit ? 'editar' : 'crear');
        $data = $this->validate();

        try {
            DB::transaction(function () use ($data): void {
                $payload = [
                    'codigo_tex' => $this->normalizarCodigo($data['tipoExpedienteForm']['codigo']),
                    'nombre_tex' => $this->mayusculas($this->normalizarTexto($data['tipoExpedienteForm']['nombre']) ?? ''),
                    'descripcion_tex' => $this->normalizarTexto($data['tipoExpedienteForm']['descripcion']),
                    'obligatorio_tex' => (bool) $data['tipoExpedienteForm']['obligatorio'],
                ];

                if ($this->editingId) {
                    TipoExpediente::query()->findOrFail($this->editingId)->update($payload);

                    return;
                }

                TipoExpediente::query()->create([
                    ...$payload,
                    'estado_tex' => EstadoCatalogo::Habilitado->value,
                ]);
            });
        } catch (Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo guardar el tipo de expediente. Intente nuevamente.', type: 'error');

            return;
        }

        $this->dispatch(
            'toast',
            message: $this->isEdit ? 'Tipo de expediente actualizado correctamente.' : 'Tipo de expediente registrado correctamente.',
            type: 'success'
        );

        unset($this->tiposExpediente);
        $this->modal('modal-tipo-expediente')->close();
        $this->resetForm();
    }

    #[On('tipo-expediente-cambiar-estado')]
    public function cambiarEstado(?int $id_tipo_expediente = null): void
    {
        $this->autorizarPermiso('tipo_expediente', 'estado');

        if ($id_tipo_expediente === null) {
            $this->dispatch('toast', message: 'ID de tipo de expediente no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_tipo_expediente): void {
                $tipoExpediente = TipoExpediente::query()->findOrFail($id_tipo_expediente);

                $tipoExpediente->update([
                    'estado_tex' => $tipoExpediente->estado_tex === EstadoCatalogo::Habilitado
                        ? EstadoCatalogo::Deshabilitado->value
                        : EstadoCatalogo::Habilitado->value,
                ]);
            });
        } catch (Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo cambiar el estado del tipo de expediente.', type: 'error');

            return;
        }

        unset($this->tiposExpediente);
        $this->dispatch('toast', message: 'Estado del tipo de expediente actualizado.', type: 'success');
    }

    #[On('tipo-expediente-eliminar')]
    public function eliminar(?int $id_tipo_expediente = null): void
    {
        $this->autorizarPermiso('tipo_expediente', 'eliminar');

        if ($id_tipo_expediente === null) {
            $this->dispatch('toast', message: 'ID de tipo de expediente no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_tipo_expediente): void {
                $tipoExpediente = TipoExpediente::query()
                    ->withCount(['requisitos', 'inscripcionesExpediente'])
                    ->findOrFail($id_tipo_expediente);

                if ($tipoExpediente->requisitos_count > 0 || $tipoExpediente->inscripciones_expediente_count > 0) {
                    throw new RuntimeException('El tipo de expediente tiene registros relacionados y no puede eliminarse.');
                }

                $tipoExpediente->delete();
            });
        } catch (Throwable $throwable) {
            report_if(! $throwable instanceof RuntimeException, $throwable);
            $this->dispatch(
                'toast',
                message: $throwable->getMessage() ?: 'No se pudo eliminar el tipo de expediente.',
                type: 'error'
            );

            return;
        }

        if ($this->editingId === $id_tipo_expediente) {
            $this->resetForm();
            $this->modal('modal-tipo-expediente')->close();
        }

        unset($this->tiposExpediente);
        $this->dispatch('toast', message: 'Tipo de expediente eliminado correctamente.', type: 'success');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'isEdit']);
        $this->tituloModal = 'Nuevo tipo de expediente';
        $this->tipoExpedienteForm = [
            'codigo' => '',
            'nombre' => '',
            'descripcion' => '',
            'obligatorio' => true,
        ];
        $this->resetValidation();
    }

    #[Computed]
    public function tiposExpediente(): LengthAwarePaginator
    {
        return TipoExpediente::query()
            ->withCount(['requisitos', 'inscripcionesExpediente'])
            ->when(filled($this->buscar), function (Builder $query): void {
                $search = $this->normalizarTexto($this->buscar);

                $query->where(function (Builder $innerQuery) use ($search): void {
                    $innerQuery->where('codigo_tex', 'like', "%{$search}%")
                        ->orWhere('nombre_tex', 'like', "%{$search}%")
                        ->orWhere('descripcion_tex', 'like', "%{$search}%");
                });
            })
            ->when($this->estadosSeleccionados !== [], function (Builder $query): void {
                $query->whereIn('estado_tex', $this->estadosSeleccionados);
            })
            ->orderByDesc('id_tex')
            ->paginate(10);
    }

    private function normalizarCodigo(string $valor): string
    {
        $codigo = preg_replace('/\s+/u', '_', trim($valor));

        return mb_strtoupper($codigo ?? '', 'UTF-8');
    }

    private function normalizarTexto(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $normalizado = preg_replace('/\s+/u', ' ', trim($valor));

        return filled($normalizado) ? $normalizado : null;
    }

    private function mayusculas(string $valor): string
    {
        return mb_strtoupper($valor, 'UTF-8');
    }
};
