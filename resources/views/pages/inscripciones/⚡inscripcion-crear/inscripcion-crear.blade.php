@section ('icon')
    <x-header.icon icon="note-pencil" />
@endsection

@section ('header')
    <x-header.title>NUEVA INSCRIPCION</x-header.title>
@endsection

@section ('breadcrumbs')
    <x-breadcrumbs>
        <x-breadcrumbs.item href="{{ route('inicio') }}" wire:navigate>INICIO</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item href="{{ route('inscripciones.index') }}" wire:navigate>INSCRIPCIONES</x-breadcrumbs.item>
        <x-breadcrumbs.separator />
        <x-breadcrumbs.item href="{{ route('inscripciones.crear') }}" wire:navigate>
            NUEVA INSCRIPCION</x-breadcrumbs.item
        >
    </x-breadcrumbs>
@endsection

<div class="-mt-5 space-y-5">
    <div class="grid gap-5 xl:grid-cols-[19rem_minmax(0,1fr)]">
        <div class="space-y-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900">
                <div class="space-y-5">
                    <div class="space-y-1">
                        <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Proceso de registro</p>
                        <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100">Formulario por pasos</h3>
                    </div>

                    <div class="relative">
                        {{-- Linea conectora vertical --}}
                        <div class="absolute top-5 bottom-5 left-5 w-px bg-zinc-200 dark:bg-white/10"></div>

                        <div class="relative space-y-1">
                            @foreach ($this->pasos as $indice => $paso)
                                @php
                                    $esActivo = $pasoActual === $indice;
                                    $esCompletado = $indice < $pasoActual && match ($indice) {
                                        1 => $personaId !== null,
                                        2 => filled($generalForm['anio_graduacion']),
                                        3 => $inscripcionId !== null,
                                        4 => $this->tienePagoValidadoWizard,
                                        default => false,
                                    };
                                    $esPendiente = ! $esActivo && ! $esCompletado;
                                @endphp
                                <button
                                    type="button"
                                    wire:key="paso-inscripcion-{{ $indice }}"
                                    wire:click="irPaso({{ $indice }})"
                                    @class ([
                                        'group relative w-full rounded-xl p-2.5 text-left transition',
                                        'bg-yellow-500 shadow-sm dark:bg-yellow-500' => $esActivo,
                                        'hover:bg-zinc-50 dark:hover:bg-zinc-800/50' => ! $esActivo,
                                    ])
                                >
                                    <div class="flex items-center gap-3">
                                        {{-- Indicador circular --}}
                                        <div
                                            @class ([
                                                'relative z-10 flex size-10 shrink-0 items-center justify-center rounded-full transition',
                                                'bg-white text-yellow-500 shadow-sm dark:bg-zinc-900 dark:text-yellow-400' => $esActivo,
                                                'bg-lime-500 text-white' => $esCompletado,
                                                'border border-zinc-200 bg-white text-zinc-400 dark:border-white/10 dark:bg-zinc-800 dark:text-zinc-500' => $esPendiente,
                                            ])
                                        >
                                            @if ($esCompletado)
                                                <x-ui.icon name="check" size="5" class="text-current" />
                                            @else
                                                <x-ui.icon :name="$paso['icono']" size="5" class="text-current" />
                                            @endif
                                        </div>

                                        {{-- Texto --}}
                                        <div class="min-w-0 flex-1">
                                            <p
                                                @class ([
                                                    'truncate text-sm font-medium',
                                                    'text-white dark:text-zinc-900' => $esActivo,
                                                    'text-zinc-800 dark:text-zinc-100' => $esCompletado,
                                                    'text-zinc-500 dark:text-zinc-400' => $esPendiente,
                                                ])
                                            >
                                                {{ $paso['titulo'] }}
                                            </p>
                                            <p
                                                @class ([
                                                    'truncate text-xs',
                                                    'text-zinc-600 dark:text-yellow-900' => $esActivo,
                                                    'text-zinc-600 dark:text-zinc-300' => $esCompletado,
                                                    'text-zinc-400 dark:text-zinc-500' => $esPendiente,
                                                ])
                                            >
                                                {{ $paso['descripcion'] }}
                                            </p>
                                        </div>

                                        {{-- Badge estado --}}
                                        @if ($esActivo)
                                            <span
                                                class="shrink-0 rounded-full bg-white/20 px-2 py-0.5 text-[10px] font-semibold tracking-wider text-white uppercase dark:bg-zinc-900/30 dark:text-zinc-900"
                                            >
                                                Actual
                                            </span>
                                        @elseif ($esCompletado)
                                            <span
                                                class="shrink-0 rounded-full bg-lime-50 px-2 py-0.5 text-[10px] font-semibold tracking-wider text-lime-600 uppercase dark:bg-lime-500/10 dark:text-lime-400"
                                            >
                                                Listo
                                            </span>
                                        @endif
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <flux:button variant="filled" class="w-full font-normal" wire:click="volverListado">
                        <x-slot name="icon">
                            <x-ui.icon name="caret-circle-left" size="5" class="text-current" />
                        </x-slot>
                        Volver al listado
                    </flux:button>
                </div>
            </div>
        </div>

        <div class="space-y-5">
            @if ($pasoActual === 1)
                <div
                    class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900"
                >
                    <div class="space-y-1">
                        <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Paso 1</p>
                        <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100">Informacion personal</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Registre la identidad, nacimiento, domicilio y datos base del postulante.</p>
                    </div>
                </div>
                <div
                    class="relative rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900"
                >
                    <div
                        wire:loading.flex
                        wire:target="guardarPasoPersonal"
                        class="absolute inset-0 z-30 hidden items-center justify-center rounded-xl bg-white/80 backdrop-blur-[2px] dark:bg-zinc-900/80"
                    >
                        <div class="flex flex-col items-center gap-3 text-center">
                            <x-ui.icon name="spinner-gap" size="10" class="text-accent animate-spin" />
                            <div>
                                <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">Guardando informacion personal</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Esto puede tomar unos segundos...</p>
                            </div>
                        </div>
                    </div>

                    @php
                        $tipoDocumentoDniId = (string) optional(
                            $this->tiposDocumento->first(
                                fn ($tipoDocumento) => in_array((string) $tipoDocumento->codigo_tdo, ['1', 'DNI'], true)
                                    || ((int) ($tipoDocumento->longitud_minima_tdo ?? 0) === 8
                                        && (int) ($tipoDocumento->longitud_maxima_tdo ?? 0) === 8
                                        && str_contains(mb_strtoupper((string) $tipoDocumento->nombre_tdo, 'UTF-8'), 'IDENTIDAD')),
                            ),
                        )->id_tdo;
                    @endphp

                    <form
                        wire:submit="guardarPasoPersonal"
                        class="space-y-5"
                        x-data="{
                            tipoDocumentoId: @js($personaForm['id_tipo_documento']),
                            numeroDocumento: @js($personaForm['numero_documento']),
                            soloUnApellido: @js($personaForm['solo_un_apellido']),
                            tieneApellidoCasada: @js($personaForm['tiene_apellido_casada']),
                            discapacidad: @js($personaForm['discapacidad']),
                            tipoLenguaMaterna: @js($personaForm['tipo_lengua_materna']),
                            dniTipoDocumentoId: @js($tipoDocumentoDniId),
                            tipoLenguaNativa: @js(\App\Enums\TipoLenguaMaternaPersona::Nativa->value),
                            tipoLenguaExtranjera: @js(\App\Enums\TipoLenguaMaternaPersona::Extranjera->value),
                            esTipoDocumentoDni() {
                                return this.dniTipoDocumentoId !== '' && String(this.tipoDocumentoId) === String(this.dniTipoDocumentoId);
                            },
                            puedeBuscarDatosLocal() {
                                const numero = String(this.numeroDocumento ?? '').replace(/\D+/g, '');

                                if (! this.tipoDocumentoId) {
                                    return false;
                                }

                                if (this.esTipoDocumentoDni()) {
                                    return numero.length === 8;
                                }

                                return numero.length > 0;
                            },
                            colSpanApellidos() {
                                const visible = 1 + (! this.soloUnApellido ? 1 : 0) + (this.tieneApellidoCasada ? 1 : 0);

                                if (visible === 3) {
                                    return 'lg:col-span-4';
                                }

                                if (visible === 1) {
                                    return 'lg:col-span-12';
                                }

                                return 'lg:col-span-6';
                            },
                            colSpanLengua() {
                                return (this.tipoLenguaMaterna === this.tipoLenguaNativa || this.tipoLenguaMaterna === this.tipoLenguaExtranjera)
                                    ? 'lg:col-span-4'
                                    : 'lg:col-span-6';
                            },
                            init() {
                                $wire.$watch('personaForm.solo_un_apellido', (value) => this.soloUnApellido = value);
                                $wire.$watch('personaForm.tiene_apellido_casada', (value) => this.tieneApellidoCasada = value);
                                $wire.$watch('personaForm.discapacidad', (value) => this.discapacidad = value);
                                $wire.$watch('personaForm.tipo_lengua_materna', (value) => this.tipoLenguaMaterna = value ?? '');
                            },
                        }"
                        x-on:ui-select-changed.window="
                            if ($event.detail.name === 'personaForm.id_tipo_documento')
                                tipoDocumentoId = $event.detail.value ?? '';
                            if ($event.detail.name === 'personaForm.tipo_lengua_materna')
                                tipoLenguaMaterna = $event.detail.value ?? '';
                        "
                    >
                        <section
                            class="space-y-4 rounded-lg border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40"
                        >
                            <div class="space-y-1">
                                <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Identificacion</p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">Seleccione el documento, registre el numero y use RENIEC solo cuando aplique.</p>
                            </div>

                            <div class="grid gap-4 lg:grid-cols-12 lg:items-start">
                                <div class="lg:col-span-4">
                                    <x-ui.select
                                        label="Tipo documento"
                                        :invalid="$errors->has('personaForm.id_tipo_documento')"
                                        :error="$errors->first('personaForm.id_tipo_documento')"
                                        wire:model="personaForm.id_tipo_documento"
                                        required
                                        floating
                                    >
                                        <option value="">Seleccionar tipo</option>
                                        @foreach ($this->tiposDocumento as $tipoDocumento)
                                            <option value="{{ $tipoDocumento->id_tdo }}">
                                                {{ $tipoDocumento->nombre_tdo }}
                                            </option>
                                        @endforeach
                                    </x-ui.select>
                                </div>

                                <div class="lg:col-span-5">
                                    <x-ui.input
                                        label="Numero documento"
                                        type="text"
                                        wire:model="personaForm.numero_documento"
                                        x-on:input="numeroDocumento = $event.target.value"
                                        :invalid="$errors->has('personaForm.numero_documento')"
                                        :error="$errors->first('personaForm.numero_documento')"
                                        required
                                        floating
                                    />
                                </div>

                                <div class="lg:col-span-3">
                                    <div>
                                        <flux:button
                                            type="button"
                                            variant="primary"
                                            color="purple"
                                            wire:click="buscarDatosPersona"
                                            wire:loading.attr="disabled"
                                            wire:target="buscarDatosPersona"
                                            x-bind:disabled="!puedeBuscarDatosLocal()"
                                            class="h-12 w-full justify-center"
                                        >
                                            <x-slot name="icon">
                                                <x-ui.icon
                                                    x-show="esTipoDocumentoDni()"
                                                    x-cloak
                                                    name="identification-card"
                                                    size="5"
                                                    class="text-current"
                                                />
                                                <x-ui.icon
                                                    x-show="!esTipoDocumentoDni()"
                                                    x-cloak
                                                    name="magnifying-glass"
                                                    size="5"
                                                    class="text-current"
                                                />
                                            </x-slot>
                                            <span wire:loading.remove wire:target="buscarDatosPersona"
                                                >Buscar datos</span
                                            >
                                            <span wire:loading wire:target="buscarDatosPersona">Buscando...</span>
                                        </flux:button>
                                    </div>
                                </div>
                            </div>

                            @if ($reniecMensaje)
                                @php
                                    $calloutVariant = match ($reniecTipoMensaje) {
                                        'success' => 'success',
                                        'warning' => 'warning',
                                        'error' => 'danger',
                                        default => 'secondary',
                                    };

                                    $calloutIcon = match ($reniecTipoMensaje) {
                                        'success' => 'check-circle',
                                        'warning' => 'exclamation-circle',
                                        'error' => 'x-circle',
                                        default => 'information-circle',
                                    };
                                @endphp
                                <flux:callout
                                    :variant="$calloutVariant"
                                    :icon="$calloutIcon"
                                    heading="Busqueda de datos"
                                >
                                    {{ $reniecMensaje }}
                                </flux:callout>
                            @endif
                        </section>

                        <section
                            class="space-y-4 rounded-lg border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40"
                        >
                            <div class="space-y-1">
                                <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Datos personales</p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">Complete los apellidos, nombres, datos personales y estado civil del postulante.</p>
                            </div>

                            <div class="flex flex-wrap items-center gap-x-8 gap-y-3">
                                <flux:switch
                                    wire:model="personaForm.solo_un_apellido"
                                    label="Solo un apellido"
                                    align="left"
                                />

                                <flux:switch
                                    wire:model="personaForm.tiene_apellido_casada"
                                    label="Tiene apellido de casado(a)"
                                    align="left"
                                />
                            </div>

                            <div class="grid gap-4 lg:grid-cols-12">
                                <div x-bind:class="colSpanApellidos()">
                                    <x-ui.input
                                        label="Primer apellido"
                                        type="text"
                                        wire:model="personaForm.apellido_paterno"
                                        :invalid="$errors->has('personaForm.apellido_paterno')"
                                        :error="$errors->first('personaForm.apellido_paterno')"
                                        required
                                        floating
                                    />
                                </div>

                                <div x-show="!soloUnApellido" x-cloak x-bind:class="colSpanApellidos()">
                                    <x-ui.input
                                        label="Segundo apellido"
                                        type="text"
                                        wire:model="personaForm.apellido_materno"
                                        :invalid="$errors->has('personaForm.apellido_materno')"
                                        :error="$errors->first('personaForm.apellido_materno')"
                                        floating
                                    />
                                </div>

                                <div x-show="tieneApellidoCasada" x-cloak x-bind:class="colSpanApellidos()">
                                    <x-ui.input
                                        label="Apellido casada"
                                        type="text"
                                        wire:model="personaForm.apellido_casada"
                                        :invalid="$errors->has('personaForm.apellido_casada')"
                                        :error="$errors->first('personaForm.apellido_casada')"
                                        floating
                                    />
                                </div>
                            </div>

                            <div class="grid gap-4 lg:grid-cols-12">
                                <div class="lg:col-span-6">
                                    <x-ui.input
                                        label="Nombres"
                                        type="text"
                                        wire:model="personaForm.nombres"
                                        :invalid="$errors->has('personaForm.nombres')"
                                        :error="$errors->first('personaForm.nombres')"
                                        required
                                        floating
                                    />
                                </div>

                                <div class="lg:col-span-6">
                                    <x-ui.input
                                        label="Fecha nacimiento"
                                        type="date"
                                        wire:model="personaForm.fecha_nacimiento"
                                        :invalid="$errors->has('personaForm.fecha_nacimiento')"
                                        :error="$errors->first('personaForm.fecha_nacimiento')"
                                        required
                                        floating
                                    />
                                </div>
                            </div>

                            <div class="grid gap-4 lg:grid-cols-12">
                                <div class="lg:col-span-6">
                                    <x-ui.select
                                        label="Sexo"
                                        wire:model="personaForm.sexo"
                                        :invalid="$errors->has('personaForm.sexo')"
                                        :error="$errors->first('personaForm.sexo')"
                                        required
                                        floating
                                    >
                                        <option value="">Seleccionar sexo</option>
                                        @foreach (\App\Enums\SexoPersona::cases() as $sexo)
                                            <option value="{{ $sexo->value }}">{{ $sexo->getNombre() }}</option>
                                        @endforeach
                                    </x-ui.select>
                                </div>

                                <div class="lg:col-span-6">
                                    <x-ui.select
                                        label="Estado civil"
                                        wire:model="personaForm.id_estado_civil"
                                        :invalid="$errors->has('personaForm.id_estado_civil')"
                                        :error="$errors->first('personaForm.id_estado_civil')"
                                        required
                                        floating
                                    >
                                        <option value="">Seleccionar estado civil</option>
                                        @foreach ($this->estadosCiviles as $estadoCivil)
                                            <option value="{{ $estadoCivil->id_eci }}">
                                                {{ $estadoCivil->nombre_eci }}
                                            </option>
                                        @endforeach
                                    </x-ui.select>
                                </div>
                            </div>
                        </section>

                        <section
                            class="space-y-4 rounded-lg border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40"
                        >
                            <div class="space-y-1">
                                <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Nacimiento y domicilio</p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">Registre el origen, ubigeo, domicilio y datos de contacto del postulante.</p>
                            </div>

                            <div class="grid gap-4 lg:grid-cols-12">
                                <div class="lg:col-span-6">
                                    <x-ui.select-filter
                                        label="Pais nacimiento"
                                        wire:model="personaForm.id_pais_nacimiento"
                                        :invalid="$errors->has('personaForm.id_pais_nacimiento')"
                                        :error="$errors->first('personaForm.id_pais_nacimiento')"
                                        required
                                        floating
                                    >
                                        <option value="">Seleccionar pais</option>
                                        @foreach ($this->paises as $pais)
                                            <option value="{{ $pais->id_pai }}">{{ $pais->nombre_pai }}</option>
                                        @endforeach
                                    </x-ui.select-filter>
                                </div>

                                <div class="lg:col-span-6">
                                    <x-ui.select-filter
                                        label="Nacionalidad"
                                        wire:model="personaForm.id_nacionalidad"
                                        :invalid="$errors->has('personaForm.id_nacionalidad')"
                                        :error="$errors->first('personaForm.id_nacionalidad')"
                                        required
                                        floating
                                    >
                                        <option value="">Seleccionar nacionalidad</option>
                                        @foreach ($this->nacionalidades as $nacionalidad)
                                            <option value="{{ $nacionalidad->id_nac }}">
                                                {{ $nacionalidad->nombre_nac }}
                                            </option>
                                        @endforeach
                                    </x-ui.select-filter>
                                </div>
                            </div>

                            <div class="grid gap-4 lg:grid-cols-12">
                                <div class="lg:col-span-6">
                                    <x-form.select.search
                                        label="Ubigeo nacimiento"
                                        wire:model="personaForm.id_ubigeo_nacimiento"
                                        :action="route('api.ubigeo.search')"
                                        :selected-label="$this->ubigeoNacimientoLabel"
                                        placeholder="Buscar ubigeo de nacimiento"
                                        :invalid="$errors->has('personaForm.id_ubigeo_nacimiento')"
                                        :error="$errors->first('personaForm.id_ubigeo_nacimiento')"
                                        required
                                        floating
                                    />
                                </div>

                                <div class="lg:col-span-6">
                                    <x-form.select.search
                                        label="Ubigeo domicilio"
                                        wire:model="personaForm.id_ubigeo_domicilio"
                                        :action="route('api.ubigeo.search')"
                                        :selected-label="$this->ubigeoDomicilioLabel"
                                        placeholder="Buscar ubigeo de domicilio"
                                        :invalid="$errors->has('personaForm.id_ubigeo_domicilio')"
                                        :error="$errors->first('personaForm.id_ubigeo_domicilio')"
                                        required
                                        floating
                                    />
                                </div>
                            </div>

                            <div class="grid gap-4 lg:grid-cols-12">
                                <div class="lg:col-span-6">
                                    <x-ui.input
                                        label="Direccion"
                                        type="text"
                                        wire:model="personaForm.direccion"
                                        :invalid="$errors->has('personaForm.direccion')"
                                        :error="$errors->first('personaForm.direccion')"
                                        required
                                        floating
                                    />
                                </div>

                                <div class="lg:col-span-6">
                                    <x-ui.input
                                        label="Correo electronico"
                                        type="email"
                                        wire:model="personaForm.correo_electronico"
                                        :invalid="$errors->has('personaForm.correo_electronico')"
                                        :error="$errors->first('personaForm.correo_electronico')"
                                        floating
                                    />
                                </div>
                            </div>

                            <div class="grid gap-4 lg:grid-cols-12">
                                <div class="lg:col-span-6">
                                    <x-ui.input
                                        label="Celular"
                                        type="text"
                                        wire:model="personaForm.celular"
                                        :invalid="$errors->has('personaForm.celular')"
                                        :error="$errors->first('personaForm.celular')"
                                        floating
                                    />
                                </div>

                                <div class="lg:col-span-6">
                                    <x-ui.input
                                        label="Telefono"
                                        type="text"
                                        wire:model="personaForm.telefono"
                                        :invalid="$errors->has('personaForm.telefono')"
                                        :error="$errors->first('personaForm.telefono')"
                                        floating
                                    />
                                </div>
                            </div>
                        </section>

                        <section
                            class="space-y-4 rounded-lg border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40"
                        >
                            <div class="space-y-1">
                                <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Perfil complementario</p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">Registre discapacidad, identidad etnica y lengua materna del postulante.</p>
                            </div>

                            <div class="flex items-center">
                                <flux:switch
                                    wire:model="personaForm.discapacidad"
                                    label="Tiene discapacidad"
                                    align="left"
                                />
                            </div>

                            <div class="grid gap-4 lg:grid-cols-12">
                                <div x-show="discapacidad" x-cloak class="lg:col-span-6">
                                    <x-ui.select
                                        label="Tipo discapacidad"
                                        wire:model="personaForm.id_tipo_discapacidad"
                                        :invalid="$errors->has('personaForm.id_tipo_discapacidad')"
                                        :error="$errors->first('personaForm.id_tipo_discapacidad')"
                                        floating
                                    >
                                        <option value="">Seleccionar tipo</option>
                                        @foreach ($this->tiposDiscapacidad as $tipoDiscapacidad)
                                            <option value="{{ $tipoDiscapacidad->id_tdi }}">
                                                {{ $tipoDiscapacidad->nombre_tdi }}
                                            </option>
                                        @endforeach
                                    </x-ui.select>
                                </div>
                            </div>

                            <div class="grid gap-4 lg:grid-flow-row-dense lg:grid-cols-12">
                                <div x-bind:class="colSpanLengua()">
                                    <x-ui.select-filter
                                        label="Identidad etnica"
                                        wire:model="personaForm.id_identidad_etnica"
                                        :invalid="$errors->has('personaForm.id_identidad_etnica')"
                                        :error="$errors->first('personaForm.id_identidad_etnica')"
                                        required
                                        floating
                                    >
                                        <option value="">Seleccionar identidad</option>
                                        @foreach ($this->identidadesEtnicas as $identidadEtnica)
                                            <option value="{{ $identidadEtnica->id_iet }}">
                                                {{ $identidadEtnica->nombre_iet }}
                                            </option>
                                        @endforeach
                                    </x-ui.select-filter>
                                </div>

                                <div x-bind:class="colSpanLengua()">
                                    <x-ui.select
                                        label="Tipo lengua materna"
                                        wire:model="personaForm.tipo_lengua_materna"
                                        :invalid="$errors->has('personaForm.tipo_lengua_materna')"
                                        :error="$errors->first('personaForm.tipo_lengua_materna')"
                                        required
                                        floating
                                    >
                                        <option value="">Seleccionar tipo</option>
                                        @foreach (\App\Enums\TipoLenguaMaternaPersona::cases() as $tipoLengua)
                                            <option value="{{ $tipoLengua->value }}">
                                                {{ $tipoLengua->getNombre() }}
                                            </option>
                                        @endforeach
                                    </x-ui.select>
                                </div>

                                <div
                                    x-show="tipoLenguaMaterna === tipoLenguaNativa"
                                    x-cloak
                                    x-bind:class="colSpanLengua()"
                                >
                                    <x-ui.select-filter
                                        label="Lengua nativa"
                                        wire:model="personaForm.id_lengua_nativa"
                                        :invalid="$errors->has('personaForm.id_lengua_nativa')"
                                        :error="$errors->first('personaForm.id_lengua_nativa')"
                                        floating
                                    >
                                        <option value="">Seleccionar lengua</option>
                                        @foreach ($this->lenguasNativas as $lenguaNativa)
                                            <option value="{{ $lenguaNativa->id_lna }}">
                                                {{ $lenguaNativa->nombre_lna }}
                                            </option>
                                        @endforeach
                                    </x-ui.select-filter>
                                </div>

                                <div
                                    x-show="tipoLenguaMaterna === tipoLenguaExtranjera"
                                    x-cloak
                                    x-bind:class="colSpanLengua()"
                                >
                                    <x-ui.select-filter
                                        label="Lengua extranjera"
                                        wire:model="personaForm.id_lengua_extranjera"
                                        :invalid="$errors->has('personaForm.id_lengua_extranjera')"
                                        :error="$errors->first('personaForm.id_lengua_extranjera')"
                                        floating
                                    >
                                        <option value="">Seleccionar lengua</option>
                                        @foreach ($this->lenguasExtranjeras as $lenguaExtranjera)
                                            <option value="{{ $lenguaExtranjera->id_lex }}">
                                                {{ $lenguaExtranjera->nombre_lex }}
                                            </option>
                                        @endforeach
                                    </x-ui.select-filter>
                                </div>
                            </div>

                            <x-ui.textarea
                                label="Observacion"
                                wire:model="personaForm.observacion"
                                :invalid="$errors->has('personaForm.observacion')"
                                :error="$errors->first('personaForm.observacion')"
                                rows="3"
                                floating
                            />
                        </section>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Al guardar este paso, se registrara o actualizara la persona base del postulante.</p>

                            <flux:button variant="primary" type="submit" class="font-normal">
                                <x-slot name="icon">
                                    <x-ui.icon name="caret-circle-right" size="5" class="text-white" />
                                </x-slot>
                                <span wire:loading.remove wire:target="guardarPasoPersonal">Guardar y continuar</span>
                                <span wire:loading wire:target="guardarPasoPersonal">Guardando...</span>
                            </flux:button>
                        </div>
                    </form>
                </div>
            @elseif ($pasoActual === 2)
                <div
                    class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900"
                >
                    <div class="space-y-1">
                        <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Paso 2</p>
                        <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100">Informacion general</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Registre la procedencia educativa y el historial de postulacion del postulante.</p>
                    </div>
                </div>
                <div
                    class="relative rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900"
                >
                    <div
                        wire:loading.flex
                        wire:target="guardarPasoGeneral"
                        class="absolute inset-0 z-30 hidden items-center justify-center rounded-xl bg-white/80 backdrop-blur-[2px] dark:bg-zinc-900/80"
                    >
                        <div class="flex flex-col items-center gap-3 text-center">
                            <x-ui.icon name="spinner-gap" size="10" class="text-accent animate-spin" />
                            <div>
                                <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">Guardando informacion general</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Esto puede tomar unos segundos...</p>
                            </div>
                        </div>
                    </div>

                    <form
                        wire:submit="guardarPasoGeneral"
                        class="space-y-5"
                        x-data="{
                            paisColegioId: @js($generalForm['id_pais_colegio']),
                            paisPeruId: @js($this->paisPeruId),
                            esPaisPeru() {
                                return this.paisPeruId !== '' && String(this.paisColegioId) === String(this.paisPeruId);
                            },
                            init() {
                                $wire.$watch('generalForm.id_pais_colegio', (value) => this.paisColegioId = value ?? '');

                                this.$nextTick(() => {
                                    const colegioId = @js($generalForm['id_colegio']);
                                    const colegioLabel = @js($this->colegioLabel);
                                    if (colegioId && colegioLabel) {
                                        window.dispatchEvent(new CustomEvent('ui-select-search-filled', {
                                            detail: { name: 'generalForm.id_colegio', value: colegioId, label: colegioLabel },
                                            bubbles: true,
                                        }));
                                    }
                                });
                            },
                        }"
                        x-on:ui-select-changed.window="
                            if ($event.detail.name === 'generalForm.id_pais_colegio')
                                paisColegioId = $event.detail.value ?? '';
                        "
                    >
                        <section
                            class="space-y-4 rounded-lg border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40"
                        >
                            <div class="space-y-1">
                                <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Procedencia educativa</p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">Defina el ano de graduacion, pais del colegio y tipo de institucion educativa.</p>
                            </div>

                            <div class="grid gap-4 lg:grid-cols-12">
                                <div class="lg:col-span-4">
                                    <x-ui.input
                                        label="Ano de graduacion"
                                        type="number"
                                        wire:model="generalForm.anio_graduacion"
                                        :invalid="$errors->has('generalForm.anio_graduacion')"
                                        :error="$errors->first('generalForm.anio_graduacion')"
                                        required
                                        floating
                                    />
                                </div>

                                <div class="lg:col-span-4">
                                    <x-ui.select-filter
                                        label="Pais del colegio"
                                        wire:model="generalForm.id_pais_colegio"
                                        :invalid="$errors->has('generalForm.id_pais_colegio')"
                                        :error="$errors->first('generalForm.id_pais_colegio')"
                                        required
                                        floating
                                    >
                                        <option value="">Seleccionar pais</option>
                                        @foreach ($this->paises as $pais)
                                            <option value="{{ $pais->id_pai }}">{{ $pais->nombre_pai }}</option>
                                        @endforeach
                                    </x-ui.select-filter>
                                </div>

                                <div class="lg:col-span-4">
                                    <x-ui.select
                                        label="Tipo de colegio"
                                        wire:model="generalForm.id_tipo_colegio"
                                        :invalid="$errors->has('generalForm.id_tipo_colegio')"
                                        :error="$errors->first('generalForm.id_tipo_colegio')"
                                        required
                                        floating
                                    >
                                        <option value="">Seleccionar tipo</option>
                                        @foreach ($this->tiposColegio as $tipoColegio)
                                            <option value="{{ $tipoColegio->id_tco }}">
                                                {{ $tipoColegio->nombre_tco }}
                                            </option>
                                        @endforeach
                                    </x-ui.select>
                                </div>
                            </div>
                        </section>

                        <section
                            class="space-y-4 rounded-lg border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40"
                        >
                            <div class="space-y-1">
                                <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Colegio de procedencia</p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">Si el colegio es peruano, seleccione el registro. Si es extranjero, registre el nombre manualmente.</p>
                            </div>

                            <div class="grid gap-4 lg:grid-cols-12">
                                <div x-show="paisColegioId && esPaisPeru()" x-cloak class="lg:col-span-12">
                                    <x-form.select.search
                                        label="Colegio"
                                        wire:model="generalForm.id_colegio"
                                        :action="route('api.colegio.search')"
                                        :selected-label="$this->colegioLabel"
                                        placeholder="Buscar colegio por codigo o nombre"
                                        :invalid="$errors->has('generalForm.id_colegio')"
                                        :error="$errors->first('generalForm.id_colegio')"
                                        required
                                        floating
                                    />
                                </div>

                                <div x-show="paisColegioId && !esPaisPeru()" x-cloak class="lg:col-span-12">
                                    <x-ui.input
                                        label="Nombre del colegio extranjero"
                                        type="text"
                                        wire:model="generalForm.nombre_colegio_extranjero"
                                        :invalid="$errors->has('generalForm.nombre_colegio_extranjero')"
                                        :error="$errors->first('generalForm.nombre_colegio_extranjero')"
                                        required
                                        floating
                                    />
                                </div>

                                <div
                                    x-show="!paisColegioId"
                                    x-cloak
                                    class="rounded-xl border border-dashed border-zinc-300 bg-white/70 px-4 py-3 text-sm text-zinc-500 lg:col-span-12 dark:border-white/10 dark:bg-zinc-900/50 dark:text-zinc-400"
                                >
                                    Seleccione primero el pais del colegio para continuar con la procedencia educativa.
                                </div>
                            </div>
                        </section>

                        <section
                            class="space-y-4 rounded-lg border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40"
                        >
                            <div class="space-y-1">
                                <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Historial de postulacion</p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">Indique cuantas veces ya postulo a la UNU y a otras universidades.</p>
                            </div>

                            <div class="grid gap-4 lg:grid-cols-12">
                                <div class="lg:col-span-6">
                                    <x-ui.input
                                        label="Veces que postulo a la UNU"
                                        type="number"
                                        wire:model="generalForm.veces_postula_unu"
                                        :invalid="$errors->has('generalForm.veces_postula_unu')"
                                        :error="$errors->first('generalForm.veces_postula_unu')"
                                        required
                                        floating
                                    />
                                </div>

                                <div class="lg:col-span-6">
                                    <x-ui.input
                                        label="Veces que postulo a otras universidades"
                                        type="number"
                                        wire:model="generalForm.veces_postula_otras"
                                        :invalid="$errors->has('generalForm.veces_postula_otras')"
                                        :error="$errors->first('generalForm.veces_postula_otras')"
                                        required
                                        floating
                                    />
                                </div>
                            </div>

                            <x-ui.textarea
                                label="Observacion general"
                                wire:model="generalForm.observacion"
                                :invalid="$errors->has('generalForm.observacion')"
                                :error="$errors->first('generalForm.observacion')"
                                rows="3"
                                floating
                            />
                        </section>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Al guardar este paso, quedara lista la procedencia educativa para continuar con la postulacion.</p>

                            <div class="flex gap-3">
                                <flux:button
                                    variant="filled"
                                    type="button"
                                    wire:click="irPasoAnterior"
                                    class="font-normal"
                                >
                                    Volver
                                </flux:button>

                                <flux:button variant="primary" type="submit" class="font-normal">
                                    <x-slot name="icon">
                                        <x-ui.icon name="caret-circle-right" size="5" class="text-white" />
                                    </x-slot>
                                    <span wire:loading.remove wire:target="guardarPasoGeneral"
                                        >Guardar y continuar</span
                                    >
                                    <span wire:loading wire:target="guardarPasoGeneral">Guardando...</span>
                                </flux:button>
                            </div>
                        </div>
                    </form>
                </div>
            @elseif ($pasoActual === 3)
                @php ($contextoProceso = $this->contextoProcesoPostulacion)
                @php ($procesoActivo = $this->procesoPostulacionActivo)
                <div
                    class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900"
                >
                    <div class="space-y-1">
                        <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Paso 3</p>
                        <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100">Postulacion</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Seleccione la oferta activa del proceso vigente y deje listo el borrador de inscripcion.</p>
                    </div>
                </div>
                <div
                    class="relative rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900"
                >
                    <div
                        wire:loading.flex
                        wire:target="guardarPasoPostulacion"
                        class="absolute inset-0 z-30 hidden items-center justify-center rounded-xl bg-white/80 backdrop-blur-[2px] dark:bg-zinc-900/80"
                    >
                        <div class="flex flex-col items-center gap-3 text-center">
                            <x-ui.icon name="spinner-gap" size="10" class="text-accent animate-spin" />
                            <div>
                                <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">Guardando postulacion</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Esto puede tomar unos segundos...</p>
                            </div>
                        </div>
                    </div>

                    @if (! $contextoProceso['disponible'])
                        <div class="space-y-5">
                            <flux:callout
                                variant="warning"
                                icon="exclamation-circle"
                                heading="{{ $contextoProceso['mensaje'] }}"
                            >
                                <p class="text-sm">No podremos continuar con la postulacion hasta que exista un proceso habilitado y con ofertas activas.</p>
                            </flux:callout>

                            <div class="flex gap-3">
                                <flux:button variant="filled" wire:click="irPasoAnterior" class="font-normal">
                                    Volver al paso anterior
                                </flux:button>

                                <flux:button variant="filled" wire:click="volverListado" class="font-normal">
                                    Volver al listado
                                </flux:button>
                            </div>
                        </div>
                    @else
                        <form
                            wire:submit="guardarPasoPostulacion"
                            class="space-y-5"
                            x-on:duplicado-confirmado.window="$wire.confirmarGuardadoDuplicado()"
                            x-on:duplicado-cancelado.window="$wire.cancelarDuplicadoPostulacion()"
                        >
                            <section
                                class="space-y-4 rounded-lg border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40"
                            >
                                <div class="space-y-1">
                                    <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Proceso vigente</p>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Este paso usara el proceso disponible segun la fecha y la configuracion academica activa.</p>
                                </div>

                                <div class="grid gap-4 lg:grid-cols-12">
                                    <div class="lg:col-span-6">
                                        <x-ui.input
                                            label="Semestre"
                                            :value="$procesoActivo?->semestre?->nombre_sem"
                                            disabled
                                            floating
                                        />
                                    </div>

                                    <div class="lg:col-span-6">
                                        <x-ui.input
                                            label="Proceso"
                                            :value="$procesoActivo?->nombre_pro"
                                            disabled
                                            floating
                                        />
                                    </div>
                                </div>

                                <flux:callout
                                    variant="success"
                                    icon="check-circle"
                                    heading="{{ $contextoProceso['mensaje'] }}"
                                >
                                    <p class="text-sm">
                                        Ventana activa: {{ $procesoActivo?->fecha_inicio_pro?->format('d/m/Y') }} - {{ $procesoActivo?->fecha_fin_pro?->format('d/m/Y') }}
                                    </p>
                                </flux:callout>
                            </section>

                            <section
                                class="space-y-4 rounded-lg border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40"
                            >
                                <div class="space-y-1">
                                    <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Seleccion de oferta</p>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">La seleccion se construye en cascada para mostrar solo combinaciones validas del proceso actual.</p>
                                </div>

                                <div class="grid gap-4 lg:grid-cols-12">
                                    {{-- Modalidad: siempre visible --}}
                                    <div class="lg:col-span-6">
                                        <x-ui.select
                                            label="Modalidad"
                                            wire:model.live="postulacionForm.id_modalidad_admision"
                                            :invalid="$errors->has('postulacionForm.id_modalidad_admision')"
                                            :error="$errors->first('postulacionForm.id_modalidad_admision')"
                                            required
                                            floating
                                        >
                                            <option value="">Seleccionar modalidad</option>
                                            @foreach ($this->modalidadesPostulacionDisponibles as $modalidad)
                                                <option value="{{ $modalidad['id'] }}">
                                                    {{ $modalidad['nombre'] }}
                                                </option>
                                            @endforeach
                                        </x-ui.select>
                                    </div>

                                    {{-- Lugar: aparece tras seleccionar modalidad --}}
                                    @if ($postulacionForm['id_modalidad_admision'] !== '')
                                        <div
                                            class="lg:col-span-6"
                                            wire:key="lugar-{{ $postulacionForm['id_modalidad_admision'] }}"
                                        >
                                            <x-ui.select
                                                label="Lugar de inscripcion"
                                                wire:model.live="postulacionForm.id_lugar_inscripcion"
                                                :invalid="$errors->has('postulacionForm.id_lugar_inscripcion')"
                                                :error="$errors->first('postulacionForm.id_lugar_inscripcion')"
                                                required
                                                floating
                                            >
                                                <option value="">Seleccionar lugar</option>
                                                @foreach ($this->lugaresPostulacionDisponibles as $lugar)
                                                    <option value="{{ $lugar['id'] }}">{{ $lugar['nombre'] }}</option>
                                                @endforeach
                                            </x-ui.select>
                                        </div>
                                    @endif

                                    {{-- Turno: aparece tras seleccionar lugar --}}
                                    @if ($postulacionForm['id_lugar_inscripcion'] !== '')
                                        <div
                                            class="lg:col-span-4"
                                            wire:key="turno-{{ $postulacionForm['id_modalidad_admision'] }}-{{ $postulacionForm['id_lugar_inscripcion'] }}"
                                        >
                                            <x-ui.select
                                                label="Turno"
                                                wire:model.live="postulacionForm.id_turno"
                                                :invalid="$errors->has('postulacionForm.id_turno')"
                                                :error="$errors->first('postulacionForm.id_turno')"
                                                required
                                                floating
                                            >
                                                <option value="">Seleccionar turno</option>
                                                @foreach ($this->turnosPostulacionDisponibles as $turno)
                                                    <option value="{{ $turno['id'] }}">{{ $turno['nombre'] }}</option>
                                                @endforeach
                                            </x-ui.select>
                                        </div>
                                    @endif

                                    {{-- Oferta: aparece tras seleccionar turno --}}
                                    @if ($postulacionForm['id_turno'] !== '')
                                        <div
                                            class="lg:col-span-8"
                                            wire:key="oferta-{{ $postulacionForm['id_modalidad_admision'] }}-{{ $postulacionForm['id_lugar_inscripcion'] }}-{{ $postulacionForm['id_turno'] }}"
                                        >
                                            <x-ui.select-filter
                                                label="Oferta"
                                                wire:model.live="postulacionForm.id_proceso_oferta"
                                                :invalid="$errors->has('postulacionForm.id_proceso_oferta')"
                                                :error="$errors->first('postulacionForm.id_proceso_oferta')"
                                                required
                                                floating
                                            >
                                                <option value="">Seleccionar oferta</option>
                                                @foreach ($this->procesoOfertasPostulacionDisponibles as $procesoOferta)
                                                    <option value="{{ $procesoOferta->id_pof }}">
                                                        {{ $this->descripcionProcesoOferta($procesoOferta) }}
                                                    </option>
                                                @endforeach
                                            </x-ui.select-filter>
                                        </div>
                                    @endif
                                </div>
                            </section>

                            <section
                                class="space-y-4 rounded-lg border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40"
                            >
                                <div class="space-y-1">
                                    <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Condicion especial</p>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Si corresponde, seleccione un convenio vigente para este mismo proceso.</p>
                                </div>

                                <div class="grid gap-4 lg:grid-cols-12">
                                    <div class="lg:col-span-12">
                                        <x-ui.select
                                            label="Convenio"
                                            wire:model.live="postulacionForm.id_convenio_proceso"
                                            :invalid="$errors->has('postulacionForm.id_convenio_proceso')"
                                            :error="$errors->first('postulacionForm.id_convenio_proceso')"
                                            floating
                                        >
                                            <option value="">Sin convenio</option>
                                            @foreach ($this->conveniosPostulacionDisponibles as $convenioProceso)
                                                <option value="{{ $convenioProceso->id_cpr }}">
                                                    {{ $this->descripcionConvenioProceso($convenioProceso) }}
                                                </option>
                                            @endforeach
                                        </x-ui.select>
                                    </div>
                                </div>
                            </section>

                            <section
                                class="space-y-4 rounded-lg border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40"
                            >
                                <div class="space-y-1">
                                    <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Resumen de postulacion</p>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Antes de continuar, revise que la modalidad, oferta y condicion especial sean las correctas.</p>
                                </div>

                                @php ($ofertaSeleccionada = $this->procesoOfertasPostulacionDisponibles->firstWhere('id_pof', (int) $postulacionForm['id_proceso_oferta']))
                                @php ($convenioSeleccionado = $this->conveniosPostulacionDisponibles->firstWhere('id_cpr', (int) $postulacionForm['id_convenio_proceso']))

                                <flux:callout
                                    variant="{{ $ofertaSeleccionada ? 'success' : 'secondary' }}"
                                    icon="{{ $ofertaSeleccionada ? 'check-circle' : 'information-circle' }}"
                                    heading="{{ $ofertaSeleccionada ? 'La postulacion ya tiene una oferta seleccionada.' : 'Complete modalidad, lugar, turno y oferta para continuar.' }}"
                                >
                                    @if ($ofertaSeleccionada)
                                        <div class="space-y-1 text-sm">
                                            <p><span class="font-medium">Oferta:</span> {{ $this->descripcionProcesoOferta($ofertaSeleccionada) }}</p>
                                            <p><span class="font-medium">Convenio:</span> {{ $this->descripcionConvenioProceso($convenioSeleccionado) }}</p>
                                        </div>
                                    @endif
                                </flux:callout>
                            </section>

                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">Al guardar este paso se creara o actualizara el borrador de inscripcion con la oferta seleccionada.</p>

                                <div class="flex gap-3">
                                    <flux:button
                                        variant="filled"
                                        type="button"
                                        wire:click="irPasoAnterior"
                                        class="font-normal"
                                    >
                                        Volver
                                    </flux:button>

                                    <flux:button variant="primary" type="submit" class="font-normal">
                                        <x-slot name="icon">
                                            <x-ui.icon name="caret-circle-right" size="5" class="text-white" />
                                        </x-slot>
                                        <span wire:loading.remove wire:target="guardarPasoPostulacion"
                                            >Guardar y continuar</span
                                        >
                                        <span wire:loading wire:target="guardarPasoPostulacion">Guardando...</span>
                                    </flux:button>
                                </div>
                            </div>
                        </form>
                    @endif
                </div>
            @elseif ($pasoActual === 4)
                @php ($inscripcionBorrador = $this->inscripcionBorrador)
                @php ($reglaPagoSeleccionada = $this->reglaPagoSeleccionada)
                @php ($obligacionPagoSeleccionada = $this->obligacionPagoSeleccionada)
                <div
                    class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900"
                >
                    <div class="space-y-1">
                        <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Paso 4</p>
                        <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100">Pago</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Seleccione la regla de pago, busque la operacion bancaria y valide el abono del postulante.</p>
                    </div>
                </div>
                <div
                    class="relative rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900"
                >
                    <div
                        wire:loading.flex
                        wire:target="buscarPagosPasoPago,confirmarPasoPago"
                        class="absolute inset-0 z-30 hidden items-center justify-center rounded-xl bg-white/80 backdrop-blur-[2px] dark:bg-zinc-900/80"
                    >
                        <div class="flex flex-col items-center gap-3 text-center">
                            <x-ui.icon name="spinner-gap" size="10" class="text-accent animate-spin" />
                            <div>
                                <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">Procesando validacion de pago</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Esto puede tomar unos segundos...</p>
                            </div>
                        </div>
                    </div>

                    @if ($inscripcionBorrador === null)
                        <div class="space-y-5">
                            <flux:callout
                                variant="warning"
                                icon="exclamation-circle"
                                heading="No existe una inscripcion borrador para validar pagos."
                            >
                                <p class="text-sm">Primero debemos guardar la postulacion para poder asociar una regla de pago y validar la operacion bancaria.</p>
                            </flux:callout>

                            <div class="flex gap-3">
                                <flux:button variant="filled" wire:click="irPasoAnterior" class="font-normal">
                                    Volver al paso anterior
                                </flux:button>

                                <flux:button variant="filled" wire:click="volverListado" class="font-normal">
                                    Volver al listado
                                </flux:button>
                            </div>
                        </div>
                    @elseif ($this->reglasPagoDisponibles->isEmpty())
                        <div class="space-y-5">
                            <flux:callout
                                variant="warning"
                                icon="exclamation-circle"
                                heading="No hay reglas de pago vigentes para esta postulacion."
                            >
                                <p class="text-sm">Revise la configuracion de reglas de pago del proceso actual antes de continuar con la validacion bancaria.</p>
                            </flux:callout>

                            <div class="flex gap-3">
                                <flux:button variant="filled" wire:click="irPasoAnterior" class="font-normal">
                                    Volver al paso anterior
                                </flux:button>

                                <flux:button variant="filled" wire:click="volverListado" class="font-normal">
                                    Volver al listado
                                </flux:button>
                            </div>
                        </div>
                    @else
                        <div class="space-y-5">
                            <section
                                class="space-y-4 rounded-lg border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40"
                            >
                                <div class="space-y-1">
                                    <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Contexto de inscripcion</p>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Validaremos el pago sobre la inscripcion borrador ya creada en el paso anterior.</p>
                                </div>

                                <div class="grid gap-4 lg:grid-cols-12">
                                    <div class="lg:col-span-4">
                                        <x-ui.input
                                            label="Codigo inscripcion"
                                            :value="$inscripcionBorrador->codigo_ins"
                                            disabled
                                            floating
                                        />
                                    </div>
                                    <div class="lg:col-span-4">
                                        <x-ui.input
                                            label="Postulante"
                                            :value="trim(implode(' ', array_filter([
                                                $inscripcionBorrador->persona?->apellido_paterno_per,
                                                $inscripcionBorrador->persona?->apellido_materno_per,
                                                $inscripcionBorrador->persona?->nombres_per,
                                            ])))"
                                            disabled
                                            floating
                                        />
                                    </div>
                                    <div class="lg:col-span-4">
                                        <x-ui.input
                                            label="Oferta seleccionada"
                                            :value="$this->descripcionProcesoOferta($inscripcionBorrador->procesoOferta)"
                                            disabled
                                            floating
                                        />
                                    </div>
                                </div>
                            </section>

                            <section
                                class="space-y-4 rounded-lg border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40"
                            >
                                <div class="space-y-1">
                                    <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Regla de pago</p>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Seleccione la tarifa que corresponde a esta inscripcion antes de buscar el pago del banco.</p>
                                </div>

                                <div class="grid gap-4 lg:grid-cols-12">
                                    <div class="lg:col-span-12">
                                        <x-ui.select
                                            label="Regla de pago"
                                            wire:model.live="pagoForm.id_regla_pago"
                                            :invalid="$errors->has('pagoForm.id_regla_pago')"
                                            :error="$errors->first('pagoForm.id_regla_pago')"
                                            required
                                            floating
                                        >
                                            <option value="">Seleccionar regla</option>
                                            @foreach ($this->reglasPagoDisponibles as $reglaPago)
                                                <option value="{{ $reglaPago->id_rpa }}">
                                                    {{ $this->descripcionReglaPago($reglaPago) }}
                                                </option>
                                            @endforeach
                                        </x-ui.select>
                                    </div>
                                </div>

                                @if ($reglaPagoSeleccionada)
                                    <flux:callout
                                        variant="secondary"
                                        icon="information-circle"
                                        heading="Resumen de la regla seleccionada"
                                    >
                                        <div class="grid gap-3 text-sm lg:grid-cols-2">
                                            <p><span class="font-medium">Concepto:</span> {{ $reglaPagoSeleccionada->conceptoPago?->nombre_cpg }}</p>
                                            <p><span class="font-medium">Monto total:</span> S/ {{ number_format((float) $reglaPagoSeleccionada->monto_total_rpa, 2) }}</p>
                                            <p><span class="font-medium">Cuotas:</span> {{ $reglaPagoSeleccionada->permite_cuotas_rpa ? 'Hasta '.$reglaPagoSeleccionada->numero_maximo_cuotas_rpa : 'Pago unico' }}</p>
                                            <p><span class="font-medium">Primera cuota minima:</span> S/ {{ number_format((float) $reglaPagoSeleccionada->monto_minimo_primera_cuota_rpa, 2) }}</p>
                                        </div>
                                    </flux:callout>
                                @endif

                                @if ($obligacionPagoSeleccionada)
                                    <flux:callout
                                        variant="{{ $obligacionPagoSeleccionada->estado_iop->value === 'PAGADO' ? 'success' : 'warning' }}"
                                        icon="{{ $obligacionPagoSeleccionada->estado_iop->value === 'PAGADO' ? 'check-circle' : 'clock' }}"
                                        heading="Estado actual de la obligacion"
                                    >
                                        <div class="grid gap-3 text-sm lg:grid-cols-3">
                                            <p><span class="font-medium">Pagado:</span> S/ {{ number_format((float) $obligacionPagoSeleccionada->monto_pagado_iop, 2) }}</p>
                                            <p><span class="font-medium">Saldo:</span> S/ {{ number_format((float) $obligacionPagoSeleccionada->saldo_iop, 2) }}</p>
                                            <p><span class="font-medium">Estado:</span> {{ $obligacionPagoSeleccionada->estado_iop->value }}</p>
                                        </div>
                                    </flux:callout>
                                @endif
                            </section>

                            <section
                                class="space-y-4 rounded-lg border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40"
                            >
                                <div class="space-y-1">
                                    <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Busqueda de pago bancario</p>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Busque la operacion usando DNI, operacion visible y fecha del abono importado del banco.</p>
                                </div>

                                <div class="grid gap-4 lg:grid-cols-12">
                                    <div class="lg:col-span-4">
                                        <x-ui.input
                                            label="Numero documento"
                                            wire:model="pagoForm.numero_documento"
                                            disabled
                                            :invalid="$errors->has('pagoForm.numero_documento')"
                                            :error="$errors->first('pagoForm.numero_documento')"
                                            floating
                                        />
                                    </div>

                                    <div class="lg:col-span-4">
                                        <x-ui.input
                                            label="Operacion visible"
                                            type="text"
                                            wire:model="pagoForm.numero_operacion"
                                            :invalid="$errors->has('pagoForm.numero_operacion')"
                                            :error="$errors->first('pagoForm.numero_operacion')"
                                            required
                                            floating
                                        />
                                    </div>

                                    <div class="lg:col-span-4">
                                        <x-ui.input
                                            label="Fecha de pago"
                                            type="date"
                                            wire:model="pagoForm.fecha_pago"
                                            :invalid="$errors->has('pagoForm.fecha_pago')"
                                            :error="$errors->first('pagoForm.fecha_pago')"
                                            required
                                            floating
                                        />
                                    </div>
                                </div>

                                <div class="flex justify-end">
                                    <flux:button
                                        type="button"
                                        variant="primary"
                                        color="purple"
                                        wire:click="buscarPagosPasoPago"
                                        wire:loading.attr="disabled"
                                        wire:target="buscarPagosPasoPago"
                                    >
                                        <x-slot name="icon">
                                            <x-ui.icon name="magnifying-glass" size="5" class="text-white" />
                                        </x-slot>
                                        <span wire:loading.remove wire:target="buscarPagosPasoPago">Buscar pago</span>
                                        <span wire:loading wire:target="buscarPagosPasoPago">Buscando...</span>
                                    </flux:button>
                                </div>
                            </section>

                            @if ($busquedaPagoRealizada)
                                <section
                                    class="space-y-4 rounded-lg border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40"
                                >
                                    <div class="space-y-1">
                                        <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Resultados importados</p>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Seleccione el pago correcto antes de confirmar la validacion de la obligacion.</p>
                                    </div>

                                    @if ($this->detallesPagoEncontrados->isEmpty())
                                        <flux:callout
                                            variant="warning"
                                            icon="exclamation-circle"
                                            heading="No se encontraron pagos pendientes con esos datos."
                                        >
                                            <p class="text-sm">Revise la operacion visible, la fecha o confirme que el TXT del banco ya fue importado.</p>
                                        </flux:callout>
                                    @else
                                        <div class="grid gap-3">
                                            @foreach ($this->detallesPagoEncontrados as $detallePago)
                                                <button
                                                    type="button"
                                                    wire:key="detalle-pago-{{ $detallePago->id_ibd }}"
                                                    wire:click="seleccionarDetallePago({{ $detallePago->id_ibd }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="buscarPagosPasoPago,confirmarPasoPago,seleccionarDetallePago"
                                                    @class ([
                                                        'w-full rounded-xl border px-4 py-3 text-left transition',
                                                        'border-green-500 bg-green-50/70 dark:border-green-400 dark:bg-green-500/10' => (string) $pagoForm['id_detalle_banco'] === (string) $detallePago->id_ibd,
                                                        'border-zinc-200 bg-white hover:border-zinc-300 dark:border-white/10 dark:bg-zinc-900 dark:hover:border-white/20' => (string) $pagoForm['id_detalle_banco'] !== (string) $detallePago->id_ibd,
                                                    ])
                                                >
                                                    <div
                                                        class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between"
                                                    >
                                                        <div class="space-y-1">
                                                            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">
                                                                {{ $this->resumenDetallePago($detallePago) }}
                                                            </p>
                                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                                                Depositante: {{ $detallePago->nombre_depositante_ibd ?: 'SIN NOMBRE' }}
                                                            </p>
                                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                                                Operacion banco: {{ $detallePago->numero_operacion_raw_ibd ?: $detallePago->numero_operacion_ibd ?: 'SIN OPERACION' }}
                                                            </p>
                                                        </div>

                                                        <span
                                                            class="inline-flex items-center rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300"
                                                        >
                                                            {{ $detallePago->estado_ibd->value }}
                                                        </span>
                                                    </div>
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                </section>
                            @endif

                            @if ($obligacionPagoSeleccionada && $obligacionPagoSeleccionada->pagos->isNotEmpty())
                                <section
                                    class="space-y-4 rounded-lg border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40"
                                >
                                    <div class="space-y-1">
                                        <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Pagos ya validados</p>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Esta obligacion ya tiene cuotas registradas. Aqui puede revisar el historial acumulado.</p>
                                    </div>

                                    <div class="space-y-3">
                                        @foreach ($obligacionPagoSeleccionada->pagos as $pagoRegistrado)
                                            <div
                                                class="rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-white/10 dark:bg-zinc-900"
                                            >
                                                <div
                                                    class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between"
                                                >
                                                    <div class="space-y-1">
                                                        <p class="font-medium text-zinc-800 dark:text-zinc-100">
                                                            Cuota {{ $pagoRegistrado->numero_cuota_ipa }} | S/ {{ number_format((float) $pagoRegistrado->monto_ipa, 2) }}
                                                        </p>
                                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                                            {{ $pagoRegistrado->numero_operacion_ipa ?: 'SIN OPERACION' }} | {{ $pagoRegistrado->fecha_pago_ipa?->format('d/m/Y') }}
                                                        </p>
                                                    </div>
                                                    <span
                                                        class="inline-flex items-center rounded-full bg-green-50 px-3 py-1 text-xs font-medium text-green-700 dark:bg-green-500/10 dark:text-green-300"
                                                    >
                                                        {{ $pagoRegistrado->estado_validacion_ipa->value }}
                                                    </span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </section>
                            @endif

                            @if ($this->tienePagoValidadoWizard)
                                <flux:callout
                                    variant="success"
                                    icon="check-circle"
                                    heading="Pago validado correctamente"
                                >
                                    <p class="text-sm">Esta inscripcion ya tiene un pago verificado. Puede continuar directamente al paso de expedientes.</p>
                                </flux:callout>
                                <div class="flex justify-end">
                                    <flux:button
                                        variant="primary"
                                        type="button"
                                        wire:click="continuarPasoExpedientes"
                                        class="font-normal"
                                    >
                                        <x-slot name="icon">
                                            <x-ui.icon name="caret-circle-right" size="5" class="text-white" />
                                        </x-slot>
                                        Continuar a expedientes
                                    </flux:button>
                                </div>
                            @else
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Cuando confirmemos el pago, quedara asociado a la inscripcion y el wizard avanzara al paso de expedientes.</p>

                                    <div class="flex gap-3">
                                        <flux:button
                                            variant="filled"
                                            type="button"
                                            wire:click="irPasoAnterior"
                                            wire:loading.attr="disabled"
                                            wire:target="buscarPagosPasoPago,confirmarPasoPago"
                                            class="font-normal"
                                        >
                                            Volver
                                        </flux:button>

                                        <flux:button
                                            variant="primary"
                                            type="button"
                                            wire:click="confirmarPasoPago"
                                            wire:loading.attr="disabled"
                                            wire:target="confirmarPasoPago"
                                            class="font-normal"
                                        >
                                            <x-slot name="icon">
                                                <x-ui.icon name="check" size="5" class="text-white" />
                                            </x-slot>
                                            <span wire:loading.remove wire:target="confirmarPasoPago"
                                                >Confirmar pago y continuar</span
                                            >
                                            <span wire:loading wire:target="confirmarPasoPago">Validando...</span>
                                        </flux:button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @elseif ($pasoActual === 5)
                @php ($inscripcionBorrador = $this->inscripcionBorrador)
                @php ($requisitosExpediente = $this->requisitosExpedienteWizard)
                <div
                    class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900"
                >
                    <div class="space-y-1">
                        <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Paso 5</p>
                        <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100">Expedientes</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Revise los requisitos del proceso y registre el estado de cada expediente presentado por el postulante.</p>
                    </div>
                </div>
                <div
                    class="relative rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900"
                >
                    <div
                        wire:loading.flex
                        wire:target="guardarPasoExpedientes"
                        class="absolute inset-0 z-30 hidden items-center justify-center rounded-xl bg-white/80 backdrop-blur-[2px] dark:bg-zinc-900/80"
                    >
                        <div class="flex flex-col items-center gap-3 text-center">
                            <x-ui.icon name="spinner-gap" size="10" class="text-accent animate-spin" />
                            <div>
                                <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">Guardando expedientes</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Esto puede tomar unos segundos...</p>
                            </div>
                        </div>
                    </div>

                    @if ($inscripcionBorrador === null)
                        <div class="space-y-6">
                            <flux:callout
                                variant="warning"
                                icon="exclamation-circle"
                                heading="No existe una inscripcion borrador para registrar expedientes."
                            >
                                <p class="text-sm">Primero debemos completar los pasos previos del wizard antes de cerrar la revision documental.</p>
                            </flux:callout>

                            <div class="flex gap-3">
                                <flux:button variant="ghost" wire:click="irPasoAnterior" class="font-normal">
                                    Volver al paso anterior
                                </flux:button>

                                <flux:button variant="ghost" wire:click="volverListado" class="font-normal">
                                    Volver al listado
                                </flux:button>
                            </div>
                        </div>
                    @elseif ($requisitosExpediente->isEmpty())
                        <div class="space-y-6">
                            <flux:callout
                                variant="warning"
                                icon="exclamation-circle"
                                heading="No hay requisitos de expediente configurados para este proceso."
                            >
                                <p class="text-sm">Revise el mantenedor de requisitos antes de intentar finalizar la inscripcion.</p>
                            </flux:callout>

                            <div class="flex gap-3">
                                <flux:button variant="ghost" wire:click="irPasoAnterior" class="font-normal">
                                    Volver al paso anterior
                                </flux:button>

                                <flux:button variant="ghost" wire:click="volverListado" class="font-normal">
                                    Volver al listado
                                </flux:button>
                            </div>
                        </div>
                    @else
                        <form wire:submit="guardarPasoExpedientes" class="space-y-6">
                            <section
                                class="space-y-4 rounded-lg border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40"
                            >
                                <div class="space-y-1">
                                    <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Resumen de cierre</p>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">La inscripcion se activara solo si todos los expedientes obligatorios quedan aprobados.</p>
                                </div>

                                <div class="grid gap-4 lg:grid-cols-12">
                                    <div class="lg:col-span-4">
                                        <x-ui.input
                                            label="Codigo inscripcion"
                                            :value="$inscripcionBorrador->codigo_ins"
                                            disabled
                                            floating
                                        />
                                    </div>
                                    <div class="lg:col-span-4">
                                        <x-ui.input
                                            label="Estado actual"
                                            :value="$inscripcionBorrador->estado_ins->value"
                                            disabled
                                            floating
                                        />
                                    </div>
                                    <div class="lg:col-span-4">
                                        <x-ui.input
                                            label="Postulante"
                                            :value="trim(implode(' ', array_filter([
                                                $inscripcionBorrador->persona?->apellido_paterno_per,
                                                $inscripcionBorrador->persona?->apellido_materno_per,
                                                $inscripcionBorrador->persona?->nombres_per,
                                            ])))"
                                            disabled
                                            floating
                                        />
                                    </div>
                                </div>
                            </section>

                            <section
                                class="space-y-4 rounded-lg border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40"
                            >
                                <div class="space-y-1">
                                    <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Revision documental</p>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Registre el estado de revision, observaciones y una referencia simple del documento recibido.</p>
                                </div>

                                <div class="space-y-4">
                                    @foreach ($expedientesForm as $indice => $expediente)
                                        @php ($requisito = $requisitosExpediente->firstWhere('id_tipo_expediente', (int) $expediente['id_tipo_expediente']))
                                        <div
                                            wire:key="expediente-wizard-{{ $expediente['id_tipo_expediente'] }}"
                                            class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-zinc-900"
                                        >
                                            <div class="grid gap-4 lg:grid-cols-12">
                                                <div class="space-y-1 lg:col-span-4">
                                                    <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">
                                                        {{ $requisito?->tipoExpediente?->nombre_tex ?? 'EXPEDIENTE' }}
                                                    </p>
                                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                                        {{ $requisito?->tipoExpediente?->descripcion_tex ?? 'Sin descripcion registrada.' }}
                                                    </p>
                                                    <div class="pt-1">
                                                        <flux:badge
                                                            :color="$requisito?->obligatorio_rex ? 'emerald' : 'zinc'"
                                                            size="lg"
                                                        >
                                                            {{ $requisito?->obligatorio_rex ? 'OBLIGATORIO' : 'OPCIONAL' }}
                                                        </flux:badge>
                                                    </div>
                                                </div>

                                                <div class="lg:col-span-3">
                                                    <x-ui.select
                                                        label="Estado de revision"
                                                        wire:model="expedientesForm.{{ $indice }}.estado_revision"
                                                        :invalid="$errors->has('expedientesForm.'.$indice.'.estado_revision')"
                                                        :error="$errors->first('expedientesForm.'.$indice.'.estado_revision')"
                                                        required
                                                        floating
                                                    >
                                                        @foreach ($this->estadosRevisionExpediente() as $estadoRevision)
                                                            <option value="{{ $estadoRevision->value }}">
                                                                {{ $estadoRevision->value }}
                                                            </option>
                                                        @endforeach
                                                    </x-ui.select>
                                                </div>

                                                <div class="lg:col-span-5">
                                                    <x-ui.input
                                                        label="Referencia del documento"
                                                        type="text"
                                                        wire:model="expedientesForm.{{ $indice }}.nombre_archivo"
                                                        :invalid="$errors->has('expedientesForm.'.$indice.'.nombre_archivo')"
                                                        :error="$errors->first('expedientesForm.'.$indice.'.nombre_archivo')"
                                                        floating
                                                    />
                                                </div>

                                                <div class="lg:col-span-12">
                                                    <x-ui.textarea
                                                        label="Observacion"
                                                        wire:model="expedientesForm.{{ $indice }}.observacion"
                                                        :invalid="$errors->has('expedientesForm.'.$indice.'.observacion')"
                                                        :error="$errors->first('expedientesForm.'.$indice.'.observacion')"
                                                        rows="2"
                                                        floating
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </section>

                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">Al guardar este paso se registrara la revision documental y se cerrara el wizard de inscripcion.</p>

                                <div class="flex gap-3">
                                    <flux:button
                                        variant="ghost"
                                        type="button"
                                        wire:click="irPasoAnterior"
                                        class="font-normal"
                                    >
                                        Volver
                                    </flux:button>

                                    <flux:button variant="primary" type="submit" class="font-normal">
                                        <x-slot name="icon">
                                            <x-ui.icon name="check" size="5" class="text-white" />
                                        </x-slot>
                                        <span wire:loading.remove wire:target="guardarPasoExpedientes"
                                            >Guardar y finalizar</span
                                        >
                                        <span wire:loading wire:target="guardarPasoExpedientes">Guardando...</span>
                                    </flux:button>
                                </div>
                            </div>
                        </form>
                    @endif
                </div>
            @else
                <div
                    class="rounded-2xl border border-dashed border-zinc-300 bg-white px-6 py-16 text-center shadow-xs dark:border-white/10 dark:bg-zinc-900"
                >
                    <div class="flex flex-col items-center justify-center space-y-3">
                        <x-ui.icon name="stack" size="14" class="text-zinc-400 dark:text-zinc-500" />
                        <p class="text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ $this->pasos[$pasoActual]['titulo'] }}
                        </p>
                        <p class="max-w-md text-sm text-zinc-500 dark:text-zinc-400">Este paso se habilitara en la siguiente iteracion. La base del wizard ya quedo lista para continuar sin perder el avance anterior.</p>

                        <div class="flex gap-3">
                            <flux:button variant="filled" wire:click="irPasoAnterior" class="font-normal">
                                Volver al paso anterior
                            </flux:button>

                            <flux:button variant="filled" wire:click="volverListado" class="font-normal">
                                Volver al listado
                            </flux:button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
