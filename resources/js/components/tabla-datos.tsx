import { router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Search, X } from '@keyline-icons/react/fill';
import { useEffect, useRef, useState } from 'react';
import type { ReactNode } from 'react';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { cn } from '@/lib/utils';
import type { Paginado } from '@/types';

export interface Columna<T> {
    clave: string;
    titulo: ReactNode;
    celda: (fila: T, indice: number) => ReactNode;
    className?: string;
}

interface PropsTablaDatos<T> {
    columnas: Columna<T>[];
    filas: T[];
    claveFila: (fila: T) => string | number;
    /** Contenido cuando no hay filas, por ejemplo un <EstadoVacio />. */
    vacio?: ReactNode;
    /** Búsqueda, filtros y acciones; van en la cabecera de la tarjeta. */
    barra?: ReactNode;
    /** Paginador de Laravel; el resumen y las páginas van al pie de la tarjeta. */
    paginado?: Paginado<T>;
    /** Controles extra al pie, junto a la paginación (por ejemplo, filas por página). */
    pie?: ReactNode;
    className?: string;
}

/**
 * Tabla con el estilo del sistema: una tarjeta con la barra de filtros arriba, el
 * encabezado en versalitas, filas que se resaltan al pasar el mouse y la paginación al pie.
 */
export function TablaDatos<T>({ columnas, filas, claveFila, vacio, barra, paginado, pie, className }: PropsTablaDatos<T>) {
    return (
        <div className={cn('shadow-card overflow-hidden rounded-2xl border border-zinc-200/80 bg-white dark:border-white/[0.07] dark:bg-white/[0.02] dark:shadow-none', className)}>
            {barra && <div className="flex flex-wrap items-center gap-2.5 border-b border-zinc-100 bg-white p-3 sm:px-4 dark:border-white/[0.06] dark:bg-transparent">{barra}</div>}
            <Table>
                <TableHeader className="bg-zinc-50/80 dark:bg-white/[0.025]">
                    <TableRow className="border-zinc-200/70 hover:bg-transparent dark:border-white/[0.06]">
                        {columnas.map((columna) => (
                            <TableHead key={columna.clave} className={cn('h-10 px-4 first:ps-5 last:pe-5', columna.className, 'text-[11px] font-semibold tracking-[0.06em] text-zinc-500 uppercase dark:text-zinc-400')}>
                                {columna.titulo}
                            </TableHead>
                        ))}
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {filas.length === 0 ? (
                        <TableRow className="hover:bg-transparent">
                            <TableCell colSpan={columnas.length} className="p-0">
                                {vacio ?? <p className="px-4 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">No hay registros.</p>}
                            </TableCell>
                        </TableRow>
                    ) : (
                        filas.map((fila, indice) => (
                            <TableRow
                                key={claveFila(fila)}
                                className="group/fila hover:bg-index-50/40 has-aria-expanded:bg-index-50/40 border-zinc-100 transition-colors dark:border-white/[0.04] dark:hover:bg-white/[0.025] dark:has-aria-expanded:bg-white/[0.025]"
                            >
                                {columnas.map((columna) => (
                                    <TableCell key={columna.clave} className={cn('px-4 py-3.5 text-sm text-zinc-700 first:ps-5 last:pe-5 dark:text-zinc-300', columna.className)}>
                                        {columna.celda(fila, indice)}
                                    </TableCell>
                                ))}
                            </TableRow>
                        ))
                    )}
                </TableBody>
            </Table>
            {paginado && paginado.total > 0 && (
                <div className="border-t border-zinc-100 bg-zinc-50/40 px-4 py-3 sm:px-5 dark:border-white/[0.06] dark:bg-white/[0.01]">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                        {pie}
                        <div className="min-w-0 flex-1">
                            <Paginacion paginado={paginado} />
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}

/**
 * Páginas que se muestran: la primera, la última y las vecinas de la actual, con
 * puntos suspensivos (null) donde se saltan números.
 */
function paginasVisibles(actual: number, ultima: number): (number | null)[] {
    const visibles = Array.from({ length: ultima }, (_valor, indice) => indice + 1).filter((pagina) => pagina === 1 || pagina === ultima || Math.abs(pagina - actual) <= 1);
    const paginas: (number | null)[] = [];

    visibles.forEach((pagina, indice) => {
        const anterior = visibles[indice - 1];

        // Si solo se salta una página, se muestra su número en lugar de los puntos.
        if (anterior !== undefined && pagina - anterior === 2) {
            paginas.push(anterior + 1);
        } else if (anterior !== undefined && pagina - anterior > 2) {
            paginas.push(null);
        }

        paginas.push(pagina);
    });

    return paginas;
}

/**
 * Paginación de un paginador de Laravel: resumen "Mostrando X–Y de Z" y los botones de
 * página, anterior y siguiente, que conservan los filtros de la URL.
 */
export function Paginacion<T>({ paginado }: { paginado: Paginado<T> }) {
    const urlDePagina = (pagina: number): string | null => paginado.links.find((enlace) => enlace.label === String(pagina))?.url ?? null;
    const anterior = paginado.links[0]?.url ?? null;
    const siguiente = paginado.links[paginado.links.length - 1]?.url ?? null;
    const visitar = (url: string | null): void => {
        if (url) {
            router.get(url, {}, { preserveState: true, preserveScroll: true });
        }
    };
    const claseBoton = 'flex h-8 min-w-8 items-center justify-center rounded-lg px-2 text-[13px] font-medium tabular-nums transition-colors disabled:pointer-events-none disabled:opacity-40';

    return (
        <div className="flex flex-col items-center justify-between gap-3 sm:flex-row">
            <p className="text-[13px] text-zinc-500 dark:text-zinc-400">
                Mostrando <span className="font-medium text-zinc-800 tabular-nums dark:text-zinc-200">{paginado.from ?? 0}</span>–
                <span className="font-medium text-zinc-800 tabular-nums dark:text-zinc-200">{paginado.to ?? 0}</span> de <span className="font-medium text-zinc-800 tabular-nums dark:text-zinc-200">{paginado.total}</span>{' '}
                registros
            </p>
            {paginado.last_page > 1 && (
                <nav className="flex items-center gap-1" aria-label="Paginación">
                    <button
                        type="button"
                        className={cn(claseBoton, 'text-zinc-500 hover:bg-zinc-100 hover:text-zinc-800 dark:text-zinc-400 dark:hover:bg-white/[0.06] dark:hover:text-white')}
                        disabled={!anterior}
                        onClick={() => visitar(anterior)}
                        aria-label="Página anterior"
                    >
                        <ChevronLeft className="size-4" />
                    </button>
                    {paginasVisibles(paginado.current_page, paginado.last_page).map((pagina, indice) =>
                        pagina === null ? (
                            <span key={`salto-${indice}`} className="w-6 text-center text-zinc-400">
                                …
                            </span>
                        ) : (
                            <button
                                key={pagina}
                                type="button"
                                onClick={() => pagina !== paginado.current_page && visitar(urlDePagina(pagina))}
                                aria-current={pagina === paginado.current_page ? 'page' : undefined}
                                className={cn(
                                    claseBoton,
                                    pagina === paginado.current_page
                                        ? 'from-index-400 to-index-600 bg-linear-to-b text-white shadow-[0_2px_6px_-1px_rgba(41,111,240,0.45),inset_0_1px_0_rgba(255,255,255,0.25)]'
                                        : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-white/[0.06] dark:hover:text-white',
                                )}
                            >
                                {pagina}
                            </button>
                        ),
                    )}
                    <button
                        type="button"
                        className={cn(claseBoton, 'text-zinc-500 hover:bg-zinc-100 hover:text-zinc-800 dark:text-zinc-400 dark:hover:bg-white/[0.06] dark:hover:text-white')}
                        disabled={!siguiente}
                        onClick={() => visitar(siguiente)}
                        aria-label="Página siguiente"
                    >
                        <ChevronRight className="size-4" />
                    </button>
                </nav>
            )}
        </div>
    );
}

/**
 * Columna "Registro": fecha y, debajo, la hora en gris.
 */
export function CeldaRegistro({ registro }: { registro: { fecha: string; hora: string } | null }) {
    if (!registro) {
        return <span className="text-zinc-400 dark:text-zinc-500">—</span>;
    }

    return (
        <>
            <span className="block tabular-nums">{registro.fecha}</span>
            <span className="block text-xs text-zinc-400 tabular-nums dark:text-zinc-500">{registro.hora}</span>
        </>
    );
}

type ValorFiltro = string | number | boolean | string[] | null | undefined;

/**
 * Filtros de un listado guardados en la URL. Cada cambio vuelve a pedir la página
 * conservando el estado y el scroll; los textos esperan a que se deje de escribir.
 */
export function useFiltros<F extends Record<string, ValorFiltro>>(iniciales: F, espera = 350): [F, (cambios: Partial<F>, inmediato?: boolean) => void] {
    const [filtros, setFiltros] = useState<F>(iniciales);
    const temporizador = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(
        () => () => {
            if (temporizador.current) {
                clearTimeout(temporizador.current);
            }
        },
        [],
    );

    const cambiar = (cambios: Partial<F>, inmediato = false): void => {
        const nuevos = { ...filtros, ...cambios };
        setFiltros(nuevos);

        if (temporizador.current) {
            clearTimeout(temporizador.current);
        }

        const consulta = Object.fromEntries(Object.entries(nuevos).filter(([, valor]) => valor !== '' && valor !== null && valor !== undefined && valor !== false && !(Array.isArray(valor) && valor.length === 0)));
        const pedir = (): void =>
            router.get(window.location.pathname, consulta as Record<string, string>, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });

        if (inmediato) {
            pedir();
        } else {
            temporizador.current = setTimeout(pedir, espera);
        }
    };

    return [filtros, cambiar];
}

/**
 * Caja de búsqueda de los listados, de 40 px como los filtros. La tecla "/" la enfoca
 * desde cualquier parte de la página.
 */
export function Buscador({ valor, alCambiar, placeholder = 'Buscar…', className }: { valor: string; alCambiar: (valor: string) => void; placeholder?: string; className?: string }) {
    const campo = useRef<HTMLInputElement>(null);

    useEffect(() => {
        const alPresionar = (evento: KeyboardEvent): void => {
            const destino = evento.target as HTMLElement | null;
            const escribiendo = destino?.closest('input, textarea, select, [contenteditable="true"]');

            if (evento.key === '/' && !escribiendo && !evento.metaKey && !evento.ctrlKey) {
                evento.preventDefault();
                campo.current?.focus();
            }
        };

        document.addEventListener('keydown', alPresionar);

        return () => document.removeEventListener('keydown', alPresionar);
    }, []);

    return (
        <div className={cn('group/buscador relative w-full sm:max-w-xs', className)}>
            <Search className="group-focus-within/buscador:text-index-500 pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-zinc-400 transition-colors" />
            <input
                ref={campo}
                type="search"
                value={valor}
                onChange={(evento) => alCambiar(evento.target.value)}
                onKeyDown={(evento) => evento.key === 'Escape' && valor !== '' && alCambiar('')}
                placeholder={placeholder}
                aria-label={placeholder}
                className="focus:border-index-400 focus:ring-index-500/10 dark:focus:border-index-500/60 h-10 w-full rounded-xl border border-zinc-200 bg-zinc-50/60 ps-9 pe-10 text-sm text-zinc-700 transition-[border-color,box-shadow,background-color] placeholder:text-zinc-400 hover:border-zinc-300 focus:bg-white focus:ring-4 focus:outline-hidden dark:border-white/10 dark:bg-white/[0.03] dark:text-zinc-200 dark:hover:border-white/15 dark:focus:bg-white/[0.04] [&::-webkit-search-cancel-button]:hidden"
            />
            {valor !== '' ? (
                <button
                    type="button"
                    onClick={() => alCambiar('')}
                    aria-label="Limpiar búsqueda"
                    className="absolute end-2 top-1/2 flex size-6 -translate-y-1/2 items-center justify-center rounded-md bg-zinc-200/70 text-zinc-500 transition-colors hover:bg-zinc-300/70 hover:text-zinc-700 dark:bg-white/10 dark:text-zinc-300 dark:hover:bg-white/15"
                >
                    <X className="size-3" />
                </button>
            ) : (
                <kbd className="pointer-events-none absolute end-2.5 top-1/2 hidden h-5 -translate-y-1/2 items-center rounded-md border border-zinc-200 bg-white px-1.5 font-sans text-[11px] text-zinc-400 shadow-[0_1px_0_rgba(16,24,40,0.05)] sm:flex dark:border-white/10 dark:bg-white/[0.04] dark:text-zinc-500">
                    /
                </kbd>
            )}
        </div>
    );
}
