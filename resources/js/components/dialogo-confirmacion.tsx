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
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>{opciones?.titulo}</AlertDialogTitle>
                        {opciones?.descripcion && <AlertDialogDescription>{opciones.descripcion}</AlertDialogDescription>}
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel onClick={() => cerrar(false)}>{opciones?.textoCancelar ?? 'Cancelar'}</AlertDialogCancel>
                        <AlertDialogAction variant={opciones?.peligro ? 'destructive' : 'default'} onClick={() => cerrar(true)}>
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
