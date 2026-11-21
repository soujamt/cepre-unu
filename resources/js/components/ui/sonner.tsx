import { Alert, CircleCheck, Info, LoaderCircle, OctagonX } from '@keyline-icons/react';
import { Toaster as Sonner, type ToasterProps } from 'sonner';
import { useTema } from '@/hooks/use-tema';

/**
 * Avisos (toasts) discretos: tarjeta compacta con un punto de color en el ícono,
 * sin botón de cerrar y de corta duración, abajo a la derecha.
 */
function Toaster(props: ToasterProps) {
    const { oscuro } = useTema();

    return (
        <Sonner
            theme={oscuro ? 'dark' : 'light'}
            className="toaster group"
            position="bottom-right"
            duration={3500}
            visibleToasts={3}
            gap={8}
            icons={{
                success: <CircleCheck className="size-4 text-green-500" />,
                info: <Info className="size-4 text-index-500" />,
                warning: <Alert className="size-4 text-amber-500" />,
                error: <OctagonX className="size-4 text-red-500" />,
                loading: <LoaderCircle className="size-4 animate-spin text-zinc-400" />,
            }}
            toastOptions={{
                classNames: {
                    toast: 'aviso-toast',
                    title: 'aviso-toast-titulo',
                    description: 'aviso-toast-descripcion',
                },
            }}
            {...props}
        />
    );
}

export { Toaster };
