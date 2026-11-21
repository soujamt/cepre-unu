import { Head } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { BotonTema } from '@/components/boton-tema';
import { Enlace } from '@/components/enlace';
import { Marca } from '@/components/marca';
import { portal } from '@/routes/publico';
import type { DatosLayout } from '@/types';

/**
 * Layout del portal del postulante (sin iniciar sesión): cabecera con el logo,
 * título de la página y contenido centrado.
 */
export default function PublicoLayout({ titulo, subtitulo, children }: DatosLayout & { children: ReactNode }) {
    return (
        <div className="min-h-dvh bg-zinc-50 pb-16 dark:bg-zinc-950">
            {titulo && <Head title={titulo} />}

            <header className="border-b border-zinc-200/80 bg-white dark:border-white/10 dark:bg-zinc-900">
                <div className="mx-auto flex max-w-5xl items-center justify-between gap-4 px-5 py-4">
                    <Enlace href={portal.url()}>
                        <Marca />
                    </Enlace>
                    <BotonTema />
                </div>
            </header>

            {titulo && (
                <div className="mx-auto max-w-5xl px-5 pt-8 pb-2">
                    <h1 className="text-2xl font-semibold tracking-tight text-balance text-zinc-900 dark:text-white">{titulo}</h1>
                    {subtitulo && <p className="mt-1 text-sm text-pretty text-zinc-500 dark:text-zinc-400">{subtitulo}</p>}
                </div>
            )}

            <main className="mx-auto max-w-5xl px-5 pt-4">{children}</main>
        </div>
    );
}
