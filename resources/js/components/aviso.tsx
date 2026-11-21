import { Alert, CircleCheck, Info, OctagonAlert } from '@keyline-icons/react/fill';
import type { ReactNode } from 'react';
import type { Icono } from '@/lib/iconos';
import { cn } from '@/lib/utils';

export type TonoAviso = 'info' | 'exito' | 'aviso' | 'peligro';

const estilos: Record<TonoAviso, { icono: Icono; color: string }> = {
    info: { icono: Info, color: 'bg-index-500/10 text-index-500 dark:text-index-300' },
    exito: { icono: CircleCheck, color: 'bg-green-500/10 text-green-600 dark:text-green-400' },
    aviso: { icono: Alert, color: 'bg-amber-500/10 text-amber-500 dark:text-amber-400' },
    peligro: { icono: OctagonAlert, color: 'bg-red-500/10 text-red-500 dark:text-red-400' },
};

/**
 * Aviso dentro de una página: tarjeta neutra donde el color queda solo en el recuadro
 * del ícono, para que informe sin robar la atención.
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
                'flex items-start gap-3 rounded-2xl border border-zinc-200/80 bg-zinc-50/60 p-3 pe-4 dark:border-white/[0.07] dark:bg-white/[0.02]',
                accion && 'sm:items-center',
                className,
            )}
        >
            <span className={cn('flex size-8 shrink-0 items-center justify-center rounded-lg', estilos[tono].color)}>
                <IconoAviso className="size-4" />
            </span>
            <div className={cn('min-w-0 flex-1 space-y-0.5 text-sm', !titulo && 'pt-1.5')}>
                {titulo && <p className="font-medium text-zinc-800 dark:text-zinc-100">{titulo}</p>}
                {children && <div className="text-pretty text-zinc-500 dark:text-zinc-400">{children}</div>}
            </div>
            {accion && <div className="shrink-0">{accion}</div>}
        </div>
    );
}
