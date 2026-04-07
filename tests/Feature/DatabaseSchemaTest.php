<?php

use App\Models\Usuario;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('uses tbl_usuario as the authenticatable table', function (): void {
    expect((new Usuario)->getTable())->toBe('tbl_usuario');

    expect(Schema::hasTable('tbl_usuario'))->toBeTrue();
    expect(Schema::hasColumns('tbl_usuario', [
        'id_usu',
        'usuario_usu',
        'clave_usu',
        'correo_usu',
        'nombres_usu',
    ]))->toBeTrue();
});

it('stores the additional persona fields required by the legend', function (): void {
    expect(Schema::hasColumns('tbl_persona', [
        'solo_un_apellido_per',
        'apellido_casada_per',
        'tipo_lengua_materna_per',
    ]))->toBeTrue();
});

it('stores the external offer code and allows repeated place names with different codes', function (): void {
    expect(Schema::hasColumn('tbl_oferta_base', 'codigo_externo_ofb'))->toBeTrue();
    expect(Schema::hasColumn('tbl_oferta_base', 'id_modalidad_lugar_inscripcion'))->toBeTrue();
    expect(Schema::hasTable('tbl_modalidad_lugar_inscripcion'))->toBeTrue();

    $ubigeoId = fake()->unique()->numerify('######');

    DB::table('tbl_ubigeo')->insert([
        'id_ubi' => $ubigeoId,
        'codigo_departamento_ubi' => '25',
        'codigo_provincia_ubi' => '01',
        'codigo_distrito_ubi' => substr($ubigeoId, 4, 2),
        'nombre_departamento_ubi' => 'Ucayali',
        'nombre_provincia_ubi' => 'Coronel Portillo',
        'nombre_distrito_ubi' => fake()->city(),
        'estado_ubi' => 'HABILITADO',
    ]);

    $sedeId = (int) DB::table('tbl_sede')->insertGetId([
        'codigo_sed' => fake()->unique()->lexify('SED????'),
        'nombre_sed' => fake()->unique()->company(),
        'id_ubigeo' => $ubigeoId,
        'estado_sed' => 'HABILITADO',
    ], 'id_sed');

    $insertA = DB::table('tbl_lugar_inscripcion')->insert([
        'codigo_lin' => '603',
        'nombre_lin' => 'PUCALLPA',
        'id_sede' => $sedeId,
        'estado_lin' => 'HABILITADO',
    ]);

    $insertB = DB::table('tbl_lugar_inscripcion')->insert([
        'codigo_lin' => '604',
        'nombre_lin' => 'PUCALLPA',
        'id_sede' => $sedeId,
        'estado_lin' => 'HABILITADO',
    ]);

    expect($insertA)->toBeTrue();
    expect($insertB)->toBeTrue();
});

it('stores the enhanced payment rule fields for cuotas and convenios', function (): void {
    expect(Schema::hasColumns('tbl_regla_pago', [
        'id_convenio_proceso',
        'permite_cuotas_rpa',
        'requiere_pago_completo_rpa',
    ]))->toBeTrue();
});

it('enforces unique document per persona', function (): void {
    $tipoDocumentoId = insertTipoDocumento();

    insertPersona($tipoDocumentoId, '12345678');

    expect(fn (): bool => insertPersona($tipoDocumentoId, '12345678'))
        ->toThrow(QueryException::class);
});

it('enforces one inscription per person and process offer', function (): void {
    $context = createInscripcionContext();

    insertInscripcion($context['persona_id'], $context['proceso_oferta_id'], $context['convenio_proceso_id'], 'INS-001');

    expect(fn (): bool => insertInscripcion($context['persona_id'], $context['proceso_oferta_id'], $context['convenio_proceso_id'], 'INS-002'))
        ->toThrow(QueryException::class);
});

it('prevents bank import details from being reused across inscription payments', function (): void {
    $context = createPagoContext();

    DB::table('tbl_inscripcion_pago')->insert([
        'id_inscripcion_obligacion_pago' => $context['obligacion_pago_id'],
        'id_importacion_banco_detalle' => $context['detalle_banco_id'],
        'numero_cuota_ipa' => 1,
        'monto_ipa' => 50,
        'fecha_pago_ipa' => now(),
        'numero_operacion_ipa' => 'OP-001',
        'estado_validacion_ipa' => 'VERIFICADO',
    ]);

    expect(fn (): bool => DB::table('tbl_inscripcion_pago')->insert([
        'id_inscripcion_obligacion_pago' => $context['obligacion_pago_id'],
        'id_importacion_banco_detalle' => $context['detalle_banco_id'],
        'numero_cuota_ipa' => 2,
        'monto_ipa' => 50,
        'fecha_pago_ipa' => now(),
        'numero_operacion_ipa' => 'OP-002',
        'estado_validacion_ipa' => 'PENDIENTE',
    ]))->toThrow(QueryException::class);
});

function insertTipoDocumento(): int
{
    return (int) DB::table('tbl_tipo_documento')->insertGetId([
        'codigo_tdo' => fake()->unique()->lexify('DOC????'),
        'nombre_tdo' => 'DNI',
        'estado_tdo' => 'HABILITADO',
    ], 'id_tdo');
}

function insertPersona(int $tipoDocumentoId, string $numeroDocumento): bool
{
    return DB::table('tbl_persona')->insert([
        'id_tipo_documento' => $tipoDocumentoId,
        'numero_documento_per' => $numeroDocumento,
        'solo_un_apellido_per' => false,
        'apellido_paterno_per' => 'Perez',
        'nombres_per' => 'Juan',
        'fecha_nacimiento_per' => '2000-01-01',
        'sexo_per' => 'MASCULINO',
        'discapacidad_per' => false,
        'estado_per' => 'HABILITADO',
    ]);
}

/**
 * @return array{persona_id:int, proceso_oferta_id:int, convenio_proceso_id:int}
 */
function createInscripcionContext(): array
{
    $ubigeoId = fake()->unique()->numerify('######');

    DB::table('tbl_ubigeo')->insert([
        'id_ubi' => $ubigeoId,
        'codigo_departamento_ubi' => '25',
        'codigo_provincia_ubi' => '01',
        'codigo_distrito_ubi' => substr($ubigeoId, 4, 2),
        'nombre_departamento_ubi' => 'Ucayali',
        'nombre_provincia_ubi' => 'Coronel Portillo',
        'nombre_distrito_ubi' => fake()->city(),
        'estado_ubi' => 'HABILITADO',
    ]);

    $tipoDocumentoId = insertTipoDocumento();
    $tipoColegioId = (int) DB::table('tbl_tipo_colegio')->insertGetId([
        'codigo_tco' => fake()->unique()->lexify('TCO????'),
        'nombre_tco' => 'PUBLICO',
        'estado_tco' => 'HABILITADO',
    ], 'id_tco');
    $paisId = (int) DB::table('tbl_pais')->insertGetId([
        'codigo_pai' => fake()->unique()->countryCode(),
        'nombre_pai' => 'Peru',
        'estado_pai' => 'HABILITADO',
    ], 'id_pai');
    $nacionalidadId = (int) DB::table('tbl_nacionalidad')->insertGetId([
        'codigo_nac' => fake()->unique()->lexify('NAC????'),
        'nombre_nac' => 'Peruana',
        'estado_nac' => 'HABILITADO',
    ], 'id_nac');
    $modalidadId = (int) DB::table('tbl_modalidad_admision')->insertGetId([
        'codigo_mad' => fake()->unique()->lexify('MAD????'),
        'nombre_mad' => 'Ordinario',
        'estado_mad' => 'HABILITADO',
    ], 'id_mad');
    $sedeId = (int) DB::table('tbl_sede')->insertGetId([
        'codigo_sed' => fake()->unique()->lexify('SED????'),
        'nombre_sed' => 'Pucallpa',
        'id_ubigeo' => $ubigeoId,
        'estado_sed' => 'HABILITADO',
    ], 'id_sed');
    $lugarInscripcionId = (int) DB::table('tbl_lugar_inscripcion')->insertGetId([
        'codigo_lin' => fake()->unique()->lexify('LIN????'),
        'nombre_lin' => 'Caja Central',
        'id_sede' => $sedeId,
        'estado_lin' => 'HABILITADO',
    ], 'id_lin');
    $carreraId = (int) DB::table('tbl_carrera')->insertGetId([
        'codigo_car' => fake()->unique()->lexify('CAR????'),
        'nombre_car' => 'Ingenieria de Sistemas',
        'estado_car' => 'HABILITADO',
    ], 'id_car');
    $turnoId = (int) DB::table('tbl_turno')->insertGetId([
        'codigo_tur' => fake()->unique()->lexify('TUR????'),
        'nombre_tur' => 'Manana',
        'estado_tur' => 'HABILITADO',
    ], 'id_tur');
    $semestreId = (int) DB::table('tbl_semestre')->insertGetId([
        'codigo_sem' => fake()->unique()->lexify('SEM????'),
        'nombre_sem' => '2026-I',
        'fecha_inicio_inscripcion_sem' => '2026-01-01',
        'fecha_fin_inscripcion_sem' => '2026-06-30',
        'estado_sem' => 'HABILITADO',
    ], 'id_sem');
    $procesoId = (int) DB::table('tbl_proceso')->insertGetId([
        'id_semestre' => $semestreId,
        'codigo_pro' => fake()->unique()->lexify('PRO????'),
        'nombre_pro' => 'Ordinario 2026-I',
        'fecha_inicio_pro' => '2026-01-01',
        'fecha_fin_pro' => '2026-03-31',
        'estado_pro' => 'HABILITADO',
    ], 'id_pro');
    $sedeCarreraId = (int) DB::table('tbl_sede_carrera')->insertGetId([
        'id_sede' => $sedeId,
        'id_carrera' => $carreraId,
        'codigo_externo_sca' => fake()->unique()->numerify('####'),
        'estado_sca' => 'HABILITADO',
    ], 'id_sca');
    $modalidadLugarInscripcionId = (int) DB::table('tbl_modalidad_lugar_inscripcion')->insertGetId([
        'id_modalidad_admision' => $modalidadId,
        'id_lugar_inscripcion' => $lugarInscripcionId,
        'estado_mli' => 'HABILITADO',
    ], 'id_mli');
    $ofertaBaseId = (int) DB::table('tbl_oferta_base')->insertGetId([
        'id_modalidad_lugar_inscripcion' => $modalidadLugarInscripcionId,
        'id_sede_carrera' => $sedeCarreraId,
        'estado_ofb' => 'HABILITADO',
    ], 'id_ofb');
    $procesoOfertaId = (int) DB::table('tbl_proceso_oferta')->insertGetId([
        'id_proceso' => $procesoId,
        'id_oferta_base' => $ofertaBaseId,
        'id_turno' => $turnoId,
        'estado_pof' => 'HABILITADO',
    ], 'id_pof');
    $convenioId = (int) DB::table('tbl_convenio')->insertGetId([
        'codigo_con' => fake()->unique()->lexify('CON????'),
        'nombre_con' => 'Convenio General',
        'tipo_beneficio_con' => 'DESCUENTO',
        'monto_descuento_con' => 10,
        'estado_con' => 'HABILITADO',
    ], 'id_con');
    $convenioProcesoId = (int) DB::table('tbl_convenio_proceso')->insertGetId([
        'id_convenio' => $convenioId,
        'id_proceso' => $procesoId,
        'fecha_inicio_cpr' => '2026-01-01',
        'fecha_fin_cpr' => '2026-03-31',
        'cupo_maximo_cpr' => 100,
        'cupo_usado_cpr' => 0,
        'estado_cpr' => 'HABILITADO',
    ], 'id_cpr');
    $personaId = (int) DB::table('tbl_persona')->insertGetId([
        'id_tipo_documento' => $tipoDocumentoId,
        'numero_documento_per' => fake()->unique()->numerify('########'),
        'solo_un_apellido_per' => false,
        'apellido_paterno_per' => 'Perez',
        'nombres_per' => 'Maria',
        'fecha_nacimiento_per' => '2001-01-01',
        'sexo_per' => 'FEMENINO',
        'id_pais_nacimiento' => $paisId,
        'id_nacionalidad' => $nacionalidadId,
        'id_ubigeo_nacimiento' => $ubigeoId,
        'id_ubigeo_domicilio' => $ubigeoId,
        'discapacidad_per' => false,
        'tipo_lengua_materna_per' => 'NATIVA',
        'estado_per' => 'HABILITADO',
    ], 'id_per');
    DB::table('tbl_colegio')->insert([
        'id_col' => fake()->unique()->lexify('COL#####'),
        'codigo_modular_col' => fake()->unique()->numerify('#######'),
        'nombre_col' => 'Colegio UNU',
        'id_tipo_colegio' => $tipoColegioId,
        'id_ubigeo' => $ubigeoId,
        'estado_col' => 'HABILITADO',
    ]);

    return [
        'persona_id' => $personaId,
        'proceso_oferta_id' => $procesoOfertaId,
        'convenio_proceso_id' => $convenioProcesoId,
    ];
}

function insertInscripcion(int $personaId, int $procesoOfertaId, int $convenioProcesoId, string $codigo): bool
{
    return DB::table('tbl_inscripcion')->insert([
        'codigo_ins' => $codigo,
        'id_persona' => $personaId,
        'id_proceso_oferta' => $procesoOfertaId,
        'id_convenio_proceso' => $convenioProcesoId,
        'fecha_inscripcion_ins' => now(),
        'estado_ins' => 'PENDIENTE',
    ]);
}

/**
 * @return array{obligacion_pago_id:int, detalle_banco_id:int}
 */
function createPagoContext(): array
{
    $inscripcionContext = createInscripcionContext();

    DB::table('tbl_inscripcion')->insert([
        'codigo_ins' => 'INS-PAGO',
        'id_persona' => $inscripcionContext['persona_id'],
        'id_proceso_oferta' => $inscripcionContext['proceso_oferta_id'],
        'id_convenio_proceso' => $inscripcionContext['convenio_proceso_id'],
        'fecha_inscripcion_ins' => now(),
        'estado_ins' => 'PENDIENTE',
    ]);

    $inscripcionId = (int) DB::table('tbl_inscripcion')
        ->where('codigo_ins', 'INS-PAGO')
        ->value('id_ins');

    $conceptoPagoId = (int) DB::table('tbl_concepto_pago')->insertGetId([
        'codigo_cpg' => fake()->unique()->lexify('CPG????'),
        'nombre_cpg' => 'Inscripcion',
        'permite_cuotas_cpg' => true,
        'estado_cpg' => 'HABILITADO',
    ], 'id_cpg');

    $procesoId = (int) DB::table('tbl_proceso')->value('id_pro');
    $modalidadId = (int) DB::table('tbl_modalidad_admision')->value('id_mad');

    $reglaPagoId = (int) DB::table('tbl_regla_pago')->insertGetId([
        'id_concepto_pago' => $conceptoPagoId,
        'id_proceso' => $procesoId,
        'id_modalidad_admision' => $modalidadId,
        'id_convenio_proceso' => null,
        'monto_total_rpa' => 100,
        'permite_cuotas_rpa' => true,
        'monto_minimo_primera_cuota_rpa' => 50,
        'numero_maximo_cuotas_rpa' => 2,
        'requiere_pago_completo_rpa' => false,
        'vigente_desde_rpa' => '2026-01-01',
        'estado_rpa' => 'HABILITADO',
    ], 'id_rpa');

    $obligacionPagoId = (int) DB::table('tbl_inscripcion_obligacion_pago')->insertGetId([
        'id_inscripcion' => $inscripcionId,
        'id_regla_pago' => $reglaPagoId,
        'monto_total_iop' => 100,
        'monto_pagado_iop' => 0,
        'saldo_iop' => 100,
        'numero_cuotas_iop' => 2,
        'estado_iop' => 'PENDIENTE',
    ], 'id_iop');

    $importacionBancoId = (int) DB::table('tbl_importacion_banco')->insertGetId([
        'nombre_archivo_iba' => 'pagos.txt',
        'fecha_importacion_iba' => now(),
        'total_registros_iba' => 1,
        'total_monto_iba' => 50,
        'estado_iba' => 'PENDIENTE',
    ], 'id_iba');

    $detalleBancoId = (int) DB::table('tbl_importacion_banco_detalle')->insertGetId([
        'id_importacion_banco' => $importacionBancoId,
        'numero_documento_ibd' => '12345678',
        'nombre_depositante_ibd' => 'Juan Perez',
        'numero_operacion_ibd' => 'OP-001',
        'fecha_pago_ibd' => now(),
        'monto_ibd' => 50,
        'texto_linea_ibd' => 'detalle',
        'estado_ibd' => 'PENDIENTE',
    ], 'id_ibd');

    return [
        'obligacion_pago_id' => $obligacionPagoId,
        'detalle_banco_id' => $detalleBancoId,
    ];
}
