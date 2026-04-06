<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/auth', '/auth/login');
Route::middleware('guest')->group(function (): void {
    Route::livewire('/auth/login', 'pages::auth.login')->name('login');
});

Route::redirect('/', '/inicio');
Route::middleware('auth')->group(function (): void {
    Route::livewire('/inicio', 'pages::inicio.index')->name('inicio');

    Route::livewire('/seguridad/usuarios', 'pages::seguridad.usuarios')
        ->middleware('permiso:usuario_indice')
        ->name('usuarios.index');
    Route::livewire('/seguridad/roles', 'pages::seguridad.roles')
        ->middleware('permiso:rol_indice')
        ->name('roles.index');
    Route::livewire('/seguridad/roles/{rol}/permisos', 'pages::seguridad.roles-permisos')
        ->middleware('permiso:rol_permisos')
        ->name('roles.permisos');
    Route::livewire('/seguridad/permisos', 'pages::seguridad.permisos')
        ->middleware('permiso:permiso_indice')
        ->name('permisos.index');

    Route::livewire('/mantenedores/tipos-expediente', 'pages::mantenedores.tipos-expediente')
        ->middleware('permiso:tipo_expediente_indice')
        ->name('tipos-expediente.index');

    Route::livewire('/configuracion-academica/semestres', 'pages::configuracion-academica.semestres')
        ->middleware('permiso:semestre_indice')
        ->name('semestres.index');

    Route::livewire('/configuracion-academica/procesos', 'pages::configuracion-academica.procesos')
        ->middleware('permiso:proceso_indice')
        ->name('procesos.index');
});
