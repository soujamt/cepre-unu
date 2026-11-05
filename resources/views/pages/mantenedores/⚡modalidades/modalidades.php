<?php

use App\Enums\EstadoCatalogo;
use App\Models\ModalidadAdmision;
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
#[Title('Modalidades de admisión')]
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
     * @var array{codigo: string, nombre: string, descripcion: string}
     */
    public array $modalidadForm = [
        'codigo' => '',
        'nombre' => '',
        'descripcion' => '',
    ];

    public string $tituloModal = 'Nueva modalidad';

    public bool $isEdit = false;

    public ?int $editingId = null;

    public function mount(): void
    {
        $this->autorizarPermiso('modalidad', 'indice');
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
            'modalidadForm.codigo' => [
                'required',
                'string',
                'max:20',
                'regex:/^\s*[A-Za-z0-9_-]+\s*$/',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($this->existeModalidadCon('codigo_mad', (string) $value)) {
                        $fail('Ya existe una modalidad con ese código.');
                    }
                },
            ],
            'modalidadForm.nombre' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($this->existeModalidadCon('nombre_mad', (string) $value)) {
                        $fail('Ya existe una modalidad con ese nombre.');
                    }
                },
            ],
            'modalidadForm.descripcion' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'modalidadForm.codigo.required' => 'El código es obligatorio.',
            'modalidadForm.codigo.max' => 'El código no debe superar los 20 caracteres.',
            'modalidadForm.codigo.regex' => 'El código solo admite letras, números, guion y guion bajo.',
            'modalidadForm.nombre.required' => 'El nombre es obligatorio.',
            'modalidadForm.nombre.max' => 'El nombre no debe superar los 255 caracteres.',
            'modalidadForm.descripcion.max' => 'La descripción no debe superar los 1000 caracteres.',
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

    public function cargarModalidad(?int $id = null): void
    {
        $this->autorizarPermiso('modalidad', $id === null ? 'crear' : 'editar');
        $this->resetValidation();

        if ($id === null) {
            $this->resetForm();
            $this->modal('modal-modalidad')->show();

            return;
        }

        $modalidad = ModalidadAdmision::query()->findOrFail($id);

        $this->editingId = $modalidad->id_mad;
        $this->modalidadForm = [
            'codigo' => $modalidad->codigo_mad,
            'nombre' => $modalidad->nombre_mad,
            'descripcion' => (string) ($modalidad->descripcion_mad ?? ''),
        ];
        $this->tituloModal = 'Editar modalidad';
        $this->isEdit = true;

        $this->modal('modal-modalidad')->show();
    }

    public function guardar(): void
    {
        $this->autorizarPermiso('modalidad', $this->isEdit ? 'editar' : 'crear');
        $data = $this->validate();

        try {
            DB::transaction(function () use ($data): void {
                $payload = [
                    'codigo_mad' => $this->normalizarMayusculas($data['modalidadForm']['codigo']),
                    'nombre_mad' => $this->normalizarMayusculas($data['modalidadForm']['nombre']),
                    'descripcion_mad' => $this->normalizarTexto($data['modalidadForm']['descripcion'] ?? null),
                ];

                if ($this->editingId !== null) {
                    ModalidadAdmision::query()->findOrFail($this->editingId)->update($payload);

                    return;
                }

                ModalidadAdmision::query()->create([
                    ...$payload,
                    'estado_mad' => EstadoCatalogo::Habilitado->value,
                ]);
            });
        } catch (Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo guardar la modalidad. Intente nuevamente.', type: 'error');

            return;
        }

        $this->dispatch(
            'toast',
            message: $this->isEdit ? 'Modalidad actualizada correctamente.' : 'Modalidad registrada correctamente.',
            type: 'success'
        );

        unset($this->modalidades);
        $this->modal('modal-modalidad')->close();
        $this->resetForm();
    }

    #[On('modalidad-cambiar-estado')]
    public function cambiarEstado(?int $id_modalidad = null): void
    {
        $this->autorizarPermiso('modalidad', 'estado');

        if ($id_modalidad === null) {
            $this->dispatch('toast', message: 'ID de modalidad no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_modalidad): void {
                $modalidad = ModalidadAdmision::query()->findOrFail($id_modalidad);

                $modalidad->update([
                    'estado_mad' => $modalidad->estado_mad === EstadoCatalogo::Habilitado
                        ? EstadoCatalogo::Deshabilitado->value
                        : EstadoCatalogo::Habilitado->value,
                ]);
            });
        } catch (Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo cambiar el estado de la modalidad.', type: 'error');

            return;
        }

        unset($this->modalidades);
        $this->dispatch('toast', message: 'Estado de la modalidad actualizado.', type: 'success');
    }

    #[On('modalidad-eliminar')]
    public function eliminar(?int $id_modalidad = null): void
    {
        $this->autorizarPermiso('modalidad', 'eliminar');

        if ($id_modalidad === null) {
            $this->dispatch('toast', message: 'ID de modalidad no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_modalidad): void {
                $modalidad = ModalidadAdmision::query()
                    ->withCount([
                        'modalidadesLugarInscripcion' => fn (Builder $query) => $query->withTrashed(),
                        'reglasPago' => fn (Builder $query) => $query->withTrashed(),
                    ])
                    ->findOrFail($id_modalidad);

                if ($modalidad->modalidades_lugar_inscripcion_count > 0) {
                    throw new RuntimeException('La modalidad está asignada a lugares de inscripción y no puede eliminarse. Puede deshabilitarla.');
                }

                if ($modalidad->reglas_pago_count > 0) {
                    throw new RuntimeException('La modalidad tiene reglas de pago y no puede eliminarse. Puede deshabilitarla.');
                }

                $modalidad->delete();
            });
        } catch (Throwable $throwable) {
            report_if(! $throwable instanceof RuntimeException, $throwable);
            $this->dispatch(
                'toast',
                message: $throwable instanceof RuntimeException ? $throwable->getMessage() : 'No se pudo eliminar la modalidad.',
                type: 'error'
            );

            return;
        }

        if ($this->editingId === $id_modalidad) {
            $this->resetForm();
            $this->modal('modal-modalidad')->close();
        }

        unset($this->modalidades);
        $this->dispatch('toast', message: 'Modalidad eliminada correctamente.', type: 'success');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'isEdit']);
        $this->tituloModal = 'Nueva modalidad';
        $this->modalidadForm = [
            'codigo' => '',
            'nombre' => '',
            'descripcion' => '',
        ];
        $this->resetValidation();
    }

    #[Computed]
    public function modalidades(): LengthAwarePaginator
    {
        return ModalidadAdmision::query()
            ->withCount([
                'modalidadesLugarInscripcion as lugares_habilitados_count' => fn (Builder $query) => $query->where('estado_mli', EstadoCatalogo::Habilitado->value),
            ])
            ->when(filled($this->buscar), function (Builder $query): void {
                $search = $this->normalizarTexto($this->buscar);

                $query->where(function (Builder $innerQuery) use ($search): void {
                    $innerQuery->where('codigo_mad', 'like', "%{$search}%")
                        ->orWhere('nombre_mad', 'like', "%{$search}%");
                });
            })
            ->when($this->estadosSeleccionados !== [], function (Builder $query): void {
                $query->whereIn('estado_mad', $this->estadosSeleccionados);
            })
            ->orderBy('nombre_mad')
            ->paginate(10);
    }

    /**
     * Revisa duplicados también entre las modalidades eliminadas, porque la
     * tabla mantiene los índices únicos sobre ellas.
     */
    private function existeModalidadCon(string $columna, string $valor): bool
    {
        $valor = $this->normalizarMayusculas($valor);

        if ($valor === '') {
            return false;
        }

        return ModalidadAdmision::query()
            ->withTrashed()
            ->whereRaw("UPPER({$columna}) = ?", [$valor])
            ->when($this->editingId, fn (Builder $query) => $query->where('id_mad', '!=', $this->editingId))
            ->exists();
    }

    private function normalizarMayusculas(?string $valor): string
    {
        return mb_strtoupper($this->normalizarTexto($valor) ?? '', 'UTF-8');
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
