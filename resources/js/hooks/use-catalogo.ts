import { router, useForm } from '@inertiajs/react';
import type { FormDataType } from '@inertiajs/core';
import { useState } from 'react';
import { useConfirmacion } from '@/components/dialogo-confirmacion';

/** Llave del registro: un número, o un código como el de los colegios. */
export type LlaveCatalogo = string | number;

/**
 * Funciones de Wayfinder de las rutas del mantenedor (por ejemplo, las de @/routes/carreras).
 */
interface RutasCatalogo {
    /** Sin ruta de registro, el formulario solo edita (por ejemplo, la capacidad de un aula asignada). */
    store?: { url: () => string };
    update: { url: (id: LlaveCatalogo) => string };
    estado?: { url: (id: LlaveCatalogo) => string };
    destroy: { url: (id: LlaveCatalogo) => string };
}

/**
 * Lo que necesita <ListadoCatalogo> para manejar las filas y el formulario lateral.
 */
export interface ControlCatalogo<TFila> {
    abrirNuevo: () => void;
    abrirEdicion: (fila: TFila) => void;
    cambiarEstado: (fila: TFila) => Promise<void>;
    eliminar: (fila: TFila) => Promise<void>;
    guardar: () => void;
    hoja: { abierta: boolean; cerrar: () => void; titulo: string };
    /** true mientras el formulario edita un registro existente. */
    editando: boolean;
    procesando: boolean;
}

interface OpcionesCatalogo<TFila, TFormulario> {
    /** Valores del formulario para un registro nuevo. */
    vacio: TFormulario;
    /** Valores del formulario a partir de una fila al editar. */
    aFormulario: (fila: TFila) => TFormulario;
    idDe: (fila: TFila) => LlaveCatalogo;
    nombreDe: (fila: TFila) => string;
    rutas: RutasCatalogo;
    /** Textos: "Nueva carrera", "Editar carrera" y "la carrera" para las confirmaciones. */
    titulos: { nuevo: string; editar: string; entidad: string };
    /** Se llama al abrir el formulario, con la fila al editar o null al registrar (por ejemplo, para la etiqueta de un buscador). */
    alAbrir?: (fila: TFila | null) => void;
}

/**
 * Flujo común de un mantenedor: formulario lateral para registrar o editar y
 * confirmación antes de cambiar el estado o eliminar. Los avisos llegan del servidor.
 */
export function useCatalogo<TFila, TFormulario extends FormDataType<TFormulario>>({ vacio, aFormulario, idDe, nombreDe, rutas, titulos, alAbrir }: OpcionesCatalogo<TFila, TFormulario>) {
    const confirmar = useConfirmacion();
    const formulario = useForm<TFormulario>(vacio);
    const [abierta, setAbierta] = useState(false);
    const [editandoId, setEditandoId] = useState<LlaveCatalogo | null>(null);

    const abrirNuevo = (): void => {
        formulario.clearErrors();
        formulario.setData(vacio);
        setEditandoId(null);
        alAbrir?.(null);
        setAbierta(true);
    };

    const abrirEdicion = (fila: TFila): void => {
        formulario.clearErrors();
        formulario.setData(aFormulario(fila));
        setEditandoId(idDe(fila));
        alAbrir?.(fila);
        setAbierta(true);
    };

    const cerrar = (): void => setAbierta(false);

    const guardar = (): void => {
        const opciones = { preserveScroll: true, onSuccess: () => setAbierta(false) };

        if (editandoId === null) {
            if (rutas.store) {
                formulario.post(rutas.store.url(), opciones);
            }
        } else {
            formulario.put(rutas.update.url(editandoId), opciones);
        }
    };

    const cambiarEstado = async (fila: TFila): Promise<void> => {
        const confirmado = await confirmar({
            titulo: 'Confirmación requerida',
            descripcion: `¿Está seguro de que desea cambiar el estado de ${titulos.entidad} "${nombreDe(fila)}"?`,
            textoConfirmar: 'Aceptar',
        });

        if (confirmado && rutas.estado) {
            router.patch(rutas.estado.url(idDe(fila)), {}, { preserveScroll: true });
        }
    };

    const eliminar = async (fila: TFila): Promise<void> => {
        const confirmado = await confirmar({
            titulo: 'Atención',
            descripcion: `¿Está seguro de que desea eliminar ${titulos.entidad} "${nombreDe(fila)}"? Esta acción es irreversible.`,
            textoConfirmar: 'Eliminar',
            peligro: true,
        });

        if (confirmado) {
            router.delete(rutas.destroy.url(idDe(fila)), { preserveScroll: true });
        }
    };

    const control: ControlCatalogo<TFila> = {
        hoja: { abierta, cerrar, titulo: editandoId === null ? titulos.nuevo : titulos.editar },
        abrirNuevo,
        abrirEdicion,
        guardar,
        cambiarEstado,
        eliminar,
        editando: editandoId !== null,
        procesando: formulario.processing,
    };

    return { formulario, control };
}
