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
                'label' => 'Inscripciones',
                'valor' => $this->actividadHoy['nuevas_inscripciones'],
                'descripcion' => 'registros creados en la jornada actual',
                'icon' => 'group',
                'tono' => 'info',
                'badge' => $this->actividadHoy['nuevas_inscripciones'] > 0 ? 'Con movimiento' : 'Sin altas',
            ],
            [
                'label' => 'Pagos validados',
                'valor' => $this->actividadHoy['pagos_validados'],
                'descripcion' => 'confirmaciones bancarias aprobadas',
                'icon' => 'check-shield',
                'tono' => 'success',
                'badge' => $this->actividadHoy['pagos_validados'] > 0 ? 'Validando bien' : 'Sin validaciones',
            ],
            [
                'label' => 'Expedientes',
                'valor' => $this->actividadHoy['expedientes_revisados'],
                'descripcion' => 'inscripciones con revisión documental hoy',
                'icon' => 'copy',
                'tono' => 'neutral',
                'badge' => $this->actividadHoy['expedientes_revisados'] > 0 ? 'Equipo activo' : 'Sin revisión',
            ],
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

                <p class="pt-1 text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Actividad de hoy</p>

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
        <div class="space-y-3">
            <div>
                <p class="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Resumen del proceso</p>
                <h3 class="mt-1 text-base font-semibold text-zinc-900 dark:text-white">Estado de las inscripciones de {{ $this->procesoActual->nombre_pro }}</h3>
            </div>

            <x-resumen>
                <x-resumen.item
                    label="Total inscritas"
                    :value="$this->estadisticas['total']"
                    icon="group"
                    tono="info"
                    detalle-icon="check-circle"
                    :detalle="$this->estadisticas['activas'].' activas · '.$this->estadisticas['pendientes'].' pendientes'"
                />

                <x-resumen.item
                    label="Activas"
                    :value="$this->estadisticas['activas']"
                    icon="check-circle"
                    tono="success"
                    detalle-icon="check"
                    detalle="Expedientes y pago completos"
                />

                <x-resumen.item
                    label="Pago pendiente"
                    :value="$this->estadisticas['pago_pendiente']"
                    icon="dollar-circle"
                    :tono="$this->estadisticas['pago_pendiente'] > 0 ? 'warning' : 'success'"
                    :detalle-icon="$this->estadisticas['pago_pendiente'] > 0 ? 'clock' : 'check'"
                    :detalle="$this->estadisticas['pago_pendiente'] > 0 ? 'Con saldo por cobrar' : 'Pagos al día'"
                />

                <x-resumen.item
                    label="Docs observados"
                    :value="$this->estadisticas['docs_observados']"
                    icon="file-search"
                    :tono="$this->estadisticas['docs_observados'] > 0 ? 'warning' : 'success'"
                    :detalle-icon="$this->estadisticas['docs_observados'] > 0 ? 'alert-triangle' : 'check'"
                    :detalle="$this->estadisticas['docs_observados'] > 0 ? 'Observados o rechazados' : 'Sin observaciones'"
                />
            </x-resumen>
        </div>

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1.35fr)_minmax(21rem,0.9fr)]">
                @php
                    $puedeVerInscripciones = auth()->user()?->hasPermisoAccion('inscripcion_indice') ?? false;
                    $tipoTitulo = fn (?string $texto): string => preg_replace_callback(
                        '/(?<=\s)(De|Del|La|Las|Los|Y|E|En|Con|Para)(?=\s)/u',
                        fn (array $conector): string => mb_strtolower($conector[1], 'UTF-8'),
                        \Illuminate\Support\Str::title(mb_strtolower((string) $texto, 'UTF-8')),
                    );
                @endphp
                <div class="flex h-full flex-col rounded-xl border border-zinc-200/80 bg-white shadow-card dark:border-white/[0.06] dark:bg-zinc-900">
                    <div class="flex items-center justify-between gap-3 border-b border-zinc-200/80 px-5 py-4 dark:border-white/[0.06]">
                        <div>
                            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Últimas inscripciones</h3>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Registros más recientes del proceso {{ $this->procesoActual->nombre_pro }}</p>
                        </div>

                        @if ($puedeVerInscripciones && $this->inscripcionesRecientes->isNotEmpty())
                            <a
                                href="{{ route('inscripciones.index') }}"
                                wire:navigate
                                class="inline-flex shrink-0 items-center gap-1 text-sm font-medium text-index-600 hover:text-index-700 dark:text-index-300 dark:hover:text-index-200"
                            >
                                Ver todas
                                <x-ui.icon name="chevron-right" size="4" />
                            </a>
                        @endif
                    </div>

                    @if ($this->inscripcionesRecientes->isEmpty())
                        <div class="flex flex-1 flex-col items-center justify-center gap-2 px-5 py-10 text-center">
                            <x-ui.icon name="clipboard-detail" size="8" class="text-zinc-300 dark:text-zinc-600" />
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Aún no hay inscripciones en este proceso.</p>
                        </div>
                    @else
                        <ul class="divide-y divide-zinc-200/80 dark:divide-white/[0.06]">
                            @foreach ($this->inscripcionesRecientes as $inscripcion)
                                @php
                                    $persona = $inscripcion->persona;
                                    $iniciales = mb_strtoupper(
                                        mb_substr((string) $persona?->nombres_per, 0, 1).mb_substr((string) $persona?->apellido_paterno_per, 0, 1),
                                        'UTF-8',
                                    );
                                    $nombreCompleto = trim($tipoTitulo($persona?->nombres_per).' '.$tipoTitulo($persona?->apellido_paterno_per).' '.$tipoTitulo($persona?->apellido_materno_per));
                                    $carrera = $inscripcion->procesoOferta?->ofertaBase?->sedeCarrera?->carrera?->nombre_car;
                                    $fecha = $inscripcion->fecha_inscripcion_ins;
                                @endphp
                                <li wire:key="inscripcion-reciente-{{ $inscripcion->id_ins }}">
                                    <a
                                        @if ($puedeVerInscripciones)
                                            href="{{ route('inscripciones.detalle', encode($inscripcion->id_ins)) }}"
                                            wire:navigate
                                        @endif
                                        @class ([
                                            'flex items-center gap-3 px-5 py-3',
                                            'transition-colors hover:bg-zinc-50 dark:hover:bg-white/[0.03]' => $puedeVerInscripciones,
                                        ])
                                    >
                                        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-xs font-semibold text-zinc-600 dark:bg-white/10 dark:text-zinc-300">
                                            {{ $iniciales ?: '?' }}
                                        </span>

                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-medium text-zinc-900 dark:text-white">{{ $nombreCompleto ?: 'Sin datos del postulante' }}</span>
                                            <span class="block truncate text-xs text-zinc-500 dark:text-zinc-400">
                                                {{ $carrera ? $tipoTitulo($carrera) : 'Sin carrera' }}
                                                <span class="text-zinc-300 dark:text-zinc-600">·</span>
                                                <span class="font-mono">{{ $inscripcion->codigo_ins }}</span>
                                            </span>
                                        </span>

                                        <span class="flex shrink-0 flex-col items-end gap-1">
                                            @if ($inscripcion->estado_ins)
                                                <flux:badge size="sm" color="{{ $inscripcion->estado_ins->getColor() }}">{{ $tipoTitulo($inscripcion->estado_ins->getNombre()) }}</flux:badge>
                                            @endif
                                            <span class="text-xs text-zinc-400 tabular-nums dark:text-zinc-500" @if ($fecha) title="{{ $fecha->format('d/m/Y h:i a') }}" @endif>
                                                {{ $fecha?->diffForHumans() ?? 'Sin fecha' }}
                                            </span>
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div class="h-full rounded-xl border border-zinc-200/80 bg-white shadow-card dark:border-white/[0.06] dark:bg-zinc-900">
                    <div class="border-b border-zinc-200/80 px-5 py-4 dark:border-white/[0.06]">
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Alertas operativas</h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Puntos que necesitan atención en el proceso</p>
                    </div>

                    <div class="p-5">

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
