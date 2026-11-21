import { Alert, Ban, ChevronDown, CircleCheck, Clock, Eraser, Filter, Lock, SquareCheck, SquareX } from '@keyline-icons/react';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuCheckboxItem, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';

const todosLosEstados = [
    { valor: 'HABILITADO', etiqueta: 'Habilitado', icono: SquareCheck, color: 'text-green-500' },
    { valor: 'DESHABILITADO', etiqueta: 'Deshabilitado', icono: SquareX, color: 'text-red-500' },
    { valor: 'CERRADO', etiqueta: 'Cerrado', icono: Lock, color: 'text-zinc-400' },
    { valor: 'PENDIENTE', etiqueta: 'Pendiente', icono: Clock, color: 'text-amber-500' },
    { valor: 'PROCESADO', etiqueta: 'Procesado', icono: CircleCheck, color: 'text-green-500' },
    { valor: 'OBSERVADO', etiqueta: 'Observado', icono: Alert, color: 'text-red-500' },
    { valor: 'ACTIVO', etiqueta: 'Activo', icono: CircleCheck, color: 'text-green-500' },
    { valor: 'ANULADO', etiqueta: 'Anulado', icono: Ban, color: 'text-red-500' },
];

const estadosCatalogo = ['HABILITADO', 'DESHABILITADO'];

/**
 * Filtro por estado de los catálogos: casillas Habilitado / Deshabilitado y la
 * cantidad marcada sobre el botón.
 */
export function FiltroEstado({ seleccionados, alCambiar, valores = estadosCatalogo }: { seleccionados: string[]; alCambiar: (estados: string[]) => void; valores?: string[] }) {
    const estados = todosLosEstados.filter((estado) => valores.includes(estado.valor));
    const alternar = (valor: string, marcado: boolean): void => {
        alCambiar(marcado ? [...seleccionados, valor] : seleccionados.filter((estado) => estado !== valor));
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="outline" className="w-full font-normal sm:w-auto">
                    <Filter className="size-4.5" />
                    Estado
                    {seleccionados.length > 0 && (
                        <span className="ms-1 inline-flex min-w-5 items-center justify-center rounded-full bg-index-500 px-1.5 text-[11px] leading-5 font-semibold text-white">
                            {seleccionados.length}
                        </span>
                    )}
                    <ChevronDown className="size-4 text-zinc-400" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start" className="w-48">
                {estados.map(({ valor, etiqueta, icono: Icono, color }) => (
                    <DropdownMenuCheckboxItem
                        key={valor}
                        checked={seleccionados.includes(valor)}
                        onCheckedChange={(marcado) => alternar(valor, marcado === true)}
                        onSelect={(evento) => evento.preventDefault()}
                    >
                        <Icono className={`size-4 ${color}`} />
                        {etiqueta}
                    </DropdownMenuCheckboxItem>
                ))}
                {seleccionados.length > 0 && (
                    <>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem variant="destructive" onSelect={() => alCambiar([])}>
                            <Eraser />
                            Limpiar filtros
                        </DropdownMenuItem>
                    </>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
