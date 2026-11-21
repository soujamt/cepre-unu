import { Ban, CircleCheck, CircleDollarSign, Clock, Download, Users } from '@keyline-icons/react';
import { FiltroLista } from '@/components/filtro-lista';
import { Panel } from '@/components/panel';
import { Resumen, ResumenItem } from '@/components/resumen';
import { useFiltros } from '@/components/tabla-datos';
import { Button } from '@/components/ui/button';
import { usePermiso } from '@/hooks/use-permiso';
import { inicio } from '@/routes';
import { exportar, index } from '@/routes/reportes/inscripciones';
import type { Opcion } from '@/types';

type Filtros = { proceso: string; procesoElegido: string | null; modalidad: string; sede: string; turno: string; estado: string };

interface Grupo {
    nombre: string;
    total: number;
    activas?: number;
    pendientes?: number;
    anuladas?: number;
    pagoPendiente?: number;
}

interface PropsReporte {
    filtros: Filtros;
    resumen: { total: number; activas: number; pendientes: number; anuladas: number; pagoPendiente: number; pagoParcial: number };
    porCarrera: Grupo[];
    porModalidad: Grupo[];
    porTurno: Grupo[];
    opciones: { procesos: Opcion[]; modalidades: Opcion[]; sedes: Opcion[]; turnos: Opcion[]; estados: Opcion[] };
}

const TODOS = 'todos';

/**
 * Cuadro con una barra horizontal por grupo, proporcional al mayor total.
 */
function Cuadro({ titulo, grupos, columnas }: { titulo: string; grupos: Grupo[]; columnas: { clave: keyof Grupo; titulo: string }[] }) {
    const maximo = Math.max(1, ...grupos.map((grupo) => grupo.total));

    return (
        <Panel titulo={titulo} descripcion={`${grupos.length} grupo(s)`}>
            {grupos.length === 0 ? (
                <p className="text-sm text-zinc-500">Sin inscripciones con los filtros actuales.</p>
            ) : (
                <div className="space-y-3.5">
                    {grupos.map((grupo) => (
                        <div key={grupo.nombre} className="space-y-1.5">
                            <div className="flex items-baseline justify-between gap-3 text-sm">
                                <span className="min-w-0 truncate font-medium text-zinc-800 dark:text-zinc-100" title={grupo.nombre}>
                                    {grupo.nombre}
                                </span>
                                <span className="shrink-0 font-semibold text-zinc-900 tabular-nums dark:text-white">{grupo.total}</span>
                            </div>
                            <div className="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-white/[0.06]">
                                <div className="h-full rounded-full bg-linear-to-r from-index-400 to-index-600" style={{ width: `${(grupo.total / maximo) * 100}%` }} />
                            </div>
                            <div className="flex flex-wrap gap-x-3 text-xs text-zinc-500 tabular-nums dark:text-zinc-400">
                                {columnas.map((columna) => (
                                    <span key={String(columna.clave)}>
                                        {columna.titulo}: {grupo[columna.clave] ?? 0}
                                    </span>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </Panel>
    );
}

export default function ReporteInscripciones({ filtros, resumen, porCarrera, porModalidad, porTurno, opciones }: PropsReporte) {
    const puede = usePermiso();
    const [filtro, cambiarFiltro] = useFiltros<Filtros>(filtros);
    const procesoVisible = filtro.procesoElegido === TODOS ? '' : filtro.proceso;

    const enlaceExportar = (): string => {
        const parametros = new URLSearchParams();
        parametros.set('proceso', filtro.procesoElegido === TODOS ? TODOS : filtro.proceso);
        (['modalidad', 'sede', 'turno', 'estado'] as const).forEach((clave) => filtro[clave] && parametros.set(clave, filtro[clave]));

        return `${exportar.url()}?${parametros.toString()}`;
    };

    return (
        <div className="space-y-6">
            <div className="flex flex-wrap items-center gap-3">
                <FiltroLista
                    valor={procesoVisible}
                    alCambiar={(proceso) => cambiarFiltro({ proceso: proceso === '' ? TODOS : proceso, procesoElegido: proceso === '' ? TODOS : null, modalidad: '', sede: '', turno: '' }, true)}
                    opciones={opciones.procesos}
                    textoTodos="Todos los procesos"
                    className="sm:w-44"
                />
                <FiltroLista valor={filtro.modalidad} alCambiar={(modalidad) => cambiarFiltro({ modalidad }, true)} opciones={opciones.modalidades} textoTodos="Todas las modalidades" className="sm:w-52" />
                <FiltroLista valor={filtro.sede} alCambiar={(sede) => cambiarFiltro({ sede }, true)} opciones={opciones.sedes} textoTodos="Todas las sedes" className="sm:w-48" />
                <FiltroLista valor={filtro.turno} alCambiar={(turno) => cambiarFiltro({ turno }, true)} opciones={opciones.turnos} textoTodos="Todos los turnos" className="sm:w-40" />
                <FiltroLista valor={filtro.estado} alCambiar={(estado) => cambiarFiltro({ estado }, true)} opciones={opciones.estados} textoTodos="Todos los estados" className="sm:w-40" />
                {puede('reporte_inscripcion_exportar_excel') && (
                    <Button asChild className="sm:ms-auto">
                        <a href={enlaceExportar()}>
                            <Download className="size-4.5" />
                            Exportar Excel
                        </a>
                    </Button>
                )}
            </div>

            <Resumen columnas={5}>
                <ResumenItem etiqueta="Total" valor={resumen.total} icono={Users} tono="info" />
                <ResumenItem etiqueta="Activas" valor={resumen.activas} icono={CircleCheck} tono="exito" />
                <ResumenItem etiqueta="Pendientes" valor={resumen.pendientes} icono={Clock} tono="aviso" />
                <ResumenItem etiqueta="Con saldo" valor={resumen.pagoPendiente} icono={CircleDollarSign} tono="aviso" detalle={`${resumen.pagoParcial} con pago parcial`} />
                <ResumenItem etiqueta="Anuladas" valor={resumen.anuladas} icono={Ban} tono={resumen.anuladas > 0 ? 'peligro' : 'neutro'} />
            </Resumen>

            <div className="grid items-start gap-5 xl:grid-cols-2">
                <Cuadro
                    titulo="Por carrera"
                    grupos={porCarrera}
                    columnas={[
                        { clave: 'activas', titulo: 'Activas' },
                        { clave: 'pendientes', titulo: 'Pendientes' },
                        { clave: 'anuladas', titulo: 'Anuladas' },
                    ]}
                />
                <div className="space-y-5">
                    <Cuadro
                        titulo="Por modalidad"
                        grupos={porModalidad}
                        columnas={[
                            { clave: 'activas', titulo: 'Activas' },
                            { clave: 'pagoPendiente', titulo: 'Con saldo' },
                        ]}
                    />
                    <Cuadro
                        titulo="Por turno"
                        grupos={porTurno}
                        columnas={[
                            { clave: 'activas', titulo: 'Activas' },
                            { clave: 'pendientes', titulo: 'Pendientes' },
                        ]}
                    />
                </div>
            </div>
        </div>
    );
}

ReporteInscripciones.layout = {
    titulo: 'Reporte de inscripciones',
    subtitulo: 'Totales del proceso por estado y pago, y su distribución por carrera, modalidad y turno.',
    icono: 'reportes',
    migas: [{ titulo: 'Inicio', href: inicio.url() }, { titulo: 'Reportes' }, { titulo: 'Inscripciones', href: index.url() }],
};
