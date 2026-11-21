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
        <section className={cn('rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-card sm:p-6 dark:border-white/[0.07] dark:bg-white/[0.02] dark:shadow-none', className)}>
            {(titulo || acciones) && (
                <header className="mb-5 flex flex-wrap items-start justify-between gap-3">
                    <div className="min-w-0 space-y-0.5">
                        {titulo && <h2 className="text-[15px] font-semibold text-zinc-900 dark:text-white">{titulo}</h2>}
                        {descripcion && <p className="text-sm text-pretty text-zinc-500 dark:text-zinc-400">{descripcion}</p>}
                    </div>
                    {acciones && <div className="flex shrink-0 items-center gap-2">{acciones}</div>}
                </header>
            )}
            {children}
        </section>
    );
}

/**
 * Mensaje para listados sin resultados o secciones sin datos.
 */
export function EstadoVacio({ icono: IconoVacio, titulo, descripcion, accion }: { icono?: Icono; titulo: string; descripcion?: ReactNode; accion?: ReactNode }) {
    return (
        <div className="flex flex-col items-center justify-center gap-4 px-6 py-14 text-center">
            {IconoVacio && (
                <span className="relative flex size-20 items-center justify-center">
                    <span aria-hidden className="absolute inset-0 rounded-full border border-dashed border-zinc-200 dark:border-white/10" />
                    <span aria-hidden className="absolute inset-3 rounded-full bg-zinc-50 dark:bg-white/[0.03]" />
                    <span className="relative flex size-11 items-center justify-center rounded-2xl border border-zinc-200/80 bg-white text-zinc-400 shadow-[0_4px_12px_-4px_rgba(16,24,40,0.12)] dark:border-white/[0.08] dark:bg-zinc-800 dark:text-zinc-400 dark:shadow-none">
                        <IconoVacio className="size-5.5" />
                    </span>
                </span>
            )}
            <div className="space-y-1">
                <p className="text-sm font-semibold text-zinc-900 dark:text-white">{titulo}</p>
                {descripcion && <p className="max-w-sm text-[13px] text-pretty text-zinc-500 dark:text-zinc-400">{descripcion}</p>}
            </div>
            {accion}
        </div>
    );
}
