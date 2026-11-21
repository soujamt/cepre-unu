import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { cn } from '@/lib/utils';
import type { Opcion } from '@/types';

/** Radix no admite un ítem con valor vacío; este valor representa "todos". */
const TODOS = '__todos__';

interface PropsFiltroLista {
    valor: string;
    alCambiar: (valor: string) => void;
    opciones: Opcion[];
    /** Texto de la opción sin filtro, por ejemplo "Todos los tipos". */
    textoTodos: string;
    /** Resalta la lista cuando tiene una opción elegida (no conviene si siempre la tiene). */
    resaltar?: boolean;
    className?: string;
}

/**
 * Clases de las listas de filtro: borde sólido y, con una opción elegida, el color principal.
 */
export function claseListaFiltro(activo: boolean): string {
    return cn(
        'h-10! w-full rounded-xl text-sm shadow-none transition-[border-color,background-color,color,box-shadow] focus-visible:ring-4 focus-visible:ring-index-500/15 sm:w-56',
        activo
            ? 'border-index-300 bg-index-50/70 text-index-700 dark:border-index-500/40 dark:bg-index-500/10 dark:text-index-200 [&_svg]:text-index-500!'
            : 'border-zinc-200 bg-zinc-50/60 text-zinc-700 hover:border-zinc-300 dark:border-white/10 dark:bg-white/[0.03] dark:text-zinc-200 dark:hover:border-white/15',
    );
}

/**
 * Filtro de una sola opción para los listados, de la misma altura que el buscador.
 */
export function FiltroLista({ valor, alCambiar, opciones, textoTodos, resaltar = true, className }: PropsFiltroLista) {
    return (
        <Select value={valor === '' ? TODOS : valor} onValueChange={(nuevo) => alCambiar(nuevo === TODOS ? '' : nuevo)}>
            <SelectTrigger aria-label={textoTodos} className={cn(claseListaFiltro(resaltar && valor !== ''), className)}>
                <SelectValue />
            </SelectTrigger>
            <SelectContent position="popper" align="start">
                <SelectItem value={TODOS}>{textoTodos}</SelectItem>
                {opciones.map((opcion) => (
                    <SelectItem key={opcion.valor} value={opcion.valor}>
                        {opcion.nombre}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
