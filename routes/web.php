<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/inicio');
Route::livewire('/inicio', 'pages::inicio.index')->name('inicio');
