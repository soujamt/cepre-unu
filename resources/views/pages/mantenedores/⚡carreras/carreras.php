<?php

use App\Enums\EstadoCatalogo;
use App\Models\Carrera;
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
#[Title('Carreras')]
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
     * @var array{codigo: string, nombre: string}
     */
    public array $carreraForm = [
        'codigo' => '',
        'nombre' => '',
    ];

    public string $tituloModal = 'Nueva carrera';

    public bool $isEdit = false;

    public ?int $editingId = null;

    public function mount(): void
    {
        $this->autorizarPermiso('carrera', 'indice');
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
            'carreraForm.codigo' => [
                'required',
                'string',
                'max:20',
                'regex:/^\s*[A-Za-z0-9_-]+\s*$/',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($this->existeCarreraCon('codigo_car', (string) $value)) {
                        $fail('Ya existe una carrera con ese código.');
                    }
                },
            ],
            'carreraForm.nombre' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($this->existeCarreraCon('nombre_car', (string) $value)) {
                        $fail('Ya existe una carrera con ese nombre.');
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
            'carreraForm.codigo.required' => 'El código es obligatorio.',
            'carreraForm.codigo.max' => 'El código no debe superar los 20 caracteres.',
            'carreraForm.codigo.regex' => 'El código solo admite letras, números, guion y guion bajo.',
            'carreraForm.nombre.required' => 'El nombre es obligatorio.',
            'carreraForm.nombre.max' => 'El nombre no debe superar los 255 caracteres.',
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

    public function cargarCarrera(?int $id = null): void
    {
        $this->autorizarPermiso('carrera', $id === null ? 'crear' : 'editar');
        $this->resetValidation();

        if ($id === null) {
            $this->resetForm();
            $this->modal('modal-carrera')->show();

            return;
        }

        $carrera = Carrera::query()->findOrFail($id);

        $this->editingId = $carrera->id_car;
        $this->carreraForm = [
            'codigo' => $carrera->codigo_car,
            'nombre' => $carrera->nombre_car,
        ];
        $this->tituloModal = 'Editar carrera';
        $this->isEdit = true;

        $this->modal('modal-carrera')->show();
    }

    public function guardar(): void
    {
        $this->autorizarPermiso('carrera', $this->isEdit ? 'editar' : 'crear');
        $data = $this->validate();

        try {
            DB::transaction(function () use ($data): void {
                $payload = [
                    'codigo_car' => $this->normalizarMayusculas($data['carreraForm']['codigo']),
                    'nombre_car' => $this->normalizarMayusculas($data['carreraForm']['nombre']),
                ];

                if ($this->editingId !== null) {
                    Carrera::query()->findOrFail($this->editingId)->update($payload);

                    return;
                }

                Carrera::query()->create([
                    ...$payload,
                    'estado_car' => EstadoCatalogo::Habilitado->value,
                ]);
            });
        } catch (Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo guardar la carrera. Intente nuevamente.', type: 'error');

            return;
        }

        $this->dispatch(
            'toast',
            message: $this->isEdit ? 'Carrera actualizada correctamente.' : 'Carrera registrada correctamente.',
            type: 'success'
        );

        unset($this->carreras);
        $this->modal('modal-carrera')->close();
        $this->resetForm();
    }

    #[On('carrera-cambiar-estado')]
    public function cambiarEstado(?int $id_carrera = null): void
    {
        $this->autorizarPermiso('carrera', 'estado');

        if ($id_carrera === null) {
            $this->dispatch('toast', message: 'ID de carrera no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_carrera): void {
                $carrera = Carrera::query()->findOrFail($id_carrera);

                $carrera->update([
                    'estado_car' => $carrera->estado_car === EstadoCatalogo::Habilitado
                        ? EstadoCatalogo::Deshabilitado->value
                        : EstadoCatalogo::Habilitado->value,
                ]);
            });
        } catch (Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo cambiar el estado de la carrera.', type: 'error');

            return;
        }

        unset($this->carreras);
        $this->dispatch('toast', message: 'Estado de la carrera actualizado.', type: 'success');
    }

    #[On('carrera-eliminar')]
    public function eliminar(?int $id_carrera = null): void
    {
        $this->autorizarPermiso('carrera', 'eliminar');

        if ($id_carrera === null) {
            $this->dispatch('toast', message: 'ID de carrera no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_carrera): void {
                $carrera = Carrera::query()
                    ->withCount(['sedeCarreras' => fn (Builder $query) => $query->withTrashed()])
                    ->findOrFail($id_carrera);

                if ($carrera->sede_carreras_count > 0) {
                    throw new RuntimeException('La carrera está asignada a una sede y no puede eliminarse. Puede deshabilitarla.');
                }

                $carrera->delete();
            });
        } catch (Throwable $throwable) {
            report_if(! $throwable instanceof RuntimeException, $throwable);
            $this->dispatch(
                'toast',
                message: $throwable instanceof RuntimeException ? $throwable->getMessage() : 'No se pudo eliminar la carrera.',
                type: 'error'
            );

            return;
        }

        if ($this->editingId === $id_carrera) {
            $this->resetForm();
            $this->modal('modal-carrera')->close();
        }

        unset($this->carreras);
        $this->dispatch('toast', message: 'Carrera eliminada correctamente.', type: 'success');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'isEdit']);
        $this->tituloModal = 'Nueva carrera';
        $this->carreraForm = [
            'codigo' => '',
            'nombre' => '',
        ];
        $this->resetValidation();
    }

    #[Computed]
    public function carreras(): LengthAwarePaginator
    {
        return Carrera::query()
            ->withCount('sedeCarreras')
            ->when(filled($this->buscar), function (Builder $query): void {
                $search = $this->normalizarTexto($this->buscar);

                $query->where(function (Builder $innerQuery) use ($search): void {
                    $innerQuery->where('codigo_car', 'like', "%{$search}%")
                        ->orWhere('nombre_car', 'like', "%{$search}%");
                });
            })
            ->when($this->estadosSeleccionados !== [], function (Builder $query): void {
                $query->whereIn('estado_car', $this->estadosSeleccionados);
            })
            ->orderBy('nombre_car')
            ->paginate(10);
    }

    /**
     * Revisa duplicados también entre las carreras eliminadas, porque la
     * tabla mantiene los índices únicos sobre ellas.
     */
    private function existeCarreraCon(string $columna, string $valor): bool
    {
        $valor = $this->normalizarMayusculas($valor);

        if ($valor === '') {
            return false;
        }

        return Carrera::query()
            ->withTrashed()
            ->whereRaw("UPPER({$columna}) = ?", [$valor])
            ->when($this->editingId, fn (Builder $query) => $query->where('id_car', '!=', $this->editingId))
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
