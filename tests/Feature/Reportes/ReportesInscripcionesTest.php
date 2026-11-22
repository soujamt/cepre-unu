<?php

use App\Exports\InscripcionesFormatoCepreExport;
use App\Models\Accion;
use App\Models\Carrera;
use App\Models\ConceptoPago;
use App\Models\EstadoCivil;
use App\Models\Inscripcion;
use App\Models\ModalidadAdmision;
use App\Models\Nacionalidad;
use App\Models\Pais;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use App\Models\Persona;
use App\Models\Proceso;
use App\Models\ReglaPago;
use App\Models\Rol;
use App\Models\Sede;
use App\Models\TipoDocumento;
use App\Models\Turno;
use App\Models\Ubigeo;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
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
 * @return array{inscripcion:Inscripcion,proceso:Proceso,modalidad:ModalidadAdmision,sede:Sede,carrera:Carrera,turno:Turno}
 */
function crearContextoReporteInscripcion(int $indice, string $estado = 'ACTIVO'): array
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

    $proceso = Proceso::query()->create([
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

    $carrera = Carrera::query()->create([
        'codigo_car' => "REP-CAR-{$indice}",
        'nombre_car' => "CARRERA REPORTE {$indice}",
        'estado_car' => 'HABILITADO',
    ]);

    $turno = Turno::query()->create([
        'codigo_tur' => "REP-TUR-{$indice}",
        'nombre_tur' => "TURNO REPORTE {$indice}",
        'estado_tur' => 'HABILITADO',
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
        'id_proceso' => $proceso->id_pro,
        'id_sede' => $sede->id_sed,
        'id_modalidad_admision' => $modalidad->id_mad,
        'id_carrera' => $carrera->id_car,
        'id_turno' => $turno->id_tur,
        'fecha_inscripcion_ins' => '2026-04-17 09:00:00',
        'estado_ins' => $estado,
    ]);

    return compact('inscripcion', 'proceso', 'modalidad', 'sede', 'carrera', 'turno');
}

test('authorized users can access reportes de inscripciones page', function () {
    Carbon::setTestNow('2026-04-17 09:30:00');

    $usuario = Usuario::factory()->create();
    otorgarPermisosReporteInscripcion($usuario, ['reporte_inscripcion_indice']);

    crearContextoReporteInscripcion(1);

    $this->actingAs($usuario)
        ->get(route('reportes.inscripciones.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('reportes/inscripciones')->has('resumen')->has('porCarrera'));
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

    $contextoActual = crearContextoReporteInscripcion(2, 'ACTIVO');
    $contextoAnterior = crearContextoReporteInscripcion(3, 'ANULADO');

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
        'monto_total_rpa' => 500,
        'permite_cuotas_rpa' => true,
        'monto_minimo_primera_cuota_rpa' => 250,
        'numero_maximo_cuotas_rpa' => 2,
        'requiere_pago_completo_rpa' => false,
        'vigente_desde_rpa' => '2026-04-01',
        'vigente_hasta_rpa' => '2026-04-30',
        'estado_rpa' => 'HABILITADO',
    ]);

    $contextoAnterior['proceso']->update([
        'fecha_inicio_pro' => '2026-01-01',
        'fecha_fin_pro' => '2026-02-28',
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

    $this->actingAs($usuario);

    $carreras = fn (array $consulta): array => collect($this->get(route('reportes.inscripciones.index', $consulta))->inertiaProps('porCarrera'))->pluck('nombre')->sort()->values()->all();

    $this->get(route('reportes.inscripciones.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('filtros.proceso', (string) $contextoActual['proceso']->id_pro)
            ->where('porModalidad.0.nombre', 'MODALIDAD REPORTE 2')
            ->where('resumen.pagoParcial', 1)
            ->where('porEstadoPago', fn ($estados): bool => collect($estados)->firstWhere('clave', 'PARCIAL')['total'] === 1)
            ->where('porDia', fn ($dias): bool => collect($dias)->sum('total') === 1)
            ->where('porSede', fn ($sedes): bool => collect($sedes)->sum('total') === 1)
            ->has('porSexo')
            ->has('porOrigen')
            ->has('recaudacion.esperado'));

    expect($carreras([]))->toBe(['CARRERA REPORTE 2'])
        ->and($carreras(['proceso' => 'todos']))->toBe(['CARRERA REPORTE 2', 'CARRERA REPORTE 3'])
        ->and($carreras(['proceso' => $contextoActual['proceso']->id_pro]))->toBe(['CARRERA REPORTE 2']);
});

test('authorized users can export reporte inscripciones with names instead of admission codes', function () {
    Carbon::setTestNow('2026-04-17 09:30:00');
    Excel::fake();

    $usuario = Usuario::factory()->create();
    otorgarPermisosReporteInscripcion($usuario, ['reporte_inscripcion_indice', 'reporte_inscripcion_exportar_excel']);

    $contextoA = crearContextoReporteInscripcion(10, 'ACTIVO');
    $contextoB = crearContextoReporteInscripcion(11, 'ACTIVO');

    $this->actingAs($usuario);

    $this->get(route('reportes.inscripciones.exportar', ['proceso' => $contextoA['proceso']->id_pro]));

    Excel::assertDownloaded('reporte-inscripciones-20260417-093000.xlsx', function (InscripcionesFormatoCepreExport $export) use ($contextoA): bool {
        $rows = $export->collection();

        $fila = $rows->first();
        $headings = $export->headings();

        expect($rows)->toHaveCount(1)
            ->and($fila[array_search('NUMERO_DOCUMENTO', $headings, true)])->toBe($contextoA['inscripcion']->persona->numero_documento_per)
            ->and($fila[array_search('CODIGO_LUGAR_INSCRIPCION', $headings, true)])->toBe('SEDE REPORTE 10 / MODALIDAD REPORTE 10')
            ->and($fila[array_search('CODIGO_CARRERA', $headings, true)])->toBe('CARRERA REPORTE 10');

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

    $this->get(route('reportes.inscripciones.exportar', ['proceso' => 'todos']))->assertForbidden();
});

test('reportes page hides export button when user lacks exportar_excel permiso', function () {
    Carbon::setTestNow('2026-04-17 09:30:00');

    $usuario = Usuario::factory()->create();
    otorgarPermisosReporteInscripcion($usuario, ['reporte_inscripcion_indice']);

    crearContextoReporteInscripcion(13, 'ACTIVO');

    $this->actingAs($usuario);

    $this->get(route('reportes.inscripciones.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('auth.permisos.claves', fn ($claves): bool => ! collect($claves)->contains('reporte_inscripcion_exportar_excel')));

    Carbon::setTestNow();
});
