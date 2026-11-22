<?php

namespace App\Http\Controllers\Inscripciones;

use App\Http\Controllers\Controller;
use App\Models\Inscripcion;
use App\Models\InscripcionCambio;
use App\Models\InscripcionObligacionPago;
use App\Models\InscripcionPago;
use App\Models\Ubigeo;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Ficha completa de una inscripción: postulante, postulación, colegio y pagos.
 */
class InscripcionDetalleController extends Controller
{
    public function __invoke(string $inscripcion): Response
    {
        $id = decode($inscripcion);
        abort_if($id === null, 404);

        $registro = Inscripcion::query()
            ->with([
                'persona.tipoDocumento', 'persona.ubigeoNacimiento', 'persona.ubigeoDomicilio',
                'proceso', 'sede', 'modalidadAdmision', 'carrera', 'turno', 'procesoAula.aula',
                'paisColegio', 'colegio.tipoColegio', 'tipoColegio',
                'obligacionesPago.reglaPago.conceptoPago', 'obligacionesPago.pagos.importacionBancoDetalle', 'obligacionesPago.pagos.usuarioValidador',
                'cambios' => fn ($query) => $query->with('usuarioCreador')->latest('id_ica'),
            ])
            ->findOrFail($id);

        $persona = $registro->persona;
        $aula = $registro->procesoAula?->aula;
        $obligaciones = $registro->obligacionesPago;

        return Inertia::render('inscripciones/detalle', [
            'inscripcion' => [
                'id' => $inscripcion,
                'codigo' => $registro->codigo_ins,
                'estado' => $registro->estado_ins->value,
                'enRevision' => $registro->enRevision(),
                'origen' => $registro->origen_ins,
                'fecha' => $registro->fecha_inscripcion_ins?->format('d/m/Y h:i a'),
                'motivoAnulacion' => $registro->motivo_anulacion_ins,
                'postulante' => $registro->nombrePostulante(),
                'proceso' => $registro->proceso?->nombre_pro,
                'pago' => [
                    'estado' => $registro->estadoPagoGeneral(),
                    'pagado' => (float) $obligaciones->sum('monto_pagado_iop'),
                    'total' => (float) $obligaciones->sum('monto_total_iop'),
                ],
            ],
            'persona' => [
                'tipoDocumento' => $persona?->tipoDocumento?->nombre_tdo,
                'documento' => $persona?->numero_documento_per,
                'correo' => $persona?->correo_electronico_per,
                'celular' => $persona?->celular_per,
                'telefono' => $persona?->telefono_per,
                'apoderado' => $persona?->nombre_apoderado_per,
                'celularApoderado' => $persona?->celular_apoderado_per,
                'nacimiento' => $persona?->fecha_nacimiento_per?->format('d/m/Y'),
                'sexo' => $persona?->sexo_per?->value,
                'direccion' => $persona?->direccion_per,
                'ubigeoNacimiento' => $this->ubigeo($persona?->ubigeoNacimiento),
                'ubigeoDomicilio' => $this->ubigeo($persona?->ubigeoDomicilio),
            ],
            'postulacion' => [
                'carrera' => $registro->carrera?->nombre_car,
                'sede' => $registro->sede?->nombre_sed,
                'modalidad' => $registro->modalidadAdmision?->nombre_mad,
                'turno' => $registro->turno?->nombre_tur,
                'aula' => $aula === null ? null : implode(' · ', array_filter([$aula->nombre_aul, $aula->piso_aul?->getNombre(), $aula->lugar_aul?->getNombre()])),
                'colegio' => $registro->colegio?->nombre_col ?? $registro->nombre_colegio_extranjero_ins,
                'tipoColegio' => $registro->colegio?->tipoColegio?->nombre_tco ?? $registro->tipoColegio?->nombre_tco,
                'paisColegio' => $registro->paisColegio?->nombre_pai ?? null,
                'anioGraduacion' => $registro->anio_graduacion_ins,
            ],
            'obligaciones' => $obligaciones->map(fn (InscripcionObligacionPago $obligacion): array => [
                'id' => $obligacion->id_iop,
                'concepto' => $obligacion->reglaPago?->conceptoPago?->nombre_cpg,
                'total' => (float) $obligacion->monto_total_iop,
                'pagado' => (float) $obligacion->monto_pagado_iop,
                'saldo' => (float) $obligacion->saldo_iop,
                'estado' => $obligacion->estado_iop->value,
                'pagos' => $obligacion->pagos->map(fn (InscripcionPago $pago): array => [
                    'id' => $pago->id_ipa,
                    'operacion' => $pago->importacionBancoDetalle?->numero_operacion_visible_ibd ?: $pago->numero_operacion_ipa,
                    'documento' => $pago->importacionBancoDetalle?->numero_documento_ibd,
                    'fecha' => $pago->fecha_pago_ipa?->format('d/m/Y'),
                    'monto' => (float) $pago->monto_ipa,
                    'estado' => $pago->estado_validacion_ipa?->value ?? (string) $pago->estado_validacion_ipa,
                    'validador' => $pago->usuarioValidador?->nombres_usu,
                ])->values(),
            ])->values(),
            'cambios' => $registro->cambios->map(fn (InscripcionCambio $cambio): array => [
                'id' => $cambio->id_ica,
                'fecha' => $cambio->creado_en?->format('d/m/Y h:i a'),
                'usuario' => $cambio->usuarioCreador?->nombres_usu,
                'motivo' => $cambio->motivo_ica,
                'cambios' => $cambio->cambios_ica,
            ])->values(),
        ]);
    }

    private function ubigeo(?Ubigeo $ubigeo): ?string
    {
        return $ubigeo === null ? null : "{$ubigeo->nombre_departamento_ubi} / {$ubigeo->nombre_provincia_ubi} / {$ubigeo->nombre_distrito_ubi}";
    }
}
