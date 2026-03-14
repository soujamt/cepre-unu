<?php

use Livewire\Component;

new class extends Component {
    // ...
};
?>

<x-sidebar>
    <x-sidebar.panel>
        <x-slot:header>
            <img
                src="https://img.pikbest.com/png-images/20240729/elegant-lion-head-logo-on-white-background_10687962.png!bw700"
                alt="Logo"
                class="h-10 w-10 rounded-lg border border-zinc-200 ring-2 ring-zinc-200/50 dark:border-zinc-800 dark:ring-zinc-800/50"
            />
            INICIO
        </x-slot:header>

        <x-sidebar.item
            label="INICIO"
            href="{{ route('inicio') }}"
            :active="request()->routeIs('inicio')"
            wire:key="menu-inicio"
        >
            <i class="ki-solid ki-home text-lg"></i>
        </x-sidebar.item>

        <x-sidebar.group label="OPCIONES" :active="false" wire:key="menu-opciones">
            <x-slot:icon>
                <i class="ki-solid ki-user text-lg"></i>
            </x-slot:icon>
            <x-sidebar.sub-item label="OPCIÓN 1" href="#" :active="false" wire:key="submenu-opcion-1" />
            <x-sidebar.sub-item label="OPCIÓN 2" href="#" :active="false" wire:key="submenu-opcion-2" />
        </x-sidebar.group>

        <x-sidebar.item label="CONFIGURACIÓN" href="#" :active="false" wire:key="menu-configuracion">
            <i class="ki-solid ki-settings text-lg"></i>
        </x-sidebar.item>

        <x-sidebar.item label="Sin Menús" href="#" :active="false" wire:key="menu-sin-menus">
            <i class="ki-solid ki-minus-squared text-lg"></i>
        </x-sidebar.item>

        <x-slot:footer>
            <livewire:livewire.navegacion.profile.index :isNavbar="false" />
        </x-slot:footer>
    </x-sidebar.panel>
</x-sidebar>
