import { Save } from '@keyline-icons/react';
import type { FormEvent, ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetDescription, SheetTitle } from '@/components/ui/sheet';

/**
 * Formulario en un panel lateral derecho, como el modal "flyout" de las pantallas
 * Livewire: título, campos y el botón Guardar al pie.
 */
export function HojaFormulario({
    abierta,
    alCerrar,
    titulo,
    descripcion,
    alEnviar,
    procesando,
    textoGuardar = 'Guardar',
    children,
}: {
    abierta: boolean;
    alCerrar: () => void;
    titulo: string;
    descripcion?: string;
    alEnviar: () => void;
    procesando: boolean;
    textoGuardar?: string;
    children: ReactNode;
}) {
    const enviar = (evento: FormEvent): void => {
        evento.preventDefault();
        alEnviar();
    };

    return (
        <Sheet open={abierta} onOpenChange={(abrir) => !abrir && alCerrar()}>
            <SheetContent side="right" className="flex w-full flex-col gap-0 p-0 sm:max-w-md">
                <div className="border-b border-zinc-200/80 px-5 py-4 dark:border-white/[0.06]">
                    <SheetTitle className="text-lg font-semibold text-zinc-900 dark:text-white">{titulo}</SheetTitle>
                    <SheetDescription className={descripcion ? 'text-sm text-zinc-500 dark:text-zinc-400' : 'sr-only'}>
                        {descripcion ?? titulo}
                    </SheetDescription>
                </div>

                <form onSubmit={enviar} noValidate className="flex min-h-0 flex-1 flex-col">
                    <div className="scrollbar-fino flex-1 space-y-5 overflow-y-auto px-5 py-5">{children}</div>
                    <div className="flex justify-end gap-3 border-t border-zinc-200/80 px-5 py-4 dark:border-white/[0.06]">
                        <Button type="button" variant="ghost" onClick={alCerrar}>
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={procesando}>
                            <Save className="size-4.5" />
                            {procesando ? 'Guardando…' : textoGuardar}
                        </Button>
                    </div>
                </form>
            </SheetContent>
        </Sheet>
    );
}
