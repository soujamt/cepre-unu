<?php

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('guests are redirected to login when they try to access inicio', function () {
    $this->get('/inicio')
        ->assertRedirect('/auth/login');
});

test('a user can log in with valid credentials', function () {
    $usuario = Usuario::factory()->create([
        'correo_usu' => 'admin@cepre.test',
        'clave_usu' => 'secret123',
        'estado_usu' => 'HABILITADO',
    ]);

    Livewire::test('pages::auth.login')
        ->set('correo', 'admin@cepre.test')
        ->set('password', 'secret123')
        ->call('login')
        ->assertRedirect(route('inicio'));

    $this->assertAuthenticatedAs($usuario);
});

test('a user cannot log in with invalid credentials', function () {
    RateLimiter::clear('admin@cepre.test|127.0.0.1');

    Usuario::factory()->create([
        'correo_usu' => 'admin@cepre.test',
        'clave_usu' => 'secret123',
        'estado_usu' => 'HABILITADO',
    ]);

    Livewire::test('pages::auth.login')
        ->set('correo', 'admin@cepre.test')
        ->set('password', 'incorrecta')
        ->call('login')
        ->assertHasErrors(['correo']);

    $this->assertGuest();
});

test('a disabled user cannot log in', function () {
    RateLimiter::clear('admin@cepre.test|127.0.0.1');

    Usuario::factory()->create([
        'correo_usu' => 'admin@cepre.test',
        'clave_usu' => 'secret123',
        'estado_usu' => 'DESHABILITADO',
    ]);

    Livewire::test('pages::auth.login')
        ->set('correo', 'admin@cepre.test')
        ->set('password', 'secret123')
        ->call('login')
        ->assertHasErrors(['correo']);

    $this->assertGuest();
});

test('login normalizes the email before authenticating', function () {
    $usuario = Usuario::factory()->create([
        'correo_usu' => 'admin@cepre.test',
        'clave_usu' => 'secret123',
        'estado_usu' => 'HABILITADO',
    ]);

    Livewire::withQueryParams([])
        ->test('pages::auth.login')
        ->set('correo', '  ADMIN@CEPRE.TEST ')
        ->set('password', 'secret123')
        ->call('login')
        ->assertRedirect(route('inicio'));

    $this->assertAuthenticatedAs($usuario);
});

test('login is rate limited after too many failed attempts', function () {
    $key = 'admin@cepre.test|127.0.0.1';
    RateLimiter::clear($key);

    Usuario::factory()->create([
        'correo_usu' => 'admin@cepre.test',
        'clave_usu' => 'secret123',
        'estado_usu' => 'HABILITADO',
    ]);

    $component = Livewire::test('pages::auth.login')
        ->set('correo', 'admin@cepre.test')
        ->set('password', 'incorrecta');

    for ($i = 0; $i < 5; $i++) {
        $component->call('login')
            ->assertHasErrors(['correo']);
    }

    $component->call('login')
        ->assertHasErrors(['lockout']);

    expect(RateLimiter::tooManyAttempts($key, 5))->toBeTrue();
});

test('an authenticated user can log out from the profile component', function () {
    $usuario = Usuario::factory()->create([
        'correo_usu' => 'admin@cepre.test',
        'clave_usu' => 'secret123',
        'estado_usu' => 'HABILITADO',
    ]);

    $this->actingAs($usuario);

    Livewire::test('livewire.navegacion.profile.index')
        ->call('logout')
        ->assertRedirect(route('login'));

    $this->assertGuest();
});
