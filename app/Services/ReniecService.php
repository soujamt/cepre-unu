<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReniecService
{
    /**
     * @return array{
     *     success: bool,
     *     origen?: string,
     *     data?: array{
     *         numero: string,
     *         nombres: string,
     *         apellido_paterno: string,
     *         apellido_materno: string,
     *         nombre_completo?: string
     *     },
     *     message?: string
     * }
     */
    public function consultar(string $dni): array
    {
        $dni = preg_replace('/\D+/', '', $dni) ?? '';

        if (strlen($dni) !== 8) {
            return [
                'success' => false,
                'message' => 'El DNI debe tener 8 dígitos.',
            ];
        }

        $cacheKey = "reniec_{$dni}";
        $cached = Cache::get($cacheKey);

        if (is_array($cached) && ($cached['success'] ?? false) === true) {
            return $cached;
        }

        $baseUrl = (string) config('services.reniec.url');

        if ($baseUrl === '') {
            return [
                'success' => false,
                'message' => 'No se configuró URL_API_RENIEC.',
            ];
        }

        $token = trim((string) config('services.reniec.token'));

        if ($token === '') {
            return [
                'success' => false,
                'message' => 'No se configuró el token del servicio RENIEC.',
            ];
        }

        try {
            $request = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
                ->timeout(10)
                ->retry(2, 100);

            if (app()->isLocal() && (bool) config('services.reniec.allow_insecure_ssl', false)) {
                $request = $request->withoutVerifying();
            }

            $response = $request->get(rtrim($baseUrl, '/').'/v1/reniec/dni', [
                'numero' => $dni,
            ]);

            $json = $response->json();

            if ($response->successful() && is_array($json)) {
                if (! empty($json['document_number'])) {
                    $result = [
                        'success' => true,
                        'origen' => 'API',
                        'data' => [
                            'numero' => (string) ($json['document_number'] ?? $dni),
                            'nombres' => (string) ($json['first_name'] ?? ''),
                            'apellido_paterno' => (string) ($json['first_last_name'] ?? ''),
                            'apellido_materno' => (string) ($json['second_last_name'] ?? ''),
                            'nombre_completo' => (string) ($json['full_name'] ?? ''),
                        ],
                    ];

                    Cache::put($cacheKey, $result, now()->addDay());

                    return $result;
                }

                if (isset($json['success']) && $json['success']) {
                    $json['origen'] = 'API';
                    Cache::put($cacheKey, $json, now()->addDay());

                    return $json;
                }
            }

            if (is_array($json) && ! empty($json['error'])) {
                return [
                    'success' => false,
                    'message' => $this->traducirMensajeProveedor((string) $json['error']),
                ];
            }

            if (is_array($json) && ! empty($json['message'])) {
                return [
                    'success' => false,
                    'message' => $this->traducirMensajeProveedor((string) $json['message']),
                ];
            }

            if ($response->status() === 404) {
                return [
                    'success' => false,
                    'message' => 'No se encontraron datos en RENIEC.',
                ];
            }

            Log::warning('Respuesta no esperada RENIEC', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);

            return [
                'success' => false,
                'message' => 'Error consultando RENIEC.',
            ];
        } catch (RequestException $exception) {
            $json = $exception->response?->json();

            if (is_array($json) && ! empty($json['error'])) {
                return [
                    'success' => false,
                    'message' => $this->traducirMensajeProveedor((string) $json['error']),
                ];
            }

            if (is_array($json) && ! empty($json['message'])) {
                return [
                    'success' => false,
                    'message' => $this->traducirMensajeProveedor((string) $json['message']),
                ];
            }

            Log::error('Excepción RENIEC: '.$exception->getMessage());

            return [
                'success' => false,
                'message' => app()->isLocal() ? $exception->getMessage() : 'Error de conexión con el servicio.',
            ];
        } catch (Throwable $exception) {
            Log::error('Excepción RENIEC: '.$exception->getMessage());

            return [
                'success' => false,
                'message' => app()->isLocal() ? $exception->getMessage() : 'Error de conexión con el servicio.',
            ];
        }
    }

    private function traducirMensajeProveedor(string $mensaje): string
    {
        $mensajeNormalizado = mb_strtolower(trim($mensaje), 'UTF-8');

        if (
            str_contains($mensajeNormalizado, 'apikey required / limit exceeded')
            || str_contains($mensajeNormalizado, 'api key required / limit exceeded')
        ) {
            return 'El servicio RENIEC no está disponible en este momento por configuración o límite de consultas.';
        }

        if (str_contains($mensajeNormalizado, 'apikey required') || str_contains($mensajeNormalizado, 'api key required')) {
            return 'El servicio RENIEC no tiene una API key válida configurada.';
        }

        if (str_contains($mensajeNormalizado, 'limit exceeded')) {
            return 'El servicio RENIEC alcanzó su límite de consultas. Intente nuevamente más tarde.';
        }

        if (str_contains($mensajeNormalizado, 'invalid request')) {
            return 'La consulta enviada a RENIEC no es válida.';
        }

        return $mensaje;
    }
}
