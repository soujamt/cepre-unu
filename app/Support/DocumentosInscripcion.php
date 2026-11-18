<?php

namespace App\Support;

use App\Enums\EstadoValidacionPago;
use App\Models\FotoPostulante;
use App\Models\Inscripcion;
use App\Models\InscripcionPago;
use App\Models\Parametro;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DocumentoPdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Arma la ficha de matrícula y el carnet en PDF con la misma información
 * que imprimía el sistema anterior.
 */
class DocumentosInscripcion
{
    /**
     * @var list<string>
     */
    public const RELACIONES = [
        'persona.tipoDocumento',
        'persona.estadoCivil',
        'persona.ubigeoNacimiento',
        'persona.ubigeoDomicilio',
        'persona.lenguaNativa',
        'persona.lenguaExtranjera',
        'proceso',
        'sede',
        'modalidadAdmision',
        'carrera',
        'turno',
        'colegio',
        'tipoColegio',
        'obligacionesPago.reglaPago.conceptoPago',
        'obligacionesPago.pagos.importacionBancoDetalle',
    ];

    public function ficha(Inscripcion $inscripcion): DocumentoPdf
    {
        $inscripcion->loadMissing(self::RELACIONES);
        $obligacion = $inscripcion->obligacionesPago->sortByDesc('id_iop')->first();
        $pagos = $inscripcion->obligacionesPago
            ->flatMap->pagos
            ->filter(fn (InscripcionPago $pago): bool => $pago->estado_validacion_ipa === EstadoValidacionPago::Verificado)
            ->sortBy('numero_cuota_ipa')
            ->values();

        return Pdf::loadView('pdf.ficha-matricula', [
            'inscripcion' => $inscripcion,
            'persona' => $inscripcion->persona,
            'obligacion' => $obligacion,
            'pagos' => $pagos,
            'foto' => FotoPostulante::deDocumento($inscripcion->persona?->numero_documento_per)?->comoDataUri(),
            'logo' => $this->logo(),
        ])->setPaper('a4');
    }

    /**
     * Un carnet por hoja A4, como en el sistema anterior.
     *
     * @param  Collection<int, Inscripcion>  $inscripciones
     */
    public function carnets(Collection $inscripciones): DocumentoPdf
    {
        $inscripciones->each->loadMissing(self::RELACIONES);

        return Pdf::loadView('pdf.carnet', [
            'carnets' => $inscripciones->map(fn (Inscripcion $inscripcion): array => $this->datosCarnet($inscripcion))->all(),
            ...$this->datosFirma(),
        ])->setPaper('a4');
    }

    /**
     * Carnet con datos de ejemplo para revisar la configuración.
     */
    public function carnetDeMuestra(): DocumentoPdf
    {
        return Pdf::loadView('pdf.carnet', [
            'carnets' => [[
                'turno' => 'MAÑANA',
                'modalidad' => 'ORDINARIO',
                'carrera' => 'ING. DE SISTEMAS',
                'documento' => 'DNI N° 00000000',
                'apellidos' => 'APELLIDO PATERNO MATERNO',
                'nombres' => 'NOMBRES DE MUESTRA',
                'foto' => null,
                'sede' => 'SEDE',
                'proceso' => 'PROCESO',
                'qr' => $this->qr(url('/')),
            ]],
            ...$this->datosFirma(),
        ])->setPaper('a4');
    }

    /**
     * @return array<string, mixed>
     */
    private function datosCarnet(Inscripcion $inscripcion): array
    {
        $persona = $inscripcion->persona;

        return [
            'turno' => $inscripcion->turno?->nombre_tur,
            'modalidad' => $inscripcion->modalidadAdmision?->nombre_mad,
            'carrera' => $inscripcion->carrera?->nombre_car,
            'documento' => self::siglaDocumento($persona?->tipoDocumento?->codigo_tdo).' N° '.$persona?->numero_documento_per,
            'apellidos' => trim($persona?->apellido_paterno_per.' '.$persona?->apellido_materno_per),
            'nombres' => $persona?->nombres_per,
            'foto' => FotoPostulante::deDocumento($persona?->numero_documento_per)?->comoDataUri(),
            'sede' => $inscripcion->sede?->nombre_sed,
            'proceso' => $inscripcion->proceso?->nombre_pro,
            'qr' => $this->qr(route('carnet.verificar', $inscripcion->token_verificacion_ins)),
        ];
    }

    /**
     * @return array{logo:?string,firma:?string,director:?string,cargo:?string,nota:?string}
     */
    private function datosFirma(): array
    {
        $rutaFirma = Parametro::valor('carnet_firma_sello');

        return [
            'logo' => $this->logo(),
            'firma' => $rutaFirma !== null && Storage::disk('local')->exists($rutaFirma)
                ? 'data:image/png;base64,'.base64_encode(Storage::disk('local')->get($rutaFirma))
                : null,
            'director' => Parametro::valor('carnet_director_nombre'),
            'cargo' => Parametro::valor('carnet_director_cargo'),
            'nota' => Parametro::valor('carnet_nota'),
        ];
    }

    public static function siglaDocumento(?string $codigoTipo): string
    {
        return match ($codigoTipo) {
            '1' => 'DNI',
            '2' => 'CE',
            default => 'DOC.',
        };
    }

    private function logo(): ?string
    {
        $ruta = public_path('assets/media/logo_unu.png');

        return is_file($ruta) ? 'data:image/png;base64,'.base64_encode((string) file_get_contents($ruta)) : null;
    }

    private function qr(string $contenido): string
    {
        $svg = (new Writer(new ImageRenderer(new RendererStyle(180, 1), new SvgImageBackEnd)))->writeString($contenido);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
