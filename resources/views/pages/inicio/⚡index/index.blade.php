@section ('icon')
    <x-header.icon icon="home" />
@endsection

@section ('header')
    <x-header.title>INICIO</x-header.title>
@endsection

@section ('breadcrumbs')
    <x-breadcrumbs>
        <x-breadcrumbs.item href="{{ route('inicio') }}" wire:navigate>INICIO</x-breadcrumbs.item>
    </x-breadcrumbs>
@endsection

<div class="flex w-full flex-col gap-5">
    <div class="flex flex-col gap-1">
        <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">Hola, Usuario ðŸ‘‹</h1>
        <p class="text-zinc-500 dark:text-zinc-400">Bienvenido al Sistema de GestiÃ³n de Inscripciones del Centro Preuniversitario de la Universidad Nacional de Ucayali - UNU. Selecciona un mÃ³dulo para comenzar.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="w-full">
            <x-card>
                <x-slot:header>
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex size-8 items-center justify-center rounded-md bg-white text-zinc-700 shadow-xs dark:bg-zinc-800 dark:text-zinc-200"
                            >
                                <x-ui.icon name="home" size="5" />
                            </div>

                            <div class="space-y-1">
                                <p class="text-sm font-semibold text-zinc-900 dark:text-white">Panel principal</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Acceso rÃ¡pido a los mÃ³dulos del sistema</p>
                            </div>
                        </div>

                        <span
                            class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300"
                        >
                            En lÃ­nea
                        </span>
                    </div>
                </x-slot:header>

                <div class="space-y-3">
                    Hola
                </div>

                <x-slot:footer>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex flex-wrap gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                            <span class="rounded-full bg-white px-3 py-1 dark:bg-zinc-900">Inscripciones</span>
                            <span class="rounded-full bg-white px-3 py-1 dark:bg-zinc-900">Pagos</span>
                            <span class="rounded-full bg-white px-3 py-1 dark:bg-zinc-900">Reportes</span>
                        </div>

                        <div class="flex gap-2">
                            <flux:button variant="ghost">Ver resumen</flux:button>
                            <flux:button variant="primary">Ingresar</flux:button>
                        </div>
                    </div>
                </x-slot:footer>
            </x-card>
        </div>
    </div>
</div>
