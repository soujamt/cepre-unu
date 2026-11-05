<x-inscripcion.encabezado-paso
    :paso="2"
    :total="count($this->pasos)"
    :icono="$this->pasos[2]['icono']"
    titulo="Información general"
    descripcion="Registre la procedencia educativa y el historial de postulación del postulante."
/>
<div
class="relative rounded-xl border border-zinc-200/80 bg-white p-5 shadow-card dark:border-white/[0.06] dark:bg-zinc-900"
>
<div
    wire:loading.flex
    wire:target="guardarPasoGeneral"
    class="absolute inset-0 z-30 hidden items-center justify-center rounded-xl bg-white/80 backdrop-blur-[2px] dark:bg-zinc-900/80"
>
    <div class="flex flex-col items-center gap-3 text-center">
        <x-ui.icon name="loader-lines" size="10" class="text-accent animate-spin" />
        <div>
            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">Guardando información general</p>
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
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Defina el año de graduación, país del colegio y tipo de institución educativa.</p>
        </div>

        <div class="grid gap-4 lg:grid-cols-12">
            <div class="lg:col-span-4">
                <x-ui.input
                    label="Año de graduación"
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
                    label="País del colegio"
                    wire:model="generalForm.id_pais_colegio"
                    :invalid="$errors->has('generalForm.id_pais_colegio')"
                    :error="$errors->first('generalForm.id_pais_colegio')"
                    required
                    floating
                >
                    <option value="">Seleccionar país</option>
                    @foreach ($this->paises as $pais)
                        <option value="{{ $pais->id_pai }}">{{ $pais->nombre_pai }}</option>
                    @endforeach
                </x-ui.select-filter>
            </div>

            <div class="lg:col-span-4">
                <x-ui.select
                    label="Tipo de colegio"
                    wire:model.live="generalForm.id_tipo_colegio"
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
                    filter-model="generalForm.id_tipo_colegio"
                    filter-query-name="id_tipo_colegio"
                    :filter-required="true"
                    :reset-on-filter-change="true"
                    :selected-label="$this->colegioLabel"
                    placeholder="Seleccione el tipo y busque por código o nombre"
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
                class="rounded-xl border border-dashed border-zinc-300 bg-white/70 px-4 py-3 text-sm text-zinc-500 lg:col-span-12 dark:border-white/[0.06] dark:bg-zinc-900/50 dark:text-zinc-400"
            >
                Seleccione primero el país del colegio para continuar con la procedencia educativa.
            </div>
        </div>
    </section>

    <section
        class="space-y-4 rounded-lg border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40"
    >
        <div class="space-y-1">
            <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Historial de postulación</p>
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
            label="Observación general"
            wire:model="generalForm.observacion"
            :invalid="$errors->has('generalForm.observacion')"
            :error="$errors->first('generalForm.observacion')"
            rows="3"
            floating
        />
    </section>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-zinc-500 dark:text-zinc-400">Al guardar este paso, quedará lista la procedencia educativa para continuar con la postulación.</p>

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
                    <x-ui.icon name="chevron-right-circle" size="5" class="text-white" />
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
