<?php

use App\Enums\EstadoImportacionBancoDetalle;
use App\Models\Accion;
use App\Models\Aula;
use App\Models\Carrera;
use App\Models\ConceptoPago;
use App\Models\EstadoCivil;
use App\Models\ImportacionBancoDetalle;
use App\Models\Inscripcion;
use App\Models\InscripcionObligacionPago;
use App\Models\InscripcionPago;
use App\Models\ModalidadAdmision;
use App\Models\Nacionalidad;
use App\Models\Pais;
use App\Models\Parametro;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use App\Models\Persona;
use App\Models\Proceso;
use App\Models\ProcesoAula;
use App\Models\ReglaPago;
use App\Models\Rol;
use App\Models\Sede;
use App\Models\TipoDocumento;
use App\Models\Turno;
use App\Models\Ubigeo;
use App\Models\Usuario;
use App\Support\Drive\SincronizadorDrive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Support\SessionKey;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

const URL_DRIVE = 'https://script.google.com/macros/s/prueba/exec';

beforeEach(function () {
    config(['services.drive.url' => URL_DRIVE, 'services.drive.token' => 'clave-secreta']);
});

function otorgarPermisosDrive(Usuario $usuario, array $claves): void
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

function pagoDrive(string $operacion, string $fecha, string $estado = 'PENDIENTE'): ImportacionBancoDetalle
{
    return ImportacionBancoDetalle::query()->create([
        'origen_ibd' => 'MANUAL', 'numero_documento_ibd' => '04500001', 'referencia_ibd' => 'RIOS VELA ANA',
        'numero_operacion_raw_ibd' => $operacion, 'numero_operacion_visible_ibd' => $operacion, 'numero_operacion_ibd' => $operacion,
        'fecha_pago_ibd' => $fecha, 'monto_ibd' => 275, 'ubicacion_ibd' => 'RECAUDACIÓN BBVA', 'estado_ibd' => $estado,
    ]);
}

/**
 * Proceso 2027-II (el anterior terminó el 31/08/2026) con una inscripción activa en el
 * aula A-101 del turno MAÑANA, otra anulada y cuatro pagos: el de la inscripción (anterior
 * al proceso), uno libre del periodo, uno libre antiguo y uno asociado a otro proceso.
 *
 * @return array{proceso: Proceso, inscripcion: Inscripcion}
 */
function contextoDrive(): array
{
    Proceso::query()->create(['codigo_pro' => 'p_2027_1', 'nombre_pro' => '2027-I', 'fecha_inicio_pro' => '2026-05-01', 'fecha_fin_pro' => '2026-08-31 23:59:00', 'estado_pro' => 'HABILITADO']);
    $proceso = Proceso::query()->create(['codigo_pro' => 'p_2027_2', 'nombre_pro' => '2027-II', 'fecha_inicio_pro' => '2026-10-01', 'fecha_fin_pro' => '2027-03-31', 'estado_pro' => 'HABILITADO']);

    $ubigeo = Ubigeo::query()->firstOrCreate(['id_ubi' => '250101'], [
        'codigo_departamento_ubi' => '25', 'codigo_provincia_ubi' => '01', 'codigo_distrito_ubi' => '01',
        'nombre_departamento_ubi' => 'UCAYALI', 'nombre_provincia_ubi' => 'CORONEL PORTILLO', 'nombre_distrito_ubi' => 'CALLERIA', 'estado_ubi' => 'HABILITADO',
    ]);
    $sede = Sede::query()->create(['codigo_sed' => 'CENTRAL', 'nombre_sed' => 'CENTRAL', 'id_ubigeo' => $ubigeo->id_ubi, 'estado_sed' => 'HABILITADO']);
    $carrera = Carrera::query()->create(['codigo_car' => 'SIS', 'nombre_car' => 'SISTEMAS', 'estado_car' => 'HABILITADO']);
    $modalidad = ModalidadAdmision::query()->create(['codigo_mad' => 'PRUEBA', 'nombre_mad' => 'MODALIDAD DE PRUEBA', 'estado_mad' => 'HABILITADO']);
    $turno = Turno::query()->create(['codigo_tur' => 'MAN', 'nombre_tur' => 'MAÑANA PRUEBA', 'estado_tur' => 'HABILITADO']);
    $aula = Aula::query()->create(['nombre_aul' => 'A-101', 'piso_aul' => 'PISO 1', 'lugar_aul' => 'UNU', 'orden_aul' => 1, 'estado_aul' => 'HABILITADO']);
    $procesoAula = ProcesoAula::query()->create(['id_proceso' => $proceso->id_pro, 'id_turno' => $turno->id_tur, 'id_aula' => $aula->id_aul, 'capacidad_pau' => 40, 'estado_pau' => 'HABILITADO']);

    $persona = Persona::query()->create([
        'id_tipo_documento' => TipoDocumento::query()->firstOrCreate(['codigo_tdo' => 'DNI'], ['nombre_tdo' => 'DNI', 'longitud_minima_tdo' => 8, 'longitud_maxima_tdo' => 8, 'estado_tdo' => 'HABILITADO'])->id_tdo,
        'numero_documento_per' => '04500001', 'solo_un_apellido_per' => false, 'apellido_paterno_per' => 'RIOS', 'apellido_materno_per' => 'VELA', 'nombres_per' => 'ANA',
        'id_estado_civil' => EstadoCivil::query()->firstOrCreate(['codigo_eci' => 'SOL'], ['nombre_eci' => 'SOLTERO(A)', 'estado_eci' => 'HABILITADO'])->id_eci,
        'id_pais_nacimiento' => Pais::query()->firstOrCreate(['codigo_pai' => 'PE'], ['nombre_pai' => 'PERU', 'estado_pai' => 'HABILITADO'])->id_pai,
        'id_nacionalidad' => Nacionalidad::query()->firstOrCreate(['codigo_nac' => 'PER'], ['nombre_nac' => 'PERUANA', 'estado_nac' => 'HABILITADO'])->id_nac,
        'id_ubigeo_nacimiento' => $ubigeo->id_ubi, 'id_ubigeo_domicilio' => $ubigeo->id_ubi, 'correo_electronico_per' => 'ana@mail.com', 'estado_per' => 'HABILITADO',
    ]);

    $base = ['id_persona' => $persona->id_per, 'id_proceso' => $proceso->id_pro, 'id_sede' => $sede->id_sed, 'id_modalidad_admision' => $modalidad->id_mad, 'id_carrera' => $carrera->id_car, 'id_turno' => $turno->id_tur];
    $inscripcion = Inscripcion::query()->create([...$base, 'codigo_ins' => '2720001', 'id_proceso_aula' => $procesoAula->id_pau, 'fecha_inscripcion_ins' => '2026-10-05 09:15:30', 'estado_ins' => 'ACTIVO']);
    Inscripcion::query()->create([...$base, 'codigo_ins' => '2720002', 'fecha_inscripcion_ins' => '2026-10-06 10:00:00', 'estado_ins' => 'ANULADO']);

    $concepto = ConceptoPago::query()->create(['codigo_cpg' => 'PRUEBA', 'nombre_cpg' => 'CONCEPTO DE PRUEBA', 'permite_cuotas_cpg' => true, 'estado_cpg' => 'HABILITADO']);
    $regla = ReglaPago::query()->create([
        'id_concepto_pago' => $concepto->id_cpg, 'id_proceso' => $proceso->id_pro, 'monto_total_rpa' => 550, 'permite_cuotas_rpa' => true,
        'monto_minimo_primera_cuota_rpa' => 275, 'numero_maximo_cuotas_rpa' => 2, 'requiere_pago_completo_rpa' => false,
        'vigente_desde_rpa' => '2026-01-01', 'vigente_hasta_rpa' => '2027-12-31', 'estado_rpa' => 'HABILITADO',
    ]);
    $obligacion = (new InscripcionObligacionPago)->forceFill(['id_inscripcion' => $inscripcion->id_ins, 'id_regla_pago' => $regla->id_rpa, 'monto_total_iop' => 550, 'monto_pagado_iop' => 275, 'saldo_iop' => 275, 'numero_cuotas_iop' => 2, 'estado_iop' => 'PARCIAL']);
    $obligacion->save();
    $usado = pagoDrive('40001', '2026-08-15', EstadoImportacionBancoDetalle::Asociado->value);
    InscripcionPago::query()->create(['id_inscripcion_obligacion_pago' => $obligacion->id_iop, 'id_importacion_banco_detalle' => $usado->id_ibd, 'numero_cuota_ipa' => 1, 'monto_ipa' => 275, 'fecha_pago_ipa' => '2026-08-15', 'numero_operacion_ipa' => '40001']);

    pagoDrive('40002', '2026-10-02');
    pagoDrive('40003', '2026-07-01');
    pagoDrive('40004', '2026-10-03', EstadoImportacionBancoDetalle::Asociado->value);

    return compact('proceso', 'inscripcion');
}

/**
 * @return array<string, mixed>
 */
function enviadoAlDrive(): array
{
    $enviados = Http::recorded();
    expect($enviados)->toHaveCount(1);

    return $enviados[0][0]->data();
}

function respuestaDriveCorrecta(): void
{
    Http::fake([URL_DRIVE => Http::response(['ok' => true, 'hojas' => []])]);
}

test('the sync sends the DATA and PAGOS sheets of the proceso with the drive structure', function () {
    $contexto = contextoDrive();
    respuestaDriveCorrecta();

    $this->artisan('cepre:sincronizar-drive', ['--proceso' => $contexto['proceso']->id_pro])
        ->expectsOutput('DATA CEPRE-UNU 2027-II: 1 filas.')
        ->expectsOutput('PAGOS 2027-II: 2 filas.')
        ->assertSuccessful();

    Http::assertSent(fn (Request $request): bool => $request->url() === URL_DRIVE && $request['token'] === 'clave-secreta' && $request['proceso'] === '2027-II');

    [$data, $pagos] = enviadoAlDrive()['hojas'];
    $encabezados = $data['encabezados'];
    $fila = array_combine($encabezados, $data['filas'][0]);

    expect($data['archivo'])->toBe('DATA CEPRE-UNU 2027-II')
        ->and($data['pestana'])->toBe('DATA')
        ->and(array_slice($encabezados, 0, 3))->toBe(['NRO_REG', 'FECHA_Y_HORA', 'CODIGO_TIPO_DOCUMENTO'])
        ->and(array_slice($encabezados, -3))->toBe(['AULA', 'TURNO', 'ESTADO'])
        ->and($data['formatos'])->each->toBe('@')
        ->and($data['filas'])->toHaveCount(1)
        ->and($fila['NRO_REG'])->toBe('2720001')
        ->and($fila['FECHA_Y_HORA'])->toBe('05/10/2026 09:15:30')
        ->and($fila['NUMERO_DOCUMENTO'])->toBe('04500001')
        ->and($fila['APELLIDO_CASADA'])->toBe('')
        ->and($fila['AULA'])->toBe('A-101')
        ->and($fila['TURNO'])->toBe('MAÑANA PRUEBA')
        ->and($fila['ESTADO'])->toBe('ACTIVO')
        ->and($pagos['archivo'])->toBe('PAGOS 2027-II')
        ->and($pagos['pestana'])->toBe('PAGOS')
        ->and($pagos['encabezados'])->toBe(['NRO.', 'FECHA', 'DNI', 'REFERENCIA', 'IMPORTE', 'N° OPERACIÓN', 'UBICACIÓN', 'ESTADO', 'N° REG.', 'SEDE', 'OBSERVACIÓN'])
        ->and($pagos['formatos'][0])->toBe('0')
        ->and($pagos['formatos'][4])->toBe('0.00')
        ->and(array_column($pagos['filas'], 5))->toBe(['40001', '40002'])
        ->and($pagos['filas'][0][8])->toBe('2720001')
        ->and(Parametro::valor(SincronizadorDrive::PARAMETRO_ULTIMA))->not->toBeNull();
});

test('the sync is not attempted when the drive is not configured', function () {
    config(['services.drive.url' => null]);
    Http::fake();

    $this->artisan('cepre:sincronizar-drive')
        ->expectsOutput('La sincronización con el Drive no está configurada. Complete DRIVE_SYNC_URL y DRIVE_SYNC_TOKEN en el archivo .env.')
        ->assertFailed();

    Http::assertNothingSent();
});

test('the sync reports why the drive did not accept the data', function (Closure $respuesta, string $mensaje) {
    $contexto = contextoDrive();
    Http::fake([URL_DRIVE => $respuesta]);

    $this->artisan('cepre:sincronizar-drive', ['--proceso' => $contexto['proceso']->id_pro])
        ->expectsOutput($mensaje)
        ->assertFailed();

    expect(Parametro::valor(SincronizadorDrive::PARAMETRO_ULTIMA))->toBeNull();
})->with([
    'clave incorrecta' => [fn () => Http::response(['ok' => false, 'error' => 'clave incorrecta (revise DRIVE_SYNC_TOKEN)']), 'El Drive rechazó la sincronización: clave incorrecta (revise DRIVE_SYNC_TOKEN).'],
    'script sin acceso público' => [fn () => Http::response('<html>Iniciar sesión</html>', 200), 'El Drive no respondió como se esperaba. Revise que el script esté publicado como aplicación web con acceso para "Cualquier usuario".'],
    'sin internet' => [fn () => throw new ConnectionException('sin red'), 'No se pudo conectar con el Drive. Revise la conexión a internet de esta PC.'],
]);

test('users without the export permiso cannot sync the drive', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosDrive($usuario, ['inscripcion_indice']);
    Http::fake();

    $this->actingAs($usuario)->post(route('inscripciones.drive'))->assertForbidden();

    Http::assertNothingSent();
});

test('the drive button syncs the filtered proceso and reports the result', function () {
    $contexto = contextoDrive();
    $usuario = Usuario::factory()->create();
    otorgarPermisosDrive($usuario, ['inscripcion_indice', 'inscripcion_exportar_excel']);
    respuestaDriveCorrecta();

    $this->actingAs($usuario)
        ->get(route('inscripciones.index', ['proceso' => $contexto['proceso']->id_pro]))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('drive', ['configurado' => true, 'ultima' => null]));

    $this->actingAs($usuario)
        ->from(route('inscripciones.index'))
        ->post(route('inscripciones.drive'), ['proceso' => (string) $contexto['proceso']->id_pro])
        ->assertRedirect(route('inscripciones.index'))
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'success', 'message' => 'Drive actualizado: DATA CEPRE-UNU 2027-II (1 filas) y PAGOS 2027-II (2 filas).']);

    expect(enviadoAlDrive()['proceso'])->toBe('2027-II');
});
