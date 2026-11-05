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
     * Mensaje para el usuario cuando la consulta no se puede hacer por
     * configuración del servidor o del proveedor; el detalle va al log.
     */
    public const MENSAJE_NO_DISPONIBLE = 'La consulta a RENIEC no está disponible en este momento. Complete los datos manualmente.';

    public const MENSAJE_SIN_CONEXION = 'No se pudo conectar con RENIEC. Intente nuevamente o complete los datos manualmente.';

    /**
     * "disponible" es false cuando el servicio no se pudo usar (configuración
     * o conexión), para mostrarlo como aviso y no como error del usuario.
     *
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
     *     message?: string,
     *     disponible?: bool
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
            return $this->noDisponible('Falta configurar URL_API_RENIEC.');
        }

        $token = trim((string) config('services.reniec.token'));

        if ($token === '') {
            return $this->noDisponible('Falta configurar el token del servicio RENIEC.');
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
                return $this->respuestaProveedor((string) $json['error']);
            }

            if (is_array($json) && ! empty($json['message'])) {
                return $this->respuestaProveedor((string) $json['message']);
            }

            if ($response->status() === 404) {
                return [
                    'success' => false,
                    'message' => 'No se encontraron datos en RENIEC.',
                ];
            }

            return $this->sinConexion('Respuesta no esperada de RENIEC (HTTP '.$response->status().'): '.mb_substr($response->body(), 0, 500));
        } catch (RequestException $exception) {
            $json = $exception->response?->json();

            if (is_array($json) && ! empty($json['error'])) {
                return $this->respuestaProveedor((string) $json['error']);
            }

            if (is_array($json) && ! empty($json['message'])) {
                return $this->respuestaProveedor((string) $json['message']);
            }

            return $this->sinConexion('Excepción RENIEC: '.$exception->getMessage());
        } catch (Throwable $exception) {
            return $this->sinConexion('Excepción RENIEC: '.$exception->getMessage());
        }
    }

    /**
     * Traduce el error del proveedor: los de configuración (API key, límite)
     * se muestran como "no disponible" y su texto original va al log.
     *
     * @return array{success: false, message: string, disponible?: false}
     */
    private function respuestaProveedor(string $mensaje): array
    {
        $mensajeNormalizado = mb_strtolower(trim($mensaje), 'UTF-8');

        if (str_contains($mensajeNormalizado, 'apikey required') || str_contains($mensajeNormalizado, 'api key required')) {
            return $this->noDisponible("El proveedor RENIEC respondió: {$mensaje}");
        }

        if (str_contains($mensajeNormalizado, 'limit exceeded')) {
            Log::warning("RENIEC alcanzó el límite de consultas: {$mensaje}");

            return [
                'success' => false,
                'message' => 'Se alcanzó el límite de consultas a RENIEC. Intente más tarde o complete los datos manualmente.',
                'disponible' => false,
            ];
        }

        if (str_contains($mensajeNormalizado, 'invalid request')) {
            return [
                'success' => false,
                'message' => 'RENIEC no pudo procesar la consulta. Verifique el número de DNI.',
            ];
        }

        return [
            'success' => false,
            'message' => $mensaje,
        ];
    }

    /**
     * @return array{success: false, message: string, disponible: false}
     */
    private function noDisponible(string $detalleTecnico): array
    {
        Log::warning("RENIEC no disponible: {$detalleTecnico}");

        return [
            'success' => false,
            'message' => self::MENSAJE_NO_DISPONIBLE,
            'disponible' => false,
        ];
    }

    /**
     * @return array{success: false, message: string, disponible: false}
     */
    private function sinConexion(string $detalleTecnico): array
    {
        Log::error($detalleTecnico);

        return [
            'success' => false,
            'message' => self::MENSAJE_SIN_CONEXION,
            'disponible' => false,
        ];
    }
}
