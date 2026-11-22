<?php

use App\Models\Accion;
use App\Models\Aula;
use App\Models\Carrera;
use App\Models\EstadoCivil;
use App\Models\Inscripcion;
use App\Models\ModalidadAdmision;
use App\Models\Nacionalidad;
use App\Models\Pais;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use App\Models\Persona;
use App\Models\Proceso;
use App\Models\ProcesoAula;
use App\Models\ProcesoCarrera;
use App\Models\Rol;
use App\Models\Sede;
use App\Models\TipoDocumento;
use App\Models\Turno;
use App\Models\Ubigeo;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Support\SessionKey;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

function otorgarPermisosEdicion(Usuario $usuario, array $claves): void
{
    $rol = Rol::query()->create(['nombre_rol' => 'ROL '.Str::upper(Str::random(10)), 'descripcion_rol' => 'Rol de pruebas', 'estado_rol' => 'HABILITADO']);
    $usuario->roles()->attach($rol->id_rol);

    foreach ($claves as $clave) {
        $accionClave = Accion::query()->pluck('clave_accion')
            ->sortByDesc(fn (string $accion): int => mb_strlen($accion, 'UTF-8'))
            ->first(fn (string $accion): bool => str_ends_with($clave, "_{$accion}"));
        $permisoClave = Str::beforeLast($clave, "_{$accionClave}");
        $permiso = Permiso::query()->firstOrCreate(['clave_permiso' => $permisoClave], ['nombre_permiso' => Str::upper($permisoClave), 'estado_permiso' => 'HABILITADO']);
        $accion = Accion::query()->where('clave_accion', $accionClave)->firstOrFail();
        $permisoAccion = PermisoAccion::query()->updateOrCreate(
            ['id_permiso' => $permiso->id_permiso, 'id_accion' => $accion->id_acc],
            ['clave_peracc' => "{$permisoClave}_{$accionClave}", 'estado_peracc' => 'HABILITADO', 'eliminado_en' => null],
        );
        $rol->permisoAcciones()->syncWithoutDetaching([$permisoAccion->id_peracc]);
    }
}

/**
 * Proceso con dos sedes y dos turnos. La sede CENTRAL ofrece ENFERMERÍA y SISTEMAS;
 * la sede NORTE solo SISTEMAS. DERECHO está deshabilitada en CENTRAL. El turno TARDE
 * tiene un aula con un solo cupo.
 *
 * @return array{inscripcion: Inscripcion, usuario: Usuario, central: Sede, norte: Sede, enfermeria: Carrera, sistemas: Carrera, derecho: Carrera, manana: Turno, tarde: Turno, aulaManana: ProcesoAula, aulaTarde: ProcesoAula, proceso: Proceso, persona: Persona}
 */
function contextoEdicion(string $estado = 'ACTIVO'): array
{
    $ubigeo = Ubigeo::query()->firstOrCreate(['id_ubi' => '250101'], ['codigo_departamento_ubi' => '25', 'codigo_provincia_ubi' => '01', 'codigo_distrito_ubi' => '01',
        'nombre_departamento_ubi' => 'UCAYALI', 'nombre_provincia_ubi' => 'CORONEL PORTILLO', 'nombre_distrito_ubi' => 'CALLERIA', 'estado_ubi' => 'HABILITADO',
    ]);
    $proceso = Proceso::query()->create(['codigo_pro' => 'p_2027_1', 'nombre_pro' => '2027-I', 'fecha_inicio_pro' => '2026-10-01', 'fecha_fin_pro' => '2027-03-31', 'estado_pro' => 'HABILITADO']);
    $modalidad = ModalidadAdmision::query()->create(['codigo_mad' => 'PRUEBA', 'nombre_mad' => 'MODALIDAD DE PRUEBA', 'estado_mad' => 'HABILITADO']);

    $sede = fn (string $nombre): Sede => Sede::query()->create(['codigo_sed' => $nombre, 'nombre_sed' => $nombre, 'id_ubigeo' => $ubigeo->id_ubi, 'estado_sed' => 'HABILITADO']);
    $carrera = fn (string $nombre): Carrera => Carrera::query()->create(['codigo_car' => Str::upper(Str::random(6)), 'nombre_car' => $nombre, 'estado_car' => 'HABILITADO']);
    $turno = fn (string $nombre): Turno => Turno::query()->create(['codigo_tur' => $nombre, 'nombre_tur' => $nombre, 'estado_tur' => 'HABILITADO']);
    $oferta = fn (Sede $sede, Carrera $carrera, string $estado = 'HABILITADO') => ProcesoCarrera::query()->create(['id_proceso' => $proceso->id_pro, 'id_sede' => $sede->id_sed, 'id_carrera' => $carrera->id_car, 'estado_pca' => $estado]);
    $aula = function (Turno $turno, string $nombre, int $capacidad) use ($proceso): ProcesoAula {
        $aula = Aula::query()->create(['nombre_aul' => $nombre, 'piso_aul' => 'PISO 1', 'lugar_aul' => 'UNU', 'orden_aul' => 1, 'estado_aul' => 'HABILITADO']);

        return ProcesoAula::query()->create(['id_proceso' => $proceso->id_pro, 'id_turno' => $turno->id_tur, 'id_aula' => $aula->id_aul, 'capacidad_pau' => $capacidad, 'estado_pau' => 'HABILITADO']);
    };

    [$central, $norte] = [$sede('CENTRAL'), $sede('NORTE')];
    [$enfermeria, $sistemas, $derecho] = [$carrera('ENFERMERÍA'), $carrera('SISTEMAS'), $carrera('DERECHO')];
    [$manana, $tarde] = [$turno('MAÑANA'), $turno('TARDE')];
    $oferta($central, $enfermeria);
    $oferta($central, $sistemas);
    $oferta($norte, $sistemas);
    $oferta($central, $derecho, 'DESHABILITADO');
    [$aulaManana, $aulaTarde] = [$aula($manana, 'A-101', 40), $aula($tarde, 'B-201', 1)];

    $persona = Persona::query()->create([
        'id_tipo_documento' => TipoDocumento::query()->firstOrCreate(['codigo_tdo' => 'DNI'], ['nombre_tdo' => 'DNI', 'longitud_minima_tdo' => 8, 'longitud_maxima_tdo' => 8, 'estado_tdo' => 'HABILITADO'])->id_tdo,
        'numero_documento_per' => '74500001', 'solo_un_apellido_per' => false, 'apellido_paterno_per' => 'RIOS', 'apellido_materno_per' => 'VELA', 'nombres_per' => 'ANA',
        'id_estado_civil' => EstadoCivil::query()->firstOrCreate(['codigo_eci' => 'SOL'], ['nombre_eci' => 'SOLTERO(A)', 'estado_eci' => 'HABILITADO'])->id_eci,
        'id_pais_nacimiento' => Pais::query()->firstOrCreate(['codigo_pai' => 'PE'], ['nombre_pai' => 'PERU', 'estado_pai' => 'HABILITADO'])->id_pai,
        'id_nacionalidad' => Nacionalidad::query()->firstOrCreate(['codigo_nac' => 'PER'], ['nombre_nac' => 'PERUANA', 'estado_nac' => 'HABILITADO'])->id_nac,
        'id_ubigeo_nacimiento' => $ubigeo->id_ubi, 'id_ubigeo_domicilio' => $ubigeo->id_ubi, 'correo_electronico_per' => 'ana@mail.com', 'estado_per' => 'HABILITADO',
    ]);

    $inscripcion = Inscripcion::query()->create([
        'codigo_ins' => '2710001', 'id_persona' => $persona->id_per, 'id_proceso' => $proceso->id_pro, 'id_sede' => $central->id_sed,
        'id_modalidad_admision' => $modalidad->id_mad, 'id_carrera' => $enfermeria->id_car, 'id_turno' => $manana->id_tur,
        'id_proceso_aula' => $aulaManana->id_pau, 'fecha_inscripcion_ins' => '2026-11-10 08:00:00', 'estado_ins' => $estado,
    ]);

    $usuario = Usuario::factory()->create(['nombres_usu' => 'GESTOR CEPRE']);
    otorgarPermisosEdicion($usuario, ['inscripcion_indice', 'inscripcion_editar']);

    return compact('inscripcion', 'usuario', 'central', 'norte', 'enfermeria', 'sistemas', 'derecho', 'manana', 'tarde', 'aulaManana', 'aulaTarde', 'proceso', 'persona');
}

/**
 * @param  array<string, mixed>  $contexto
 * @return array{sede: int, carrera: int, turno: int, motivo: string}
 */
function cambioPostulacion(array $contexto, array $datos = []): array
{
    return [
        'sede' => $contexto['central']->id_sed,
        'carrera' => $contexto['enfermeria']->id_car,
        'turno' => $contexto['manana']->id_tur,
        'motivo' => 'Solicitud del postulante con documento presentado.',
        ...$datos,
    ];
}

test('users without inscripcion editar permiso cannot edit a postulacion', function () {
    $contexto = contextoEdicion();
    $lector = Usuario::factory()->create();
    otorgarPermisosEdicion($lector, ['inscripcion_indice']);
    $id = encode($contexto['inscripcion']->id_ins);

    $this->actingAs($lector)->get(route('inscripciones.editar', $id))->assertForbidden();
    $this->actingAs($lector)->put(route('inscripciones.postulacion', $id), cambioPostulacion($contexto, ['turno' => $contexto['tarde']->id_tur]))->assertForbidden();

    expect($contexto['inscripcion']->fresh()->id_turno)->toBe($contexto['manana']->id_tur);
});

test('the edit page offers the enabled careers by sede and the turnos with their free seats', function () {
    $contexto = contextoEdicion();

    $this->actingAs($contexto['usuario'])
        ->get(route('inscripciones.editar', encode($contexto['inscripcion']->id_ins)))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('inscripciones/editar')
            ->where('inscripcion.codigo', '2710001')
            ->where('inscripcion.turno', (string) $contexto['manana']->id_tur)
            ->where('sedes', [
                ['valor' => (string) $contexto['central']->id_sed, 'nombre' => 'CENTRAL', 'carreras' => [
                    ['valor' => (string) $contexto['enfermeria']->id_car, 'nombre' => 'ENFERMERÍA'],
                    ['valor' => (string) $contexto['sistemas']->id_car, 'nombre' => 'SISTEMAS'],
                ]],
                ['valor' => (string) $contexto['norte']->id_sed, 'nombre' => 'NORTE', 'carreras' => [
                    ['valor' => (string) $contexto['sistemas']->id_car, 'nombre' => 'SISTEMAS'],
                ]],
            ])
            ->where('turnos', [
                ['valor' => (string) $contexto['manana']->id_tur, 'nombre' => 'MAÑANA', 'libres' => 40],
                ['valor' => (string) $contexto['tarde']->id_tur, 'nombre' => 'TARDE', 'libres' => 1],
            ]));
});

test('an annulled inscripcion cannot be edited', function () {
    $contexto = contextoEdicion('ANULADO');
    $id = encode($contexto['inscripcion']->id_ins);

    $this->actingAs($contexto['usuario'])
        ->get(route('inscripciones.editar', $id))
        ->assertRedirect(route('inscripciones.detalle', $id))
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'warning', 'message' => 'Solo se pueden editar inscripciones activas o pendientes.']);

    $this->actingAs($contexto['usuario'])
        ->put(route('inscripciones.postulacion', $id), cambioPostulacion($contexto, ['turno' => $contexto['tarde']->id_tur]))
        ->assertSessionHasErrors(['cambios' => 'Solo se pueden editar inscripciones activas o pendientes.']);

    expect($contexto['inscripcion']->fresh()->id_turno)->toBe($contexto['manana']->id_tur);
});

test('the change requires a motivo of at least ten characters', function () {
    $contexto = contextoEdicion();
    $id = encode($contexto['inscripcion']->id_ins);

    $this->actingAs($contexto['usuario'])
        ->put(route('inscripciones.postulacion', $id), cambioPostulacion($contexto, ['turno' => $contexto['tarde']->id_tur, 'motivo' => '']))
        ->assertSessionHasErrors(['motivo' => 'El motivo del cambio es obligatorio.']);

    $this->actingAs($contexto['usuario'])
        ->put(route('inscripciones.postulacion', $id), cambioPostulacion($contexto, ['turno' => $contexto['tarde']->id_tur, 'motivo' => 'corto']))
        ->assertSessionHasErrors(['motivo' => 'El motivo debe tener al menos 10 caracteres.']);

    expect($contexto['inscripcion']->fresh()->id_turno)->toBe($contexto['manana']->id_tur)
        ->and($contexto['inscripcion']->cambios()->count())->toBe(0);
});

test('the career must be enabled in the chosen sede of the proceso', function (string $sede, string $carrera) {
    $contexto = contextoEdicion();

    $this->actingAs($contexto['usuario'])
        ->put(route('inscripciones.postulacion', encode($contexto['inscripcion']->id_ins)), cambioPostulacion($contexto, ['sede' => $contexto[$sede]->id_sed, 'carrera' => $contexto[$carrera]->id_car]))
        ->assertSessionHasErrors(['carrera' => 'La carrera no está habilitada en esa sede para el proceso.']);

    expect($contexto['inscripcion']->fresh()->id_carrera)->toBe($contexto['enfermeria']->id_car);
})->with([
    'carrera ofrecida solo en otra sede' => ['norte', 'enfermeria'],
    'carrera deshabilitada en la sede' => ['central', 'derecho'],
]);

test('saving without changes is rejected', function () {
    $contexto = contextoEdicion();

    $this->actingAs($contexto['usuario'])
        ->put(route('inscripciones.postulacion', encode($contexto['inscripcion']->id_ins)), cambioPostulacion($contexto))
        ->assertSessionHasErrors(['cambios' => 'No hay cambios: la sede, la carrera y el turno son los mismos.']);

    expect($contexto['inscripcion']->cambios()->count())->toBe(0);
});

test('changing the turno moves the student to a classroom of the new turno and records the change', function () {
    $contexto = contextoEdicion();
    $id = encode($contexto['inscripcion']->id_ins);

    $this->actingAs($contexto['usuario'])
        ->put(route('inscripciones.postulacion', $id), cambioPostulacion($contexto, ['turno' => $contexto['tarde']->id_tur]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('inscripciones.detalle', $id))
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'success', 'message' => 'Postulación de la inscripción 2710001 actualizada. Vuelva a imprimir la ficha y el carnet.']);

    $inscripcion = $contexto['inscripcion']->fresh();
    $cambio = $inscripcion->cambios()->sole();

    expect($inscripcion->id_turno)->toBe($contexto['tarde']->id_tur)
        ->and($inscripcion->id_proceso_aula)->toBe($contexto['aulaTarde']->id_pau)
        ->and($cambio->motivo_ica)->toBe('Solicitud del postulante con documento presentado.')
        ->and($cambio->id_usuario_creador)->toBe($contexto['usuario']->id_usu)
        ->and($cambio->cambios_ica)->toBe([
            ['campo' => 'Turno', 'antes' => 'MAÑANA', 'despues' => 'TARDE'],
            ['campo' => 'Aula', 'antes' => 'A-101', 'despues' => 'B-201'],
        ]);

    $this->actingAs($contexto['usuario'])
        ->get(route('inscripciones.detalle', $id))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('postulacion.turno', 'TARDE')
            ->where('cambios.0.usuario', 'GESTOR CEPRE')
            ->where('cambios.0.motivo', 'Solicitud del postulante con documento presentado.')
            ->where('cambios.0.cambios.0', ['campo' => 'Turno', 'antes' => 'MAÑANA', 'despues' => 'TARDE']));
});

test('changing the sede and career keeps the classroom when the turno stays the same', function () {
    $contexto = contextoEdicion();

    $this->actingAs($contexto['usuario'])
        ->put(route('inscripciones.postulacion', encode($contexto['inscripcion']->id_ins)), cambioPostulacion($contexto, ['sede' => $contexto['norte']->id_sed, 'carrera' => $contexto['sistemas']->id_car]))
        ->assertSessionHasNoErrors();

    $inscripcion = $contexto['inscripcion']->fresh();

    expect($inscripcion->id_sede)->toBe($contexto['norte']->id_sed)
        ->and($inscripcion->id_carrera)->toBe($contexto['sistemas']->id_car)
        ->and($inscripcion->id_proceso_aula)->toBe($contexto['aulaManana']->id_pau)
        ->and($inscripcion->cambios()->sole()->cambios_ica)->toBe([
            ['campo' => 'Sede', 'antes' => 'CENTRAL', 'despues' => 'NORTE'],
            ['campo' => 'Carrera', 'antes' => 'ENFERMERÍA', 'despues' => 'SISTEMAS'],
        ]);
});

test('a turno without free seats is rejected and nothing changes', function () {
    $contexto = contextoEdicion();
    Inscripcion::query()->create([
        'codigo_ins' => '2710002', 'id_persona' => $contexto['persona']->id_per, 'id_proceso' => $contexto['proceso']->id_pro, 'id_sede' => $contexto['central']->id_sed,
        'id_modalidad_admision' => $contexto['inscripcion']->id_modalidad_admision, 'id_carrera' => $contexto['sistemas']->id_car, 'id_turno' => $contexto['tarde']->id_tur,
        'id_proceso_aula' => $contexto['aulaTarde']->id_pau, 'fecha_inscripcion_ins' => '2026-11-11 08:00:00', 'estado_ins' => 'ACTIVO',
    ]);

    $this->actingAs($contexto['usuario'])
        ->put(route('inscripciones.postulacion', encode($contexto['inscripcion']->id_ins)), cambioPostulacion($contexto, ['turno' => $contexto['tarde']->id_tur]))
        ->assertSessionHasErrors(['cambios' => 'El turno elegido no tiene aulas con cupo libre. Amplíe la capacidad o elija otro turno.']);

    $inscripcion = $contexto['inscripcion']->fresh();

    expect($inscripcion->id_turno)->toBe($contexto['manana']->id_tur)
        ->and($inscripcion->id_proceso_aula)->toBe($contexto['aulaManana']->id_pau)
        ->and($inscripcion->cambios()->count())->toBe(0);
});
