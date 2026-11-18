<?php

namespace App\Http\Controllers;

use App\Enums\EstadoInscripcion;
use App\Models\Inscripcion;
use App\Support\DocumentosInscripcion;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;

class DocumentoInscripcionController extends Controller
{
    public function __construct(private DocumentosInscripcion $documentos) {}

    /**
     * Ficha de matrícula de una inscripción, abierta en el navegador.
     */
    public function ficha(string $inscripcion): Response
    {
        $registro = $this->resolverInscripcion($inscripcion);

        return $this->documentos->ficha($registro)->stream("ficha-{$registro->codigo_ins}.pdf");
    }

    /**
     * Carnet de una inscripción ACTIVA.
     */
    public function carnet(string $inscripcion): Response
    {
        $registro = $this->resolverInscripcion($inscripcion);

        abort_unless($registro->estado_ins === EstadoInscripcion::Activo, 409, 'El carnet solo se genera para inscripciones ACTIVAS.');

        return $this->documentos->carnets(collect([$registro]))->stream("carnet-{$registro->codigo_ins}.pdf");
    }

    /**
     * Ficha descargada por el postulante desde un enlace firmado del portal.
     */
    public function fichaPublica(string $inscripcion): Response
    {
        return $this->ficha($inscripcion);
    }

    /**
     * Carnet descargado por el postulante desde un enlace firmado del portal.
     */
    public function carnetPublico(string $inscripcion): Response
    {
        return $this->carnet($inscripcion);
    }

    /**
     * Enlaces firmados para que el postulante descargue sus documentos.
     *
     * @return array{ficha:string,carnet:?string}
     */
    public static function enlacesPublicos(Inscripcion $inscripcion): array
    {
        $vence = now()->addMinutes(60);
        $id = encode($inscripcion->id_ins);

        return [
            'ficha' => URL::temporarySignedRoute('publico.ficha', $vence, ['inscripcion' => $id]),
            'carnet' => $inscripcion->estado_ins === EstadoInscripcion::Activo
                ? URL::temporarySignedRoute('publico.carnet', $vence, ['inscripcion' => $id])
                : null,
        ];
    }

    /**
     * Carnet con datos de ejemplo para revisar la firma y la nota configuradas.
     */
    public function muestra(): Response
    {
        return $this->documentos->carnetDeMuestra()->stream('carnet-muestra.pdf');
    }

    private function resolverInscripcion(string $inscripcion): Inscripcion
    {
        $idInscripcion = decode($inscripcion);

        abort_if($idInscripcion === null, 404);

        return Inscripcion::query()->findOrFail($idInscripcion);
    }
}
