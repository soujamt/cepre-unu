<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/auth', '/auth/login');
Route::livewire('/auth/login', 'pages::auth.login')->name('login');

Route::redirect('/', '/inicio');
Route::livewire('/inicio', 'pages::inicio.index')->name('inicio');
