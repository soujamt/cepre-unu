import { usePage } from '@inertiajs/react';
import { ArrowUpRight, CalendarDays, ChevronRight, CircleCheck, CircleDollarSign, ClipboardList, Clock, ShieldCheck, TrendingUp, Wallet } from '@keyline-icons/react/fill';
import type { ReactNode } from 'react';
import { Aviso } from '@/components/aviso';
import { AvatarPostulante } from '@/components/avatar-postulante';
import { Enlace } from '@/components/enlace';
import { BarraSegmentada, BarrasApiladas, ColumnasPorDia, Dona, ListaOcupacion, Medidor, TarjetaGrafico, colorEstado, colorGrafico, numero, soles } from '@/components/graficos';
import type { Parte, SerieApilada } from '@/components/graficos';
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

interface Conteo {
    clave: string;
    nombre: string;
    total: number;
}

interface Grupo {
    nombre: string;
    total: number;
    activas: number;
    pendientes: number;
    anuladas: number;
}

interface CifrasResumen {
    total: number;
    activas: number;
    pendientes: number;
    anuladas: number;
    enRevision: number;
    conSaldo: number;
    pagoParcial: number;
    desdePortal: number;
}

interface Graficos {
    recaudacion: { esperado: number; recaudado: number; porcentaje: number };
    porDia: { fecha: string; etiqueta: string; total: number }[];
    porEstado: Conteo[];
    porEstadoPago: Conteo[];
    porCarrera: Grupo[];
    porTurno: Grupo[];
    porSede: Grupo[];
    ocupacion: { nombre: string; capacidad: number; asignadas: number }[];
}

interface Alerta {
    titulo: string;
    detalle: string;
    tono: 'danger' | 'warning' | 'info';
    cantidad: number;
    enlace: string;
}

interface InscripcionReciente {
    id: string;
    codigo: string;
    nombre: string;
    iniciales: string;
    foto: string | null;
    carrera: string | null;
    estado: string;
    fecha: string | null;
    hace: string | null;
}

interface Props {
    proceso: { nombre: string; apertura: string | null; cierre: string | null; avance: number | null; diasRestantes: number | null } | null;
    resumen: CifrasResumen | null;
    graficos: Graficos | null;
    actividadHoy: { inscripciones: number; pagosValidados: number };
    alertas: Alerta[];
    recientes: InscripcionReciente[];
}

const estiloAlerta: Record<Alerta['tono'], { color: string; etiqueta: string }> = {
    danger: { color: 'bg-red-500', etiqueta: 'Urgente' },
    warning: { color: 'bg-amber-500', etiqueta: 'Atender' },
    info: { color: 'bg-index-500', etiqueta: 'Revisar' },
};

const accesosRapidos: { titulo: string; descripcion: string; icono: NombreIcono; tono: Tono; href: string; permiso: string }[] = [
    { titulo: 'Inscripciones', descripcion: 'Registro y seguimiento de postulantes', icono: 'inscripciones', tono: 'info', href: inscripciones.url(), permiso: 'inscripcion_indice' },
    { titulo: 'Pagos', descripcion: 'Pagos registrados por proceso', icono: 'pagos', tono: 'exito', href: pagos.url(), permiso: 'pago_indice' },
    { titulo: 'Validar pagos', descripcion: 'Asociar pagos del banco a inscripciones', icono: 'validarPagos', tono: 'aviso', href: validarPagos.url(), permiso: 'pago_crear' },
    { titulo: 'Reportes', descripcion: 'Gráficos e inscritos en formato CEPRE', icono: 'reportes', tono: 'neutro', href: reporteInscripciones.url(), permiso: 'reporte_inscripcion_indice' },
    { titulo: 'Procesos', descripcion: 'Procesos, oferta, aulas y reglas de pago', icono: 'procesos', tono: 'info', href: procesos.url(), permiso: 'proceso_indice' },
    { titulo: 'Usuarios', descripcion: 'Usuarios, roles y permisos', icono: 'usuarios', tono: 'neutro', href: usuarios.url(), permiso: 'usuario_indice' },
];

const nombreEstado: Record<string, string> = { ACTIVO: 'Activas', PENDIENTE: 'Pendientes', ANULADO: 'Anuladas' };

const seriesEstado: SerieApilada<Grupo>[] = [
    { clave: 'activas', nombre: 'Activas', color: colorGrafico.activo },
    { clave: 'pendientes', nombre: 'Pendientes', color: colorGrafico.pendiente },
    { clave: 'anuladas', nombre: 'Anuladas', color: colorGrafico.anulado },
];

const coloresSerie = [colorGrafico.serie1, colorGrafico.serie2, colorGrafico.serie3, colorGrafico.neutro];

const titulo = (texto: string): string => texto.charAt(0) + texto.slice(1).toLowerCase();

/** Reparte grupos (turnos, sedes) en partes de una barra con los colores de serie en orden fijo. */
const partesDeGrupos = (grupos: Grupo[]): Parte[] =>
    grupos.map((grupo, indice) => ({ clave: grupo.nombre, nombre: titulo(grupo.nombre), total: grupo.total, color: coloresSerie[Math.min(indice, coloresSerie.length - 1)] }));

function EnlaceVer({ href, children }: { href: string; children: ReactNode }) {
    return (
        <Enlace href={href} className="inline-flex shrink-0 items-center gap-1 rounded-lg px-2 py-1 text-sm font-medium text-index-600 transition-colors hover:bg-index-50 dark:text-index-300 dark:hover:bg-index-500/10">
            {children}
            <ChevronRight className="size-4" />
        </Enlace>
    );
}

/**
 * Cabecera del proceso: nombre, fechas, avance del periodo y la cifra principal.
 */
function CabeceraProceso({ proceso, resumen, actividadHoy, nombreUsuario }: { proceso: NonNullable<Props['proceso']>; resumen: CifrasResumen; actividadHoy: Props['actividadHoy']; nombreUsuario: string }) {
    return (
        <section className="relative isolate overflow-hidden rounded-3xl bg-menu p-6 text-white sm:p-8 dark">
            <div aria-hidden className="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(55%_120%_at_100%_0%,rgb(41_111_240/0.45),transparent_60%),radial-gradient(40%_90%_at_0%_100%,rgb(27_175_122/0.18),transparent_70%)]" />
            <div className="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
                <div className="min-w-0 space-y-4">
                    <p className="text-sm text-zinc-400">Hola, {nombreUsuario}</p>
                    <div className="space-y-2">
                        <h2 className="flex flex-wrap items-center gap-2.5 text-2xl font-semibold tracking-tight sm:text-3xl">
                            Proceso {proceso.nombre}
                            <Badge variant="exito" className="rounded-full">
                                En curso
                            </Badge>
                        </h2>
                        <p className="flex items-center gap-1.5 text-sm text-zinc-400">
                            <CalendarDays className="size-4" />
                            {proceso.apertura ?? '—'} al {proceso.cierre ?? '—'}
                        </p>
                    </div>
                    {proceso.avance !== null && (
                        <div className="max-w-md space-y-2">
                            <div className="flex justify-between text-xs text-zinc-400">
                                <span>Avance del periodo de inscripción</span>
                                <span className="font-medium text-white tabular-nums">{proceso.avance}%</span>
                            </div>
                            <div className="h-2 overflow-hidden rounded-full bg-white/10">
                                <div className="h-full rounded-full bg-linear-to-r from-index-400 to-index-500" style={{ width: `${proceso.avance}%` }} />
                            </div>
                            <p className="text-xs text-zinc-400">
                                {proceso.diasRestantes === 0 ? 'Último día de inscripción.' : `Faltan ${proceso.diasRestantes} día(s) para el cierre.`}
                            </p>
                        </div>
                    )}
                </div>

                <div className="flex flex-wrap items-end gap-6 sm:gap-10">
                    <div>
                        <p className="text-sm text-zinc-400">Inscritos en el proceso</p>
                        <p className="text-6xl font-semibold tracking-tight">{numero(resumen.total)}</p>
                    </div>
                    <div className="grid grid-cols-2 gap-2">
                        {[
                            { etiqueta: 'Inscripciones hoy', valor: actividadHoy.inscripciones },
                            { etiqueta: 'Pagos validados hoy', valor: actividadHoy.pagosValidados },
                        ].map(({ etiqueta, valor }) => (
                            <div key={etiqueta} className="min-w-32 rounded-2xl border border-white/10 bg-white/[0.05] px-4 py-3 backdrop-blur">
                                <p className="text-xs text-zinc-400">{etiqueta}</p>
                                <p className="mt-1 flex items-center gap-1.5 text-2xl font-semibold tabular-nums">
                                    {numero(valor)}
                                    {valor > 0 && <TrendingUp className="size-4 text-emerald-400" />}
                                </p>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </section>
    );
}

function UltimasInscripciones({ recientes, puedeVer }: { recientes: InscripcionReciente[]; puedeVer: boolean }) {
    if (recientes.length === 0) {
        return <EstadoVacio icono={ClipboardList} titulo="Aún no hay inscripciones en este proceso." />;
    }

    return (
        <ul className="-mx-5 -mb-5 divide-y divide-zinc-100 border-t border-zinc-100 dark:divide-white/[0.05] dark:border-white/[0.05]">
            {recientes.map((inscripcion) => {
                const contenido = (
                    <>
                        <AvatarPostulante foto={inscripcion.foto} iniciales={inscripcion.iniciales} nombre={inscripcion.nombre} />
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
                            <span className="text-xs text-zinc-400 dark:text-zinc-500" title={inscripcion.fecha ?? undefined}>
                                {inscripcion.hace ?? 'Sin fecha'}
                            </span>
                        </span>
                    </>
                );

                return (
                    <li key={inscripcion.id}>
                        {puedeVer ? (
                            <Enlace href={detalle.url(inscripcion.id)} className="flex items-center gap-3 px-5 py-3 transition-colors hover:bg-zinc-50/80 dark:hover:bg-white/[0.02]">
                                {contenido}
                            </Enlace>
                        ) : (
                            <div className="flex items-center gap-3 px-5 py-3">{contenido}</div>
                        )}
                    </li>
                );
            })}
        </ul>
    );
}

function ParaAtender({ alertas }: { alertas: Alerta[] }) {
    if (alertas.length === 0) {
        return (
            <Aviso tono="exito" titulo="Todo en orden">
                No hay pendientes en el proceso actual.
            </Aviso>
        );
    }

    return (
        <ul className="space-y-2">
            {alertas.map((alerta) => (
                <li key={alerta.titulo}>
                    <Enlace
                        href={alerta.enlace}
                        className="group flex items-center gap-3.5 rounded-2xl border border-zinc-200/70 p-3.5 transition-colors hover:border-zinc-300 hover:bg-zinc-50/60 dark:border-white/[0.07] dark:hover:border-white/15 dark:hover:bg-white/[0.02]"
                    >
                        <span className="relative flex size-11 shrink-0 items-center justify-center rounded-xl bg-zinc-100 text-lg font-semibold text-zinc-900 tabular-nums dark:bg-white/[0.06] dark:text-white">
                            {numero(alerta.cantidad)}
                            <span className={cn('absolute -top-0.5 -right-0.5 size-2.5 rounded-full ring-2 ring-white dark:ring-zinc-900', estiloAlerta[alerta.tono].color)} />
                        </span>
                        <span className="min-w-0 flex-1">
                            <span className="flex items-center gap-2 text-sm font-medium text-zinc-900 dark:text-white">
                                {alerta.titulo}
                                <span className="text-[11px] font-medium text-zinc-400">{estiloAlerta[alerta.tono].etiqueta}</span>
                            </span>
                            <span className="block text-xs text-pretty text-zinc-500 dark:text-zinc-400">{alerta.detalle}</span>
                        </span>
                        <ChevronRight className="size-4 shrink-0 text-zinc-300 transition-transform group-hover:translate-x-0.5 dark:text-zinc-600" />
                    </Enlace>
                </li>
            ))}
        </ul>
    );
}

export default function Inicio({ proceso, resumen, graficos, actividadHoy, alertas, recientes }: Props) {
    const { user } = usePage().props.auth;
    const puede = usePermiso();
    const puedeVerInscripciones = puede('inscripcion_indice');
    const accesos = accesosRapidos.filter((acceso) => puede(acceso.permiso));
    const nombreUsuario = user?.nombre ?? 'usuario';

    return (
        <div className="space-y-6">
            {proceso && resumen && graficos ? (
                <>
                    <CabeceraProceso proceso={proceso} resumen={resumen} actividadHoy={actividadHoy} nombreUsuario={nombreUsuario} />

                    <Resumen>
                        <ResumenItem
                            etiqueta="Activas"
                            valor={numero(resumen.activas)}
                            icono={CircleCheck}
                            tono="exito"
                            detalle={`${resumen.total === 0 ? 0 : Math.round((resumen.activas / resumen.total) * 100)}% del total inscrito`}
                        />
                        <ResumenItem
                            etiqueta="En revisión"
                            valor={numero(resumen.enRevision)}
                            icono={ShieldCheck}
                            tono={resumen.enRevision > 0 ? 'peligro' : 'neutro'}
                            iconoDetalle={Clock}
                            detalle={`${numero(resumen.desdePortal)} inscritas desde el portal`}
                        />
                        <ResumenItem
                            etiqueta="Con saldo pendiente"
                            valor={numero(resumen.conSaldo)}
                            icono={CircleDollarSign}
                            tono={resumen.conSaldo > 0 ? 'aviso' : 'exito'}
                            detalle={resumen.conSaldo > 0 ? `${numero(resumen.pagoParcial)} con pago parcial` : 'Pagos al día'}
                        />
                        <ResumenItem
                            etiqueta="Recaudado"
                            valor={soles(graficos.recaudacion.recaudado)}
                            icono={Wallet}
                            tono="info"
                            detalle={`${graficos.recaudacion.porcentaje}% de ${soles(graficos.recaudacion.esperado)} esperados`}
                        >
                            <div className="pt-1.5">
                                <Medidor valor={graficos.recaudacion.recaudado} maximo={graficos.recaudacion.esperado} />
                            </div>
                        </ResumenItem>
                    </Resumen>

                    <div className="grid gap-5 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
                        <TarjetaGrafico
                            titulo="Inscripciones por día"
                            descripcion="Últimos 14 días; la columna resaltada es hoy."
                            tabla={{ columnas: ['Día', 'Inscripciones'], filas: graficos.porDia.map((dia) => [dia.etiqueta, dia.total]) }}
                        >
                            <ColumnasPorDia datos={graficos.porDia} />
                        </TarjetaGrafico>
                        <TarjetaGrafico
                            titulo="Estado de las inscripciones"
                            descripcion="Del proceso actual."
                            tabla={{ columnas: ['Estado', 'Inscripciones'], filas: graficos.porEstado.map((estado) => [nombreEstado[estado.clave] ?? estado.nombre, estado.total]) }}
                        >
                            <div className="flex flex-1 items-center">
                                <Dona
                                    etiquetaCentro="inscritos"
                                    partes={graficos.porEstado.map((estado) => ({ clave: estado.clave, nombre: nombreEstado[estado.clave] ?? estado.nombre, total: estado.total, color: colorEstado[estado.clave] }))}
                                />
                            </div>
                        </TarjetaGrafico>
                    </div>

                    <div className="grid gap-5 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
                        <TarjetaGrafico
                            titulo="Carreras con más inscritos"
                            descripcion={graficos.porCarrera.length > 8 ? `Las 8 con más inscritos de ${graficos.porCarrera.length} carreras.` : 'Inscritos por carrera y estado.'}
                            accion={puede('reporte_inscripcion_indice') ? <EnlaceVer href={reporteInscripciones.url()}>Reporte</EnlaceVer> : undefined}
                            tabla={{ columnas: ['Carrera', 'Activas', 'Pendientes', 'Anuladas', 'Total'], filas: graficos.porCarrera.map((grupo) => [grupo.nombre, grupo.activas, grupo.pendientes, grupo.anuladas, grupo.total]) }}
                        >
                            <BarrasApiladas filas={graficos.porCarrera} series={seriesEstado} maximoFilas={8} />
                        </TarjetaGrafico>
                        <div className="grid content-start gap-5">
                            <TarjetaGrafico
                                titulo="Estado del pago"
                                descripcion="Inscripciones no anuladas."
                                tabla={{ columnas: ['Estado', 'Inscripciones'], filas: graficos.porEstadoPago.map((estado) => [titulo(estado.nombre), estado.total]) }}
                            >
                                <BarraSegmentada partes={graficos.porEstadoPago.map((estado) => ({ clave: estado.clave, nombre: titulo(estado.nombre), total: estado.total, color: colorEstado[estado.clave] ?? colorGrafico.neutro }))} />
                            </TarjetaGrafico>
                            <TarjetaGrafico
                                titulo="Por turno y sede"
                                tabla={{ columnas: ['Grupo', 'Inscripciones'], filas: [...graficos.porTurno.map((grupo) => [`Turno ${titulo(grupo.nombre)}`, grupo.total]), ...graficos.porSede.map((grupo) => [titulo(grupo.nombre), grupo.total])] }}
                            >
                                <div className="space-y-5">
                                    <div className="space-y-2">
                                        <p className="text-xs font-medium text-zinc-500 dark:text-zinc-400">Turno</p>
                                        <BarraSegmentada partes={partesDeGrupos(graficos.porTurno)} />
                                    </div>
                                    <div className="space-y-2">
                                        <p className="text-xs font-medium text-zinc-500 dark:text-zinc-400">Sede</p>
                                        <BarraSegmentada partes={partesDeGrupos(graficos.porSede)} />
                                    </div>
                                </div>
                            </TarjetaGrafico>
                        </div>
                    </div>

                    <div className="grid gap-5 xl:grid-cols-3">
                        <TarjetaGrafico titulo="Para atender" descripcion="Lo que necesita revisión en el proceso.">
                            <ParaAtender alertas={alertas} />
                        </TarjetaGrafico>
                        <TarjetaGrafico titulo="Ocupación de aulas" descripcion="Inscripciones con aula frente a las vacantes por turno.">
                            <ListaOcupacion filas={graficos.ocupacion} />
                        </TarjetaGrafico>
                        <TarjetaGrafico titulo="Últimas inscripciones" accion={puedeVerInscripciones && recientes.length > 0 ? <EnlaceVer href={inscripciones.url()}>Ver todas</EnlaceVer> : undefined}>
                            <UltimasInscripciones recientes={recientes} puedeVer={puedeVerInscripciones} />
                        </TarjetaGrafico>
                    </div>
                </>
            ) : (
                <>
                    <section className="rounded-3xl border border-zinc-200/80 bg-white p-6 shadow-card dark:border-white/[0.07] dark:bg-white/[0.02] dark:shadow-none">
                        <p className="text-sm text-zinc-500 dark:text-zinc-400">Hola, {nombreUsuario}</p>
                        <h2 className="mt-1 text-xl font-semibold text-zinc-900 dark:text-white">No hay un proceso habilitado</h2>
                    </section>
                    <Aviso tono="aviso" titulo="No hay ningún proceso habilitado">
                        Cuando exista un proceso vigente, aquí verás el resumen de inscripciones, pagos, aulas y pendientes.
                    </Aviso>
                </>
            )}

            {accesos.length > 0 && (
                <section className="space-y-3">
                    <h2 className="text-[15px] font-semibold text-zinc-900 dark:text-white">Accesos rápidos</h2>
                    <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        {accesos.map(({ titulo: tituloAcceso, descripcion, icono, tono, href }) => (
                            <Enlace
                                key={tituloAcceso}
                                href={href}
                                className="group flex min-w-0 items-center gap-3.5 rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-card transition-[border-color,box-shadow] hover:border-zinc-300 hover:shadow-card-hover dark:border-white/[0.07] dark:bg-white/[0.02] dark:shadow-none dark:hover:border-white/15"
                            >
                                <RecuadroIcono icono={iconos[icono]} tono={tono} />
                                <div className="min-w-0 flex-1">
                                    <p className="text-sm font-medium text-zinc-900 dark:text-white">{tituloAcceso}</p>
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
    subtitulo: 'Cómo va el proceso de admisión: inscritos, pagos, aulas y pendientes.',
    icono: 'inicio',
    migas: [{ titulo: 'Inicio' }],
};
