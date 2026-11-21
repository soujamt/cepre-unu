import type { NombreIconoPagina } from '@/lib/iconos';

export type TipoAviso = 'success' | 'error' | 'warning' | 'info';

/**
 * Aviso de una sola vez (toast) enviado desde el servidor con Inertia::flash('toast', ...).
 */
export interface Aviso {
    type: TipoAviso;
    message: string;
}

export interface UsuarioSesion {
    nombre: string;
    correo: string;
    iniciales: string;
}

export interface PermisosSesion {
    administrador: boolean;
    claves: string[];
}

export interface Auth {
    user: UsuarioSesion | null;
    permisos: PermisosSesion;
}

export interface Miga {
    titulo: string;
    href?: string;
}

/**
 * Datos que cada página entrega al layout: título, ícono del encabezado y ruta de navegación.
 */
export interface DatosLayout {
    titulo?: string;
    /** Texto bajo el título (portal del postulante). */
    subtitulo?: string;
    icono?: NombreIconoPagina;
    migas?: Miga[];
}

export interface EnlacePaginacion {
    url: string | null;
    label: string;
    active: boolean;
}

/**
 * Paginador de Laravel serializado (LengthAwarePaginator::toArray()).
 */
export interface Paginado<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: EnlacePaginacion[];
}

/**
 * Fecha de creación de un registro, para la columna "Registro".
 */
export interface Registro {
    fecha: string;
    hora: string;
}

/**
 * Filtros de los listados de mantenedores (búsqueda y estados marcados).
 */
export interface FiltrosCatalogo {
    buscar: string;
    estado: string[];
    [clave: string]: string | string[];
}

/** Opción de una lista desplegable que llega del servidor (por ejemplo, los valores de un enum). */
export interface Opcion {
    valor: string;
    nombre: string;
}
