<?php

use App\Http\Controllers\Api\ColegioSearchController;
use App\Http\Controllers\Api\UbigeoSearchController;
use Illuminate\Support\Facades\Route;

Route::redirect('/auth', '/auth/login');
Route::middleware('guest')->group(function (): void {
    Route::livewire('/auth/login', 'pages::auth.login')->name('login');
});

Route::redirect('/', '/inicio');
Route::middleware('auth')->group(function (): void {
    Route::get('/api/ubigeos/search', UbigeoSearchController::class)
        ->name('api.ubigeo.search');

    Route::get('/api/colegios/search', ColegioSearchController::class)
        ->name('api.colegio.search');

    // Dashboard
    Route::livewire('/inicio', 'pages::inicio.index')->name('inicio');

    // Seguridad
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

    // Mantenedores
    Route::livewire('/mantenedores/tipos-expediente', 'pages::mantenedores.tipos-expediente')
        ->middleware('permiso:tipo_expediente_indice')
        ->name('tipos-expediente.index');

    // Configuración Académica
    Route::livewire('/configuracion-academica/semestres', 'pages::configuracion-academica.semestres')
        ->middleware('permiso:semestre_indice')
        ->name('semestres.index');

    Route::livewire('/configuracion-academica/procesos', 'pages::configuracion-academica.procesos')
        ->middleware('permiso:proceso_indice')
        ->name('procesos.index');

    Route::livewire('/configuracion-academica/ofertas', 'pages::configuracion-academica.ofertas')
        ->middleware('permiso:oferta_indice')
        ->name('ofertas.index');

    Route::livewire('/configuracion-academica/ofertas/asignar', 'pages::configuracion-academica.ofertas-asignar')
        ->middleware('permiso:oferta_crear')
        ->name('ofertas.asignar');

    Route::livewire('/configuracion-academica/aulas', 'pages::configuracion-academica.aulas-proceso')
        ->middleware('permiso:aula_proceso_indice')
        ->name('aulas.index');

    Route::livewire('/configuracion-academica/aulas/asignar', 'pages::configuracion-academica.aulas-proceso-asignar')
        ->middleware('permiso:aula_proceso_crear')
        ->name('aulas.asignar');

    Route::livewire('/configuracion-academica/reglas-pago', 'pages::configuracion-academica.reglas-pago')
        ->middleware('permiso:regla_pago_indice')
        ->name('reglas-pago.index');

    Route::livewire('/configuracion-academica/convenios', 'pages::configuracion-academica.convenios')
        ->middleware('permiso:convenio_indice')
        ->name('convenios.index');

    Route::livewire('/configuracion-academica/requisitos', 'pages::configuracion-academica.requisitos')
        ->middleware('permiso:requisito_expediente_indice')
        ->name('requisitos.index');

    // Inscripciones
    Route::livewire('/inscripciones', 'pages::inscripciones.inscripciones')
        ->middleware('permiso:inscripcion_indice')
        ->name('inscripciones.index');

    Route::livewire('/inscripciones/nueva', 'pages::inscripciones.inscripcion-crear')
        ->middleware('permiso:inscripcion_crear')
        ->name('inscripciones.crear');

    Route::livewire('/inscripciones/{inscripcion}/continuar', 'pages::inscripciones.inscripcion-crear')
        ->middleware('permiso:inscripcion_crear')
        ->name('inscripciones.continuar');

    Route::livewire('/inscripciones/{inscripcion}/detalle', 'pages::inscripciones.inscripcion-detalle')
        ->middleware('permiso:inscripcion_indice')
        ->name('inscripciones.detalle');

    Route::livewire('/inscripciones/pagos', 'pages::inscripciones.pagos')
        ->middleware('permiso:pago_indice')
        ->name('pagos.index');

    Route::livewire('/inscripciones/pagos/validar', 'pages::inscripciones.pagos-validar')
        ->middleware('permiso:pago_crear')
        ->name('pagos.validar');

    // Reportes
    Route::livewire('/reportes/inscripciones', 'pages::reportes.inscripciones')
        ->middleware('permiso:reporte_inscripcion_indice')
        ->name('reportes.inscripciones.index');
});
