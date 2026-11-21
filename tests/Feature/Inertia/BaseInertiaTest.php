<?php

use App\Models\Accion;
use App\Models\Permiso;
use App\Models\PermisoAccion;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Support\SessionKey;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

/**
 * @param  list<string>  $claves
 */
function usuarioConAcciones(array $claves, string $nombreRol = ''): Usuario
{
    $usuario = Usuario::factory()->create([
        'nombres_usu' => 'MARIA ELENA QUISPE',
        'correo_usu' => 'mquispe@unu.edu.pe',
    ]);

    $rol = Rol::query()->create([
        'nombre_rol' => $nombreRol !== '' ? $nombreRol : 'ROL '.Str::upper(Str::random(8)),
        'descripcion_rol' => 'Rol de pruebas',
        'estado_rol' => 'HABILITADO',
    ]);
    $usuario->roles()->attach($rol->id_rol);

    foreach ($claves as $clave) {
        [$clavePermiso, $claveAccion] = explode('_', $clave, 2);
        $permiso = Permiso::query()->firstOrCreate(
            ['clave_permiso' => $clavePermiso],
            ['nombre_permiso' => Str::upper($clavePermiso), 'estado_permiso' => 'HABILITADO'],
        );
        $accion = Accion::query()->where('clave_accion', $claveAccion)->firstOrFail();
        $permisoAccion = PermisoAccion::query()->updateOrCreate(
            ['id_permiso' => $permiso->id_permiso, 'id_accion' => $accion->id_acc],
            ['clave_peracc' => $clave, 'estado_peracc' => 'HABILITADO', 'eliminado_en' => null],
        );
        $rol->permisoAcciones()->syncWithoutDetaching([$permisoAccion->id_peracc]);
    }

    return $usuario;
}

it('shares the session user and the permission keys with Inertia pages', function (): void {
    $usuario = usuarioConAcciones(['inscripcion_indice', 'pago_crear']);

    $this->actingAs($usuario)
        ->get(route('dev.componentes'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('dev/componentes')
            ->where('app.nombre', config('app.name'))
            ->where('auth.user.nombre', 'Maria Elena Quispe')
            ->where('auth.user.iniciales', 'ME')
            ->where('auth.user.correo', 'mq*****@unu.edu.pe')
            ->where('auth.permisos.administrador', false)
            ->where('auth.permisos.claves', fn ($claves): bool => collect($claves)->sort()->values()->all() === ['inscripcion_indice', 'pago_crear']));
});

it('marks administrators as having every permission', function (): void {
    $usuario = usuarioConAcciones([], 'ADMINISTRADOR');

    $this->actingAs($usuario)
        ->get(route('dev.componentes'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('auth.permisos.administrador', true)
            ->where('auth.permisos.claves', []));
});

it('redirects guests to the login page', function (): void {
    $this->get(route('dev.componentes'))->assertRedirect(route('login'));
});

it('logs out, invalidates the session and flashes a toast', function (): void {
    $this->actingAs(usuarioConAcciones([]))
        ->post(route('logout'))
        ->assertRedirect(route('login'))
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'success', 'message' => 'Sesión cerrada correctamente']);

    $this->assertGuest();
});

it('sends Inertia visits to the login page with a full page load', function (): void {
    $this->actingAs(usuarioConAcciones([]))
        ->post(route('logout'), [], ['X-Inertia' => 'true'])
        ->assertStatus(409)
        ->assertHeader('X-Inertia-Location', route('login'));

    $this->assertGuest();
});
