<?php

use App\Enums\EstadoCatalogo;
use App\Enums\TipoBeneficioConvenio;
use App\Models\Convenio;
use App\Models\ConvenioProceso;
use App\Models\Proceso;
use App\Models\Semestre;
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
#[Title('CONVENIOS')]
class extends Component
{
    use AuthorizesPermisos;
    use WithPagination;

    #[Url(as: 'q')]
    public string $buscar = '';

    #[Url(as: 'semestre')]
    public string $idSemestre = '';

    #[Url(as: 'proceso')]
    public string $idProceso = '';

    /**
     * @var array<string, bool>
     */
    #[Url(as: 'estado')]
    public array $filtroEstado = [];

    /**
     * @var array{
     *     nombre:string,
     *     descripcion:string,
     *     tipo_beneficio:string,
     *     monto_descuento:string,
     *     porcentaje_descuento:string,
     *     id_proceso:string,
     *     fecha_inicio:string,
     *     fecha_fin:string,
     *     cupo_maximo:string
     * }
     */
    public array $convenioForm = [
        'nombre' => '',
        'descripcion' => '',
        'tipo_beneficio' => TipoBeneficioConvenio::Descuento->value,
        'monto_descuento' => '',
        'porcentaje_descuento' => '',
        'id_proceso' => '',
        'fecha_inicio' => '',
        'fecha_fin' => '',
        'cupo_maximo' => '',
    ];

    public string $tituloModal = 'Nuevo convenio';

    public bool $isEdit = false;

    public ?int $editingId = null;

    public ?int $editingConvenioId = null;

    public function mount(): void
    {
        $this->autorizarPermiso('convenio', 'indice');
    }

    public function updatedBuscar(): void
    {
        $this->resetPage();
    }

    public function updatedIdSemestre(): void
    {
        $this->idProceso = '';
        $this->resetPage();
    }

    public function updatedIdProceso(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroEstado(): void
    {
        $this->resetPage();
    }

    public function updatedConvenioFormTipoBeneficio(string $value): void
    {
        if ($value === TipoBeneficioConvenio::ExoneracionTotal->value) {
            $this->convenioForm['monto_descuento'] = '';
            $this->convenioForm['porcentaje_descuento'] = '';
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'convenioForm.nombre' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $nombre = $this->mayusculas($this->normalizarTexto((string) $value) ?? '');

                    if ($nombre === '') {
                        $fail('El nombre es obligatorio.');

                        return;
                    }

                    $exists = Convenio::query()
                        ->withTrashed()
                        ->where('nombre_con', $nombre)
                        ->when($this->editingConvenioId, fn (Builder $query) => $query->where('id_con', '!=', $this->editingConvenioId))
                        ->exists();

                    if ($exists) {
                        $fail('Ya existe un convenio con ese nombre.');
                    }
                },
            ],
            'convenioForm.descripcion' => ['nullable', 'string', 'max:1000'],
            'convenioForm.tipo_beneficio' => ['required', 'in:'.implode(',', array_column(TipoBeneficioConvenio::cases(), 'value'))],
            'convenioForm.monto_descuento' => [
                'nullable',
                'numeric',
                'min:0',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->convenioForm['tipo_beneficio'] === TipoBeneficioConvenio::ExoneracionTotal->value) {
                        return;
                    }

                    if (! filled($value) && ! filled($this->convenioForm['porcentaje_descuento'])) {
                        $fail('Debe indicar un monto o porcentaje de beneficio.');
                    }
                },
            ],
            'convenioForm.porcentaje_descuento' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->convenioForm['tipo_beneficio'] === TipoBeneficioConvenio::ExoneracionTotal->value) {
                        return;
                    }

                    if (! filled($value) && ! filled($this->convenioForm['monto_descuento'])) {
                        $fail('Debe indicar un monto o porcentaje de beneficio.');
                    }
                },
            ],
            'convenioForm.id_proceso' => [
                'required',
                'integer',
                'exists:tbl_proceso,id_pro',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (blank($this->editingConvenioId)) {
                        return;
                    }

                    $exists = ConvenioProceso::query()
                        ->withTrashed()
                        ->where('id_convenio', $this->editingConvenioId)
                        ->where('id_proceso', (int) $value)
                        ->when($this->editingId, fn (Builder $query) => $query->where('id_cpr', '!=', $this->editingId))
                        ->exists();

                    if ($exists) {
                        $fail('El convenio ya esta asignado a ese proceso.');
                    }
                },
            ],
            'convenioForm.fecha_inicio' => [
                'required',
                'date',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $proceso = $this->procesoSeleccionado();

                    if ($proceso === null || blank($value)) {
                        return;
                    }

                    $fechaInicioProceso = $proceso->fecha_inicio_pro?->format('Y-m-d');
                    $fechaFinProceso = $proceso->fecha_fin_pro?->format('Y-m-d');

                    if ($fechaInicioProceso !== null && (string) $value < $fechaInicioProceso) {
                        $fail('La fecha de inicio del convenio no puede ser menor que la fecha de inicio del proceso.');
                    }

                    if ($fechaFinProceso !== null && (string) $value > $fechaFinProceso) {
                        $fail('La fecha de inicio del convenio no puede ser mayor que la fecha final del proceso.');
                    }
                },
            ],
            'convenioForm.fecha_fin' => [
                'required',
                'date',
                'after_or_equal:convenioForm.fecha_inicio',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $proceso = $this->procesoSeleccionado();

                    if ($proceso === null || blank($value)) {
                        return;
                    }

                    $fechaInicioProceso = $proceso->fecha_inicio_pro?->format('Y-m-d');
                    $fechaFinProceso = $proceso->fecha_fin_pro?->format('Y-m-d');

                    if ($fechaInicioProceso !== null && (string) $value < $fechaInicioProceso) {
                        $fail('La fecha final del convenio no puede ser menor que la fecha de inicio del proceso.');
                    }

                    if ($fechaFinProceso !== null && (string) $value > $fechaFinProceso) {
                        $fail('La fecha final del convenio no puede ser mayor que la fecha final del proceso.');
                    }
                },
            ],
            'convenioForm.cupo_maximo' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'convenioForm.nombre.required' => 'El nombre es obligatorio.',
            'convenioForm.tipo_beneficio.required' => 'El tipo de beneficio es obligatorio.',
            'convenioForm.id_proceso.required' => 'El proceso es obligatorio.',
            'convenioForm.fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'convenioForm.fecha_fin.required' => 'La fecha de fin es obligatoria.',
            'convenioForm.fecha_fin.after_or_equal' => 'La fecha final no puede ser menor que la inicial.',
            'convenioForm.cupo_maximo.required' => 'El cupo maximo es obligatorio.',
            'convenioForm.cupo_maximo.min' => 'El cupo maximo debe ser mayor a cero.',
        ];
    }

    public function estados(): array
    {
        return EstadoCatalogo::cases();
    }

    public function tiposBeneficio(): array
    {
        return TipoBeneficioConvenio::cases();
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
    public function semestresFiltroDisponibles(): Collection
    {
        return Semestre::query()
            ->where(function (Builder $query): void {
                $query->whereHas('procesos.conveniosProceso')
                    ->orWhere(function (Builder $builder): void {
                        $builder->where('estado_sem', EstadoCatalogo::Habilitado->value)
                            ->whereHas('procesos', fn (Builder $procesoQuery) => $procesoQuery->where('estado_pro', EstadoCatalogo::Habilitado->value));
                    });
            })
            ->orderByDesc('fecha_inicio_inscripcion_sem')
            ->orderByDesc('id_sem')
            ->get();
    }

    #[Computed]
    public function procesosFiltroDisponibles(): Collection
    {
        return Proceso::query()
            ->with('semestre')
            ->when(filled($this->idSemestre), fn (Builder $query) => $query->where('id_semestre', (int) $this->idSemestre))
            ->where(function (Builder $query): void {
                $query->whereHas('conveniosProceso')
                    ->orWhere('estado_pro', EstadoCatalogo::Habilitado->value);
            })
            ->orderByDesc('id_pro')
            ->get();
    }

    #[Computed]
    public function procesosFormularioDisponibles(): Collection
    {
        $procesoSeleccionado = filled($this->convenioForm['id_proceso'])
            ? (int) $this->convenioForm['id_proceso']
            : null;

        return Proceso::query()
            ->with('semestre')
            ->where(function (Builder $query) use ($procesoSeleccionado): void {
                $query->where('estado_pro', EstadoCatalogo::Habilitado->value);

                if ($procesoSeleccionado !== null) {
                    $query->orWhere('id_pro', $procesoSeleccionado);
                }
            })
            ->orderByDesc('id_pro')
            ->get();
    }

    #[Computed]
    public function codigoConvenioVisible(): string
    {
        if ($this->editingConvenioId !== null) {
            return (string) Convenio::query()->whereKey($this->editingConvenioId)->value('codigo_con');
        }

        return Convenio::generarCodigoCorrelativo();
    }

    public function limpiarFiltroEstado(): void
    {
        $this->filtroEstado = [];
    }

    public function limpiarFiltrosAcademicos(): void
    {
        $this->idSemestre = '';
        $this->idProceso = '';
        $this->resetPage();
    }

    public function cargarConvenio(?int $id = null): void
    {
        $this->autorizarPermiso('convenio', $id === null ? 'crear' : 'editar');
        $this->resetValidation();

        if ($id === null) {
            $this->resetForm();
            $this->tituloModal = 'Nuevo convenio';
            $this->isEdit = false;
            $this->modal('modal-convenio')->show();

            return;
        }

        $convenioProceso = ConvenioProceso::query()
            ->with('convenio')
            ->findOrFail($id);

        $this->editingId = $convenioProceso->id_cpr;
        $this->editingConvenioId = $convenioProceso->id_convenio;
        $this->convenioForm = [
            'nombre' => $convenioProceso->convenio?->nombre_con ?? '',
            'descripcion' => $convenioProceso->convenio?->descripcion_con ?? '',
            'tipo_beneficio' => $convenioProceso->convenio?->tipo_beneficio_con?->value ?? TipoBeneficioConvenio::Descuento->value,
            'monto_descuento' => $convenioProceso->convenio?->monto_descuento_con !== null
                ? number_format((float) $convenioProceso->convenio->monto_descuento_con, 2, '.', '')
                : '',
            'porcentaje_descuento' => $convenioProceso->convenio?->porcentaje_descuento_con !== null
                ? number_format((float) $convenioProceso->convenio->porcentaje_descuento_con, 2, '.', '')
                : '',
            'id_proceso' => (string) $convenioProceso->id_proceso,
            'fecha_inicio' => $convenioProceso->fecha_inicio_cpr?->format('Y-m-d') ?? '',
            'fecha_fin' => $convenioProceso->fecha_fin_cpr?->format('Y-m-d') ?? '',
            'cupo_maximo' => (string) $convenioProceso->cupo_maximo_cpr,
        ];
        $this->tituloModal = 'Editar convenio';
        $this->isEdit = true;

        $this->modal('modal-convenio')->show();
    }

    public function guardar(): void
    {
        $this->autorizarPermiso('convenio', $this->isEdit ? 'editar' : 'crear');
        $data = $this->validate();

        try {
            DB::transaction(function () use ($data): void {
                $tipoBeneficio = $data['convenioForm']['tipo_beneficio'];

                $payloadConvenio = [
                    'nombre_con' => $this->mayusculas($this->normalizarTexto($data['convenioForm']['nombre']) ?? ''),
                    'descripcion_con' => $this->normalizarTexto($data['convenioForm']['descripcion']),
                    'tipo_beneficio_con' => $tipoBeneficio,
                    'monto_descuento_con' => $tipoBeneficio === TipoBeneficioConvenio::ExoneracionTotal->value
                        ? null
                        : (filled($data['convenioForm']['monto_descuento']) ? (float) $data['convenioForm']['monto_descuento'] : null),
                    'porcentaje_descuento_con' => $tipoBeneficio === TipoBeneficioConvenio::ExoneracionTotal->value
                        ? null
                        : (filled($data['convenioForm']['porcentaje_descuento']) ? (float) $data['convenioForm']['porcentaje_descuento'] : null),
                ];

                if ($this->editingId !== null && $this->editingConvenioId !== null) {
                    Convenio::query()->findOrFail($this->editingConvenioId)->update($payloadConvenio);

                    ConvenioProceso::query()->findOrFail($this->editingId)->update([
                        'id_proceso' => (int) $data['convenioForm']['id_proceso'],
                        'fecha_inicio_cpr' => $data['convenioForm']['fecha_inicio'],
                        'fecha_fin_cpr' => $data['convenioForm']['fecha_fin'],
                        'cupo_maximo_cpr' => (int) $data['convenioForm']['cupo_maximo'],
                    ]);

                    return;
                }

                $convenio = Convenio::query()->create([
                    ...$payloadConvenio,
                    'codigo_con' => Convenio::generarCodigoCorrelativo(),
                    'estado_con' => EstadoCatalogo::Habilitado->value,
                ]);

                ConvenioProceso::query()->create([
                    'id_convenio' => $convenio->id_con,
                    'id_proceso' => (int) $data['convenioForm']['id_proceso'],
                    'fecha_inicio_cpr' => $data['convenioForm']['fecha_inicio'],
                    'fecha_fin_cpr' => $data['convenioForm']['fecha_fin'],
                    'cupo_maximo_cpr' => (int) $data['convenioForm']['cupo_maximo'],
                    'cupo_usado_cpr' => 0,
                    'estado_cpr' => EstadoCatalogo::Habilitado->value,
                ]);
            });
        } catch (\Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo guardar el convenio. Intente nuevamente.', type: 'error');

            return;
        }

        $this->dispatch(
            'toast',
            message: $this->isEdit ? 'Convenio actualizado correctamente.' : 'Convenio registrado correctamente.',
            type: 'success'
        );

        unset($this->convenios);
        $this->modal('modal-convenio')->close();
        $this->resetForm();
    }

    #[On('convenio-cambiar-estado')]
    public function cambiarEstado(?int $id_convenio_proceso = null): void
    {
        $this->autorizarPermiso('convenio', 'estado');

        if ($id_convenio_proceso === null) {
            $this->dispatch('toast', message: 'ID de convenio no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_convenio_proceso): void {
                $convenioProceso = ConvenioProceso::query()->findOrFail($id_convenio_proceso);

                $convenioProceso->update([
                    'estado_cpr' => $convenioProceso->estado_cpr === EstadoCatalogo::Habilitado
                        ? EstadoCatalogo::Deshabilitado->value
                        : EstadoCatalogo::Habilitado->value,
                ]);
            });
        } catch (\Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo cambiar el estado del convenio.', type: 'error');

            return;
        }

        unset($this->convenios);
        $this->dispatch('toast', message: 'Estado del convenio actualizado.', type: 'success');
    }

    #[On('convenio-eliminar')]
    public function eliminar(?int $id_convenio_proceso = null): void
    {
        $this->autorizarPermiso('convenio', 'eliminar');

        if ($id_convenio_proceso === null) {
            $this->dispatch('toast', message: 'ID de convenio no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_convenio_proceso): void {
                $convenioProceso = ConvenioProceso::query()
                    ->withCount(['inscripciones', 'reglasPago'])
                    ->findOrFail($id_convenio_proceso);

                if ($convenioProceso->inscripciones_count > 0 || $convenioProceso->reglas_pago_count > 0) {
                    throw new \RuntimeException('El convenio tiene registros relacionados y no puede eliminarse.');
                }

                $convenioProceso->delete();
            });
        } catch (\Throwable $throwable) {
            report_if(! $throwable instanceof \RuntimeException, $throwable);
            $this->dispatch(
                'toast',
                message: $throwable->getMessage() ?: 'No se pudo eliminar el convenio.',
                type: 'error'
            );

            return;
        }

        if ($this->editingId === $id_convenio_proceso) {
            $this->resetForm();
            $this->modal('modal-convenio')->close();
        }

        unset($this->convenios);
        $this->dispatch('toast', message: 'Convenio eliminado correctamente.', type: 'success');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'editingConvenioId', 'isEdit']);
        $this->tituloModal = 'Nuevo convenio';
        $this->convenioForm = [
            'nombre' => '',
            'descripcion' => '',
            'tipo_beneficio' => TipoBeneficioConvenio::Descuento->value,
            'monto_descuento' => '',
            'porcentaje_descuento' => '',
            'id_proceso' => '',
            'fecha_inicio' => '',
            'fecha_fin' => '',
            'cupo_maximo' => '',
        ];
        $this->resetValidation();
    }

    #[Computed]
    public function convenios(): LengthAwarePaginator
    {
        return ConvenioProceso::query()
            ->with(['convenio', 'proceso.semestre'])
            ->withCount(['inscripciones', 'reglasPago'])
            ->when(filled($this->buscar), function (Builder $query): void {
                $search = $this->normalizarTexto($this->buscar);

                $query->where(function (Builder $innerQuery) use ($search): void {
                    $innerQuery->whereHas('convenio', fn (Builder $convenioQuery) => $convenioQuery
                        ->where('codigo_con', 'like', "%{$search}%")
                        ->orWhere('nombre_con', 'like', "%{$search}%"))
                        ->orWhereHas('proceso', fn (Builder $procesoQuery) => $procesoQuery
                            ->where('nombre_pro', 'like', "%{$search}%")
                            ->orWhereHas('semestre', fn (Builder $semestreQuery) => $semestreQuery
                                ->where('nombre_sem', 'like', "%{$search}%")));
                });
            })
            ->when(filled($this->idSemestre), fn (Builder $query) => $query
                ->whereHas('proceso', fn (Builder $procesoQuery) => $procesoQuery
                    ->where('id_semestre', (int) $this->idSemestre)))
            ->when(filled($this->idProceso), fn (Builder $query) => $query->where('id_proceso', (int) $this->idProceso))
            ->when($this->estadosSeleccionados !== [], fn (Builder $query) => $query->whereIn('estado_cpr', $this->estadosSeleccionados))
            ->orderByDesc('id_cpr')
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

    private function mayusculas(string $valor): string
    {
        return mb_strtoupper($valor, 'UTF-8');
    }

    private function procesoSeleccionado(): ?Proceso
    {
        if (blank($this->convenioForm['id_proceso'])) {
            return null;
        }

        return Proceso::query()->find((int) $this->convenioForm['id_proceso']);
    }
};
