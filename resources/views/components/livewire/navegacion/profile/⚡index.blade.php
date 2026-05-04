<?php

use App\Models\Usuario;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public bool $isNavbar = false;

    public string $name = '';

    public string $email = '';

    public string $avatarUrl = '';

    public function mount(bool $isNavbar = false): void
    {
        $this->isNavbar = $isNavbar;

        /** @var Usuario|null $usuario */
        $usuario = Auth::user();

        $this->name = $usuario?->nombres_usu ?: 'Usuario';
        $this->email = $this->ocultarCorreo($usuario?->correo_usu ?: '');
        $this->avatarUrl = $usuario?->avatar_url ?: '';
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
        <flux:profile :chevron="true" :avatar="$avatarUrl" />
        <flux:navmenu class="max-w-lg">
            <div class="flex items-center gap-3 px-2 py-1.5">
                <flux:avatar size="sm" badge badge:color="green" badge:circle :src="$avatarUrl" />
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
                Cerrar sesion
            </flux:button>
        </flux:navmenu>
    </flux:dropdown>
    @else
    <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <flux:avatar size="sm" badge badge:color="green" badge:circle :src="$avatarUrl" />
            <div>
                <flux:text>{{ $name }}</flux:text>
                <flux:text variant="subtle" class="text-xs">{{ $email }}</flux:text>
            </div>
        </div>
        <flux:button variant="ghost" size="sm" color="red" wire:click="logout">
            <x-slot name="icon">
                <x-ui.icon name="power" size="5" class="text-zinc-500 dark:text-zinc-400" />
            </x-slot>
        </flux:button>
    </div>
    @endif
</div>