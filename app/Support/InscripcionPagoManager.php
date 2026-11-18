<?php

namespace App\Support;

use App\Enums\EstadoImportacionBancoDetalle;
use App\Enums\EstadoInscripcion;
use App\Enums\EstadoObligacionPago;
use App\Enums\EstadoValidacionPago;
use App\Models\ImportacionBancoDetalle;
use App\Models\Inscripcion;
use App\Models\InscripcionObligacionPago;
use App\Models\InscripcionPago;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
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
                'id_usuario_validador' => Auth::id(),
            ]);

            $detalle->estado_ibd = EstadoImportacionBancoDetalle::Asociado;
            $detalle->save();

            $montoPagadoNuevo = round($montoPagadoActual + $montoDetalle, 2);
            $saldoNuevo = round(max((float) $obligacion->monto_total_iop - $montoPagadoNuevo, 0), 2);

            $obligacion->monto_pagado_iop = $montoPagadoNuevo;
            $obligacion->saldo_iop = $saldoNuevo;
            $obligacion->estado_iop = $this->resolverEstadoObligacion($montoPagadoNuevo, $saldoNuevo);
            $obligacion->save();

            $this->activarInscripcionPendiente($obligacion);

            return $pago->fresh([
                'importacionBancoDetalle',
                'obligacionPago',
            ]);
        });
    }

    /**
     * Asocia varios pagos del mismo DNI a la obligación en una sola transacción.
     *
     * @param  iterable<ImportacionBancoDetalle>  $detalles
     * @return list<InscripcionPago>
     */
    public function asociarDetalles(InscripcionObligacionPago $obligacion, iterable $detalles): array
    {
        return DB::transaction(function () use ($obligacion, $detalles): array {
            $pagos = [];

            foreach ($detalles as $detalle) {
                $pagos[] = $this->asociarDetalleAObligacion($obligacion->fresh(), $detalle);
            }

            return $pagos;
        });
    }

    /**
     * @return array{asociados: int, omitidos: int, pendientes: int}
     */
    public function validarPendientesDeImportacion(int $idImportacionBanco): array
    {
        $asociados = 0;
        $omitidos = 0;

        $detalles = ImportacionBancoDetalle::query()
            ->where('id_importacion_banco', $idImportacionBanco)
            ->where('estado_ibd', EstadoImportacionBancoDetalle::Pendiente)
            ->whereDoesntHave('inscripcionPago')
            ->orderBy('fecha_pago_ibd')
            ->orderBy('id_ibd')
            ->get();

        $detalles
            ->groupBy(fn (ImportacionBancoDetalle $detalle): string => $this->normalizarValorNumerico($detalle->numero_documento_ibd) ?? 'sin-documento-'.$detalle->id_ibd)
            ->each(function (Collection $detallesDocumento) use (&$asociados, &$omitidos): void {
                $numeroDocumento = $this->normalizarValorNumerico($detallesDocumento->first()?->numero_documento_ibd);

                if ($numeroDocumento === null) {
                    $omitidos += $detallesDocumento->count();

                    return;
                }

                $obligacion = $this->buscarObligacionPendientePorDocumento($numeroDocumento);

                if ($obligacion === null) {
                    $omitidos += $detallesDocumento->count();

                    return;
                }

                foreach ($detallesDocumento as $detalle) {
                    $obligacionActual = $obligacion->fresh(['inscripcion.persona', 'reglaPago']);

                    if ($obligacionActual === null) {
                        $omitidos++;

                        continue;
                    }

                    if ($this->saldoPendienteActual($obligacionActual) <= 0) {
                        $omitidos++;

                        continue;
                    }

                    try {
                        $this->asociarDetalleAObligacion($obligacionActual, $detalle);
                        $asociados++;
                    } catch (DomainException) {
                        $omitidos++;
                    }
                }
            });

        $pendientes = ImportacionBancoDetalle::query()
            ->where('id_importacion_banco', $idImportacionBanco)
            ->where('estado_ibd', EstadoImportacionBancoDetalle::Pendiente)
            ->whereDoesntHave('inscripcionPago')
            ->count();

        return [
            'asociados' => $asociados,
            'omitidos' => $omitidos,
            'pendientes' => $pendientes,
        ];
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

    private function buscarObligacionPendienteParaDetalle(ImportacionBancoDetalle $detalle): ?InscripcionObligacionPago
    {
        $numeroDocumento = $this->normalizarValorNumerico($detalle->numero_documento_ibd);

        if ($numeroDocumento === null) {
            return null;
        }

        $montoDetalle = round((float) $detalle->monto_ibd, 2);

        $obligaciones = InscripcionObligacionPago::query()
            ->with(['inscripcion.persona', 'reglaPago'])
            ->whereIn('estado_iop', [
                EstadoObligacionPago::Pendiente->value,
                EstadoObligacionPago::Parcial->value,
            ])
            ->where('saldo_iop', $montoDetalle)
            ->whereHas('inscripcion.persona')
            ->get()
            ->filter(fn (InscripcionObligacionPago $obligacion): bool => $this->normalizarValorNumerico(
                $obligacion->inscripcion?->persona?->numero_documento_per,
            ) === $numeroDocumento)
            ->take(2)
            ->values();

        if ($obligaciones->count() !== 1) {
            return null;
        }

        return $obligaciones->first();
    }

    private function buscarObligacionPendientePorDocumento(string $numeroDocumento): ?InscripcionObligacionPago
    {
        $obligaciones = InscripcionObligacionPago::query()
            ->with(['inscripcion.persona', 'reglaPago'])
            ->whereIn('estado_iop', [
                EstadoObligacionPago::Pendiente->value,
                EstadoObligacionPago::Parcial->value,
            ])
            ->whereHas('inscripcion.persona')
            ->get()
            ->filter(fn (InscripcionObligacionPago $obligacion): bool => $this->normalizarValorNumerico(
                $obligacion->inscripcion?->persona?->numero_documento_per,
            ) === $numeroDocumento)
            ->filter(fn (InscripcionObligacionPago $obligacion): bool => $this->saldoPendienteActual($obligacion) > 0)
            ->take(2)
            ->values();

        if ($obligaciones->count() !== 1) {
            return null;
        }

        return $obligaciones->first();
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

    /**
     * Reglas del sistema anterior: se acepta pagar de más (S/ 501 sobre 500)
     * y el mínimo se alcanza sumando varios pagos del mismo DNI, así que aquí
     * solo se impide seguir pagando una obligación ya cubierta.
     */
    private function validarMontoSegunRegla(
        InscripcionObligacionPago $obligacion,
        float $montoDetalle,
        float $saldoPendiente,
        int $numeroPagosVerificados,
    ): void {
        if ($obligacion->reglaPago === null) {
            throw new DomainException('La obligacion de pago no tiene una regla de pago asociada.');
        }

        $esGratuita = round((float) $obligacion->monto_total_iop, 2) <= 0;

        if ($esGratuita && $numeroPagosVerificados === 0 && $montoDetalle <= 0) {
            return;
        }

        if ($saldoPendiente <= 0) {
            throw new DomainException('La obligacion de pago ya se encuentra cubierta.');
        }
    }

    /**
     * Monto que debe estar pagado para activar la inscripción: el mínimo de la
     * primera cuota si la regla permite cuotas, o el total si no.
     */
    public function montoMinimoParaActivar(InscripcionObligacionPago $obligacion): float
    {
        $reglaPago = $obligacion->reglaPago;
        $montoTotal = round((float) $obligacion->monto_total_iop, 2);

        if ($reglaPago !== null && $reglaPago->permite_cuotas_rpa && ! $reglaPago->requiere_pago_completo_rpa) {
            return min(round((float) $reglaPago->monto_minimo_primera_cuota_rpa, 2), $montoTotal);
        }

        return $montoTotal;
    }

    private function saldoPendienteActual(InscripcionObligacionPago $obligacion): float
    {
        $montoPagadoActual = (float) $obligacion->pagos()
            ->where('estado_validacion_ipa', EstadoValidacionPago::Verificado)
            ->sum('monto_ipa');

        return round(max((float) $obligacion->monto_total_iop - $montoPagadoActual, 0), 2);
    }

    /**
     * Aprueba una inscripción hecha en el portal: deja de estar en revisión y
     * se activa si lo pagado ya alcanza el mínimo.
     */
    public function aprobarRevision(Inscripcion $inscripcion, ?int $idUsuario): void
    {
        DB::transaction(function () use ($inscripcion, $idUsuario): void {
            $inscripcion->update([
                'requiere_revision_ins' => false,
                'revisado_en' => now(),
                'id_usuario_revisor' => $idUsuario,
            ]);

            $inscripcion->obligacionesPago()->get()->each(function (InscripcionObligacionPago $obligacion) use ($inscripcion): void {
                $obligacion->setRelation('inscripcion', $inscripcion);
                $this->activarInscripcionPendiente($obligacion);
            });
        });
    }

    /**
     * La inscripción pendiente se activa cuando lo pagado alcanza el mínimo,
     * salvo que esté en revisión.
     */
    private function activarInscripcionPendiente(InscripcionObligacionPago $obligacion): void
    {
        $inscripcion = $obligacion->inscripcion;

        if ($inscripcion === null || $inscripcion->estado_ins !== EstadoInscripcion::Pendiente || $inscripcion->requiere_revision_ins) {
            return;
        }

        if (round((float) $obligacion->monto_pagado_iop, 2) < $this->montoMinimoParaActivar($obligacion)) {
            return;
        }

        $inscripcion->update(['estado_ins' => EstadoInscripcion::Activo->value]);
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
