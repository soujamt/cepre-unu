<?php

use App\Support\Drive\SincronizadorDrive;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Mantiene al día las hojas DATA y PAGOS del Drive mientras el sistema está encendido
 * (en la PC Windows lo ejecuta "php artisan schedule:work", que abre iniciar-red-local).
 */
Schedule::command('cepre:sincronizar-drive')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->when(fn (): bool => app(SincronizadorDrive::class)->configurado());
