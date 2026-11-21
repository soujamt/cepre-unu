import { cn } from '@/lib/utils';

/**
 * Logo de la UNU con el nombre del sistema.
 */
export function Marca({ className, tamano = 'md' }: { className?: string; tamano?: 'md' | 'lg' }) {
    return (
        <div className={cn('flex min-w-0 items-center gap-3', className)}>
            <span
                className={cn(
                    'flex shrink-0 items-center justify-center rounded-xl border border-zinc-200/80 bg-white shadow-xs dark:border-white/10 dark:bg-zinc-900 dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.06)]',
                    tamano === 'lg' ? 'size-11' : 'size-10',
                )}
            >
                <img src="/assets/media/logo_unu.png" alt="Logo UNU" className={tamano === 'lg' ? 'w-7' : 'w-6'} />
            </span>
            <span className="min-w-0 leading-tight">
                <span className={cn('block font-semibold text-zinc-900 dark:text-white', tamano === 'lg' ? 'text-base' : 'text-[15px]')}>CEPRE UNU</span>
                <span className="block truncate text-xs text-zinc-500 dark:text-zinc-400">Centro Preuniversitario</span>
            </span>
        </div>
    );
}
