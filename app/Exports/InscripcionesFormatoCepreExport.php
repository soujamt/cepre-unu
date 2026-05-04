<?php

namespace App\Exports;

use App\Enums\SexoPersona;
use App\Enums\TipoLenguaMaternaPersona;
use App\Models\Inscripcion;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class InscripcionesFormatoCepreExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithStrictNullComparison
{
    public function __construct(
        public Collection $inscripciones,
    ) {}

    public function collection(): Collection
    {
        return $this->inscripciones
            ->values()
            ->map(fn (Inscripcion $inscripcion): array => $this->mapInscripcion($inscripcion))
            ->values();
    }

    public function headings(): array
    {
        return [
            'CODIGO_TIPO_DOCUMENTO',
            'NUMERO_DOCUMENTO',
            'CODIGO_SOLO_UN_APELLIDO',
            'PRIMER_APELLIDO',
            'SEGUNDO_APELLIDO',
            'NOMBRES',
            'ESTADO_CIVIL',
            'APELLIDO_CASADA',
            'SEXO',
            'CODIGO_NACIMIENTO_PAIS',
            'CODIGO_NACIONALIDAD',
            'CODIGO_NACIMIENTO_UBIGEO',
            'FECHA_NACIMIENTO',
            'CONDICION_DISCAPACIDAD',
            'TIPO_DISCAPACIDAD',
            'CELULAR',
            'TELEFONO',
            'CORREO_ELECTRONICO',
            'CODIGO_UBIGEO_DIRECCION',
            'DIRECCION',
            'GRADUACION',
            'CODIGO_PAIS_COLEGIO',
            'CODIGO_COLEGIO',
            'NOMBRE_COLEGIO',
            'TIPO_COLEGIO',
            'VECES_POST_UNU',
            'VECES_POST_OTROS',
            'LENGUA_MATERNA',
            'IDENTIDAD_ETNICA',
            'LENGUA_NATIVA',
            'LENGUA_EXTRANJERA',
            'CODIGO_LUGAR_INSCRIPCION',
            'CODIGO_CARRERA',
            'OBSERVACION',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $highestRow = max(1, $event->sheet->getHighestRow());
                $range = "A1:AH{$highestRow}";

                $event->sheet->getStyle($range)->applyFromArray([
                    'font' => [
                        'name' => 'Arial',
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => [
                                'argb' => 'FFBFBFBF',
                            ],
                        ],
                    ],
                ]);
            },
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function mapInscripcion(Inscripcion $inscripcion): array
    {
        $persona = $inscripcion->persona;
        $tipoDocumento = $persona?->tipoDocumento;
        $sexo = $this->codigoSexo($persona?->sexo_per);
        $lenguaMaterna = $this->codigoLenguaMaterna($persona?->tipo_lengua_materna_per);

        return [
            $this->stringOrNull($tipoDocumento?->codigo_tdo),
            $this->stringOrNull($persona?->numero_documento_per),
            $this->codigoBooleano($persona?->solo_un_apellido_per),
            $this->stringOrNull($persona?->apellido_paterno_per),
            $this->stringOrNull($persona?->apellido_materno_per),
            $this->stringOrNull($persona?->nombres_per),
            $this->stringOrNull($persona?->estadoCivil?->codigo_eci),
            $this->stringOrNull($persona?->apellido_casada_per),
            $sexo,
            $this->stringOrNull($persona?->paisNacimiento?->codigo_pai),
            $this->stringOrNull($persona?->nacionalidad?->codigo_nac),
            $this->stringOrNull($persona?->ubigeoNacimiento?->id_ubi),
            $persona?->fecha_nacimiento_per?->format('d/m/Y'),
            $this->codigoBooleano($persona?->discapacidad_per),
            $this->stringOrNull($persona?->tipoDiscapacidad?->codigo_tdi),
            $this->stringOrNull($persona?->celular_per),
            $this->stringOrNull($persona?->telefono_per),
            $this->stringOrNull($persona?->correo_electronico_per),
            $this->stringOrNull($persona?->ubigeoDomicilio?->id_ubi),
            $this->stringOrNull($persona?->direccion_per),
            $inscripcion->anio_graduacion_ins,
            $this->stringOrNull($inscripcion->paisColegio?->codigo_pai),
            $this->stringOrNull($inscripcion->colegio?->codigo_modular_col),
            $this->nombreColegio($inscripcion),
            $this->stringOrNull($inscripcion->tipoColegio?->codigo_tco),
            $inscripcion->veces_postula_unu_ins ?? 0,
            $inscripcion->veces_postula_otras_ins ?? 0,
            $lenguaMaterna,
            $this->stringOrNull($persona?->identidadEtnica?->nombre_iet),
            $this->stringOrNull($persona?->lenguaNativa?->codigo_lna),
            $this->stringOrNull($persona?->lenguaExtranjera?->codigo_lex),
            $this->stringOrNull($inscripcion->procesoOferta?->ofertaBase?->modalidadLugarInscripcion?->lugarInscripcion?->codigo_lin),
            $this->stringOrNull($inscripcion->procesoOferta?->ofertaBase?->codigo_externo_ofb),
            $this->stringOrNull($inscripcion->observacion_ins ?: $persona?->observacion_per),
        ];
    }

    private function codigoSexo(null|SexoPersona|string $sexo): ?string
    {
        if ($sexo instanceof SexoPersona) {
            return match ($sexo) {
                SexoPersona::Masculino => 'M',
                SexoPersona::Femenino => 'F',
                default => 'O',
            };
        }

        if (! is_string($sexo) || blank($sexo)) {
            return null;
        }

        return match (mb_strtoupper($sexo, 'UTF-8')) {
            'MASCULINO' => 'M',
            'FEMENINO' => 'F',
            default => 'O',
        };
    }

    private function codigoLenguaMaterna(null|TipoLenguaMaternaPersona|string $tipo): ?string
    {
        if ($tipo instanceof TipoLenguaMaternaPersona) {
            return $tipo->value;
        }

        if (! is_string($tipo) || blank($tipo)) {
            return null;
        }

        return mb_strtoupper($tipo, 'UTF-8');
    }

    private function codigoBooleano(?bool $valor): int
    {
        return $valor ? 1 : 0;
    }

    private function nombreColegio(Inscripcion $inscripcion): ?string
    {
        return $this->stringOrNull(
            $inscripcion->nombre_colegio_extranjero_ins
            ?: $inscripcion->colegio?->nombre_col
        );
    }

    private function stringOrNull(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return (string) $valor;
    }
}
