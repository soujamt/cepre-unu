<?php

namespace App\Http\Requests\Auth;

use App\Enums\EstadoUsuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class LoginRequest extends FormRequest
{
    private const MAXIMO_INTENTOS = 5;

    private const SEGUNDOS_BLOQUEO = 60;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'correo' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'correo.required' => 'El correo electrónico es obligatorio.',
            'correo.email' => 'Ingresa un correo electrónico válido.',
            'password.required' => 'La contraseña es obligatoria.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['correo' => Str::lower(trim((string) $this->input('correo')))]);
    }

    /**
     * Inicia sesión solo con usuarios habilitados. Tras cinco intentos fallidos con el
     * mismo correo e IP bloquea el acceso un minuto y avisa cuántos segundos faltan.
     *
     * @throws ValidationException
     */
    public function autenticar(): void
    {
        $this->asegurarQueNoEsteBloqueado();

        $autenticado = Auth::attempt([
            'correo_usu' => $this->string('correo')->toString(),
            'password' => $this->string('password')->toString(),
            'estado_usu' => EstadoUsuario::Habilitado->value,
        ]);

        if (! $autenticado) {
            RateLimiter::hit($this->claveIntentos(), self::SEGUNDOS_BLOQUEO);

            throw ValidationException::withMessages([
                'correo' => 'Las credenciales ingresadas no son válidas.',
            ]);
        }

        RateLimiter::clear($this->claveIntentos());
    }

    /**
     * @throws ValidationException
     */
    private function asegurarQueNoEsteBloqueado(): void
    {
        if (! RateLimiter::tooManyAttempts($this->claveIntentos(), self::MAXIMO_INTENTOS)) {
            return;
        }

        $segundos = RateLimiter::availableIn($this->claveIntentos());

        Inertia::flash('segundosBloqueo', $segundos);

        throw ValidationException::withMessages([
            'lockout' => "Demasiados intentos. Intenta nuevamente en {$segundos} segundos.",
        ]);
    }

    private function claveIntentos(): string
    {
        return Str::transliterate(Str::lower($this->string('correo')->toString())).'|'.$this->ip();
    }
}
