import type { ReactNode } from 'react';
import type { Icono } from '@/lib/iconos';
import { cn } from '@/lib/utils';

type Tono = 'neutro' | 'exito' | 'aviso' | 'peligro' | 'info';

const clasesDetalle: Record<Tono, string> = {
    exito: 'bg-green-50 text-green-700 ring-green-600/15 dark:bg-green-500/10 dark:text-green-400 dark:ring-green-400/20',
    aviso: 'bg-amber-50 text-amber-700 ring-amber-600/15 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-400/20',
    peligro: 'bg-red-50 text-red-700 ring-red-600/15 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-400/20',
    info: 'bg-index-50 text-index-600 ring-index-500/15 dark:bg-index-500/10 dark:text-index-300 dark:ring-index-400/20',
    neutro: 'bg-zinc-100 text-zinc-600 ring-zinc-500/10 dark:bg-white/5 dark:text-zinc-400 dark:ring-white/10',
};

const clasesIcono: Record<Tono, string> = {
    exito: 'text-green-500',
    aviso: 'text-amber-500',
    peligro: 'text-red-500',
    info: 'text-index-500',
    neutro: 'text-zinc-400 dark:text-zinc-500',
};

const columnasGrilla = {
    2: 'sm:grid-cols-2',
    3: 'sm:grid-cols-3',
    4: 'sm:grid-cols-2 xl:grid-cols-4',
    5: 'sm:grid-cols-2 lg:grid-cols-5',
} as const;

/**
 * Bandeja de indicadores: las tarjetas van juntas sobre un fondo gris.
 */
export function Resumen({ columnas = 4, children, className }: { columnas?: keyof typeof columnasGrilla; children: ReactNode; className?: string }) {
    return (
        <div className={cn('rounded-xl border border-zinc-200/80 bg-zinc-100/70 p-1 dark:border-white/[0.06] dark:bg-zinc-950/60', className)}>
            <div className={cn('grid grid-cols-1 gap-1', columnasGrilla[columnas])}>{children}</div>
        </div>
    );
}

interface PropsResumenItem {
    etiqueta: string;
    valor: ReactNode;
    icono?: Icono;
    tono?: Tono;
    detalle?: ReactNode;
    iconoDetalle?: Icono;
    /** Primera tarjeta resaltada con el color principal. */
    destacado?: boolean;
    title?: string;
    /** Convierte la tarjeta en un botón, por ejemplo para filtrar un listado. */
    alPulsar?: () => void;
    activo?: boolean;
}

/**
 * Indicador numérico de un <Resumen>.
 */
export function ResumenItem({ etiqueta, valor, icono: IconoItem, tono = 'neutro', detalle, iconoDetalle: IconoDetalle, destacado = false, title, alPulsar, activo = false }: PropsResumenItem) {
    const contenido = (
        <>
            <div className="flex items-start justify-between gap-3">
                <p className={cn('text-sm font-medium', destacado ? 'text-index-600 dark:text-index-300' : 'text-zinc-600 dark:text-zinc-400')}>{etiqueta}</p>
                {IconoItem && <IconoItem className={cn('size-5 shrink-0', destacado ? 'text-index-500 dark:text-index-300' : clasesIcono[tono])} />}
            </div>
            <p className={cn('text-3xl leading-none font-semibold tracking-tight tabular-nums', destacado ? 'text-index-600 dark:text-index-300' : 'text-zinc-900 dark:text-white')}>{valor}</p>
            {detalle && (
                <div className="flex min-h-5 flex-wrap items-center gap-1.5">
                    <span className={cn('inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-xs font-medium ring-1 ring-inset', clasesDetalle[tono])}>
                        {IconoDetalle && <IconoDetalle className="size-3.5 shrink-0" />}
                        {detalle}
                    </span>
                </div>
            )}
        </>
    );

    const clases = cn(
        'flex min-w-0 flex-col justify-between gap-3 rounded-md border p-4 text-start transition-shadow',
        destacado || activo
            ? 'border-index-200 bg-index-50/70 shadow-card dark:border-index-500/25 dark:bg-index-500/10'
            : 'border-zinc-200/80 bg-white shadow-card dark:border-white/[0.06] dark:bg-zinc-900 dark:shadow-none',
    );

    return alPulsar ? (
        <button type="button" title={title} onClick={alPulsar} aria-pressed={activo} className={cn(clases, 'hover:shadow-card-hover')}>
            {contenido}
        </button>
    ) : (
        <div title={title} className={clases}>
            {contenido}
        </div>
    );
}
