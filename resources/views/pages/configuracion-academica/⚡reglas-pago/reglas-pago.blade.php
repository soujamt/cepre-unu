@section ('icon')
    <x-header.icon icon="receipt" />
@endsection

@section ('header')
    <x-header.title>REGLAS DE PAGO</x-header.title>
@endsection

@section ('breadcrumbs')
    <x-breadcrumbs>
        <x-breadcrumbs.item href="{{ route('inicio') }}" wire:navigate>INICIO</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item href="{{ route('reglas-pago.index') }}" wire:navigate>REGLAS DE PAGO</x-breadcrumbs.item>
    </x-breadcrumbs>
@endsection

<div class="space-y-5">
    @php
        $puedeGestionarAcciones = auth()->user()?->hasPermisoAccion('regla_pago_editar')
            || auth()->user()?->hasPermisoAccion('regla_pago_estado')
            || auth()->user()?->hasPermisoAccion('regla_pago_eliminar');
    @endphp

    <div class="flex justify-start gap-3">
        <div class="w-full lg:w-xs">
            <flux:input wire:model.live.debounce.500ms="buscar" placeholder="Buscar reglas de pago" clearable>
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
                            <div class="me-1.5 flex size-6 items-center justify-center rounded-sm border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900 dark:shadow-black">
                                <x-ui.icon :name="$estado->getIcon()" size="4" class="text-{{ $estado->getColor() }}-500" />
                            </div>
                            {{ ucfirst(strtolower($estado->getNombre())) }}
                        </flux:menu.checkbox>
                    @endforeach

                    @if (count($this->estadosSeleccionados) > 0)
                        <flux:menu.separator />
                        <flux:menu.item variant="danger" wire:click="limpiarFiltroEstado" class="font-normal!">
                            <x-slot name="icon">
                                <div class="me-1.5 flex size-6 items-center justify-center rounded-sm border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900 dark:shadow-black">
                                    <x-ui.icon name="trash" size="4" />
                                </div>
                            </x-slot>
                            Limpiar filtros
                        </flux:menu.item>
                    @endif
                </flux:menu>
            </flux:dropdown>
        </div>

        @permiso('regla_pago_crear')
            <flux:button class="ms-auto w-full font-normal lg:w-auto" variant="primary" wire:click="cargarReglaPago">
                <x-slot name="icon">
                    <x-ui.icon name="plus" size="5" class="text-white" />
                </x-slot>
                Nuevo
            </flux:button>
        @endpermiso
    </div>

    <div class="relative">
        <x-table>
            <x-table.head>
                <x-table.row>
                    <x-table.heading class="w-16 max-w-16">N&deg;</x-table.heading>
                    <x-table.heading class="min-w-36">CONCEPTO</x-table.heading>
                    <x-table.heading class="min-w-64">CONTEXTO</x-table.heading>
                    <x-table.heading class="min-w-28">MONTO</x-table.heading>
                    <x-table.heading class="min-w-28">CUOTAS</x-table.heading>
                    <x-table.heading class="min-w-34">VIGENCIA</x-table.heading>
                    <x-table.heading class="min-w-30">ESTADO</x-table.heading>
                    <x-table.heading class="min-w-35">REGISTRO</x-table.heading>
                    <x-table.heading class="w-24"></x-table.heading>
                </x-table.row>
            </x-table.head>

            <x-table.body>
                @forelse ($this->reglasPago as $reglaPago)
                    <x-table.row>
                        <x-table.cell>{{ $this->reglasPago->firstItem() + $loop->index }}</x-table.cell>
                        <x-table.cell class="whitespace-normal py-1">
                            <div class="space-y-0.5">
                                <div class="font-medium text-zinc-800 dark:text-zinc-100">{{ $reglaPago->conceptoPago?->nombre_cpg }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $reglaPago->conceptoPago?->codigo_cpg }}</div>
                            </div>
                        </x-table.cell>
                        <x-table.cell class="whitespace-normal py-1">
                            <div class="space-y-0.5">
                                <div class="font-medium leading-5 text-zinc-800 dark:text-zinc-100">{{ $reglaPago->proceso?->nombre_pro }}</div>
                                <div class="flex flex-wrap gap-2">
                                    <flux:badge color="zinc" size="lg">
                                        {{ $reglaPago->modalidadAdmision?->nombre_mad ?? 'TODAS LAS MODALIDADES' }}
                                    </flux:badge>
                                    @if ($reglaPago->convenioProceso?->convenio?->nombre_con)
                                        <flux:badge color="sky" size="lg">
                                            {{ $reglaPago->convenioProceso->convenio->nombre_con }}
                                        </flux:badge>
                                    @endif
                                </div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $reglaPago->proceso?->semestre?->nombre_sem }}
                                </div>
                            </div>
                        </x-table.cell>
                        <x-table.cell class="whitespace-normal py-1">
                            <div class="space-y-0.5">
                                <div class="font-medium text-zinc-800 dark:text-zinc-100">S/ {{ number_format((float) $reglaPago->monto_total_rpa, 2) }}</div>
                                @if ($reglaPago->requiere_pago_completo_rpa)
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">Exige pago completo</div>
                                @endif
                            </div>
                        </x-table.cell>
                        <x-table.cell class="whitespace-normal py-1">
                            @if ($reglaPago->permite_cuotas_rpa)
                                <div class="space-y-0.5">
                                    <div class="font-medium text-zinc-800 dark:text-zinc-100">{{ $reglaPago->numero_maximo_cuotas_rpa }} max.</div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                        Min. 1ra cuota: S/ {{ number_format((float) $reglaPago->monto_minimo_primera_cuota_rpa, 2) }}
                                    </div>
                                </div>
                            @else
                                <flux:badge color="zinc" size="lg">Pago unico</flux:badge>
                            @endif
                        </x-table.cell>
                        <x-table.cell class="whitespace-normal py-1">
                            <div class="space-y-0.5">
                                <div>{{ $reglaPago->vigente_desde_rpa?->format('d/m/Y') }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $reglaPago->vigente_hasta_rpa?->format('d/m/Y') ?? 'Sin fecha final' }}
                                </div>
                            </div>
                        </x-table.cell>
                        <x-table.cell>
                            <flux:badge color="{{ $reglaPago->estado_rpa->getColor() }}" size="lg">
                                <x-slot name="icon">
                                    <x-ui.icon :name="$reglaPago->estado_rpa->getIcon()" size="4" class="me-1" />
                                </x-slot>
                                {{ $reglaPago->estado_rpa->getNombre() }}
                            </flux:badge>
                        </x-table.cell>
                        <x-table.cell>{{ $reglaPago->creado_en->format('d M Y, h:i a') }}</x-table.cell>
                        <x-table.cell align="end">
                            @if ($puedeGestionarAcciones)
                                <flux:dropdown>
                                    <flux:button icon:trailing="chevron-down" size="sm" variant="filled" class="font-normal">
                                        Acciones
                                    </flux:button>
                                    <flux:menu>
                                        @permiso('regla_pago_editar')
                                            <flux:menu.item wire:click="cargarReglaPago({{ $reglaPago->id_rpa }})">
                                                <x-slot name="icon">
                                                    <x-ui.icon name="pencil" size="4" class="me-2" />
                                                </x-slot>
                                                Editar
                                            </flux:menu.item>
                                        @endpermiso

                                        @permiso('regla_pago_estado')
                                            <flux:menu.item wire:click="$js.cargarAlertaEstado({{ $reglaPago->id_rpa }}, '{{ $reglaPago->conceptoPago?->nombre_cpg }}')">
                                                <x-slot name="icon">
                                                    <x-ui.icon name="arrows-clockwise" size="4" class="me-2" />
                                                </x-slot>
                                                Estado
                                            </flux:menu.item>
                                        @endpermiso

                                        @permiso('regla_pago_eliminar')
                                            <flux:menu.item
                                                variant="danger"
                                                wire:click="$js.cargarAlertaEliminar({{ $reglaPago->id_rpa }}, '{{ $reglaPago->conceptoPago?->nombre_cpg }}')"
                                            >
                                                <x-slot name="icon">
                                                    <x-ui.icon name="trash" size="4" class="me-2" />
                                                </x-slot>
                                                Eliminar
                                            </flux:menu.item>
                                        @endpermiso
                                    </flux:menu>
                                </flux:dropdown>
                            @endif
                        </x-table.cell>
                    </x-table.row>
                @empty
                    <x-table.row class="hover:bg-transparent dark:hover:bg-transparent">
                        <x-table.cell colspan="9" class="h-auto py-10 text-center whitespace-normal">
                            <div class="flex flex-col items-center justify-center space-y-3">
                                <x-ui.icon name="receipt" size="12" class="text-zinc-400 dark:text-zinc-500" />
                                <p class="text-zinc-500 dark:text-zinc-400">No se encontraron reglas de pago.</p>
                            </div>
                        </x-table.cell>
                    </x-table.row>
                @endforelse
            </x-table.body>
        </x-table>

        <div
            class="absolute inset-0 bg-white/50 dark:bg-zinc-950/50"
            wire:loading
            wire:target="buscar, filtroEstado, limpiarFiltroEstado, nextPage, previousPage, gotoPage, guardar, cambiarEstado, eliminar"
        ></div>
        <div
            class="absolute inset-0 flex items-center justify-center"
            wire:loading.flex
            wire:target="buscar, filtroEstado, limpiarFiltroEstado, nextPage, previousPage, gotoPage, guardar, cambiarEstado, eliminar"
        >
            <x-ui.icon name="spinner-gap" size="6" class="animate-spin text-zinc-500 dark:text-zinc-300" />
        </div>
    </div>

    <div class="flex w-full justify-between">
        <div class="flex items-center text-sm text-zinc-500 dark:text-zinc-400">
            Mostrando {{ $this->reglasPago->firstItem() ?? 0 }} - {{ $this->reglasPago->lastItem() ?? 0 }} de {{ $this->reglasPago->total() }} registros
        </div>
        {{ $this->reglasPago->links() }}
    </div>

    @if (auth()->user()?->hasPermisoAccion('regla_pago_crear') || auth()->user()?->hasPermisoAccion('regla_pago_editar'))
        <flux:modal flyout variant="floating" name="modal-regla-pago" class="px-0! py-5! md:w-lg!">
            <div class="space-y-5">
                <flux:heading size="lg" class="px-5">{{ $tituloModal }}</flux:heading>
                <flux:separator variant="subtle" />

                <form wire:submit="guardar" class="space-y-5">
                    <div class="space-y-5 px-5">
                        <div class="grid gap-5 md:grid-cols-2">
                            <x-ui.select
                                label="Concepto de pago"
                                wire:model.live="reglaPagoForm.id_concepto_pago"
                                :invalid="$errors->has('reglaPagoForm.id_concepto_pago')"
                                :error="$errors->first('reglaPagoForm.id_concepto_pago')"
                                required
                                floating
                            >
                                <option value="">Seleccionar concepto</option>
                                @foreach ($this->conceptosPagoDisponibles as $conceptoPago)
                                    <option wire:key="concepto-pago-{{ $conceptoPago->id_cpg }}" value="{{ $conceptoPago->id_cpg }}">
                                        {{ $conceptoPago->nombre_cpg }}
                                    </option>
                                @endforeach
                            </x-ui.select>

                            <x-ui.select
                                label="Proceso"
                                wire:model.live="reglaPagoForm.id_proceso"
                                :invalid="$errors->has('reglaPagoForm.id_proceso')"
                                :error="$errors->first('reglaPagoForm.id_proceso')"
                                required
                                floating
                            >
                                <option value="">Seleccionar proceso</option>
                                @foreach ($this->procesosDisponibles as $proceso)
                                    <option wire:key="regla-proceso-{{ $proceso->id_pro }}" value="{{ $proceso->id_pro }}">
                                        {{ $proceso->nombre_pro }} | {{ $proceso->semestre?->nombre_sem }}
                                    </option>
                                @endforeach
                            </x-ui.select>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <x-ui.select
                                label="Modalidad"
                                wire:model.live="reglaPagoForm.id_modalidad_admision"
                                :invalid="$errors->has('reglaPagoForm.id_modalidad_admision')"
                                :error="$errors->first('reglaPagoForm.id_modalidad_admision')"
                                hint="Opcional. Si queda vacio, aplica a todas las modalidades."
                                floating
                            >
                                <option value="">Todas las modalidades</option>
                                @foreach ($this->modalidadesDisponibles as $modalidad)
                                    <option wire:key="regla-modalidad-{{ $modalidad->id_mad }}" value="{{ $modalidad->id_mad }}">
                                        {{ $modalidad->nombre_mad }}
                                    </option>
                                @endforeach
                            </x-ui.select>

                            <x-ui.select
                                label="Convenio"
                                wire:model.live="reglaPagoForm.id_convenio_proceso"
                                :invalid="$errors->has('reglaPagoForm.id_convenio_proceso')"
                                :error="$errors->first('reglaPagoForm.id_convenio_proceso')"
                                hint="Opcional. Se filtra segun el proceso elegido."
                                floating
                            >
                                <option value="">Sin convenio</option>
                                @foreach ($this->conveniosProcesoDisponibles as $convenioProceso)
                                    <option wire:key="regla-convenio-{{ $convenioProceso->id_cpr }}" value="{{ $convenioProceso->id_cpr }}">
                                        {{ $convenioProceso->convenio?->nombre_con }}
                                    </option>
                                @endforeach
                            </x-ui.select>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <x-ui.input
                                label="Monto total"
                                type="number"
                                min="0"
                                step="0.01"
                                wire:model="reglaPagoForm.monto_total"
                                :invalid="$errors->has('reglaPagoForm.monto_total')"
                                :error="$errors->first('reglaPagoForm.monto_total')"
                                required
                                floating
                            />

                            <x-ui.input
                                label="Numero maximo de cuotas"
                                type="number"
                                min="1"
                                step="1"
                                wire:model="reglaPagoForm.numero_maximo_cuotas"
                                :invalid="$errors->has('reglaPagoForm.numero_maximo_cuotas')"
                                :error="$errors->first('reglaPagoForm.numero_maximo_cuotas')"
                                required
                                :disabled="! $reglaPagoForm['permite_cuotas']"
                                floating
                            />
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <x-ui.input
                                label="Monto minimo primera cuota"
                                type="number"
                                min="0"
                                step="0.01"
                                wire:model="reglaPagoForm.monto_minimo_primera_cuota"
                                :invalid="$errors->has('reglaPagoForm.monto_minimo_primera_cuota')"
                                :error="$errors->first('reglaPagoForm.monto_minimo_primera_cuota')"
                                :disabled="! $reglaPagoForm['permite_cuotas']"
                                hint="Solo se usa cuando la regla permite cuotas."
                                floating
                            />

                            <div class="grid gap-3 sm:grid-cols-2">
                                <label class="flex items-center gap-3 rounded-lg border border-zinc-200 px-4 py-3 dark:border-zinc-700">
                                    <input
                                        type="checkbox"
                                        wire:model.live="reglaPagoForm.permite_cuotas"
                                        class="size-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-400 dark:border-zinc-600 dark:bg-zinc-900"
                                    />
                                    <div class="space-y-1">
                                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">Permite cuotas</p>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Habilita pagos parciales.</p>
                                    </div>
                                </label>

                                <label class="flex items-center gap-3 rounded-lg border border-zinc-200 px-4 py-3 dark:border-zinc-700">
                                    <input
                                        type="checkbox"
                                        wire:model.live="reglaPagoForm.requiere_pago_completo"
                                        class="size-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-400 dark:border-zinc-600 dark:bg-zinc-900"
                                    />
                                    <div class="space-y-1">
                                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">Exige pago completo</p>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Para habilitar la inscripcion.</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <x-ui.input
                                label="Vigente desde"
                                type="date"
                                wire:model="reglaPagoForm.vigente_desde"
                                :invalid="$errors->has('reglaPagoForm.vigente_desde')"
                                :error="$errors->first('reglaPagoForm.vigente_desde')"
                                required
                                floating
                            />

                            <x-ui.input
                                label="Vigente hasta"
                                type="date"
                                wire:model="reglaPagoForm.vigente_hasta"
                                :invalid="$errors->has('reglaPagoForm.vigente_hasta')"
                                :error="$errors->first('reglaPagoForm.vigente_hasta')"
                                hint="Opcional. Dejalo vacio si no tiene fecha final."
                                floating
                            />
                        </div>
                    </div>

                    <flux:separator variant="subtle" />

                    <div class="flex px-5">
                        <flux:spacer />
                        <flux:button variant="primary" type="submit" class="font-normal">
                            <x-slot name="icon">
                                <x-ui.icon name="floppy-disk" size="5" class="text-white" />
                            </x-slot>
                            Guardar
                        </flux:button>
                    </div>
                </form>
            </div>
        </flux:modal>
    @endif
</div>

@script
    <script>
        this.$js.cargarAlertaEstado = (id_regla_pago, nombre) => {
            this.$dispatch('alert', {
                type: 'question',
                title: 'Confirmacion requerida',
                message: 'Â¿Esta seguro de que desea cambiar el estado de la regla de pago "' + nombre + '"?',
                showCancel: true,
                showConfirm: true,
                confirmText: 'Aceptar',
                cancelText: 'Cancelar',
                confirmEvent: 'regla-pago-cambiar-estado',
                confirmParams: { id_regla_pago: id_regla_pago },
            });
        };

        this.$js.cargarAlertaEliminar = (id_regla_pago, nombre) => {
            this.$dispatch('alert', {
                type: 'error',
                title: 'Atencion',
                message: 'Â¿Esta seguro de que desea eliminar la regla de pago "' + nombre + '"? Esta accion es irreversible.',
                showCancel: true,
                showConfirm: true,
                confirmText: 'Eliminar',
                cancelText: 'Cancelar',
                confirmEvent: 'regla-pago-eliminar',
                confirmParams: { id_regla_pago: id_regla_pago },
            });
        };
    </script>
@endscript
