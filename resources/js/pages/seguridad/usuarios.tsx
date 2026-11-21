import { Users } from '@keyline-icons/react/fill';
import { CampoOpciones, CampoTexto } from '@/components/campo';
import { ListadoCatalogo } from '@/components/listado-catalogo';
import { Badge } from '@/components/ui/badge';
import { useCatalogo } from '@/hooks/use-catalogo';
import { inicio } from '@/routes';
import { destroy, estado, index, store, update } from '@/routes/usuarios';
import type { FiltrosCatalogo, Opcion, Paginado, Registro } from '@/types';

interface Usuario {
    id: number;
    nombres: string;
    correo: string | null;
    iniciales: string;
    roles: { id: number; nombre: string }[];
    /** Es el usuario con la sesión iniciada: no puede deshabilitarse ni eliminarse a sí mismo. */
    esUsted: boolean;
    estado: string;
    registro: Registro | null;
}

interface PropsUsuarios {
    usuarios: Paginado<Usuario>;
    filtros: FiltrosCatalogo;
    roles: Opcion[];
}

export default function Usuarios({ usuarios, filtros, roles }: PropsUsuarios) {
    const { formulario, control } = useCatalogo({
        vacio: { nombres: '', correo: '', password: '', roles: [] as string[] },
        aFormulario: (usuario: Usuario) => ({
            nombres: usuario.nombres,
            correo: usuario.correo ?? '',
            password: '',
            roles: usuario.roles.map((rol) => String(rol.id)),
        }),
        idDe: (usuario) => usuario.id,
        nombreDe: (usuario) => usuario.nombres,
        rutas: { store, update, estado, destroy },
        titulos: { nuevo: 'Nuevo usuario', editar: 'Editar usuario', entidad: 'el usuario' },
    });

    return (
        <ListadoCatalogo
            permiso="usuario"
            paginado={usuarios}
            filtros={filtros}
            catalogo={control}
            textoNuevo="Nuevo"
            placeholderBusqueda="Buscar por nombre o correo"
            vacio={{ icono: Users, titulo: 'No se encontraron usuarios registrados.' }}
            ocultarAccion={(usuario, accion) => usuario.esUsted && accion !== 'editar'}
            columnas={[
                {
                    clave: 'usuario',
                    titulo: 'Usuario',
                    celda: (usuario) => (
                        <span className="flex items-center gap-3">
                            <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-linear-to-b from-index-400 to-index-600 text-xs font-semibold text-white shadow-[inset_0_1px_0_rgba(255,255,255,0.3)]">
                                {usuario.iniciales}
                            </span>
                            <span className="min-w-0">
                                <span className="flex items-center gap-2 font-medium text-zinc-900 dark:text-white">
                                    <span className="truncate">{usuario.nombres}</span>
                                    {usuario.esUsted && <Badge variant="default">Usted</Badge>}
                                </span>
                                <span className="block truncate text-xs text-zinc-500 dark:text-zinc-400">{usuario.correo ?? 'Sin correo'}</span>
                            </span>
                        </span>
                    ),
                },
                {
                    clave: 'roles',
                    titulo: 'Roles',
                    className: 'whitespace-normal',
                    celda: (usuario) =>
                        usuario.roles.length === 0 ? (
                            <span className="text-sm text-zinc-400 dark:text-zinc-500">Sin roles</span>
                        ) : (
                            <span className="flex flex-wrap items-center gap-1.5">
                                {usuario.roles.slice(0, 2).map((rol) => (
                                    <Badge key={rol.id} variant="secondary">
                                        {rol.nombre}
                                    </Badge>
                                ))}
                                {usuario.roles.length > 2 && <span className="text-xs text-zinc-500 dark:text-zinc-400">+{usuario.roles.length - 2} más</span>}
                            </span>
                        ),
                },
            ]}
            campos={
                <>
                    <CampoTexto
                        etiqueta="Nombres"
                        value={formulario.data.nombres}
                        onChange={(evento) => formulario.setData('nombres', evento.target.value)}
                        error={formulario.errors.nombres}
                        maxLength={150}
                        requerido
                    />
                    <CampoTexto
                        etiqueta="Correo"
                        type="email"
                        autoComplete="off"
                        value={formulario.data.correo}
                        onChange={(evento) => formulario.setData('correo', evento.target.value)}
                        error={formulario.errors.correo}
                        maxLength={150}
                        requerido
                    />
                    <CampoTexto
                        etiqueta="Contraseña"
                        type="password"
                        autoComplete="new-password"
                        value={formulario.data.password}
                        onChange={(evento) => formulario.setData('password', evento.target.value)}
                        error={formulario.errors.password}
                        ayuda={control.editando ? 'Déjela vacía para mantener la contraseña actual.' : 'Mínimo 8 caracteres.'}
                        requerido={!control.editando}
                    />
                    <CampoOpciones
                        etiqueta="Roles"
                        opciones={roles}
                        seleccionadas={formulario.data.roles}
                        alCambiar={(seleccionadas) => formulario.setData('roles', seleccionadas)}
                        error={formulario.errors.roles}
                    />
                </>
            }
        />
    );
}

Usuarios.layout = {
    titulo: 'Usuarios',
    subtitulo: 'Personas con acceso al sistema y los roles que definen lo que pueden hacer.',
    icono: 'usuarios',
    migas: [{ titulo: 'Inicio', href: inicio.url() }, { titulo: 'Seguridad' }, { titulo: 'Usuarios', href: index.url() }],
};
