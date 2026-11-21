import type { ReactNode } from 'react';
import type { Icono } from '@/lib/iconos';
import { cn } from '@/lib/utils';

/**
 * Superficie blanca con borde y sombra suave, la base de las secciones de cada página.
 */
export function Panel({
    titulo,
    descripcion,
    acciones,
    children,
    className,
}: {
    titulo?: ReactNode;
    descripcion?: ReactNode;
    acciones?: ReactNode;
    children: ReactNode;
    className?: string;
}) {
    return (
        <section className={cn('rounded-xl border border-zinc-200/80 bg-white p-5 shadow-card dark:border-white/[0.06] dark:bg-zinc-900', className)}>
            {(titulo || acciones) && (
                <header className="mb-4 flex flex-wrap items-start justify-between gap-3">
                    <div className="min-w-0 space-y-1">
                        {titulo && <h2 className="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">{titulo}</h2>}
                        {descripcion && <p className="text-sm text-pretty text-zinc-500 dark:text-zinc-400">{descripcion}</p>}
                    </div>
                    {acciones && <div className="flex shrink-0 items-center gap-2">{acciones}</div>}
                </header>
            )}
            {children}
        </section>
    );
}

const tonos = {
    neutro: 'bg-zinc-100 text-zinc-500 dark:bg-white/10 dark:text-zinc-300',
    primario: 'bg-index-50 text-index-600 dark:bg-index-500/10 dark:text-index-300',
    exito: 'bg-green-500/10 text-green-600 dark:text-green-400',
    aviso: 'bg-amber-500/10 text-amber-600 dark:text-amber-300',
    peligro: 'bg-red-500/10 text-red-600 dark:text-red-400',
} as const;

/**
 * Indicador numérico del inicio y de los listados (por ejemplo, "Activas 28").
 */
export function TarjetaIndicador({
    etiqueta,
    valor,
    detalle,
    icono: IconoIndicador,
    tono = 'neutro',
    activo = false,
    alPulsar,
}: {
    etiqueta: string;
    valor: ReactNode;
    detalle?: ReactNode;
    icono?: Icono;
    tono?: keyof typeof tonos;
    activo?: boolean;
    alPulsar?: () => void;
}) {
    const Contenido = (
        <>
            <div className="flex items-start justify-between gap-3">
                <p className="text-sm font-medium text-zinc-500 dark:text-zinc-400">{etiqueta}</p>
                {IconoIndicador && (
                    <span className={cn('flex size-8 shrink-0 items-center justify-center rounded-lg', tonos[tono])}>
                        <IconoIndicador className="size-4.5" />
                    </span>
                )}
            </div>
            <p className="mt-2 text-2xl font-semibold tracking-tight text-zinc-900 tabular-nums dark:text-white">{valor}</p>
            {detalle && <div className="mt-1.5 text-xs text-zinc-500 dark:text-zinc-400">{detalle}</div>}
        </>
    );

    const clases = cn(
        'rounded-xl border bg-white p-4 text-start shadow-card dark:bg-zinc-900',
        activo ? 'border-index-300 ring-1 ring-index-300 dark:border-index-500/50 dark:ring-index-500/50' : 'border-zinc-200/80 dark:border-white/[0.06]',
    );

    return alPulsar ? (
        <button type="button" onClick={alPulsar} aria-pressed={activo} className={cn(clases, 'transition-colors hover:border-zinc-300 dark:hover:border-white/15')}>
            {Contenido}
        </button>
    ) : (
        <div className={clases}>{Contenido}</div>
    );
}

/**
 * Mensaje para listados sin resultados o secciones sin datos.
 */
export function EstadoVacio({ icono: IconoVacio, titulo, descripcion, accion }: { icono?: Icono; titulo: string; descripcion?: ReactNode; accion?: ReactNode }) {
    return (
        <div className="flex flex-col items-center justify-center gap-3 px-6 py-12 text-center">
            {IconoVacio && (
                <span className="flex size-12 items-center justify-center rounded-xl bg-zinc-100 text-zinc-400 dark:bg-white/5 dark:text-zinc-500">
                    <IconoVacio className="size-6" />
                </span>
            )}
            <div className="space-y-1">
                <p className="text-sm font-medium text-zinc-900 dark:text-white">{titulo}</p>
                {descripcion && <p className="max-w-sm text-sm text-pretty text-zinc-500 dark:text-zinc-400">{descripcion}</p>}
            </div>
            {accion}
        </div>
    );
}
