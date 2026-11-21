import { Head, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import { EncabezadoApp } from '@/components/encabezado-app';
import { MenuLateral } from '@/components/menu-lateral';
import { Sheet, SheetContent, SheetDescription, SheetTitle } from '@/components/ui/sheet';
import type { DatosLayout } from '@/types';

/**
 * Layout de las pantallas del personal: menú lateral y panel con encabezado.
 * Es persistente; cada página indica su título, ícono y migas con `Pagina.layout`.
 */
export default function AppLayout({ titulo, icono, migas, children }: DatosLayout & { children: ReactNode }) {
    const [menuMovil, setMenuMovil] = useState(false);

    useEffect(() => router.on('navigate', () => setMenuMovil(false)), []);

    return (
        <div className="flex h-dvh bg-surface text-zinc-800 dark:text-zinc-100">
            {titulo && <Head title={titulo} />}

            <MenuLateral className="hidden md:flex" />

            <Sheet open={menuMovil} onOpenChange={setMenuMovil}>
                <SheetContent side="left" className="w-68 border-0 p-0 sm:max-w-68 [&>button]:hidden">
                    <SheetTitle className="sr-only">Menú principal</SheetTitle>
                    <SheetDescription className="sr-only">Navegación del sistema</SheetDescription>
                    <MenuLateral alNavegar={() => setMenuMovil(false)} />
                </SheetContent>
            </Sheet>

            <main className="flex w-full min-w-0 flex-1 flex-col md:py-2 md:pe-2">
                <div className="flex min-h-0 flex-1 flex-col overflow-hidden bg-white md:rounded-2xl md:border md:border-zinc-200/80 md:shadow-[0_1px_2px_rgba(0,0,0,0.04),0_8px_24px_-12px_rgba(0,0,0,0.08)] dark:bg-zinc-900 dark:md:border-white/[0.06] dark:md:shadow-[inset_0_1px_0_rgba(255,255,255,0.03)]">
                    <EncabezadoApp titulo={titulo} icono={icono} migas={migas} alAbrirMenu={() => setMenuMovil(true)} />

                    <div className="scrollbar-fino flex min-h-0 flex-1 flex-col overflow-y-auto" scroll-region="">
                        {titulo && (
                            <div className="flex items-center gap-3 border-b border-zinc-200/80 px-4 py-3 md:hidden dark:border-white/[0.06]">
                                <h1 className="text-base font-semibold tracking-tight text-zinc-900 dark:text-white">{titulo}</h1>
                            </div>
                        )}
                        <div className="flex min-h-0 flex-1 flex-col px-4 pt-5 sm:px-6 sm:pt-6">
                            <div className="flex-1">{children}</div>
                            <div className="h-6 shrink-0" />
                        </div>
                    </div>
                </div>
            </main>
        </div>
    );
}
