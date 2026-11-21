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
    className?: string;
}

/**
 * Filtro de una sola opción para los listados, de la misma altura que el buscador.
 */
export function FiltroLista({ valor, alCambiar, opciones, textoTodos, className }: PropsFiltroLista) {
    return (
        <Select value={valor === '' ? TODOS : valor} onValueChange={(nuevo) => alCambiar(nuevo === TODOS ? '' : nuevo)}>
            <SelectTrigger aria-label={textoTodos} className={cn('h-10! w-full bg-control sm:w-56', className)}>
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
