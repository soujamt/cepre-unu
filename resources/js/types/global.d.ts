import type { Auth, Aviso, DatosLayout } from '@/types';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            app: { nombre: string; version: string };
            auth: Auth;
            [key: string]: unknown;
        };
        flashDataType: {
            toast?: Aviso;
            /** Segundos que faltan para volver a intentar el inicio de sesión. */
            segundosBloqueo?: number;
        };
        layoutProps: DatosLayout;
    }
}
