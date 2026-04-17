<?php

use App\Exports\InscripcionesFormatoCepreExport;
use App\Models\Accion;
use App\Models\Carrera;
use App\Models\ConceptoPago;
use App\Models\Convenio;
use App\Models\ConvenioProceso;
use App\Models\EstadoCivil;
use App\Models\Inscripcion;
use App\Models\InscripcionExpediente;
use App\Models\LugarInscripcion;
use App\Models\ModalidadAdmision;
use App\Models\ModalidadLugarInscripcion;
use App\Models\Nacionalidad;
use App\Models\OfertaBase;
use App\Models\Pais;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use App\Models\Persona;
use App\Models\Proceso;
use App\Models\ProcesoOferta;
use App\Models\ReglaPago;
use App\Models\Rol;
use App\Models\Sede;
use App\Models\SedeCarrera;
use App\Models\Semestre;
use App\Models\TipoDocumento;
use App\Models\TipoExpediente;
use App\Models\Turno;
use App\Models\Ubigeo;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class);

function otorgarPermisosReporteInscripcion(Usuario $usuario, array $claves): void
{
    $rol = Rol::query()->create([
        'nombre_rol' => 'ROL '.Str::upper(Str::random(10)),
        'descripcion_rol' => 'Rol de pruebas',
        'estado_rol' => 'HABILITADO',
    ]);

    $usuario->roles()->attach($rol->id_rol);

    foreach ($claves as $clave) {
        $accionClave = Accion::query()
            ->pluck('clave_accion')
            ->sortByDesc(fn (string $accion): int => mb_strlen($accion, 'UTF-8'))
            ->first(fn (string $accion): bool => str_ends_with($clave, "_{$accion}"));

        if (! is_string($accionClave)) {
            throw new RuntimeException("No se pudo resolver la accion para la clave {$clave}.");
        }

        $permisoClave = Str::beforeLast($clave, "_{$accionClave}");

        $permiso = Permiso::query()->firstOrCreate(
            ['clave_permiso' => $permisoClave],
            [
                'nombre_permiso' => Str::upper(str_replace('_', ' ', $permisoClave)),
                'estado_permiso' => 'HABILITADO',
            ],
        );

        $accion = Accion::query()->where('clave_accion', $accionClave)->firstOrFail();

        $permisoAccion = PermisoAccion::query()->updateOrCreate(
            [
                'id_permiso' => $permiso->id_permiso,
                'id_accion' => $accion->id_acc,
            ],
            [
                'clave_peracc' => "{$permiso->clave_permiso}_{$accion->clave_accion}",
                'estado_peracc' => 'HABILITADO',
                'eliminado_en' => null,
            ],
        );

        $rol->permisoAcciones()->syncWithoutDetaching([$permisoAccion->id_peracc]);
    }
}

/**
 * @return array{inscripcion:Inscripcion,proceso:Proceso,modalidad:ModalidadAdmision,lugar:LugarInscripcion,turno:Turno,convenioProceso:ConvenioProceso}
 */
function crearContextoReporteInscripcion(int $indice, string $estado = 'ACTIVO', ?string $nombreConvenio = null): array
{
    $ubigeo = Ubigeo::query()->create([
        'id_ubi' => sprintf('2601%02d', $indice),
        'codigo_departamento_ubi' => '26',
        'codigo_provincia_ubi' => '01',
        'codigo_distrito_ubi' => sprintf('%02d', $indice),
        'nombre_departamento_ubi' => 'UCAYALI',
        'nombre_provincia_ubi' => 'PADRE ABAD',
        'nombre_distrito_ubi' => "DISTRITO {$indice}",
        'estado_ubi' => 'HABILITADO',
    ]);

    $tipoDocumento = TipoDocumento::query()->firstOrCreate(
        ['codigo_tdo' => 'DNI'],
        [
            'nombre_tdo' => 'DNI',
            'longitud_minima_tdo' => 8,
            'longitud_maxima_tdo' => 8,
            'estado_tdo' => 'HABILITADO',
        ],
    );

    $estadoCivil = EstadoCivil::query()->firstOrCreate(
        ['codigo_eci' => 'SOL'],
        [
            'nombre_eci' => 'SOLTERO(A)',
            'estado_eci' => 'HABILITADO',
        ],
    );

    $pais = Pais::query()->firstOrCreate(
        ['codigo_pai' => 'PE'],
        [
            'nombre_pai' => 'PERU',
            'estado_pai' => 'HABILITADO',
        ],
    );

    $nacionalidad = Nacionalidad::query()->firstOrCreate(
        ['codigo_nac' => 'PER'],
        [
            'nombre_nac' => 'PERUANA',
            'estado_nac' => 'HABILITADO',
        ],
    );

    $semestre = Semestre::query()->create([
        'codigo_sem' => "rep-sem-{$indice}",
        'nombre_sem' => "REPORTE 2026-{$indice}",
        'fecha_inicio_inscripcion_sem' => '2026-04-01',
        'fecha_fin_inscripcion_sem' => '2026-04-30',
        'estado_sem' => 'HABILITADO',
    ]);

    $proceso = Proceso::query()->create([
        'id_semestre' => $semestre->id_sem,
        'codigo_pro' => "REP-PRO-{$indice}",
        'nombre_pro' => "PROCESO REPORTE {$indice}",
        'fecha_inicio_pro' => '2026-04-01',
        'fecha_fin_pro' => '2026-04-30',
        'estado_pro' => 'HABILITADO',
    ]);

    $modalidad = ModalidadAdmision::query()->create([
        'codigo_mad' => "REP-MAD-{$indice}",
        'nombre_mad' => "MODALIDAD REPORTE {$indice}",
        'estado_mad' => 'HABILITADO',
    ]);

    $sede = Sede::query()->create([
        'codigo_sed' => "REP-SEDE-{$indice}",
        'nombre_sed' => "SEDE REPORTE {$indice}",
        'id_ubigeo' => $ubigeo->id_ubi,
        'estado_sed' => 'HABILITADO',
    ]);

    $lugar = LugarInscripcion::query()->create([
        'codigo_lin' => "REP-LIN-{$indice}",
        'nombre_lin' => "LUGAR REPORTE {$indice}",
        'id_sede' => $sede->id_sed,
        'estado_lin' => 'HABILITADO',
    ]);

    $carrera = Carrera::query()->create([
        'codigo_car' => "REP-CAR-{$indice}",
        'nombre_car' => "CARRERA REPORTE {$indice}",
        'estado_car' => 'HABILITADO',
    ]);

    $sedeCarrera = SedeCarrera::query()->create([
        'id_sede' => $sede->id_sed,
        'id_carrera' => $carrera->id_car,
        'codigo_externo_sca' => "REP-SCA-{$indice}",
        'estado_sca' => 'HABILITADO',
    ]);

    $modalidadLugar = ModalidadLugarInscripcion::query()->create([
        'id_modalidad_admision' => $modalidad->id_mad,
        'id_lugar_inscripcion' => $lugar->id_lin,
        'estado_mli' => 'HABILITADO',
    ]);

    $ofertaBase = OfertaBase::query()->create([
        'codigo_externo_ofb' => (string) (3700 + $indice),
        'id_modalidad_lugar_inscripcion' => $modalidadLugar->id_mli,
        'id_sede_carrera' => $sedeCarrera->id_sca,
        'estado_ofb' => 'HABILITADO',
    ]);

    $turno = Turno::query()->create([
        'codigo_tur' => "REP-TUR-{$indice}",
        'nombre_tur' => "TURNO REPORTE {$indice}",
        'estado_tur' => 'HABILITADO',
    ]);

    $procesoOferta = ProcesoOferta::query()->create([
        'id_proceso' => $proceso->id_pro,
        'id_oferta_base' => $ofertaBase->id_ofb,
        'id_turno' => $turno->id_tur,
        'estado_pof' => 'HABILITADO',
    ]);

    $convenio = Convenio::query()->create([
        'codigo_con' => "REP-CON-{$indice}",
        'nombre_con' => $nombreConvenio ?? "CONVENIO REPORTE {$indice}",
        'tipo_beneficio_con' => 'DESCUENTO',
        'monto_descuento_con' => 100,
        'estado_con' => 'HABILITADO',
    ]);

    $convenioProceso = ConvenioProceso::query()->create([
        'id_convenio' => $convenio->id_con,
        'id_proceso' => $proceso->id_pro,
        'fecha_inicio_cpr' => '2026-04-01',
        'fecha_fin_cpr' => '2026-04-30',
        'cupo_maximo_cpr' => 10,
        'estado_cpr' => 'HABILITADO',
    ]);

    $persona = Persona::query()->create([
        'id_tipo_documento' => $tipoDocumento->id_tdo,
        'numero_documento_per' => (string) (7650000 + $indice),
        'solo_un_apellido_per' => false,
        'apellido_paterno_per' => "REPORTE{$indice}",
        'apellido_materno_per' => "MATERNO{$indice}",
        'nombres_per' => "PERSONA {$indice}",
        'id_estado_civil' => $estadoCivil->id_eci,
        'id_pais_nacimiento' => $pais->id_pai,
        'id_nacionalidad' => $nacionalidad->id_nac,
        'id_ubigeo_nacimiento' => $ubigeo->id_ubi,
        'id_ubigeo_domicilio' => $ubigeo->id_ubi,
        'estado_per' => 'HABILITADO',
    ]);

    $inscripcion = Inscripcion::query()->create([
        'codigo_ins' => "REP-INS-{$indice}",
        'id_persona' => $persona->id_per,
        'id_proceso_oferta' => $procesoOferta->id_pof,
        'id_convenio_proceso' => $convenioProceso->id_cpr,
        'fecha_inscripcion_ins' => '2026-04-17 09:00:00',
        'estado_ins' => $estado,
    ]);

    return compact('inscripcion', 'proceso', 'modalidad', 'lugar', 'turno', 'convenioProceso');
}

test('authorized users can access reportes de inscripciones page', function () {
    Carbon::setTestNow('2026-04-17 09:30:00');

    $usuario = Usuario::factory()->create();
    otorgarPermisosReporteInscripcion($usuario, ['reporte_inscripcion_indice']);

    crearContextoReporteInscripcion(1);

    $this->actingAs($usuario)
        ->get(route('reportes.inscripciones.index'))
        ->assertOk()
        ->assertSee('REPORTES DE INSCRIPCIONES');
});

test('users without reporte permiso cannot access reportes de inscripciones page', function () {
    $usuario = Usuario::factory()->create();

    $this->actingAs($usuario)
        ->get(route('reportes.inscripciones.index'))
        ->assertForbidden();
});

test('reportes de inscripciones defaults to current semester and filters data', function () {
    Carbon::setTestNow('2026-04-17 09:30:00');

    $usuario = Usuario::factory()->create();
    otorgarPermisosReporteInscripcion($usuario, ['reporte_inscripcion_indice']);

    $contextoActual = crearContextoReporteInscripcion(2, 'ACTIVO', 'HIJOS UNU');
    $contextoAnterior = crearContextoReporteInscripcion(3, 'ANULADO', 'MUNICIPALIDAD');

    $conceptoPago = ConceptoPago::query()->create([
        'codigo_cpg' => 'REP-CUOTA',
        'nombre_cpg' => 'INSCRIPCION',
        'permite_cuotas_cpg' => true,
        'estado_cpg' => 'HABILITADO',
    ]);

    $reglaPago = ReglaPago::query()->create([
        'id_concepto_pago' => $conceptoPago->id_cpg,
        'id_proceso' => $contextoActual['proceso']->id_pro,
        'id_modalidad_admision' => $contextoActual['modalidad']->id_mad,
        'id_convenio_proceso' => $contextoActual['convenioProceso']->id_cpr,
        'monto_total_rpa' => 500,
        'permite_cuotas_rpa' => true,
        'monto_minimo_primera_cuota_rpa' => 250,
        'numero_maximo_cuotas_rpa' => 2,
        'requiere_pago_completo_rpa' => false,
        'vigente_desde_rpa' => '2026-04-01',
        'vigente_hasta_rpa' => '2026-04-30',
        'estado_rpa' => 'HABILITADO',
    ]);

    $tipoExpediente = TipoExpediente::query()->create([
        'codigo_tex' => 'REP-DNI',
        'nombre_tex' => 'DNI',
        'estado_tex' => 'HABILITADO',
    ]);

    $contextoAnterior['proceso']->semestre->update([
        'fecha_inicio_inscripcion_sem' => '2026-01-01',
        'fecha_fin_inscripcion_sem' => '2026-02-28',
    ]);

    DB::table('tbl_inscripcion_obligacion_pago')->insert([
        'id_inscripcion' => $contextoActual['inscripcion']->id_ins,
        'id_regla_pago' => $reglaPago->id_rpa,
        'monto_total_iop' => 500,
        'monto_pagado_iop' => 250,
        'saldo_iop' => 250,
        'numero_cuotas_iop' => 2,
        'estado_iop' => 'PARCIAL',
        'creado_en' => now(),
        'actualizado_en' => now(),
    ]);

    InscripcionExpediente::query()->create([
        'id_inscripcion' => $contextoActual['inscripcion']->id_ins,
        'id_tipo_expediente' => $tipoExpediente->id_tex,
        'nombre_archivo_iex' => 'doc.pdf',
        'archivo_url_iex' => 'expedientes/doc.pdf',
        'estado_revision_iex' => 'OBSERVADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::reportes.inscripciones')
        ->assertSet('idSemestre', (string) $contextoActual['proceso']->id_semestre)
        ->assertSee('CARRERA REPORTE 2')
        ->assertSee('MODALIDAD REPORTE 2')
        ->assertSee('HIJOS UNU')
        ->assertDontSee('CARRERA REPORTE 3')
        ->set('idSemestre', '')
        ->assertSee('CARRERA REPORTE 2')
        ->assertSee('CARRERA REPORTE 3')
        ->set('idProceso', (string) $contextoActual['proceso']->id_pro)
        ->assertSee('HIJOS UNU')
        ->assertDontSee('MUNICIPALIDAD');
});

test('authorized users can export reporte inscripciones to excel respecting active filters', function () {
    Carbon::setTestNow('2026-04-17 09:30:00');
    Excel::fake();

    $usuario = Usuario::factory()->create();
    otorgarPermisosReporteInscripcion($usuario, ['reporte_inscripcion_indice', 'reporte_inscripcion_exportar_excel']);

    $contextoA = crearContextoReporteInscripcion(10, 'ACTIVO');
    $contextoB = crearContextoReporteInscripcion(11, 'ACTIVO');

    $this->actingAs($usuario);

    Livewire::test('pages::reportes.inscripciones')
        ->set('idSemestre', '')
        ->set('idProceso', (string) $contextoA['proceso']->id_pro)
        ->call('exportarExcel');

    Excel::assertDownloaded('reporte-inscripciones-20260417-093000.xlsx', function (InscripcionesFormatoCepreExport $export) use ($contextoA): bool {
        $rows = $export->collection();

        expect($rows)->toHaveCount(1)
            ->and($rows->first()[1])->toBe($contextoA['inscripcion']->persona->numero_documento_per);

        return true;
    });

    Carbon::setTestNow();
});

test('users without exportar_excel permiso cannot export reporte inscripciones', function () {
    Excel::fake();

    $usuario = Usuario::factory()->create();
    otorgarPermisosReporteInscripcion($usuario, ['reporte_inscripcion_indice']);

    crearContextoReporteInscripcion(12, 'ACTIVO');

    $this->actingAs($usuario);

    Livewire::test('pages::reportes.inscripciones')
        ->set('idSemestre', '')
        ->call('exportarExcel')
        ->assertForbidden();
});
