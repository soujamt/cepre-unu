<?php

use App\Enums\EstadoImportacionBanco;
use App\Enums\EstadoImportacionBancoDetalle;
use App\Enums\EstadoObligacionPago;
use App\Enums\EstadoValidacionPago;
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
use App\Models\Persona;
use App\Models\Proceso;
use App\Models\ProcesoOferta;
use App\Models\ReglaPago;
use App\Models\Sede;
use App\Models\SedeCarrera;
use App\Models\Semestre;
use App\Models\TipoDocumento;
use App\Models\Turno;
use App\Models\Ubigeo;
use App\Models\Usuario;
use App\Support\InscripcionPagoManager;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function crearContextoInscripcionPago(array $opciones = []): array
{
    $numeroDocumento = $opciones['numero_documento'] ?? '7450332';

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
        'monto_total_rpa' => $opciones['monto_total_regla'] ?? 550.00,
        'permite_cuotas_rpa' => $opciones['permite_cuotas'] ?? true,
        'monto_minimo_primera_cuota_rpa' => $opciones['monto_minimo_primera_cuota'] ?? 275.00,
        'numero_maximo_cuotas_rpa' => $opciones['numero_maximo_cuotas'] ?? 3,
        'requiere_pago_completo_rpa' => $opciones['requiere_pago_completo'] ?? false,
        'vigente_desde_rpa' => '2026-03-01',
        'vigente_hasta_rpa' => '2026-06-30',
        'estado_rpa' => 'HABILITADO',
    ]);

    $persona = Persona::query()->create([
        'id_tipo_documento' => $tipoDocumento->id_tdo,
        'numero_documento_per' => $numeroDocumento,
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
        'codigo_ins' => 'INS-0001',
        'id_persona' => $persona->id_per,
        'id_proceso_oferta' => $procesoOferta->id_pof,
        'fecha_inscripcion_ins' => '2026-03-20 08:00:00',
        'estado_ins' => 'PENDIENTE',
    ]);

    $obligacion = new InscripcionObligacionPago([
        'id_inscripcion' => $inscripcion->id_ins,
        'id_regla_pago' => $reglaPago->id_rpa,
        'monto_total_iop' => $opciones['monto_total_obligacion'] ?? $reglaPago->monto_total_rpa,
        'numero_cuotas_iop' => $opciones['numero_cuotas_obligacion'] ?? $reglaPago->numero_maximo_cuotas_rpa,
        'estado_iop' => EstadoObligacionPago::Pendiente,
    ]);
    $obligacion->monto_pagado_iop = $opciones['monto_pagado_obligacion'] ?? 0;
    $obligacion->saldo_iop = $opciones['saldo_obligacion'] ?? $reglaPago->monto_total_rpa;
    $obligacion->save();

    $importacion = ImportacionBanco::query()->create([
        'nombre_archivo_iba' => 'REC10768.TXT',
        'hash_archivo_iba' => 'hash-prueba-1',
        'moneda_iba' => 'PEN',
        'fecha_importacion_iba' => '2026-03-20 08:00:00',
        'fecha_lote_iba' => '2026-03-19',
        'total_registros_iba' => 1,
        'total_registros_reportado_iba' => 1,
        'total_monto_iba' => $opciones['monto_detalle'] ?? 300.00,
        'total_monto_reportado_iba' => $opciones['monto_detalle'] ?? 300.00,
        'estado_iba' => EstadoImportacionBanco::Procesado,
    ]);

    $detalle = ImportacionBancoDetalle::query()->create([
        'id_importacion_banco' => $importacion->id_iba,
        'numero_documento_ibd' => $opciones['numero_documento_detalle'] ?? $numeroDocumento,
        'nombre_depositante_ibd' => 'LUCY BETTY FALCON ROJAS',
        'numero_operacion_raw_ibd' => $opciones['numero_operacion_raw'] ?? '817041676',
        'numero_operacion_visible_ibd' => $opciones['numero_operacion_visible'] ?? '41676',
        'numero_operacion_ibd' => $opciones['numero_operacion_raw'] ?? '817041676',
        'codigo_canal_ibd' => 'Y%G',
        'codigo_oficina_ibd' => '01068',
        'fecha_pago_ibd' => $opciones['fecha_pago'] ?? '2026-03-19',
        'monto_ibd' => $opciones['monto_detalle'] ?? 300.00,
        'texto_linea_ibd' => str_repeat('0', 152),
        'estado_ibd' => EstadoImportacionBancoDetalle::Pendiente,
    ]);

    return compact('obligacion', 'detalle', 'inscripcion', 'persona', 'reglaPago');
}

test('can search available imported payments by documento operacion y fecha', function () {
    $usuario = Usuario::factory()->create();
    $this->actingAs($usuario);

    $contexto = crearContextoInscripcionPago();

    ImportacionBancoDetalle::query()->create([
        'id_importacion_banco' => $contexto['detalle']->id_importacion_banco,
        'numero_documento_ibd' => $contexto['persona']->numero_documento_per,
        'nombre_depositante_ibd' => 'LUCY BETTY FALCON ROJAS',
        'numero_operacion_raw_ibd' => '817049999',
        'numero_operacion_visible_ibd' => '49999',
        'numero_operacion_ibd' => '817049999',
        'codigo_canal_ibd' => 'WNET',
        'codigo_oficina_ibd' => '0204',
        'fecha_pago_ibd' => '2026-03-19',
        'monto_ibd' => 300.00,
        'texto_linea_ibd' => str_repeat('1', 152),
        'estado_ibd' => EstadoImportacionBancoDetalle::Pendiente,
    ]);

    $resultados = app(InscripcionPagoManager::class)->buscarDetallesDisponibles(
        $contexto['persona']->numero_documento_per,
        '41676',
        '2026-03-19',
    );

    expect($resultados)->toHaveCount(1);
    expect($resultados->sole()->id_ibd)->toBe($contexto['detalle']->id_ibd);
});

test('can associate an imported payment detail to an obligation and update balances', function () {
    $usuario = Usuario::factory()->create();
    $this->actingAs($usuario);

    $contexto = crearContextoInscripcionPago([
        'monto_total_regla' => 550.00,
        'monto_detalle' => 300.00,
        'monto_minimo_primera_cuota' => 275.00,
        'permite_cuotas' => true,
        'requiere_pago_completo' => false,
    ]);

    $pago = app(InscripcionPagoManager::class)->asociarDetalleAObligacion(
        $contexto['obligacion'],
        $contexto['detalle'],
    );

    expect($pago->estado_validacion_ipa)->toBe(EstadoValidacionPago::Verificado);
    expect((float) $pago->monto_ipa)->toBe(300.00);
    expect($pago->numero_cuota_ipa)->toBe(1);
    expect($pago->numero_operacion_ipa)->toBe('41676');

    $contexto['detalle']->refresh();
    $contexto['obligacion']->refresh();

    expect($contexto['detalle']->estado_ibd)->toBe(EstadoImportacionBancoDetalle::Asociado);
    expect((float) $contexto['obligacion']->monto_pagado_iop)->toBe(300.00);
    expect((float) $contexto['obligacion']->saldo_iop)->toBe(250.00);
    expect($contexto['obligacion']->estado_iop)->toBe(EstadoObligacionPago::Parcial);

    $this->assertDatabaseHas('tbl_inscripcion_pago', [
        'id_ipa' => $pago->id_ipa,
        'id_inscripcion_obligacion_pago' => $contexto['obligacion']->id_iop,
        'id_importacion_banco_detalle' => $contexto['detalle']->id_ibd,
        'numero_cuota_ipa' => 1,
        'numero_operacion_ipa' => '41676',
        'estado_validacion_ipa' => 'VERIFICADO',
    ]);
});

test('cannot associate an imported detail that is already used', function () {
    $usuario = Usuario::factory()->create();
    $this->actingAs($usuario);

    $contexto = crearContextoInscripcionPago();
    $manager = app(InscripcionPagoManager::class);

    $manager->asociarDetalleAObligacion($contexto['obligacion'], $contexto['detalle']);

    expect(fn () => $manager->asociarDetalleAObligacion($contexto['obligacion']->fresh(), $contexto['detalle']->fresh()))
        ->toThrow(DomainException::class, 'El pago bancario seleccionado ya no se encuentra disponible.');
});

test('requires the minimum amount for the first installment', function () {
    $usuario = Usuario::factory()->create();
    $this->actingAs($usuario);

    $contexto = crearContextoInscripcionPago([
        'monto_total_regla' => 550.00,
        'monto_detalle' => 200.00,
        'monto_minimo_primera_cuota' => 275.00,
        'permite_cuotas' => true,
    ]);

    expect(fn () => app(InscripcionPagoManager::class)->asociarDetalleAObligacion($contexto['obligacion'], $contexto['detalle']))
        ->toThrow(DomainException::class, 'El pago no alcanza el monto minimo requerido para la primera cuota.');

    expect(InscripcionPago::query()->count())->toBe(0);
    expect($contexto['detalle']->fresh()->estado_ibd)->toBe(EstadoImportacionBancoDetalle::Pendiente);
});

test('requires full payment when the rule does not allow installments', function () {
    $usuario = Usuario::factory()->create();
    $this->actingAs($usuario);

    $contexto = crearContextoInscripcionPago([
        'monto_total_regla' => 500.00,
        'monto_detalle' => 300.00,
        'monto_minimo_primera_cuota' => 0,
        'permite_cuotas' => false,
        'numero_maximo_cuotas' => 1,
    ]);

    expect(fn () => app(InscripcionPagoManager::class)->asociarDetalleAObligacion($contexto['obligacion'], $contexto['detalle']))
        ->toThrow(DomainException::class, 'La regla de pago requiere cubrir el saldo completo en este registro.');

    expect(InscripcionPago::query()->count())->toBe(0);
});
