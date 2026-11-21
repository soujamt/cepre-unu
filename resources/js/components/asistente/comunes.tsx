import { ArrowLeft, ArrowRight } from '@keyline-icons/react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import type { Opcion } from '@/types';

/**
 * Bloque de un paso con su título corto y una línea de ayuda.
 */
export function Seccion({ titulo, descripcion, children }: { titulo: string; descripcion?: string; children: ReactNode }) {
    return (
        <section className="space-y-4 border-t border-zinc-100 pt-6 first:border-t-0 first:pt-0 dark:border-white/[0.06]">
            <div>
                <h3 className="text-sm font-semibold text-zinc-900 dark:text-white">{titulo}</h3>
                {descripcion && <p className="text-sm text-zinc-500 dark:text-zinc-400">{descripcion}</p>}
            </div>
            {children}
        </section>
    );
}

export function Interruptor({ etiqueta, marcado, alCambiar }: { etiqueta: string; marcado: boolean; alCambiar: (marcado: boolean) => void }) {
    return (
        <label className="flex cursor-pointer items-center gap-2.5 text-sm text-zinc-700 dark:text-zinc-200">
            <Switch checked={marcado} onCheckedChange={alCambiar} />
            {etiqueta}
        </label>
    );
}

export function Opciones({ opciones, vacio }: { opciones: Opcion[]; vacio: string }) {
    return (
        <>
            <option value="">{vacio}</option>
            {opciones.map((opcion) => (
                <option key={opcion.valor} value={opcion.valor}>
                    {opcion.nombre}
                </option>
            ))}
        </>
    );
}

/**
 * Pie de los pasos: volver al anterior y la acción principal.
 */
export function PiePaso({ alVolver, textoAccion, alAccion, procesando, extra }: { alVolver?: () => void; textoAccion: string; alAccion: () => void; procesando: boolean; extra?: ReactNode }) {
    return (
        <div className="flex flex-col-reverse gap-3 border-t border-zinc-100 pt-5 sm:flex-row sm:items-center sm:justify-between dark:border-white/[0.06]">
            <div>
                {alVolver && (
                    <Button variant="outline" onClick={alVolver} disabled={procesando}>
                        <ArrowLeft className="size-4.5" />
                        Anterior
                    </Button>
                )}
            </div>
            <div className="flex flex-wrap items-center gap-2.5">
                {extra}
                <Button onClick={alAccion} disabled={procesando}>
                    {procesando ? 'Guardando…' : textoAccion}
                    <ArrowRight className="size-4.5" />
                </Button>
            </div>
        </div>
    );
}
