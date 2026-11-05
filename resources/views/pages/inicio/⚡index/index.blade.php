@section ('icon')
    <x-header.icon icon="home" />
@endsection

@section ('header')
    <x-header.title>Inicio</x-header.title>
@endsection

@section ('breadcrumbs')
    <x-breadcrumbs>
        <x-breadcrumbs.item href="{{ route('inicio') }}" wire:navigate>Inicio</x-breadcrumbs.item>
    </x-breadcrumbs>
@endsection

<div class="space-y-6">
    @php
        $actividadCards = [
            [
                'label' => 'Inscripciones hoy',
                'valor' => $this->actividadHoy['nuevas_inscripciones'],
                'descripcion' => 'registros creados en la jornada actual',
                'icon' => 'group',
                'tono' => 'info',
                'badge' => $this->actividadHoy['nuevas_inscripciones'] > 0 ? 'Con movimiento' : 'Sin altas',
            ],
            [
                'label' => 'Pagos validados hoy',
                'valor' => $this->actividadHoy['pagos_validados'],
                'descripcion' => 'confirmaciones bancarias aprobadas',
                'icon' => 'check-shield',
                'tono' => 'success',
                'badge' => $this->actividadHoy['pagos_validados'] > 0 ? 'Validando bien' : 'Sin validaciones',
            ],
            [
                'label' => 'Expedientes revisados',
                'valor' => $this->actividadHoy['expedientes_revisados'],
                'descripcion' => 'inscripciones con revisión documental hoy',
                'icon' => 'copy',
                'tono' => 'neutral',
                'badge' => $this->actividadHoy['expedientes_revisados'] > 0 ? 'Equipo activo' : 'Sin revisión',
            ],
        ];

        $estadoInscripcionClasses = [
            'ACTIVO' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300',
            'PENDIENTE' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300',
            'ANULADO' => 'border-red-200 bg-red-50 text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-300',
        ];

        $alertaStyles = [
            'danger' => [
                'card' => 'border-red-200 bg-red-50/80 text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-300',
                'badge' => 'border-red-200 bg-red-100 text-red-700 dark:border-red-500/20 dark:bg-red-500/15 dark:text-red-300',
                'label' => 'Urgente',
                'icon' => 'alert-octagon',
            ],
            'warning' => [
                'card' => 'border-amber-200 bg-amber-50/80 text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300',
                'badge' => 'border-amber-200 bg-amber-100 text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/15 dark:text-amber-300',
                'label' => 'Atender',
                'icon' => 'alert-circle',
            ],
            'info' => [
                'card' => 'border-sky-200 bg-sky-50/80 text-sky-700 dark:border-sky-500/20 dark:bg-sky-500/10 dark:text-sky-300',
                'badge' => 'border-sky-200 bg-sky-100 text-sky-700 dark:border-sky-500/20 dark:bg-sky-500/15 dark:text-sky-300',
                'label' => 'Seguimiento',
                'icon' => 'info-circle',
            ],
        ];

        $alertasPorTono = $this->alertasOperativas->countBy('tono');
    @endphp

    <div class="overflow-hidden rounded-xl border border-zinc-200/80 bg-white shadow-card dark:border-white/[0.06] dark:bg-zinc-900">
        <div class="grid gap-6 px-6 py-6 lg:grid-cols-[minmax(0,1.5fr)_minmax(19rem,0.95fr)] lg:px-7">
            <div class="space-y-4">
                <div class="space-y-2">
                    <p class="text-xs font-semibold tracking-[0.22em] text-zinc-500 uppercase dark:text-zinc-400">Vista general</p>
                    <h1 class="max-w-2xl text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">
                        Bienvenido, {{ Auth::user()?->nombres_usu ?? 'Usuario' }}
                    </h1>
                    <p class="max-w-2xl text-sm text-zinc-500 dark:text-zinc-400">
                        Panel operativo del proceso de admisión. Aquí puedes revisar el estado del proceso vigente, la actividad del día y los puntos que necesitan atención.
                    </p>
                </div>

                <x-resumen :columnas="3">
                    @foreach ($actividadCards as $card)
                        <x-resumen.item
                            :label="$card['label']"
                            :value="$card['valor']"
                            :icon="$card['icon']"
                            :tono="$card['tono']"
                            :detalle="$card['badge']"
                            :detalle-icon="$card['valor'] > 0 ? 'trending-up' : 'minus'"
                            :destacado="$loop->first"
                            :title="$card['descripcion']"
                        />
                    @endforeach
                </x-resumen>
            </div>

            <div class="rounded-xl border border-zinc-200/80 bg-zinc-50/70 p-5 dark:border-white/[0.06] dark:bg-zinc-950/40">
                @if ($this->procesoActual)
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold tracking-[0.18em] text-emerald-600 uppercase dark:text-emerald-400">Proceso vigente</p>
                                <h3 class="mt-1 text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->procesoActual->nombre_pro }}</h3>
                            </div>
                            <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
                                En curso
                            </span>
                        </div>

                        <div class="space-y-2 text-sm text-zinc-600 dark:text-zinc-300">
                            <p><span class="font-medium text-zinc-900 dark:text-white">Apertura:</span> {{ $this->procesoActual->fecha_inicio_pro?->format('d/m/Y h:i a') ?? '—' }}</p>
                            <p><span class="font-medium text-zinc-900 dark:text-white">Cierre:</span> {{ $this->procesoActual->fecha_fin_pro?->format('d/m/Y h:i a') ?? '—' }}</p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="rounded-xl bg-white p-3 shadow-xs dark:bg-zinc-900/80">
                                <p class="text-[11px] font-semibold tracking-[0.16em] text-zinc-500 uppercase dark:text-zinc-400">Estado</p>
                                <p class="mt-1 text-sm font-medium text-zinc-900 dark:text-white">Inscripción operativa</p>
                            </div>

                            <div class="rounded-xl bg-white p-3 shadow-xs dark:bg-zinc-900/80">
                                <p class="text-[11px] font-semibold tracking-[0.16em] text-zinc-500 uppercase dark:text-zinc-400">Pendientes</p>
                                <p class="mt-1 text-sm font-medium text-zinc-900 dark:text-white">{{ $this->estadisticas['pendientes'] }} registros por completar</p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="space-y-4">
                        <p class="text-xs font-semibold tracking-[0.18em] text-amber-600 uppercase dark:text-amber-400">Disponibilidad</p>
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">No hay un proceso habilitado</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Hasta que exista un proceso vigente con configuración activa, el panel mostrará solo información general.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if ($this->procesoActual)
        <div class="grid gap-5 xl:grid-cols-[minmax(0,1.35fr)_minmax(21rem,0.9fr)]">
            <div class="space-y-5">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-zinc-200/80 bg-white p-5 shadow-card dark:border-white/[0.06] dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-3">
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total inscritas</p>
                        <p class="text-4xl font-semibold text-zinc-900 dark:text-white">
                            {{ $this->estadisticas['total'] }}
                        </p>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500">
                            {{ $this->estadisticas['activas'] }} activas · {{ $this->estadisticas['pendientes'] }} pendientes
                        </p>
                    </div>
                    <div
                        class="flex size-12 items-center justify-center rounded-xl bg-zinc-100 text-zinc-500 dark:bg-white/10 dark:text-zinc-300"
                    >
                        <x-ui.icon name="group" size="6" />
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/50 p-5 shadow-card dark:border-emerald-500/20 dark:bg-emerald-500/5">
                <div class="flex items-start justify-between gap-3">
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-emerald-700 dark:text-emerald-300">Activas</p>
                        <p class="text-4xl font-semibold text-zinc-900 dark:text-white">
                            {{ $this->estadisticas['activas'] }}
                        </p>
                        <p class="text-xs text-emerald-700/80 dark:text-emerald-300/80">expedientes y pago completos</p>
                    </div>
                    <div
                        class="flex size-12 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300"
                    >
                        <x-ui.icon name="check-circle" size="6" />
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-amber-200/80 bg-amber-50/50 p-5 shadow-card dark:border-amber-500/20 dark:bg-amber-500/5">
                <div class="flex items-start justify-between gap-3">
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-amber-700 dark:text-amber-300">Pago pendiente</p>
                        <p class="text-4xl font-semibold text-zinc-900 dark:text-white">
                            {{ $this->estadisticas['pago_pendiente'] }}
                        </p>
                        <p class="text-xs text-amber-700/80 dark:text-amber-300/80">con saldo por cobrar</p>
                    </div>
                    <div
                        class="flex size-12 items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300"
                    >
                        <x-ui.icon name="dollar-circle" size="6" />
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-red-200/80 bg-red-50/50 p-5 shadow-card dark:border-red-500/20 dark:bg-red-500/5">
                <div class="flex items-start justify-between gap-3">
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-red-700 dark:text-red-300">Docs observados</p>
                        <p class="text-4xl font-semibold text-zinc-900 dark:text-white">
                            {{ $this->estadisticas['docs_observados'] }}
                        </p>
                        <p class="text-xs text-red-700/80 dark:text-red-300/80">observados o rechazados</p>
                    </div>
                    <div
                        class="flex size-12 items-center justify-center rounded-xl bg-red-100 text-red-600 dark:bg-red-500/10 dark:text-red-300"
                    >
                        <x-ui.icon name="alert-triangle" size="6" />
                    </div>
                </div>
            </div>
                </div>

                <div class="rounded-xl border border-zinc-200/80 bg-white p-5 shadow-card dark:border-white/[0.06] dark:bg-zinc-900">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Actividad reciente</p>
                            <h3 class="mt-1 text-base font-semibold text-zinc-900 dark:text-white">Últimas inscripciones del proceso</h3>
                        </div>
                    </div>

                    @if ($this->inscripcionesRecientes->isEmpty())
                        <div class="rounded-xl border border-dashed border-zinc-200/80 bg-zinc-50/80 p-5 text-sm text-zinc-500 dark:border-white/[0.06] dark:bg-zinc-950/40 dark:text-zinc-400">
                            Aún no hay inscripciones recientes para mostrar en este proceso.
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach ($this->inscripcionesRecientes as $inscripcion)
                                <div class="flex flex-col gap-3 rounded-xl border border-zinc-200/80 bg-zinc-50/70 p-4 dark:border-white/[0.06] dark:bg-zinc-950/40 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-zinc-900 dark:text-white">
                                            {{ $inscripcion->persona?->apellido_paterno_per }} {{ $inscripcion->persona?->apellido_materno_per }}, {{ $inscripcion->persona?->nombres_per }}
                                        </p>
                                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $inscripcion->codigo_ins }} · {{ $inscripcion->procesoOferta?->ofertaBase?->sedeCarrera?->carrera?->nombre_car ?? 'Sin carrera' }}
                                        </p>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2 text-xs">
                                        @php
                                            $estadoInscripcion = strtoupper($inscripcion->estado_ins?->value ?? '');
                                            $estadoBadgeClass = $estadoInscripcionClasses[$estadoInscripcion] ?? 'border-zinc-200 bg-white text-zinc-600 dark:border-white/10 dark:bg-zinc-900 dark:text-zinc-300';
                                        @endphp
                                        <span class="rounded-full border px-3 py-1 font-medium {{ $estadoBadgeClass }}">
                                            {{ $inscripcion->estado_ins?->value ?? '—' }}
                                        </span>
                                        <span class="rounded-full border border-zinc-200 bg-white px-3 py-1 font-medium text-zinc-600 dark:border-white/10 dark:bg-zinc-900 dark:text-zinc-300">
                                            {{ $inscripcion->fecha_inscripcion_ins?->format('d/m H:i') ?? 'Sin fecha' }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="space-y-5">
                <div class="rounded-xl border border-zinc-200/80 bg-white p-5 shadow-card dark:border-white/[0.06] dark:bg-zinc-900">
                    <div class="mb-4">
                        <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Atención inmediata</p>
                        <h3 class="mt-1 text-base font-semibold text-zinc-900 dark:text-white">Alertas operativas</h3>
                    </div>

                    @if ($this->alertasOperativas->isEmpty())
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50/80 p-4 text-sm text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
                            No hay alertas críticas en este momento para el proceso actual.
                        </div>
                    @else
                        <div class="mb-4 flex flex-wrap gap-2">
                            @if (($alertasPorTono['danger'] ?? 0) > 0)
                                <span class="rounded-full border border-red-200 bg-red-50 px-3 py-1 text-xs font-semibold text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-300">
                                    {{ $alertasPorTono['danger'] }} urgente{{ ($alertasPorTono['danger'] ?? 0) > 1 ? 's' : '' }}
                                </span>
                            @endif
                            @if (($alertasPorTono['warning'] ?? 0) > 0)
                                <span class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
                                    {{ $alertasPorTono['warning'] }} por atender
                                </span>
                            @endif
                            @if (($alertasPorTono['info'] ?? 0) > 0)
                                <span class="rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700 dark:border-sky-500/20 dark:bg-sky-500/10 dark:text-sky-300">
                                    {{ $alertasPorTono['info'] }} en seguimiento
                                </span>
                            @endif
                        </div>

                        <div class="space-y-3">
                            @foreach ($this->alertasOperativas as $alerta)
                                @php
                                    $estiloAlerta = $alertaStyles[$alerta['tono']] ?? $alertaStyles['info'];
                                @endphp
                                <div class="rounded-xl border p-4 {{ $estiloAlerta['card'] }}">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex min-w-0 items-start gap-3">
                                            <div class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-xl bg-white/70 dark:bg-black/10">
                                                <x-ui.icon :name="$estiloAlerta['icon']" size="5" />
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold">{{ $alerta['titulo'] }}</p>
                                                <p class="mt-1 text-xs opacity-90">{{ $alerta['detalle'] }}</p>
                                            </div>
                                        </div>
                                        <span class="rounded-full border px-2.5 py-1 text-[11px] font-semibold whitespace-nowrap {{ $estiloAlerta['badge'] }}">
                                            {{ $estiloAlerta['label'] }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @else
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-500/20 dark:bg-amber-500/5">
            <div class="flex items-center gap-3">
                <x-ui.icon name="alert-triangle" size="5" class="text-amber-500 dark:text-amber-400" />
                <p class="text-sm font-medium text-amber-700 dark:text-amber-400">No hay ningún proceso habilitado en este momento.</p>
            </div>
        </div>
    @endif

    @php
        $modulos = collect([
            [
                'label' => 'Inscripciones',
                'descripcion' => 'Gestiona el registro de postulantes al CEPRE',
                'icon' => 'clipboard-detail',
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
                'icon' => 'check-shield',
                'href' => route('pagos.validar'),
                'icon_class' => 'bg-sky-100 text-sky-600 dark:bg-sky-500/10 dark:text-sky-400',
                'permiso' => 'pago_crear',
            ],
            [
                'label' => 'Reportes',
                'descripcion' => 'Exporta el listado de inscritos en formato CEPRE',
                'icon' => 'bar-chart',
                'href' => route('reportes.inscripciones.index'),
                'icon_class' => 'bg-violet-100 text-violet-600 dark:bg-violet-500/10 dark:text-violet-400',
                'permiso' => 'reporte_inscripcion_indice',
            ],
            [
                'label' => 'Conf. Académica',
                'descripcion' => 'Administra procesos, ofertas, aulas y convenios',
                'icon' => 'cog',
                'href' => route('procesos.index'),
                'icon_class' => 'bg-amber-100 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400',
                'permiso' => 'proceso_indice',
            ],
            [
                'label' => 'Seguridad',
                'descripcion' => 'Gestiona usuarios, roles y permisos del sistema',
                'icon' => 'lock-keyhole',
                'href' => route('usuarios.index'),
                'icon_class' => 'bg-red-100 text-red-600 dark:bg-red-500/10 dark:text-red-400',
                'permiso' => 'usuario_indice',
            ],
        ])->filter(fn (array $m): bool => auth()->user()?->hasPermisoAccion($m['permiso']) ?? false);
    @endphp

    @if ($modulos->isNotEmpty())
        <div class="rounded-xl border border-zinc-200/80 bg-white p-5 shadow-card dark:border-white/[0.06] dark:bg-zinc-900">
            <div class="mb-4">
                <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Accesos rápidos</p>
                <h3 class="mt-1 text-base font-semibold text-zinc-900 dark:text-white">Módulos más usados</h3>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($modulos as $modulo)
                    <a
                        href="{{ $modulo['href'] }}"
                        wire:navigate
                        class="group flex min-w-0 items-center gap-4 rounded-xl border border-zinc-200/80 bg-white p-4 shadow-card transition hover:border-zinc-300 hover:shadow-card-hover dark:border-white/[0.06] dark:bg-zinc-900 dark:hover:border-white/20"
                    >
                        <div
                            class="flex size-10 shrink-0 items-center justify-center rounded-xl {{ $modulo['icon_class'] }}"
                        >
                            <x-ui.icon :name="$modulo['icon']" size="5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $modulo['label'] }}</p>
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
