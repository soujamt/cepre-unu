<?php

namespace App\Http\Controllers\Publico;

use App\Enums\EstadoInscripcion;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DocumentoInscripcionController;
use App\Http\Requests\Publico\ConsultaInscripcionRequest;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Portal del postulante (sin iniciar sesión): inicio y consulta de la inscripción.
 */
class PortalController extends Controller
{
    public function portal(): Response
    {
        return Inertia::render('publico/portal');
    }

    public function consulta(): Response
    {
        return Inertia::render('publico/consulta');
    }

    /**
     * Resultado de la consulta en JSON: la página lo pide sin navegar, así los
     * datos del postulante no quedan guardados en el historial del navegador.
     */
    public function buscar(ConsultaInscripcionRequest $request): JsonResponse
    {
        $inscripcion = $request->inscripcion();
        $persona = $inscripcion->persona;
        $aula = $inscripcion->procesoAula?->aula;
        $obligacion = $inscripcion->obligacionesPago->sortByDesc('id_iop')->first();
        $anulada = $inscripcion->estado_ins === EstadoInscripcion::Anulado;

        return response()->json([
            'codigo' => (string) $inscripcion->codigo_ins,
            'nombre' => trim($persona?->nombres_per.' '.$persona?->apellido_paterno_per.' '.$persona?->apellido_materno_per),
            'estado' => $inscripcion->enRevision() ? 'EN REVISIÓN' : $inscripcion->estado_ins->getNombre(),
            'aula' => $aula ? [
                'nombre' => $aula->nombre_aul,
                'detalle' => trim($aula->piso_aul?->getNombre().' · '.$aula->lugar_aul?->getNombre(), ' ·'),
            ] : null,
            'datos' => [
                ['etiqueta' => 'Proceso', 'valor' => $inscripcion->proceso?->nombre_pro],
                ['etiqueta' => 'Sede', 'valor' => $inscripcion->sede?->nombre_sed],
                ['etiqueta' => 'Modalidad', 'valor' => $inscripcion->modalidadAdmision?->nombre_mad],
                ['etiqueta' => 'Carrera', 'valor' => $inscripcion->carrera?->nombre_car],
                ['etiqueta' => 'Turno', 'valor' => $inscripcion->turno?->descripcionConHorario()],
                [
                    'etiqueta' => 'Pagado',
                    'valor' => $obligacion
                        ? 'S/ '.number_format((float) $obligacion->monto_pagado_iop, 2).' de S/ '.number_format((float) $obligacion->monto_total_iop, 2)
                        : null,
                ],
            ],
            'enlaces' => $anulada ? null : DocumentoInscripcionController::enlacesPublicos($inscripcion),
        ]);
    }
}
