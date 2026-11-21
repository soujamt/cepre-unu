import { Alert, CircleQuestion } from '@keyline-icons/react';
import { createContext, useCallback, useContext, useRef, useState } from 'react';
import type { ReactNode } from 'react';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';

export interface OpcionesConfirmacion {
    titulo: string;
    descripcion?: ReactNode;
    textoConfirmar?: string;
    textoCancelar?: string;
    /** Acción que borra o anula: el botón se muestra en rojo. */
    peligro?: boolean;
}

type Confirmar = (opciones: OpcionesConfirmacion) => Promise<boolean>;

const ContextoConfirmacion = createContext<Confirmar | null>(null);

/**
 * Diálogo de confirmación único para toda la aplicación. Reemplaza a las alertas
 * de confirmación que las pantallas Livewire abrían con dispatch('alert').
 */
export function ProveedorConfirmacion({ children }: { children: ReactNode }) {
    const [opciones, setOpciones] = useState<OpcionesConfirmacion | null>(null);
    const resolver = useRef<((confirmado: boolean) => void) | null>(null);

    const confirmar = useCallback<Confirmar>((nuevas) => {
        setOpciones(nuevas);

        return new Promise<boolean>((resolve) => {
            resolver.current = resolve;
        });
    }, []);

    const cerrar = (confirmado: boolean): void => {
        resolver.current?.(confirmado);
        resolver.current = null;
        setOpciones(null);
    };

    return (
        <ContextoConfirmacion.Provider value={confirmar}>
            {children}
            <AlertDialog open={opciones !== null} onOpenChange={(abierto) => !abierto && cerrar(false)}>
                <AlertDialogContent className="gap-0 p-0 sm:max-w-[25rem]">
                    <AlertDialogHeader className="flex flex-row place-items-start items-start gap-3.5 space-y-0 p-5 text-start sm:text-start">
                        <span
                            className={
                                opciones?.peligro
                                    ? 'flex size-9 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-500 ring-4 ring-red-50/50 dark:bg-red-500/10 dark:ring-red-500/5'
                                    : 'flex size-9 shrink-0 items-center justify-center rounded-full bg-index-50 text-index-500 ring-4 ring-index-50/50 dark:bg-index-500/10 dark:ring-index-500/5'
                            }
                        >
                            {opciones?.peligro ? <Alert className="size-4.5" /> : <CircleQuestion className="size-4.5" />}
                        </span>
                        <div className="min-w-0 space-y-1 pt-0.5">
                            <AlertDialogTitle className="text-[15px] font-semibold text-zinc-900 dark:text-white">{opciones?.titulo}</AlertDialogTitle>
                            {opciones?.descripcion && (
                                <AlertDialogDescription className="text-sm text-pretty text-zinc-500 dark:text-zinc-400">{opciones.descripcion}</AlertDialogDescription>
                            )}
                        </div>
                    </AlertDialogHeader>
                    <AlertDialogFooter className="gap-2 rounded-b-2xl border-t border-zinc-200/70 bg-zinc-50/70 px-5 py-3.5 dark:border-white/[0.06] dark:bg-white/[0.02]">
                        <AlertDialogCancel size="sm" onClick={() => cerrar(false)}>
                            {opciones?.textoCancelar ?? 'Cancelar'}
                        </AlertDialogCancel>
                        <AlertDialogAction size="sm" variant={opciones?.peligro ? 'destructive' : 'default'} onClick={() => cerrar(true)}>
                            {opciones?.textoConfirmar ?? 'Confirmar'}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </ContextoConfirmacion.Provider>
    );
}

/**
 * Pide confirmación y devuelve true si el usuario acepta:
 * `if (await confirmar({ titulo: '¿Anular la inscripción?', peligro: true })) { ... }`
 */
export function useConfirmacion(): Confirmar {
    const confirmar = useContext(ContextoConfirmacion);

    if (confirmar === null) {
        throw new Error('useConfirmacion debe usarse dentro de <ProveedorConfirmacion>.');
    }

    return confirmar;
}
