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

    <link rel="shortcut icon" href="{{ asset('assets/media/logo_unu.png') }}" type="image/x-icon" />

    @vite (['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @fluxAppearance
</head>
<body
    class="flex h-screen bg-zinc-100 text-zinc-800 antialiased dark:bg-zinc-950 dark:text-zinc-100"
    x-data="{ sidebarOpen: false }"
>
    <livewire:livewire.navegacion.sidebar.index />

    <main class="flex w-full min-w-0 flex-1 flex-col md:py-2 md:pe-2">
        <div
            class="flex min-h-0 flex-1 flex-col overflow-hidden bg-white md:rounded-2xl md:border md:border-zinc-200/80 md:shadow-[0_1px_2px_rgba(0,0,0,0.04),0_8px_24px_-12px_rgba(0,0,0,0.08)] dark:bg-zinc-900 dark:md:border-white/[0.06] dark:md:shadow-[inset_0_1px_0_rgba(255,255,255,0.03)]"
        >
            <x-header>
                <x-slot:left>
                    <flux:button
                        id="mobile-menu-btn"
                        class="md:hidden"
                        @click="sidebarOpen = !sidebarOpen"
                        aria-label="Abrir menú"
                        variant="ghost"
                        square
                    >
                        <x-slot name="icon">
                            <x-ui.icon name="menu" size="6" class="text-zinc-500 dark:text-zinc-400" />
                        </x-slot>
                    </flux:button>

                    <div class="hidden items-center gap-3 md:flex">
                        @yield ('icon')

                        <div class="flex flex-col gap-0.5">
                            @yield ('header')
                            @yield ('breadcrumbs')
                        </div>
                    </div>
                </x-slot:left>

                <x-slot:right>
                    <div class="flex items-center gap-2">
                        <flux:button
                            x-data
                            x-on:click="$flux.dark = !$flux.dark"
                            variant="subtle"
                            square
                            aria-label="Cambiar tema"
                        >
                            <x-slot name="icon">
                                <x-ui.icon
                                    name="moon"
                                    size="5"
                                    class="text-zinc-500 dark:text-zinc-300"
                                    x-show="$flux.dark"
                                    x-cloak
                                />
                                <x-ui.icon
                                    name="sun"
                                    size="5"
                                    class="text-zinc-500 dark:text-zinc-300"
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
            <div class="custom-scrollbar flex min-h-0 flex-1 flex-col overflow-y-auto">
                <div
                    class="flex items-center gap-3 border-b border-zinc-200/80 px-4 py-3 md:hidden dark:border-white/[0.06]"
                >
                    @yield ('icon')
                    @yield ('header')
                </div>
                <div class="flex min-h-0 flex-1 flex-col px-4 pt-5 sm:px-6 sm:pt-6">
                    <div class="flex-1">{{ $slot }}</div>
                    <div class="h-6 shrink-0"></div>
                </div>
            </div>
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
        class="fixed inset-0 z-30 bg-zinc-950/30 backdrop-blur-sm md:hidden"
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
