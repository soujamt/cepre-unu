<?php

use App\Enums\EstadoImportacionBancoDetalle;
use App\Enums\EstadoInscripcion;
use App\Enums\EstadoObligacionPago;
use App\Models\ImportacionBancoDetalle;
use App\Models\InscripcionObligacionPago;
use App\Support\InscripcionPagoManager;
use App\Traits\AuthorizesPermisos;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('VALIDAR PAGOS')]
class extends Component
{
    use AuthorizesPermisos;

    public string $buscarObligacion = '';

    public ?int $idObligacionSeleccionada = null;

    public string $dniBusqueda = '';

    public string $numeroOperacionBusqueda = '';

    public string $fechaPagoBusqueda = '';

    public ?int $idDetalleSeleccionado = null;

    /**
     * @var array<int, int>
     */
    public array $resultadoDetalleIds = [];

    public bool $busquedaRealizada = false;

    public function mount(): void
    {
        $this->autorizarPermiso('pago', 'crear');
        $this->fechaPagoBusqueda = now()->subDay()->toDateString();
    }

    public function updatedBuscarObligacion(): void
    {
        $this->limpiarSeleccionPago();
    }

    public function seleccionarObligacion(int $idObligacion): void
    {
        $this->idObligacionSeleccionada = $idObligacion;
        $this->limpiarSeleccionPago();

        $obligacion = $this->obligacionSeleccionada;

        if ($obligacion === null) {
            return;
        }

        $this->dniBusqueda = $obligacion->inscripcion?->persona?->numero_documento_per ?? '';
    }

    public function buscarPagos(InscripcionPagoManager $manager): void
    {
        $this->validate([
            'idObligacionSeleccionada' => ['required', 'integer', 'exists:tbl_inscripcion_obligacion_pago,id_iop'],
            'dniBusqueda' => ['required', 'string'],
            'numeroOperacionBusqueda' => ['required', 'string'],
            'fechaPagoBusqueda' => ['required', 'date'],
        ], [
            'idObligacionSeleccionada.required' => 'Debe seleccionar una obligacion de pago.',
            'dniBusqueda.required' => 'El DNI es obligatorio para buscar pagos.',
            'numeroOperacionBusqueda.required' => 'La operacion es obligatoria para buscar pagos.',
            'fechaPagoBusqueda.required' => 'La fecha de pago es obligatoria para buscar pagos.',
        ]);

        $this->resultadoDetalleIds = $manager
            ->buscarDetallesDisponibles(
                $this->dniBusqueda,
                $this->numeroOperacionBusqueda,
                $this->fechaPagoBusqueda,
            )
            ->pluck('id_ibd')
            ->map(fn (int|string $id): int => (int) $id)
            ->all();

        $this->idDetalleSeleccionado = null;
        $this->busquedaRealizada = true;
    }

    public function seleccionarDetalle(int $idDetalle): void
    {
        $this->idDetalleSeleccionado = $idDetalle;
    }

    public function confirmarPago(InscripcionPagoManager $manager): void
    {
        $this->validate([
            'idObligacionSeleccionada' => ['required', 'integer', 'exists:tbl_inscripcion_obligacion_pago,id_iop'],
            'idDetalleSeleccionado' => ['required', 'integer'],
        ], [
            'idDetalleSeleccionado.required' => 'Debe seleccionar un pago importado antes de confirmar.',
        ]);

        $obligacion = $this->obligacionSeleccionada;
        $detalle = $this->detalleSeleccionado;

        if ($obligacion === null || $detalle === null) {
            $this->dispatch('toast', message: 'No se pudo cargar la obligacion o el pago seleccionado.', type: 'error');

            return;
        }

        try {
            $manager->asociarDetalleAObligacion($obligacion, $detalle);
        } catch (\DomainException $exception) {
            $this->dispatch('toast', message: $exception->getMessage(), type: 'error');

            return;
        } catch (\Throwable $throwable) {
            report($throwable);
            $this->dispatch('toast', message: 'No se pudo validar el pago seleccionado.', type: 'error');

            return;
        }

        $this->idObligacionSeleccionada = $obligacion->fresh()?->id_iop;
        $this->limpiarSeleccionPago();
        $this->dniBusqueda = $obligacion->inscripcion?->persona?->numero_documento_per ?? $this->dniBusqueda;

        unset($this->obligacionSeleccionada);
        unset($this->obligacionesDisponibles);

        $this->dispatch('toast', message: 'Pago validado y asociado correctamente.', type: 'success');
    }

    public function limpiarBusquedaObligacion(): void
    {
        $this->buscarObligacion = '';
    }

    public function limpiarSeleccionObligacion(): void
    {
        $this->idObligacionSeleccionada = null;
        $this->dniBusqueda = '';
        $this->limpiarSeleccionPago();
    }

    #[Computed]
    public function obligacionesDisponibles(): \Illuminate\Database\Eloquent\Collection
    {
        return InscripcionObligacionPago::query()
            ->with([
                'inscripcion.persona',
                'reglaPago.conceptoPago',
            ])
            ->whereIn('estado_iop', [
                EstadoObligacionPago::Pendiente->value,
                EstadoObligacionPago::Parcial->value,
            ])
            ->whereHas('inscripcion', function (Builder $query): void {
                $query->where('estado_ins', '!=', EstadoInscripcion::Anulado->value);
            })
            ->when(filled($this->buscarObligacion), function (Builder $query): void {
                $texto = $this->normalizarTexto($this->buscarObligacion);

                $query->where(function (Builder $builder) use ($texto): void {
                    $builder->whereHas('inscripcion', function (Builder $inscripcionQuery) use ($texto): void {
                        $inscripcionQuery->where('codigo_ins', 'like', "%{$texto}%")
                            ->orWhereHas('persona', function (Builder $personaQuery) use ($texto): void {
                                $personaQuery->where('numero_documento_per', 'like', "%{$texto}%")
                                    ->orWhere('apellido_paterno_per', 'like', "%{$texto}%")
                                    ->orWhere('apellido_materno_per', 'like', "%{$texto}%")
                                    ->orWhere('nombres_per', 'like', "%{$texto}%");
                            });
                    });
                });
            })
            ->orderByDesc('id_iop')
            ->limit(12)
            ->get();
    }

    #[Computed]
    public function obligacionSeleccionada(): ?InscripcionObligacionPago
    {
        if ($this->idObligacionSeleccionada === null) {
            return null;
        }

        return InscripcionObligacionPago::query()
            ->with([
                'inscripcion.persona',
                'reglaPago.conceptoPago',
                'pagos.importacionBancoDetalle',
            ])
            ->find($this->idObligacionSeleccionada);
    }

    #[Computed]
    public function detallesEncontrados(): \Illuminate\Database\Eloquent\Collection
    {
        if ($this->resultadoDetalleIds === []) {
            return ImportacionBancoDetalle::query()->whereRaw('1 = 0')->get();
        }

        return ImportacionBancoDetalle::query()
            ->whereIn('id_ibd', $this->resultadoDetalleIds)
            ->orderByDesc('fecha_pago_ibd')
            ->orderByDesc('id_ibd')
            ->get();
    }

    #[Computed]
    public function detalleSeleccionado(): ?ImportacionBancoDetalle
    {
        if ($this->idDetalleSeleccionado === null) {
            return null;
        }

        return ImportacionBancoDetalle::query()->find($this->idDetalleSeleccionado);
    }

    public function resumenObligacion(InscripcionObligacionPago $obligacion): string
    {
        $inscripcion = $obligacion->inscripcion;
        $persona = $inscripcion?->persona;

        return collect([
            $inscripcion?->codigo_ins,
            $persona?->numero_documento_per,
            trim(implode(' ', array_filter([
                $persona?->apellido_paterno_per,
                $persona?->apellido_materno_per,
                $persona?->nombres_per,
            ]))),
        ])->filter()->implode(' | ');
    }

    public function resumenDetalle(ImportacionBancoDetalle $detalle): string
    {
        return collect([
            $detalle->numero_documento_ibd ?: 'SIN DOCUMENTO',
            $detalle->numero_operacion_visible_ibd ?: $detalle->numero_operacion_ibd ?: 'SIN OPERACION',
            $detalle->fecha_pago_ibd?->format('d/m/Y'),
        ])->filter()->implode(' | ');
    }

    public function detalleDisponible(ImportacionBancoDetalle $detalle): bool
    {
        return $detalle->estado_ibd === EstadoImportacionBancoDetalle::Pendiente
            && $detalle->inscripcionPago()->doesntExist();
    }

    private function limpiarSeleccionPago(): void
    {
        $this->numeroOperacionBusqueda = '';
        $this->idDetalleSeleccionado = null;
        $this->resultadoDetalleIds = [];
        $this->busquedaRealizada = false;
    }

    private function normalizarTexto(string $valor): string
    {
        return preg_replace('/\s+/u', ' ', trim($valor)) ?: '';
    }
};
