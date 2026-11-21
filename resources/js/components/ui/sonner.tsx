import { Alert, CircleCheck, Info, LoaderCircle, OctagonX } from '@keyline-icons/react';
import type { CSSProperties } from 'react';
import { Toaster as Sonner, type ToasterProps } from 'sonner';
import { useTema } from '@/hooks/use-tema';

/**
 * Contenedor de avisos (toasts) con el tema del sistema y los íconos de Keyline.
 */
function Toaster(props: ToasterProps) {
    const { oscuro } = useTema();

    return (
        <Sonner
            theme={oscuro ? 'dark' : 'light'}
            className="toaster group"
            position="bottom-right"
            closeButton
            icons={{
                success: <CircleCheck className="size-4 text-green-600 dark:text-green-400" />,
                info: <Info className="size-4 text-sky-600 dark:text-sky-400" />,
                warning: <Alert className="size-4 text-amber-600 dark:text-amber-400" />,
                error: <OctagonX className="size-4 text-red-600 dark:text-red-400" />,
                loading: <LoaderCircle className="size-4 animate-spin" />,
            }}
            style={
                {
                    '--normal-bg': 'var(--popover)',
                    '--normal-text': 'var(--popover-foreground)',
                    '--normal-border': 'var(--border)',
                    '--border-radius': 'calc(var(--radius) + 2px)',
                } as CSSProperties
            }
            {...props}
        />
    );
}

export { Toaster };
