<?php

namespace App\Support;

use App\Enums\EstadoImportacionBancoDetalle;
use App\Enums\EstadoObligacionPago;
use App\Enums\EstadoValidacionPago;
use App\Models\ImportacionBancoDetalle;
use App\Models\InscripcionObligacionPago;
use App\Models\InscripcionPago;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class InscripcionPagoManager
{
    /**
     * @return Collection<int, ImportacionBancoDetalle>
     */
    public function buscarDetallesDisponibles(
        string $numeroDocumento,
        ?string $numeroOperacionVisible = null,
        CarbonInterface|string|null $fechaPago = null,
    ): Collection {
        $numeroDocumentoNormalizado = $this->normalizarValorNumerico($numeroDocumento);
        $numeroOperacionVisibleNormalizada = $this->normalizarValorNumerico($numeroOperacionVisible);
        $fechaPagoNormalizada = $this->normalizarFecha($fechaPago);

        return ImportacionBancoDetalle::query()
            ->where('estado_ibd', EstadoImportacionBancoDetalle::Pendiente)
            ->whereDoesntHave('inscripcionPago')
            ->where('numero_documento_ibd', $numeroDocumentoNormalizado)
            ->when($numeroOperacionVisibleNormalizada !== null, function ($query) use ($numeroOperacionVisibleNormalizada): void {
                $query->where('numero_operacion_visible_ibd', $numeroOperacionVisibleNormalizada);
            })
            ->when($fechaPagoNormalizada !== null, function ($query) use ($fechaPagoNormalizada): void {
                $query->whereDate('fecha_pago_ibd', $fechaPagoNormalizada->toDateString());
            })
            ->orderByDesc('fecha_pago_ibd')
            ->orderByDesc('id_ibd')
            ->get();
    }

    public function asociarDetalleAObligacion(
        InscripcionObligacionPago $obligacion,
        ImportacionBancoDetalle $detalle,
    ): InscripcionPago {
        $obligacion->loadMissing([
            'inscripcion.persona',
            'reglaPago',
            'pagos',
        ]);
        $detalle->loadMissing('inscripcionPago');

        $this->validarDisponibilidadDetalle($detalle);
        $this->validarDocumentoTitular($obligacion, $detalle);

        return DB::transaction(function () use ($obligacion, $detalle): InscripcionPago {
            $obligacion->refresh()->loadMissing(['reglaPago', 'inscripcion.persona', 'pagos']);
            $detalle->refresh()->loadMissing('inscripcionPago');

            $this->validarDisponibilidadDetalle($detalle);
            $this->validarDocumentoTitular($obligacion, $detalle);

            $montoPagadoActual = (float) $obligacion->pagos()
                ->where('estado_validacion_ipa', EstadoValidacionPago::Verificado)
                ->sum('monto_ipa');
            $saldoPendiente = round((float) $obligacion->monto_total_iop - $montoPagadoActual, 2);
            $montoDetalle = round((float) $detalle->monto_ibd, 2);
            $numeroPagosVerificados = $obligacion->pagos()
                ->where('estado_validacion_ipa', EstadoValidacionPago::Verificado)
                ->count();

            $this->validarMontoSegunRegla($obligacion, $montoDetalle, $saldoPendiente, $numeroPagosVerificados);

            $numeroCuota = ((int) $obligacion->pagos()->max('numero_cuota_ipa')) + 1;

            $pago = InscripcionPago::query()->create([
                'id_inscripcion_obligacion_pago' => $obligacion->id_iop,
                'id_importacion_banco_detalle' => $detalle->id_ibd,
                'numero_cuota_ipa' => $numeroCuota,
                'monto_ipa' => $montoDetalle,
                'fecha_pago_ipa' => $detalle->fecha_pago_ibd,
                'numero_operacion_ipa' => $detalle->numero_operacion_visible_ibd ?: $detalle->numero_operacion_ibd,
                'estado_validacion_ipa' => EstadoValidacionPago::Verificado,
                'validado_en' => now(),
                'id_usuario_validador' => auth()->id(),
            ]);

            $detalle->estado_ibd = EstadoImportacionBancoDetalle::Asociado;
            $detalle->save();

            $montoPagadoNuevo = round($montoPagadoActual + $montoDetalle, 2);
            $saldoNuevo = round(max((float) $obligacion->monto_total_iop - $montoPagadoNuevo, 0), 2);

            $obligacion->monto_pagado_iop = $montoPagadoNuevo;
            $obligacion->saldo_iop = $saldoNuevo;
            $obligacion->estado_iop = $this->resolverEstadoObligacion($montoPagadoNuevo, $saldoNuevo);
            $obligacion->save();

            return $pago->fresh([
                'importacionBancoDetalle',
                'obligacionPago',
            ]);
        });
    }

    private function validarDisponibilidadDetalle(ImportacionBancoDetalle $detalle): void
    {
        if ($detalle->estado_ibd !== EstadoImportacionBancoDetalle::Pendiente) {
            throw new DomainException('El pago bancario seleccionado ya no se encuentra disponible.');
        }

        if ($detalle->inscripcionPago !== null) {
            throw new DomainException('El pago bancario seleccionado ya fue asociado anteriormente.');
        }
    }

    private function validarDocumentoTitular(InscripcionObligacionPago $obligacion, ImportacionBancoDetalle $detalle): void
    {
        $numeroDocumentoInscripcion = $this->normalizarValorNumerico(
            $obligacion->inscripcion?->persona?->numero_documento_per,
        );
        $numeroDocumentoDetalle = $this->normalizarValorNumerico($detalle->numero_documento_ibd);

        if ($numeroDocumentoInscripcion === null || $numeroDocumentoDetalle === null) {
            throw new DomainException('No se pudo validar el documento del pago bancario.');
        }

        if ($numeroDocumentoInscripcion !== $numeroDocumentoDetalle) {
            throw new DomainException('El DNI del pago bancario no coincide con el postulante de la inscripcion.');
        }
    }

    private function validarMontoSegunRegla(
        InscripcionObligacionPago $obligacion,
        float $montoDetalle,
        float $saldoPendiente,
        int $numeroPagosVerificados,
    ): void {
        if ($saldoPendiente <= 0) {
            throw new DomainException('La obligacion de pago ya se encuentra cubierta.');
        }

        if ($montoDetalle > $saldoPendiente) {
            throw new DomainException('El monto del pago excede el saldo pendiente de la obligacion.');
        }

        $reglaPago = $obligacion->reglaPago;

        if ($reglaPago === null) {
            throw new DomainException('La obligacion no tiene una regla de pago asociada.');
        }

        if (! $reglaPago->permite_cuotas_rpa && $numeroPagosVerificados > 0) {
            throw new DomainException('La regla de pago no permite registrar mas de un pago.');
        }

        if ((! $reglaPago->permite_cuotas_rpa || $reglaPago->requiere_pago_completo_rpa) && $montoDetalle < $saldoPendiente) {
            throw new DomainException('La regla de pago requiere cubrir el saldo completo en este registro.');
        }

        if (
            $reglaPago->permite_cuotas_rpa
            && $numeroPagosVerificados === 0
            && (float) $reglaPago->monto_minimo_primera_cuota_rpa > 0
            && $montoDetalle < (float) $reglaPago->monto_minimo_primera_cuota_rpa
        ) {
            throw new DomainException('El pago no alcanza el monto minimo requerido para la primera cuota.');
        }
    }

    private function resolverEstadoObligacion(float $montoPagado, float $saldoPendiente): EstadoObligacionPago
    {
        if ($saldoPendiente <= 0) {
            return EstadoObligacionPago::Pagado;
        }

        if ($montoPagado > 0) {
            return EstadoObligacionPago::Parcial;
        }

        return EstadoObligacionPago::Pendiente;
    }

    private function normalizarFecha(CarbonInterface|string|null $fechaPago): ?CarbonInterface
    {
        if ($fechaPago === null || $fechaPago === '') {
            return null;
        }

        if ($fechaPago instanceof CarbonInterface) {
            return $fechaPago;
        }

        return Carbon::parse($fechaPago);
    }

    private function normalizarValorNumerico(?string $valor): ?string
    {
        if ($valor === null || trim($valor) === '') {
            return null;
        }

        $soloDigitos = preg_replace('/\D+/', '', trim($valor));

        if ($soloDigitos === null || $soloDigitos === '') {
            return null;
        }

        return ltrim($soloDigitos, '0') ?: '0';
    }
}
