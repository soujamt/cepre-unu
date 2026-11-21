import { usePage } from '@inertiajs/react';
import { ArrowUpRight, Ban, CalendarDays, Check, ChevronRight, CircleCheck, CircleDollarSign, CircleX, ClipboardList, Clock, Minus, TrendingUp, Users } from '@keyline-icons/react';
import type { ReactNode } from 'react';
import { Aviso } from '@/components/aviso';
import { Enlace } from '@/components/enlace';
import { InsigniaEstado } from '@/components/insignia-estado';
import { EstadoVacio } from '@/components/panel';
import { RecuadroIcono, Resumen, ResumenItem } from '@/components/resumen';
import type { Tono } from '@/components/resumen';
import { Badge } from '@/components/ui/badge';
import { usePermiso } from '@/hooks/use-permiso';
import { iconos } from '@/lib/iconos';
import type { NombreIcono } from '@/lib/iconos';
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

/** Color del punto de cada alerta: el texto queda neutro. */
const puntoAlerta: Record<Alerta['tono'], { color: string; etiqueta: string }> = {
    danger: { color: 'bg-red-500', etiqueta: 'Urgente' },
    warning: { color: 'bg-amber-500', etiqueta: 'Atender' },
    info: { color: 'bg-index-500', etiqueta: 'Seguimiento' },
};

const accesosRapidos: { titulo: string; descripcion: string; icono: NombreIcono; tono: Tono; href: string; permiso: string }[] = [
    { titulo: 'Inscripciones', descripcion: 'Registro y seguimiento de postulantes', icono: 'inscripciones', tono: 'info', href: inscripciones.url(), permiso: 'inscripcion_indice' },
    { titulo: 'Pagos', descripcion: 'Pagos registrados por proceso', icono: 'pagos', tono: 'exito', href: pagos.url(), permiso: 'pago_indice' },
    { titulo: 'Validar pagos', descripcion: 'Aprobar pagos bancarios importados', icono: 'validarPagos', tono: 'aviso', href: validarPagos.url(), permiso: 'pago_crear' },
    { titulo: 'Reportes', descripcion: 'Listado de inscritos en formato CEPRE', icono: 'reportes', tono: 'neutro', href: reporteInscripciones.url(), permiso: 'reporte_inscripcion_indice' },
    { titulo: 'Procesos', descripcion: 'Procesos, oferta, aulas y reglas de pago', icono: 'procesos', tono: 'info', href: procesos.url(), permiso: 'proceso_indice' },
    { titulo: 'Usuarios', descripcion: 'Usuarios, roles y permisos', icono: 'usuarios', tono: 'neutro', href: usuarios.url(), permiso: 'usuario_indice' },
];

function Tarjeta({ titulo, descripcion, accion, children, className }: { titulo: string; descripcion?: string; accion?: ReactNode; children: ReactNode; className?: string }) {
    return (
        <section className={cn('flex flex-col rounded-2xl border border-zinc-200/80 bg-white shadow-card dark:border-white/[0.07] dark:bg-white/[0.02] dark:shadow-none', className)}>
            <header className="flex items-start justify-between gap-3 px-5 pt-5 pb-4">
                <div className="min-w-0">
                    <h2 className="text-[15px] font-semibold text-zinc-900 dark:text-white">{titulo}</h2>
                    {descripcion && <p className="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">{descripcion}</p>}
                </div>
                {accion}
            </header>
            {children}
        </section>
    );
}

export default function Inicio({ proceso, estadisticas, actividadHoy, alertas, recientes }: Props) {
    const { user } = usePage().props.auth;
    const puede = usePermiso();
    const puedeVerInscripciones = puede('inscripcion_indice');
    const accesos = accesosRapidos.filter((acceso) => puede(acceso.permiso));

    return (
        <div className="space-y-8">
            <div className="flex flex-col gap-4 rounded-2xl border border-zinc-200/80 bg-linear-to-br from-white via-white to-index-50/60 p-5 shadow-card sm:flex-row sm:items-center sm:justify-between sm:p-6 dark:border-white/[0.07] dark:from-white/[0.03] dark:via-white/[0.02] dark:to-index-500/[0.06] dark:shadow-none">
                <div className="space-y-1">
                    <p className="text-sm text-zinc-500 dark:text-zinc-400">Bienvenido, {user?.nombre ?? 'usuario'}</p>
                    {proceso ? (
                        <>
                            <h2 className="flex flex-wrap items-center gap-2 text-lg font-semibold tracking-tight text-zinc-900 dark:text-white">
                                Proceso {proceso.nombre}
                                <Badge variant="exito">En curso</Badge>
                            </h2>
                            <p className="flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400">
                                <CalendarDays className="size-4 text-zinc-400" />
                                {proceso.apertura ?? '—'} al {proceso.cierre ?? '—'}
                            </p>
                        </>
                    ) : (
                        <h2 className="text-lg font-semibold tracking-tight text-zinc-900 dark:text-white">No hay un proceso habilitado</h2>
                    )}
                </div>

                <div className="grid grid-cols-2 gap-2 sm:w-auto">
                    {[
                        { etiqueta: 'Inscripciones hoy', valor: actividadHoy.inscripciones },
                        { etiqueta: 'Pagos validados hoy', valor: actividadHoy.pagosValidados },
                    ].map(({ etiqueta, valor }) => (
                        <div key={etiqueta} className="min-w-36 rounded-xl border border-zinc-200/70 bg-white/80 px-4 py-3 dark:border-white/[0.07] dark:bg-white/[0.03]">
                            <p className="text-xs text-zinc-500 dark:text-zinc-400">{etiqueta}</p>
                            <p className="mt-1 flex items-center gap-1.5 text-xl font-semibold text-zinc-900 tabular-nums dark:text-white">
                                {valor}
                                {valor > 0 ? <TrendingUp className="size-4 text-green-500" /> : <Minus className="size-4 text-zinc-300 dark:text-zinc-600" />}
                            </p>
                        </div>
                    ))}
                </div>
            </div>

            {proceso && estadisticas ? (
                <>
                    <section className="space-y-4">
                        <div>
                            <h2 className="text-[15px] font-semibold text-zinc-900 dark:text-white">Resumen del proceso</h2>
                            <p className="text-sm text-zinc-500 dark:text-zinc-400">Estado de las inscripciones de {proceso.nombre}</p>
                        </div>
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
                    </section>

                    <div className="grid gap-5 xl:grid-cols-[minmax(0,1.5fr)_minmax(20rem,1fr)]">
                        <Tarjeta
                            titulo="Últimas inscripciones"
                            descripcion={`Registros más recientes de ${proceso.nombre}`}
                            accion={
                                puedeVerInscripciones && recientes.length > 0 ? (
                                    <Enlace
                                        href={inscripciones.url()}
                                        className="inline-flex shrink-0 items-center gap-1 rounded-lg px-2 py-1 text-sm font-medium text-index-600 transition-colors hover:bg-index-50 dark:text-index-300 dark:hover:bg-index-500/10"
                                    >
                                        Ver todas
                                        <ChevronRight className="size-4" />
                                    </Enlace>
                                ) : undefined
                            }
                        >
                            {recientes.length === 0 ? (
                                <EstadoVacio icono={ClipboardList} titulo="Aún no hay inscripciones en este proceso." />
                            ) : (
                                <ul className="divide-y divide-zinc-100 border-t border-zinc-100 dark:divide-white/[0.05] dark:border-white/[0.05]">
                                    {recientes.map((inscripcion) => {
                                        const contenido = (
                                            <>
                                                <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-xs font-semibold text-zinc-600 ring-1 ring-zinc-200/70 dark:bg-white/[0.06] dark:text-zinc-300 dark:ring-white/10">
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
                                                    <Enlace href={detalle.url(inscripcion.id)} className="flex items-center gap-3 px-5 py-3 transition-colors last:rounded-b-2xl hover:bg-zinc-50/80 dark:hover:bg-white/[0.02]">
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
                        </Tarjeta>

                        <Tarjeta titulo="Para atender" descripcion="Puntos del proceso que necesitan revisión">
                            <div className="px-5 pb-5">
                                {alertas.length === 0 ? (
                                    <Aviso tono="exito" titulo="Todo en orden">
                                        No hay pendientes críticos en el proceso actual.
                                    </Aviso>
                                ) : (
                                    <ul className="space-y-1">
                                        {alertas.map((alerta) => (
                                            <li key={alerta.titulo} className="flex items-start gap-3 rounded-xl px-3 py-2.5 transition-colors hover:bg-zinc-50 dark:hover:bg-white/[0.02]">
                                                <span className={cn('mt-1.5 size-2 shrink-0 rounded-full ring-4 ring-zinc-100 dark:ring-white/[0.04]', puntoAlerta[alerta.tono].color)} />
                                                <div className="min-w-0 flex-1">
                                                    <p className="text-sm font-medium text-zinc-800 dark:text-zinc-100">{alerta.titulo}</p>
                                                    <p className="text-xs text-pretty text-zinc-500 dark:text-zinc-400">{alerta.detalle}</p>
                                                </div>
                                                <span className="shrink-0 pt-0.5 text-[11px] font-medium text-zinc-400 dark:text-zinc-500">{puntoAlerta[alerta.tono].etiqueta}</span>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                        </Tarjeta>
                    </div>
                </>
            ) : (
                <Aviso tono="aviso" titulo="No hay ningún proceso habilitado">
                    Cuando exista un proceso vigente, aquí verás el resumen de inscripciones, pagos y pendientes.
                </Aviso>
            )}

            {accesos.length > 0 && (
                <section className="space-y-4">
                    <div>
                        <h2 className="text-[15px] font-semibold text-zinc-900 dark:text-white">Accesos rápidos</h2>
                        <p className="text-sm text-zinc-500 dark:text-zinc-400">Las pantallas que más se usan</p>
                    </div>
                    <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        {accesos.map(({ titulo, descripcion, icono, tono, href }) => (
                            <Enlace
                                key={titulo}
                                href={href}
                                className="group flex min-w-0 items-center gap-3.5 rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-card transition-[border-color,box-shadow] hover:border-zinc-300 hover:shadow-card-hover dark:border-white/[0.07] dark:bg-white/[0.02] dark:shadow-none dark:hover:border-white/15"
                            >
                                <RecuadroIcono icono={iconos[icono].relleno} tono={tono} />
                                <div className="min-w-0 flex-1">
                                    <p className="text-sm font-medium text-zinc-900 dark:text-white">{titulo}</p>
                                    <p className="truncate text-xs text-zinc-500 dark:text-zinc-400">{descripcion}</p>
                                </div>
                                <ArrowUpRight className="size-4 shrink-0 text-zinc-300 transition-[color,transform] group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:text-zinc-500 dark:text-zinc-600 dark:group-hover:text-zinc-400" />
                            </Enlace>
                        ))}
                    </div>
                </section>
            )}
        </div>
    );
}

Inicio.layout = {
    titulo: 'Inicio',
    subtitulo: 'Resumen del proceso de admisión, la actividad del día y los pendientes.',
    icono: 'inicio',
    migas: [{ titulo: 'Inicio' }],
};
