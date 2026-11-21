import { usePage } from '@inertiajs/react';
import { ChevronDown } from '@keyline-icons/react';
import { useEffect, useState } from 'react';
import { Enlace } from '@/components/enlace';
import { Marca } from '@/components/marca';
import { TarjetaUsuario } from '@/components/menu-usuario';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { usePermiso } from '@/hooks/use-permiso';
import { iconos } from '@/lib/iconos';
import { hrefActivo, itemInicio, seccionesMenu } from '@/lib/navegacion';
import type { ItemMenu, SeccionMenu } from '@/lib/navegacion';
import { cn } from '@/lib/utils';

/** Secciones que el usuario cerró; se recuerdan entre visitas. */
const CLAVE_CERRADAS = 'menu.secciones-cerradas';

function leerCerradas(): string[] {
    try {
        return JSON.parse(localStorage.getItem(CLAVE_CERRADAS) ?? '[]') as string[];
    } catch {
        return [];
    }
}

function ItemDelMenu({ item, activo, alNavegar }: { item: ItemMenu; activo: boolean; alNavegar?: () => void }) {
    const { trazo: IconoTrazo, relleno: IconoRelleno } = iconos[item.icono];
    const Icono = activo ? IconoRelleno : IconoTrazo;

    return (
        <Enlace
            href={item.href}
            prefetch
            onClick={alNavegar}
            aria-current={activo ? 'page' : undefined}
            className={cn(
                'group flex h-9 items-center gap-2.5 rounded-lg px-2.5 text-[13.5px] transition-colors duration-150',
                activo
                    ? 'bg-white font-medium text-zinc-900 shadow-[0_1px_2px_rgba(16,24,40,0.06)] ring-1 ring-zinc-200/80 dark:bg-white/[0.07] dark:text-white dark:shadow-none dark:ring-white/[0.06]'
                    : 'text-zinc-600 hover:bg-zinc-200/50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-white/[0.04] dark:hover:text-zinc-100',
            )}
        >
            <Icono
                className={cn(
                    'size-[18px] transition-colors',
                    activo ? 'text-index-500 dark:text-index-400' : 'text-zinc-400 group-hover:text-zinc-600 dark:text-zinc-500 dark:group-hover:text-zinc-300',
                )}
            />
            <span className="truncate">{item.titulo}</span>
        </Enlace>
    );
}

function Seccion({
    seccion,
    hrefActual,
    abierta,
    alAlternar,
    alNavegar,
}: {
    seccion: SeccionMenu;
    hrefActual: string | null;
    abierta: boolean;
    alAlternar: () => void;
    alNavegar?: () => void;
}) {
    return (
        <Collapsible open={abierta} onOpenChange={alAlternar} className="border-t border-dashed border-zinc-300/70 pt-3 dark:border-white/[0.08]">
            <CollapsibleTrigger className="group flex h-7 w-full items-center justify-between rounded-md px-2.5 text-xs font-medium text-zinc-400 transition-colors hover:text-zinc-600 dark:text-zinc-500 dark:hover:text-zinc-300">
                {seccion.titulo}
                <ChevronDown className={cn('size-3.5 opacity-0 transition-[opacity,transform] duration-200 group-hover:opacity-100 group-focus-visible:opacity-100', !abierta && '-rotate-90 opacity-100')} />
            </CollapsibleTrigger>
            <CollapsibleContent className="mt-1 flex flex-col gap-0.5 pb-3">
                {seccion.items.map((item) => (
                    <ItemDelMenu key={item.href} item={item} activo={item.href === hrefActual} alNavegar={alNavegar} />
                ))}
            </CollapsibleContent>
        </Collapsible>
    );
}

/**
 * Menú lateral por secciones, con un ícono por pantalla. Solo muestra lo que el usuario
 * puede abrir; en modo oscuro queda casi negro para separarse del panel principal.
 */
export function MenuLateral({ alNavegar, className }: { alNavegar?: () => void; className?: string }) {
    const { url } = usePage();
    const { version } = usePage().props.app;
    const puede = usePermiso();
    const [cerradas, setCerradas] = useState<string[]>(leerCerradas);

    const secciones = seccionesMenu
        .map((seccion) => ({ ...seccion, items: seccion.items.filter((item) => !item.permiso || puede(item.permiso)) }))
        .filter((seccion) => seccion.items.length > 0);

    const hrefActual = hrefActivo(url, [itemInicio.href, ...secciones.flatMap((seccion) => seccion.items.map((item) => item.href))]);
    const seccionActiva = secciones.find((seccion) => seccion.items.some((item) => item.href === hrefActual))?.titulo;

    // La sección de la pantalla actual siempre queda abierta.
    useEffect(() => {
        if (seccionActiva && cerradas.includes(seccionActiva)) {
            setCerradas((actuales) => actuales.filter((titulo) => titulo !== seccionActiva));
        }
    }, [seccionActiva, cerradas]);

    useEffect(() => {
        localStorage.setItem(CLAVE_CERRADAS, JSON.stringify(cerradas));
    }, [cerradas]);

    const alternar = (titulo: string): void => {
        setCerradas((actuales) => (actuales.includes(titulo) ? actuales.filter((actual) => actual !== titulo) : [...actuales, titulo]));
    };

    return (
        <aside className={cn('flex h-full w-68 flex-col bg-surface', className)}>
            <div className="flex h-16 shrink-0 items-center px-4">
                <Marca />
            </div>

            <nav className="scrollbar-fino flex-1 space-y-3 overflow-y-auto px-3 pt-1 pb-4" aria-label="Menú principal">
                <ItemDelMenu item={itemInicio} activo={hrefActual === itemInicio.href} alNavegar={alNavegar} />

                {secciones.map((seccion) => (
                    <Seccion
                        key={seccion.titulo}
                        seccion={seccion}
                        hrefActual={hrefActual}
                        abierta={!cerradas.includes(seccion.titulo)}
                        alAlternar={() => alternar(seccion.titulo)}
                        alNavegar={alNavegar}
                    />
                ))}
            </nav>

            <div className="px-3 pb-3">
                <div className="rounded-2xl border border-zinc-200/80 bg-white p-2.5 shadow-[0_1px_2px_rgba(16,24,40,0.04)] dark:border-white/[0.06] dark:bg-white/[0.03] dark:shadow-none">
                    <TarjetaUsuario />
                </div>
                <div className="flex items-center justify-between px-2 pt-2.5">
                    <span className="text-[11px] text-zinc-400 dark:text-zinc-500">CEPRE UNU © {new Date().getFullYear()}</span>
                    <span className="rounded-md border border-zinc-200 bg-white px-1.5 py-0.5 font-mono text-[10px] text-zinc-500 dark:border-white/10 dark:bg-white/[0.03] dark:text-zinc-400">
                        {version}
                    </span>
                </div>
            </div>
        </aside>
    );
}
