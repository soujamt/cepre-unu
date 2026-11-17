<?php

use App\Enums\EstadoImportacionBancoDetalle;
use App\Exports\PagosExport;
use App\Models\ImportacionBancoDetalle;
use App\Traits\AuthorizesPermisos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Todos los pagos (del TXT del banco y registrados a mano), como la hoja
 * "PAGOS" del sistema anterior: LIBRE si aún no se usa y REGISTRADO cuando
 * ya pertenece a una inscripción.
 */
new
#[Layout('layouts.app')]
#[Title('Pagos')]
class extends Component
{
    use AuthorizesPermisos;
    use WithPagination;

    /**
     * Cuentas de la UNU donde se registran pagos a mano.
     */
    public const UBICACIONES = ['ADMISIÓN', 'POSGRADO', 'PARENTESCO UNU', 'OTRA CUENTA UNU'];

    #[Url(as: 'q')]
    public string $buscar = '';

    #[Url(as: 'estado')]
    public string $filtroEstado = '';

    /**
     * @var array{fecha:string,dni:string,referencia:string,importe:string,numero_operacion:string,ubicacion:string,observacion:string}
     */
    public array $pagoForm = [];

    public ?int $editingId = null;

    public bool $soloObservacion = false;

    public function mount(): void
    {
        $this->autorizarPermiso('pago', 'indice');
        $this->resetForm();
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
        if ($this->soloObservacion) {
            return ['pagoForm.observacion' => ['nullable', 'string', 'max:255']];
        }

        return [
            'pagoForm.fecha' => ['required', 'date', 'before_or_equal:today'],
            'pagoForm.dni' => ['required', 'regex:/^\d{8,12}$/'],
            'pagoForm.referencia' => ['nullable', 'string', 'max:255'],
            'pagoForm.importe' => ['required', 'numeric', 'gte:0'],
            'pagoForm.numero_operacion' => [
                'required',
                'regex:/^\d{1,30}$/',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $existe = ImportacionBancoDetalle::query()
                        ->where('numero_documento_ibd', trim((string) $this->pagoForm['dni']))
                        ->where('numero_operacion_visible_ibd', mb_strtoupper(trim((string) $value), 'UTF-8'))
                        ->when($this->editingId, fn (Builder $query) => $query->where('id_ibd', '!=', $this->editingId))
                        ->exists();

                    if ($existe) {
                        $fail('Ya existe un pago con ese número de operación para el mismo DNI.');
                    }
                },
            ],
            'pagoForm.ubicacion' => ['required', 'string', 'max:255'],
            'pagoForm.observacion' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'pagoForm.fecha.required' => 'La fecha es obligatoria.',
            'pagoForm.fecha.before_or_equal' => 'La fecha no puede ser futura.',
            'pagoForm.dni.required' => 'El DNI es obligatorio.',
            'pagoForm.dni.regex' => 'El DNI debe tener entre 8 y 12 dígitos.',
            'pagoForm.importe.required' => 'El importe es obligatorio.',
            'pagoForm.importe.gte' => 'El importe no puede ser negativo.',
            'pagoForm.numero_operacion.required' => 'El número de operación es obligatorio.',
            'pagoForm.numero_operacion.regex' => 'El número de operación solo admite dígitos.',
            'pagoForm.ubicacion.required' => 'Indique dónde se realizó el pago.',
        ];
    }

    public function cargarPago(?int $id = null): void
    {
        $this->resetForm();

        if ($id === null) {
            $this->autorizarPermiso('pago', 'crear');
            $this->modal('modal-pago')->show();

            return;
        }

        $this->autorizarPermiso('pago', 'editar');
        $pago = ImportacionBancoDetalle::query()->findOrFail($id);

        $this->editingId = $pago->id_ibd;
        $this->soloObservacion = ! $this->esEditable($pago);
        $this->pagoForm = [
            'fecha' => $pago->fecha_pago_ibd?->format('Y-m-d') ?? '',
            'dni' => (string) $pago->numero_documento_ibd,
            'referencia' => (string) ($pago->referencia_ibd ?? $pago->nombre_depositante_ibd ?? ''),
            'importe' => number_format((float) $pago->monto_ibd, 2, '.', ''),
            'numero_operacion' => (string) ($pago->numero_operacion_visible_ibd ?? ''),
            'ubicacion' => (string) ($pago->ubicacion_ibd ?? ''),
            'observacion' => (string) ($pago->observacion_ibd ?? ''),
        ];

        $this->modal('modal-pago')->show();
    }

    public function guardar(): void
    {
        $this->autorizarPermiso('pago', $this->editingId === null ? 'crear' : 'editar');
        $data = $this->validate()['pagoForm'];

        $observacion = $this->nullableUpper($data['observacion'] ?? null);

        if ($this->soloObservacion) {
            ImportacionBancoDetalle::query()->findOrFail($this->editingId)->update(['observacion_ibd' => $observacion]);
        } else {
            $operacion = mb_strtoupper(trim($data['numero_operacion']), 'UTF-8');
            $payload = [
                'origen_ibd' => 'MANUAL',
                'numero_documento_ibd' => trim($data['dni']),
                'referencia_ibd' => $this->nullableUpper($data['referencia'] ?? null),
                'numero_operacion_raw_ibd' => $operacion,
                'numero_operacion_visible_ibd' => $operacion,
                'numero_operacion_ibd' => $operacion,
                'fecha_pago_ibd' => $data['fecha'],
                'monto_ibd' => round((float) $data['importe'], 2),
                'ubicacion_ibd' => mb_strtoupper(trim($data['ubicacion']), 'UTF-8'),
                'observacion_ibd' => $observacion,
            ];

            if ($this->editingId === null) {
                ImportacionBancoDetalle::query()->create([
                    ...$payload,
                    'estado_ibd' => EstadoImportacionBancoDetalle::Pendiente->value,
                ]);
            } else {
                $pago = ImportacionBancoDetalle::query()->findOrFail($this->editingId);
                abort_unless($this->esEditable($pago), 403);
                $pago->update($payload);
            }
        }

        $mensaje = $this->editingId === null ? 'Pago registrado correctamente.' : 'Pago actualizado correctamente.';

        unset($this->pagos);
        $this->modal('modal-pago')->close();
        $this->resetForm();
        $this->dispatch('toast', message: $mensaje, type: 'success');
    }

    #[On('pago-eliminar')]
    public function eliminar(?int $id_pago = null): void
    {
        $this->autorizarPermiso('pago', 'eliminar');

        $pago = ImportacionBancoDetalle::query()->find($id_pago);

        if ($pago === null || ! $this->esEditable($pago)) {
            $this->dispatch('toast', message: 'Solo se pueden eliminar pagos registrados a mano que sigan libres.', type: 'error');

            return;
        }

        $pago->delete();
        unset($this->pagos);
        $this->dispatch('toast', message: 'Pago eliminado correctamente.', type: 'success');
    }

    public function exportarExcel(): BinaryFileResponse
    {
        $this->autorizarPermiso('pago', 'exportar_excel');

        return Excel::download(
            new PagosExport($this->basePagosQuery()->with($this->relaciones())->orderByDesc('fecha_pago_ibd')->orderByDesc('id_ibd')->get()),
            'pagos-cepre-'.now()->format('Ymd-His').'.xlsx',
        );
    }

    /**
     * Un pago manual se puede corregir o borrar mientras siga libre; los del
     * TXT y los ya registrados solo admiten observación.
     */
    public function esEditable(ImportacionBancoDetalle $pago): bool
    {
        return $pago->origen_ibd === 'MANUAL'
            && $pago->estado_ibd === EstadoImportacionBancoDetalle::Pendiente
            && ! $pago->inscripcionPago()->exists();
    }

    /**
     * @return array{total:int,libres:int,registrados:int,monto:float}
     */
    #[Computed]
    public function resumen(): array
    {
        $query = $this->basePagosQuery();

        return [
            'total' => (clone $query)->count(),
            'libres' => (clone $query)->where('estado_ibd', EstadoImportacionBancoDetalle::Pendiente->value)->count(),
            'registrados' => (clone $query)->where('estado_ibd', EstadoImportacionBancoDetalle::Asociado->value)->count(),
            'monto' => (float) (clone $query)->sum('monto_ibd'),
        ];
    }

    #[Computed]
    public function pagos(): LengthAwarePaginator
    {
        return $this->basePagosQuery()
            ->with($this->relaciones())
            ->orderByDesc('fecha_pago_ibd')
            ->orderByDesc('id_ibd')
            ->paginate(15);
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->soloObservacion = false;
        $this->pagoForm = [
            'fecha' => now()->toDateString(),
            'dni' => '',
            'referencia' => '',
            'importe' => '',
            'numero_operacion' => '',
            'ubicacion' => 'ADMISIÓN',
            'observacion' => '',
        ];
        $this->resetValidation();
    }

    private function basePagosQuery(): Builder
    {
        return ImportacionBancoDetalle::query()
            ->when(filled($this->buscar), function (Builder $query): void {
                $texto = trim($this->buscar);

                $query->where(function (Builder $inner) use ($texto): void {
                    $inner->where('numero_documento_ibd', 'like', "%{$texto}%")
                        ->orWhere('numero_operacion_visible_ibd', 'like', "%{$texto}%")
                        ->orWhere('referencia_ibd', 'like', "%{$texto}%")
                        ->orWhere('nombre_depositante_ibd', 'like', "%{$texto}%");
                });
            })
            ->when(filled($this->filtroEstado), fn (Builder $query) => $query->where('estado_ibd', $this->filtroEstado));
    }

    /**
     * @return list<string>
     */
    private function relaciones(): array
    {
        return ['inscripcionPago.obligacionPago.inscripcion.sede'];
    }

    private function nullableUpper(?string $valor): ?string
    {
        $valor = trim((string) $valor);

        return $valor === '' ? null : mb_strtoupper($valor, 'UTF-8');
    }
};
