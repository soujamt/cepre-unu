<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>{{ isset($title) ? $title . ' - CEPRE UNU' : 'CEPRE UNU' }}</title>

    <meta
        name="description"
        content="Plataforma institucional del Centro Preuniversitario de la Universidad Nacional de Ucayali - UNU"
    />
    <meta name="author" content="CEPRE - UNU" />

    @vite (['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @fluxAppearance
</head>
<body class="flex h-screen bg-white antialiased dark:bg-zinc-800/75" x-data="{ sidebarOpen: false }">
    <div class="bg-unia-pattern fixed inset-0 -z-10"></div>

    <livewire:livewire.navegacion.sidebar.index />

    <main class="flex w-full min-w-0 flex-1 flex-col">
        <x-header>
            <x-slot:left>
                <flux:button
                    id="mobile-menu-btn"
                    class="md:hidden"
                    @click="sidebarOpen = !sidebarOpen"
                    aria-label="Toggle Menu"
                    variant="ghost"
                >
                    <x-slot name="icon">
                        <x-ui.icon name="menu" size="6" class="text-zinc-500 dark:text-zinc-400" />
                    </x-slot>
                </flux:button>

                <div class="hidden items-center gap-3 md:flex">
                    @yield ('icon')

                    <div class="flex flex-col">
                        @yield ('header')
                        @yield ('breadcrumbs')
                    </div>
                </div>
            </x-slot:left>

            <x-slot:right>
                <div class="flex items-center gap-2">
                    <!-- Theme Toggle Button -->
                    <flux:button x-data x-on:click="$flux.dark = !$flux.dark">
                        <x-slot name="icon">
                            <x-ui.icon
                                name="moon"
                                size="6"
                                class="text-zinc-600 dark:text-white"
                                x-show="$flux.dark"
                                x-cloak
                            />
                            <x-ui.icon
                                name="sun"
                                size="6"
                                class="text-zinc-600 dark:text-white"
                                x-show="!$flux.dark"
                                x-cloak
                            />
                        </x-slot>
                    </flux:button>
                </div>
                <div class="flex items-center md:hidden">
                    <livewire:livewire.navegacion.profile.index :isNavbar="true" />
                </div>
            </x-slot:right>
        </x-header>

        <!-- Scrollable Content -->
        <div class="flex-1 overflow-y-auto">
            <div
                class="z-10 flex h-16 items-center justify-between border-b border-zinc-200 shadow-xs dark:shadow-lg px-5 md:hidden dark:border-zinc-700"
            >
                <div class="flex items-center gap-3">
                    @yield ('icon')
                    @yield ('header')
                </div>
            </div>
            <div class="h-full w-full p-5">{{ $slot }}</div>
        </div>
    </main>

    <!-- Mobile Overlay -->
    <div
        x-show="sidebarOpen"
        x-transition:enter="transition-opacity duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity duration-300"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-30 bg-black/20 backdrop-blur-sm md:hidden"
        @click="sidebarOpen = false"
        style="display: none"
    ></div>

    @persist ('toast')
        <x-notificacion />
    @endpersist

    @persist ('alert')
        <x-alerta />
    @endpersist

    @livewireScripts
    @fluxScripts
</body>
</html>
