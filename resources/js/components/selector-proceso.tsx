import { router } from '@inertiajs/react';
import { InsigniaEstado } from '@/components/insignia-estado';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { cn } from '@/lib/utils';

export interface OpcionProceso {
    valor: string;
    nombre: string;
    estado: string;
}

/**
 * Lista para elegir el proceso de trabajo de la pantalla; al cambiarlo se vuelve a
 * pedir la página con ?proceso=ID (y los filtros extra que se indiquen).
 */
export function SelectorProceso({
    procesos,
    valor,
    consultaExtra = {},
    className,
}: {
    procesos: OpcionProceso[];
    valor: string | number | null | undefined;
    consultaExtra?: Record<string, string>;
    className?: string;
}) {
    const cambiar = (proceso: string): void => {
        router.get(window.location.pathname, { ...consultaExtra, proceso }, { preserveScroll: true });
    };

    return (
        <Select value={valor === null || valor === undefined ? undefined : String(valor)} onValueChange={cambiar}>
            <SelectTrigger aria-label="Proceso" className={cn('h-10! w-full bg-control sm:w-64', className)}>
                <span className="text-zinc-500 dark:text-zinc-400">Proceso:</span>
                <SelectValue placeholder="Seleccione un proceso" />
            </SelectTrigger>
            <SelectContent position="popper" align="start" className="min-w-64">
                {procesos.map((proceso) => (
                    <SelectItem key={proceso.valor} value={proceso.valor}>
                        <span className="flex w-full items-center justify-between gap-3">
                            <span className="font-medium">{proceso.nombre}</span>
                            <InsigniaEstado estado={proceso.estado} className="scale-90" />
                        </span>
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
