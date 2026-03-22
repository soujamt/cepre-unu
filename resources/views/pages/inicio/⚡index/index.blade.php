@section ('icon')
    <x-header.icon icon="home" />
@endsection

@section ('header')
    <x-header.title>INICIO</x-header.title>
@endsection

@section ('breadcrumbs')
    <x-breadcrumbs>
        <x-breadcrumbs.item href="{{ route('inicio') }}" wire:navigate>INICIO</x-breadcrumbs.item>
    </x-breadcrumbs>
@endsection

<div class="flex w-full flex-col gap-5">
    <div class="flex flex-col gap-1">
        <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">Hola, Usuario 👋</h1>
        <p class="text-zinc-500 dark:text-zinc-400">Bienvenido al Sistema de Gestión de Inscripciones del Centro Preuniversitario de la Universidad Nacional de Ucayali - UNU. Selecciona un módulo para comenzar.</p>
    </div>
    <form class="w-full max-w-md space-y-5" wire:submit="save" novalidate>
        <x-ui.input
            name="usuario"
            label="Usuario"
            type="text"
            wire:model="usuario"
            :invalid="$errors->has('usuario')"
            :error="$errors->first('usuario')"
            required
            floating
        />

        <x-ui.input
            name="correo"
            label="Correo electrónico"
            type="email"
            wire:model="correo"
            :invalid="$errors->has('correo')"
            :error="$errors->first('correo')"
            required
            floating
        />

        <x-ui.textarea
            name="descripcion"
            label="Descripción"
            wire:model="descripcion"
            :invalid="$errors->has('descripcion')"
            :error="$errors->first('descripcion')"
            autoResize
            required
            floating
        />

        <x-ui.select
            name="carrera"
            label="Carrera"
            wire:model="carrera"
            :invalid="$errors->has('carrera')"
            :error="$errors->first('carrera')"
            required
            floating
        >
            <x-form.select.option value="sistemas">Ingeniería de Sistemas</x-form.select.option>
            <x-form.select.option value="civil">Ingeniería Civil</x-form.select.option>
            <x-form.select.option value="derecho">Derecho</x-form.select.option>
            <x-form.select.option value="contabilidad">Contabilidad</x-form.select.option>
        </x-ui.select>

        <x-ui.select-multiple
            name="modulos"
            label="Módulos"
            wire:model="modulos"
            :invalid="$errors->has('modulos')"
            :error="$errors->first('modulos')"
            required
            floating
        >
            <x-form.select.option value="inscripciones">Inscripciones</x-form.select.option>
            <x-form.select.option value="pagos">Pagos</x-form.select.option>
            <x-form.select.option value="reportes">Reportes</x-form.select.option>
            <x-form.select.option value="seguridad">Seguridad</x-form.select.option>
        </x-ui.select-multiple>

        <x-ui.select-filter
            name="programa"
            label="Programa"
            wire:model="programa"
            :invalid="$errors->has('programa')"
            :error="$errors->first('programa')"
            required
            floating
        >
            <option value="cepre" data-description="Modalidad regular">CEPRE Regular</option>
            <option value="intensivo" data-description="Preparación acelerada">Ciclo Intensivo</option>
            <option value="repaso" data-description="Refuerzo final antes del examen">Ciclo de Repaso</option>
        </x-ui.select-filter>

        <flux:field>
            <flux:checkbox wire:model="acepta_terminos" label="Aceptar términos" />
        </flux:field>

        <flux:field variant="inline">
            <flux:label>Estado activo</flux:label>
            <flux:switch wire:model="activo" />
        </flux:field>

        <flux:button type="submit" variant="primary" class="w-full"> SAVE </flux:button>
    </form>
</div>
