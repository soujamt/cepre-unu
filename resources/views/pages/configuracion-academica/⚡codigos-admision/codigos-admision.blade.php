@section ('icon')
    <x-header.icon icon="barcode" />
@endsection

@section ('header')
    <x-header.title>Códigos de Admisión</x-header.title>
@endsection

@section ('breadcrumbs')
    <x-breadcrumbs>
        <x-breadcrumbs.item href="{{ route('inicio') }}" wire:navigate>Inicio</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item href="{{ route('codigos-admision.index') }}" wire:navigate>Códigos de Admisión</x-breadcrumbs.item>
    </x-breadcrumbs>
@endsection

<div class="space-y-5">
    @php
        $puedeEditar = auth()->user()?->hasPermisoAccion('codigo_admision_editar') ?? false;
        $avance = $this->avance;
    @endphp

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="min-w-0 space-y-5">
            <div class="rounded-xl border border-zinc-200/80 bg-white p-5 shadow-card dark:border-white/[0.06] dark:bg-zinc-900">
                <div class="max-w-sm">
                    <x-ui.select
                        label="Proceso"
                        wire:model.live="idProceso"
                        :invalid="$errors->has('idProceso')"
                        :error="$errors->first('idProceso')"
                        required
                        floating
                    >
                        <option value="">Seleccionar proceso</option>
                        @foreach ($this->procesosDisponibles as $procesoOpcion)
                            <option wire:key="proceso-codigos-{{ $procesoOpcion->id_pro }}" value="{{ $procesoOpcion->id_pro }}">{{ $procesoOpcion->nombre_pro }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
            </div>

            @if ($erroresPlantilla !== [])
                <flux:callout variant="warning" icon="exclamation-triangle" heading="Filas de la plantilla que no se cargaron">
                    <ul class="list-disc space-y-0.5 ps-5 text-sm">
                        @foreach ($erroresPlantilla as $error)
                            <li wire:key="error-plantilla-{{ $loop->index }}">{{ $error }}</li>
                        @endforeach
                    </ul>
                </flux:callout>
            @endif

            @if ($this->combinaciones->isEmpty())
                <div class="rounded-xl border border-dashed border-zinc-300 bg-white px-6 py-10 text-center text-sm text-zinc-500 shadow-card dark:border-white/[0.06] dark:bg-zinc-900 dark:text-zinc-400">
                    {{ $this->procesoSeleccionado ? 'El proceso no tiene oferta. Configúrela en Oferta del proceso.' : 'Seleccione un proceso.' }}
                </div>
            @else
                <form wire:submit="guardar" class="overflow-hidden rounded-xl border border-zinc-200/80 bg-white shadow-card dark:border-white/[0.06] dark:bg-zinc-900">
                    <table class="w-full text-sm">
                        <thead class="bg-zinc-50 text-left text-xs tracking-wide text-zinc-500 uppercase dark:bg-white/5 dark:text-zinc-400">
                            <tr>
                                <th scope="col" class="px-4 py-2.5 font-medium">Lugar / carrera</th>
                                <th scope="col" class="w-44 px-4 py-2.5 font-medium">Código Admisión</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-white/5">
                            @foreach ($this->combinaciones as $fila)
                                @php
                                    $esLugar = $fila['tipo'] === App\Support\CodigosAdmisionProceso::TIPO_LUGAR;
                                @endphp
                                <tr wire:key="codigo-{{ $fila['clave'] }}" @class (['bg-zinc-50/70 dark:bg-white/[0.03]' => $esLugar])>
                                    <th scope="row" @class (['px-4 py-2 text-left', 'font-semibold text-zinc-900 dark:text-white' => $esLugar, 'ps-9 font-normal text-zinc-700 dark:text-zinc-200' => ! $esLugar])>
                                        @if ($esLugar)
                                            LUGAR: {{ $fila['sede'] }} / {{ $fila['modalidad'] }}
                                        @else
                                            {{ $fila['carrera'] }}
                                        @endif
                                    </th>
                                    <td class="px-4 py-1.5">
                                        <input
                                            type="text"
                                            aria-label="Código de {{ $esLugar ? 'lugar '.$fila['sede'].' '.$fila['modalidad'] : $fila['carrera'].' en '.$fila['modalidad'] }}"
                                            wire:model="codigos.{{ $fila['clave'] }}"
                                            maxlength="20"
                                            autocomplete="off"
                                            spellcheck="false"
                                            @disabled (! $puedeEditar)
                                            class="w-full rounded-md border border-zinc-200 bg-white px-2.5 py-1.5 text-sm tabular-nums uppercase focus:border-zinc-400 focus:ring-0 dark:border-white/10 dark:bg-zinc-800"
                                        />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if ($puedeEditar)
                        <div class="flex justify-end border-t border-zinc-200/80 p-4 dark:border-white/10">
                            <flux:button type="submit" variant="primary" class="font-normal">
                                <x-slot name="icon">
                                    <x-ui.icon name="save" size="5" class="text-white" />
                                </x-slot>
                                Guardar códigos
                            </flux:button>
                        </div>
                    @endif
                </form>
            @endif
        </div>

        <div class="space-y-4">
            <div class="space-y-4 rounded-xl border border-zinc-200/80 bg-white p-5 shadow-card dark:border-white/[0.06] dark:bg-zinc-900">
                <div>
                    <p class="text-xs font-medium tracking-wide text-zinc-500 uppercase dark:text-zinc-400">Avance</p>
                    <p class="mt-1 text-2xl font-semibold text-zinc-900 tabular-nums dark:text-white">{{ $avance['con_codigo'] }} / {{ $avance['total'] }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Sin código, la exportación de inscritos lleva el nombre.</p>
                </div>

                <flux:button class="w-full font-normal" wire:click="descargarPlantilla" :disabled="$this->combinaciones->isEmpty()">
                    <x-slot name="icon">
                        <x-ui.icon name="arrow-in-down-square-half" size="5" />
                    </x-slot>
                    Descargar plantilla
                </flux:button>

                @if ($puedeEditar)
                    <label class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-lg border border-dashed border-zinc-300 px-3 py-2.5 text-sm text-zinc-700 hover:bg-zinc-50 focus-within:ring-2 focus-within:ring-zinc-400 dark:border-white/15 dark:text-zinc-200 dark:hover:bg-white/5">
                        <x-ui.icon name="arrow-out-up-square-half" size="5" />
                        <span wire:loading.remove wire:target="plantilla">Subir plantilla llena</span>
                        <span wire:loading wire:target="plantilla">Cargando…</span>
                        <input type="file" accept=".xlsx,.xls" wire:model="plantilla" class="sr-only" />
                    </label>
                    @error ('plantilla')
                        <p class="text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                @endif

                <p class="text-xs text-pretty text-zinc-500 dark:text-zinc-400">
                    Envíe la plantilla a Admisión. Deben llenar solo la columna CODIGO_ADMISION y no tocar la columna CLAVE.
                </p>
            </div>
        </div>
    </div>
</div>
