import { usePage } from '@inertiajs/react';
import { ChevronDown } from '@keyline-icons/react/fill';
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

function ItemDelMenu({ item, activo, alNavegar }: { item: ItemMenu; activo: boolean; alNavegar?: () => void }) {
    const Icono = iconos[item.icono];

    return (
        <Enlace
            href={item.href}
            prefetch
            onClick={alNavegar}
            aria-current={activo ? 'page' : undefined}
            className={cn(
                'group relative flex h-9 items-center gap-2.5 rounded-lg px-2.5 text-[13.5px] transition-colors duration-150',
                activo
                    ? 'bg-white/[0.08] font-medium text-white shadow-[inset_0_1px_0_rgba(255,255,255,0.05)]'
                    : 'text-zinc-400 hover:bg-white/[0.04] hover:text-zinc-100',
            )}
        >
            {activo && <span className="absolute inset-y-2 -start-3 w-[3px] rounded-e-full bg-index-500" aria-hidden />}
            <Icono className={cn('size-[18px] transition-colors', activo ? 'text-index-400' : 'text-zinc-500 group-hover:text-zinc-300')} />
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
    const contieneActiva = seccion.items.some((item) => item.href === hrefActual);

    return (
        <Collapsible open={abierta} onOpenChange={alAlternar}>
            <CollapsibleTrigger
                className={cn(
                    'group flex h-8 w-full items-center justify-between rounded-lg px-2.5 text-[11px] font-semibold tracking-[0.08em] uppercase transition-colors hover:bg-white/[0.03] hover:text-zinc-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/15',
                    abierta || contieneActiva ? 'text-zinc-300' : 'text-zinc-500',
                )}
            >
                <span className="flex items-center gap-2">
                    {seccion.titulo}
                    {!abierta && contieneActiva && <span className="size-1.5 rounded-full bg-index-500" aria-hidden />}
                </span>
                <ChevronDown className={cn('size-3.5 transition-transform duration-200', !abierta && '-rotate-90')} />
            </CollapsibleTrigger>
            <CollapsibleContent className="overflow-hidden data-[state=closed]:animate-collapsible-up data-[state=open]:animate-collapsible-down">
                <div className="flex flex-col gap-0.5 pt-1 pb-2">
                    {seccion.items.map((item) => (
                        <ItemDelMenu key={item.href} item={item} activo={item.href === hrefActual} alNavegar={alNavegar} />
                    ))}
                </div>
            </CollapsibleContent>
        </Collapsible>
    );
}

/**
 * Menú lateral por secciones, con un ícono por pantalla. Solo muestra lo que el usuario
 * puede abrir. Es oscuro en los dos temas (clase "dark") para separarse del panel
 * principal. Las secciones empiezan plegadas y se abre la de la pantalla actual.
 */
export function MenuLateral({ alNavegar, className }: { alNavegar?: () => void; className?: string }) {
    const { url } = usePage();
    const { version } = usePage().props.app;
    const puede = usePermiso();

    const secciones = seccionesMenu
        .map((seccion) => ({ ...seccion, items: seccion.items.filter((item) => !item.permiso || puede(item.permiso)) }))
        .filter((seccion) => seccion.items.length > 0);

    const hrefActual = hrefActivo(url, [itemInicio.href, ...secciones.flatMap((seccion) => seccion.items.map((item) => item.href))]);
    const seccionActiva = secciones.find((seccion) => seccion.items.some((item) => item.href === hrefActual))?.titulo;

    const [abiertas, setAbiertas] = useState<string[]>(seccionActiva ? [seccionActiva] : []);

    // Al entrar a una pantalla de otra sección, se abre esa y se pliegan las demás.
    useEffect(() => {
        setAbiertas(seccionActiva ? [seccionActiva] : []);
    }, [seccionActiva]);

    const alternar = (titulo: string): void => {
        setAbiertas((actuales) => (actuales.includes(titulo) ? actuales.filter((actual) => actual !== titulo) : [...actuales, titulo]));
    };

    return (
        <aside className={cn('dark flex h-full w-68 flex-col bg-menu text-zinc-300', className)}>
            <div className="flex h-16 shrink-0 items-center px-4">
                <Marca />
            </div>

            <nav className="scrollbar-fino flex-1 space-y-1 overflow-y-auto px-3 pt-1 pb-4" aria-label="Menú principal">
                <div className="pb-2">
                    <ItemDelMenu item={itemInicio} activo={hrefActual === itemInicio.href} alNavegar={alNavegar} />
                </div>

                {secciones.map((seccion) => (
                    <Seccion
                        key={seccion.titulo}
                        seccion={seccion}
                        hrefActual={hrefActual}
                        abierta={abiertas.includes(seccion.titulo)}
                        alAlternar={() => alternar(seccion.titulo)}
                        alNavegar={alNavegar}
                    />
                ))}
            </nav>

            <div className="px-3 pb-3">
                <div className="rounded-2xl border border-white/[0.06] bg-white/[0.03] p-2.5">
                    <TarjetaUsuario />
                </div>
                <div className="flex items-center justify-between px-2 pt-2.5">
                    <span className="text-[11px] text-zinc-500">CEPRE UNU © {new Date().getFullYear()}</span>
                    <span className="rounded-md border border-white/10 bg-white/[0.03] px-1.5 py-0.5 font-mono text-[10px] text-zinc-400">
                        {version}
                    </span>
                </div>
            </div>
        </aside>
    );
}
