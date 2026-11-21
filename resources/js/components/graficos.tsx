import { useRef, useState } from 'react';
import type { MouseEvent, ReactNode } from 'react';
import { cn } from '@/lib/utils';

/**
 * Gráficos propios en HTML y SVG (sin librerías): columnas por día, barras apiladas,
 * dona, barra segmentada y medidores. Todos traen leyenda o nombres visibles, un globo
 * al pasar el mouse y, en su tarjeta, una vista de tabla con los mismos datos.
 *
 * Los colores salen de variables CSS (--grafico-*) validadas para tema claro y oscuro.
 */

export const colorGrafico = {
    activo: 'var(--grafico-activo)',
    pendiente: 'var(--grafico-pendiente)',
    anulado: 'var(--grafico-anulado)',
    serie1: 'var(--grafico-serie-1)',
    serie2: 'var(--grafico-serie-2)',
    serie3: 'var(--grafico-serie-3)',
    neutro: 'var(--grafico-neutro)',
    principal: 'var(--color-index-500)',
} as const;

/** Color de cada estado de inscripción y de pago. */
export const colorEstado: Record<string, string> = {
    ACTIVO: colorGrafico.activo,
    PENDIENTE: colorGrafico.pendiente,
    ANULADO: colorGrafico.anulado,
    PAGADO: colorGrafico.activo,
    PARCIAL: colorGrafico.pendiente,
    EXONERADO: colorGrafico.serie1,
    'SIN REGLA': colorGrafico.neutro,
};

/** Texto sobre un relleno de color: oscuro sobre el amarillo, blanco sobre los demás. */
const textoSobre = (color: string): string => (color === colorGrafico.pendiente ? 'text-zinc-950' : 'text-white');

export const numero = (valor: number): string => valor.toLocaleString('es-PE');

export const soles = (monto: number): string => `S/ ${monto.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const porcentaje = (parte: number, total: number): string => (total === 0 ? '0%' : `${Math.round((parte / total) * 100)}%`);

/** Tope del eje: el siguiente número "redondo" (1, 2, 5 × 10ⁿ) sobre el máximo. */
function topeEje(maximo: number): number {
    if (maximo <= 4) {
        return 4;
    }

    const potencia = 10 ** Math.floor(Math.log10(maximo));

    return ([1, 2, 2.5, 5, 10].map((factor) => factor * potencia).find((tope) => tope >= maximo) ?? maximo);
}

/* ---------- Globo (tooltip) ---------- */

interface EstadoGlobo {
    x: number;
    y: number;
    contenido: ReactNode;
}

/**
 * Globo que sigue al mouse dentro del contenedor del gráfico.
 */
function useGlobo() {
    const contenedor = useRef<HTMLDivElement>(null);
    const [globo, setGlobo] = useState<EstadoGlobo | null>(null);

    const mostrar = (evento: MouseEvent, contenido: ReactNode): void => {
        const caja = contenedor.current?.getBoundingClientRect();

        if (caja) {
            setGlobo({ x: evento.clientX - caja.left, y: evento.clientY - caja.top, contenido });
        }
    };

    const elemento = globo && (
        <div
            role="tooltip"
            className="pointer-events-none absolute z-20 w-max max-w-64 -translate-x-1/2 -translate-y-[calc(100%+12px)] rounded-xl border border-zinc-200/80 bg-white px-3 py-2 text-xs text-zinc-600 shadow-[0_8px_24px_-8px_rgba(16,24,40,0.25)] dark:border-white/10 dark:bg-zinc-800 dark:text-zinc-300"
            style={{ left: globo.x, top: globo.y }}
        >
            {globo.contenido}
        </div>
    );

    return { contenedor, mostrar, ocultar: () => setGlobo(null), elemento };
}

function LineaGlobo({ color, nombre, valor }: { color?: string; nombre: string; valor: ReactNode }) {
    return (
        <span className="flex items-center justify-between gap-4">
            <span className="flex items-center gap-1.5">
                {color && <span className="size-2 rounded-full" style={{ backgroundColor: color }} />}
                {nombre}
            </span>
            <span className="font-semibold text-zinc-900 tabular-nums dark:text-white">{valor}</span>
        </span>
    );
}

/* ---------- Tarjeta y leyenda ---------- */

export interface TablaGrafico {
    columnas: string[];
    filas: (string | number)[][];
}

/**
 * Tarjeta de un gráfico con título y, si se da la tabla, el cambio Gráfico / Tabla.
 */
export function TarjetaGrafico({
    titulo,
    descripcion,
    accion,
    tabla,
    children,
    className,
}: {
    titulo: string;
    descripcion?: ReactNode;
    accion?: ReactNode;
    tabla?: TablaGrafico;
    children: ReactNode;
    className?: string;
}) {
    const [vista, setVista] = useState<'grafico' | 'tabla'>('grafico');

    return (
        <section className={cn('flex min-w-0 flex-col rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-card dark:border-white/[0.07] dark:bg-white/[0.02] dark:shadow-none', className)}>
            <header className="mb-5 flex flex-wrap items-start justify-between gap-3">
                <div className="min-w-0">
                    <h2 className="text-[15px] font-semibold text-zinc-900 dark:text-white">{titulo}</h2>
                    {descripcion && <p className="mt-0.5 text-[13px] text-zinc-500 dark:text-zinc-400">{descripcion}</p>}
                </div>
                <div className="flex shrink-0 items-center gap-2">
                    {accion}
                    {tabla && (
                        <div className="flex rounded-lg bg-zinc-100 p-0.5 text-xs font-medium dark:bg-white/[0.06]" role="group" aria-label="Vista">
                            {(['grafico', 'tabla'] as const).map((opcion) => (
                                <button
                                    key={opcion}
                                    type="button"
                                    onClick={() => setVista(opcion)}
                                    aria-pressed={vista === opcion}
                                    className={cn(
                                        'rounded-md px-2.5 py-1 transition-colors',
                                        vista === opcion ? 'bg-white text-zinc-900 shadow-[0_1px_2px_rgba(16,24,40,0.08)] dark:bg-white/10 dark:text-white' : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200',
                                    )}
                                >
                                    {opcion === 'grafico' ? 'Gráfico' : 'Tabla'}
                                </button>
                            ))}
                        </div>
                    )}
                </div>
            </header>
            <div className="flex min-h-0 flex-1 flex-col">{vista === 'tabla' && tabla ? <VistaTabla tabla={tabla} /> : children}</div>
        </section>
    );
}

function VistaTabla({ tabla }: { tabla: TablaGrafico }) {
    return (
        <div className="scrollbar-fino max-h-80 overflow-auto rounded-xl border border-zinc-200/70 dark:border-white/[0.07]">
            <table className="w-full text-sm">
                <thead className="sticky top-0 bg-zinc-50 text-[11px] font-semibold tracking-[0.06em] text-zinc-500 uppercase dark:bg-zinc-800 dark:text-zinc-400">
                    <tr>
                        {tabla.columnas.map((columna, indice) => (
                            <th key={columna} className={cn('h-9 px-3', indice === 0 ? 'text-start' : 'text-end')}>
                                {columna}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody className="divide-y divide-zinc-100 dark:divide-white/[0.05]">
                    {tabla.filas.map((fila) => (
                        <tr key={String(fila[0])}>
                            {fila.map((celda, indice) => (
                                <td key={indice} className={cn('px-3 py-2', indice === 0 ? 'text-zinc-700 dark:text-zinc-200' : 'text-end text-zinc-900 tabular-nums dark:text-white')}>
                                    {typeof celda === 'number' ? numero(celda) : celda}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

export interface ItemLeyenda {
    nombre: string;
    color: string;
    valor?: ReactNode;
}

export function Leyenda({ items, className }: { items: ItemLeyenda[]; className?: string }) {
    return (
        <ul className={cn('flex flex-wrap gap-x-4 gap-y-1.5 text-xs text-zinc-600 dark:text-zinc-300', className)}>
            {items.map((item) => (
                <li key={item.nombre} className="flex items-center gap-1.5">
                    <span className="size-2.5 rounded-[3px]" style={{ backgroundColor: item.color }} />
                    <span>{item.nombre}</span>
                    {item.valor !== undefined && <span className="font-semibold text-zinc-900 tabular-nums dark:text-white">{item.valor}</span>}
                </li>
            ))}
        </ul>
    );
}

/* ---------- Columnas por día ---------- */

/**
 * Columnas de una sola serie a lo largo del tiempo, con eje de valores y la columna
 * de hoy (la última) resaltada.
 */
export function ColumnasPorDia({ datos, alto = 200, unidad = 'inscripciones' }: { datos: { fecha: string; etiqueta: string; total: number }[]; alto?: number; unidad?: string }) {
    const globo = useGlobo();
    const tope = topeEje(Math.max(0, ...datos.map((dato) => dato.total)));
    const marcas = [tope, tope / 2, 0];
    const salto = Math.max(1, Math.ceil(datos.length / 8));

    return (
        <div ref={globo.contenedor} className="relative" onMouseLeave={globo.ocultar}>
            <div className="flex gap-3">
                <div className="flex flex-col justify-between pb-6 text-end text-[11px] text-zinc-400 tabular-nums dark:text-zinc-500" style={{ height: alto }}>
                    {marcas.map((marca) => (
                        <span key={marca} className="-translate-y-1/2 leading-none first:translate-y-0 last:translate-y-0">
                            {numero(Math.round(marca))}
                        </span>
                    ))}
                </div>
                <div className="relative min-w-0 flex-1">
                    <div className="pointer-events-none absolute inset-x-0 top-0 flex flex-col justify-between" style={{ height: alto - 24 }}>
                        {marcas.map((marca, indice) => (
                            <span key={marca} className={cn('h-px w-full', indice === marcas.length - 1 ? 'bg-zinc-300 dark:bg-white/15' : 'bg-(--grafico-grilla)')} />
                        ))}
                    </div>
                    <div className="relative flex items-end gap-[2px]" style={{ height: alto - 24 }}>
                        {datos.map((dato, indice) => {
                            const esHoy = indice === datos.length - 1;

                            return (
                                <div
                                    key={dato.fecha}
                                    className="group/columna flex h-full flex-1 cursor-default items-end justify-center"
                                    onMouseMove={(evento) =>
                                        globo.mostrar(
                                            evento,
                                            <>
                                                <span className="mb-1 block font-medium text-zinc-900 dark:text-white">{esHoy ? `Hoy, ${dato.etiqueta}` : dato.etiqueta}</span>
                                                <LineaGlobo color={colorGrafico.principal} nombre={unidad} valor={numero(dato.total)} />
                                            </>,
                                        )
                                    }
                                >
                                    <div
                                        className={cn(
                                            'w-full max-w-6 rounded-t-[4px] transition-[opacity,filter] group-hover/columna:brightness-110',
                                            esHoy ? 'bg-linear-to-t from-index-600 to-index-400' : 'bg-index-500/75 group-hover/columna:bg-index-500 dark:bg-index-400/70',
                                        )}
                                        style={{ height: dato.total === 0 ? 0 : `max(3px, ${(dato.total / tope) * 100}%)` }}
                                    />
                                </div>
                            );
                        })}
                    </div>
                    <div className="mt-2 flex gap-[2px] text-[11px] text-zinc-400 tabular-nums dark:text-zinc-500">
                        {datos.map((dato, indice) => (
                            <span key={dato.fecha} className="relative h-4 flex-1">
                                {(datos.length - 1 - indice) % salto === 0 && <span className="absolute left-1/2 -translate-x-1/2 whitespace-nowrap">{dato.etiqueta}</span>}
                            </span>
                        ))}
                    </div>
                </div>
            </div>
            {globo.elemento}
        </div>
    );
}

/* ---------- Dona ---------- */

export interface Parte {
    clave: string;
    nombre: string;
    total: number;
    color: string;
}

/**
 * Dona con el total al centro y la leyenda con cantidad y porcentaje de cada parte.
 */
export function Dona({ partes, etiquetaCentro, tamano = 168 }: { partes: Parte[]; etiquetaCentro: string; tamano?: number }) {
    const globo = useGlobo();
    const [activa, setActiva] = useState<string | null>(null);
    const total = partes.reduce((suma, parte) => suma + parte.total, 0);
    const radio = 42;
    const circunferencia = 2 * Math.PI * radio;
    const visibles = partes.filter((parte) => parte.total > 0);
    const separacion = visibles.length > 1 ? 1.6 : 0;
    let acumulado = 0;

    return (
        <div ref={globo.contenedor} className="relative flex flex-col items-center gap-5 sm:flex-row sm:gap-6" onMouseLeave={() => (setActiva(null), globo.ocultar())}>
            <div className="relative shrink-0" style={{ width: tamano, height: tamano }}>
                <svg viewBox="0 0 100 100" className="size-full -rotate-90" role="img" aria-label={`${etiquetaCentro}: ${partes.map((parte) => `${parte.nombre} ${parte.total}`).join(', ')}`}>
                    <circle cx="50" cy="50" r={radio} fill="none" strokeWidth="11" className="stroke-zinc-100 dark:stroke-white/[0.06]" />
                    {visibles.map((parte) => {
                        const largo = (parte.total / Math.max(1, total)) * circunferencia;
                        const inicio = acumulado;
                        acumulado += largo;

                        return (
                            <circle
                                key={parte.clave}
                                cx="50"
                                cy="50"
                                r={radio}
                                fill="none"
                                stroke={parte.color}
                                strokeWidth={activa === parte.clave ? 14 : 11}
                                strokeDasharray={`${Math.max(0.01, largo - separacion)} ${circunferencia}`}
                                strokeDashoffset={-inicio}
                                className={cn('cursor-default transition-[stroke-width,opacity] duration-150', activa && activa !== parte.clave && 'opacity-40')}
                                onMouseMove={(evento) => {
                                    setActiva(parte.clave);
                                    globo.mostrar(evento, <LineaGlobo color={parte.color} nombre={parte.nombre} valor={`${numero(parte.total)} · ${porcentaje(parte.total, total)}`} />);
                                }}
                            />
                        );
                    })}
                </svg>
                <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center text-center">
                    <span className="text-3xl font-semibold tracking-tight text-zinc-900 dark:text-white">{numero(total)}</span>
                    <span className="text-xs text-zinc-500 dark:text-zinc-400">{etiquetaCentro}</span>
                </div>
            </div>
            <ul className="w-full min-w-0 space-y-2.5">
                {partes.map((parte) => (
                    <li
                        key={parte.clave}
                        onMouseEnter={() => setActiva(parte.clave)}
                        onMouseLeave={() => setActiva(null)}
                        className={cn('flex items-center gap-2.5 rounded-lg px-2 py-1 text-sm transition-colors', activa === parte.clave && 'bg-zinc-50 dark:bg-white/[0.04]')}
                    >
                        <span className="size-2.5 shrink-0 rounded-[3px]" style={{ backgroundColor: parte.color }} />
                        <span className="min-w-0 flex-1 truncate text-zinc-600 dark:text-zinc-300">{parte.nombre}</span>
                        <span className="font-semibold text-zinc-900 tabular-nums dark:text-white">{numero(parte.total)}</span>
                        <span className="w-10 text-end text-xs text-zinc-400 tabular-nums">{porcentaje(parte.total, total)}</span>
                    </li>
                ))}
            </ul>
            {globo.elemento}
        </div>
    );
}

/* ---------- Barra segmentada ---------- */

/**
 * Un total dividido en partes (hasta 6): bloques proporcionales con su porcentaje
 * dentro cuando entra, y la leyenda con nombres y cantidades.
 */
export function BarraSegmentada({ partes }: { partes: Parte[] }) {
    const globo = useGlobo();
    const total = partes.reduce((suma, parte) => suma + parte.total, 0);
    const visibles = partes.filter((parte) => parte.total > 0);

    if (total === 0) {
        return <p className="text-sm text-zinc-500 dark:text-zinc-400">Sin datos.</p>;
    }

    return (
        <div ref={globo.contenedor} className="relative space-y-3" onMouseLeave={globo.ocultar}>
            <div className="flex h-7 gap-[2px]" role="img" aria-label={visibles.map((parte) => `${parte.nombre} ${porcentaje(parte.total, total)}`).join(', ')}>
                {visibles.map((parte) => {
                    const ancho = (parte.total / total) * 100;

                    return (
                        <div
                            key={parte.clave}
                            className={cn('flex min-w-1 cursor-default items-center rounded-[4px] px-2 text-[11px] font-semibold transition-[filter] hover:brightness-110', textoSobre(parte.color))}
                            style={{ width: `${ancho}%`, backgroundColor: parte.color }}
                            onMouseMove={(evento) => globo.mostrar(evento, <LineaGlobo color={parte.color} nombre={parte.nombre} valor={`${numero(parte.total)} · ${porcentaje(parte.total, total)}`} />)}
                        >
                            {ancho >= 12 && porcentaje(parte.total, total)}
                        </div>
                    );
                })}
            </div>
            <Leyenda items={partes.map((parte) => ({ nombre: parte.nombre, color: parte.color, valor: numero(parte.total) }))} />
            {globo.elemento}
        </div>
    );
}

/* ---------- Barras apiladas horizontales ---------- */

export interface FilaApilada {
    nombre: string;
    total: number;
}

/** Serie de las barras apiladas: una columna numérica de las filas. */
export interface SerieApilada<T extends FilaApilada> {
    clave: keyof T & string;
    nombre: string;
    color: string;
}

/**
 * Una barra horizontal por grupo (de mayor a menor), partida por series, con el total
 * al final. Al pasar el mouse muestra el desglose del grupo.
 */
export function BarrasApiladas<T extends FilaApilada>({ filas, series, maximoFilas }: { filas: T[]; series: SerieApilada<T>[]; maximoFilas?: number }) {
    const globo = useGlobo();
    const visibles = maximoFilas ? filas.slice(0, maximoFilas) : filas;
    const maximo = Math.max(1, ...visibles.map((fila) => fila.total));

    if (visibles.length === 0) {
        return <p className="text-sm text-zinc-500 dark:text-zinc-400">Sin inscripciones con los filtros actuales.</p>;
    }

    return (
        <div ref={globo.contenedor} className="relative space-y-4" onMouseLeave={globo.ocultar}>
            {series.length > 1 && <Leyenda items={series.map((serie) => ({ nombre: serie.nombre, color: serie.color }))} />}
            <ul className="space-y-2.5">
                {visibles.map((fila) => (
                    <li
                        key={fila.nombre}
                        className="group/fila grid cursor-default grid-cols-[minmax(0,11rem)_minmax(0,1fr)] items-center gap-3 sm:grid-cols-[minmax(0,14rem)_minmax(0,1fr)]"
                        onMouseMove={(evento) =>
                            globo.mostrar(
                                evento,
                                <span className="block min-w-40 space-y-1">
                                    <span className="mb-1.5 block font-medium text-zinc-900 dark:text-white">{fila.nombre}</span>
                                    {series.map((serie) => (
                                        <LineaGlobo key={serie.clave} color={serie.color} nombre={serie.nombre} valor={numero(Number(fila[serie.clave] ?? 0))} />
                                    ))}
                                    {series.length > 1 && <LineaGlobo nombre="Total" valor={numero(fila.total)} />}
                                </span>,
                            )
                        }
                    >
                        <span className="truncate text-[13px] text-zinc-600 group-hover/fila:text-zinc-900 dark:text-zinc-300 dark:group-hover/fila:text-white" title={fila.nombre}>
                            {fila.nombre}
                        </span>
                        <span className="flex min-w-0 items-center gap-2">
                            <span className="flex h-3.5 min-w-0 gap-[2px]" style={{ width: `${(fila.total / maximo) * 100}%` }}>
                                {series.map((serie) => {
                                    const valor = Number(fila[serie.clave] ?? 0);

                                    return valor > 0 ? (
                                        <span
                                            key={serie.clave}
                                            className="h-full first:rounded-s-[2px] last:rounded-e-[4px] group-hover/fila:brightness-110"
                                            style={{ width: `${(valor / fila.total) * 100}%`, backgroundColor: serie.color }}
                                        />
                                    ) : null;
                                })}
                            </span>
                            <span className="shrink-0 text-xs font-semibold text-zinc-900 tabular-nums dark:text-white">{numero(fila.total)}</span>
                        </span>
                    </li>
                ))}
            </ul>
            {globo.elemento}
        </div>
    );
}

/* ---------- Medidores ---------- */

/**
 * Avance de un valor contra su meta (por ejemplo, lo recaudado frente a lo esperado).
 */
export function Medidor({ valor, maximo, tono = 'principal' }: { valor: number; maximo: number; tono?: 'principal' | 'activo' }) {
    const avance = maximo === 0 ? 0 : Math.min(100, (valor / maximo) * 100);

    return (
        <div className="h-2.5 overflow-hidden rounded-full bg-index-100 dark:bg-index-500/15" role="meter" aria-valuenow={Math.round(avance)} aria-valuemin={0} aria-valuemax={100}>
            <div
                className={cn('h-full rounded-full transition-[width] duration-700', tono === 'activo' ? 'bg-(--grafico-activo)' : 'bg-linear-to-r from-index-400 to-index-600')}
                style={{ width: `${avance}%` }}
            />
        </div>
    );
}

/**
 * Ocupación por fila: nombre, barra y cifra. El color avisa cuando se llena (80 %) o se pasa.
 */
export function ListaOcupacion({ filas }: { filas: { nombre: string; asignadas: number; capacidad: number }[] }) {
    if (filas.length === 0) {
        return <p className="text-sm text-zinc-500 dark:text-zinc-400">El proceso no tiene aulas asignadas a turnos.</p>;
    }

    return (
        <ul className="space-y-4">
            {filas.map((fila) => {
                const uso = fila.capacidad === 0 ? 0 : (fila.asignadas / fila.capacidad) * 100;
                const color = uso > 100 ? colorGrafico.anulado : uso >= 80 ? colorGrafico.pendiente : colorGrafico.principal;

                return (
                    <li key={fila.nombre} className="space-y-1.5">
                        <div className="flex items-baseline justify-between gap-3 text-sm">
                            <span className="truncate font-medium text-zinc-800 dark:text-zinc-100">{fila.nombre}</span>
                            <span className="shrink-0 text-xs text-zinc-500 tabular-nums dark:text-zinc-400">
                                <span className="font-semibold text-zinc-900 dark:text-white">{numero(fila.asignadas)}</span> de {numero(fila.capacidad)} vacantes · {Math.round(uso)}%
                            </span>
                        </div>
                        <div className="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-white/[0.06]">
                            <div className="h-full rounded-full transition-[width] duration-700" style={{ width: `${Math.min(100, uso)}%`, backgroundColor: color }} />
                        </div>
                    </li>
                );
            })}
        </ul>
    );
}
