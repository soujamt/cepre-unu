import { Menu } from '@keyline-icons/react/fill';
import { Fragment } from 'react';
import { BotonTema } from '@/components/boton-tema';
import { Enlace } from '@/components/enlace';
import { MenuUsuarioMovil } from '@/components/menu-usuario';
import { Button } from '@/components/ui/button';
import { iconos } from '@/lib/iconos';
import type { NombreIcono } from '@/lib/iconos';
import { cn } from '@/lib/utils';
import type { Miga } from '@/types';

/**
 * Ícono de la pantalla en un recuadro azul con degradado y brillo interior, como el botón principal.
 */
export function IconoPagina({ nombre, className }: { nombre: NombreIcono; className?: string }) {
    const Icono = iconos[nombre];

    return (
        <span
            className={cn(
                'flex size-11 shrink-0 items-center justify-center rounded-xl bg-linear-to-b from-index-400 to-index-600 shadow-[0_4px_10px_-3px_rgba(41,111,240,0.55),inset_0_1px_0_rgba(255,255,255,0.35)] ring-1 ring-index-600/30',
                className,
            )}
        >
            <Icono className="size-5 text-white" />
        </span>
    );
}

/**
 * Ruta de navegación al estilo "Inicio / Mantenedores / Carreras": los niveles previos en gris.
 */
export function Migas({ migas }: { migas: Miga[] }) {
    return (
        <nav aria-label="Ruta de navegación" className="flex min-w-0 items-center gap-1.5 text-sm">
            {migas.map((miga, indice) => {
                const ultima = indice === migas.length - 1;

                return (
                    <Fragment key={`${miga.titulo}-${indice}`}>
                        {indice > 0 && <span className="text-zinc-300 dark:text-zinc-600">/</span>}
                        {miga.href && !ultima ? (
                            <Enlace href={miga.href} className="truncate text-zinc-500 transition-colors hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">
                                {miga.titulo}
                            </Enlace>
                        ) : (
                            <span className={cn('truncate', ultima ? 'font-medium text-zinc-900 dark:text-white' : 'text-zinc-500 dark:text-zinc-400')}>{miga.titulo}</span>
                        )}
                    </Fragment>
                );
            })}
        </nav>
    );
}

/**
 * Barra superior del panel: menú (en celular) y ruta de navegación a la izquierda; tema y usuario a la derecha.
 */
export function EncabezadoApp({ migas = [], alAbrirMenu }: { migas?: Miga[]; alAbrirMenu: () => void }) {
    return (
        <header className="z-10 flex h-15 shrink-0 items-center justify-between gap-3 border-b border-zinc-200/70 px-4 sm:px-8 dark:border-white/[0.06]">
            <div className="flex min-w-0 items-center gap-2">
                <Button variant="ghost" size="icon" className="-ms-2 md:hidden" onClick={alAbrirMenu} aria-label="Abrir menú">
                    <Menu className="size-5.5 text-zinc-500 dark:text-zinc-400" />
                </Button>
                {migas.length > 0 && <Migas migas={migas} />}
            </div>

            <div className="flex items-center gap-1.5">
                <BotonTema />
                <div className="md:hidden">
                    <MenuUsuarioMovil />
                </div>
            </div>
        </header>
    );
}

/**
 * Título de la pantalla con su ícono y una línea que explica para qué sirve.
 */
export function TituloPagina({ titulo, subtitulo, icono }: { titulo: string; subtitulo?: string; icono?: NombreIcono }) {
    return (
        <div className="flex items-center gap-4">
            {icono && <IconoPagina nombre={icono} />}
            <div className="min-w-0 space-y-0.5">
                <h1 className="text-xl font-semibold tracking-tight text-zinc-900 dark:text-white">{titulo}</h1>
                {subtitulo && <p className="text-sm text-pretty text-zinc-500 dark:text-zinc-400">{subtitulo}</p>}
            </div>
        </div>
    );
}
