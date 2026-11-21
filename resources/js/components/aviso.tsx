import { Alert, CircleCheck, Info, OctagonAlert } from '@keyline-icons/react';
import type { ReactNode } from 'react';
import type { Icono } from '@/lib/iconos';
import { cn } from '@/lib/utils';

export type TonoAviso = 'info' | 'exito' | 'aviso' | 'peligro';

const estilos: Record<TonoAviso, { icono: Icono; color: string }> = {
    info: { icono: Info, color: 'text-index-500' },
    exito: { icono: CircleCheck, color: 'text-green-500' },
    aviso: { icono: Alert, color: 'text-amber-500' },
    peligro: { icono: OctagonAlert, color: 'text-red-500' },
};

/**
 * Aviso dentro de una página: tarjeta neutra donde el color queda solo en el ícono,
 * para que informe sin robar la atención.
 */
export function Aviso({
    tono = 'info',
    titulo,
    children,
    icono,
    accion,
    className,
}: {
    tono?: TonoAviso;
    titulo?: ReactNode;
    children?: ReactNode;
    icono?: Icono;
    accion?: ReactNode;
    className?: string;
}) {
    const IconoAviso = icono ?? estilos[tono].icono;

    return (
        <div
            role={tono === 'peligro' ? 'alert' : 'status'}
            className={cn(
                'flex items-start gap-3 rounded-xl border border-zinc-200/80 bg-zinc-50/60 px-4 py-3 dark:border-white/[0.07] dark:bg-white/[0.02]',
                className,
            )}
        >
            <IconoAviso className={cn('mt-0.5 size-4 shrink-0', estilos[tono].color)} />
            <div className="min-w-0 flex-1 space-y-0.5 text-sm">
                {titulo && <p className="font-medium text-zinc-800 dark:text-zinc-100">{titulo}</p>}
                {children && <div className="text-pretty text-zinc-500 dark:text-zinc-400">{children}</div>}
            </div>
            {accion && <div className="shrink-0">{accion}</div>}
        </div>
    );
}
