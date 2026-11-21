import { router } from '@inertiajs/react';
import { ShieldKey, ShieldUser } from '@keyline-icons/react';
import { CampoArea, CampoTexto } from '@/components/campo';
import { ListadoCatalogo } from '@/components/listado-catalogo';
import { Badge } from '@/components/ui/badge';
import { useCatalogo } from '@/hooks/use-catalogo';
import { usePermiso } from '@/hooks/use-permiso';
import { inicio } from '@/routes';
import { destroy, estado, index, permisos, store, update } from '@/routes/roles';
import type { FiltrosCatalogo, Paginado, Registro } from '@/types';

interface Rol {
    id: number;
    nombre: string;
    descripcion: string | null;
    usuarios: number;
    /** Acciones habilitadas asignadas al rol. */
    acciones: number;
    esAdministrador: boolean;
    estado: string;
    registro: Registro | null;
}

export default function Roles({ roles, filtros }: { roles: Paginado<Rol>; filtros: FiltrosCatalogo }) {
    const puede = usePermiso();
    const { formulario, control } = useCatalogo({
        vacio: { nombre: '', descripcion: '' },
        aFormulario: (rol: Rol) => ({ nombre: rol.nombre, descripcion: rol.descripcion ?? '' }),
        idDe: (rol) => rol.id,
        nombreDe: (rol) => rol.nombre,
        rutas: { store, update, estado, destroy },
        titulos: { nuevo: 'Nuevo rol', editar: 'Editar rol', entidad: 'el rol' },
    });
    const editandoAdministrador = control.editando && formulario.data.nombre === 'ADMINISTRADOR';

    return (
        <ListadoCatalogo
            permiso="rol"
            paginado={roles}
            filtros={filtros}
            catalogo={control}
            textoNuevo="Nuevo"
            placeholderBusqueda="Buscar por nombre o descripción"
            vacio={{ icono: ShieldUser, titulo: 'No se encontraron roles registrados.' }}
            ocultarAccion={(rol, accion) => rol.esAdministrador && accion !== 'editar'}
            accionesExtra={(rol) => [
                { etiqueta: 'Permisos', icono: ShieldKey, alSeleccionar: () => router.visit(permisos.url(rol.id)), visible: puede('rol_permisos') },
            ]}
            columnas={[
                {
                    clave: 'nombre',
                    titulo: 'Rol',
                    className: 'whitespace-normal',
                    celda: (rol) => (
                        <>
                            <span className="flex items-center gap-2 font-medium text-zinc-900 dark:text-white">
                                {rol.nombre}
                                {rol.esAdministrador && <Badge variant="default">Acceso total</Badge>}
                            </span>
                            {rol.descripcion && <span className="block text-xs text-zinc-500 dark:text-zinc-400">{rol.descripcion}</span>}
                        </>
                    ),
                },
                { clave: 'usuarios', titulo: 'Usuarios', className: 'w-24 tabular-nums', celda: (rol) => rol.usuarios },
                { clave: 'acciones', titulo: 'Acciones', className: 'w-24 tabular-nums', celda: (rol) => (rol.esAdministrador ? 'Todas' : rol.acciones) },
            ]}
            campos={
                <>
                    <CampoTexto
                        etiqueta="Nombre"
                        value={formulario.data.nombre}
                        onChange={(evento) => formulario.setData('nombre', evento.target.value)}
                        error={formulario.errors.nombre}
                        disabled={editandoAdministrador}
                        ayuda={editandoAdministrador ? 'El nombre del rol ADMINISTRADOR no se puede cambiar.' : undefined}
                        requerido
                    />
                    <CampoArea
                        etiqueta="Descripción"
                        value={formulario.data.descripcion}
                        onChange={(evento) => formulario.setData('descripcion', evento.target.value)}
                        error={formulario.errors.descripcion}
                        maxLength={500}
                    />
                </>
            }
        />
    );
}

Roles.layout = {
    titulo: 'Roles',
    subtitulo: 'Agrupan permisos para asignarlos a los usuarios.',
    icono: 'roles',
    migas: [{ titulo: 'Inicio', href: inicio.url() }, { titulo: 'Seguridad' }, { titulo: 'Roles', href: index.url() }],
};
