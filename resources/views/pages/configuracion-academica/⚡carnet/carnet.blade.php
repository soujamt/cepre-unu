@section ('icon')
    <x-header.icon icon="id-card" />
@endsection

@section ('header')
    <x-header.title>Configuración del carnet</x-header.title>
@endsection

@section ('breadcrumbs')
    <x-breadcrumbs>
        <x-breadcrumbs.item href="{{ route('inicio') }}" wire:navigate>Inicio</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item href="{{ route('carnet.configuracion') }}" wire:navigate>Carnet</x-breadcrumbs.item>
    </x-breadcrumbs>
@endsection

<div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_22rem]">
    <form wire:submit="guardar" class="space-y-5 rounded-xl border border-zinc-200/80 bg-white p-5 shadow-card dark:border-white/[0.06] dark:bg-zinc-900" aria-labelledby="titulo-config-carnet">
        <div class="space-y-1">
            <h2 id="titulo-config-carnet" class="text-base font-semibold text-zinc-900 dark:text-white">Datos que se imprimen en todos los carnets</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">La firma y el sello van a la derecha del carnet, encima del nombre del director.</p>
        </div>

        <x-ui.input label="Nombre del director" name="director_nombre" wire:model="carnetForm.director_nombre" :invalid="$errors->has('carnetForm.director_nombre')" :error="$errors->first('carnetForm.director_nombre')" required floating />
        <x-ui.input label="Cargo" name="director_cargo" wire:model="carnetForm.director_cargo" :invalid="$errors->has('carnetForm.director_cargo')" :error="$errors->first('carnetForm.director_cargo')" required floating />
        <x-ui.textarea label="Nota al pie del carnet" name="nota" wire:model="carnetForm.nota" :invalid="$errors->has('carnetForm.nota')" :error="$errors->first('carnetForm.nota')" autoResize floating />

        <div>
            <label for="firma-sello" class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Imagen del sello y la firma (PNG, de preferencia con fondo transparente, hasta 1 MB)</label>
            <input
                id="firma-sello"
                type="file"
                accept="image/png"
                wire:model="firma"
                class="mt-2 block w-full text-sm text-zinc-600 file:me-3 file:rounded-lg file:border-0 file:bg-zinc-100 file:px-3 file:py-2 file:text-sm file:font-medium hover:file:bg-zinc-200 dark:text-zinc-300 dark:file:bg-zinc-800"
            />
            @error ('firma')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror
        </div>

        @permiso ('parametro_editar')
            <div class="flex justify-end">
                <flux:button variant="primary" type="submit" class="font-normal">
                    <x-slot name="icon">
                        <x-ui.icon name="save" size="5" class="text-white" />
                    </x-slot>
                    Guardar configuración
                </flux:button>
            </div>
        @endpermiso
    </form>

    <aside class="space-y-4 rounded-xl border border-zinc-200/80 bg-white p-5 shadow-card dark:border-white/[0.06] dark:bg-zinc-900" aria-label="Vista previa de la firma">
        <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Vista previa</p>
        <div class="flex min-h-32 items-center justify-center rounded-lg border border-dashed border-zinc-300 bg-white p-3 dark:border-white/10">
            @if ($firma)
                <img src="{{ $firma->temporaryUrl() }}" alt="Nueva imagen de sello y firma" class="max-h-28 object-contain" />
            @elseif ($this->firmaActual)
                <img src="{{ $this->firmaActual }}" alt="Sello y firma actuales" class="max-h-28 object-contain" />
            @else
                <p class="text-sm text-zinc-500">Aún no hay firma cargada.</p>
            @endif
        </div>
        <p class="text-center text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $carnetForm['director_nombre'] ?: 'NOMBRE DEL DIRECTOR' }}</p>
        <p class="text-center text-xs text-zinc-500 dark:text-zinc-400">{{ $carnetForm['director_cargo'] ?: 'Cargo' }}</p>
        <flux:button variant="filled" class="w-full font-normal" href="{{ route('carnet.muestra') }}" target="_blank">
            Ver carnet de muestra (PDF)
        </flux:button>
    </aside>
</div>
