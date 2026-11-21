import { MoreVertical } from '@keyline-icons/react';
import { Fragment } from 'react';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import type { Icono } from '@/lib/iconos';

export interface AccionFila {
    etiqueta: string;
    icono: Icono;
    alSeleccionar: () => void;
    /** Acción destructiva (eliminar, anular): se muestra en rojo y separada. */
    peligro?: boolean;
    /** Se oculta si el usuario no tiene el permiso. */
    visible?: boolean;
}

/**
 * Menú de tres puntos de cada fila con las acciones que el usuario puede hacer.
 * No se muestra si no hay ninguna.
 */
export function MenuAcciones({ acciones }: { acciones: AccionFila[] }) {
    const visibles = acciones.filter((accion) => accion.visible !== false);

    if (visibles.length === 0) {
        return null;
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon-sm" aria-label="Acciones">
                    <MoreVertical className="size-5" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="min-w-40">
                {visibles.map(({ etiqueta, icono: IconoAccion, alSeleccionar, peligro }, indice) => (
                    <Fragment key={etiqueta}>
                        {peligro && indice > 0 && <DropdownMenuSeparator />}
                        <DropdownMenuItem variant={peligro ? 'destructive' : 'default'} onSelect={alSeleccionar}>
                            <IconoAccion />
                            {etiqueta}
                        </DropdownMenuItem>
                    </Fragment>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
