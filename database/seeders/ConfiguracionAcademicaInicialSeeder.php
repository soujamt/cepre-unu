<?php

namespace Database\Seeders;

use App\Enums\EstadoCatalogo;
use App\Enums\EstadoProceso;
use App\Models\Carrera;
use App\Models\ConceptoPago;
use App\Models\ModalidadAdmision;
use App\Models\Proceso;
use App\Models\ProcesoCarrera;
use App\Models\ProcesoModalidad;
use App\Models\ReglaPago;
use App\Models\Sede;
use Illuminate\Database\Seeder;

class ConfiguracionAcademicaInicialSeeder extends Seeder
{
    public function run(): void
    {
        $proceso = Proceso::query()->updateOrCreate(
            ['codigo_pro' => '2026_1'],
            [
                'nombre_pro' => '2026-1',
                'fecha_inicio_pro' => '2026-04-01 00:00:00',
                'fecha_fin_pro' => '2026-04-30 23:59:59',
                'estado_pro' => EstadoProceso::Habilitado->value,
            ],
        );

        $modalidades = ModalidadAdmision::query()
            ->where('estado_mad', EstadoCatalogo::Habilitado->value)
            ->whereNotNull('condicion_egreso_mad')
            ->get();

        foreach ($modalidades as $modalidad) {
            ProcesoModalidad::query()->updateOrCreate(
                ['id_proceso' => $proceso->id_pro, 'id_modalidad_admision' => $modalidad->id_mad],
                ['estado_pmo' => EstadoCatalogo::Habilitado->value],
            );
        }

        $sedePrincipal = Sede::query()->where('codigo_sed', 'SEDCAL')->firstOrFail();

        foreach (Carrera::query()->where('estado_car', EstadoCatalogo::Habilitado->value)->get() as $carrera) {
            ProcesoCarrera::query()->updateOrCreate(
                ['id_proceso' => $proceso->id_pro, 'id_sede' => $sedePrincipal->id_sed, 'id_carrera' => $carrera->id_car],
                ['estado_pca' => EstadoCatalogo::Habilitado->value],
            );
        }

        $conceptoUnaCuota = ConceptoPago::query()->where('codigo_cpg', 'INS_UNA')->firstOrFail();
        $conceptoMasCuotas = ConceptoPago::query()->where('codigo_cpg', 'INS_CUO')->firstOrFail();

        ReglaPago::query()->updateOrCreate(
            [
                'id_concepto_pago' => $conceptoUnaCuota->id_cpg,
                'id_proceso' => $proceso->id_pro,
                'id_modalidad_admision' => null,
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
    }
}
