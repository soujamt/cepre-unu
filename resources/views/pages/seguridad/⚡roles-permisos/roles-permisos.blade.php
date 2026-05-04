@section ('icon')
    <x-header.icon icon="shield" />
@endsection

@section ('header')
    <x-header.title>PERMISOS DEL ROL</x-header.title>
@endsection

@section ('breadcrumbs')
    <x-breadcrumbs>
        <x-breadcrumbs.item href="{{ route('inicio') }}" wire:navigate>INICIO</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item href="{{ route('roles.index') }}" wire:navigate>ROLES</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item href="{{ route('roles.permisos', $rolActual->id_rol) }}" wire:navigate>
            PERMISOS
        </x-breadcrumbs.item>
    </x-breadcrumbs>
@endsection

<div class="space-y-5">
    <div class="rounded-lg border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-700 dark:bg-zinc-800">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="space-y-1">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $rolActual->nombre_rol }}</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ $rolActual->descripcion_rol ?: 'Sin descripción registrada.' }}
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row">
                <flux:button href="{{ route('roles.index') }}" wire:navigate variant="filled" class="font-normal">
                    <x-slot name="icon">
                        <x-ui.icon name="arrow-left" size="5" />
                    </x-slot>
                    Volver
                </flux:button>

                @if ($rolActual->nombre_rol !== 'ADMINISTRADOR')
                    <flux:button wire:click="guardar" variant="primary" class="font-normal">
                        <x-slot name="icon">
                            <x-ui.icon name="floppy-disk" size="5" class="text-white" />
                        </x-slot>
                        Guardar
                    </flux:button>
                @endif
            </div>
        </div>
    </div>

    @if ($rolActual->nombre_rol === 'ADMINISTRADOR')
        <flux:callout
            variant="warning"
            heading="El rol ADMINISTRADOR tiene acceso total al sistema y no requiere asignación manual de permisos."
            class="rounded-md!"
        />
    @endif

    <div class="grid gap-4 xl:grid-cols-2">
        @foreach ($this->permisosDisponibles as $permiso)
            <section
                wire:key="permiso-{{ $permiso->id_permiso }}"
                class="rounded-lg border border-zinc-200 bg-white shadow-xs dark:border-zinc-700 dark:bg-zinc-800"
            >
                <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="font-semibold text-zinc-900 dark:text-white">{{ $permiso->nombre_permiso }}</h3>
                        </div>

                        <flux:badge color="{{ $permiso->estado_permiso->getColor() }}" size="lg">
                            <x-slot name="icon">
                                <x-ui.icon :name="$permiso->estado_permiso->getIcon()" size="4" class="me-1" />
                            </x-slot>
                            {{ $permiso->estado_permiso->getNombre() }}
                        </flux:badge>
                    </div>
                </div>

                <div class="px-4 py-4">
                    @forelse ($permiso->permisoAcciones as $permisoAccion)
                        @if ($loop->first)
                            <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                        @endif
                        <label
                            wire:key="peracc-{{ $permisoAccion->id_peracc }}"
                            class="flex items-start gap-3 rounded-md border border-zinc-200 px-3 py-2 transition-colors hover:border-sky-300 hover:bg-sky-50/60 dark:border-zinc-700 dark:hover:border-sky-700 dark:hover:bg-sky-950/20"
                        >
                            <input
                                type="checkbox"
                                wire:model.live="seleccionadas"
                                value="{{ $permisoAccion->id_peracc }}"
                                class="mt-0.5 size-4 shrink-0 rounded border-zinc-300 accent-sky-600 focus:ring-sky-500 dark:border-zinc-600 dark:bg-zinc-900 dark:accent-sky-500 dark:focus:ring-sky-500"
                                @disabled ($rolActual->nombre_rol === 'ADMINISTRADOR')
                            />

                            <div class="min-w-0">
                                <p class="text-sm font-medium text-zinc-900 dark:text-white">
                                    {{ $permisoAccion->accion?->nombre_accion ?? $permisoAccion->clave_peracc }}
                                </p>
                            </div>
                        </label>
                        @if ($loop->last)
                            </div>
                        @endif
                    @empty
                        <div class="flex flex-col items-center justify-center gap-2 py-6 text-center">
                            <x-ui.icon name="receipt" size="10" class="text-zinc-400 dark:text-zinc-500" />
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">No hay acciones disponibles para este módulo.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>
</div>
