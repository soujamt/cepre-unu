<?php

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Support\SessionKey;
use Inertia\Testing\AssertableInertia;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    RateLimiter::clear('admin@cepre.test|127.0.0.1');
});

function usuarioDeLogin(string $estado = 'HABILITADO'): Usuario
{
    return Usuario::factory()->create([
        'correo_usu' => 'admin@cepre.test',
        'clave_usu' => 'secret123',
        'estado_usu' => $estado,
    ]);
}

test('guests are redirected to login when they try to access inicio', function () {
    $this->get('/inicio')
        ->assertRedirect('/auth/login');
});

test('the login page is an Inertia page', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('auth/login'));
});

test('a user can log in with valid credentials', function () {
    $usuario = usuarioDeLogin();

    $this->post(route('login.store'), ['correo' => 'admin@cepre.test', 'password' => 'secret123'])
        ->assertRedirect(route('inicio'))
        ->assertSessionHas(SessionKey::FLASH_DATA.'.toast', ['type' => 'success', 'message' => 'Bienvenido de nuevo']);

    $this->assertAuthenticatedAs($usuario);
});

test('after logging in the browser opens the page it wanted with a full page load', function () {
    usuarioDeLogin();

    $this->get(route('inscripciones.index'))->assertRedirect(route('login'));

    $this->post(route('login.store'), ['correo' => 'admin@cepre.test', 'password' => 'secret123'], ['X-Inertia' => 'true'])
        ->assertStatus(409)
        ->assertHeader('X-Inertia-Location', route('inscripciones.index'));
});

test('a user cannot log in with invalid credentials', function () {
    usuarioDeLogin();

    $this->from(route('login'))
        ->post(route('login.store'), ['correo' => 'admin@cepre.test', 'password' => 'incorrecta'])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['correo' => 'Las credenciales ingresadas no son válidas.']);

    $this->assertGuest();
});

test('a disabled user cannot log in', function () {
    usuarioDeLogin('DESHABILITADO');

    $this->post(route('login.store'), ['correo' => 'admin@cepre.test', 'password' => 'secret123'])
        ->assertSessionHasErrors('correo');

    $this->assertGuest();
});

test('login requires an email and a password', function () {
    $this->post(route('login.store'), ['correo' => 'no-es-correo', 'password' => ''])
        ->assertSessionHasErrors([
            'correo' => 'Ingresa un correo electrónico válido.',
            'password' => 'La contraseña es obligatoria.',
        ]);
});

test('login normalizes the email before authenticating', function () {
    $usuario = usuarioDeLogin();

    $this->post(route('login.store'), ['correo' => '  ADMIN@CEPRE.TEST ', 'password' => 'secret123'])
        ->assertRedirect(route('inicio'));

    $this->assertAuthenticatedAs($usuario);
});

test('login is locked for a minute after five failed attempts', function () {
    usuarioDeLogin();
    $credenciales = ['correo' => 'admin@cepre.test', 'password' => 'incorrecta'];

    for ($intento = 0; $intento < 5; $intento++) {
        $this->post(route('login.store'), $credenciales)->assertSessionHasErrors('correo');
    }

    $this->post(route('login.store'), ['correo' => 'admin@cepre.test', 'password' => 'secret123'])
        ->assertSessionHasErrors('lockout')
        ->assertSessionHas(SessionKey::FLASH_DATA.'.segundosBloqueo', fn (int $segundos): bool => $segundos > 0 && $segundos <= 60);

    $this->assertGuest();
});

test('an authenticated user can log out from the profile component', function () {
    $this->actingAs(usuarioDeLogin());

    Livewire::test('livewire.navegacion.profile.index')
        ->call('logout')
        ->assertRedirect(route('login'));

    $this->assertGuest();
});
