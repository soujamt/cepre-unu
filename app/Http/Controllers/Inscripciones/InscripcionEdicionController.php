<?php

namespace App\Http\Controllers\Inscripciones;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inscripciones\ActualizarPostulacionRequest;
use App\Models\Inscripcion;
use App\Support\Inscripcion\CambioPostulacion;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

/**
 * Cambio de sede, carrera o turno de una inscripción registrada, confirmado con un
 * motivo que queda en el historial.
 */
class InscripcionEdicionController extends Controller
{
    public function edit(string $inscripcion, CambioPostulacion $cambio): Response|RedirectResponse
    {
        $id = decode($inscripcion);
        abort_if($id === null, 404);

        $registro = Inscripcion::query()
            ->with(['persona', 'proceso', 'sede', 'carrera', 'turno', 'modalidadAdmision', 'procesoAula.aula'])
            ->findOrFail($id);

        if (! $cambio->esEditable($registro)) {
            return $this->alDetalle($inscripcion, 'Solo se pueden editar inscripciones activas o pendientes.', 'warning');
        }

        return Inertia::render('inscripciones/editar', [
            'inscripcion' => [
                'id' => $inscripcion,
                'codigo' => $registro->codigo_ins,
                'estado' => $registro->estado_ins->value,
                'enRevision' => $registro->enRevision(),
                'postulante' => $registro->nombrePostulante(),
                'documento' => $registro->persona?->numero_documento_per,
                'proceso' => $registro->proceso?->nombre_pro,
                'modalidad' => $registro->modalidadAdmision?->nombre_mad,
                'aula' => $registro->procesoAula?->aula?->nombre_aul,
                'sede' => (string) $registro->id_sede,
                'carrera' => (string) $registro->id_carrera,
                'turno' => (string) $registro->id_turno,
            ],
            'sedes' => $cambio->sedes($registro),
            'turnos' => $cambio->turnos($registro),
        ]);
    }

    public function update(ActualizarPostulacionRequest $request, string $inscripcion, CambioPostulacion $cambio): RedirectResponse
    {
        $datos = $request->validated();

        try {
            $cambio->aplicar($request->inscripcion()->id_ins, (int) $datos['sede'], (int) $datos['carrera'], (int) $datos['turno'], $datos['motivo']);
        } catch (RuntimeException $error) {
            return back()->withErrors(['cambios' => $error->getMessage()]);
        }

        return $this->alDetalle($inscripcion, "Postulación de la inscripción {$request->inscripcion()->codigo_ins} actualizada. Vuelva a imprimir la ficha y el carnet.");
    }

    private function alDetalle(string $inscripcion, string $mensaje, string $tipo = 'success'): RedirectResponse
    {
        Inertia::flash('toast', ['type' => $tipo, 'message' => $mensaje]);

        return to_route('inscripciones.detalle', $inscripcion);
    }
}
