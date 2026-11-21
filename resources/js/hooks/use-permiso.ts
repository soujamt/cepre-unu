import { usePage } from '@inertiajs/react';

/**
 * Indica si el usuario tiene la acción indicada (por ejemplo "inscripcion_crear").
 * El administrador tiene todas. Solo controla lo que se muestra: el servidor
 * vuelve a comprobar cada permiso.
 */
export function usePermiso(): (clave: string) => boolean {
    const { permisos } = usePage().props.auth;

    return (clave: string): boolean => permisos.administrador || permisos.claves.includes(clave);
}
