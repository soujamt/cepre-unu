import { router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import type { Cambio, DatosAsistente, Formularios, ValorCampo } from '@/components/asistente/tipos';

/**
 * Copia local de los formularios del asistente. Los campos se envían al servidor con la
 * siguiente acción; los que cambian otros datos (DNI, tipo de documento, sede…) se envían
 * al momento, como en la pantalla anterior. El servidor responde con el estado nuevo.
 */
export function useAsistente(asistente: DatosAsistente, rutaActualizar: string, rutaFoto?: string) {
    const errores = usePage().props.errors as Record<string, string>;
    const [formularios, setFormularios] = useState<Formularios>(asistente.formularios);
    const [etiquetas, setEtiquetas] = useState(asistente.etiquetas);
    const [confirmacion, setConfirmacion] = useState(asistente.confirmacionDatos);
    const [procesando, setProcesando] = useState(false);

    /** Formularios tal como se enviaron en la última solicitud. */
    const enviados = useRef<Formularios>(asistente.formularios);

    // Al llegar el estado del servidor se toman sus valores, salvo los campos que el
    // usuario siguió editando mientras la solicitud estaba en camino.
    useEffect(() => {
        setFormularios((locales) => {
            const combinados = structuredClone(asistente.formularios);

            (Object.keys(combinados) as (keyof Formularios)[]).forEach((formulario) => {
                Object.keys(locales[formulario] ?? {}).forEach((campo) => {
                    if (locales[formulario][campo] !== enviados.current[formulario]?.[campo]) {
                        (combinados[formulario] as Record<string, unknown>)[campo] = locales[formulario][campo];
                    }
                });
            });

            enviados.current = asistente.formularios;

            return combinados;
        });
        setEtiquetas(asistente.etiquetas);
        setConfirmacion(asistente.confirmacionDatos);
    }, [asistente]);

    const pendientes = (siguientes: Formularios, siguientesEtiquetas = etiquetas, siguienteConfirmacion = confirmacion): Cambio[] => {
        const cambios: Cambio[] = [];

        (Object.keys(siguientes) as (keyof Formularios)[]).forEach((formulario) => {
            Object.entries(siguientes[formulario]).forEach(([campo, valor]) => {
                if (asistente.formularios[formulario][campo] !== valor) {
                    cambios.push({ ruta: `${formulario}.${campo}`, valor: valor as ValorCampo });
                }
            });
        });

        const nombresEtiqueta = { ubigeoNacimiento: 'ubigeoNacimientoLabel', ubigeoDomicilio: 'ubigeoDomicilioLabel', colegio: 'colegioLabel' } as const;

        (Object.keys(nombresEtiqueta) as (keyof typeof nombresEtiqueta)[]).forEach((clave) => {
            if (siguientesEtiquetas[clave] !== asistente.etiquetas[clave]) {
                cambios.push({ ruta: nombresEtiqueta[clave], valor: siguientesEtiquetas[clave] });
            }
        });

        if (siguienteConfirmacion !== asistente.confirmacionDatos) {
            cambios.push({ ruta: 'confirmacionDatos', valor: siguienteConfirmacion });
        }

        return cambios;
    };

    const enviar = (cambios: Cambio[], base: Formularios, accion?: string, parametros: ValorCampo[] = []): void => {
        enviados.current = base;
        router.post(
            rutaActualizar,
            { cambios, accion: accion ?? null, parametros },
            {
                preserveScroll: true,
                preserveState: true,
                onStart: () => accion && setProcesando(true),
                onFinish: () => accion && setProcesando(false),
            },
        );
    };

    /**
     * Ejecuta una acción del asistente con los cambios pendientes de los formularios.
     */
    const accion = (nombre: string, ...parametros: ValorCampo[]): void => enviar(pendientes(formularios), formularios, nombre, parametros);

    /**
     * Cambia un campo; con vivo=true se envía de inmediato (y lo último, tras los pendientes).
     */
    const cambiar = <F extends keyof Formularios>(formulario: F, campo: string, valor: string | number | boolean, vivo = false): void => {
        const siguientes = { ...formularios, [formulario]: { ...formularios[formulario], [campo]: valor } } as Formularios;
        setFormularios(siguientes);

        if (vivo) {
            const ruta = `${formulario}.${campo}`;
            enviar([...pendientes(siguientes).filter((cambio) => cambio.ruta !== ruta), { ruta, valor }], siguientes);
        }
    };

    const cambiarEtiqueta = (clave: keyof DatosAsistente['etiquetas'], texto: string): void => setEtiquetas((actuales) => ({ ...actuales, [clave]: texto }));

    const marcarPago = (id: number, marcado: boolean): void => {
        enviar([...pendientes(formularios), { ruta: `pagosSeleccionados.${id}`, valor: marcado }], formularios);
    };

    const subirFoto = (archivo: File): void => {
        if (rutaFoto) {
            router.post(rutaFoto, { foto: archivo }, { forceFormData: true, preserveScroll: true, preserveState: true, onStart: () => setProcesando(true), onFinish: () => setProcesando(false) });
        }
    };

    return { formularios, etiquetas, confirmacion, setConfirmacion, errores, procesando, accion, cambiar, cambiarEtiqueta, marcarPago, subirFoto };
}

export type ControlAsistente = ReturnType<typeof useAsistente>;
