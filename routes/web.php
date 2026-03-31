<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/auth', '/auth/login');
Route::middleware('guest')->group(function (): void {
    Route::livewire('/auth/login', 'pages::auth.login')->name('login');
});

Route::redirect('/', '/inicio');
Route::middleware('auth')->group(function (): void {
    Route::livewire('/inicio', 'pages::inicio.index')->name('inicio');

    Route::livewire('/seguridad/roles', 'pages::seguridad.roles')
        ->middleware('permiso:roles_indice')
        ->name('roles.index');
    Route::livewire('/seguridad/roles/{rol}/permisos', 'pages::seguridad.roles-permisos')
        ->middleware('permiso:roles_permisos')
        ->name('roles.permisos');
    Route::livewire('/seguridad/permisos', 'pages::seguridad.permisos')
        ->middleware('permiso:permisos_indice')
        ->name('permisos.index');
});
