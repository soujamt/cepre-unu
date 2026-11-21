import type * as React from 'react';
import { Alert, CircleCheck, Info, LoaderCircle, OctagonX } from '@keyline-icons/react/fill';
import { Toaster as Sonner, type ToasterProps } from 'sonner';
import { useTema } from '@/hooks/use-tema';

/**
 * Avisos (toasts): tarjeta con el ícono en un recuadro del color del tipo, título,
 * mensaje y una línea de color abajo que marca el tiempo que queda. Abajo a la derecha.
 */
function Toaster(props: ToasterProps) {
    const { oscuro } = useTema();

    return (
        <Sonner
            theme={oscuro ? 'dark' : 'light'}
            className="toaster group"
            position="bottom-right"
            duration={4500}
            visibleToasts={3}
            gap={10}
            style={{ '--width': '364px' } as React.CSSProperties}
            icons={{
                success: <CircleCheck className="size-[18px]" />,
                info: <Info className="size-[18px]" />,
                warning: <Alert className="size-[18px]" />,
                error: <OctagonX className="size-[18px]" />,
                loading: <LoaderCircle className="size-[18px] animate-spin" />,
            }}
            toastOptions={{
                classNames: {
                    toast: 'aviso-toast',
                    content: 'aviso-toast-contenido',
                    title: 'aviso-toast-titulo',
                    description: 'aviso-toast-descripcion',
                },
            }}
            {...props}
        />
    );
}

export { Toaster };
