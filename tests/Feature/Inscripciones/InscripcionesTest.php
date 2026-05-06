<?php

use App\Exports\InscripcionesFormatoCepreExport;
use App\Models\Accion;
use App\Models\Aula;
use App\Models\Carrera;
use App\Models\EstadoCivil;
use App\Models\Inscripcion;
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
use App\Models\ProcesoAula;
use App\Models\ProcesoOferta;
use App\Models\Rol;
use App\Models\Sede;
use App\Models\SedeCarrera;
use App\Models\Semestre;
use App\Models\TipoDocumento;
use App\Models\Turno;
use App\Models\Ubigeo;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class);

function otorgarPermisosInscripcion(Usuario $usuario, array $claves): void
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
 * @return array{inscripcion: Inscripcion, proceso: Proceso, persona: Persona, aula: Aula, procesoAula: ProcesoAula}
 */
function crearContextoListadoInscripcion(int $indice, string $estado = 'PENDIENTE'): array
{
    $ubigeo = Ubigeo::query()->create([
        'id_ubi' => sprintf('2501%02d', $indice),
        'codigo_departamento_ubi' => '25',
        'codigo_provincia_ubi' => '01',
        'codigo_distrito_ubi' => sprintf('%02d', $indice),
        'nombre_departamento_ubi' => 'UCAYALI',
        'nombre_provincia_ubi' => 'CORONEL PORTILLO',
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
        'codigo_sem' => "semestre_2026_{$indice}",
        'nombre_sem' => "SEMESTRE 2026-{$indice}",
        'fecha_inicio_inscripcion_sem' => '2026-03-01',
        'fecha_fin_inscripcion_sem' => '2026-06-30',
        'estado_sem' => 'HABILITADO',
    ]);

    $proceso = Proceso::query()->create([
        'id_semestre' => $semestre->id_sem,
        'codigo_pro' => "proceso_{$indice}",
        'nombre_pro' => "PROCESO {$indice}",
        'fecha_inicio_pro' => '2026-03-01',
        'fecha_fin_pro' => '2026-06-30',
        'estado_pro' => 'HABILITADO',
    ]);

    $modalidad = ModalidadAdmision::query()->create([
        'codigo_mad' => "MAD{$indice}",
        'nombre_mad' => "MODALIDAD {$indice}",
        'estado_mad' => 'HABILITADO',
    ]);

    $sede = Sede::query()->create([
        'codigo_sed' => "SEDE{$indice}",
        'nombre_sed' => "SEDE {$indice}",
        'id_ubigeo' => $ubigeo->id_ubi,
        'estado_sed' => 'HABILITADO',
    ]);

    $lugarInscripcion = LugarInscripcion::query()->create([
        'codigo_lin' => "LIN{$indice}",
        'nombre_lin' => "LUGAR {$indice}",
        'id_sede' => $sede->id_sed,
        'estado_lin' => 'HABILITADO',
    ]);

    $carrera = Carrera::query()->create([
        'codigo_car' => "CAR{$indice}",
        'nombre_car' => "CARRERA {$indice}",
        'estado_car' => 'HABILITADO',
    ]);

    $sedeCarrera = SedeCarrera::query()->create([
        'id_sede' => $sede->id_sed,
        'id_carrera' => $carrera->id_car,
        'codigo_externo_sca' => "SCA{$indice}",
        'estado_sca' => 'HABILITADO',
    ]);

    $modalidadLugar = ModalidadLugarInscripcion::query()->create([
        'id_modalidad_admision' => $modalidad->id_mad,
        'id_lugar_inscripcion' => $lugarInscripcion->id_lin,
        'estado_mli' => 'HABILITADO',
    ]);

    $ofertaBase = OfertaBase::query()->create([
        'codigo_externo_ofb' => (string) (2700 + $indice),
        'id_modalidad_lugar_inscripcion' => $modalidadLugar->id_mli,
        'id_sede_carrera' => $sedeCarrera->id_sca,
        'estado_ofb' => 'HABILITADO',
    ]);

    $turno = Turno::query()->create([
        'codigo_tur' => "TUR{$indice}",
        'nombre_tur' => "TURNO {$indice}",
        'estado_tur' => 'HABILITADO',
    ]);

    $procesoOferta = ProcesoOferta::query()->create([
        'id_proceso' => $proceso->id_pro,
        'id_oferta_base' => $ofertaBase->id_ofb,
        'id_turno' => $turno->id_tur,
        'estado_pof' => 'HABILITADO',
    ]);

    $aula = Aula::query()->create([
        'nombre_aul' => "AULA {$indice}",
        'piso_aul' => 'PISO 1',
        'lugar_aul' => 'UNU',
        'orden_aul' => $indice,
        'estado_aul' => 'HABILITADO',
    ]);

    $procesoAula = ProcesoAula::query()->create([
        'id_proceso' => $proceso->id_pro,
        'id_turno' => $turno->id_tur,
        'id_aula' => $aula->id_aul,
        'capacidad_pau' => 40,
        'estado_pau' => 'HABILITADO',
    ]);

    $persona = Persona::query()->create([
        'id_tipo_documento' => $tipoDocumento->id_tdo,
        'numero_documento_per' => (string) (7450000 + $indice),
        'solo_un_apellido_per' => false,
        'apellido_paterno_per' => "APELLIDO{$indice}",
        'apellido_materno_per' => "MATERNO{$indice}",
        'nombres_per' => "NOMBRES {$indice}",
        'id_estado_civil' => $estadoCivil->id_eci,
        'id_pais_nacimiento' => $pais->id_pai,
        'id_nacionalidad' => $nacionalidad->id_nac,
        'id_ubigeo_nacimiento' => $ubigeo->id_ubi,
        'id_ubigeo_domicilio' => $ubigeo->id_ubi,
        'correo_electronico_per' => "persona{$indice}@mail.com",
        'estado_per' => 'HABILITADO',
    ]);

    $inscripcion = Inscripcion::query()->create([
        'codigo_ins' => "INS-{$indice}",
        'id_persona' => $persona->id_per,
        'id_proceso_oferta' => $procesoOferta->id_pof,
        'id_proceso_aula' => $procesoAula->id_pau,
        'fecha_inscripcion_ins' => '2026-03-20 08:00:00',
        'estado_ins' => $estado,
    ]);

    return compact('inscripcion', 'proceso', 'persona', 'aula', 'procesoAula');
}

test('authorized users can access inscripciones page', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcion($usuario, ['inscripcion_indice']);

    $this->actingAs($usuario)
        ->get(route('inscripciones.index'))
        ->assertOk()
        ->assertSee('INSCRIPCIONES');
});

test('authenticated users without permiso cannot access inscripciones page', function () {
    $usuario = Usuario::factory()->create();

    $this->actingAs($usuario)
        ->get(route('inscripciones.index'))
        ->assertForbidden();
});

test('users can filter inscripciones by search process and state', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcion($usuario, ['inscripcion_indice']);

    $contextoPendiente = crearContextoListadoInscripcion(1, 'PENDIENTE');
    $contextoActivo = crearContextoListadoInscripcion(2, 'ACTIVO');

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.inscripciones')
        ->set('idSemestre', '')
        ->assertSee('INS-1')
        ->assertSee('INS-2')
        ->assertSee($contextoPendiente['aula']->nombre_aul)
        ->assertSee($contextoActivo['aula']->nombre_aul)
        ->set('buscar', $contextoPendiente['persona']->numero_documento_per)
        ->assertSee('INS-1')
        ->assertDontSee('INS-2')
        ->set('buscar', '')
        ->set('idProceso', (string) $contextoActivo['proceso']->id_pro)
        ->assertSee('INS-2')
        ->assertDontSee('INS-1')
        ->set('idProceso', '')
        ->set('filtroEstado.ACTIVO', true)
        ->assertSee('INS-2')
        ->assertDontSee('INS-1');
});

test('list defaults to current semester and can filter by semester modalidad and lugar', function () {
    Carbon::setTestNow('2026-04-17 09:30:00');

    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcion($usuario, ['inscripcion_indice']);

    $contextoSemestreActual = crearContextoListadoInscripcion(9, 'ACTIVO');
    $contextoOtroSemestre = crearContextoListadoInscripcion(10, 'ACTIVO');

    $contextoSemestreActual['proceso']->semestre->update([
        'fecha_inicio_inscripcion_sem' => '2026-04-01',
        'fecha_fin_inscripcion_sem' => '2026-04-30',
        'nombre_sem' => 'SEMESTRE ACTUAL',
    ]);

    $contextoOtroSemestre['proceso']->semestre->update([
        'fecha_inicio_inscripcion_sem' => '2026-01-01',
        'fecha_fin_inscripcion_sem' => '2026-02-28',
        'nombre_sem' => 'SEMESTRE ANTERIOR',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.inscripciones')
        ->assertSet('idSemestre', (string) $contextoSemestreActual['proceso']->id_semestre)
        ->assertSee('INS-9')
        ->assertDontSee('INS-10')
        ->set('idSemestre', (string) $contextoOtroSemestre['proceso']->id_semestre)
        ->assertSee('INS-10')
        ->assertDontSee('INS-9')
        ->set('idSemestre', (string) $contextoSemestreActual['proceso']->id_semestre)
        ->set('idModalidad', (string) $contextoSemestreActual['proceso']->procesosOferta->first()->ofertaBase->modalidadLugarInscripcion->id_modalidad_admision)
        ->assertSee('INS-9')
        ->assertDontSee('INS-10')
        ->set('idLugarInscripcion', (string) $contextoSemestreActual['proceso']->procesosOferta->first()->ofertaBase->modalidadLugarInscripcion->id_lugar_inscripcion)
        ->assertSee('INS-9')
        ->assertDontSee('INS-10');

    Carbon::setTestNow();
});

test('inscripciones summary shows counts by state', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcion($usuario, ['inscripcion_indice']);

    crearContextoListadoInscripcion(1, 'PENDIENTE');
    crearContextoListadoInscripcion(2, 'ACTIVO');
    crearContextoListadoInscripcion(3, 'ANULADO');

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.inscripciones')
        ->set('idSemestre', '')
        ->assertSee('3')
        ->assertSee('1');
});

test('authorized users can access inscripcion detail page', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcion($usuario, ['inscripcion_indice']);

    $contextoActivo = crearContextoListadoInscripcion(4, 'ACTIVO');

    $this->actingAs($usuario)
        ->get(route('inscripciones.detalle', encode($contextoActivo['inscripcion']->id_ins)))
        ->assertOk()
        ->assertSee('DETALLE DE INSCRIPCION')
        ->assertSee($contextoActivo['inscripcion']->codigo_ins)
        ->assertSee($contextoActivo['persona']->numero_documento_per)
        ->assertSee($contextoActivo['proceso']->nombre_pro)
        ->assertSee($contextoActivo['aula']->nombre_aul);
});

test('authorized users can access inscripcion detail page even when the inscripcion is pending or annulled', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcion($usuario, ['inscripcion_indice']);

    $contextoPendiente = crearContextoListadoInscripcion(41, 'PENDIENTE');
    $contextoAnulado = crearContextoListadoInscripcion(42, 'ANULADO');

    $this->actingAs($usuario)
        ->get(route('inscripciones.detalle', encode($contextoPendiente['inscripcion']->id_ins)))
        ->assertOk()
        ->assertSee($contextoPendiente['inscripcion']->codigo_ins)
        ->assertSee($contextoPendiente['persona']->numero_documento_per);

    $this->actingAs($usuario)
        ->get(route('inscripciones.detalle', encode($contextoAnulado['inscripcion']->id_ins)))
        ->assertOk()
        ->assertSee($contextoAnulado['inscripcion']->codigo_ins)
        ->assertSee($contextoAnulado['persona']->numero_documento_per);
});

test('list shows detail action for active and pending inscripciones and continue action for pending ones', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcion($usuario, ['inscripcion_indice', 'inscripcion_crear']);

    crearContextoListadoInscripcion(5, 'ACTIVO');
    crearContextoListadoInscripcion(6, 'PENDIENTE');

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.inscripciones')
        ->set('idSemestre', '')
        ->assertSee('Ver detalle')
        ->assertSee('Continuar registro');
});

test('report list does not show continue action when user lacks inscripcion crear permiso', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcion($usuario, ['inscripcion_indice']);

    crearContextoListadoInscripcion(61, 'PENDIENTE');

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.inscripciones')
        ->set('idSemestre', '')
        ->assertDontSee('Continuar registro');
});

test('users can export inscripciones to excel preserving current filters', function () {
    Carbon::setTestNow('2026-04-17 09:30:00');
    Excel::fake();

    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcion($usuario, ['inscripcion_indice', 'inscripcion_exportar_excel']);

    $contextoPendiente = crearContextoListadoInscripcion(7, 'PENDIENTE');
    $contextoActivo = crearContextoListadoInscripcion(8, 'ACTIVO');

    $contextoPendiente['persona']->update([
        'solo_un_apellido_per' => true,
        'apellido_materno_per' => null,
        'fecha_nacimiento_per' => '2001-02-03',
        'sexo_per' => 'FEMENINO',
        'discapacidad_per' => false,
        'tipo_lengua_materna_per' => 'NATIVA',
        'direccion_per' => 'JR. LOS LAURELES 123',
        'celular_per' => '987654321',
    ]);

    $contextoPendiente['inscripcion']->update([
        'anio_graduacion_ins' => 2024,
        'veces_postula_unu_ins' => 1,
        'veces_postula_otras_ins' => 2,
        'observacion_ins' => 'Registro exportable',
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.inscripciones')
        ->set('idSemestre', '')
        ->set('filtroEstado.PENDIENTE', true)
        ->call('exportarExcel');

    Excel::assertDownloaded('inscripciones-cepre-20260417-093000.xlsx', function (InscripcionesFormatoCepreExport $export): bool {
        $rows = $export->collection();

        expect($rows)->toHaveCount(1)
            ->and($rows->first()[1])->toBe('7450007')
            ->and($rows->first()[2])->toBe(1)
            ->and($rows->first()[8])->toBe('F')
            ->and($rows->first()[20])->toBe(2024)
            ->and($rows->first()[25])->toBe(1)
            ->and($rows->first()[26])->toBe(2)
            ->and($rows->first()[33])->toBe('Registro exportable');

        return true;
    });

    Carbon::setTestNow();
});

test('users without export permiso cannot export inscripciones to excel', function () {
    Excel::fake();

    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcion($usuario, ['inscripcion_indice']);

    crearContextoListadoInscripcion(11, 'ACTIVO');

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.inscripciones')
        ->set('idSemestre', '')
        ->call('exportarExcel')
        ->assertForbidden();
});

test('anular inscripcion libera el aula asignada', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcion($usuario, ['inscripcion_indice', 'inscripcion_estado']);

    $contextoActivo = crearContextoListadoInscripcion(90, 'ACTIVO');

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.inscripciones')
        ->set('idInscripcionAnular', $contextoActivo['inscripcion']->id_ins)
        ->set('motivoAnulacion', 'Anulación por desistimiento del postulante.')
        ->call('anularInscripcion')
        ->assertHasNoErrors();

    expect($contextoActivo['inscripcion']->fresh()->estado_ins->value)->toBe('ANULADO')
        ->and($contextoActivo['inscripcion']->fresh()->id_proceso_aula)->toBeNull();
});

test('no se puede asignar aula a una inscripcion anulada', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcion($usuario, ['inscripcion_indice', 'inscripcion_editar']);

    $contextoAnulado = crearContextoListadoInscripcion(91, 'ANULADO');

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.inscripciones')
        ->call('abrirModalAsignarAula', $contextoAnulado['inscripcion']->id_ins)
        ->assertDispatched('toast', message: 'No se puede asignar aula a una inscripción anulada.', type: 'error');

    Livewire::test('pages::inscripciones.inscripciones')
        ->set('idInscripcionAula', $contextoAnulado['inscripcion']->id_ins)
        ->set('idProcesoAulaSeleccionada', (string) $contextoAnulado['procesoAula']->id_pau)
        ->call('guardarAulaInscripcion')
        ->assertDispatched('toast', message: 'No se puede asignar aula a una inscripción anulada.', type: 'error');
});
