<?php

use App\Enums\EstadoImportacionBanco;
use App\Enums\EstadoImportacionBancoDetalle;
use App\Enums\EstadoObligacionPago;
use App\Models\Accion;
use App\Models\Carrera;
use App\Models\ConceptoPago;
use App\Models\EstadoCivil;
use App\Models\ImportacionBanco;
use App\Models\ImportacionBancoDetalle;
use App\Models\Inscripcion;
use App\Models\InscripcionObligacionPago;
use App\Models\InscripcionPago;
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
use App\Models\Turno;
use App\Models\Ubigeo;
use App\Models\Usuario;
use App\Support\InscripcionPagoManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function otorgarPermisosPagoValidar(Usuario $usuario, array $claves): void
{
    $rol = Rol::query()->create([
        'nombre_rol' => 'ROL '.Str::upper(Str::random(10)),
        'descripcion_rol' => 'Rol de pruebas',
        'estado_rol' => 'HABILITADO',
    ]);

    $usuario->roles()->attach($rol->id_rol);

    foreach ($claves as $clave) {
        $permisoClave = Str::beforeLast($clave, '_');
        $accionClave = Str::afterLast($clave, '_');

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

function crearContextoPagoValidar(array $opciones = []): array
{
    $tipoDocumento = TipoDocumento::query()->create([
        'codigo_tdo' => 'DNI',
        'nombre_tdo' => 'DNI',
        'longitud_minima_tdo' => 8,
        'longitud_maxima_tdo' => 8,
        'estado_tdo' => 'HABILITADO',
    ]);

    $estadoCivil = EstadoCivil::query()->create([
        'codigo_eci' => 'SOL',
        'nombre_eci' => 'SOLTERO(A)',
        'estado_eci' => 'HABILITADO',
    ]);

    $pais = Pais::query()->create([
        'codigo_pai' => 'PE',
        'nombre_pai' => 'PERU',
        'estado_pai' => 'HABILITADO',
    ]);

    $nacionalidad = Nacionalidad::query()->create([
        'codigo_nac' => 'PER',
        'nombre_nac' => 'PERUANA',
        'estado_nac' => 'HABILITADO',
    ]);

    $ubigeo = Ubigeo::query()->create([
        'id_ubi' => '250101',
        'codigo_departamento_ubi' => '25',
        'codigo_provincia_ubi' => '01',
        'codigo_distrito_ubi' => '01',
        'nombre_departamento_ubi' => 'UCAYALI',
        'nombre_provincia_ubi' => 'CORONEL PORTILLO',
        'nombre_distrito_ubi' => 'CALLERIA',
        'estado_ubi' => 'HABILITADO',
    ]);

    $semestre = Semestre::query()->create([
        'codigo_sem' => 'semestre_2026_i',
        'nombre_sem' => 'SEMESTRE 2026-I',
        'fecha_inicio_inscripcion_sem' => '2026-03-01',
        'fecha_fin_inscripcion_sem' => '2026-06-30',
        'estado_sem' => 'HABILITADO',
    ]);

    $proceso = Proceso::query()->create([
        'id_semestre' => $semestre->id_sem,
        'codigo_pro' => 'proceso_ordinario_2026_i',
        'nombre_pro' => 'PROCESO ORDINARIO 2026-I',
        'fecha_inicio_pro' => '2026-03-01',
        'fecha_fin_pro' => '2026-06-30',
        'estado_pro' => 'HABILITADO',
    ]);

    $modalidad = ModalidadAdmision::query()->create([
        'codigo_mad' => 'RES',
        'nombre_mad' => 'RESERVA - CEPREUNU',
        'estado_mad' => 'HABILITADO',
    ]);

    $sede = Sede::query()->create([
        'codigo_sed' => 'SEDCAL',
        'nombre_sed' => 'SEDE CALLERIA',
        'id_ubigeo' => $ubigeo->id_ubi,
        'estado_sed' => 'HABILITADO',
    ]);

    $lugarInscripcion = LugarInscripcion::query()->create([
        'codigo_lin' => '603',
        'nombre_lin' => 'PUCALLPA | SEDE CALLERIA',
        'id_sede' => $sede->id_sed,
        'estado_lin' => 'HABILITADO',
    ]);

    $carrera = Carrera::query()->create([
        'codigo_car' => 'MED',
        'nombre_car' => 'MEDICINA',
        'estado_car' => 'HABILITADO',
    ]);

    $sedeCarrera = SedeCarrera::query()->create([
        'id_sede' => $sede->id_sed,
        'id_carrera' => $carrera->id_car,
        'codigo_externo_sca' => 'SC001',
        'estado_sca' => 'HABILITADO',
    ]);

    $modalidadLugar = ModalidadLugarInscripcion::query()->create([
        'id_modalidad_admision' => $modalidad->id_mad,
        'id_lugar_inscripcion' => $lugarInscripcion->id_lin,
        'estado_mli' => 'HABILITADO',
    ]);

    $ofertaBase = OfertaBase::query()->create([
        'codigo_externo_ofb' => '2700',
        'id_modalidad_lugar_inscripcion' => $modalidadLugar->id_mli,
        'id_sede_carrera' => $sedeCarrera->id_sca,
        'estado_ofb' => 'HABILITADO',
    ]);

    $turno = Turno::query()->create([
        'codigo_tur' => 'MAN',
        'nombre_tur' => 'MANANA',
        'estado_tur' => 'HABILITADO',
    ]);

    $procesoOferta = ProcesoOferta::query()->create([
        'id_proceso' => $proceso->id_pro,
        'id_oferta_base' => $ofertaBase->id_ofb,
        'id_turno' => $turno->id_tur,
        'estado_pof' => 'HABILITADO',
    ]);

    $conceptoPago = ConceptoPago::query()->create([
        'codigo_cpg' => 'INS',
        'nombre_cpg' => 'INSCRIPCION',
        'permite_cuotas_cpg' => true,
        'estado_cpg' => 'HABILITADO',
    ]);

    $reglaPago = ReglaPago::query()->create([
        'id_concepto_pago' => $conceptoPago->id_cpg,
        'id_proceso' => $proceso->id_pro,
        'id_modalidad_admision' => $modalidad->id_mad,
        'monto_total_rpa' => 550.00,
        'permite_cuotas_rpa' => true,
        'monto_minimo_primera_cuota_rpa' => 275.00,
        'numero_maximo_cuotas_rpa' => 3,
        'requiere_pago_completo_rpa' => false,
        'vigente_desde_rpa' => '2026-03-01',
        'vigente_hasta_rpa' => '2026-06-30',
        'estado_rpa' => 'HABILITADO',
    ]);

    $persona = Persona::query()->create([
        'id_tipo_documento' => $tipoDocumento->id_tdo,
        'numero_documento_per' => $opciones['numero_documento'] ?? '7450332',
        'solo_un_apellido_per' => false,
        'apellido_paterno_per' => 'FALCON',
        'apellido_materno_per' => 'ROJAS',
        'nombres_per' => 'LUCY BETTY',
        'id_estado_civil' => $estadoCivil->id_eci,
        'id_pais_nacimiento' => $pais->id_pai,
        'id_nacionalidad' => $nacionalidad->id_nac,
        'id_ubigeo_nacimiento' => $ubigeo->id_ubi,
        'id_ubigeo_domicilio' => $ubigeo->id_ubi,
        'estado_per' => 'HABILITADO',
    ]);

    $inscripcion = Inscripcion::query()->create([
        'codigo_ins' => $opciones['codigo_ins'] ?? 'INS-0001',
        'id_persona' => $persona->id_per,
        'id_proceso_oferta' => $procesoOferta->id_pof,
        'fecha_inscripcion_ins' => '2026-03-20 08:00:00',
        'estado_ins' => 'PENDIENTE',
    ]);

    $obligacion = new InscripcionObligacionPago([
        'id_inscripcion' => $inscripcion->id_ins,
        'id_regla_pago' => $reglaPago->id_rpa,
        'monto_total_iop' => 550.00,
        'numero_cuotas_iop' => 3,
        'estado_iop' => 'PENDIENTE',
    ]);
    $obligacion->monto_pagado_iop = 0;
    $obligacion->saldo_iop = 550.00;
    $obligacion->save();

    $importacion = ImportacionBanco::query()->create([
        'nombre_archivo_iba' => 'REC10768.TXT',
        'hash_archivo_iba' => 'hash-validar-1',
        'moneda_iba' => 'PEN',
        'fecha_importacion_iba' => '2026-03-20 08:00:00',
        'fecha_lote_iba' => '2026-03-19',
        'total_registros_iba' => 1,
        'total_registros_reportado_iba' => 1,
        'total_monto_iba' => 300.00,
        'total_monto_reportado_iba' => 300.00,
        'estado_iba' => EstadoImportacionBanco::Procesado,
    ]);

    $detalle = ImportacionBancoDetalle::query()->create([
        'id_importacion_banco' => $importacion->id_iba,
        'numero_documento_ibd' => $persona->numero_documento_per,
        'nombre_depositante_ibd' => 'LUCY BETTY FALCON ROJAS',
        'numero_operacion_raw_ibd' => '817041676',
        'numero_operacion_visible_ibd' => '41676',
        'numero_operacion_ibd' => '817041676',
        'codigo_canal_ibd' => 'Y%G',
        'codigo_oficina_ibd' => '01068',
        'fecha_pago_ibd' => '2026-03-19',
        'monto_ibd' => 300.00,
        'texto_linea_ibd' => str_repeat('0', 152),
        'estado_ibd' => EstadoImportacionBancoDetalle::Pendiente,
    ]);

    return compact('obligacion', 'detalle', 'inscripcion', 'persona');
}

test('authorized users can access validar pagos page', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosPagoValidar($usuario, ['pago_crear']);

    $this->actingAs($usuario)
        ->get(route('pagos.validar'))
        ->assertOk()
        ->assertSee('VALIDAR PAGOS');
});

test('users can search pending obligations and find imported payments', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosPagoValidar($usuario, ['pago_crear']);

    $contexto = crearContextoPagoValidar();

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.pagos-validar')
        ->set('buscarObligacion', $contexto['persona']->numero_documento_per)
        ->assertSee($contexto['inscripcion']->codigo_ins)
        ->call('seleccionarObligacion', $contexto['obligacion']->id_iop)
        ->assertSet('dniBusqueda', $contexto['persona']->numero_documento_per)
        ->set('numeroOperacionBusqueda', '41676')
        ->set('fechaPagoBusqueda', '2026-03-19')
        ->call('buscarPagos')
        ->assertSee('41676')
        ->assertSee('LUCY BETTY FALCON ROJAS');
});

test('users can validate an imported payment from the validation page', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosPagoValidar($usuario, ['pago_crear']);

    $contexto = crearContextoPagoValidar();

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.pagos-validar')
        ->call('seleccionarObligacion', $contexto['obligacion']->id_iop)
        ->set('numeroOperacionBusqueda', '41676')
        ->set('fechaPagoBusqueda', '2026-03-19')
        ->call('buscarPagos')
        ->call('seleccionarDetalle', $contexto['detalle']->id_ibd)
        ->call('confirmarPago')
        ->assertDispatched('toast', message: 'Pago validado y asociado correctamente.', type: 'success');

    $this->assertDatabaseHas('tbl_inscripcion_pago', [
        'id_inscripcion_obligacion_pago' => $contexto['obligacion']->id_iop,
        'id_importacion_banco_detalle' => $contexto['detalle']->id_ibd,
        'numero_operacion_ipa' => '41676',
        'estado_validacion_ipa' => 'VERIFICADO',
    ]);

    $this->assertDatabaseHas('tbl_importacion_banco_detalle', [
        'id_ibd' => $contexto['detalle']->id_ibd,
        'estado_ibd' => 'ASOCIADO',
    ]);

    $this->assertDatabaseHas('tbl_inscripcion_obligacion_pago', [
        'id_iop' => $contexto['obligacion']->id_iop,
        'monto_pagado_iop' => 300.00,
        'saldo_iop' => 250.00,
        'estado_iop' => 'PARCIAL',
    ]);
});

test('users can validate pending payments from a txt import by documento and pending amount', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosPagoValidar($usuario, ['pago_indice', 'pago_crear']);

    $contexto = crearContextoPagoValidar();

    $detallePrevio = ImportacionBancoDetalle::query()->create([
        'id_importacion_banco' => $contexto['detalle']->id_importacion_banco,
        'numero_documento_ibd' => $contexto['persona']->numero_documento_per,
        'nombre_depositante_ibd' => 'LUCY BETTY FALCON ROJAS',
        'numero_operacion_raw_ibd' => '817041600',
        'numero_operacion_visible_ibd' => '41600',
        'numero_operacion_ibd' => '817041600',
        'codigo_canal_ibd' => 'Y%G',
        'codigo_oficina_ibd' => '01068',
        'fecha_pago_ibd' => '2026-03-18',
        'monto_ibd' => 250.00,
        'texto_linea_ibd' => str_repeat('0', 152),
        'estado_ibd' => EstadoImportacionBancoDetalle::Asociado,
    ]);

    InscripcionPago::query()->create([
        'id_inscripcion_obligacion_pago' => $contexto['obligacion']->id_iop,
        'id_importacion_banco_detalle' => $detallePrevio->id_ibd,
        'numero_cuota_ipa' => 1,
        'monto_ipa' => 250.00,
        'fecha_pago_ipa' => '2026-03-18',
        'numero_operacion_ipa' => '41600',
        'estado_validacion_ipa' => 'VERIFICADO',
        'validado_en' => now(),
        'id_usuario_validador' => $usuario->id_usu,
    ]);

    $contexto['obligacion']->forceFill([
        'monto_pagado_iop' => 250.00,
        'saldo_iop' => 300.00,
        'estado_iop' => EstadoObligacionPago::Parcial,
    ])->save();

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.pagos')
        ->call('validarPagosPendientes', $contexto['detalle']->id_importacion_banco)
        ->assertDispatched('toast', message: '1 pago(s) pendiente(s) asociado(s). 0 quedan por revisar.', type: 'success');

    $this->assertDatabaseHas('tbl_inscripcion_pago', [
        'id_inscripcion_obligacion_pago' => $contexto['obligacion']->id_iop,
        'id_importacion_banco_detalle' => $contexto['detalle']->id_ibd,
        'monto_ipa' => 300.00,
        'estado_validacion_ipa' => 'VERIFICADO',
    ]);

    $this->assertDatabaseHas('tbl_importacion_banco_detalle', [
        'id_ibd' => $contexto['detalle']->id_ibd,
        'estado_ibd' => 'ASOCIADO',
    ]);

    $this->assertDatabaseHas('tbl_inscripcion_obligacion_pago', [
        'id_iop' => $contexto['obligacion']->id_iop,
        'monto_pagado_iop' => 550.00,
        'saldo_iop' => 0.00,
        'estado_iop' => 'PAGADO',
    ]);

    Livewire::test('pages::inscripciones.pagos')
        ->call('validarPagosPendientes', $contexto['detalle']->id_importacion_banco)
        ->assertDispatched('toast', message: 'No se asociaron pagos. Revise que existan pendientes con DNI y saldo coincidente.', type: 'warning');

    expect($contexto['obligacion']->pagos()->count())->toBe(2);
});

test('pending txt payments match inscriptions when the bank document has leading zeros', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosPagoValidar($usuario, ['pago_indice', 'pago_crear']);

    $contexto = crearContextoPagoValidar([
        'numero_documento' => '122285',
    ]);

    $contexto['detalle']->forceFill([
        'numero_documento_ibd' => '00122285',
        'monto_ibd' => 550.00,
    ])->save();

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.pagos')
        ->call('validarPagosPendientes', $contexto['detalle']->id_importacion_banco)
        ->assertDispatched('toast', message: '1 pago(s) pendiente(s) asociado(s). 0 quedan por revisar.', type: 'success');

    $this->assertDatabaseHas('tbl_inscripcion_pago', [
        'id_inscripcion_obligacion_pago' => $contexto['obligacion']->id_iop,
        'id_importacion_banco_detalle' => $contexto['detalle']->id_ibd,
        'monto_ipa' => 550.00,
        'estado_validacion_ipa' => 'VERIFICADO',
    ]);

    $this->assertDatabaseHas('tbl_importacion_banco_detalle', [
        'id_ibd' => $contexto['detalle']->id_ibd,
        'estado_ibd' => 'ASOCIADO',
    ]);
});

test('searching imported payments returns no results when the operation does not match', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosPagoValidar($usuario, ['pago_crear']);

    $contexto = crearContextoPagoValidar();

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.pagos-validar')
        ->call('seleccionarObligacion', $contexto['obligacion']->id_iop)
        ->set('numeroOperacionBusqueda', '99999')
        ->set('fechaPagoBusqueda', '2026-03-19')
        ->call('buscarPagos')
        ->assertDontSee('41676')
        ->assertDontSee('LUCY BETTY FALCON ROJAS');
});

test('users cannot validate an imported payment that was already associated', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosPagoValidar($usuario, ['pago_crear']);

    $contexto = crearContextoPagoValidar();
    $manager = app(InscripcionPagoManager::class);

    $manager->asociarDetalleAObligacion($contexto['obligacion'], $contexto['detalle']);

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.pagos-validar')
        ->call('seleccionarObligacion', $contexto['obligacion']->fresh()->id_iop)
        ->set('idDetalleSeleccionado', $contexto['detalle']->id_ibd)
        ->call('confirmarPago')
        ->assertDispatched('toast', message: 'El pago bancario seleccionado ya no se encuentra disponible.', type: 'error');
});
