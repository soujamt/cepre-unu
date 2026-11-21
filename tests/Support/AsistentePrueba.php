<?php

namespace Tests\Support;

use App\Http\Controllers\Inscripciones\ControladorAsistente;
use App\Support\Inscripcion\AsistenteInscripcion;
use App\Support\Inscripcion\AsistentePersonal;
use App\Support\Inscripcion\AsistentePortal;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Inertia\Support\SessionKey;
use PHPUnit\Framework\Assert;

/**
 * Maneja el asistente de inscripción por HTTP con la misma forma que Livewire::test():
 * set() envía el cambio del campo (y su reacción) como lo haría la pantalla, call() pide
 * una acción y las aserciones leen el estado guardado en la sesión y la última respuesta.
 *
 * Los datos que el navegador no puede cambiar (paso actual, persona, inscripción…) se
 * preparan escribiéndolos directamente en la sesión, como arreglo de la prueba; para
 * comprobar que el servidor los rechaza se usa intentarCambiar().
 *
 * $prueba es el TestCase de la prueba (o el proxy que devuelve test()).
 */
class AsistentePrueba
{
    private ?TestResponse $respuesta = null;

    private function __construct(private object $prueba, private string $modo, private string $token) {}

    public static function personal(object $prueba, ?string $inscripcion = null): self
    {
        $respuesta = $prueba->get($inscripcion === null ? route('inscripciones.crear') : route('inscripciones.continuar', $inscripcion));
        $respuesta->assertRedirect();

        return new self($prueba, 'personal', Str::afterLast((string) $respuesta->headers->get('Location'), '/'));
    }

    public static function portal(object $prueba): self
    {
        $respuesta = $prueba->get(route('publico.inscripcion'));
        $respuesta->assertRedirect();

        return new self($prueba, 'portal', Str::afterLast((string) $respuesta->headers->get('Location'), '/'));
    }

    public function set(string|array $ruta, mixed $valor = null): self
    {
        if (is_array($ruta)) {
            foreach ($ruta as $clave => $dato) {
                $this->set($clave, $dato);
            }

            return $this;
        }

        if ($ruta === 'fotoPostulante') {
            $this->respuesta = $this->prueba->post(route('inscripciones.asistente.foto', $this->token), ['foto' => $valor]);

            return $this;
        }

        if ($this->asistente()->esEditable($ruta)) {
            $this->respuesta = $this->prueba->post($this->rutaActualizar(), ['cambios' => [['ruta' => $ruta, 'valor' => $valor]]]);

            return $this;
        }

        $estado = $this->estado();
        data_set($estado, $ruta, $valor);
        session()->put("asistentes.{$this->modo}.{$this->token}", $estado);

        return $this;
    }

    /**
     * Envía el cambio por HTTP aunque el dato no sea editable (para probar que se rechaza).
     */
    public function intentarCambiar(string $ruta, mixed $valor): self
    {
        $this->respuesta = $this->prueba->post($this->rutaActualizar(), ['cambios' => [['ruta' => $ruta, 'valor' => $valor]]]);

        return $this;
    }

    public function call(string $accion, mixed ...$parametros): self
    {
        $this->respuesta = $this->prueba->post($this->rutaActualizar(), ['accion' => $accion, 'parametros' => $parametros]);

        return $this;
    }

    public function get(string $propiedad): mixed
    {
        return $this->asistente()->{$propiedad};
    }

    public function assertSet(string $ruta, mixed $esperado): self
    {
        Assert::assertEquals($esperado, data_get($this->estado(), $ruta), "El dato {$ruta} del asistente no coincide.");

        return $this;
    }

    public function assertHasNoErrors(): self
    {
        $this->respuesta?->assertSessionHasNoErrors();

        return $this;
    }

    /**
     * @param  string|array<int|string, string>  $claves  Campos, o campo => regla que debe fallar.
     */
    public function assertHasErrors(string|array $claves = []): self
    {
        Assert::assertNotNull($this->respuesta, 'No hubo una solicitud al asistente.');
        $reglas = session(ControladorAsistente::CLAVE_REGLAS, []);

        foreach ((array) $claves as $clave => $regla) {
            if (is_int($clave)) {
                $this->respuesta->assertSessionHasErrors($regla);

                continue;
            }

            $this->respuesta->assertSessionHasErrors($clave);
            Assert::assertContains(Str::snake(Str::before($regla, ':')), $reglas[$clave] ?? [], "La regla {$regla} de {$clave} no falló.");
        }

        if ($claves === [] || $claves === '') {
            $this->respuesta->assertSessionHasErrors();
        }

        return $this;
    }

    /**
     * @param  array<string, mixed>  $parametros
     */
    public function assertDispatched(string $evento, mixed ...$parametros): self
    {
        $coincide = collect(session(ControladorAsistente::CLAVE_EVENTOS, []))->contains(
            fn (array $despachado): bool => $despachado['nombre'] === $evento
                && collect($parametros)->every(fn (mixed $valor, string|int $clave): bool => ($despachado['parametros'][$clave] ?? null) == $valor),
        );

        Assert::assertTrue($coincide, "No se emitió {$evento} con ".json_encode($parametros, JSON_UNESCAPED_UNICODE).'. Emitidos: '.json_encode(session(ControladorAsistente::CLAVE_EVENTOS, []), JSON_UNESCAPED_UNICODE));

        return $this;
    }

    public function assertRedirect(?string $url = null): self
    {
        Assert::assertNotNull($this->respuesta, 'No hubo una solicitud al asistente.');
        $this->respuesta->assertRedirect($url);

        if ($url !== null) {
            Assert::assertNotSame($this->rutaMostrar(), $url);
        }

        return $this;
    }

    public function assertForbidden(): self
    {
        Assert::assertNotNull($this->respuesta, 'No hubo una solicitud al asistente.');
        $this->respuesta->assertForbidden();

        return $this;
    }

    /**
     * El texto no llega al navegador en los datos de la pantalla.
     */
    public function assertDontSee(string $texto): self
    {
        Assert::assertStringNotContainsString($texto, json_encode($this->vista(), JSON_UNESCAPED_UNICODE));

        return $this;
    }

    public function assertToast(string $mensaje, ?string $tipo = null): self
    {
        $aviso = session(SessionKey::FLASH_DATA.'.toast');

        Assert::assertSame($mensaje, $aviso['message'] ?? null);

        if ($tipo !== null) {
            Assert::assertSame($tipo, $aviso['type'] ?? null);
        }

        return $this;
    }

    /**
     * Props de la pantalla de React para el estado actual.
     *
     * @return array<string, mixed>
     */
    public function vista(): array
    {
        return $this->asistente()->vista();
    }

    public function respuesta(): ?TestResponse
    {
        return $this->respuesta;
    }

    public function token(): string
    {
        return $this->token;
    }

    /**
     * @return array<string, mixed>
     */
    private function estado(): array
    {
        $estado = session("asistentes.{$this->modo}.{$this->token}");
        Assert::assertIsArray($estado, 'El asistente ya no está abierto en la sesión.');

        return $estado;
    }

    private function asistente(): AsistenteInscripcion
    {
        $clase = $this->modo === 'portal' ? AsistentePortal::class : AsistentePersonal::class;

        return $clase::desdeEstado($this->estado());
    }

    private function rutaActualizar(): string
    {
        return $this->modo === 'portal' ? route('publico.inscripcion.actualizar', $this->token) : route('inscripciones.asistente.actualizar', $this->token);
    }

    private function rutaMostrar(): string
    {
        return $this->modo === 'portal' ? route('publico.inscripcion.asistente', $this->token) : route('inscripciones.asistente', $this->token);
    }
}
