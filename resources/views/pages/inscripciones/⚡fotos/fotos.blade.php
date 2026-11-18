@section ('icon')
    <x-header.icon icon="image" />
@endsection

@section ('header')
    <x-header.title>Fotos</x-header.title>
@endsection

@section ('breadcrumbs')
    <x-breadcrumbs>
        <x-breadcrumbs.item href="{{ route('inicio') }}" wire:navigate>Inicio</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item href="{{ route('fotos.index') }}" wire:navigate>Fotos</x-breadcrumbs.item>
    </x-breadcrumbs>
@endsection

<div class="space-y-5">
    <search class="flex flex-wrap items-center gap-3">
        <div class="w-full sm:w-52">
            <x-ui.select wire:model.live="idProceso" placeholder="Todos los procesos" aria-label="Filtrar por proceso">
                <option value="">Todas las fotos</option>
                @foreach ($this->procesos as $proceso)
                    <option wire:key="proceso-foto-{{ $proceso->id_pro }}" value="{{ $proceso->id_pro }}">Inscritos {{ $proceso->nombre_pro }}</option>
                @endforeach
            </x-ui.select>
        </div>

        <div class="w-full sm:w-64">
            <flux:input wire:model.live.debounce.500ms="buscar" placeholder="Buscar por DNI" aria-label="Buscar foto por DNI" inputmode="numeric" clearable>
                <x-slot name="icon">
                    <x-ui.icon name="search" size="5" class="text-zinc-400 dark:text-zinc-500" />
                </x-slot>
            </flux:input>
        </div>

        <flux:badge color="zinc" size="lg">Total fotos: {{ $this->totalFotos }}</flux:badge>

        <div class="flex w-full flex-wrap gap-2 sm:ms-auto sm:w-auto">
            @permiso ('foto_exportar_excel')
                <flux:button class="font-normal" wire:click="exportarFotos">
                    <x-slot name="icon">
                        <x-ui.icon name="arrow-in-down-square-half" size="5" />
                    </x-slot>
                    Exportar fotos
                </flux:button>
            @endpermiso

            @permiso ('foto_crear')
                <flux:modal.trigger name="modal-subir-fotos">
                    <flux:button class="font-normal" variant="primary">
                        <x-slot name="icon">
                            <x-ui.icon name="arrow-out-up-square-half" size="5" class="text-white" />
                        </x-slot>
                        Subir fotos
                    </flux:button>
                </flux:modal.trigger>
            @endpermiso
        </div>
    </search>

    <div class="relative">
        @if ($this->fotos->isEmpty())
            <div class="rounded-xl border border-dashed border-zinc-300 bg-white px-6 py-12 text-center shadow-card dark:border-white/[0.06] dark:bg-zinc-900">
                <x-ui.icon name="image" size="12" class="text-zinc-400 dark:text-zinc-500" />
                <p class="mt-3 text-zinc-500 dark:text-zinc-400">No hay fotos con los filtros actuales.</p>
            </div>
        @else
            <ul class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6" role="list">
                @foreach ($this->fotos as $foto)
                    <li wire:key="foto-{{ $foto->id_fot }}" class="group relative rounded-xl border border-zinc-200/80 bg-white p-2 shadow-card dark:border-white/[0.06] dark:bg-zinc-900">
                        <img
                            src="{{ route('fotos.ver', $foto->numero_documento_fot) }}?v={{ $foto->actualizado_en?->timestamp }}"
                            alt="Foto del DNI {{ $foto->numero_documento_fot }}"
                            width="300"
                            height="400"
                            loading="lazy"
                            decoding="async"
                            class="aspect-[3/4] w-full rounded-lg bg-zinc-100 object-cover dark:bg-zinc-800"
                        />
                        <p class="mt-2 text-center text-sm font-medium text-zinc-800 tabular-nums dark:text-zinc-100">{{ $foto->numero_documento_fot }}</p>
                        <p class="truncate text-center text-xs text-zinc-500 dark:text-zinc-400" title="{{ $this->nombres[$foto->numero_documento_fot] ?? '' }}">
                            {{ $this->nombres[$foto->numero_documento_fot] ?? 'Aún sin inscripción' }}
                        </p>
                        @permiso ('foto_eliminar')
                            <button
                                type="button"
                                wire:click="$js.cargarAlertaEliminar({{ $foto->id_fot }}, '{{ $foto->numero_documento_fot }}')"
                                class="absolute end-3 top-3 flex size-7 items-center justify-center rounded-full bg-white/90 text-red-600 shadow-sm hover:bg-white focus-visible:ring-2 focus-visible:ring-red-500 dark:bg-zinc-900/90"
                                aria-label="Eliminar foto del DNI {{ $foto->numero_documento_fot }}"
                            >
                                <x-ui.icon name="x" size="4" />
                            </button>
                        @endpermiso
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="flex w-full justify-between">
        <div class="flex items-center text-sm text-zinc-500 dark:text-zinc-400">
            Mostrando {{ $this->fotos->firstItem() ?? 0 }} - {{ $this->fotos->lastItem() ?? 0 }} de {{ $this->fotos->total() }} fotos
        </div>
        {{ $this->fotos->links() }}
    </div>

    @permiso ('foto_crear')
        <flux:modal flyout variant="floating" name="modal-subir-fotos" class="px-0! py-5! md:w-lg!">
            <div class="space-y-5">
                <flux:heading size="lg" class="px-5" id="titulo-subir-fotos">Subir fotos</flux:heading>
                <flux:separator variant="subtle" />

                <form wire:submit="subirFotos" class="space-y-5" aria-labelledby="titulo-subir-fotos">
                    <div class="space-y-4 px-5">
                        <div>
                            <label for="archivos-fotos" class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Fotos (JPG o PNG, hasta 2 MB cada una)</label>
                            <input
                                id="archivos-fotos"
                                type="file"
                                accept="image/jpeg,image/png"
                                multiple
                                wire:model="archivos"
                                aria-describedby="ayuda-archivos-fotos"
                                class="mt-2 block w-full text-sm text-zinc-600 file:me-3 file:rounded-lg file:border-0 file:bg-zinc-100 file:px-3 file:py-2 file:text-sm file:font-medium hover:file:bg-zinc-200 dark:text-zinc-300 dark:file:bg-zinc-800"
                            />
                            <p id="ayuda-archivos-fotos" class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Nombre cada archivo con el DNI (por ejemplo 47940211.jpg). Si una foto ya existe, se reemplaza.</p>
                            <div wire:loading wire:target="archivos" class="mt-2 text-xs text-zinc-500">Cargando fotos…</div>
                            @error ('archivos')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        @foreach ($archivos as $indice => $archivo)
                            <div wire:key="archivo-{{ $indice }}" class="flex items-start gap-3">
                                @if ($archivo->isPreviewable())
                                    <img src="{{ $archivo->temporaryUrl() }}" alt="" width="48" height="64" class="h-16 w-12 shrink-0 rounded object-cover" />
                                @endif
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $archivo->getClientOriginalName() }}</p>
                                    <x-ui.input
                                        label="DNI"
                                        type="text"
                                        inputmode="numeric"
                                        maxlength="12"
                                        wire:model="documentos.{{ $indice }}"
                                        :invalid="$errors->has('documentos.'.$indice) || $errors->has('archivos.'.$indice)"
                                        :error="$errors->first('documentos.'.$indice) ?: $errors->first('archivos.'.$indice)"
                                        required
                                        floating
                                    />
                                </div>
                                <flux:button size="sm" variant="subtle" square type="button" wire:click="quitarArchivo({{ $indice }})" aria-label="Quitar {{ $archivo->getClientOriginalName() }}">
                                    <x-slot name="icon">
                                        <x-ui.icon name="x" size="4" />
                                    </x-slot>
                                </flux:button>
                            </div>
                        @endforeach
                    </div>

                    <flux:separator variant="subtle" />

                    <div class="flex px-5">
                        <flux:spacer />
                        <flux:button variant="primary" type="submit" class="font-normal" wire:loading.attr="disabled" wire:target="subirFotos,archivos">
                            <x-slot name="icon">
                                <x-ui.icon name="save" size="5" class="text-white" />
                            </x-slot>
                            Guardar fotos
                        </flux:button>
                    </div>
                </form>
            </div>
        </flux:modal>
    @endpermiso
</div>

@script
    <script>
        this.$js.cargarAlertaEliminar = (id_foto, documento) => {
            this.$dispatch('alert', {
                type: 'error',
                title: 'Atención',
                message: '¿Eliminar la foto del DNI ' + documento + '?',
                showCancel: true,
                showConfirm: true,
                confirmText: 'Eliminar',
                cancelText: 'Cancelar',
                confirmEvent: 'foto-eliminar',
                confirmParams: { id_foto: id_foto },
            });
        };
    </script>
@endscript
