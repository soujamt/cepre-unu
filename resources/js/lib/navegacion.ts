import type { NombreIconoMenu } from '@/lib/iconos';
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
    /** Acción que debe tener el usuario para ver el ítem. */
    permiso?: string;
}

export interface GrupoMenu {
    titulo: string;
    icono: NombreIconoMenu;
    items: ItemMenu[];
}

export const itemInicio: ItemMenu & { icono: NombreIconoMenu } = {
    titulo: 'Inicio',
    href: inicio.url(),
    icono: 'inicio',
};

/**
 * Mismos grupos, orden y permisos que el menú lateral de las pantallas Livewire.
 */
export const gruposMenu: GrupoMenu[] = [
    {
        titulo: 'Seguridad',
        icono: 'seguridad',
        items: [
            { titulo: 'Usuarios', href: usuarios.url(), permiso: 'usuario_indice' },
            { titulo: 'Roles', href: roles.url(), permiso: 'rol_indice' },
            { titulo: 'Permisos', href: permisos.url(), permiso: 'permiso_indice' },
        ],
    },
    {
        titulo: 'Mantenedores',
        icono: 'mantenedores',
        items: [
            { titulo: 'Aulas', href: aulas.url(), permiso: 'aula_indice' },
            { titulo: 'Carreras', href: carreras.url(), permiso: 'carrera_indice' },
            { titulo: 'Colegios', href: colegios.url(), permiso: 'colegio_indice' },
            { titulo: 'Conceptos de pago', href: conceptosPago.url(), permiso: 'concepto_pago_indice' },
            { titulo: 'Modalidades', href: modalidades.url(), permiso: 'modalidad_indice' },
            { titulo: 'Sedes', href: sedes.url(), permiso: 'sede_indice' },
            { titulo: 'Turnos', href: turnos.url(), permiso: 'turno_indice' },
        ],
    },
    {
        titulo: 'Conf. académica',
        icono: 'configuracion',
        items: [
            { titulo: 'Procesos', href: procesos.url(), permiso: 'proceso_indice' },
            { titulo: 'Aulas', href: aulasProceso.url(), permiso: 'aula_proceso_indice' },
            { titulo: 'Oferta del proceso', href: ofertas.url(), permiso: 'oferta_indice' },
            { titulo: 'Reglas de pago', href: reglasPago.url(), permiso: 'regla_pago_indice' },
            { titulo: 'Códigos de Admisión', href: codigosAdmision.url(), permiso: 'codigo_admision_indice' },
            { titulo: 'Carnet', href: carnet.url(), permiso: 'parametro_indice' },
        ],
    },
    {
        titulo: 'Inscripciones',
        icono: 'inscripciones',
        items: [
            { titulo: 'Inscripciones', href: inscripciones.url(), permiso: 'inscripcion_indice' },
            { titulo: 'Fotos', href: fotos.url(), permiso: 'foto_indice' },
            { titulo: 'Pagos', href: pagos.url(), permiso: 'pago_indice' },
            { titulo: 'Validar pagos', href: validarPagos.url(), permiso: 'pago_crear' },
        ],
    },
    {
        titulo: 'Reportes',
        icono: 'reportes',
        items: [{ titulo: 'Rep. inscripciones', href: reporteInscripciones.url(), permiso: 'reporte_inscripcion_indice' }],
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
