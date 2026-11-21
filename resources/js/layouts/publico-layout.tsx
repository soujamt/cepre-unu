import { Head } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { BotonTema } from '@/components/boton-tema';
import { Enlace } from '@/components/enlace';
import { Marca } from '@/components/marca';
import { portal } from '@/routes/publico';
import type { DatosLayout } from '@/types';

/**
 * Layout del portal del postulante (sin iniciar sesión): una franja oscura con el logo,
 * el título y el subtítulo, y el contenido que se monta sobre ella.
 */
export default function PublicoLayout({ titulo, subtitulo, children }: DatosLayout & { children: ReactNode }) {
    return (
        <div className="min-h-dvh bg-surface pb-16">
            {titulo && <Head title={titulo} />}

            <div className="dark relative isolate overflow-hidden bg-menu">
                <div
                    aria-hidden
                    className="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(60%_120%_at_85%_0%,rgb(41_111_240/0.35),transparent_60%),radial-gradient(40%_80%_at_0%_100%,rgb(41_111_240/0.18),transparent_70%)]"
                />
                <div
                    aria-hidden
                    className="pointer-events-none absolute inset-0 -z-10 bg-[linear-gradient(to_right,rgb(255_255_255/0.04)_1px,transparent_1px),linear-gradient(to_bottom,rgb(255_255_255/0.04)_1px,transparent_1px)] mask-[radial-gradient(70%_100%_at_50%_0%,black,transparent)] bg-size-[32px_32px]"
                />

                <header className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-5">
                    <Enlace href={portal.url()} className="rounded-xl focus-visible:ring-2 focus-visible:ring-white/30 focus-visible:outline-none">
                        <Marca />
                    </Enlace>
                    <BotonTema />
                </header>

                <div className="mx-auto max-w-6xl px-5 pt-6 pb-24 sm:pt-10">
                    {titulo && <h1 className="text-2xl font-semibold tracking-tight text-balance text-white sm:text-3xl">{titulo}</h1>}
                    {subtitulo && <p className="mt-2 max-w-2xl text-sm text-pretty text-zinc-400 sm:text-base">{subtitulo}</p>}
                </div>
            </div>

            <main className="relative mx-auto -mt-16 max-w-6xl px-5">{children}</main>
        </div>
    );
}
