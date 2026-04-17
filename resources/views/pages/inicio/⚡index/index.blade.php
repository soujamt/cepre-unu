@section ('icon')
    <x-header.icon icon="house-simple" />
@endsection

@section ('header')
    <x-header.title>INICIO</x-header.title>
@endsection

@section ('breadcrumbs')
    <x-breadcrumbs>
        <x-breadcrumbs.item href="{{ route('inicio') }}" wire:navigate>INICIO</x-breadcrumbs.item>
    </x-breadcrumbs>
@endsection

<div class="space-y-6">
    {{-- Saludo --}}
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
            Bienvenido, {{ Auth::user()?->nombres_usu ?? 'Usuario' }}
        </h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            Sistema de Gestión de Inscripciones — Centro Preuniversitario UNU
        </p>
    </div>

    {{-- Proceso activo --}}
    @if ($this->procesoActual)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-500/20 dark:bg-emerald-500/5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div
                        class="flex size-9 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400"
                    >
                        <x-ui.icon name="calendar-check" size="5" />
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-widest text-emerald-600 dark:text-emerald-500">
                            Proceso activo
                        </p>
                        <p class="font-semibold text-zinc-900 dark:text-white">
                            {{ $this->procesoActual->nombre_pro }}
                        </p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            {{ $this->procesoActual->semestre?->nombre_sem }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-4 text-xs text-zinc-500 dark:text-zinc-400">
                    @if ($this->procesoActual->fecha_inicio_pro)
                        <span>
                            Inicio:
                            <span class="font-medium text-zinc-700 dark:text-zinc-300">
                                {{ $this->procesoActual->fecha_inicio_pro->format('d M Y') }}
                            </span>
                        </span>
                    @endif
                    @if ($this->procesoActual->fecha_fin_pro)
                        <span>
                            Fin:
                            <span class="font-medium text-zinc-700 dark:text-zinc-300">
                                {{ $this->procesoActual->fecha_fin_pro->format('d M Y') }}
                            </span>
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Estadísticas del proceso --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-3">
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total inscritas</p>
                        <p class="text-4xl font-semibold text-zinc-900 dark:text-white">
                            {{ $this->estadisticas['total'] }}
                        </p>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500">
                            {{ $this->estadisticas['activas'] }} activas
                            · {{ $this->estadisticas['pendientes'] }} pendientes
                        </p>
                    </div>
                    <div
                        class="flex size-12 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-500 dark:bg-white/10 dark:text-zinc-300"
                    >
                        <x-ui.icon name="users-three" size="6" />
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-3">
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Activas</p>
                        <p class="text-4xl font-semibold text-zinc-900 dark:text-white">
                            {{ $this->estadisticas['activas'] }}
                        </p>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500">expedientes y pago completos</p>
                    </div>
                    <div
                        class="flex size-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300"
                    >
                        <x-ui.icon name="check-circle" size="6" />
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-3">
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Pago pendiente</p>
                        <p class="text-4xl font-semibold text-zinc-900 dark:text-white">
                            {{ $this->estadisticas['pago_pendiente'] }}
                        </p>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500">con saldo por cobrar</p>
                    </div>
                    <div
                        class="flex size-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300"
                    >
                        <x-ui.icon name="currency-circle-dollar" size="6" />
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-3">
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Docs observados</p>
                        <p class="text-4xl font-semibold text-zinc-900 dark:text-white">
                            {{ $this->estadisticas['docs_observados'] }}
                        </p>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500">observados o rechazados</p>
                    </div>
                    <div
                        class="flex size-12 items-center justify-center rounded-2xl bg-sky-100 text-sky-600 dark:bg-sky-500/10 dark:text-sky-300"
                    >
                        <x-ui.icon name="warning" size="6" />
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-500/20 dark:bg-amber-500/5">
            <div class="flex items-center gap-3">
                <x-ui.icon name="warning" size="5" class="text-amber-500 dark:text-amber-400" />
                <p class="text-sm font-medium text-amber-700 dark:text-amber-400">
                    No hay ningún proceso habilitado en este momento.
                </p>
            </div>
        </div>
    @endif

    {{-- Accesos rápidos --}}
    @php
        $modulos = collect([
            [
                'label' => 'Inscripciones',
                'descripcion' => 'Gestiona el registro de postulantes al CEPRE',
                'icon' => 'clipboard-text',
                'href' => route('inscripciones.index'),
                'icon_class' => 'bg-zinc-100 text-zinc-600 dark:bg-white/10 dark:text-zinc-300',
                'permiso' => 'inscripcion_indice',
            ],
            [
                'label' => 'Pagos',
                'descripcion' => 'Consulta los pagos registrados por proceso',
                'icon' => 'receipt',
                'href' => route('pagos.index'),
                'icon_class' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400',
                'permiso' => 'pago_indice',
            ],
            [
                'label' => 'Validar pagos',
                'descripcion' => 'Verifica y aprueba pagos bancarios importados',
                'icon' => 'shield-check',
                'href' => route('pagos.validar'),
                'icon_class' => 'bg-sky-100 text-sky-600 dark:bg-sky-500/10 dark:text-sky-400',
                'permiso' => 'pago_crear',
            ],
            [
                'label' => 'Reportes',
                'descripcion' => 'Exporta el listado de inscritos en formato CEPRE',
                'icon' => 'chart-bar',
                'href' => route('reportes.inscripciones.index'),
                'icon_class' => 'bg-violet-100 text-violet-600 dark:bg-violet-500/10 dark:text-violet-400',
                'permiso' => 'reporte_inscripcion_indice',
            ],
            [
                'label' => 'Conf. Académica',
                'descripcion' => 'Administra semestres, procesos, ofertas y convenios',
                'icon' => 'gear-six',
                'href' => route('procesos.index'),
                'icon_class' => 'bg-amber-100 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400',
                'permiso' => 'proceso_indice',
            ],
            [
                'label' => 'Seguridad',
                'descripcion' => 'Gestiona usuarios, roles y permisos del sistema',
                'icon' => 'lock-key',
                'href' => route('usuarios.index'),
                'icon_class' => 'bg-red-100 text-red-600 dark:bg-red-500/10 dark:text-red-400',
                'permiso' => 'usuario_indice',
            ],
        ])->filter(fn (array $m): bool => auth()->user()?->hasPermisoAccion($m['permiso']) ?? false);
    @endphp

    @if ($modulos->isNotEmpty())
        <div>
            <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">
                Accesos rápidos
            </p>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($modulos as $modulo)
                    <a
                        href="{{ $modulo['href'] }}"
                        wire:navigate
                        class="group flex items-center gap-4 rounded-xl border border-zinc-200 bg-white p-4 shadow-xs transition hover:border-zinc-300 hover:shadow-sm dark:border-white/10 dark:bg-zinc-900 dark:hover:border-white/20"
                    >
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-xl {{ $modulo['icon_class'] }}">
                            <x-ui.icon :name="$modulo['icon']" size="5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white">
                                {{ $modulo['label'] }}
                            </p>
                            <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $modulo['descripcion'] }}
                            </p>
                        </div>
                        <x-ui.icon
                            name="arrow-right"
                            size="4"
                            class="shrink-0 text-zinc-300 transition group-hover:text-zinc-400 dark:text-zinc-600 dark:group-hover:text-zinc-500"
                        />
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>
