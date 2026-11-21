import { router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Search, X } from '@keyline-icons/react';
import { useEffect, useRef, useState } from 'react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
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
    className?: string;
}

/**
 * Tabla con el estilo del sistema: encabezado gris, filas separadas y celdas con texto pequeño.
 */
export function TablaDatos<T>({ columnas, filas, claveFila, vacio, className }: PropsTablaDatos<T>) {
    return (
        <div className={cn('overflow-hidden rounded-xl border border-zinc-200/80 bg-white shadow-card dark:border-white/[0.06] dark:bg-zinc-900', className)}>
            <Table>
                <TableHeader className="bg-zinc-50/80 dark:bg-white/[0.02]">
                    <TableRow className="hover:bg-transparent">
                        {columnas.map((columna) => (
                            <TableHead
                                key={columna.clave}
                                className={cn('h-10 px-4 text-xs font-medium tracking-wide text-zinc-500 uppercase dark:text-zinc-400', columna.className)}
                            >
                                {columna.titulo}
                            </TableHead>
                        ))}
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {filas.length === 0 ? (
                        <TableRow className="hover:bg-transparent">
                            <TableCell colSpan={columnas.length} className="p-0">
                                {vacio ?? <p className="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">No hay registros.</p>}
                            </TableCell>
                        </TableRow>
                    ) : (
                        filas.map((fila, indice) => (
                            <TableRow key={claveFila(fila)} className="border-zinc-100 dark:border-white/[0.04]">
                                {columnas.map((columna) => (
                                    <TableCell key={columna.clave} className={cn('px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300', columna.className)}>
                                        {columna.celda(fila, indice)}
                                    </TableCell>
                                ))}
                            </TableRow>
                        ))
                    )}
                </TableBody>
            </Table>
        </div>
    );
}

/**
 * Paginación de un paginador de Laravel: resumen "Mostrando X–Y de Z" y botones
 * anterior/siguiente que conservan los filtros de la URL.
 */
export function Paginacion<T>({ paginado }: { paginado: Paginado<T> }) {
    if (paginado.last_page <= 1) {
        return paginado.total > 0 ? <ResumenPaginacion paginado={paginado} /> : null;
    }

    const anterior = paginado.links[0]?.url ?? null;
    const siguiente = paginado.links[paginado.links.length - 1]?.url ?? null;
    const visitar = (url: string | null): void => {
        if (url) {
            router.get(url, {}, { preserveState: true, preserveScroll: true });
        }
    };

    return (
        <div className="flex flex-col items-center justify-between gap-3 sm:flex-row">
            <ResumenPaginacion paginado={paginado} />
            <div className="flex items-center gap-2">
                <Button variant="outline" size="sm" disabled={!anterior} onClick={() => visitar(anterior)}>
                    <ChevronLeft />
                    Anterior
                </Button>
                <span className="text-sm text-zinc-500 tabular-nums dark:text-zinc-400">
                    {paginado.current_page} / {paginado.last_page}
                </span>
                <Button variant="outline" size="sm" disabled={!siguiente} onClick={() => visitar(siguiente)}>
                    Siguiente
                    <ChevronRight />
                </Button>
            </div>
        </div>
    );
}

function ResumenPaginacion<T>({ paginado }: { paginado: Paginado<T> }) {
    return (
        <p className="text-sm text-zinc-500 dark:text-zinc-400">
            Mostrando <span className="tabular-nums">{paginado.from ?? 0}</span> - <span className="tabular-nums">{paginado.to ?? 0}</span> de{' '}
            <span className="tabular-nums">{paginado.total}</span> registros
        </p>
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

    useEffect(() => () => {
        if (temporizador.current) {
            clearTimeout(temporizador.current);
        }
    }, []);

    const cambiar = (cambios: Partial<F>, inmediato = false): void => {
        const nuevos = { ...filtros, ...cambios };
        setFiltros(nuevos);

        if (temporizador.current) {
            clearTimeout(temporizador.current);
        }

        const consulta = Object.fromEntries(
            Object.entries(nuevos).filter(([, valor]) => valor !== '' && valor !== null && valor !== undefined && valor !== false && !(Array.isArray(valor) && valor.length === 0)),
        );
        const pedir = (): void => router.get(window.location.pathname, consulta as Record<string, string>, { preserveState: true, preserveScroll: true, replace: true });

        if (inmediato) {
            pedir();
        } else {
            temporizador.current = setTimeout(pedir, espera);
        }
    };

    return [filtros, cambiar];
}

/**
 * Caja de búsqueda de los listados, de 40 px como los filtros.
 */
export function Buscador({ valor, alCambiar, placeholder = 'Buscar…', className }: { valor: string; alCambiar: (valor: string) => void; placeholder?: string; className?: string }) {
    return (
        <div className={cn('relative w-full sm:max-w-xs', className)}>
            <Search className="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-zinc-400" />
            <input
                type="search"
                value={valor}
                onChange={(evento) => alCambiar(evento.target.value)}
                placeholder={placeholder}
                aria-label={placeholder}
                className="h-10 w-full rounded-lg border border-zinc-200 border-b-zinc-300/80 bg-control ps-9 pe-9 text-sm text-zinc-700 shadow-xs placeholder:text-zinc-400 focus:ring-1 focus:ring-primary focus:ring-offset-1 focus:ring-offset-background focus:outline-hidden dark:border-white/10 dark:text-zinc-200 [&::-webkit-search-cancel-button]:hidden"
            />
            {valor !== '' && (
                <button
                    type="button"
                    onClick={() => alCambiar('')}
                    aria-label="Limpiar búsqueda"
                    className="absolute end-2 top-1/2 flex size-6 -translate-y-1/2 items-center justify-center rounded-md text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200"
                >
                    <X className="size-3.5" />
                </button>
            )}
        </div>
    );
}
