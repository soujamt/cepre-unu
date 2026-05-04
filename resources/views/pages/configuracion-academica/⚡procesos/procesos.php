<?php

use App\Enums\EstadoProceso;
use App\Models\Proceso;
use App\Models\Semestre;
use App\Support\EstadoAcademicoSynchronizer;
use App\Traits\AuthorizesPermisos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('layouts.app')]
#[Title('PROCESOS')]
class extends Component
{
    use AuthorizesPermisos;
    use WithPagination;

    #[Url(as: 'q')]
    public string $buscar = '';

    #[Url(as: 'semestre')]
    public string $idSemestre = '';

    /**
     * @var array<string, bool>
     */
    #[Url(as: 'estado')]
    public array $filtroEstado = [];

    /**
     * @var array{id_semestre: string, nombre: string, fecha_inicio: string, fecha_fin: string}
     */
    public array $procesoForm = [
        'id_semestre' => '',
        'nombre' => '',
        'fecha_inicio' => '',
        'fecha_fin' => '',
    ];

    public string $tituloModal = 'Nuevo proceso';

    public bool $isEdit = false;

    public ?int $editingId = null;

    public function mount(): void
    {
        $this->autorizarPermiso('proceso', 'indice');
    }

    public function updatedBuscar(): void
    {
        $this->resetPage();
    }

    public function updatedIdSemestre(): void
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
            'procesoForm.id_semestre' => ['required', 'integer', 'exists:tbl_semestre,id_sem'],
            'procesoForm.nombre' => [
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

                    $exists = Proceso::query()
                        ->withTrashed()
                        ->where('nombre_pro', $nombre)
                        ->when($this->editingId, fn (Builder $query) => $query->where('id_pro', '!=', $this->editingId))
                        ->exists();

                    if ($exists) {
                        $fail('Ya existe un proceso con ese nombre.');
                    }

                    $codigoExiste = Proceso::query()
                        ->withTrashed()
                        ->where('codigo_pro', $codigo)
                        ->when($this->editingId, fn (Builder $query) => $query->where('id_pro', '!=', $this->editingId))
                        ->exists();

                    if ($codigoExiste) {
                        $fail('El nombre genera un codigo que ya existe en otro proceso.');
                    }
                },
            ],
            'procesoForm.fecha_inicio' => [
                'required',
                'date',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $semestre = $this->semestreSeleccionado();

                    if ($semestre === null || blank($value)) {
                        return;
                    }

                    $fechaInicioSemestre = $semestre->fecha_inicio_inscripcion_sem?->format('Y-m-d');
                    $fechaFinSemestre = $semestre->fecha_fin_inscripcion_sem?->format('Y-m-d');

                    if ($fechaInicioSemestre !== null && (string) $value < $fechaInicioSemestre) {
                        $fail('La fecha de inicio del proceso no puede ser menor que la fecha de inicio del semestre.');
                    }

                    if ($fechaFinSemestre !== null && (string) $value > $fechaFinSemestre) {
                        $fail('La fecha de inicio del proceso no puede ser mayor que la fecha final del semestre.');
                    }
                },
            ],
            'procesoForm.fecha_fin' => [
                'required',
                'date',
                'after_or_equal:procesoForm.fecha_inicio',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $semestre = $this->semestreSeleccionado();

                    if ($semestre === null || blank($value)) {
                        return;
                    }

                    $fechaInicioSemestre = $semestre->fecha_inicio_inscripcion_sem?->format('Y-m-d');
                    $fechaFinSemestre = $semestre->fecha_fin_inscripcion_sem?->format('Y-m-d');

                    if ($fechaInicioSemestre !== null && (string) $value < $fechaInicioSemestre) {
                        $fail('La fecha final del proceso no puede ser menor que la fecha de inicio del semestre.');
                    }

                    if ($fechaFinSemestre !== null && (string) $value > $fechaFinSemestre) {
                        $fail('La fecha final del proceso no puede ser mayor que la fecha final del semestre.');
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
            'procesoForm.id_semestre.required' => 'El semestre es obligatorio.',
            'procesoForm.id_semestre.exists' => 'El semestre seleccionado no es valido.',
            'procesoForm.nombre.required' => 'El nombre es obligatorio.',
            'procesoForm.fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'procesoForm.fecha_fin.required' => 'La fecha de fin es obligatoria.',
            'procesoForm.fecha_fin.after_or_equal' => 'La fecha final no puede ser menor que la fecha inicial.',
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

    #[Computed]
    public function semestresDisponibles(): Collection
    {
        app(EstadoAcademicoSynchronizer::class)->sync();

        return Semestre::query()
            ->orderByDesc('id_sem')
            ->get();
    }

    public function limpiarFiltroEstado(): void
    {
        $this->filtroEstado = [];
    }

    public function limpiarFiltrosAcademicos(): void
    {
        $this->idSemestre = '';
        $this->resetPage();
    }

    public function cargarProceso(?int $id = null): void
    {
        $this->autorizarPermiso('proceso', $id === null ? 'crear' : 'editar');
        $this->resetValidation();

        if ($id === null) {
            $this->resetForm();
            $this->tituloModal = 'Nuevo proceso';
            $this->isEdit = false;
            $this->modal('modal-proceso')->show();

            return;
        }

        $proceso = Proceso::query()->findOrFail($id);

        $this->editingId = $proceso->id_pro;
        $this->procesoForm = [
            'id_semestre' => (string) $proceso->id_semestre,
            'nombre' => $proceso->nombre_pro,
            'fecha_inicio' => $proceso->fecha_inicio_pro?->format('Y-m-d') ?? '',
            'fecha_fin' => $proceso->fecha_fin_pro?->format('Y-m-d') ?? '',
        ];
        $this->tituloModal = 'Editar proceso';
        $this->isEdit = true;

        $this->modal('modal-proceso')->show();
    }

    public function guardar(): void
    {
        $this->autorizarPermiso('proceso', $this->isEdit ? 'editar' : 'crear');
        $data = $this->validate();

        try {
            DB::transaction(function () use ($data): void {
                $nombreNormalizado = $this->normalizarTexto($data['procesoForm']['nombre']) ?? '';
                $payload = [
                    'id_semestre' => (int) $data['procesoForm']['id_semestre'],
                    'codigo_pro' => $this->generarCodigo($nombreNormalizado),
                    'nombre_pro' => $this->mayusculas($nombreNormalizado),
                    'fecha_inicio_pro' => $data['procesoForm']['fecha_inicio'],
                    'fecha_fin_pro' => $data['procesoForm']['fecha_fin'],
                ];

                if ($this->editingId) {
                    Proceso::query()->findOrFail($this->editingId)->update($payload);

                    return;
                }

                Proceso::query()->create([
                    ...$payload,
                    'estado_pro' => EstadoProceso::Habilitado->value,
                ]);
            });
        } catch (\Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo guardar el proceso. Intente nuevamente.', type: 'error');

            return;
        }

        $this->dispatch(
            'toast',
            message: $this->isEdit ? 'Proceso actualizado correctamente.' : 'Proceso registrado correctamente.',
            type: 'success'
        );

        unset($this->procesos);
        $this->modal('modal-proceso')->close();
        $this->resetForm();
    }

    #[On('proceso-cambiar-estado')]
    public function cambiarEstado(?int $id_proceso = null): void
    {
        $this->autorizarPermiso('proceso', 'estado');

        if ($id_proceso === null) {
            $this->dispatch('toast', message: 'ID de proceso no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_proceso): void {
                $synchronizer = app(EstadoAcademicoSynchronizer::class);

                $synchronizer->sync();

                $proceso = Proceso::query()->findOrFail($id_proceso);

                if ($proceso->estado_pro === EstadoProceso::Cerrado || $synchronizer->procesoEstaVencido($proceso)) {
                    if ($proceso->estado_pro !== EstadoProceso::Cerrado) {
                        $proceso->update([
                            'estado_pro' => EstadoProceso::Cerrado->value,
                        ]);
                    }

                    throw new \RuntimeException('El proceso ya venció por fecha y ahora se encuentra cerrado.');
                }

                $nuevoEstado = match ($proceso->estado_pro) {
                    EstadoProceso::Habilitado => EstadoProceso::Deshabilitado->value,
                    EstadoProceso::Deshabilitado => EstadoProceso::Habilitado->value,
                };

                $proceso->update([
                    'estado_pro' => $nuevoEstado,
                ]);
            });
        } catch (\Throwable $throwable) {
            report_if(! $throwable instanceof \RuntimeException, $throwable);
            $this->dispatch(
                'toast',
                message: $throwable->getMessage() ?: 'No se pudo cambiar el estado del proceso.',
                type: 'error'
            );

            return;
        }

        unset($this->procesos);
        $this->dispatch('toast', message: 'Estado del proceso actualizado.', type: 'success');
    }

    #[On('proceso-eliminar')]
    public function eliminar(?int $id_proceso = null): void
    {
        $this->autorizarPermiso('proceso', 'eliminar');

        if ($id_proceso === null) {
            $this->dispatch('toast', message: 'ID de proceso no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_proceso): void {
                $proceso = Proceso::query()
                    ->withCount(['procesosOferta', 'conveniosProceso', 'reglasPago', 'requisitosExpediente'])
                    ->findOrFail($id_proceso);

                if (
                    $proceso->procesos_oferta_count > 0
                    || $proceso->convenios_proceso_count > 0
                    || $proceso->reglas_pago_count > 0
                    || $proceso->requisitos_expediente_count > 0
                ) {
                    throw new \RuntimeException('El proceso tiene configuraciones relacionadas y no puede eliminarse.');
                }

                $proceso->delete();
            });
        } catch (\Throwable $throwable) {
            report_if(! $throwable instanceof \RuntimeException, $throwable);
            $this->dispatch(
                'toast',
                message: $throwable->getMessage() ?: 'No se pudo eliminar el proceso.',
                type: 'error'
            );

            return;
        }

        if ($this->editingId === $id_proceso) {
            $this->resetForm();
            $this->modal('modal-proceso')->close();
        }

        unset($this->procesos);
        $this->dispatch('toast', message: 'Proceso eliminado correctamente.', type: 'success');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'isEdit']);
        $this->tituloModal = 'Nuevo proceso';
        $this->procesoForm = [
            'id_semestre' => '',
            'nombre' => '',
            'fecha_inicio' => '',
            'fecha_fin' => '',
        ];
        $this->resetValidation();
    }

    #[Computed]
    public function procesos(): LengthAwarePaginator
    {
        app(EstadoAcademicoSynchronizer::class)->sync();

        return Proceso::query()
            ->with('semestre')
            ->withCount(['procesosOferta', 'conveniosProceso', 'reglasPago', 'requisitosExpediente'])
            ->when(filled($this->buscar), function (Builder $query): void {
                $search = $this->normalizarTexto($this->buscar);

                $query->where(function (Builder $innerQuery) use ($search): void {
                    $innerQuery->where('codigo_pro', 'like', "%{$search}%")
                        ->orWhere('nombre_pro', 'like', "%{$search}%")
                        ->orWhereHas('semestre', fn (Builder $semestreQuery) => $semestreQuery
                            ->where('nombre_sem', 'like', "%{$search}%"));
                });
            })
            ->when(filled($this->idSemestre), fn (Builder $query) => $query->where('id_semestre', (int) $this->idSemestre))
            ->when($this->estadosSeleccionados !== [], function (Builder $query): void {
                $query->whereIn('estado_pro', $this->estadosSeleccionados);
            })
            ->orderByDesc('id_pro')
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

    private function semestreSeleccionado(): ?Semestre
    {
        if (blank($this->procesoForm['id_semestre'])) {
            return null;
        }

        return Semestre::query()->find((int) $this->procesoForm['id_semestre']);
    }
};
