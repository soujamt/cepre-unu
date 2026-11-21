import { CircleCheck, CircleDollarSign, Clock, Download, Users, Wallet } from '@keyline-icons/react/fill';
import { FiltroLista } from '@/components/filtro-lista';
import { BarraSegmentada, BarrasApiladas, ColumnasPorDia, Dona, Medidor, TarjetaGrafico, colorEstado, colorGrafico, numero, soles } from '@/components/graficos';
import type { Parte, SerieApilada } from '@/components/graficos';
import { EstadoVacio } from '@/components/panel';
import { Resumen, ResumenItem } from '@/components/resumen';
import { useFiltros } from '@/components/tabla-datos';
import { Button } from '@/components/ui/button';
import { usePermiso } from '@/hooks/use-permiso';
import { inicio } from '@/routes';
import { exportar, index } from '@/routes/reportes/inscripciones';
import type { Opcion } from '@/types';

type Filtros = { proceso: string; procesoElegido: string | null; modalidad: string; sede: string; turno: string; estado: string };

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

interface PropsReporte {
    filtros: Filtros;
    resumen: { total: number; activas: number; pendientes: number; anuladas: number; enRevision: number; conSaldo: number; pagoParcial: number; desdePortal: number };
    recaudacion: { esperado: number; recaudado: number; porcentaje: number };
    porDia: { fecha: string; etiqueta: string; total: number }[];
    porEstado: Conteo[];
    porEstadoPago: Conteo[];
    porSexo: Conteo[];
    porOrigen: Conteo[];
    porCarrera: Grupo[];
    porModalidad: Grupo[];
    porTurno: Grupo[];
    porSede: Grupo[];
    opciones: { procesos: Opcion[]; modalidades: Opcion[]; sedes: Opcion[]; turnos: Opcion[]; estados: Opcion[] };
}

const TODOS = 'todos';

const nombreEstado: Record<string, string> = { ACTIVO: 'Activas', PENDIENTE: 'Pendientes', ANULADO: 'Anuladas' };

const seriesEstado: SerieApilada<Grupo>[] = [
    { clave: 'activas', nombre: 'Activas', color: colorGrafico.activo },
    { clave: 'pendientes', nombre: 'Pendientes', color: colorGrafico.pendiente },
    { clave: 'anuladas', nombre: 'Anuladas', color: colorGrafico.anulado },
];

const coloresSerie = [colorGrafico.serie1, colorGrafico.serie2, colorGrafico.serie3, colorGrafico.neutro];

const titulo = (texto: string): string => texto.charAt(0) + texto.slice(1).toLowerCase();

/** Partes de una barra segmentada con los colores de serie en orden fijo. */
const partesCategoricas = (conteos: Conteo[]): Parte[] =>
    conteos.map((conteo, indice) => ({ clave: conteo.clave, nombre: titulo(conteo.nombre), total: conteo.total, color: coloresSerie[Math.min(indice, coloresSerie.length - 1)] }));

const tablaGrupos = (primera: string, grupos: Grupo[]) => ({
    columnas: [primera, 'Activas', 'Pendientes', 'Anuladas', 'Total'],
    filas: grupos.map((grupo) => [grupo.nombre, grupo.activas, grupo.pendientes, grupo.anuladas, grupo.total]),
});

const tablaConteos = (primera: string, conteos: Conteo[]) => ({ columnas: [primera, 'Inscripciones'], filas: conteos.map((conteo) => [titulo(conteo.nombre), conteo.total]) });

export default function ReporteInscripciones(props: PropsReporte) {
    const { filtros, resumen, recaudacion, porDia, porEstado, porEstadoPago, porSexo, porOrigen, porCarrera, porModalidad, porTurno, porSede, opciones } = props;
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
            <div className="flex flex-wrap items-center gap-2.5 rounded-2xl border border-zinc-200/80 bg-white p-3 shadow-card sm:px-4 dark:border-white/[0.07] dark:bg-white/[0.02] dark:shadow-none">
                <FiltroLista
                    valor={procesoVisible}
                    alCambiar={(proceso) => cambiarFiltro({ proceso: proceso === '' ? TODOS : proceso, procesoElegido: proceso === '' ? TODOS : null, modalidad: '', sede: '', turno: '' }, true)}
                    opciones={opciones.procesos}
                    textoTodos="Todos los procesos"
                    resaltar={false}
                    className="sm:w-44"
                />
                <FiltroLista valor={filtro.modalidad} alCambiar={(modalidad) => cambiarFiltro({ modalidad }, true)} opciones={opciones.modalidades} textoTodos="Todas las modalidades" className="sm:w-48" />
                <FiltroLista valor={filtro.sede} alCambiar={(sede) => cambiarFiltro({ sede }, true)} opciones={opciones.sedes} textoTodos="Todas las sedes" className="sm:w-40" />
                <FiltroLista valor={filtro.turno} alCambiar={(turno) => cambiarFiltro({ turno }, true)} opciones={opciones.turnos} textoTodos="Todos los turnos" className="sm:w-36" />
                <FiltroLista valor={filtro.estado} alCambiar={(estado) => cambiarFiltro({ estado }, true)} opciones={opciones.estados} textoTodos="Todos los estados" className="sm:w-36" />
                {puede('reporte_inscripcion_exportar_excel') && (
                    <Button asChild className="sm:ms-auto">
                        <a href={enlaceExportar()}>
                            <Download className="size-4.5" />
                            Exportar Excel
                        </a>
                    </Button>
                )}
            </div>

            <Resumen>
                <ResumenItem etiqueta="Inscritos" valor={numero(resumen.total)} icono={Users} tono="info" detalle={`${numero(resumen.desdePortal)} desde el portal`} />
                <ResumenItem
                    etiqueta="Activas"
                    valor={numero(resumen.activas)}
                    icono={CircleCheck}
                    tono="exito"
                    detalle={`${resumen.total === 0 ? 0 : Math.round((resumen.activas / resumen.total) * 100)}% · ${numero(resumen.anuladas)} anuladas`}
                />
                <ResumenItem
                    etiqueta="Pendientes"
                    valor={numero(resumen.pendientes)}
                    icono={Clock}
                    tono={resumen.pendientes > 0 ? 'aviso' : 'neutro'}
                    iconoDetalle={CircleDollarSign}
                    detalle={`${numero(resumen.conSaldo)} con saldo · ${numero(resumen.pagoParcial)} parcial`}
                />
                <ResumenItem etiqueta="Recaudado" valor={soles(recaudacion.recaudado)} icono={Wallet} tono="info" detalle={`${recaudacion.porcentaje}% de ${soles(recaudacion.esperado)}`}>
                    <div className="pt-1.5">
                        <Medidor valor={recaudacion.recaudado} maximo={recaudacion.esperado} />
                    </div>
                </ResumenItem>
            </Resumen>

            {resumen.total === 0 ? (
                <div className="rounded-2xl border border-zinc-200/80 bg-white shadow-card dark:border-white/[0.07] dark:bg-white/[0.02] dark:shadow-none">
                    <EstadoVacio icono={Users} titulo="No hay inscripciones con los filtros elegidos." descripcion="Cambie el proceso o quite algunos filtros." />
                </div>
            ) : (
                <>
                    <div className="grid gap-5 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
                        <TarjetaGrafico
                            titulo="Inscripciones por día"
                            descripcion={porDia.length > 0 ? `Del ${porDia[0].etiqueta} al ${porDia[porDia.length - 1].etiqueta} (hasta 31 días).` : undefined}
                            tabla={{ columnas: ['Día', 'Inscripciones'], filas: porDia.map((dia) => [dia.etiqueta, dia.total]) }}
                        >
                            <ColumnasPorDia datos={porDia} alto={220} />
                        </TarjetaGrafico>
                        <TarjetaGrafico titulo="Estado de las inscripciones" tabla={{ columnas: ['Estado', 'Inscripciones'], filas: porEstado.map((estado) => [nombreEstado[estado.clave] ?? estado.nombre, estado.total]) }}>
                            <div className="flex flex-1 items-center">
                                <Dona
                                    etiquetaCentro="inscritos"
                                    partes={porEstado.map((estado) => ({ clave: estado.clave, nombre: nombreEstado[estado.clave] ?? estado.nombre, total: estado.total, color: colorEstado[estado.clave] }))}
                                />
                            </div>
                        </TarjetaGrafico>
                    </div>

                    <div className="grid gap-5 lg:grid-cols-3">
                        <TarjetaGrafico titulo="Estado del pago" descripcion="Inscripciones no anuladas." tabla={tablaConteos('Estado', porEstadoPago)}>
                            <BarraSegmentada partes={porEstadoPago.map((estado) => ({ clave: estado.clave, nombre: titulo(estado.nombre), total: estado.total, color: colorEstado[estado.clave] ?? colorGrafico.neutro }))} />
                        </TarjetaGrafico>
                        <TarjetaGrafico titulo="Sexo" descripcion="De los postulantes inscritos." tabla={tablaConteos('Sexo', porSexo)}>
                            <BarraSegmentada partes={partesCategoricas(porSexo)} />
                        </TarjetaGrafico>
                        <TarjetaGrafico titulo="Origen" descripcion="Dónde se registró la inscripción." tabla={tablaConteos('Origen', porOrigen)}>
                            <BarraSegmentada partes={partesCategoricas(porOrigen)} />
                        </TarjetaGrafico>
                    </div>

                    <div className="grid items-start gap-5 xl:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
                        <TarjetaGrafico titulo="Por carrera" descripcion={`${porCarrera.length} carrera(s), de mayor a menor.`} tabla={tablaGrupos('Carrera', porCarrera)}>
                            <BarrasApiladas filas={porCarrera} series={seriesEstado} />
                        </TarjetaGrafico>
                        <div className="grid gap-5">
                            <TarjetaGrafico titulo="Por modalidad" tabla={tablaGrupos('Modalidad', porModalidad)}>
                                <BarrasApiladas filas={porModalidad} series={seriesEstado} />
                            </TarjetaGrafico>
                            <TarjetaGrafico titulo="Por turno" tabla={tablaGrupos('Turno', porTurno)}>
                                <BarrasApiladas filas={porTurno} series={seriesEstado} />
                            </TarjetaGrafico>
                            <TarjetaGrafico titulo="Por sede" tabla={tablaGrupos('Sede', porSede)}>
                                <BarrasApiladas filas={porSede} series={seriesEstado} />
                            </TarjetaGrafico>
                        </div>
                    </div>
                </>
            )}
        </div>
    );
}

ReporteInscripciones.layout = {
    titulo: 'Reporte de inscripciones',
    subtitulo: 'Cifras, recaudación y gráficos del proceso por día, estado, pago, carrera, modalidad, turno y sede.',
    icono: 'reportes',
    migas: [{ titulo: 'Inicio', href: inicio.url() }, { titulo: 'Reportes' }, { titulo: 'Inscripciones', href: index.url() }],
};
