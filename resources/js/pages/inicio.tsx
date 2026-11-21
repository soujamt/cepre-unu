import { usePage } from '@inertiajs/react';
import {
    Alert,
    ArrowRight,
    Ban,
    ChartBar,
    Check,
    ChevronRight,
    CircleAlert,
    CircleCheck,
    CircleDollarSign,
    CircleX,
    ClipboardList,
    Clock,
    Info,
    Lock,
    Minus,
    OctagonAlert,
    Receipt,
    Settings,
    ShieldCheck,
    TrendingUp,
    Users,
} from '@keyline-icons/react';
import { Enlace } from '@/components/enlace';
import { InsigniaEstado } from '@/components/insignia-estado';
import { Resumen, ResumenItem } from '@/components/resumen';
import { usePermiso } from '@/hooks/use-permiso';
import type { Icono } from '@/lib/iconos';
import { cn } from '@/lib/utils';
import { detalle, index as inscripciones } from '@/routes/inscripciones';
import { index as pagos, validar as validarPagos } from '@/routes/pagos';
import { index as procesos } from '@/routes/procesos';
import { index as reporteInscripciones } from '@/routes/reportes/inscripciones';
import { index as usuarios } from '@/routes/usuarios';

interface Estadisticas {
    total: number;
    activas: number;
    pendientes: number;
    anuladas: number;
    pagoPendiente: number;
}

interface Alerta {
    titulo: string;
    detalle: string;
    tono: 'danger' | 'warning' | 'info';
}

interface InscripcionReciente {
    id: string;
    codigo: string;
    nombre: string;
    iniciales: string;
    carrera: string | null;
    estado: string;
    fecha: string | null;
    hace: string | null;
}

interface Props {
    proceso: { nombre: string; apertura: string | null; cierre: string | null } | null;
    estadisticas: Estadisticas | null;
    actividadHoy: { inscripciones: number; pagosValidados: number };
    alertas: Alerta[];
    recientes: InscripcionReciente[];
}

const estilosAlerta: Record<Alerta['tono'], { tarjeta: string; insignia: string; etiqueta: string; icono: Icono }> = {
    danger: {
        tarjeta: 'border-red-200 bg-red-50/80 text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-300',
        insignia: 'border-red-200 bg-red-100 text-red-700 dark:border-red-500/20 dark:bg-red-500/15 dark:text-red-300',
        etiqueta: 'Urgente',
        icono: OctagonAlert,
    },
    warning: {
        tarjeta: 'border-amber-200 bg-amber-50/80 text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300',
        insignia: 'border-amber-200 bg-amber-100 text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/15 dark:text-amber-300',
        etiqueta: 'Atender',
        icono: CircleAlert,
    },
    info: {
        tarjeta: 'border-sky-200 bg-sky-50/80 text-sky-700 dark:border-sky-500/20 dark:bg-sky-500/10 dark:text-sky-300',
        insignia: 'border-sky-200 bg-sky-100 text-sky-700 dark:border-sky-500/20 dark:bg-sky-500/15 dark:text-sky-300',
        etiqueta: 'Seguimiento',
        icono: Info,
    },
};

const accesosRapidos: { titulo: string; descripcion: string; icono: Icono; href: string; color: string; permiso: string }[] = [
    {
        titulo: 'Inscripciones',
        descripcion: 'Gestiona el registro de postulantes al CEPRE',
        icono: ClipboardList,
        href: inscripciones.url(),
        color: 'bg-zinc-100 text-zinc-600 dark:bg-white/10 dark:text-zinc-300',
        permiso: 'inscripcion_indice',
    },
    {
        titulo: 'Pagos',
        descripcion: 'Consulta los pagos registrados por proceso',
        icono: Receipt,
        href: pagos.url(),
        color: 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400',
        permiso: 'pago_indice',
    },
    {
        titulo: 'Validar pagos',
        descripcion: 'Verifica y aprueba pagos bancarios importados',
        icono: ShieldCheck,
        href: validarPagos.url(),
        color: 'bg-sky-100 text-sky-600 dark:bg-sky-500/10 dark:text-sky-400',
        permiso: 'pago_crear',
    },
    {
        titulo: 'Reportes',
        descripcion: 'Exporta el listado de inscritos en formato CEPRE',
        icono: ChartBar,
        href: reporteInscripciones.url(),
        color: 'bg-violet-100 text-violet-600 dark:bg-violet-500/10 dark:text-violet-400',
        permiso: 'reporte_inscripcion_indice',
    },
    {
        titulo: 'Conf. Académica',
        descripcion: 'Administra procesos, ofertas, aulas y reglas de pago',
        icono: Settings,
        href: procesos.url(),
        color: 'bg-amber-100 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400',
        permiso: 'proceso_indice',
    },
    {
        titulo: 'Seguridad',
        descripcion: 'Gestiona usuarios, roles y permisos del sistema',
        icono: Lock,
        href: usuarios.url(),
        color: 'bg-red-100 text-red-600 dark:bg-red-500/10 dark:text-red-400',
        permiso: 'usuario_indice',
    },
];

function Encabezado({ etiqueta, titulo }: { etiqueta: string; titulo?: string }) {
    return (
        <div>
            <p className="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">{etiqueta}</p>
            {titulo && <h3 className="mt-1 text-base font-semibold text-zinc-900 dark:text-white">{titulo}</h3>}
        </div>
    );
}

export default function Inicio({ proceso, estadisticas, actividadHoy, alertas, recientes }: Props) {
    const { user } = usePage().props.auth;
    const puede = usePermiso();
    const puedeVerInscripciones = puede('inscripcion_indice');
    const accesos = accesosRapidos.filter((acceso) => puede(acceso.permiso));
    const alertasPorTono = alertas.reduce<Record<string, number>>((conteo, alerta) => ({ ...conteo, [alerta.tono]: (conteo[alerta.tono] ?? 0) + 1 }), {});

    return (
        <div className="space-y-6">
            <div className="overflow-hidden rounded-xl border border-zinc-200/80 bg-white shadow-card dark:border-white/[0.06] dark:bg-zinc-900">
                <div className="grid gap-6 px-6 py-6 lg:grid-cols-[minmax(0,1.5fr)_minmax(19rem,0.95fr)] lg:px-7">
                    <div className="space-y-4">
                        <div className="space-y-2">
                            <p className="text-xs font-semibold tracking-[0.22em] text-zinc-500 uppercase dark:text-zinc-400">Vista general</p>
                            <h2 className="max-w-2xl text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">Bienvenido, {user?.nombre ?? 'Usuario'}</h2>
                            <p className="max-w-2xl text-sm text-zinc-500 dark:text-zinc-400">
                                Panel operativo del proceso de admisión. Aquí puedes revisar el estado del proceso vigente, la actividad del día y los puntos que necesitan atención.
                            </p>
                        </div>

                        <p className="pt-1 text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Actividad de hoy</p>

                        <Resumen columnas={2}>
                            <ResumenItem
                                etiqueta="Inscripciones"
                                valor={actividadHoy.inscripciones}
                                icono={Users}
                                tono="info"
                                detalle={actividadHoy.inscripciones > 0 ? 'Con movimiento' : 'Sin altas'}
                                iconoDetalle={actividadHoy.inscripciones > 0 ? TrendingUp : Minus}
                                title="Registros creados en la jornada actual"
                                destacado
                            />
                            <ResumenItem
                                etiqueta="Pagos validados"
                                valor={actividadHoy.pagosValidados}
                                icono={ShieldCheck}
                                tono="exito"
                                detalle={actividadHoy.pagosValidados > 0 ? 'Validando bien' : 'Sin validaciones'}
                                iconoDetalle={actividadHoy.pagosValidados > 0 ? TrendingUp : Minus}
                                title="Confirmaciones bancarias aprobadas"
                            />
                        </Resumen>
                    </div>

                    <div className="rounded-xl border border-zinc-200/80 bg-zinc-50/70 p-5 dark:border-white/[0.06] dark:bg-zinc-950/40">
                        {proceso ? (
                            <div className="space-y-4">
                                <div className="flex items-center justify-between gap-3">
                                    <div>
                                        <p className="text-xs font-semibold tracking-[0.18em] text-emerald-600 uppercase dark:text-emerald-400">Proceso vigente</p>
                                        <h3 className="mt-1 text-lg font-semibold text-zinc-900 dark:text-white">{proceso.nombre}</h3>
                                    </div>
                                    <span className="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
                                        En curso
                                    </span>
                                </div>

                                <div className="space-y-2 text-sm text-zinc-600 dark:text-zinc-300">
                                    <p>
                                        <span className="font-medium text-zinc-900 dark:text-white">Apertura:</span> {proceso.apertura ?? '—'}
                                    </p>
                                    <p>
                                        <span className="font-medium text-zinc-900 dark:text-white">Cierre:</span> {proceso.cierre ?? '—'}
                                    </p>
                                </div>

                                <div className="grid gap-3 sm:grid-cols-2">
                                    <div className="rounded-xl bg-white p-3 shadow-xs dark:bg-zinc-900/80">
                                        <p className="text-[11px] font-semibold tracking-[0.16em] text-zinc-500 uppercase dark:text-zinc-400">Estado</p>
                                        <p className="mt-1 text-sm font-medium text-zinc-900 dark:text-white">Inscripción operativa</p>
                                    </div>
                                    <div className="rounded-xl bg-white p-3 shadow-xs dark:bg-zinc-900/80">
                                        <p className="text-[11px] font-semibold tracking-[0.16em] text-zinc-500 uppercase dark:text-zinc-400">Pendientes</p>
                                        <p className="mt-1 text-sm font-medium text-zinc-900 dark:text-white">{estadisticas?.pendientes ?? 0} registros por completar</p>
                                    </div>
                                </div>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                <p className="text-xs font-semibold tracking-[0.18em] text-amber-600 uppercase dark:text-amber-400">Disponibilidad</p>
                                <h3 className="text-lg font-semibold text-zinc-900 dark:text-white">No hay un proceso habilitado</h3>
                                <p className="text-sm text-zinc-500 dark:text-zinc-400">
                                    Hasta que exista un proceso vigente con configuración activa, el panel mostrará solo información general.
                                </p>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {proceso && estadisticas ? (
                <>
                    <div className="space-y-3">
                        <Encabezado etiqueta="Resumen del proceso" titulo={`Estado de las inscripciones de ${proceso.nombre}`} />
                        <Resumen>
                            <ResumenItem
                                etiqueta="Total inscritas"
                                valor={estadisticas.total}
                                icono={Users}
                                tono="info"
                                iconoDetalle={CircleCheck}
                                detalle={`${estadisticas.activas} activas · ${estadisticas.pendientes} pendientes`}
                            />
                            <ResumenItem etiqueta="Activas" valor={estadisticas.activas} icono={CircleCheck} tono="exito" iconoDetalle={Check} detalle="Con pago validado" />
                            <ResumenItem
                                etiqueta="Pago pendiente"
                                valor={estadisticas.pagoPendiente}
                                icono={CircleDollarSign}
                                tono={estadisticas.pagoPendiente > 0 ? 'aviso' : 'exito'}
                                iconoDetalle={estadisticas.pagoPendiente > 0 ? Clock : Check}
                                detalle={estadisticas.pagoPendiente > 0 ? 'Con saldo por cobrar' : 'Pagos al día'}
                            />
                            <ResumenItem
                                etiqueta="Anuladas"
                                valor={estadisticas.anuladas}
                                icono={CircleX}
                                tono={estadisticas.anuladas > 0 ? 'peligro' : 'neutro'}
                                iconoDetalle={Ban}
                                detalle="Fuera del flujo activo"
                            />
                        </Resumen>
                    </div>

                    <div className="grid gap-5 xl:grid-cols-[minmax(0,1.35fr)_minmax(21rem,0.9fr)]">
                        <div className="flex h-full flex-col rounded-xl border border-zinc-200/80 bg-white shadow-card dark:border-white/[0.06] dark:bg-zinc-900">
                            <div className="flex items-center justify-between gap-3 border-b border-zinc-200/80 px-5 py-4 dark:border-white/[0.06]">
                                <div>
                                    <h3 className="text-sm font-semibold text-zinc-900 dark:text-white">Últimas inscripciones</h3>
                                    <p className="text-xs text-zinc-500 dark:text-zinc-400">Registros más recientes del proceso {proceso.nombre}</p>
                                </div>
                                {puedeVerInscripciones && recientes.length > 0 && (
                                    <Enlace
                                        href={inscripciones.url()}
                                        className="inline-flex shrink-0 items-center gap-1 text-sm font-medium text-index-600 hover:text-index-700 dark:text-index-300 dark:hover:text-index-200"
                                    >
                                        Ver todas
                                        <ChevronRight className="size-4" />
                                    </Enlace>
                                )}
                            </div>

                            {recientes.length === 0 ? (
                                <div className="flex flex-1 flex-col items-center justify-center gap-2 px-5 py-10 text-center">
                                    <ClipboardList className="size-8 text-zinc-300 dark:text-zinc-600" />
                                    <p className="text-sm text-zinc-500 dark:text-zinc-400">Aún no hay inscripciones en este proceso.</p>
                                </div>
                            ) : (
                                <ul className="divide-y divide-zinc-200/80 dark:divide-white/[0.06]">
                                    {recientes.map((inscripcion) => {
                                        const contenido = (
                                            <>
                                                <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-xs font-semibold text-zinc-600 dark:bg-white/10 dark:text-zinc-300">
                                                    {inscripcion.iniciales || '?'}
                                                </span>
                                                <span className="min-w-0 flex-1">
                                                    <span className="block truncate text-sm font-medium text-zinc-900 dark:text-white">{inscripcion.nombre || 'Sin datos del postulante'}</span>
                                                    <span className="block truncate text-xs text-zinc-500 dark:text-zinc-400">
                                                        {inscripcion.carrera ?? 'Sin carrera'}
                                                        <span className="text-zinc-300 dark:text-zinc-600"> · </span>
                                                        <span className="font-mono">{inscripcion.codigo}</span>
                                                    </span>
                                                </span>
                                                <span className="flex shrink-0 flex-col items-end gap-1">
                                                    <InsigniaEstado estado={inscripcion.estado} />
                                                    <span className="text-xs text-zinc-400 tabular-nums dark:text-zinc-500" title={inscripcion.fecha ?? undefined}>
                                                        {inscripcion.hace ?? 'Sin fecha'}
                                                    </span>
                                                </span>
                                            </>
                                        );

                                        return (
                                            <li key={inscripcion.id}>
                                                {puedeVerInscripciones ? (
                                                    <Enlace href={detalle.url(inscripcion.id)} className="flex items-center gap-3 px-5 py-3 transition-colors hover:bg-zinc-50 dark:hover:bg-white/[0.03]">
                                                        {contenido}
                                                    </Enlace>
                                                ) : (
                                                    <div className="flex items-center gap-3 px-5 py-3">{contenido}</div>
                                                )}
                                            </li>
                                        );
                                    })}
                                </ul>
                            )}
                        </div>

                        <div className="h-full rounded-xl border border-zinc-200/80 bg-white shadow-card dark:border-white/[0.06] dark:bg-zinc-900">
                            <div className="border-b border-zinc-200/80 px-5 py-4 dark:border-white/[0.06]">
                                <h3 className="text-sm font-semibold text-zinc-900 dark:text-white">Alertas operativas</h3>
                                <p className="text-xs text-zinc-500 dark:text-zinc-400">Puntos que necesitan atención en el proceso</p>
                            </div>
                            <div className="p-5">
                                {alertas.length === 0 ? (
                                    <div className="rounded-xl border border-emerald-200 bg-emerald-50/80 p-4 text-sm text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
                                        No hay alertas críticas en este momento para el proceso actual.
                                    </div>
                                ) : (
                                    <>
                                        <div className="mb-4 flex flex-wrap gap-2">
                                            {(alertasPorTono.warning ?? 0) > 0 && (
                                                <span className="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
                                                    {alertasPorTono.warning} por atender
                                                </span>
                                            )}
                                            {(alertasPorTono.danger ?? 0) > 0 && (
                                                <span className="rounded-full border border-red-200 bg-red-50 px-3 py-1 text-xs font-semibold text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-300">
                                                    {alertasPorTono.danger} urgente{alertasPorTono.danger > 1 ? 's' : ''}
                                                </span>
                                            )}
                                        </div>
                                        <div className="space-y-3">
                                            {alertas.map((alerta) => {
                                                const estilo = estilosAlerta[alerta.tono];
                                                const IconoAlerta = estilo.icono;

                                                return (
                                                    <div key={alerta.titulo} className={cn('rounded-xl border p-4', estilo.tarjeta)}>
                                                        <div className="flex items-start justify-between gap-3">
                                                            <div className="flex min-w-0 items-start gap-3">
                                                                <div className="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-xl bg-white/70 dark:bg-black/10">
                                                                    <IconoAlerta className="size-5" />
                                                                </div>
                                                                <div className="min-w-0">
                                                                    <p className="text-sm font-semibold">{alerta.titulo}</p>
                                                                    <p className="mt-1 text-xs opacity-90">{alerta.detalle}</p>
                                                                </div>
                                                            </div>
                                                            <span className={cn('rounded-full border px-2.5 py-1 text-[11px] font-semibold whitespace-nowrap', estilo.insignia)}>{estilo.etiqueta}</span>
                                                        </div>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    </>
                                )}
                            </div>
                        </div>
                    </div>
                </>
            ) : (
                <div className="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-500/20 dark:bg-amber-500/5">
                    <div className="flex items-center gap-3">
                        <Alert className="size-5 text-amber-500 dark:text-amber-400" />
                        <p className="text-sm font-medium text-amber-700 dark:text-amber-400">No hay ningún proceso habilitado en este momento.</p>
                    </div>
                </div>
            )}

            {accesos.length > 0 && (
                <div className="rounded-xl border border-zinc-200/80 bg-white p-5 shadow-card dark:border-white/[0.06] dark:bg-zinc-900">
                    <div className="mb-4">
                        <Encabezado etiqueta="Accesos rápidos" titulo="Módulos más usados" />
                    </div>
                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        {accesos.map(({ titulo, descripcion, icono: IconoAcceso, href, color }) => (
                            <Enlace
                                key={titulo}
                                href={href}
                                className="group flex min-w-0 items-center gap-4 rounded-xl border border-zinc-200/80 bg-white p-4 shadow-card transition hover:border-zinc-300 hover:shadow-card-hover dark:border-white/[0.06] dark:bg-zinc-900 dark:hover:border-white/20"
                            >
                                <div className={cn('flex size-10 shrink-0 items-center justify-center rounded-xl', color)}>
                                    <IconoAcceso className="size-5" />
                                </div>
                                <div className="min-w-0 flex-1">
                                    <p className="text-sm font-semibold text-zinc-900 dark:text-white">{titulo}</p>
                                    <p className="truncate text-xs text-zinc-500 dark:text-zinc-400">{descripcion}</p>
                                </div>
                                <ArrowRight className="size-4 shrink-0 text-zinc-300 transition group-hover:text-zinc-400 dark:text-zinc-600 dark:group-hover:text-zinc-500" />
                            </Enlace>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}

Inicio.layout = {
    titulo: 'Inicio',
    icono: 'inicio',
    migas: [{ titulo: 'Inicio' }],
};
