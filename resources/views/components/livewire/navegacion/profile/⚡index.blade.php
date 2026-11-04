<?php

use App\Models\Usuario;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
    public bool $isNavbar = false;

    public string $name = '';

    public string $email = '';

    public string $iniciales = '';

    public function mount(bool $isNavbar = false): void
    {
        $this->isNavbar = $isNavbar;

        /** @var Usuario|null $usuario */
        $usuario = Auth::user();

        $this->name = Str::title(mb_strtolower($usuario?->nombres_usu ?: 'Usuario', 'UTF-8'));
        $this->email = $this->ocultarCorreo($usuario?->correo_usu ?: '');
        $this->iniciales = $this->obtenerIniciales($this->name);
    }

    public function obtenerIniciales(string $nombre): string
    {
        $palabras = collect(preg_split('/\s+/u', trim($nombre)))->filter()->values();

        return mb_strtoupper(
            $palabras->take(2)->map(fn (string $palabra): string => mb_substr($palabra, 0, 1))->implode(''),
            'UTF-8',
        );
    }

    public function ocultarCorreo(string $correo): string
    {
        if ($correo === '') {
            return '';
        }

        $partes = explode('@', $correo);

        if (count($partes) !== 2) {
            return $correo;
        }

        [$nombreUsuario, $dominio] = $partes;

        if (strlen($nombreUsuario) <= 2) {
            $nombreOculto = str_repeat('*', strlen($nombreUsuario));
        } else {
            $nombreOculto = substr($nombreUsuario, 0, 2).str_repeat('*', strlen($nombreUsuario) - 2);
        }

        return $nombreOculto.'@'.$dominio;
    }

    public function logout(): void
    {
        Auth::guard('web')->logout();

        session()->invalidate();
        session()->regenerateToken();

        $this->dispatch('toast', message: 'Sesión cerrada correctamente', type: 'success');

        $this->redirect(route('login'), navigate: true);
    }
};
?>

<div class="w-full">
    @if ($isNavbar)
    <flux:dropdown position="bottom" align="end">
        <flux:profile :chevron="true" :initials="$iniciales" avatar:color="blue" circle />
        <flux:navmenu class="max-w-lg">
            <div class="flex items-center gap-3 px-2 py-1.5">
                <flux:avatar size="sm" circle color="blue" :initials="$iniciales" badge badge:color="green" badge:circle />
                <div>
                    <flux:text>Bienvenido nuevamente</flux:text>
                    <flux:text variant="subtle">{{ $email }}</flux:text>
                </div>
            </div>
            <flux:navmenu.separator />
            <flux:button variant="primary" color="red" class="w-full" wire:click="logout">
                <x-slot name="icon">
                    <x-ui.icon name="power" size="5" class="text-white" />
                </x-slot>
                Cerrar sesión
            </flux:button>
        </flux:navmenu>
    </flux:dropdown>
    @else
    <div class="flex items-center justify-between gap-2">
        <div class="flex min-w-0 items-center gap-2.5">
            <flux:avatar size="sm" circle color="blue" :initials="$iniciales" badge badge:color="green" badge:circle />
            <div class="min-w-0 leading-tight">
                <p class="truncate text-sm font-medium text-zinc-900 dark:text-white">{{ $name }}</p>
                <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $email }}</p>
            </div>
        </div>
        <flux:tooltip content="Cerrar sesión">
            <flux:button variant="subtle" size="sm" square wire:click="logout" aria-label="Cerrar sesión">
                <x-slot name="icon">
                    <x-ui.icon name="power" size="5" class="text-zinc-400 hover:text-red-500 dark:text-zinc-500" />
                </x-slot>
            </flux:button>
        </flux:tooltip>
    </div>
    @endif
</div>