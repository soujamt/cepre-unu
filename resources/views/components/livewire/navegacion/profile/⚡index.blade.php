<?php

use Livewire\Component;

new class extends Component
{
    public bool $isNavbar = false;

    public string $name = '';

    public string $email = '';

    public string $avatarUrl = '';

    public function mount(bool $isNavbar = false)
    {
        $this->isNavbar = $isNavbar;

        // Simulación de datos de usuario
        $this->name = 'JAMT MENDOZA';
        $this->email = $this->ocultarCorreo('CORREO@UNU.EDU.PE');
        $this->avatarUrl = 'https://lh3.googleusercontent.com/a/ACg8ocLHWMJlKrDnPse79AkzQJ13tM4f-ZWu5D14xO8rqBRyweS9zlsl=s288-c-no';
    }

    public function ocultarCorreo($correo)
    {
        $partes = explode('@', $correo);
        if (count($partes) !== 2) {
            return $correo; // Retorna el correo original si no es válido
        }

        $nombreUsuario = $partes[0];
        $dominio = $partes[1];

        if (strlen($nombreUsuario) <= 2) {
            $nombreOculto = str_repeat('*', strlen($nombreUsuario));
        } else {
            $nombreOculto = substr($nombreUsuario, 0, 2).str_repeat('*', strlen($nombreUsuario) - 2);
        }

        return $nombreOculto.'@'.$dominio;
    }

    public function logout()
    {
        //
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
                        <flux:text>¡Bienvenido de nuevo!</flux:text>
                        <flux:text variant="subtle">{{ $email }}</flux:text>
                    </div>
                </div>
                <flux:navmenu.separator />
                <flux:button variant="primary" color="red" class="w-full" wire:click="logout">
                    <x-slot name="icon">
                        <x-ui.icon name="power" size="5" class="text-white" />
                    </x-slot>
                    Cerrar Sesión
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
