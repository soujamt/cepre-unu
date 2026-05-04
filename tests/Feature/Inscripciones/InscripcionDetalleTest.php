<?php

use App\Enums\EstadoInscripcion;
use App\Enums\EstadoObligacionPago;
use App\Enums\EstadoRevisionExpediente;
use App\Enums\EstadoValidacionPago;
use App\Models\Accion;
use App\Models\Aula;
use App\Models\Inscripcion;
use App\Models\InscripcionExpediente;
use App\Models\InscripcionObligacionPago;
use App\Models\InscripcionPago;
use App\Models\Pais;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use App\Models\Persona;
use App\Models\ProcesoAula;
use App\Models\ProcesoOferta;
use App\Models\ReglaPago;
use App\Models\RequisitoExpediente;
use App\Models\Rol;
use App\Models\TipoDocumento;
use App\Models\Usuario;
use Database\Seeders\CarreraSeeder;
use Database\Seeders\ConceptoPagoSeeder;
use Database\Seeders\ConfiguracionAcademicaInicialSeeder;
use Database\Seeders\LugarInscripcionSeeder;
use Database\Seeders\ModalidadAdmisionSeeder;
use Database\Seeders\ModalidadLugarInscripcionSeeder;
use Database\Seeders\OfertaBaseSeeder;
use Database\Seeders\PaisSeeder;
use Database\Seeders\SedeCarreraSeeder;
use Database\Seeders\SedeSeeder;
use Database\Seeders\TipoDocumentoSeeder;
use Database\Seeders\TipoExpedienteSeeder;
use Database\Seeders\TurnoSeeder;
use Database\Seeders\UbigeoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function otorgarPermisosInscripcionDetalle(Usuario $usuario, array $claves): void
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

function sembrarConfiguracionParaDetalle(): void
{
    foreach ([
        TurnoSeeder::class,
        TipoExpedienteSeeder::class,
        TipoDocumentoSeeder::class,
        ConceptoPagoSeeder::class,
        PaisSeeder::class,
        UbigeoSeeder::class,
        SedeSeeder::class,
        CarreraSeeder::class,
        LugarInscripcionSeeder::class,
        ModalidadAdmisionSeeder::class,
        ModalidadLugarInscripcionSeeder::class,
        SedeCarreraSeeder::class,
        OfertaBaseSeeder::class,
        ConfiguracionAcademicaInicialSeeder::class,
    ] as $seederClass) {
        Artisan::call('db:seed', ['--class' => $seederClass, '--no-interaction' => true]);
    }
}

test('aprobar ultimo expediente obligatorio activa la inscripcion automaticamente', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionDetalle($usuario, ['inscripcion_indice', 'inscripcion_editar']);

    sembrarConfiguracionParaDetalle();

    $tipoDocumento = TipoDocumento::query()->where('longitud_minima_tdo', 8)->firstOrFail();
    $pais = Pais::query()->whereIn('codigo_pai', ['1', 'PE'])->firstOrFail();

    $procesoOferta = ProcesoOferta::query()
        ->with(['ofertaBase', 'proceso'])
        ->where('estado_pof', 'HABILITADO')
        ->firstOrFail();

    $persona = Persona::query()->create([
        'id_tipo_documento' => $tipoDocumento->id_tdo,
        'numero_documento_per' => '55667788',
        'solo_un_apellido_per' => false,
        'apellido_paterno_per' => 'HUANCA',
        'apellido_materno_per' => 'MAMANI',
        'nombres_per' => 'PEDRO',
        'id_pais_nacimiento' => $pais->id_pai,
        'estado_per' => 'HABILITADO',
    ]);

    $inscripcion = Inscripcion::query()->create([
        'codigo_ins' => 'TEST-DET-001',
        'id_persona' => $persona->id_per,
        'id_proceso_oferta' => $procesoOferta->id_pof,
        'fecha_inscripcion_ins' => now(),
        'estado_ins' => EstadoInscripcion::Pendiente->value,
    ]);

    // Crear una obligación de pago con al menos un pago verificado
    $reglaPago = ReglaPago::query()
        ->where('id_proceso', $procesoOferta->id_proceso)
        ->firstOrFail();

    $obligacion = InscripcionObligacionPago::query()->make()->forceFill([
        'id_inscripcion' => $inscripcion->id_ins,
        'id_regla_pago' => $reglaPago->id_rpa,
        'monto_total_iop' => $reglaPago->monto_total_rpa,
        'saldo_iop' => $reglaPago->monto_total_rpa,
        'estado_iop' => EstadoObligacionPago::Parcial->value,
    ]);
    $obligacion->save();

    InscripcionPago::query()->create([
        'id_inscripcion_obligacion_pago' => $obligacion->id_iop,
        'numero_cuota_ipa' => 1,
        'monto_ipa' => 100,
        'fecha_pago_ipa' => now()->subDay(),
        'estado_validacion_ipa' => EstadoValidacionPago::Verificado->value,
        'validado_en' => now(),
        'id_usuario_validador' => $usuario->id_usu,
    ]);

    $proceso = $procesoOferta->proceso;
    $requisitosObligatorios = RequisitoExpediente::query()
        ->where('id_proceso', $proceso->id_pro)
        ->where('obligatorio_rex', true)
        ->get();

    expect($requisitosObligatorios)->not->toBeEmpty();

    // Aprobar todos los requisitos excepto el último
    foreach ($requisitosObligatorios->slice(0, -1) as $requisito) {
        InscripcionExpediente::query()->create([
            'id_inscripcion' => $inscripcion->id_ins,
            'id_tipo_expediente' => $requisito->id_tipo_expediente,
            'estado_revision_iex' => EstadoRevisionExpediente::Aprobado->value,
            'revisado_en' => now(),
            'id_usuario_revisor' => $usuario->id_usu,
        ]);
    }

    // Crear el último expediente aún pendiente
    $ultimoRequisito = $requisitosObligatorios->last();

    $ultimoExpediente = InscripcionExpediente::query()->create([
        'id_inscripcion' => $inscripcion->id_ins,
        'id_tipo_expediente' => $ultimoRequisito->id_tipo_expediente,
        'estado_revision_iex' => EstadoRevisionExpediente::Pendiente->value,
        'revisado_en' => null,
        'id_usuario_revisor' => null,
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.inscripcion-detalle', ['inscripcion' => encode($inscripcion->id_ins)])
        ->call('aprobarExpediente', $ultimoExpediente->id_iex)
        ->assertDispatched('toast', type: 'success');

    expect($inscripcion->fresh()->estado_ins)->toBe(EstadoInscripcion::Activo);
});

test('aprobar expediente no activa la inscripcion si quedan obligatorios pendientes', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionDetalle($usuario, ['inscripcion_indice', 'inscripcion_editar']);

    sembrarConfiguracionParaDetalle();

    $tipoDocumento = TipoDocumento::query()->where('longitud_minima_tdo', 8)->firstOrFail();
    $pais = Pais::query()->whereIn('codigo_pai', ['1', 'PE'])->firstOrFail();

    $procesoOferta = ProcesoOferta::query()
        ->with(['ofertaBase', 'proceso'])
        ->where('estado_pof', 'HABILITADO')
        ->firstOrFail();

    $persona = Persona::query()->create([
        'id_tipo_documento' => $tipoDocumento->id_tdo,
        'numero_documento_per' => '55667799',
        'solo_un_apellido_per' => false,
        'apellido_paterno_per' => 'QUISPE',
        'apellido_materno_per' => 'TAPIA',
        'nombres_per' => 'LUCIA',
        'id_pais_nacimiento' => $pais->id_pai,
        'estado_per' => 'HABILITADO',
    ]);

    $inscripcion = Inscripcion::query()->create([
        'codigo_ins' => 'TEST-DET-002',
        'id_persona' => $persona->id_per,
        'id_proceso_oferta' => $procesoOferta->id_pof,
        'fecha_inscripcion_ins' => now(),
        'estado_ins' => EstadoInscripcion::Pendiente->value,
    ]);

    $proceso = $procesoOferta->proceso;
    $requisitosObligatorios = RequisitoExpediente::query()
        ->where('id_proceso', $proceso->id_pro)
        ->where('obligatorio_rex', true)
        ->get();

    if ($requisitosObligatorios->count() < 2) {
        $this->markTestSkipped('Se necesitan al menos 2 requisitos obligatorios para este test.');
    }

    // Solo aprobar el primero; el resto quedan sin expediente (pendientes implícitos)
    $primerRequisito = $requisitosObligatorios->first();

    $primerExpediente = InscripcionExpediente::query()->create([
        'id_inscripcion' => $inscripcion->id_ins,
        'id_tipo_expediente' => $primerRequisito->id_tipo_expediente,
        'estado_revision_iex' => EstadoRevisionExpediente::Pendiente->value,
        'revisado_en' => null,
        'id_usuario_revisor' => null,
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.inscripcion-detalle', ['inscripcion' => encode($inscripcion->id_ins)])
        ->call('aprobarExpediente', $primerExpediente->id_iex);

    expect($inscripcion->fresh()->estado_ins)->toBe(EstadoInscripcion::Pendiente);
});

test('detail page shows assigned aula information', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionDetalle($usuario, ['inscripcion_indice']);

    sembrarConfiguracionParaDetalle();

    $tipoDocumento = TipoDocumento::query()->where('longitud_minima_tdo', 8)->firstOrFail();
    $pais = Pais::query()->whereIn('codigo_pai', ['1', 'PE'])->firstOrFail();

    $procesoOferta = ProcesoOferta::query()
        ->with(['ofertaBase', 'proceso'])
        ->where('estado_pof', 'HABILITADO')
        ->firstOrFail();

    $persona = Persona::query()->create([
        'id_tipo_documento' => $tipoDocumento->id_tdo,
        'numero_documento_per' => '55667111',
        'solo_un_apellido_per' => false,
        'apellido_paterno_per' => 'RUIZ',
        'apellido_materno_per' => 'LOPEZ',
        'nombres_per' => 'MARIA',
        'id_pais_nacimiento' => $pais->id_pai,
        'estado_per' => 'HABILITADO',
    ]);

    $aula = Aula::query()->create([
        'nombre_aul' => 'AULA DETALLE 2',
        'piso_aul' => 'PISO 2',
        'lugar_aul' => 'UNU',
        'orden_aul' => 2,
        'estado_aul' => 'HABILITADO',
    ]);

    $procesoAula = ProcesoAula::query()->create([
        'id_proceso' => $procesoOferta->id_proceso,
        'id_turno' => $procesoOferta->id_turno,
        'id_aula' => $aula->id_aul,
        'capacidad_pau' => 45,
        'estado_pau' => 'HABILITADO',
    ]);

    $inscripcion = Inscripcion::query()->create([
        'codigo_ins' => 'TEST-DET-AULA',
        'id_persona' => $persona->id_per,
        'id_proceso_oferta' => $procesoOferta->id_pof,
        'id_proceso_aula' => $procesoAula->id_pau,
        'fecha_inscripcion_ins' => now(),
        'estado_ins' => EstadoInscripcion::Pendiente->value,
    ]);

    $this->actingAs($usuario)
        ->get(route('inscripciones.detalle', encode($inscripcion->id_ins)))
        ->assertOk()
        ->assertSee('Aula asignada')
        ->assertSee('AULA DETALLE 2')
        ->assertSee('PISO 2');
});

test('aprobar todos los expedientes obligatorios no activa la inscripcion si no hay pago verificado', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionDetalle($usuario, ['inscripcion_indice', 'inscripcion_editar']);

    sembrarConfiguracionParaDetalle();

    $tipoDocumento = TipoDocumento::query()->where('longitud_minima_tdo', 8)->firstOrFail();
    $pais = Pais::query()->whereIn('codigo_pai', ['1', 'PE'])->firstOrFail();

    $procesoOferta = ProcesoOferta::query()
        ->with(['ofertaBase', 'proceso'])
        ->where('estado_pof', 'HABILITADO')
        ->firstOrFail();

    $persona = Persona::query()->create([
        'id_tipo_documento' => $tipoDocumento->id_tdo,
        'numero_documento_per' => '55667711',
        'solo_un_apellido_per' => false,
        'apellido_paterno_per' => 'FLORES',
        'apellido_materno_per' => 'GUTIERREZ',
        'nombres_per' => 'ROSA',
        'id_pais_nacimiento' => $pais->id_pai,
        'estado_per' => 'HABILITADO',
    ]);

    $inscripcion = Inscripcion::query()->create([
        'codigo_ins' => 'TEST-DET-003',
        'id_persona' => $persona->id_per,
        'id_proceso_oferta' => $procesoOferta->id_pof,
        'fecha_inscripcion_ins' => now(),
        'estado_ins' => EstadoInscripcion::Pendiente->value,
    ]);

    // Obligación de pago en estado Pendiente (sin pagos verificados)
    $reglaPago = ReglaPago::query()
        ->where('id_proceso', $procesoOferta->id_proceso)
        ->firstOrFail();

    $obligacionSinPago = InscripcionObligacionPago::query()->make()->forceFill([
        'id_inscripcion' => $inscripcion->id_ins,
        'id_regla_pago' => $reglaPago->id_rpa,
        'monto_total_iop' => $reglaPago->monto_total_rpa,
        'saldo_iop' => $reglaPago->monto_total_rpa,
        'estado_iop' => EstadoObligacionPago::Pendiente->value,
    ]);
    $obligacionSinPago->save();

    $proceso = $procesoOferta->proceso;
    $requisitosObligatorios = RequisitoExpediente::query()
        ->where('id_proceso', $proceso->id_pro)
        ->where('obligatorio_rex', true)
        ->get();

    expect($requisitosObligatorios)->not->toBeEmpty();

    // Aprobar todos los requisitos excepto el último
    foreach ($requisitosObligatorios->slice(0, -1) as $requisito) {
        InscripcionExpediente::query()->create([
            'id_inscripcion' => $inscripcion->id_ins,
            'id_tipo_expediente' => $requisito->id_tipo_expediente,
            'estado_revision_iex' => EstadoRevisionExpediente::Aprobado->value,
            'revisado_en' => now(),
            'id_usuario_revisor' => $usuario->id_usu,
        ]);
    }

    $ultimoRequisito = $requisitosObligatorios->last();

    $ultimoExpediente = InscripcionExpediente::query()->create([
        'id_inscripcion' => $inscripcion->id_ins,
        'id_tipo_expediente' => $ultimoRequisito->id_tipo_expediente,
        'estado_revision_iex' => EstadoRevisionExpediente::Pendiente->value,
        'revisado_en' => null,
        'id_usuario_revisor' => null,
    ]);

    $this->actingAs($usuario);

    Livewire::test('pages::inscripciones.inscripcion-detalle', ['inscripcion' => encode($inscripcion->id_ins)])
        ->call('aprobarExpediente', $ultimoExpediente->id_iex);

    expect($inscripcion->fresh()->estado_ins)->toBe(EstadoInscripcion::Pendiente);
});
