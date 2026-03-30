<?php

use App\Enums\EstadoUsuario;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.auth')]
#[Title('INICIO DE SESION')]
class extends Component
{
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
            'correo.required' => 'El correo electronico es obligatorio.',
            'correo.email' => 'Ingresa un correo electronico valido.',
            'password.required' => 'La contrasena es obligatoria.',
        ];
    }

    public function login(): void
    {
        $credentials = $this->validate();

        $authenticated = Auth::attempt([
            'correo_usu' => $credentials['correo'],
            'password' => $credentials['password'],
            'estado_usu' => EstadoUsuario::Habilitado->value,
        ]);

        if (! $authenticated) {
            throw ValidationException::withMessages([
                'correo' => 'Las credenciales ingresadas no son validas.',
            ]);
        }

        session()->regenerate();

        $this->dispatch('toast', message: 'Bienvenido de nuevo', type: 'success');

        $this->redirectIntended(route('inicio'), navigate: true);
    }
};