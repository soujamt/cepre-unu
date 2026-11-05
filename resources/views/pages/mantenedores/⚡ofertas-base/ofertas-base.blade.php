@section ('icon')
    <x-header.icon icon="grid" />
@endsection

@section ('header')
    <x-header.title>Ofertas base</x-header.title>
@endsection

@section ('breadcrumbs')
    <x-breadcrumbs>
        <x-breadcrumbs.item href="{{ route('inicio') }}" wire:navigate>Inicio</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item href="{{ route('ofertas-base.index') }}" wire:navigate>Ofertas base</x-breadcrumbs.item>
    </x-breadcrumbs>
@endsection

<div class="space-y-5">
    @php
        $puedeGestionarAcciones = auth()->user()?->hasPermisoAccion('oferta_base_editar')
            || auth()->user()?->hasPermisoAccion('oferta_base_estado')
            || auth()->user()?->hasPermisoAccion('oferta_base_eliminar');
    @endphp

    <div class="flex flex-wrap items-center gap-3">
        <div class="w-full sm:w-64">
            <flux:input wire:model.live.debounce.500ms="buscar" placeholder="Buscar código, carrera o lugar" clearable>
                <x-slot name="icon">
                    <x-ui.icon name="search" size="5" class="text-zinc-400 dark:text-zinc-500" />
                </x-slot>
            </flux:input>
        </div>

        <div class="w-full sm:w-44">
            <x-ui.select wire:model.live="idSede" placeholder="Todas las sedes">
                <option value="">Todas las sedes</option>
                @foreach ($this->sedes as $sedeFiltro)
                    <option wire:key="sede-filtro-{{ $sedeFiltro->id_sed }}" value="{{ $sedeFiltro->id_sed }}">{{ $sedeFiltro->nombre_sed }}</option>
                @endforeach
            </x-ui.select>
        </div>

        <div class="w-full sm:w-52">
            <x-ui.select wire:model.live="idModalidad" placeholder="Todas las modalidades">
                <option value="">Todas las modalidades</option>
                @foreach ($this->modalidadesFiltro as $modalidadFiltro)
                    <option wire:key="modalidad-filtro-{{ $modalidadFiltro->id_mad }}" value="{{ $modalidadFiltro->id_mad }}">{{ $modalidadFiltro->nombre_mad }}</option>
                @endforeach
            </x-ui.select>
        </div>

        <div class="w-full sm:w-auto">
            <flux:dropdown>
                <flux:button class="w-full font-normal" icon:trailing="chevron-down">
                    <x-slot name="icon">
                        <x-ui.icon name="filter" size="5" />
                    </x-slot>
                    Estado
                    @if (count($this->estadosSeleccionados) > 0)
                        <span class="ms-1 inline-flex min-w-5 items-center justify-center rounded-full bg-index-500 px-1.5 text-[11px] leading-5 font-semibold text-white">{{ count($this->estadosSeleccionados) }}</span>
                    @endif
                </flux:button>

                <flux:menu keep-open class="dark:bg-zinc-800">
                    @foreach ($this->estados() as $estado)
                        <flux:menu.checkbox wire:model.live="filtroEstado.{{ $estado->value }}" class="font-normal">
                            <x-ui.icon :name="$estado->getIcon()" size="4" class="me-2 text-{{ $estado->getColor() }}-500" />
                            {{ ucfirst(strtolower($estado->getNombre())) }}
                        </flux:menu.checkbox>
                    @endforeach

                    @if (count($this->estadosSeleccionados) > 0)
                        <flux:menu.separator />
                        <flux:menu.item variant="danger" wire:click="limpiarFiltroEstado" class="font-normal!">
                            <x-slot name="icon">
                                <x-ui.icon name="trash" size="4" class="me-2" />
                            </x-slot>
                            Limpiar filtros
                        </flux:menu.item>
                    @endif
                </flux:menu>
            </flux:dropdown>
        </div>

        @permiso ('oferta_base_crear')
            <flux:button class="w-full font-normal sm:ms-auto sm:w-auto" variant="primary" wire:click="cargarOferta">
                <x-slot name="icon">
                    <x-ui.icon name="plus" size="5" class="text-white" />
                </x-slot>
                Nueva
            </flux:button>
        @endpermiso
    </div>

    <div class="relative">
        <x-table>
            <x-table.head>
                <x-table.row>
                    <x-table.heading class="w-16 max-w-16">N&deg;</x-table.heading>
                    <x-table.heading class="w-24">CÓDIGO</x-table.heading>
                    <x-table.heading>CARRERA</x-table.heading>
                    <x-table.heading>MODALIDAD</x-table.heading>
                    <x-table.heading>LUGAR</x-table.heading>
                    <x-table.heading class="w-24">PROCESOS</x-table.heading>
                    <x-table.heading>ESTADO</x-table.heading>
                    <x-table.heading class="w-14"><span class="sr-only">Acciones</span></x-table.heading>
                </x-table.row>
            </x-table.head>

            <x-table.body>
                @forelse ($this->ofertas as $oferta)
                    <x-table.row wire:key="oferta-base-{{ $oferta->id_ofb }}">
                        <x-table.cell>{{ $this->ofertas->firstItem() + $loop->index }}</x-table.cell>
                        <x-table.cell>
                            <span class="font-mono text-xs font-medium text-zinc-600 dark:text-zinc-300">{{ $oferta->codigo_externo_ofb ?: '—' }}</span>
                        </x-table.cell>
                        <x-table.cell class="h-auto py-2 whitespace-normal">
                            <span class="block font-medium text-zinc-800 dark:text-zinc-100">{{ $oferta->sedeCarrera?->carrera?->nombre_car ?? 'SIN CARRERA' }}</span>
                            <span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ $oferta->sedeCarrera?->sede?->nombre_sed ?? 'SIN SEDE' }}</span>
                        </x-table.cell>
                        <x-table.cell class="whitespace-normal">{{ $oferta->modalidadLugarInscripcion?->modalidadAdmision?->nombre_mad ?? 'SIN MODALIDAD' }}</x-table.cell>
                        <x-table.cell class="h-auto py-2 whitespace-normal">
                            @if ($oferta->modalidadLugarInscripcion?->lugarInscripcion)
                                <span class="block">{{ $oferta->modalidadLugarInscripcion->lugarInscripcion->nombre_lin }}</span>
                                <span class="block font-mono text-xs text-zinc-400 dark:text-zinc-500">{{ $oferta->modalidadLugarInscripcion->lugarInscripcion->codigo_lin }}</span>
                            @else
                                <span class="text-zinc-400 dark:text-zinc-500">SIN LUGAR</span>
                            @endif
                        </x-table.cell>
                        <x-table.cell>{{ $oferta->procesos_oferta_count }}</x-table.cell>
                        <x-table.cell>
                            <flux:badge color="{{ $oferta->estado_ofb->getColor() }}">
                                <x-slot name="icon">
                                    <x-ui.icon :name="$oferta->estado_ofb->getIcon()" size="4" class="me-1" />
                                </x-slot>
                                {{ $oferta->estado_ofb->getNombre() }}
                            </flux:badge>
                        </x-table.cell>
                        <x-table.cell align="end">
                            @if ($puedeGestionarAcciones)
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button size="sm" variant="subtle" square aria-label="Acciones">
                                        <x-slot name="icon">
                                            <x-ui.icon name="dots-vertical-rounded" size="5" />
                                        </x-slot>
                                    </flux:button>
                                    <flux:menu>
                                        @permiso ('oferta_base_editar')
                                            <flux:menu.item wire:click="cargarOferta({{ $oferta->id_ofb }})">
                                                <x-slot name="icon">
                                                    <x-ui.icon name="pencil" size="4" class="me-2" />
                                                </x-slot>
                                                Editar
                                            </flux:menu.item>
                                        @endpermiso

                                        @permiso ('oferta_base_estado')
                                            <flux:menu.item wire:click="$js.cargarAlertaEstado({{ $oferta->id_ofb }}, {{ \Illuminate\Support\Js::from($oferta->codigo_externo_ofb ?? (string) $oferta->id_ofb) }})">
                                                <x-slot name="icon">
                                                    <x-ui.icon name="refresh-cw" size="4" class="me-2" />
                                                </x-slot>
                                                Estado
                                            </flux:menu.item>
                                        @endpermiso

                                        @permiso ('oferta_base_eliminar')
                                            <flux:menu.item
                                                variant="danger"
                                                wire:click="$js.cargarAlertaEliminar({{ $oferta->id_ofb }}, {{ \Illuminate\Support\Js::from($oferta->codigo_externo_ofb ?? (string) $oferta->id_ofb) }})"
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
                        <x-table.cell colspan="8" class="h-auto py-10 text-center whitespace-normal">
                            <div class="flex flex-col items-center justify-center space-y-3">
                                <x-ui.icon name="grid" size="12" class="text-zinc-400 dark:text-zinc-500" />
                                <p class="text-zinc-500 dark:text-zinc-400">No se encontraron ofertas base registradas.</p>
                            </div>
                        </x-table.cell>
                    </x-table.row>
                @endforelse
            </x-table.body>
        </x-table>

        <div
            class="absolute inset-0 bg-white/50 dark:bg-zinc-950/50"
            wire:loading
            wire:target="buscar, idSede, idModalidad, filtroEstado, limpiarFiltroEstado, nextPage, previousPage, gotoPage, guardar, cambiarEstado, eliminar"
        ></div>
        <div
            class="absolute inset-0 flex items-center justify-center"
            wire:loading.flex
            wire:target="buscar, idSede, idModalidad, filtroEstado, limpiarFiltroEstado, nextPage, previousPage, gotoPage, guardar, cambiarEstado, eliminar"
        >
            <x-ui.icon name="loader-lines" size="6" class="animate-spin text-zinc-500 dark:text-zinc-300" />
        </div>
    </div>

    <div class="flex w-full justify-between">
        <div class="flex items-center text-sm text-zinc-500 dark:text-zinc-400">
            Mostrando {{ $this->ofertas->firstItem() ?? 0 }} - {{ $this->ofertas->lastItem() ?? 0 }} de {{ $this->ofertas->total() }} registros
        </div>
        {{ $this->ofertas->links() }}
    </div>

    @if (auth()->user()?->hasPermisoAccion('oferta_base_crear') || auth()->user()?->hasPermisoAccion('oferta_base_editar'))
        <flux:modal flyout variant="floating" name="modal-oferta-base" class="px-0! py-5! md:w-md!">
            <div class="space-y-5">
                <flux:heading size="lg" class="px-5">{{ $tituloModal }}</flux:heading>
                <flux:separator variant="subtle" />

                <form wire:submit="guardar" class="space-y-5">
                    <div class="space-y-5 px-5">
                        @if ($combinacionBloqueada)
                            <div class="flex items-start gap-2 rounded-lg border border-amber-300/60 bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                                <x-ui.icon name="info-circle" size="4" class="mt-0.5 shrink-0" />
                                <span>Esta oferta ya se usa en procesos: solo se puede cambiar el código.</span>
                            </div>
                        @endif

                        <x-ui.input
                            label="Código"
                            type="text"
                            wire:model="ofertaForm.codigo"
                            :invalid="$errors->has('ofertaForm.codigo')"
                            :error="$errors->first('ofertaForm.codigo')"
                            maxlength="20"
                            required
                            floating
                        />

                        <x-ui.select
                            label="Sede"
                            wire:model.live="ofertaForm.id_sede"
                            :invalid="$errors->has('ofertaForm.id_sede')"
                            :error="$errors->first('ofertaForm.id_sede')"
                            :disabled="$combinacionBloqueada"
                            required
                            floating
                        >
                            <option value="">Selecciona una sede</option>
                            @foreach ($this->sedes as $sedeOpcion)
                                <option wire:key="sede-form-{{ $sedeOpcion->id_sed }}" value="{{ $sedeOpcion->id_sed }}">{{ $sedeOpcion->nombre_sed }}</option>
                            @endforeach
                        </x-ui.select>

                        <x-ui.select
                            label="Lugar de inscripción"
                            wire:model.live="ofertaForm.id_lugar"
                            :invalid="$errors->has('ofertaForm.id_lugar')"
                            :error="$errors->first('ofertaForm.id_lugar')"
                            :disabled="$combinacionBloqueada || blank($ofertaForm['id_sede'])"
                            required
                            floating
                        >
                            <option value="">Selecciona un lugar</option>
                            @foreach ($this->lugaresFormulario as $lugarOpcion)
                                <option wire:key="lugar-form-{{ $lugarOpcion->id_lin }}" value="{{ $lugarOpcion->id_lin }}">{{ $lugarOpcion->codigo_lin }} · {{ $lugarOpcion->nombre_lin }}</option>
                            @endforeach
                        </x-ui.select>

                        <x-ui.select
                            label="Modalidad"
                            wire:model="ofertaForm.id_modalidad"
                            :invalid="$errors->has('ofertaForm.id_modalidad')"
                            :error="$errors->first('ofertaForm.id_modalidad')"
                            :disabled="$combinacionBloqueada || blank($ofertaForm['id_lugar'])"
                            required
                            floating
                        >
                            <option value="">Selecciona una modalidad</option>
                            @foreach ($this->modalidadesFormulario as $modalidadOpcion)
                                <option wire:key="modalidad-form-{{ $modalidadOpcion->id_mad }}" value="{{ $modalidadOpcion->id_mad }}">{{ $modalidadOpcion->nombre_mad }}</option>
                            @endforeach
                        </x-ui.select>

                        <x-ui.select
                            label="Carrera"
                            wire:model="ofertaForm.id_sede_carrera"
                            :invalid="$errors->has('ofertaForm.id_sede_carrera')"
                            :error="$errors->first('ofertaForm.id_sede_carrera')"
                            :disabled="$combinacionBloqueada || blank($ofertaForm['id_sede'])"
                            required
                            floating
                        >
                            <option value="">Selecciona una carrera</option>
                            @foreach ($this->carrerasFormulario as $sedeCarreraOpcion)
                                <option wire:key="carrera-form-{{ $sedeCarreraOpcion->id_sca }}" value="{{ $sedeCarreraOpcion->id_sca }}">{{ $sedeCarreraOpcion->carrera?->nombre_car }}</option>
                            @endforeach
                        </x-ui.select>

                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            Solo aparecen los lugares y carreras de la sede elegida, y las modalidades asignadas al lugar.
                        </p>
                    </div>

                    <flux:separator variant="subtle" />

                    <div class="flex px-5">
                        <flux:spacer />
                        <flux:button variant="primary" type="submit" class="font-normal">
                            <x-slot name="icon">
                                <x-ui.icon name="save" size="5" class="text-white" />
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
        this.$js.cargarAlertaEstado = (id_oferta, codigo) => {
            this.$dispatch('alert', {
                type: 'question',
                title: 'Confirmación requerida',
                message: '¿Está seguro de que desea cambiar el estado de la oferta base "' + codigo + '"?',
                showCancel: true,
                showConfirm: true,
                confirmText: 'Aceptar',
                cancelText: 'Cancelar',
                confirmEvent: 'oferta-base-cambiar-estado',
                confirmParams: { id_oferta: id_oferta },
            });
        };

        this.$js.cargarAlertaEliminar = (id_oferta, codigo) => {
            this.$dispatch('alert', {
                type: 'error',
                title: 'Atención',
                message: '¿Está seguro de que desea eliminar la oferta base "' + codigo + '"? Esta acción es irreversible.',
                showCancel: true,
                showConfirm: true,
                confirmText: 'Eliminar',
                cancelText: 'Cancelar',
                confirmEvent: 'oferta-base-eliminar',
                confirmParams: { id_oferta: id_oferta },
            });
        };
    </script>
@endscript
