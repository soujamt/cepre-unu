@section ('icon')
    <x-header.icon icon="money" />
@endsection

@section ('header')
    <x-header.title>PAGOS</x-header.title>
@endsection

@section ('breadcrumbs')
    <x-breadcrumbs>
        <x-breadcrumbs.item href="{{ route('inicio') }}" wire:navigate>INICIO</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item href="{{ route('pagos.index') }}" wire:navigate>PAGOS</x-breadcrumbs.item>
    </x-breadcrumbs>
@endsection

<div class="space-y-5">
    @permiso ('pago_crear')
        <div class="flex justify-end">
            <flux:button variant="ghost" href="{{ route('pagos.validar') }}" wire:navigate class="font-normal">
                <x-slot name="icon">
                    <x-ui.icon name="shield-check" size="5" />
                </x-slot>
                Validar pagos
            </flux:button>
        </div>
    @endpermiso

    @permiso ('pago_crear')
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900">
            <div class="space-y-5">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div class="space-y-1">
                        <h3 class="text-sm font-semibold tracking-wide text-zinc-800 dark:text-zinc-100">
                            IMPORTAR TXT DEL BANCO
                        </h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Cargue el archivo recibido del banco para registrar el lote y sus operaciones.</p>
                    </div>

                    <flux:badge color="zinc" size="lg">TXT</flux:badge>
                </div>

                <form wire:submit="importar" class="space-y-4">
                    <div class="space-y-2">
                        <label for="archivo-pagos" class="text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            Archivo TXT <span class="text-red-500">*</span>
                        </label>

                        <div
                            class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50/80 p-4 dark:border-white/10 dark:bg-zinc-950/40"
                        >
                            <input
                                id="archivo-pagos"
                                type="file"
                                wire:model="archivo"
                                accept=".txt,text/plain"
                                class="block w-full cursor-pointer rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 file:me-3 file:rounded-md file:border-0 file:bg-zinc-900 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-zinc-800 dark:border-white/10 dark:bg-zinc-900 dark:text-zinc-200 dark:file:bg-white dark:file:text-zinc-900 dark:hover:file:bg-zinc-100"
                            />
                        </div>

                        @error ('archivo')
                            <p class="flex items-center gap-1 ps-1 text-xs font-medium text-red-500 dark:text-red-400">
                                <x-ui.icon name="warning-circle" size="4" />
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Maximo 5 MB. El sistema valida cabecera, detalle y cierre del lote.</p>

                        <flux:button
                            variant="primary"
                            type="submit"
                            class="font-normal"
                            wire:loading.attr="disabled"
                            wire:target="archivo, importar"
                        >
                            <x-slot name="icon">
                                <x-ui.icon name="upload-simple" size="5" class="text-white" />
                            </x-slot>
                            Importar archivo
                        </flux:button>
                    </div>

                    <div
                        class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400"
                        wire:loading.flex
                        wire:target="archivo, importar"
                    >
                        <x-ui.icon name="spinner-gap" size="5" class="animate-spin" />
                        Procesando archivo...
                    </div>
                </form>
            </div>
        </div>
        <flux:modal flyout variant="floating" name="modal-previsualizar-importacion" class="px-0! py-5! md:w-2xl!">
            @php ($previsualizacion = $previsualizacionImportacion)

            <div class="space-y-5">
                <div class="space-y-2 px-5">
                    <flux:heading size="lg">Revision del TXT bancario</flux:heading>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ $previsualizacion['nombre_archivo'] ?? 'Archivo TXT' }}
                    </p>
                </div>

                <flux:separator variant="subtle" />

                <div class="space-y-5 px-5">
                    @if (($previsualizacion['valido'] ?? false) === true)
                        <flux:callout color="green" icon="check-circle">
                            El archivo cuadra con los totales reportados por el banco. Puede confirmar la importacion.
                        </flux:callout>
                    @elseif ($previsualizacion !== [])
                        <flux:callout color="red" icon="exclamation-triangle">
                            El archivo tiene observaciones y no se importara. Corrija el TXT o revise el lote antes de
                            continuar.
                        </flux:callout>
                    @endif

                    @if ($previsualizacion !== [])
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div
                                class="rounded-lg border border-zinc-200 bg-zinc-50/80 px-4 py-3 dark:border-white/10 dark:bg-zinc-950/40"
                            >
                                <p class="text-xs font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">Registros leidos</p>
                                <p class="mt-1 text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $previsualizacion['total_registros'] }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Reportado: {{ $previsualizacion['total_registros_reportado'] }}</p>
                            </div>

                            <div
                                class="rounded-lg border border-zinc-200 bg-zinc-50/80 px-4 py-3 dark:border-white/10 dark:bg-zinc-950/40"
                            >
                                <p class="text-xs font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">Monto leido</p>
                                <p class="mt-1 text-sm font-medium text-zinc-800 dark:text-zinc-100">S/ {{ number_format((float) $previsualizacion['total_monto'], 2) }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Reportado: S/ {{ number_format((float) $previsualizacion['total_monto_reportado'], 2) }}</p>
                            </div>
                        </div>
                        @if (($previsualizacion['observaciones'] ?? []) !== [])
                            <div class="space-y-2">
                                <h4 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Observaciones</h4>
                                <div class="space-y-2">
                                    @foreach ($previsualizacion['observaciones'] as $observacion)
                                        <div
                                            class="flex gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/30 dark:text-red-300"
                                        >
                                            <x-ui.icon name="warning-circle" size="5" class="mt-0.5 shrink-0" />
                                            <p>{{ $observacion }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if (($previsualizacion['duplicados'] ?? []) !== [])
                            <div class="space-y-3">
                                <div class="flex items-center justify-between gap-3">
                                    <h4 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Pagos duplicados</h4>
                                    <flux:badge color="red" size="lg">{{ count($previsualizacion['duplicados']) }}</flux:badge>
                                </div>

                                <div class="max-h-56 overflow-auto rounded-lg border border-red-200 dark:border-red-900/60">
                                    <x-table>
                                        <x-table.head>
                                            <x-table.row>
                                                <x-table.heading class="min-w-32">TIPO</x-table.heading>
                                                <x-table.heading class="min-w-28">DNI</x-table.heading>
                                                <x-table.heading class="min-w-32">OPERACION</x-table.heading>
                                                <x-table.heading class="min-w-28">FECHA</x-table.heading>
                                                <x-table.heading class="min-w-28">MONTO</x-table.heading>
                                            </x-table.row>
                                        </x-table.head>
                                        <x-table.body>
                                            @foreach ($previsualizacion['duplicados'] as $indice => $duplicado)
                                                @php($documentoDuplicado = (string) ($duplicado['documento'] ?? ''))
                                                <x-table.row wire:key="previsualizacion-duplicado-{{ $indice }}">
                                                    <x-table.cell>{{ $duplicado['tipo'] }}</x-table.cell>
                                                    <x-table.cell>{{ preg_match('/^\d{1,8}$/', $documentoDuplicado) === 1 ? str_pad($documentoDuplicado, 8, '0', STR_PAD_LEFT) : ($documentoDuplicado ?: 'SIN DOCUMENTO') }}</x-table.cell>
                                                    <x-table.cell>{{ $duplicado['operacion'] ?? 'SIN OPERACION' }}</x-table.cell>
                                                    <x-table.cell>{{ filled($duplicado['fecha_pago'] ?? null) ? \Illuminate\Support\Carbon::parse($duplicado['fecha_pago'])->format('d/m/Y') : 'Sin fecha' }}</x-table.cell>
                                                    <x-table.cell>S/ {{ number_format((float) ($duplicado['monto'] ?? 0), 2) }}</x-table.cell>
                                                </x-table.row>
                                            @endforeach
                                        </x-table.body>
                                    </x-table>
                                </div>
                            </div>
                        @endif

                        <div class="space-y-3">
                            <div class="flex items-center justify-between gap-3">
                                <h4 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">
                                    Muestra de operaciones
                                </h4>
                                <flux:badge
                                    color="zinc"
                                    size="lg"
                                    >{{ count($previsualizacion['detalles'] ?? []) }}</flux:badge
                                >
                            </div>

                            <div class="max-h-72 overflow-auto rounded-lg border border-zinc-200 dark:border-white/10">
                                <x-table>
                                    <x-table.head>
                                        <x-table.row>
                                            <x-table.heading class="min-w-28">DNI</x-table.heading>
                                            <x-table.heading class="min-w-36">OPERACION</x-table.heading>
                                            <x-table.heading class="min-w-28">FECHA</x-table.heading>
                                            <x-table.heading class="min-w-28">MONTO</x-table.heading>
                                        </x-table.row>
                                    </x-table.head>
                                    <x-table.body>
                                        @foreach (($previsualizacion['detalles_muestra'] ?? []) as $indice => $detalle)
                                            @php ($documento = (string) ($detalle['numero_documento_normalizado'] ?? $detalle['numero_documento'] ?? ''))
                                            <x-table.row wire:key="previsualizacion-detalle-{{ $indice }}">
                                                <x-table.cell>
                                                    {{ preg_match('/^\d{1,8}$/', $documento) === 1 ? str_pad($documento, 8, '0', STR_PAD_LEFT) : ($documento ?: 'SIN DOCUMENTO') }}</x-table.cell
                                                >
                                                <x-table.cell>
                                                    {{ $detalle['numero_operacion_visible'] ?? $detalle['numero_operacion_normalizado'] ?? $detalle['numero_operacion'] ?? 'SIN OPERACION' }}</x-table.cell
                                                >
                                                <x-table.cell>
                                                    {{ filled($detalle['fecha_pago'] ?? null) ? \Illuminate\Support\Carbon::parse($detalle['fecha_pago'])->format('d/m/Y') : 'Sin fecha' }}</x-table.cell
                                                >
                                                <x-table.cell>
                                                    S/ {{ number_format((float) ($detalle['monto'] ?? 0), 2) }}</x-table.cell
                                                >
                                            </x-table.row>
                                        @endforeach
                                    </x-table.body>
                                </x-table>
                            </div>
                        </div>
                    @endif
                </div>

                <flux:separator variant="subtle" />

                <div class="flex px-5">
                    <flux:spacer />
                    <div class="flex gap-2">
                        <flux:button
                            variant="filled"
                            wire:click="cancelarPrevisualizacionImportacion"
                            class="font-normal"
                        >
                            {{ ($previsualizacion['valido'] ?? false) === true ? 'Cancelar' : 'Entendido' }}
                        </flux:button>

                        @if (($previsualizacion['valido'] ?? false) === true)
                            <flux:button
                                variant="primary"
                                wire:click="confirmarImportacion"
                                wire:loading.attr="disabled"
                                wire:target="confirmarImportacion"
                                class="font-normal"
                            >
                                <x-slot name="icon">
                                    <x-ui.icon name="check-circle" size="5" class="text-white" />
                                </x-slot>
                                Confirmar importacion
                            </flux:button>
                        @endif
                    </div>
                </div>
            </div>
        </flux:modal>
    @endpermiso

    <div
        class="space-y-5 rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900"
    >
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
            <div class="w-full lg:w-xs">
                <flux:input wire:model.live.debounce.500ms="buscar" placeholder="Buscar importaciones" clearable>
                    <x-slot name="icon">
                        <x-ui.icon name="magnifying-glass" size="5" class="text-zinc-400 dark:text-zinc-500" />
                    </x-slot>
                </flux:input>
            </div>

            <div class="w-full lg:w-auto">
                <flux:dropdown>
                    <flux:button class="w-full font-normal" icon:trailing="chevron-down">
                        <x-slot name="icon">
                            <x-ui.icon name="funnel" size="5" />
                        </x-slot>
                        Estado
                        @if (count($this->estadosSeleccionados) > 0)
                            <flux:badge color="zinc" class="ms-2">{{ count($this->estadosSeleccionados) }}</flux:badge>
                        @endif
                    </flux:button>

                    <flux:menu keep-open class="dark:bg-zinc-800">
                        @foreach ($this->estados() as $estado)
                            <flux:menu.checkbox wire:model.live="filtroEstado.{{ $estado->value }}" class="font-normal">
                                <div
                                    class="me-1.5 flex size-6 items-center justify-center rounded-sm border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900 dark:shadow-black"
                                >
                                    <x-ui.icon
                                        :name="$estado->getIcon()"
                                        size="4"
                                        class="text-{{ $estado->getColor() }}-500"
                                    />
                                </div>
                                {{ ucfirst(strtolower($estado->getNombre())) }}
                            </flux:menu.checkbox>
                        @endforeach

                        @if (count($this->estadosSeleccionados) > 0)
                            <flux:menu.separator />
                            <flux:menu.item variant="danger" wire:click="limpiarFiltroEstado" class="font-normal!">
                                <x-slot name="icon">
                                    <div
                                        class="me-1.5 flex size-6 items-center justify-center rounded-sm border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900 dark:shadow-black"
                                    >
                                        <x-ui.icon name="trash" size="4" />
                                    </div>
                                </x-slot>
                                Limpiar filtros
                            </flux:menu.item>
                        @endif
                    </flux:menu>
                </flux:dropdown>
            </div>
        </div>

        <div class="relative">
            <x-table>
                <x-table.head>
                    <x-table.row>
                        <x-table.heading class="w-16 max-w-16">N&deg;</x-table.heading>
                        <x-table.heading class="min-w-48">ARCHIVO</x-table.heading>
                        <x-table.heading class="min-w-30">FECHA LOTE</x-table.heading>
                        <x-table.heading class="min-w-24">REGISTROS</x-table.heading>
                        <x-table.heading class="min-w-28">MONTO</x-table.heading>
                        <x-table.heading class="min-w-30">ESTADO</x-table.heading>
                        <x-table.heading class="min-w-35">IMPORTADO</x-table.heading>
                        <x-table.heading class="w-24"></x-table.heading>
                    </x-table.row>
                </x-table.head>

                <x-table.body>
                    @forelse ($this->importaciones as $importacion)
                        <x-table.row wire:key="importacion-{{ $importacion->id_iba }}">
                            <x-table.cell>{{ $this->importaciones->firstItem() + $loop->index }}</x-table.cell>
                            <x-table.cell class="whitespace-normal">
                                <div class="space-y-0.5">
                                    <div class="font-medium text-zinc-800 dark:text-zinc-100">
                                        {{ $importacion->nombre_archivo_iba }}
                                    </div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $importacion->moneda_iba ?? 'SIN MONEDA' }}
                                    </div>
                                </div>
                            </x-table.cell>
                            <x-table.cell>{{ $importacion->fecha_lote_iba?->format('d/m/Y') ?? 'N/A' }}</x-table.cell>
                            <x-table.cell>
                                <div class="space-y-0.5">
                                    <div>{{ $importacion->total_registros_iba }}</div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                        Reportado: {{ $importacion->total_registros_reportado_iba ?? 'N/A' }}
                                    </div>
                                </div>
                            </x-table.cell>
                            <x-table.cell>
                                <div class="space-y-0.5">
                                    <div>S/ {{ number_format((float) $importacion->total_monto_iba, 2) }}</div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                        Reportado: S/ {{ number_format((float) ($importacion->total_monto_reportado_iba ?? 0), 2) }}
                                    </div>
                                </div>
                            </x-table.cell>
                            <x-table.cell>
                                <flux:badge color="{{ $importacion->estado_iba->getColor() }}" size="lg">
                                    <x-slot name="icon">
                                        <x-ui.icon :name="$importacion->estado_iba->getIcon()" size="4" class="me-1" />
                                    </x-slot>
                                    {{ $importacion->estado_iba->getNombre() }}
                                </flux:badge>
                            </x-table.cell>
                            <x-table.cell>
                                {{ $importacion->fecha_importacion_iba?->format('d M Y, h:i a') }}</x-table.cell
                            >
                            <x-table.cell align="end">
                                <div class="flex items-center justify-end gap-1.5">
                                    @permiso ('pago_crear')
                                        <flux:button
                                            size="sm"
                                            variant="ghost"
                                            square
                                            tooltip="Validar pagos pendientes"
                                            tooltip:position="left"
                                            aria-label="Validar pagos pendientes"
                                            wire:click="$js.cargarAlertaValidarPendientes({{ $importacion->id_iba }}, '{{ $importacion->nombre_archivo_iba }}')"
                                            wire:loading.attr="disabled"
                                            wire:target="validarPagosPendientes({{ $importacion->id_iba }})"
                                            :disabled="$importacion->detalles_pendientes_count === 0"
                                        >
                                            <x-slot name="icon">
                                                <x-ui.icon name="shield-check" size="4" class="text-blue-500" />
                                            </x-slot>
                                        </flux:button>
                                    @endpermiso

                                    <flux:button
                                        size="sm"
                                        variant="filled"
                                        square
                                        tooltip="Ver detalle"
                                        tooltip:position="left"
                                        aria-label="Ver detalle"
                                        wire:click="verDetalle({{ $importacion->id_iba }})"
                                    >
                                        <x-slot name="icon">
                                            <x-ui.icon name="eye" size="4" />
                                        </x-slot>
                                    </flux:button>
                                </div>
                            </x-table.cell>
                        </x-table.row>
                    @empty
                        <x-table.row class="hover:bg-transparent dark:hover:bg-transparent">
                            <x-table.cell colspan="8" class="h-auto py-10 text-center whitespace-normal">
                                <div class="flex flex-col items-center justify-center space-y-3">
                                    <x-ui.icon name="money" size="12" class="text-zinc-400 dark:text-zinc-500" />
                                    <p class="text-zinc-500 dark:text-zinc-400">No se encontraron importaciones bancarias.</p>
                                </div>
                            </x-table.cell>
                        </x-table.row>
                    @endforelse
                </x-table.body>
            </x-table>

            <div
                class="absolute inset-0 bg-white/50 dark:bg-zinc-950/50"
                wire:loading
                wire:target="buscar, filtroEstado, limpiarFiltroEstado, nextPage, previousPage, gotoPage, verDetalle, validarPagosPendientes"
            ></div>
            <div
                class="absolute inset-0 flex items-center justify-center"
                wire:loading.flex
                wire:target="buscar, filtroEstado, limpiarFiltroEstado, nextPage, previousPage, gotoPage, verDetalle, validarPagosPendientes"
            >
                <x-ui.icon name="spinner-gap" size="6" class="animate-spin text-zinc-500 dark:text-zinc-300" />
            </div>
        </div>

        <div class="flex w-full justify-between">
            <div class="flex items-center text-sm text-zinc-500 dark:text-zinc-400">
                Mostrando {{ $this->importaciones->firstItem() ?? 0 }} - {{ $this->importaciones->lastItem() ?? 0 }} de {{ $this->importaciones->total() }} registros
            </div>
            {{ $this->importaciones->links() }}
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900">
        @if ($this->detalleImportacionSeleccionada)
            @php ($detalleImportacion = $this->detalleImportacionSeleccionada)
            <div class="space-y-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="space-y-2">
                        <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Detalle de importacion</p>
                        <div class="space-y-1">
                            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100">
                                {{ $detalleImportacion->nombre_archivo_iba }}
                            </h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                Lote {{ $detalleImportacion->fecha_lote_iba?->format('d/m/Y') ?? 'N/A' }} | {{ $detalleImportacion->moneda_iba ?? 'N/A' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <div
                            class="rounded-xl border border-zinc-200 bg-zinc-50/80 px-4 py-3 dark:border-white/10 dark:bg-zinc-950/40"
                        >
                            <p class="text-xs font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">Estado</p>
                            <div class="mt-2">
                                <flux:badge color="{{ $detalleImportacion->estado_iba->getColor() }}" size="lg">
                                    <x-slot name="icon">
                                        <x-ui.icon
                                            :name="$detalleImportacion->estado_iba->getIcon()"
                                            size="4"
                                            class="me-1"
                                        />
                                    </x-slot>
                                    {{ $detalleImportacion->estado_iba->getNombre() }}
                                </flux:badge>
                            </div>
                        </div>

                        <div
                            class="rounded-xl border border-zinc-200 bg-zinc-50/80 px-4 py-3 dark:border-white/10 dark:bg-zinc-950/40"
                        >
                            <p class="text-xs font-semibold tracking-wide text-zinc-500 uppercase dark:text-zinc-400">Totales</p>
                            <div class="mt-2 space-y-1 text-sm text-zinc-700 dark:text-zinc-200">
                                <p>{{ $detalleImportacion->total_registros_iba }} registros</p>
                                <p>S/ {{ number_format((float) $detalleImportacion->total_monto_iba, 2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                @if ($detalleImportacion->observacion_iba)
                    <flux:callout color="red" icon="exclamation-triangle">
                        {{ $detalleImportacion->observacion_iba }}
                    </flux:callout>
                @endif

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Operaciones</h4>
                        <flux:badge color="zinc" size="lg">{{ $detalleImportacion->detalles->count() }}</flux:badge>
                    </div>

                    <div class="grid gap-3 lg:grid-cols-2">
                        @foreach ($detalleImportacion->detalles as $detalle)
                            <div
                                class="rounded-xl border border-zinc-200 bg-zinc-50/80 p-4 dark:border-white/10 dark:bg-zinc-950/40"
                                wire:key="detalle-importacion-{{ $detalle->id_ibd }}"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="space-y-1">
                                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">
                                            {{ $detalle->nombre_depositante_ibd ?: 'SIN NOMBRE REGISTRADO' }}
                                        </p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $this->detalleResumen($detalle) }}</p>
                                        @if ($detalle->numero_operacion_raw_ibd && $detalle->numero_operacion_visible_ibd && $detalle->numero_operacion_raw_ibd !== $detalle->numero_operacion_visible_ibd)
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                                Operacion banco: {{ $detalle->numero_operacion_raw_ibd }}
                                            </p>
                                        @endif
                                    </div>

                                    <flux:badge color="{{ $this->estadoDetalleColor($detalle) }}" size="lg">
                                        <x-slot name="icon">
                                            <x-ui.icon :name="$detalle->estado_ibd->getIcon()" size="4" class="me-1" />
                                        </x-slot>
                                        {{ $this->estadoDetalleNombre($detalle) }}
                                    </flux:badge>
                                </div>

                                <div
                                    class="mt-3 flex items-center justify-between gap-3 text-sm text-zinc-600 dark:text-zinc-300"
                                >
                                    <p>{{ $detalle->fecha_pago_ibd?->format('d/m/Y') ?? 'Sin fecha' }}</p>
                                    <p class="font-medium">S/ {{ number_format((float) $detalle->monto_ibd, 2) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @else
            <div class="flex min-h-72 flex-col items-center justify-center space-y-3 text-center">
                <x-ui.icon name="wallet" size="12" class="text-zinc-400 dark:text-zinc-500" />
                <p class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Seleccione una importacion</p>
                <p class="max-w-xs text-sm text-zinc-500 dark:text-zinc-400">Aqui vera el resumen del lote y las operaciones importadas desde el TXT del banco.</p>
            </div>
        @endif
    </div>
</div>

@script
    <script>
        this.$js.cargarAlertaValidarPendientes = (id_importacion, nombre_archivo) => {
            this.$dispatch('alert', {
                type: 'question',
                title: 'Validar pagos pendientes',
                message:
                    'Se asociaran solo los pagos pendientes de "' +
                    nombre_archivo +
                    '" que coincidan por DNI y saldo pendiente. Los pagos ya asociados no se volveran a procesar.',
                showCancel: true,
                showConfirm: true,
                confirmText: 'Validar',
                cancelText: 'Cancelar',
                confirmEvent: 'pago-validar-pendientes',
                confirmParams: { id_importacion: id_importacion },
            });
        };
    </script>
@endscript
