<?php

use App\Enums\EstadoCatalogo;
use App\Models\ConceptoPago;
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
#[Title('Conceptos de pago')]
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
     * @var array{codigo: string, nombre: string, descripcion: string, permite_cuotas: bool}
     */
    public array $conceptoForm = [
        'codigo' => '',
        'nombre' => '',
        'descripcion' => '',
        'permite_cuotas' => false,
    ];

    public string $tituloModal = 'Nuevo concepto de pago';

    public bool $isEdit = false;

    public ?int $editingId = null;

    public function mount(): void
    {
        $this->autorizarPermiso('concepto_pago', 'indice');
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
            'conceptoForm.codigo' => [
                'required',
                'string',
                'max:20',
                'regex:/^\s*[A-Za-z0-9_-]+\s*$/',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($this->existeConceptoCon('codigo_cpg', (string) $value)) {
                        $fail('Ya existe un concepto de pago con ese código.');
                    }
                },
            ],
            'conceptoForm.nombre' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($this->existeConceptoCon('nombre_cpg', (string) $value)) {
                        $fail('Ya existe un concepto de pago con ese nombre.');
                    }
                },
            ],
            'conceptoForm.descripcion' => ['nullable', 'string', 'max:1000'],
            'conceptoForm.permite_cuotas' => [
                'boolean',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ((bool) $value || $this->editingId === null) {
                        return;
                    }

                    $tieneReglasConCuotas = ConceptoPago::query()
                        ->whereKey($this->editingId)
                        ->whereHas('reglasPago', fn (Builder $query) => $query->where('permite_cuotas_rpa', true))
                        ->exists();

                    if ($tieneReglasConCuotas) {
                        $fail('No se puede quitar porque hay reglas de pago en cuotas que usan este concepto.');
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
            'conceptoForm.codigo.required' => 'El código es obligatorio.',
            'conceptoForm.codigo.max' => 'El código no debe superar los 20 caracteres.',
            'conceptoForm.codigo.regex' => 'El código solo admite letras, números, guion y guion bajo.',
            'conceptoForm.nombre.required' => 'El nombre es obligatorio.',
            'conceptoForm.nombre.max' => 'El nombre no debe superar los 255 caracteres.',
            'conceptoForm.descripcion.max' => 'La descripción no debe superar los 1000 caracteres.',
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

    public function cargarConcepto(?int $id = null): void
    {
        $this->autorizarPermiso('concepto_pago', $id === null ? 'crear' : 'editar');
        $this->resetValidation();

        if ($id === null) {
            $this->resetForm();
            $this->modal('modal-concepto')->show();

            return;
        }

        $concepto = ConceptoPago::query()->findOrFail($id);

        $this->editingId = $concepto->id_cpg;
        $this->conceptoForm = [
            'codigo' => $concepto->codigo_cpg,
            'nombre' => $concepto->nombre_cpg,
            'descripcion' => (string) ($concepto->descripcion_cpg ?? ''),
            'permite_cuotas' => (bool) $concepto->permite_cuotas_cpg,
        ];
        $this->tituloModal = 'Editar concepto de pago';
        $this->isEdit = true;

        $this->modal('modal-concepto')->show();
    }

    public function guardar(): void
    {
        $this->autorizarPermiso('concepto_pago', $this->isEdit ? 'editar' : 'crear');
        $data = $this->validate();

        try {
            DB::transaction(function () use ($data): void {
                $payload = [
                    'codigo_cpg' => $this->normalizarMayusculas($data['conceptoForm']['codigo']),
                    'nombre_cpg' => $this->normalizarMayusculas($data['conceptoForm']['nombre']),
                    'descripcion_cpg' => $this->normalizarTexto($data['conceptoForm']['descripcion'] ?? null),
                    'permite_cuotas_cpg' => (bool) ($data['conceptoForm']['permite_cuotas'] ?? false),
                ];

                if ($this->editingId !== null) {
                    ConceptoPago::query()->findOrFail($this->editingId)->update($payload);

                    return;
                }

                ConceptoPago::query()->create([
                    ...$payload,
                    'estado_cpg' => EstadoCatalogo::Habilitado->value,
                ]);
            });
        } catch (Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo guardar el concepto de pago. Intente nuevamente.', type: 'error');

            return;
        }

        $this->dispatch(
            'toast',
            message: $this->isEdit ? 'Concepto de pago actualizado correctamente.' : 'Concepto de pago registrado correctamente.',
            type: 'success'
        );

        unset($this->conceptos);
        $this->modal('modal-concepto')->close();
        $this->resetForm();
    }

    #[On('concepto-cambiar-estado')]
    public function cambiarEstado(?int $id_concepto = null): void
    {
        $this->autorizarPermiso('concepto_pago', 'estado');

        if ($id_concepto === null) {
            $this->dispatch('toast', message: 'ID de concepto de pago no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_concepto): void {
                $concepto = ConceptoPago::query()->findOrFail($id_concepto);

                $concepto->update([
                    'estado_cpg' => $concepto->estado_cpg === EstadoCatalogo::Habilitado
                        ? EstadoCatalogo::Deshabilitado->value
                        : EstadoCatalogo::Habilitado->value,
                ]);
            });
        } catch (Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo cambiar el estado del concepto de pago.', type: 'error');

            return;
        }

        unset($this->conceptos);
        $this->dispatch('toast', message: 'Estado del concepto de pago actualizado.', type: 'success');
    }

    #[On('concepto-eliminar')]
    public function eliminar(?int $id_concepto = null): void
    {
        $this->autorizarPermiso('concepto_pago', 'eliminar');

        if ($id_concepto === null) {
            $this->dispatch('toast', message: 'ID de concepto de pago no proporcionado.', type: 'error');

            return;
        }

        try {
            DB::transaction(function () use ($id_concepto): void {
                $concepto = ConceptoPago::query()
                    ->withCount(['reglasPago' => fn (Builder $query) => $query->withTrashed()])
                    ->findOrFail($id_concepto);

                if ($concepto->reglas_pago_count > 0) {
                    throw new RuntimeException('El concepto tiene reglas de pago y no puede eliminarse. Puede deshabilitarlo.');
                }

                $concepto->delete();
            });
        } catch (Throwable $throwable) {
            report_if(! $throwable instanceof RuntimeException, $throwable);
            $this->dispatch(
                'toast',
                message: $throwable instanceof RuntimeException ? $throwable->getMessage() : 'No se pudo eliminar el concepto de pago.',
                type: 'error'
            );

            return;
        }

        if ($this->editingId === $id_concepto) {
            $this->resetForm();
            $this->modal('modal-concepto')->close();
        }

        unset($this->conceptos);
        $this->dispatch('toast', message: 'Concepto de pago eliminado correctamente.', type: 'success');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'isEdit']);
        $this->tituloModal = 'Nuevo concepto de pago';
        $this->conceptoForm = [
            'codigo' => '',
            'nombre' => '',
            'descripcion' => '',
            'permite_cuotas' => false,
        ];
        $this->resetValidation();
    }

    #[Computed]
    public function conceptos(): LengthAwarePaginator
    {
        return ConceptoPago::query()
            ->withCount('reglasPago')
            ->when(filled($this->buscar), function (Builder $query): void {
                $search = $this->normalizarTexto($this->buscar);

                $query->where(function (Builder $innerQuery) use ($search): void {
                    $innerQuery->where('codigo_cpg', 'like', "%{$search}%")
                        ->orWhere('nombre_cpg', 'like', "%{$search}%");
                });
            })
            ->when($this->estadosSeleccionados !== [], function (Builder $query): void {
                $query->whereIn('estado_cpg', $this->estadosSeleccionados);
            })
            ->orderBy('nombre_cpg')
            ->paginate(10);
    }

    /**
     * Revisa duplicados también entre los conceptos eliminados, porque la
     * tabla mantiene los índices únicos sobre ellas.
     */
    private function existeConceptoCon(string $columna, string $valor): bool
    {
        $valor = $this->normalizarMayusculas($valor);

        if ($valor === '') {
            return false;
        }

        return ConceptoPago::query()
            ->withTrashed()
            ->whereRaw("UPPER({$columna}) = ?", [$valor])
            ->when($this->editingId, fn (Builder $query) => $query->where('id_cpg', '!=', $this->editingId))
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
