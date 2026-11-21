import { createInertiaApp, router } from '@inertiajs/react';
import { toast } from 'sonner';
import { ProveedorConfirmacion } from '@/components/dialogo-confirmacion';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { iniciarTema } from '@/hooks/use-tema';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import PublicoLayout from '@/layouts/publico-layout';

const nombreApp = import.meta.env.VITE_APP_NAME || 'CEPRE UNU';

/** Título de cada tipo de aviso; el mensaje del servidor va debajo, como descripción. */
const titulosAviso = {
    success: 'Listo',
    error: 'No se pudo completar',
    warning: 'Atención',
    info: 'Información',
} as const;

// Avisos enviados por el servidor con Inertia::flash('toast', [...]); también los de la primera carga.
router.on('flash', (evento) => {
    const aviso = evento.detail.flash.toast;

    if (aviso) {
        toast[aviso.type](titulosAviso[aviso.type], { description: aviso.message });
    }
});

void createInertiaApp({
    title: (titulo) => (titulo ? `${titulo} - ${nombreApp}` : nombreApp),
    layout: (nombre) => {
        switch (true) {
            case nombre.startsWith('auth/'):
                return AuthLayout;
            case nombre.startsWith('publico/'):
                return PublicoLayout;
            default:
                return AppLayout;
        }
    },
    strictMode: true,
    withApp(app) {
        return (
            <TooltipProvider delayDuration={200}>
                <ProveedorConfirmacion>
                    {app}
                    <Toaster />
                </ProveedorConfirmacion>
            </TooltipProvider>
        );
    },
    progress: {
        color: '#296ff0',
    },
});

iniciarTema();
