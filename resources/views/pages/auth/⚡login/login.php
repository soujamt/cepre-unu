<?php

use App\Enums\EstadoUsuario;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.auth')]
#[Title('INICIO DE SESIÓN')]
class extends Component
{
    private const MAX_LOGIN_ATTEMPTS = 5;

    private const LOCKOUT_SECONDS = 60;

    public string $correo = '';

    public string $password = '';

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'correo' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'correo.required' => 'El correo electrónico es obligatorio.',
            'correo.email' => 'Ingresa un correo electrónico válido.',
            'password.required' => 'La contraseña es obligatoria.',
        ];
    }

    public function login(): void
    {
        $this->correo = Str::lower(trim($this->correo));

        $credentials = $this->validate();

        $this->ensureIsNotRateLimited();

        $authenticated = Auth::attempt([
            'correo_usu' => $credentials['correo'],
            'password' => $credentials['password'],
            'estado_usu' => EstadoUsuario::Habilitado->value,
        ]);

        if (! $authenticated) {
            RateLimiter::hit($this->throttleKey(), self::LOCKOUT_SECONDS);

            throw ValidationException::withMessages([
                'correo' => 'Las credenciales ingresadas no son válidas.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        session()->regenerate();

        $this->dispatch('toast', message: 'Bienvenido de nuevo', type: 'success');

        $this->redirectIntended(route('inicio'), navigate: true);
    }

    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_LOGIN_ATTEMPTS)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        $this->dispatch('rate-limited', segundos: $seconds);

        throw ValidationException::withMessages([
            'lockout' => 'rate_limited',
        ]);
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->correo)).'|'.request()->ip();
    }
};
