import type { NombreIcono } from '@/lib/iconos';
import { inicio } from '@/routes';
import { index as aulasProceso } from '@/routes/aulas';
import { configuracion as carnet } from '@/routes/carnet';
import { index as carreras } from '@/routes/carreras';
import { index as codigosAdmision } from '@/routes/codigos-admision';
import { index as conceptosPago } from '@/routes/conceptos-pago';
import { index as fotos } from '@/routes/fotos';
import { index as inscripciones } from '@/routes/inscripciones';
import { index as aulas } from '@/routes/mantenedor-aulas';
import { index as colegios } from '@/routes/mantenedor-colegios';
import { index as modalidades } from '@/routes/modalidades';
import { index as ofertas } from '@/routes/ofertas';
import { index as pagos, validar as validarPagos } from '@/routes/pagos';
import { index as permisos } from '@/routes/permisos';
import { index as procesos } from '@/routes/procesos';
import { index as reglasPago } from '@/routes/reglas-pago';
import { index as reporteInscripciones } from '@/routes/reportes/inscripciones';
import { index as roles } from '@/routes/roles';
import { index as sedes } from '@/routes/sedes';
import { index as turnos } from '@/routes/turnos';
import { index as usuarios } from '@/routes/usuarios';

export interface ItemMenu {
    titulo: string;
    href: string;
    icono: NombreIcono;
    /** Acción que debe tener el usuario para ver el ítem. */
    permiso?: string;
}

export interface SeccionMenu {
    titulo: string;
    items: ItemMenu[];
}

export const itemInicio: ItemMenu = {
    titulo: 'Inicio',
    href: inicio.url(),
    icono: 'inicio',
};

/**
 * Secciones del menú lateral: primero el trabajo diario (inscripciones y reportes),
 * luego la configuración. Cada ítem se muestra solo con su permiso.
 */
export const seccionesMenu: SeccionMenu[] = [
    {
        titulo: 'Inscripciones',
        items: [
            { titulo: 'Inscripciones', href: inscripciones.url(), icono: 'inscripciones', permiso: 'inscripcion_indice' },
            { titulo: 'Pagos', href: pagos.url(), icono: 'pagos', permiso: 'pago_indice' },
            { titulo: 'Validar pagos', href: validarPagos.url(), icono: 'validarPagos', permiso: 'pago_crear' },
            { titulo: 'Fotos', href: fotos.url(), icono: 'fotos', permiso: 'foto_indice' },
            { titulo: 'Reporte de inscripciones', href: reporteInscripciones.url(), icono: 'reportes', permiso: 'reporte_inscripcion_indice' },
        ],
    },
    {
        titulo: 'Configuración académica',
        items: [
            { titulo: 'Procesos', href: procesos.url(), icono: 'procesos', permiso: 'proceso_indice' },
            { titulo: 'Oferta del proceso', href: ofertas.url(), icono: 'oferta', permiso: 'oferta_indice' },
            { titulo: 'Aulas por proceso', href: aulasProceso.url(), icono: 'aulasProceso', permiso: 'aula_proceso_indice' },
            { titulo: 'Reglas de pago', href: reglasPago.url(), icono: 'reglasPago', permiso: 'regla_pago_indice' },
            { titulo: 'Códigos de Admisión', href: codigosAdmision.url(), icono: 'codigos', permiso: 'codigo_admision_indice' },
            { titulo: 'Carnet', href: carnet.url(), icono: 'carnet', permiso: 'parametro_indice' },
        ],
    },
    {
        titulo: 'Mantenedores',
        items: [
            { titulo: 'Aulas', href: aulas.url(), icono: 'aulas', permiso: 'aula_indice' },
            { titulo: 'Carreras', href: carreras.url(), icono: 'carreras', permiso: 'carrera_indice' },
            { titulo: 'Colegios', href: colegios.url(), icono: 'colegios', permiso: 'colegio_indice' },
            { titulo: 'Conceptos de pago', href: conceptosPago.url(), icono: 'conceptos', permiso: 'concepto_pago_indice' },
            { titulo: 'Modalidades', href: modalidades.url(), icono: 'modalidades', permiso: 'modalidad_indice' },
            { titulo: 'Sedes', href: sedes.url(), icono: 'sedes', permiso: 'sede_indice' },
            { titulo: 'Turnos', href: turnos.url(), icono: 'turnos', permiso: 'turno_indice' },
        ],
    },
    {
        titulo: 'Seguridad',
        items: [
            { titulo: 'Usuarios', href: usuarios.url(), icono: 'usuarios', permiso: 'usuario_indice' },
            { titulo: 'Roles', href: roles.url(), icono: 'roles', permiso: 'rol_indice' },
            { titulo: 'Permisos', href: permisos.url(), icono: 'permisos', permiso: 'permiso_indice' },
        ],
    },
];

/**
 * Ítem activo para la URL actual: el de ruta más larga que la contiene, así
 * "/inscripciones/pagos/validar" marca "Validar pagos" y no "Inscripciones".
 */
export function hrefActivo(urlActual: string, hrefs: string[]): string | null {
    const ruta = new URL(urlActual, window.location.origin).pathname;

    return (
        hrefs
            .filter((href) => {
                const base = new URL(href, window.location.origin).pathname;

                return ruta === base || ruta.startsWith(`${base}/`);
            })
            .sort((a, b) => b.length - a.length)[0] ?? null
    );
}
