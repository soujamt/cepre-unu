import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';

/**
 * Indica si la URL es una pantalla que todavía es de Livewire.
 */
export function useEsRutaLivewire(): (href: string) => boolean {
    const { rutasLivewire } = usePage().props;
    const expresiones = (rutasLivewire ?? []).map((patron) => new RegExp(patron));

    return (href: string): boolean => expresiones.some((expresion) => expresion.test(new URL(href, window.location.origin).pathname));
}

interface PropsEnlace {
    href: string;
    children: ReactNode;
    className?: string;
    title?: string;
    'aria-label'?: string;
    onClick?: () => void;
    /** Precarga la página al pasar el mouse (solo pantallas de React). */
    prefetch?: boolean;
}

/**
 * Enlace interno durante la migración: navega con Inertia si el destino es una
 * pantalla de React y hace una carga completa si todavía es de Livewire.
 */
export function Enlace({ href, prefetch, ...props }: PropsEnlace) {
    const esLivewire = useEsRutaLivewire();

    return esLivewire(href) ? <a href={href} {...props} /> : <Link href={href} prefetch={prefetch} {...props} />;
}
