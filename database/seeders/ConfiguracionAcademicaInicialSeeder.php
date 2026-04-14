<?php

namespace Database\Seeders;

use App\Enums\EstadoCatalogo;
use App\Enums\EstadoProceso;
use App\Enums\TipoBeneficioConvenio;
use App\Models\ConceptoPago;
use App\Models\Convenio;
use App\Models\ConvenioProceso;
use App\Models\ModalidadAdmision;
use App\Models\OfertaBase;
use App\Models\Proceso;
use App\Models\ProcesoOferta;
use App\Models\ReglaPago;
use App\Models\RequisitoExpediente;
use App\Models\Semestre;
use App\Models\TipoExpediente;
use App\Models\Turno;
use Illuminate\Database\Seeder;

class ConfiguracionAcademicaInicialSeeder extends Seeder
{
    public function run(): void
    {
        $semestre = Semestre::query()->updateOrCreate(
            ['codigo_sem' => 'semestre_2026_i'],
            [
                'nombre_sem' => 'SEMESTRE 2026-I',
                'fecha_inicio_inscripcion_sem' => '2026-04-01',
                'fecha_fin_inscripcion_sem' => '2026-04-30',
                'estado_sem' => EstadoProceso::Habilitado->value,
            ],
        );

        $proceso = Proceso::query()->updateOrCreate(
            ['codigo_pro' => 'proceso_ordinario_2026_i'],
            [
                'id_semestre' => $semestre->id_sem,
                'nombre_pro' => 'ORDINARIO',
                'fecha_inicio_pro' => '2026-04-01',
                'fecha_fin_pro' => '2026-04-30',
                'estado_pro' => EstadoProceso::Habilitado->value,
            ],
        );

        $turnoManana = Turno::query()->where('codigo_tur', 'MAN')->firstOrFail();
        $modalidadExoneracion = ModalidadAdmision::query()->where('codigo_mad', '2')->firstOrFail();

        $ofertasBase = OfertaBase::query()
            ->where('estado_ofb', EstadoCatalogo::Habilitado->value)
            ->whereHas('modalidadLugarInscripcion', function ($query) use ($modalidadExoneracion): void {
                $query
                    ->where('id_modalidad_admision', $modalidadExoneracion->id_mad)
                    ->where('id_lugar_inscripcion', 603);
            })
            ->get();

        foreach ($ofertasBase as $ofertaBase) {
            ProcesoOferta::query()->updateOrCreate(
                [
                    'id_proceso' => $proceso->id_pro,
                    'id_oferta_base' => $ofertaBase->id_ofb,
                    'id_turno' => $turnoManana->id_tur,
                ],
                [
                    'estado_pof' => EstadoProceso::Habilitado->value,
                ],
            );
        }

        $convenioHijoTrabajador = Convenio::query()->updateOrCreate(
            ['codigo_con' => 'HIJO_TRABAJADOR'],
            [
                'nombre_con' => 'HIJO DE TRABAJADOR',
                'descripcion_con' => 'CONVENIO PARA HIJO DE TRABAJADOR UNU',
                'tipo_beneficio_con' => TipoBeneficioConvenio::Descuento->value,
                'monto_descuento_con' => 200.00,
                'porcentaje_descuento_con' => null,
                'estado_con' => EstadoCatalogo::Habilitado->value,
            ],
        );

        $convenioMunicipalidad = Convenio::query()->updateOrCreate(
            ['codigo_con' => 'MUNICIPALIDAD'],
            [
                'nombre_con' => 'MUNICIPALIDAD',
                'descripcion_con' => 'CONVENIO CON MUNICIPALIDAD',
                'tipo_beneficio_con' => TipoBeneficioConvenio::ExoneracionTotal->value,
                'monto_descuento_con' => null,
                'porcentaje_descuento_con' => null,
                'estado_con' => EstadoCatalogo::Habilitado->value,
            ],
        );

        foreach ([$convenioHijoTrabajador, $convenioMunicipalidad] as $convenio) {
            ConvenioProceso::query()->updateOrCreate(
                [
                    'id_convenio' => $convenio->id_con,
                    'id_proceso' => $proceso->id_pro,
                ],
                [
                    'fecha_inicio_cpr' => '2026-04-01',
                    'fecha_fin_cpr' => '2026-04-30',
                    'cupo_maximo_cpr' => 100,
                    'estado_cpr' => EstadoCatalogo::Habilitado->value,
                ],
            );
        }

        $conceptoUnaCuota = ConceptoPago::query()->where('codigo_cpg', 'INS_UNA')->firstOrFail();
        $conceptoMasCuotas = ConceptoPago::query()->where('codigo_cpg', 'INS_CUO')->firstOrFail();

        ReglaPago::query()->updateOrCreate(
            [
                'id_concepto_pago' => $conceptoUnaCuota->id_cpg,
                'id_proceso' => $proceso->id_pro,
                'id_modalidad_admision' => null,
                'id_convenio_proceso' => null,
            ],
            [
                'monto_total_rpa' => 500.00,
                'permite_cuotas_rpa' => false,
                'monto_minimo_primera_cuota_rpa' => 500.00,
                'numero_maximo_cuotas_rpa' => 1,
                'requiere_pago_completo_rpa' => true,
                'vigente_desde_rpa' => '2026-04-01',
                'vigente_hasta_rpa' => '2026-04-30',
                'estado_rpa' => EstadoCatalogo::Habilitado->value,
            ],
        );

        ReglaPago::query()->updateOrCreate(
            [
                'id_concepto_pago' => $conceptoMasCuotas->id_cpg,
                'id_proceso' => $proceso->id_pro,
                'id_modalidad_admision' => null,
                'id_convenio_proceso' => null,
            ],
            [
                'monto_total_rpa' => 550.00,
                'permite_cuotas_rpa' => true,
                'monto_minimo_primera_cuota_rpa' => 275.00,
                'numero_maximo_cuotas_rpa' => 3,
                'requiere_pago_completo_rpa' => false,
                'vigente_desde_rpa' => '2026-04-01',
                'vigente_hasta_rpa' => '2026-04-30',
                'estado_rpa' => EstadoCatalogo::Habilitado->value,
            ],
        );

        $tiposExpediente = TipoExpediente::query()
            ->where('estado_tex', EstadoCatalogo::Habilitado->value)
            ->get();

        foreach ($tiposExpediente as $tipoExpediente) {
            RequisitoExpediente::query()->updateOrCreate(
                [
                    'id_proceso' => $proceso->id_pro,
                    'id_tipo_expediente' => $tipoExpediente->id_tex,
                ],
                [
                    'obligatorio_rex' => true,
                    'estado_rex' => EstadoCatalogo::Habilitado->value,
                ],
            );
        }
    }
}
