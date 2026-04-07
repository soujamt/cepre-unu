<?php

use App\Enums\EstadoCatalogo;
use App\Models\ConceptoPago;
use App\Models\ConvenioProceso;
use App\Models\ModalidadAdmision;
use App\Models\ReglaPago;
use App\Models\Proceso;
use App\Traits\AuthorizesPermisos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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
#[Title('REGLAS DE PAGO')]
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
     * @var array{
     *     id_concepto_pago:string,
     *     id_proceso:string,
     *     id_modalidad_admision:string,
     *     id_convenio_proceso:string,
     *     monto_total:string,
     *     permite_cuotas:bool,
     *     monto_minimo_primera_cuota:string,
     *     numero_maximo_cuotas:string,
     *     requiere_pago_completo:bool,
     *     vigente_desde:string,
     *     vigente_hasta:string
     * }
     */
    public array $reglaPagoForm = [
        'id_concepto_pago' => '',
        'id_proceso' => '',
        'id_modalidad_admision' => '',
        'id_convenio_proceso' => '',
        'monto_total' => '',
        'permite_cuotas' => false,
        'monto_minimo_primera_cuota' => '',
        'numero_maximo_cuotas' => '1',
        'requiere_pago_completo' => false,
        'vigente_desde' => '',
        'vigente_hasta' => '',
    ];

    public string $tituloModal = 'Nueva regla de pago';

    public bool $isEdit = false;

    public ?int $editingId = null;

    public function mount(): void
    {
        $this->autorizarPermiso('regla_pago', 'indice');
    }

    public function updatedBuscar(): void
    {
        $this->resetPage();
    }

    public function updatedFiltroEstado(): void
    {
        $this->resetPage();
    }

    public function updatedReglaPagoFormIdProceso(): void
    {
        if (
            filled($this->reglaPagoForm['id_convenio_proceso'])
            && ! $this->conveniosProcesoDisponibles()->contains('id_cpr', (int) $this->reglaPagoForm['id_convenio_proceso'])
        ) {
            $this->reglaPagoForm['id_convenio_proceso'] = '';
        }
    }

    public function updatedReglaPagoFormPermiteCuotas(bool $value): void
    {
        if (! $value) {
            $this->reglaPagoForm['numero_maximo_cuotas'] = '1';
            $this->reglaPagoForm['monto_minimo_primera_cuota'] = '';
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'reglaPagoForm.id_concepto_pago' => ['required', 'integer', 'exists:tbl_concepto_pago,id_cpg'],
            'reglaPagoForm.id_proceso' => [
                'required',
                'integer',
                'exists:tbl_proceso,id_pro',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (blank($this->reglaPagoForm['id_concepto_pago']) || blank($value)) {
                        return;
                    }

                    $query = ReglaPago::query()
                        ->withTrashed()
                        ->where('id_concepto_pago', (int) $this->reglaPagoForm['id_concepto_pago'])
                        ->where('id_proceso', (int) $value);

                    $modalidadId = $this->reglaPagoForm['id_modalidad_admision'];
                    $convenioProcesoId = $this->reglaPagoForm['id_convenio_proceso'];

                    if (filled($modalidadId)) {
                        $query->where('id_modalidad_admision', (int) $modalidadId);
                    } else {
                        $query->whereNull('id_modalidad_admision');
                    }

                    if (filled($convenioProcesoId)) {
                        $query->where('id_convenio_proceso', (int) $convenioProcesoId);
                    } else {
                        $query->whereNull('id_convenio_proceso');
                    }

                    if ($this->editingId !== null) {
                        $query->where('id_rpa', '!=', $this->editingId);
                    }

                    if ($query->exists()) {
                        $fail('Ya existe una regla de pago con ese mismo contexto.');
                    }
                },
            ],
            'reglaPagoForm.id_modalidad_admision' => ['nullable', 'integer', 'exists:tbl_modalidad_admision,id_mad'],
            'reglaPagoForm.id_convenio_proceso' => [
                'nullable',
                'integer',
                'exists:tbl_convenio_proceso,id_cpr',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (blank($value) || blank($this->reglaPagoForm['id_proceso'])) {
                        return;
                    }

                    $perteneceAlProceso = ConvenioProceso::query()
                        ->where('id_cpr', (int) $value)
                        ->where('id_proceso', (int) $this->reglaPagoForm['id_proceso'])
                        ->exists();

                    if (! $perteneceAlProceso) {
                        $fail('El convenio seleccionado no corresponde al proceso indicado.');
                    }
                },
            ],
            'reglaPagoForm.monto_total' => ['required', 'numeric', 'gt:0'],
            'reglaPagoForm.permite_cuotas' => [
                'boolean',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! (bool) $value) {
                        return;
                    }

                    if (blank($this->reglaPagoForm['id_concepto_pago'])) {
                        return;
                    }

                    $concepto = ConceptoPago::query()->find((int) $this->reglaPagoForm['id_concepto_pago']);

                    if ($concepto !== null && ! $concepto->permite_cuotas_cpg) {
                        $fail('El concepto seleccionado no permite cuotas.');
                    }
                },
            ],
            'reglaPagoForm.monto_minimo_primera_cuota' => [
                'nullable',
                'numeric',
                'gt:0',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $this->reglaPagoForm['permite_cuotas']) {
                        return;
                    }

                    if (blank($value)) {
                        $fail('El monto minimo de la primera cuota es obligatorio cuando se permiten cuotas.');

                        return;
                    }

                    if ((float) $value > (float) ($this->reglaPagoForm['monto_total'] ?: 0)) {
                        $fail('El monto minimo de la primera cuota no puede ser mayor al monto total.');
                    }
                },
            ],
            'reglaPagoForm.numero_maximo_cuotas' => [
                'required',
                'integer',
                'min:1',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($this->reglaPagoForm['permite_cuotas'] && (int) $value < 2) {
                        $fail('Debe indicar al menos 2 cuotas cuando la regla permite cuotas.');
                    }

                    if (! $this->reglaPagoForm['permite_cuotas'] && (int) $value !== 1) {
                        $fail('Cuando no se permiten cuotas, el maximo debe ser 1.');
                    }
                },
            ],
            'reglaPagoForm.requiere_pago_completo' => ['boolean'],
            'reglaPagoForm.vigente_desde' => ['required', 'date'],
            'reglaPagoForm.vigente_hasta' => ['nullable', 'date', 'after_or_equal:reglaPagoForm.vigente_desde'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'reglaPagoForm.id_concepto_pago.required' => 'El concepto de pago es obligatorio.',
            'reglaPagoForm.id_proceso.required' => 'El proceso es obligatorio.',
            'reglaPagoForm.id_modalidad_admision.exists' => 'La modalidad seleccionada no es valida.',
            'reglaPagoForm.id_convenio_proceso.exists' => 'El convenio seleccionado no es valido.',
            'reglaPagoForm.monto_total.required' => 'El monto total es obligatorio.',
            'reglaPagoForm.monto_total.gt' => 'El monto total debe ser mayor a cero.',
            'reglaPagoForm.numero_maximo_cuotas.required' => 'El numero maximo de cuotas es obligatorio.',
            'reglaPagoForm.vigente_desde.required' => 'La vigencia desde es obligatoria.',
            'reglaPagoForm.vigente_hasta.after_or_equal' => 'La vigencia final no puede ser menor que la inicial.',
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

    #[Computed]
    public function conceptosPagoDisponibles(): Collection
    {
        return ConceptoPago::query()
            ->where('estado_cpg', EstadoCatalogo::Habilitado->value)
            ->orderBy('nombre_cpg')
            ->get();
    }

    #[Computed]
    public function procesosDisponibles(): Collection
    {
        return Proceso::query()
            ->with('semestre')
            ->where('estado_pro', EstadoCatalogo::Habilitado->value)
            ->orderByDesc('id_pro')
            ->get();
    }

    #[Computed]
    public function modalidadesDisponibles(): Collection
    {
        return ModalidadAdmision::query()
            ->where('estado_mad', EstadoCatalogo::Habilitado->value)
            ->orderBy('nombre_mad')
            ->get();
    }

    #[Computed]
    public function conveniosProcesoDisponibles(): Collection
    {
        return ConvenioProceso::query()
            ->with(['convenio', 'proceso'])
            ->where('estado_cpr', EstadoCatalogo::Habilitado->value)
            ->when(filled($this->reglaPagoForm['id_proceso']), fn (Builder $query) => $query
                ->where('id_proceso', (int) $this->reglaPagoForm['id_proceso']))
            ->orderByDesc('id_cpr')
            ->get();
    }

    public function limpiarFiltroEstado(): void
    {
        $this->filtroEstado = [];
    }

    public function cargarReglaPago(?int $id = null): void
    {
        $this->autorizarPermiso('regla_pago', $id === null ? 'crear' : 'editar');
        $this->resetValidation();

        if ($id === null) {
            $this->resetForm();
            $this->tituloModal = 'Nueva regla de pago';
            $this->isEdit = false;
            $this->modal('modal-regla-pago')->show();

            return;
        }

        $reglaPago = ReglaPago::query()->findOrFail($id);

        $this->editingId = $reglaPago->id_rpa;
        $this->reglaPagoForm = [
            'id_concepto_pago' => (string) $reglaPago->id_concepto_pago,
            'id_proceso' => (string) $reglaPago->id_proceso,
            'id_modalidad_admision' => $reglaPago->id_modalidad_admision ? (string) $reglaPago->id_modalidad_admision : '',
            'id_convenio_proceso' => $reglaPago->id_convenio_proceso ? (string) $reglaPago->id_convenio_proceso : '',
            'monto_total' => number_format((float) $reglaPago->monto_total_rpa, 2, '.', ''),
            'permite_cuotas' => $reglaPago->permite_cuotas_rpa,
            'monto_minimo_primera_cuota' => $reglaPago->monto_minimo_primera_cuota_rpa !== null
                ? number_format((float) $reglaPago->monto_minimo_primera_cuota_rpa, 2, '.', '')
                : '',
            'numero_maximo_cuotas' => (string) $reglaPago->numero_maximo_cuotas_rpa,
            'requiere_pago_completo' => $reglaPago->requiere_pago_completo_rpa,
            'vigente_desde' => $reglaPago->vigente_desde_rpa?->format('Y-m-d') ?? '',
            'vigente_hasta' => $reglaPago->vigente_hasta_rpa?->format('Y-m-d') ?? '',
        ];
        $this->tituloModal = 'Editar regla de pago';
        $this->isEdit = true;

        $this->modal('modal-regla-pago')->show();
    }

    public function guardar(): void
    {
        $this->autorizarPermiso('regla_pago', $this->isEdit ? 'editar' : 'crear');
        $data = $this->validate();

        try {
            DB::transaction(function () use ($data): void {
                $permiteCuotas = (bool) $data['reglaPagoForm']['permite_cuotas'];

                $payload = [
                    'id_concepto_pago' => (int) $data['reglaPagoForm']['id_concepto_pago'],
                    'id_proceso' => (int) $data['reglaPagoForm']['id_proceso'],
                    'id_modalidad_admision' => filled($data['reglaPagoForm']['id_modalidad_admision'])
                        ? (int) $data['reglaPagoForm']['id_modalidad_admision']
                        : null,
                    'id_convenio_proceso' => filled($data['reglaPagoForm']['id_convenio_proceso'])
                        ? (int) $data['reglaPagoForm']['id_convenio_proceso']
                        : null,
                    'monto_total_rpa' => (float) $data['reglaPagoForm']['monto_total'],
                    'permite_cuotas_rpa' => $permiteCuotas,
                    'monto_minimo_primera_cuota_rpa' => $permiteCuotas
                        ? (float) $data['reglaPagoForm']['monto_minimo_primera_cuota']
                        : 0,
                    'numero_maximo_cuotas_rpa' => $permiteCuotas
                        ? (int) $data['reglaPagoForm']['numero_maximo_cuotas']
                        : 1,
                    'requiere_pago_completo_rpa' => (bool) $data['reglaPagoForm']['requiere_pago_completo'],
                    'vigente_desde_rpa' => $data['reglaPagoForm']['vigente_desde'],
                    'vigente_hasta_rpa' => filled($data['reglaPagoForm']['vigente_hasta'])
                        ? $data['reglaPagoForm']['vigente_hasta']
                        : null,
                ];

                if ($this->editingId !== null) {
                    ReglaPago::query()->findOrFail($this->editingId)->update($payload);

                    return;
                }

                ReglaPago::query()->create([
                    ...$payload,
                    'estado_rpa' => EstadoCatalogo::Habilitado->value,
                ]);
            });
        } catch (\Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo guardar la regla de pago. Intente nuevamente.', type: 'error');

            return;
        }

        $this->dispatch(
            'toast',
            message: $this->isEdit ? 'Regla de pago actualizada correctamente.' : 'Regla de pago registrada correctamente.',
            type: 'success'
        );

        unset($this->reglasPago);
        $this->modal('modal-regla-pago')->close();
        $this->resetForm();
    }

    #[On('regla-pago-cambiar-estado')]
    public function cambiarEstado(?int $id_regla_pago = null): void
    {
        $this->autorizarPermiso('regla_pago', 'estado');

        if ($id_regla_pago === null) {
            $this->dispatch('toast', message: 'ID de regla de pago no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_regla_pago): void {
                $reglaPago = ReglaPago::query()->findOrFail($id_regla_pago);

                $reglaPago->update([
                    'estado_rpa' => $reglaPago->estado_rpa === EstadoCatalogo::Habilitado
                        ? EstadoCatalogo::Deshabilitado->value
                        : EstadoCatalogo::Habilitado->value,
                ]);
            });
        } catch (\Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo cambiar el estado de la regla de pago.', type: 'error');

            return;
        }

        unset($this->reglasPago);
        $this->dispatch('toast', message: 'Estado de la regla de pago actualizado.', type: 'success');
    }

    #[On('regla-pago-eliminar')]
    public function eliminar(?int $id_regla_pago = null): void
    {
        $this->autorizarPermiso('regla_pago', 'eliminar');

        if ($id_regla_pago === null) {
            $this->dispatch('toast', message: 'ID de regla de pago no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_regla_pago): void {
                $reglaPago = ReglaPago::query()
                    ->withCount('obligacionesPago')
                    ->findOrFail($id_regla_pago);

                if ($reglaPago->obligaciones_pago_count > 0) {
                    throw new \RuntimeException('La regla de pago tiene obligaciones relacionadas y no puede eliminarse.');
                }

                $reglaPago->delete();
            });
        } catch (\Throwable $throwable) {
            report_if(! $throwable instanceof \RuntimeException, $throwable);
            $this->dispatch(
                'toast',
                message: $throwable->getMessage() ?: 'No se pudo eliminar la regla de pago.',
                type: 'error'
            );

            return;
        }

        if ($this->editingId === $id_regla_pago) {
            $this->resetForm();
            $this->modal('modal-regla-pago')->close();
        }

        unset($this->reglasPago);
        $this->dispatch('toast', message: 'Regla de pago eliminada correctamente.', type: 'success');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'isEdit']);
        $this->tituloModal = 'Nueva regla de pago';
        $this->reglaPagoForm = [
            'id_concepto_pago' => '',
            'id_proceso' => '',
            'id_modalidad_admision' => '',
            'id_convenio_proceso' => '',
            'monto_total' => '',
            'permite_cuotas' => false,
            'monto_minimo_primera_cuota' => '',
            'numero_maximo_cuotas' => '1',
            'requiere_pago_completo' => false,
            'vigente_desde' => '',
            'vigente_hasta' => '',
        ];
        $this->resetValidation();
    }

    #[Computed]
    public function reglasPago(): LengthAwarePaginator
    {
        return ReglaPago::query()
            ->with([
                'conceptoPago',
                'proceso.semestre',
                'modalidadAdmision',
                'convenioProceso.convenio',
            ])
            ->withCount('obligacionesPago')
            ->when(filled($this->buscar), function (Builder $query): void {
                $search = $this->normalizarTexto($this->buscar);

                $query->where(function (Builder $innerQuery) use ($search): void {
                    $innerQuery->whereHas('conceptoPago', fn (Builder $conceptoQuery) => $conceptoQuery
                        ->where('nombre_cpg', 'like', "%{$search}%")
                        ->orWhere('codigo_cpg', 'like', "%{$search}%"))
                        ->orWhereHas('proceso', fn (Builder $procesoQuery) => $procesoQuery
                            ->where('nombre_pro', 'like', "%{$search}%")
                            ->orWhereHas('semestre', fn (Builder $semestreQuery) => $semestreQuery
                                ->where('nombre_sem', 'like', "%{$search}%")))
                        ->orWhereHas('modalidadAdmision', fn (Builder $modalidadQuery) => $modalidadQuery
                            ->where('nombre_mad', 'like', "%{$search}%"))
                        ->orWhereHas('convenioProceso.convenio', fn (Builder $convenioQuery) => $convenioQuery
                            ->where('nombre_con', 'like', "%{$search}%"));
                });
            })
            ->when($this->estadosSeleccionados !== [], fn (Builder $query) => $query->whereIn('estado_rpa', $this->estadosSeleccionados))
            ->orderByDesc('id_rpa')
            ->paginate(10);
    }

    public function descripcionContexto(ReglaPago $reglaPago): string
    {
        $proceso = $reglaPago->proceso?->nombre_pro ?? 'SIN PROCESO';
        $modalidad = $reglaPago->modalidadAdmision?->nombre_mad ?? 'TODAS LAS MODALIDADES';
        $convenio = $reglaPago->convenioProceso?->convenio?->nombre_con ?? 'SIN CONVENIO';

        return "{$proceso} | {$modalidad} | {$convenio}";
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
