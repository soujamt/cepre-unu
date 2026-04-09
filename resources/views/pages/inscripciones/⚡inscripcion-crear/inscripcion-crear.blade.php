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

<div class="space-y-5">
    <div class="grid gap-5 xl:grid-cols-[19rem_minmax(0,1fr)]">
        <div class="space-y-4">
            <div
                class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900"
            >
                <div class="space-y-4">
                    <div class="space-y-1">
                        <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Proceso de registro</p>
                        <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100">Formulario por pasos</h3>
                    </div>

                    <div class="space-y-3">
                        @foreach ($this->pasos as $indice => $paso)
                            <button
                                type="button"
                                wire:key="paso-inscripcion-{{ $indice }}"
                                wire:click="irPaso({{ $indice }})"
                                @class ([
                                    'w-full rounded-xl border p-4 text-left transition',
                                    'border-zinc-900 bg-zinc-50 shadow-xs dark:border-white dark:bg-zinc-950/70' => $pasoActual === $indice,
                                    'border-zinc-200 bg-white hover:border-zinc-300 dark:border-white/10 dark:bg-zinc-900 dark:hover:border-white/20' => $pasoActual !== $indice,
                                ])
                            >
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex size-8 shrink-0 items-center justify-center rounded-full bg-zinc-900 text-sm font-semibold text-white dark:bg-white dark:text-zinc-900"
                                    >
                                        {{ $indice }}
                                    </div>

                                    <div class="space-y-1">
                                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $paso['titulo'] }}</p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $paso['descripcion'] }}</p>
                                    </div>
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div
                class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900"
            >
                <div class="space-y-3">
                    <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Estado actual</p>

                    <div class="space-y-2 text-sm text-zinc-600 dark:text-zinc-300">
                        <p>Paso: <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ $this->pasos[$pasoActual]['titulo'] }}</span></p>
                        <p>Persona base: <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ $personaId ? 'REGISTRADA' : 'PENDIENTE' }}</span></p>
                    </div>

                    <flux:button variant="filled" class="w-full font-normal" wire:click="volverListado">
                        <x-slot name="icon">
                            <x-ui.icon name="arrow-left" size="5" class="text-current" />
                        </x-slot>
                        Volver al listado
                    </flux:button>
                </div>
            </div>
        </div>

        <div class="space-y-5">
            @if ($pasoActual === 1)
                <div
                    class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900"
                >
                    <div class="space-y-1">
                        <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Paso 1</p>
                        <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100">Informacion personal</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Registre la identidad, nacimiento, domicilio y datos base del postulante.</p>
                    </div>
                </div>
                <div
                    class="relative rounded-2xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900"
                >
                    <div
                        wire:loading.flex
                        wire:target="guardarPasoPersonal"
                        class="absolute inset-0 z-30 hidden items-center justify-center rounded-2xl bg-white/80 backdrop-blur-[2px] dark:bg-zinc-900/80"
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
                        class="space-y-6"
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
                            class="space-y-4 rounded-2xl border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40"
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
                                            class="h-12 w-full justify-center rounded-xl"
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
                            class="space-y-4 rounded-2xl border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40"
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
                            class="space-y-4 rounded-2xl border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40"
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
                            class="space-y-4 rounded-2xl border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40"
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
                                    <x-ui.icon
                                        wire:loading.remove
                                        wire:target="guardarPasoPersonal"
                                        name="arrow-right"
                                        size="5"
                                        class="text-white"
                                    />
                                    <x-ui.icon
                                        wire:loading
                                        wire:target="guardarPasoPersonal"
                                        name="spinner-gap"
                                        size="5"
                                        class="animate-spin text-white"
                                    />
                                </x-slot>
                                <span wire:loading.remove wire:target="guardarPasoPersonal">Guardar y continuar</span>
                                <span wire:loading wire:target="guardarPasoPersonal">Guardando...</span>
                            </flux:button>
                        </div>
                    </form>
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
                        <p class="max-w-md text-sm text-zinc-500 dark:text-zinc-400">Este paso se habilitara en la siguiente iteracion. La base personal ya quedo guardada y lista para continuar.</p>

                        <div class="flex gap-3">
                            <flux:button variant="ghost" wire:click="irPasoAnterior" class="font-normal">
                                Volver al paso anterior
                            </flux:button>

                            <flux:button variant="ghost" wire:click="volverListado" class="font-normal">
                                Volver al listado
                            </flux:button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
