import { Link, usePage } from '@inertiajs/react';
import { ChevronDown } from '@keyline-icons/react';
import { useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import { Marca } from '@/components/marca';
import { TarjetaUsuario } from '@/components/menu-usuario';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { usePermiso } from '@/hooks/use-permiso';
import { iconosMenu } from '@/lib/iconos';
import { gruposMenu, hrefActivo, itemInicio } from '@/lib/navegacion';
import type { GrupoMenu, ItemMenu } from '@/lib/navegacion';
import { cn } from '@/lib/utils';

/**
 * Enlace del menú: navegación de Inertia si la pantalla ya está migrada y carga
 * completa si todavía es de Livewire.
 */
function EnlaceMenu({ item, className, alNavegar, children }: { item: ItemMenu; className: string; alNavegar?: () => void; children: ReactNode }) {
    if (item.inertia) {
        return (
            <Link href={item.href} prefetch className={className} onClick={alNavegar}>
                {children}
            </Link>
        );
    }

    return (
        <a href={item.href} className={className}>
            {children}
        </a>
    );
}

function Grupo({ grupo, activo, hrefActual, alNavegar }: { grupo: GrupoMenu; activo: boolean; hrefActual: string | null; alNavegar?: () => void }) {
    const [abierto, setAbierto] = useState(activo);

    // Al navegar a una pantalla del grupo, el grupo se abre (el layout se conserva entre páginas).
    useEffect(() => {
        if (activo) {
            setAbierto(true);
        }
    }, [activo]);
    const { trazo: IconoTrazo, relleno: IconoRelleno } = iconosMenu[grupo.icono];
    const IconoGrupo = activo ? IconoRelleno : IconoTrazo;

    return (
        <Collapsible open={abierto} onOpenChange={setAbierto}>
            <CollapsibleTrigger
                className={cn(
                    'group flex h-9 w-full items-center gap-2.5 rounded-lg px-2.5 text-sm transition-colors hover:bg-zinc-200/60 hover:text-zinc-900 dark:hover:bg-white/[0.04] dark:hover:text-zinc-100',
                    activo ? 'font-medium text-zinc-900 dark:text-white' : 'text-zinc-600 dark:text-zinc-400',
                )}
            >
                <IconoGrupo
                    className={cn(
                        'size-5 transition-colors',
                        activo ? 'text-index-500 dark:text-index-400' : 'text-zinc-400 group-hover:text-zinc-600 dark:text-zinc-500 dark:group-hover:text-zinc-300',
                    )}
                />
                <span>{grupo.titulo}</span>
                <ChevronDown className={cn('ms-auto size-4 text-zinc-400 transition-transform duration-200 dark:text-zinc-500', abierto && 'rotate-180')} />
            </CollapsibleTrigger>
            <CollapsibleContent className="relative ms-[1.2rem] mt-0.5 mb-1 flex flex-col gap-0.5 border-s border-zinc-200 ps-2.5 dark:border-white/[0.08]">
                {grupo.items.map((item) => {
                    const actual = item.href === hrefActual;

                    return (
                        <EnlaceMenu
                            key={item.href}
                            item={item}
                            alNavegar={alNavegar}
                            className={cn(
                                'relative flex h-8 items-center rounded-md px-2.5 text-[13px] transition-colors',
                                actual
                                    ? 'bg-index-50 font-medium text-index-600 dark:bg-index-500/10 dark:text-index-300'
                                    : 'text-zinc-500 hover:bg-zinc-200/60 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-white/[0.04] dark:hover:text-zinc-100',
                            )}
                        >
                            {actual && <span className="absolute -start-[11px] top-1.5 bottom-1.5 w-0.5 rounded-full bg-index-500" />}
                            {item.titulo}
                        </EnlaceMenu>
                    );
                })}
            </CollapsibleContent>
        </Collapsible>
    );
}

/**
 * Menú lateral con los mismos grupos y permisos que el de las pantallas Livewire.
 */
export function MenuLateral({ alNavegar, className }: { alNavegar?: () => void; className?: string }) {
    const { url } = usePage();
    const puede = usePermiso();
    const anio = new Date().getFullYear();
    const { version } = usePage().props.app;

    const grupos = gruposMenu
        .map((grupo) => ({ ...grupo, items: grupo.items.filter((item) => !item.permiso || puede(item.permiso)) }))
        .filter((grupo) => grupo.items.length > 0);

    const hrefActual = hrefActivo(url, [itemInicio.href, ...grupos.flatMap((grupo) => grupo.items.map((item) => item.href))]);
    const inicioActivo = hrefActual === itemInicio.href;
    const IconoInicio = inicioActivo ? iconosMenu.inicio.relleno : iconosMenu.inicio.trazo;

    return (
        <aside className={cn('flex h-full w-68 flex-col bg-surface', className)}>
            <div className="flex h-18 shrink-0 items-center px-4">
                <Marca />
            </div>

            <div className="scrollbar-fino flex-1 overflow-y-auto px-3 pt-2 pb-4">
                <p className="px-2.5 pb-2 text-[11px] font-medium tracking-[0.08em] text-zinc-400 uppercase dark:text-zinc-500">Menú principal</p>
                <nav className="space-y-0.5" aria-label="Menú principal">
                    <EnlaceMenu
                        item={itemInicio}
                        alNavegar={alNavegar}
                        className={cn(
                            'group flex h-9 items-center gap-2.5 rounded-lg px-2.5 text-sm transition-colors',
                            inicioActivo
                                ? 'bg-white font-medium text-zinc-900 shadow-xs ring-1 ring-zinc-200/80 dark:bg-zinc-800 dark:text-white dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.06)] dark:ring-white/[0.08]'
                                : 'text-zinc-600 hover:bg-zinc-200/60 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-white/[0.04] dark:hover:text-zinc-100',
                        )}
                    >
                        <IconoInicio className={cn('size-5', inicioActivo ? 'text-index-500 dark:text-index-400' : 'text-zinc-400 dark:text-zinc-500')} />
                        {itemInicio.titulo}
                    </EnlaceMenu>

                    {grupos.map((grupo) => (
                        <Grupo
                            key={grupo.titulo}
                            grupo={grupo}
                            activo={grupo.items.some((item) => item.href === hrefActual)}
                            hrefActual={hrefActual}
                            alNavegar={alNavegar}
                        />
                    ))}
                </nav>
            </div>

            <div className="hidden px-3 pb-2 md:block">
                <div className="rounded-xl border border-zinc-200/80 bg-white p-2.5 shadow-xs dark:border-white/[0.06] dark:bg-zinc-900 dark:shadow-none">
                    <TarjetaUsuario />
                </div>
            </div>

            <div className="flex h-11 shrink-0 items-center justify-between px-5">
                <span className="text-[11px] text-zinc-400 dark:text-zinc-500">CEPRE UNU © {anio}</span>
                <span className="rounded-md border border-zinc-200 bg-white px-1.5 py-0.5 font-mono text-[10px] text-zinc-500 dark:border-white/10 dark:bg-zinc-900 dark:text-zinc-400">
                    {version}
                </span>
            </div>
        </aside>
    );
}
