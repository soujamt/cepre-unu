import { ChevronRight, Menu } from '@keyline-icons/react';
import { Fragment } from 'react';
import { BotonTema } from '@/components/boton-tema';
import { Enlace } from '@/components/enlace';
import { MenuUsuarioMovil } from '@/components/menu-usuario';
import { Button } from '@/components/ui/button';
import { iconosPagina } from '@/lib/iconos';
import type { NombreIconoPagina } from '@/lib/iconos';
import type { Miga } from '@/types';

/**
 * Ícono de la página en su recuadro, en dos tonos con el color principal.
 */
export function IconoPagina({ nombre }: { nombre: NombreIconoPagina }) {
    const Icono = iconosPagina[nombre];

    return (
        <span className="flex size-10 shrink-0 items-center justify-center rounded-xl border border-index-100 bg-index-50 shadow-[inset_0_1px_0_rgba(255,255,255,0.8)] dark:border-index-500/20 dark:bg-index-500/10 dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.05)]">
            <Icono className="size-5 text-index-500 dark:text-index-300" />
        </span>
    );
}

export function Migas({ migas }: { migas: Miga[] }) {
    return (
        <nav aria-label="Ruta de navegación" className="hidden items-center text-xs text-zinc-500 sm:flex dark:text-zinc-400">
            {migas.map((miga, indice) => (
                <Fragment key={`${miga.titulo}-${indice}`}>
                    {indice > 0 && <ChevronRight className="mx-1 size-3 text-zinc-400" />}
                    {miga.href && indice < migas.length - 1 ? (
                        <Enlace href={miga.href} className="transition-colors hover:text-zinc-900 dark:hover:text-zinc-100">
                            {miga.titulo}
                        </Enlace>
                    ) : (
                        <span className="font-medium text-zinc-700 dark:text-zinc-200">{miga.titulo}</span>
                    )}
                </Fragment>
            ))}
        </nav>
    );
}

/**
 * Barra superior del panel: ícono, título y migas de la página; tema y usuario a la derecha.
 */
export function EncabezadoApp({ titulo, icono, migas = [], alAbrirMenu }: { titulo?: string; icono?: NombreIconoPagina; migas?: Miga[]; alAbrirMenu: () => void }) {
    return (
        <header className="z-10 flex h-16 shrink-0 items-center justify-between border-b border-zinc-200/80 px-4 sm:px-6 dark:border-white/[0.06]">
            <div className="flex min-w-0 items-center gap-3">
                <Button variant="ghost" size="icon" className="md:hidden" onClick={alAbrirMenu} aria-label="Abrir menú">
                    <Menu className="size-6 text-zinc-500 dark:text-zinc-400" />
                </Button>

                <div className="hidden min-w-0 items-center gap-3 md:flex">
                    {icono && <IconoPagina nombre={icono} />}
                    <div className="flex min-w-0 flex-col gap-0.5">
                        {titulo && <h1 className="truncate text-base leading-none font-semibold tracking-tight text-zinc-900 sm:text-lg dark:text-white">{titulo}</h1>}
                        {migas.length > 0 && <Migas migas={migas} />}
                    </div>
                </div>
            </div>

            <div className="flex items-center gap-2">
                <BotonTema />
                <div className="md:hidden">
                    <MenuUsuarioMovil />
                </div>
            </div>
        </header>
    );
}
