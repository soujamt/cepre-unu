<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/auth', '/auth/login');
Route::middleware('guest')->group(function (): void {
    Route::livewire('/auth/login', 'pages::auth.login')->name('login');
});

Route::redirect('/', '/inicio');
Route::middleware('auth')->group(function (): void {
    Route::livewire('/inicio', 'pages::inicio.index')->name('inicio');

    Route::livewire('/seguridad/permisos', 'pages::seguridad.permisos')->name('permisos.index');
});
