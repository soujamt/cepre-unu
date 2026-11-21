import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

interface PropsEnlace {
    href: string;
    children: ReactNode;
    className?: string;
    title?: string;
    'aria-label'?: string;
    onClick?: () => void;
    /** Precarga la página al pasar el mouse. */
    prefetch?: boolean;
}

/**
 * Enlace interno: navega entre pantallas con Inertia, sin recargar la página.
 */
export function Enlace({ href, prefetch, ...props }: PropsEnlace) {
    return <Link href={href} prefetch={prefetch} {...props} />;
}
