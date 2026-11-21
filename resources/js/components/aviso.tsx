import { Alert, CircleCheck, Info, OctagonAlert } from '@keyline-icons/react/fill';
import type { ReactNode } from 'react';
import type { Icono } from '@/lib/iconos';
import { cn } from '@/lib/utils';

export type TonoAviso = 'info' | 'exito' | 'aviso' | 'peligro';

const estilos: Record<TonoAviso, { icono: Icono; caja: string; recuadro: string }> = {
    info: {
        icono: Info,
        caja: 'border-index-200/70 from-index-50/90 dark:border-index-500/20 dark:from-index-500/[0.09]',
        recuadro: 'from-index-400 to-index-600 shadow-[0_4px_10px_-3px_rgba(41,111,240,0.55)]',
    },
    exito: {
        icono: CircleCheck,
        caja: 'border-green-200/70 from-green-50/90 dark:border-green-500/20 dark:from-green-500/[0.08]',
        recuadro: 'from-[#34d399] to-[#059669] shadow-[0_4px_10px_-3px_rgba(16,185,129,0.55)]',
    },
    aviso: {
        icono: Alert,
        caja: 'border-amber-200/80 from-amber-50/90 dark:border-amber-500/20 dark:from-amber-500/[0.08]',
        recuadro: 'from-[#fbbf24] to-[#f59e0b] shadow-[0_4px_10px_-3px_rgba(245,158,11,0.55)]',
    },
    peligro: {
        icono: OctagonAlert,
        caja: 'border-red-200/70 from-red-50/90 dark:border-red-500/20 dark:from-red-500/[0.08]',
        recuadro: 'from-[#f87171] to-[#dc2626] shadow-[0_4px_10px_-3px_rgba(220,38,38,0.5)]',
    },
};

/**
 * Aviso dentro de una página: un tinte suave del color del tono que se desvanece hacia
 * la derecha y el ícono en un recuadro con degradado. Informa sin gritar.
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
                'relative flex items-start gap-3.5 overflow-hidden rounded-2xl border bg-linear-to-r to-white to-60% p-3.5 pe-4 shadow-[0_1px_2px_rgba(16,24,40,0.04)] dark:to-transparent dark:shadow-none',
                estilos[tono].caja,
                accion && 'sm:items-center',
                className,
            )}
        >
            <span
                className={cn(
                    'flex size-9 shrink-0 items-center justify-center rounded-xl bg-linear-to-b text-white ring-1 ring-white/20 ring-inset',
                    estilos[tono].recuadro,
                )}
            >
                <IconoAviso className="size-[18px] drop-shadow-[0_1px_1px_rgba(0,0,0,0.15)]" />
            </span>
            <div className={cn('min-w-0 flex-1 space-y-0.5 text-sm', !titulo && 'pt-2')}>
                {titulo && <p className="font-semibold text-zinc-900 dark:text-white">{titulo}</p>}
                {children && <div className="text-[13px] leading-relaxed text-pretty text-zinc-600 dark:text-zinc-300">{children}</div>}
            </div>
            {accion && <div className="shrink-0">{accion}</div>}
        </div>
    );
}
