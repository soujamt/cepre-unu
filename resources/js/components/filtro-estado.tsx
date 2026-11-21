import { CirclePlus, Eraser } from '@keyline-icons/react/fill';
import { DropdownMenu, DropdownMenuCheckboxItem, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';

const todosLosEstados = [
    { valor: 'HABILITADO', etiqueta: 'Habilitado', color: 'bg-green-500' },
    { valor: 'DESHABILITADO', etiqueta: 'Deshabilitado', color: 'bg-red-500' },
    { valor: 'CERRADO', etiqueta: 'Cerrado', color: 'bg-zinc-400' },
    { valor: 'PENDIENTE', etiqueta: 'Pendiente', color: 'bg-amber-500' },
    { valor: 'PROCESADO', etiqueta: 'Procesado', color: 'bg-green-500' },
    { valor: 'OBSERVADO', etiqueta: 'Observado', color: 'bg-red-500' },
    { valor: 'ACTIVO', etiqueta: 'Activo', color: 'bg-green-500' },
    { valor: 'ANULADO', etiqueta: 'Anulado', color: 'bg-red-500' },
];

const estadosCatalogo = ['HABILITADO', 'DESHABILITADO'];

/**
 * Clases de los botones y listas de filtro (40 px). Sin filtro el borde es punteado;
 * con un filtro aplicado se tiñe del color principal para que se note.
 */
export function claseBotonFiltro(activo: boolean): string {
    return cn(
        'inline-flex h-10 shrink-0 items-center gap-2 rounded-xl border px-3 text-sm whitespace-nowrap transition-[border-color,background-color,color,box-shadow] outline-none focus-visible:ring-4 focus-visible:ring-index-500/15',
        activo
            ? 'border-index-300 bg-index-50/70 text-index-700 hover:bg-index-50 dark:border-index-500/40 dark:bg-index-500/10 dark:text-index-200 dark:hover:bg-index-500/15'
            : 'border-dashed border-zinc-300 bg-transparent text-zinc-600 hover:border-solid hover:border-zinc-300 hover:bg-zinc-50 hover:text-zinc-900 dark:border-white/15 dark:text-zinc-300 dark:hover:border-white/20 dark:hover:bg-white/[0.04] dark:hover:text-white',
    );
}

/**
 * Cantidad de filtros aplicados, en una pastilla junto al texto del botón.
 */
export function ContadorFiltro({ cantidad }: { cantidad: number }) {
    return cantidad > 0 ? <span className="bg-index-500 inline-flex min-w-5 items-center justify-center rounded-full px-1.5 text-[11px] leading-5 font-semibold text-white tabular-nums">{cantidad}</span> : null;
}

/**
 * Filtro por estado: casillas con el color de cada estado. Los estados elegidos se
 * muestran dentro del botón (hasta dos; luego, la cantidad).
 */
export function FiltroEstado({ seleccionados, alCambiar, valores = estadosCatalogo }: { seleccionados: string[]; alCambiar: (estados: string[]) => void; valores?: string[] }) {
    const estados = todosLosEstados.filter((estado) => valores.includes(estado.valor));
    const elegidos = estados.filter((estado) => seleccionados.includes(estado.valor));
    const alternar = (valor: string, marcado: boolean): void => {
        alCambiar(marcado ? [...seleccionados, valor] : seleccionados.filter((estado) => estado !== valor));
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger className={cn(claseBotonFiltro(elegidos.length > 0), 'w-full justify-center sm:w-auto')}>
                <CirclePlus className={cn('size-4', elegidos.length > 0 ? 'text-index-500 rotate-45' : 'text-zinc-400')} />
                Estado
                {elegidos.length > 0 && (
                    <>
                        <span className="bg-index-300/70 dark:bg-index-400/30 h-4 w-px" />
                        {elegidos.length <= 2 ? (
                            elegidos.map(({ valor, etiqueta, color }) => (
                                <span
                                    key={valor}
                                    className="inline-flex items-center gap-1.5 rounded-md bg-white px-1.5 py-0.5 text-xs font-medium text-zinc-700 shadow-[0_1px_2px_rgba(16,24,40,0.06)] dark:bg-white/10 dark:text-zinc-100"
                                >
                                    <span className={cn('size-1.5 rounded-full', color)} />
                                    {etiqueta}
                                </span>
                            ))
                        ) : (
                            <ContadorFiltro cantidad={elegidos.length} />
                        )}
                    </>
                )}
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start" className="w-52">
                <DropdownMenuLabel className="text-xs font-medium text-zinc-500">Filtrar por estado</DropdownMenuLabel>
                {estados.map(({ valor, etiqueta, color }) => (
                    <DropdownMenuCheckboxItem key={valor} checked={seleccionados.includes(valor)} onCheckedChange={(marcado) => alternar(valor, marcado === true)} onSelect={(evento) => evento.preventDefault()}>
                        <span className={cn('size-2 rounded-full', color)} />
                        {etiqueta}
                    </DropdownMenuCheckboxItem>
                ))}
                {seleccionados.length > 0 && (
                    <>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem onSelect={() => alCambiar([])} className="justify-center text-zinc-500">
                            <Eraser />
                            Limpiar filtro
                        </DropdownMenuItem>
                    </>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
