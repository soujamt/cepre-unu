import type { ReactNode } from 'react';
import type { Icono } from '@/lib/iconos';
import { cn } from '@/lib/utils';

export type Tono = 'neutro' | 'exito' | 'aviso' | 'peligro' | 'info';

/** Recuadro del ícono: degradado del color del tono con brillo interior. */
export const clasesRecuadroTono: Record<Tono, string> = {
    info: 'from-[#4f8bff] to-[#2264dc] ring-[#1b54bd]/30 shadow-[0_4px_10px_-3px_rgba(41,111,240,0.5),inset_0_1px_0_rgba(255,255,255,0.35)]',
    exito: 'from-[#34d399] to-[#10b981] ring-[#059669]/30 shadow-[0_4px_10px_-3px_rgba(16,185,129,0.45),inset_0_1px_0_rgba(255,255,255,0.35)]',
    aviso: 'from-[#fbbf24] to-[#f59e0b] ring-[#d97706]/30 shadow-[0_4px_10px_-3px_rgba(245,158,11,0.45),inset_0_1px_0_rgba(255,255,255,0.4)]',
    peligro: 'from-[#fb7185] to-[#f43f5e] ring-[#e11d48]/30 shadow-[0_4px_10px_-3px_rgba(244,63,94,0.45),inset_0_1px_0_rgba(255,255,255,0.35)]',
    neutro: 'from-zinc-500 to-zinc-700 ring-zinc-800/25 shadow-[0_4px_10px_-3px_rgba(39,39,42,0.4),inset_0_1px_0_rgba(255,255,255,0.25)]',
};

const clasesDetalle: Record<Tono, string> = {
    exito: 'text-green-600 dark:text-green-400',
    aviso: 'text-amber-600 dark:text-amber-400',
    peligro: 'text-red-600 dark:text-red-400',
    info: 'text-index-600 dark:text-index-300',
    neutro: 'text-zinc-500 dark:text-zinc-400',
};

const columnasGrilla = {
    2: 'sm:grid-cols-2',
    3: 'sm:grid-cols-3',
    4: 'sm:grid-cols-2 xl:grid-cols-4',
    5: 'sm:grid-cols-2 lg:grid-cols-5',
} as const;

/**
 * Recuadro con el ícono en blanco sobre el degradado del tono.
 */
export function RecuadroIcono({ icono: IconoRecuadro, tono = 'info', className }: { icono: Icono; tono?: Tono; className?: string }) {
    return (
        <span className={cn('flex size-9 shrink-0 items-center justify-center rounded-[10px] bg-linear-to-b ring-1', clasesRecuadroTono[tono], className)}>
            <IconoRecuadro className="size-[18px] text-white" />
        </span>
    );
}

/**
 * Grilla de indicadores.
 */
export function Resumen({ columnas = 4, children, className }: { columnas?: keyof typeof columnasGrilla; children: ReactNode; className?: string }) {
    return <div className={cn('grid grid-cols-1 gap-4', columnasGrilla[columnas], className)}>{children}</div>;
}

interface PropsResumenItem {
    etiqueta: string;
    valor: ReactNode;
    icono?: Icono;
    tono?: Tono;
    detalle?: ReactNode;
    iconoDetalle?: Icono;
    /** Tarjeta resaltada con un borde del color principal. */
    destacado?: boolean;
    title?: string;
    /** Convierte la tarjeta en un botón, por ejemplo para filtrar un listado. */
    alPulsar?: () => void;
    activo?: boolean;
}

/**
 * Indicador numérico: encabezado con el recuadro del ícono y la etiqueta, y debajo
 * el número grande con una línea de detalle en el color del tono.
 */
export function ResumenItem({ etiqueta, valor, icono: IconoItem, tono = 'neutro', detalle, iconoDetalle: IconoDetalle, destacado = false, title, alPulsar, activo = false }: PropsResumenItem) {
    const contenido = (
        <>
            <div className="flex items-center gap-3 px-4 pt-4 pb-3.5">
                {IconoItem && <RecuadroIcono icono={IconoItem} tono={tono} />}
                <p className="min-w-0 truncate text-sm font-medium text-zinc-700 dark:text-zinc-200">{etiqueta}</p>
            </div>
            <div className="flex flex-1 flex-col justify-end gap-1.5 border-t border-zinc-100 px-4 pt-3.5 pb-4 dark:border-white/[0.05]">
                <p className="text-[28px] leading-none font-semibold tracking-tight text-zinc-900 tabular-nums dark:text-white">{valor}</p>
                {detalle && (
                    <p className={cn('flex min-h-5 items-center gap-1 text-[13px] font-medium', clasesDetalle[tono])}>
                        {IconoDetalle && <IconoDetalle className="size-3.5 shrink-0" />}
                        <span className="truncate">{detalle}</span>
                    </p>
                )}
            </div>
        </>
    );

    const clases = cn(
        'flex min-w-0 flex-col rounded-2xl border bg-white text-start shadow-card transition-[box-shadow,border-color] dark:bg-white/[0.02] dark:shadow-none',
        destacado || activo ? 'border-index-300 ring-3 ring-index-500/10 dark:border-index-500/40' : 'border-zinc-200/80 dark:border-white/[0.07]',
    );

    return alPulsar ? (
        <button type="button" title={title} onClick={alPulsar} aria-pressed={activo} className={cn(clases, 'hover:border-zinc-300 hover:shadow-card-hover dark:hover:border-white/15')}>
            {contenido}
        </button>
    ) : (
        <div title={title} className={clases}>
            {contenido}
        </div>
    );
}
