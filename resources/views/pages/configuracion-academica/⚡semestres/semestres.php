<?php

use App\Enums\EstadoProceso;
use App\Models\Semestre;
use App\Support\EstadoAcademicoSynchronizer;
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
#[Title('SEMESTRES')]
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
     * @var array{nombre: string, fecha_inicio_inscripcion: string, fecha_fin_inscripcion: string}
     */
    public array $semestreForm = [
        'nombre' => '',
        'fecha_inicio_inscripcion' => '',
        'fecha_fin_inscripcion' => '',
    ];

    public string $tituloModal = 'Nuevo semestre';

    public bool $isEdit = false;

    public ?int $editingId = null;

    public function mount(): void
    {
        $this->autorizarPermiso('semestre', 'indice');
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
            'semestreForm.nombre' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $nombreNormalizado = $this->normalizarTexto((string) $value) ?? '';
                    $nombre = $this->mayusculas($nombreNormalizado);
                    $codigo = $this->generarCodigo($nombreNormalizado);

                    if ($nombre === '') {
                        $fail('El nombre es obligatorio.');

                        return;
                    }

                    $exists = Semestre::query()
                        ->withTrashed()
                        ->where('nombre_sem', $nombre)
                        ->when($this->editingId, fn (Builder $query) => $query->where('id_sem', '!=', $this->editingId))
                        ->exists();

                    if ($exists) {
                        $fail('Ya existe un semestre con ese nombre.');
                    }

                    $codigoExiste = Semestre::query()
                        ->withTrashed()
                        ->where('codigo_sem', $codigo)
                        ->when($this->editingId, fn (Builder $query) => $query->where('id_sem', '!=', $this->editingId))
                        ->exists();

                    if ($codigoExiste) {
                        $fail('El nombre genera un codigo que ya existe en otro semestre.');
                    }
                },
            ],
            'semestreForm.fecha_inicio_inscripcion' => ['required', 'date'],
            'semestreForm.fecha_fin_inscripcion' => ['required', 'date', 'after_or_equal:semestreForm.fecha_inicio_inscripcion'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'semestreForm.nombre.required' => 'El nombre es obligatorio.',
            'semestreForm.fecha_inicio_inscripcion.required' => 'La fecha de inicio de inscripcion es obligatoria.',
            'semestreForm.fecha_fin_inscripcion.required' => 'La fecha de fin de inscripcion es obligatoria.',
            'semestreForm.fecha_fin_inscripcion.after_or_equal' => 'La fecha final no puede ser menor que la fecha inicial.',
        ];
    }

    public function estados(): array
    {
        return EstadoProceso::cases();
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

    public function cargarSemestre(?int $id = null): void
    {
        $this->autorizarPermiso('semestre', $id === null ? 'crear' : 'editar');
        $this->resetValidation();

        if ($id === null) {
            $this->resetForm();
            $this->tituloModal = 'Nuevo semestre';
            $this->isEdit = false;
            $this->modal('modal-semestre')->show();

            return;
        }

        $semestre = Semestre::query()->findOrFail($id);

        $this->editingId = $semestre->id_sem;
        $this->semestreForm = [
            'nombre' => $semestre->nombre_sem,
            'fecha_inicio_inscripcion' => $semestre->fecha_inicio_inscripcion_sem?->format('Y-m-d') ?? '',
            'fecha_fin_inscripcion' => $semestre->fecha_fin_inscripcion_sem?->format('Y-m-d') ?? '',
        ];
        $this->tituloModal = 'Editar semestre';
        $this->isEdit = true;

        $this->modal('modal-semestre')->show();
    }

    public function guardar(): void
    {
        $this->autorizarPermiso('semestre', $this->isEdit ? 'editar' : 'crear');
        $data = $this->validate();

        try {
            DB::transaction(function () use ($data): void {
                $nombreNormalizado = $this->normalizarTexto($data['semestreForm']['nombre']) ?? '';
                $payload = [
                    'codigo_sem' => $this->generarCodigo($nombreNormalizado),
                    'nombre_sem' => $this->mayusculas($nombreNormalizado),
                    'fecha_inicio_inscripcion_sem' => $data['semestreForm']['fecha_inicio_inscripcion'],
                    'fecha_fin_inscripcion_sem' => $data['semestreForm']['fecha_fin_inscripcion'],
                ];

                if ($this->editingId) {
                    Semestre::query()->findOrFail($this->editingId)->update($payload);

                    return;
                }

                Semestre::query()->create([
                    ...$payload,
                    'estado_sem' => EstadoProceso::Habilitado->value,
                ]);
            });
        } catch (\Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo guardar el semestre. Intente nuevamente.', type: 'error');

            return;
        }

        $this->dispatch(
            'toast',
            message: $this->isEdit ? 'Semestre actualizado correctamente.' : 'Semestre registrado correctamente.',
            type: 'success'
        );

        unset($this->semestres);
        $this->modal('modal-semestre')->close();
        $this->resetForm();
    }

    #[On('semestre-cambiar-estado')]
    public function cambiarEstado(?int $id_semestre = null): void
    {
        $this->autorizarPermiso('semestre', 'estado');

        if ($id_semestre === null) {
            $this->dispatch('toast', message: 'ID de semestre no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_semestre): void {
                $synchronizer = app(EstadoAcademicoSynchronizer::class);

                $synchronizer->sync();

                $semestre = Semestre::query()->findOrFail($id_semestre);

                if ($semestre->estado_sem === EstadoProceso::Cerrado || $synchronizer->semestreEstaVencido($semestre)) {
                    if ($semestre->estado_sem !== EstadoProceso::Cerrado) {
                        $semestre->update([
                            'estado_sem' => EstadoProceso::Cerrado->value,
                        ]);
                    }

                    throw new \RuntimeException('El semestre ya venció por fecha y ahora se encuentra cerrado.');
                }

                $nuevoEstado = match ($semestre->estado_sem) {
                    EstadoProceso::Habilitado => EstadoProceso::Deshabilitado->value,
                    EstadoProceso::Deshabilitado => EstadoProceso::Habilitado->value,
                };

                $semestre->update([
                    'estado_sem' => $nuevoEstado,
                ]);
            });
        } catch (\Throwable $throwable) {
            report_if(! $throwable instanceof \RuntimeException, $throwable);
            $this->dispatch(
                'toast',
                message: $throwable->getMessage() ?: 'No se pudo cambiar el estado del semestre.',
                type: 'error'
            );

            return;
        }

        unset($this->semestres);
        $this->dispatch('toast', message: 'Estado del semestre actualizado.', type: 'success');
    }

    #[On('semestre-eliminar')]
    public function eliminar(?int $id_semestre = null): void
    {
        $this->autorizarPermiso('semestre', 'eliminar');

        if ($id_semestre === null) {
            $this->dispatch('toast', message: 'ID de semestre no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_semestre): void {
                $semestre = Semestre::query()
                    ->withCount('procesos')
                    ->findOrFail($id_semestre);

                if ($semestre->procesos_count > 0) {
                    throw new \RuntimeException('El semestre tiene procesos relacionados y no puede eliminarse.');
                }

                $semestre->delete();
            });
        } catch (\Throwable $throwable) {
            report_if(! $throwable instanceof \RuntimeException, $throwable);
            $this->dispatch(
                'toast',
                message: $throwable->getMessage() ?: 'No se pudo eliminar el semestre.',
                type: 'error'
            );

            return;
        }

        if ($this->editingId === $id_semestre) {
            $this->resetForm();
            $this->modal('modal-semestre')->close();
        }

        unset($this->semestres);
        $this->dispatch('toast', message: 'Semestre eliminado correctamente.', type: 'success');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'isEdit']);
        $this->tituloModal = 'Nuevo semestre';
        $this->semestreForm = [
            'nombre' => '',
            'fecha_inicio_inscripcion' => '',
            'fecha_fin_inscripcion' => '',
        ];
        $this->resetValidation();
    }

    #[Computed]
    public function semestres(): LengthAwarePaginator
    {
        app(EstadoAcademicoSynchronizer::class)->sync();

        return Semestre::query()
            ->withCount('procesos')
            ->when(filled($this->buscar), function (Builder $query): void {
                $search = $this->normalizarTexto($this->buscar);

                $query->where(function (Builder $innerQuery) use ($search): void {
                    $innerQuery->where('codigo_sem', 'like', "%{$search}%")
                        ->orWhere('nombre_sem', 'like', "%{$search}%");
                });
            })
            ->when($this->estadosSeleccionados !== [], function (Builder $query): void {
                $query->whereIn('estado_sem', $this->estadosSeleccionados);
            })
            ->orderByDesc('id_sem')
            ->paginate(10);
    }

    private function generarCodigo(string $valor): string
    {
        return str_replace('-', '_', (string) str($valor)->slug('_'));
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
