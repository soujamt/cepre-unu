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
        <div className="flex flex-col items-center justify-center gap-3 px-6 py-12 text-center">
            {IconoVacio && (
                <span className="flex size-12 items-center justify-center rounded-2xl border border-zinc-200/80 bg-white text-zinc-400 shadow-card dark:border-white/[0.07] dark:bg-white/[0.03] dark:text-zinc-500">
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
