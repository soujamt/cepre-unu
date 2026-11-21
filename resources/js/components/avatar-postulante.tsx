import { useState } from 'react';
import { cn } from '@/lib/utils';

/**
 * Foto del postulante en un círculo; si no tiene foto o no carga, sus iniciales.
 */
export function AvatarPostulante({ foto, iniciales, nombre, className }: { foto: string | null | undefined; iniciales: string; nombre: string; className?: string }) {
    const [fallo, setFallo] = useState(false);

    if (foto && !fallo) {
        return (
            <img
                src={foto}
                alt={`Foto de ${nombre}`}
                loading="lazy"
                onError={() => setFallo(true)}
                className={cn(
                    'size-10 shrink-0 rounded-full bg-zinc-100 object-cover object-top shadow-[0_0_0_1px_rgba(16,24,40,0.08)] ring-2 ring-white dark:bg-white/[0.06] dark:shadow-[0_0_0_1px_rgba(255,255,255,0.1)] dark:ring-zinc-900',
                    className,
                )}
            />
        );
    }

    return (
        <span
            title="Sin foto"
            className={cn(
                'flex size-10 shrink-0 items-center justify-center rounded-full bg-linear-to-b from-index-50 to-index-100 text-xs font-semibold text-index-700 ring-1 ring-index-200/60 dark:from-index-500/15 dark:to-index-500/5 dark:text-index-200 dark:ring-index-400/20',
                className,
            )}
        >
            {iniciales || '?'}
        </span>
    );
}
