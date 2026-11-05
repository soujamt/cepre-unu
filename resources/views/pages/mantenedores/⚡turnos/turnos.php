<?php

use App\Enums\EstadoCatalogo;
use App\Models\Turno;
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
#[Title('Turnos')]
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
    public array $turnoForm = [
        'codigo' => '',
        'nombre' => '',
    ];

    public string $tituloModal = 'Nuevo turno';

    public bool $isEdit = false;

    public ?int $editingId = null;

    public function mount(): void
    {
        $this->autorizarPermiso('turno', 'indice');
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
            'turnoForm.codigo' => [
                'required',
                'string',
                'max:20',
                'regex:/^\s*[A-Za-z0-9_-]+\s*$/',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($this->existeTurnoCon('codigo_tur', (string) $value)) {
                        $fail('Ya existe un turno con ese código.');
                    }
                },
            ],
            'turnoForm.nombre' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($this->existeTurnoCon('nombre_tur', (string) $value)) {
                        $fail('Ya existe un turno con ese nombre.');
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
            'turnoForm.codigo.required' => 'El código es obligatorio.',
            'turnoForm.codigo.max' => 'El código no debe superar los 20 caracteres.',
            'turnoForm.codigo.regex' => 'El código solo admite letras, números, guion y guion bajo.',
            'turnoForm.nombre.required' => 'El nombre es obligatorio.',
            'turnoForm.nombre.max' => 'El nombre no debe superar los 255 caracteres.',
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

    public function cargarTurno(?int $id = null): void
    {
        $this->autorizarPermiso('turno', $id === null ? 'crear' : 'editar');
        $this->resetValidation();

        if ($id === null) {
            $this->resetForm();
            $this->modal('modal-turno')->show();

            return;
        }

        $turno = Turno::query()->findOrFail($id);

        $this->editingId = $turno->id_tur;
        $this->turnoForm = [
            'codigo' => $turno->codigo_tur,
            'nombre' => $turno->nombre_tur,
        ];
        $this->tituloModal = 'Editar turno';
        $this->isEdit = true;

        $this->modal('modal-turno')->show();
    }

    public function guardar(): void
    {
        $this->autorizarPermiso('turno', $this->isEdit ? 'editar' : 'crear');
        $data = $this->validate();

        try {
            DB::transaction(function () use ($data): void {
                $payload = [
                    'codigo_tur' => $this->normalizarMayusculas($data['turnoForm']['codigo']),
                    'nombre_tur' => $this->normalizarMayusculas($data['turnoForm']['nombre']),
                ];

                if ($this->editingId !== null) {
                    Turno::query()->findOrFail($this->editingId)->update($payload);

                    return;
                }

                Turno::query()->create([
                    ...$payload,
                    'estado_tur' => EstadoCatalogo::Habilitado->value,
                ]);
            });
        } catch (Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo guardar el turno. Intente nuevamente.', type: 'error');

            return;
        }

        $this->dispatch(
            'toast',
            message: $this->isEdit ? 'Turno actualizado correctamente.' : 'Turno registrado correctamente.',
            type: 'success'
        );

        unset($this->turnos);
        $this->modal('modal-turno')->close();
        $this->resetForm();
    }

    #[On('turno-cambiar-estado')]
    public function cambiarEstado(?int $id_turno = null): void
    {
        $this->autorizarPermiso('turno', 'estado');

        if ($id_turno === null) {
            $this->dispatch('toast', message: 'ID de turno no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_turno): void {
                $turno = Turno::query()->findOrFail($id_turno);

                $turno->update([
                    'estado_tur' => $turno->estado_tur === EstadoCatalogo::Habilitado
                        ? EstadoCatalogo::Deshabilitado->value
                        : EstadoCatalogo::Habilitado->value,
                ]);
            });
        } catch (Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo cambiar el estado del turno.', type: 'error');

            return;
        }

        unset($this->turnos);
        $this->dispatch('toast', message: 'Estado del turno actualizado.', type: 'success');
    }

    #[On('turno-eliminar')]
    public function eliminar(?int $id_turno = null): void
    {
        $this->autorizarPermiso('turno', 'eliminar');

        if ($id_turno === null) {
            $this->dispatch('toast', message: 'ID de turno no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_turno): void {
                $turno = Turno::query()
                    ->withCount([
                        'procesosOferta' => fn (Builder $query) => $query->withTrashed(),
                        'procesoAulas' => fn (Builder $query) => $query->withTrashed(),
                    ])
                    ->findOrFail($id_turno);

                if ($turno->procesos_oferta_count > 0) {
                    throw new RuntimeException('El turno se usa en ofertas de procesos y no puede eliminarse. Puede deshabilitarlo.');
                }

                if ($turno->proceso_aulas_count > 0) {
                    throw new RuntimeException('El turno se usa en aulas por proceso y no puede eliminarse. Puede deshabilitarlo.');
                }

                $turno->delete();
            });
        } catch (Throwable $throwable) {
            report_if(! $throwable instanceof RuntimeException, $throwable);
            $this->dispatch(
                'toast',
                message: $throwable instanceof RuntimeException ? $throwable->getMessage() : 'No se pudo eliminar el turno.',
                type: 'error'
            );

            return;
        }

        if ($this->editingId === $id_turno) {
            $this->resetForm();
            $this->modal('modal-turno')->close();
        }

        unset($this->turnos);
        $this->dispatch('toast', message: 'Turno eliminado correctamente.', type: 'success');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'isEdit']);
        $this->tituloModal = 'Nuevo turno';
        $this->turnoForm = [
            'codigo' => '',
            'nombre' => '',
        ];
        $this->resetValidation();
    }

    #[Computed]
    public function turnos(): LengthAwarePaginator
    {
        return Turno::query()
            ->withCount('procesosOferta')
            ->when(filled($this->buscar), function (Builder $query): void {
                $search = $this->normalizarTexto($this->buscar);

                $query->where(function (Builder $innerQuery) use ($search): void {
                    $innerQuery->where('codigo_tur', 'like', "%{$search}%")
                        ->orWhere('nombre_tur', 'like', "%{$search}%");
                });
            })
            ->when($this->estadosSeleccionados !== [], function (Builder $query): void {
                $query->whereIn('estado_tur', $this->estadosSeleccionados);
            })
            ->orderBy('nombre_tur')
            ->paginate(10);
    }

    /**
     * Revisa duplicados también entre los turnos eliminados, porque la
     * tabla mantiene los índices únicos sobre ellas.
     */
    private function existeTurnoCon(string $columna, string $valor): bool
    {
        $valor = $this->normalizarMayusculas($valor);

        if ($valor === '') {
            return false;
        }

        return Turno::query()
            ->withTrashed()
            ->whereRaw("UPPER({$columna}) = ?", [$valor])
            ->when($this->editingId, fn (Builder $query) => $query->where('id_tur', '!=', $this->editingId))
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
