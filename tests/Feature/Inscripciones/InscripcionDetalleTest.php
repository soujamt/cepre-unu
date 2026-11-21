<?php

use App\Enums\EstadoInscripcion;
use App\Models\Accion;
use App\Models\Aula;
use App\Models\Inscripcion;
use App\Models\ModalidadAdmision;
use App\Models\Pais;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use App\Models\Persona;
use App\Models\ProcesoAula;
use App\Models\ProcesoCarrera;
use App\Models\Rol;
use App\Models\TipoDocumento;
use App\Models\Turno;
use App\Models\Usuario;
use Database\Seeders\CarreraSeeder;
use Database\Seeders\ConceptoPagoSeeder;
use Database\Seeders\ConfiguracionAcademicaInicialSeeder;
use Database\Seeders\ModalidadAdmisionSeeder;
use Database\Seeders\PaisSeeder;
use Database\Seeders\SedeSeeder;
use Database\Seeders\TipoDocumentoSeeder;
use Database\Seeders\TurnoSeeder;
use Database\Seeders\UbigeoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

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
        TipoDocumentoSeeder::class,
        ConceptoPagoSeeder::class,
        PaisSeeder::class,
        UbigeoSeeder::class,
        SedeSeeder::class,
        CarreraSeeder::class,
        ModalidadAdmisionSeeder::class,
        ConfiguracionAcademicaInicialSeeder::class,
    ] as $seederClass) {
        Artisan::call('db:seed', ['--class' => $seederClass, '--no-interaction' => true]);
    }
}

test('detail page shows assigned aula information', function () {
    $usuario = Usuario::factory()->create();
    otorgarPermisosInscripcionDetalle($usuario, ['inscripcion_indice']);

    sembrarConfiguracionParaDetalle();

    $tipoDocumento = TipoDocumento::query()->where('longitud_minima_tdo', 8)->firstOrFail();
    $pais = Pais::query()->whereIn('codigo_pai', ['1', 'PE'])->firstOrFail();

    $procesoCarrera = ProcesoCarrera::query()->orderBy('id_pca')->firstOrFail();
    $turno = Turno::query()->where('codigo_tur', 'MAN')->firstOrFail();

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
        'id_proceso' => $procesoCarrera->id_proceso,
        'id_turno' => $turno->id_tur,
        'id_aula' => $aula->id_aul,
        'capacidad_pau' => 45,
        'estado_pau' => 'HABILITADO',
    ]);

    $inscripcion = Inscripcion::query()->create([
        'codigo_ins' => 'TEST-DET-AULA',
        'id_persona' => $persona->id_per,
        'id_proceso' => $procesoCarrera->id_proceso,
        'id_sede' => $procesoCarrera->id_sede,
        'id_modalidad_admision' => ModalidadAdmision::query()->where('codigo_mad', 'ORD')->value('id_mad'),
        'id_carrera' => $procesoCarrera->id_carrera,
        'id_turno' => $turno->id_tur,
        'id_proceso_aula' => $procesoAula->id_pau,
        'fecha_inscripcion_ins' => now(),
        'estado_ins' => EstadoInscripcion::Pendiente->value,
    ]);

    $this->actingAs($usuario)
        ->get(route('inscripciones.detalle', encode($inscripcion->id_ins)))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('inscripciones/detalle')
            ->where('postulacion.aula', fn (string $aula): bool => str_contains($aula, 'AULA DETALLE 2') && str_contains($aula, 'PISO 2')));
});
