<?php

use App\Enums\EstadoCatalogo;
use App\Enums\TipoBeneficioConvenio;
use App\Models\Convenio;
use App\Models\ConvenioProceso;
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
#[Title('CONVENIOS')]
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
     *     codigo:string,
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
        'codigo' => '',
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
            'convenioForm.codigo' => [
                'required',
                'string',
                'max:50',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $codigo = $this->normalizarCodigo((string) $value);

                    if ($codigo === '') {
                        $fail('El codigo es obligatorio.');

                        return;
                    }

                    $exists = Convenio::query()
                        ->withTrashed()
                        ->where('codigo_con', $codigo)
                        ->when($this->editingConvenioId, fn (Builder $query) => $query->where('id_con', '!=', $this->editingConvenioId))
                        ->exists();

                    if ($exists) {
                        $fail('Ya existe un convenio con ese codigo.');
                    }
                },
            ],
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
            'convenioForm.fecha_inicio' => ['required', 'date'],
            'convenioForm.fecha_fin' => ['required', 'date', 'after_or_equal:convenioForm.fecha_inicio'],
            'convenioForm.cupo_maximo' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'convenioForm.codigo.required' => 'El codigo es obligatorio.',
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
    public function procesosDisponibles(): Collection
    {
        return Proceso::query()
            ->with('semestre')
            ->where('estado_pro', EstadoCatalogo::Habilitado->value)
            ->orderByDesc('id_pro')
            ->get();
    }

    public function limpiarFiltroEstado(): void
    {
        $this->filtroEstado = [];
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
            'codigo' => $convenioProceso->convenio?->codigo_con ?? '',
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
                    'codigo_con' => $this->normalizarCodigo($data['convenioForm']['codigo']),
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
            'codigo' => '',
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
            ->when($this->estadosSeleccionados !== [], fn (Builder $query) => $query->whereIn('estado_cpr', $this->estadosSeleccionados))
            ->orderByDesc('id_cpr')
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
