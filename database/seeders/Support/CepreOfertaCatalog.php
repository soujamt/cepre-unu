<?php

namespace Database\Seeders\Support;

class CepreOfertaCatalog
{
    /**
     * @return list<array{codigo_sed:string,nombre_sed:string,id_ubigeo:string,direccion_sed:null|string,estado_sed:string}>
     */
    public static function sedes(): array
    {
        return [
            [
                'codigo_sed' => 'SEDCAL',
                'nombre_sed' => 'PUCALLPA - CALLERÍA',
                'id_ubigeo' => '250101',
                'direccion_sed' => null,
                'estado_sed' => 'HABILITADO',
            ],
            [
                'codigo_sed' => 'FILAGU',
                'nombre_sed' => 'FILIAL AGUAYTIA',
                'id_ubigeo' => '250201',
                'direccion_sed' => null,
                'estado_sed' => 'HABILITADO',
            ],
        ];
    }

    /**
     * Modalidades del CEPRE. La de cada postulante se asigna según su año de egreso.
     *
     * @return list<array{codigo_mad:string,nombre_mad:string,descripcion_mad:string,condicion_egreso_mad:string,estado_mad:string}>
     */
    public static function modalidades(): array
    {
        return [
            [
                'codigo_mad' => 'ORD',
                'nombre_mad' => 'ORDINARIO',
                'descripcion_mad' => 'YA CULMINÓ LA SECUNDARIA',
                'condicion_egreso_mad' => 'EGRESADO',
                'estado_mad' => 'HABILITADO',
            ],
            [
                'codigo_mad' => 'EXT',
                'nombre_mad' => 'EXTRAORDINARIO O RESERVA',
                'descripcion_mad' => 'CURSA 4.° O 5.° DE SECUNDARIA',
                'condicion_egreso_mad' => 'EN_COLEGIO',
                'estado_mad' => 'HABILITADO',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function careerCodes(): array
    {
        return [
            'ADMINISTRACIÓN' => 'ADM',
            'AGRONOMIA' => 'AGR',
            'CIENCIAS DE LA COMUNICACIÓN' => 'CCO',
            'CONTABILIDAD' => 'CON',
            'DERECHO' => 'DER',
            'ECONOMÍA Y NEGOCIOS INTERNACIONALES' => 'ENI',
            'EDUCACIÓN INICIAL' => 'EDI',
            'EDUCACIÓN PRIMARIA' => 'EDP',
            'EDUCACIÓN SECUNDARIA: ESPECIALIDAD CIENCIAS NATURALES Y MEDIO AMBIENTE' => 'ESC',
            'EDUCACIÓN SECUNDARIA: ESPECIALIDAD CIENCIAS SOCIALES Y EDUCACIÓN INTERCULTURAL' => 'ESS',
            'EDUCACIÓN SECUNDARIA: ESPECIALIDAD IDIOMA INGLES' => 'EII',
            'EDUCACIÓN SECUNDARIA: ESPECIALIDAD LENGUA Y LITERATURA' => 'ELL',
            'EDUCACIÓN SECUNDARIA: ESPECIALIDAD MATEMÁTICA, FÍSICA E INFORMÁTICA' => 'EMF',
            'ENFERMERÍA' => 'ENF',
            'INGENIERÍA AGROINDUSTRIAL' => 'IAG',
            'INGENIERIA AMBIENTAL' => 'IAM',
            'INGENIERIA CIVIL' => 'ICI',
            'INGENIERÍA DE SISTEMAS' => 'IDS',
            'INGENIERIA FORESTAL' => 'IFO',
            'MEDICINA HUMANA' => 'MED',
            'PSICOLOGÍA' => 'PSI',
        ];
    }

    /**
     * @return list<array{codigo_car:string,nombre_car:string,estado_car:string}>
     */
    public static function carreras(): array
    {
        $records = [];

        foreach (self::careerCodes() as $name => $code) {
            $records[] = [
                'codigo_car' => $code,
                'nombre_car' => $name,
                'estado_car' => 'HABILITADO',
            ];
        }

        return $records;
    }
}
