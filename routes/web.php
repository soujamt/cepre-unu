<?php

use App\Http\Controllers\Api\ColegioSearchController;
use App\Http\Controllers\Api\UbigeoSearchController;
use App\Http\Controllers\Auth\SesionController;
use App\Http\Controllers\DocumentoInscripcionController;
use App\Http\Controllers\FotoPostulanteController;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\Mantenedores\AulaController;
use App\Http\Controllers\Mantenedores\CarreraController;
use App\Http\Controllers\Mantenedores\ColegioController;
use App\Http\Controllers\Mantenedores\ConceptoPagoController;
use App\Http\Controllers\Mantenedores\ModalidadController;
use App\Http\Controllers\Mantenedores\SedeController;
use App\Http\Controllers\Mantenedores\TurnoController;
use App\Http\Controllers\Publico\PortalController;
use App\Http\Controllers\Seguridad\PermisoController;
use App\Http\Controllers\Seguridad\RolController;
use App\Http\Controllers\Seguridad\RolPermisosController;
use App\Http\Controllers\Seguridad\UsuarioController;
use App\Http\Controllers\VerificacionCarnetController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/auth', '/auth/login');
Route::middleware('guest')->group(function (): void {
    Route::get('/auth/login', [SesionController::class, 'create'])->name('login');
    Route::post('/auth/login', [SesionController::class, 'store'])->name('login.store');
});

Route::post('/auth/logout', [SesionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// Catálogo de componentes de la interfaz React, solo para desarrollo.
if (app()->environment(['local', 'testing'])) {
    Route::get('/dev/componentes', fn () => Inertia::render('dev/componentes'))
        ->middleware('auth')
        ->name('dev.componentes');
}

Route::get('/verificar/{token}', VerificacionCarnetController::class)
    ->middleware('throttle:30,1')
    ->where('token', '[A-Za-z0-9]{40}')
    ->name('carnet.verificar');

// Portal del postulante: sin iniciar sesión.
Route::middleware('throttle:60,1')->group(function (): void {
    Route::get('/api/ubigeos/search', UbigeoSearchController::class)
        ->name('api.ubigeo.search');

    Route::get('/api/colegios/search', ColegioSearchController::class)
        ->name('api.colegio.search');
});

Route::get('/postulante', [PortalController::class, 'portal'])->name('publico.portal');
Route::livewire('/postulante/inscripcion', 'pages::publico.inscripcion')->name('publico.inscripcion');
Route::get('/postulante/consulta', [PortalController::class, 'consulta'])->name('publico.consulta');
Route::post('/postulante/consulta', [PortalController::class, 'buscar'])->name('publico.consulta.buscar');

Route::middleware(['signed', 'throttle:30,1'])->group(function (): void {
    Route::get('/postulante/{inscripcion}/ficha', [DocumentoInscripcionController::class, 'fichaPublica'])
        ->name('publico.ficha');

    Route::get('/postulante/{inscripcion}/carnet', [DocumentoInscripcionController::class, 'carnetPublico'])
        ->name('publico.carnet');
});

Route::redirect('/', '/inicio');
Route::middleware('auth')->group(function (): void {
    // Dashboard
    Route::get('/inicio', InicioController::class)->name('inicio');

    // Seguridad
    Route::catalogo('/seguridad/usuarios', 'usuarios', 'usuario', UsuarioController::class, 'usuario');

    Route::catalogo('/seguridad/roles', 'roles', 'rol', RolController::class, 'rol');

    Route::get('/seguridad/roles/{rol}/permisos', [RolPermisosController::class, 'edit'])
        ->middleware('permiso:rol_permisos')
        ->name('roles.permisos');

    Route::put('/seguridad/roles/{rol}/permisos', [RolPermisosController::class, 'update'])
        ->middleware('permiso:rol_permisos')
        ->name('roles.permisos.update');

    Route::catalogo('/seguridad/permisos', 'permisos', 'permiso', PermisoController::class, 'permiso');

    // Mantenedores
    Route::catalogo('/mantenedores/aulas', 'mantenedor-aulas', 'aula', AulaController::class, 'aula');

    Route::catalogo('/mantenedores/carreras', 'carreras', 'carrera', CarreraController::class, 'carrera');

    Route::catalogo('/mantenedores/conceptos-pago', 'conceptos-pago', 'concepto_pago', ConceptoPagoController::class, 'concepto');

    Route::catalogo('/mantenedores/modalidades', 'modalidades', 'modalidad', ModalidadController::class, 'modalidad');

    Route::catalogo('/mantenedores/sedes', 'sedes', 'sede', SedeController::class, 'sede');

    Route::catalogo('/mantenedores/colegios', 'mantenedor-colegios', 'colegio', ColegioController::class, 'colegio');

    Route::catalogo('/mantenedores/turnos', 'turnos', 'turno', TurnoController::class, 'turno');

    // Configuración Académica
    Route::livewire('/configuracion-academica/procesos', 'pages::configuracion-academica.procesos')
        ->middleware('permiso:proceso_indice')
        ->name('procesos.index');

    Route::livewire('/configuracion-academica/ofertas', 'pages::configuracion-academica.ofertas')
        ->middleware('permiso:oferta_indice')
        ->name('ofertas.index');

    Route::livewire('/configuracion-academica/aulas', 'pages::configuracion-academica.aulas-proceso')
        ->middleware('permiso:aula_proceso_indice')
        ->name('aulas.index');

    Route::livewire('/configuracion-academica/aulas/asignar', 'pages::configuracion-academica.aulas-proceso-asignar')
        ->middleware('permiso:aula_proceso_crear')
        ->name('aulas.asignar');

    Route::livewire('/configuracion-academica/codigos-admision', 'pages::configuracion-academica.codigos-admision')
        ->middleware('permiso:codigo_admision_indice')
        ->name('codigos-admision.index');

    Route::livewire('/configuracion-academica/carnet', 'pages::configuracion-academica.carnet')
        ->middleware('permiso:parametro_indice')
        ->name('carnet.configuracion');

    Route::get('/configuracion-academica/carnet/muestra', [DocumentoInscripcionController::class, 'muestra'])
        ->middleware('permiso:parametro_indice')
        ->name('carnet.muestra');

    Route::livewire('/configuracion-academica/reglas-pago', 'pages::configuracion-academica.reglas-pago')
        ->middleware('permiso:regla_pago_indice')
        ->name('reglas-pago.index');

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

    Route::get('/inscripciones/{inscripcion}/ficha', [DocumentoInscripcionController::class, 'ficha'])
        ->middleware('permiso:inscripcion_indice')
        ->name('inscripciones.ficha');

    Route::get('/inscripciones/{inscripcion}/carnet', [DocumentoInscripcionController::class, 'carnet'])
        ->middleware('permiso:inscripcion_indice')
        ->name('inscripciones.carnet');

    Route::livewire('/inscripciones/fotos', 'pages::inscripciones.fotos')
        ->middleware('permiso:foto_indice')
        ->name('fotos.index');

    Route::get('/fotos/{documento}', FotoPostulanteController::class)
        ->where('documento', '\d{8,12}')
        ->name('fotos.ver');

    Route::livewire('/inscripciones/pagos', 'pages::inscripciones.pagos-lista')
        ->middleware('permiso:pago_indice')
        ->name('pagos.index');

    Route::livewire('/inscripciones/pagos/importaciones', 'pages::inscripciones.pagos')
        ->middleware('permiso:pago_indice')
        ->name('pagos.importaciones');

    Route::livewire('/inscripciones/pagos/validar', 'pages::inscripciones.pagos-validar')
        ->middleware('permiso:pago_crear')
        ->name('pagos.validar');

    // Reportes
    Route::livewire('/reportes/inscripciones', 'pages::reportes.inscripciones')
        ->middleware('permiso:reporte_inscripcion_indice')
        ->name('reportes.inscripciones.index');
});
