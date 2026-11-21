import { Head, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import { EncabezadoApp, TituloPagina } from '@/components/encabezado-app';
import { MenuLateral } from '@/components/menu-lateral';
import { Sheet, SheetContent, SheetDescription, SheetTitle } from '@/components/ui/sheet';
import type { DatosLayout } from '@/types';

/**
 * Layout de las pantallas del personal: menú lateral oscuro que enmarca el panel blanco
 * (o gris oscuro en modo oscuro) con la ruta de navegación, el título y el contenido.
 * Es persistente; cada página indica su título, ícono, subtítulo y migas con `Pagina.layout`.
 */
export default function AppLayout({ titulo, subtitulo, icono, migas, children }: DatosLayout & { children: ReactNode }) {
    const [menuMovil, setMenuMovil] = useState(false);

    useEffect(() => router.on('navigate', () => setMenuMovil(false)), []);

    return (
        <div className="flex h-dvh bg-white text-zinc-800 md:bg-menu dark:bg-zinc-900 dark:text-zinc-100 md:dark:bg-menu">
            {titulo && <Head title={titulo} />}

            <MenuLateral className="hidden md:flex" />

            <Sheet open={menuMovil} onOpenChange={setMenuMovil}>
                <SheetContent side="left" className="dark w-72 border-0 bg-menu p-0 sm:max-w-72 [&>button]:hidden">
                    <SheetTitle className="sr-only">Menú principal</SheetTitle>
                    <SheetDescription className="sr-only">Navegación del sistema</SheetDescription>
                    <MenuLateral alNavegar={() => setMenuMovil(false)} className="w-full" />
                </SheetContent>
            </Sheet>

            <main className="flex w-full min-w-0 flex-1 flex-col md:py-2.5 md:pe-2.5">
                <div className="flex min-h-0 flex-1 flex-col overflow-hidden bg-white md:rounded-3xl md:shadow-[0_0_0_1px_rgba(255,255,255,0.04),0_24px_48px_-24px_rgba(0,0,0,0.5)] dark:bg-zinc-900 dark:md:shadow-[0_0_0_1px_rgba(255,255,255,0.06)]">
                    <EncabezadoApp migas={migas} alAbrirMenu={() => setMenuMovil(true)} />

                    <div className="scrollbar-fino flex min-h-0 flex-1 flex-col overflow-y-auto" scroll-region="">
                        <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-6 px-4 pt-6 pb-10 sm:px-8 sm:pt-7">
                            {titulo && <TituloPagina titulo={titulo} subtitulo={subtitulo} icono={icono} />}
                            <div className="flex-1">{children}</div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    );
}
