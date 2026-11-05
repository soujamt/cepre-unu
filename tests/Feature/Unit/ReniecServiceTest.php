<?php

use App\Services\ReniecService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    Cache::flush();

    Config::set('services.reniec.url', 'https://api.decolecta.com');
    Config::set('services.reniec.token', 'token-prueba');
    Config::set('services.reniec.allow_insecure_ssl', false);
});

it('consults reniec and maps the current decolecta response', function () {
    Http::fake([
        'https://api.decolecta.com/v1/reniec/dni*' => Http::response([
            'first_name' => 'ROXANA KARINA',
            'first_last_name' => 'DELGADO',
            'second_last_name' => 'HUAMANI',
            'full_name' => 'DELGADO HUAMANI ROXANA KARINA',
            'document_number' => '46027897',
        ]),
    ]);

    $result = app(ReniecService::class)->consultar('46027897');

    expect($result['success'])->toBeTrue()
        ->and($result['origen'])->toBe('API')
        ->and($result['data']['numero'])->toBe('46027897')
        ->and($result['data']['nombres'])->toBe('ROXANA KARINA')
        ->and($result['data']['apellido_paterno'])->toBe('DELGADO')
        ->and($result['data']['apellido_materno'])->toBe('HUAMANI')
        ->and($result['data']['nombre_completo'])->toBe('DELGADO HUAMANI ROXANA KARINA');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.decolecta.com/v1/reniec/dni?numero=46027897'
            && $request->hasHeader('Authorization', 'Bearer token-prueba');
    });
});

it('uses cache for successful queries', function () {
    Http::fake([
        'https://api.decolecta.com/v1/reniec/dni*' => Http::response([
            'first_name' => 'ROXANA KARINA',
            'first_last_name' => 'DELGADO',
            'second_last_name' => 'HUAMANI',
            'full_name' => 'DELGADO HUAMANI ROXANA KARINA',
            'document_number' => '46027897',
        ]),
    ]);

    $service = app(ReniecService::class);

    $first = $service->consultar('46027897');
    $second = $service->consultar('46027897');

    expect($first['success'])->toBeTrue()
        ->and($second['success'])->toBeTrue();

    Http::assertSentCount(1);
});

it('returns validation error when the dni is invalid', function () {
    $result = app(ReniecService::class)->consultar('123');

    expect($result)->toBe([
        'success' => false,
        'message' => 'El DNI debe tener 8 dígitos.',
    ]);
});

it('hides missing configuration from the user and logs it', function (string $clave) {
    Log::spy();
    Config::set("services.reniec.{$clave}", null);

    $result = app(ReniecService::class)->consultar('46027897');

    expect($result)->toBe([
        'success' => false,
        'message' => ReniecService::MENSAJE_NO_DISPONIBLE,
        'disponible' => false,
    ]);

    Log::shouldHaveReceived('warning')->once();
})->with(['url', 'token']);

it('returns a friendly message when the api responds with invalid request', function () {
    Http::fake([
        'https://api.decolecta.com/v1/reniec/dni*' => Http::response([
            'error' => 'Invalid request',
        ], 400),
    ]);

    $result = app(ReniecService::class)->consultar('46027897');

    expect($result)->toBe([
        'success' => false,
        'message' => 'RENIEC no pudo procesar la consulta. Verifique el número de DNI.',
    ]);
});

it('hides the provider api key error from the user', function () {
    Log::spy();
    Http::fake([
        'https://api.decolecta.com/v1/reniec/dni*' => Http::response([
            'error' => 'Apikey Required / Limit Exceeded',
        ], 400),
    ]);

    $result = app(ReniecService::class)->consultar('46027897');

    expect($result)->toBe([
        'success' => false,
        'message' => ReniecService::MENSAJE_NO_DISPONIBLE,
        'disponible' => false,
    ]);

    Log::shouldHaveReceived('warning')->withArgs(fn (string $mensaje): bool => str_contains($mensaje, 'Apikey Required'))->once();
});

it('returns a generic connection message without technical details when the request fails', function () {
    Http::fake([
        'https://api.decolecta.com/v1/reniec/dni*' => Http::response('Internal Server Error', 500),
    ]);

    $result = app(ReniecService::class)->consultar('46027897');

    expect($result)->toBe([
        'success' => false,
        'message' => ReniecService::MENSAJE_SIN_CONEXION,
        'disponible' => false,
    ]);
});
