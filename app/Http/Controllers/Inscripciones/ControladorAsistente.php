<?php

namespace App\Http\Controllers\Inscripciones;

use App\Http\Controllers\Controller;
use App\Support\Inscripcion\AsistenteInscripcion;
use App\Support\Inscripcion\SesionAsistente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Flujo común del asistente de inscripción (personal y portal): cada cambio de un campo
 * o acción se envía al servidor, que aplica las mismas reglas de los traits y guarda el
 * avance en la sesión.
 */
abstract class ControladorAsistente extends Controller
{
    /** Eventos del asistente de la última acción (avisos y preguntas), para la pantalla. */
    public const CLAVE_EVENTOS = 'asistente.eventos';

    public const CLAVE_REGLAS = 'asistente.reglas';

    public const CLAVE_PREGUNTA = 'asistente.pregunta';

    public function __construct(protected SesionAsistente $sesion) {}

    /**
     * personal o portal.
     */
    abstract protected function modo(): string;

    /**
     * @return class-string<AsistenteInscripcion>
     */
    abstract protected function clase(): string;

    abstract protected function rutaAsistente(string $token): string;

    abstract protected function rutaInicio(): string;

    abstract protected function pagina(): string;

    /**
     * Acción que responde a cada pregunta del asistente (por ejemplo, el aviso de duplicado).
     *
     * @return array<string, string>
     */
    protected function accionesDePregunta(): array
    {
        return [];
    }

    protected function abrir(AsistenteInscripcion $asistente): RedirectResponse
    {
        return redirect($this->rutaAsistente($this->sesion->abrir($this->modo(), $asistente)));
    }

    public function mostrar(Request $request, string $token): Response|RedirectResponse
    {
        $asistente = $this->sesion->cargar($this->modo(), $token, $this->clase());

        if ($asistente === null) {
            return redirect($this->rutaInicio());
        }

        return Inertia::render($this->pagina(), [
            'token' => $token,
            'asistente' => $asistente->vista(),
            'pregunta' => $request->session()->get(self::CLAVE_PREGUNTA),
        ]);
    }

    public function actualizar(Request $request, string $token): RedirectResponse
    {
        $datos = $request->validate([
            'cambios' => ['array'],
            'cambios.*.ruta' => ['required', 'string', 'max:100'],
            'cambios.*.valor' => ['nullable'],
            'accion' => ['nullable', 'string', 'max:60'],
            'parametros' => ['array'],
        ]);

        $asistente = $this->sesion->cargar($this->modo(), $token, $this->clase());

        if ($asistente === null) {
            return redirect($this->rutaInicio());
        }

        foreach ($datos['cambios'] ?? [] as $cambio) {
            $asistente->establecer($cambio['ruta'], $cambio['valor'] ?? null);
        }

        if (filled($datos['accion'] ?? null)) {
            $asistente->ejecutar($datos['accion'], array_values($datos['parametros'] ?? []));
        }

        return $this->responder($request, $token, $asistente);
    }

    public function foto(Request $request, string $token): RedirectResponse
    {
        $asistente = $this->sesion->cargar($this->modo(), $token, $this->clase());

        if ($asistente === null) {
            return redirect($this->rutaInicio());
        }

        $asistente->subirFoto($request->file('foto'));

        return $this->responder($request, $token, $asistente);
    }

    private function responder(Request $request, string $token, AsistenteInscripcion $asistente): RedirectResponse
    {
        $request->session()->flash(self::CLAVE_EVENTOS, $asistente->eventos());
        $request->session()->flash(self::CLAVE_REGLAS, $asistente->reglasFallidas());

        if (($aviso = $asistente->ultimoAviso()) !== null) {
            Inertia::flash('toast', $aviso);
        }

        $pregunta = collect($asistente->eventos())->last(fn (array $evento): bool => $evento['nombre'] === 'alert');

        if ($pregunta !== null) {
            $parametros = $pregunta['parametros'];
            $request->session()->flash(self::CLAVE_PREGUNTA, [
                'titulo' => $parametros['title'] ?? 'Confirmación requerida',
                'mensaje' => $parametros['message'] ?? '',
                'textoConfirmar' => $parametros['confirmText'] ?? 'Continuar',
                'textoCancelar' => $parametros['cancelText'] ?? 'Cancelar',
                'confirmar' => $this->accionesDePregunta()[$parametros['confirmEvent'] ?? ''] ?? null,
                'cancelar' => $this->accionesDePregunta()[$parametros['cancelEvent'] ?? ''] ?? null,
            ]);
        }

        if (($destino = $asistente->redireccion()) !== null) {
            $this->sesion->cerrar($this->modo(), $token);

            return redirect($destino);
        }

        $this->sesion->guardar($this->modo(), $token, $asistente);

        $volver = redirect($this->rutaAsistente($token));

        return $asistente->errores()->isEmpty() ? $volver : $volver->withErrors($asistente->errores());
    }
}
