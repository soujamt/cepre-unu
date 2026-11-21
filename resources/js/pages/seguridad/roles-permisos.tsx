import { useForm } from '@inertiajs/react';
import { ArrowLeft, Save, ShieldKey } from '@keyline-icons/react/fill';
import { Aviso } from '@/components/aviso';
import { Enlace } from '@/components/enlace';
import { EstadoVacio } from '@/components/panel';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { cn } from '@/lib/utils';
import { inicio } from '@/routes';
import { index as roles } from '@/routes/roles';
import { update as actualizarPermisos } from '@/routes/roles/permisos';

interface AccionPermiso {
    /** Id de la combinación permiso + acción. */
    id: number;
    nombre: string;
    clave: string;
}

interface PermisoModulo {
    id: number;
    nombre: string;
    clave: string;
    acciones: AccionPermiso[];
}

interface PropsRolesPermisos {
    rol: { id: number; nombre: string; descripcion: string | null; esAdministrador: boolean };
    permisos: PermisoModulo[];
    seleccionadas: number[];
}

export default function RolesPermisos({ rol, permisos, seleccionadas }: PropsRolesPermisos) {
    const formulario = useForm({ seleccionadas });
    const marcadas = new Set(formulario.data.seleccionadas);
    const total = permisos.reduce((suma, permiso) => suma + permiso.acciones.length, 0);
    const bloqueado = rol.esAdministrador;

    const cambiar = (ids: number[], marcar: boolean): void => {
        const nuevas = new Set(formulario.data.seleccionadas);
        ids.forEach((id) => (marcar ? nuevas.add(id) : nuevas.delete(id)));
        formulario.setData('seleccionadas', [...nuevas]);
    };

    const guardar = (): void => {
        formulario.put(actualizarPermisos.url(rol.id), { preserveScroll: true });
    };

    return (
        <div className="space-y-6">
            <div className="flex flex-col gap-4 rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-card sm:flex-row sm:items-center sm:justify-between dark:border-white/[0.07] dark:bg-white/[0.02] dark:shadow-none">
                <div className="min-w-0 space-y-1">
                    <p className="text-sm text-zinc-500 dark:text-zinc-400">Rol</p>
                    <h2 className="flex flex-wrap items-center gap-2 text-lg font-semibold text-zinc-900 dark:text-white">
                        {rol.nombre}
                        {bloqueado ? <Badge variant="default">Acceso total</Badge> : <Badge variant="secondary">{marcadas.size} de {total} acciones</Badge>}
                    </h2>
                    {rol.descripcion && <p className="text-sm text-zinc-500 dark:text-zinc-400">{rol.descripcion}</p>}
                </div>
                <div className="flex shrink-0 gap-2.5">
                    <Button variant="outline" asChild>
                        <Enlace href={roles.url()}>
                            <ArrowLeft className="size-4.5" />
                            Volver
                        </Enlace>
                    </Button>
                    {!bloqueado && (
                        <Button onClick={guardar} disabled={formulario.processing || !formulario.isDirty}>
                            <Save className="size-4.5" />
                            {formulario.processing ? 'Guardando…' : 'Guardar permisos'}
                        </Button>
                    )}
                </div>
            </div>

            {bloqueado && (
                <Aviso tono="info" titulo="El rol ADMINISTRADOR tiene acceso total">
                    No requiere asignación manual de permisos.
                </Aviso>
            )}

            {permisos.length === 0 ? (
                <EstadoVacio icono={ShieldKey} titulo="No hay permisos habilitados." descripcion="Registre permisos con sus acciones para asignarlos a los roles." />
            ) : (
                <div className="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                    {permisos.map((permiso) => {
                        const ids = permiso.acciones.map((accion) => accion.id);
                        const marcadasModulo = ids.filter((id) => marcadas.has(id)).length;
                        const todas = ids.length > 0 && marcadasModulo === ids.length;

                        return (
                            <section key={permiso.id} className="flex flex-col rounded-2xl border border-zinc-200/80 bg-white shadow-card dark:border-white/[0.07] dark:bg-white/[0.02] dark:shadow-none">
                                <header className="flex items-start justify-between gap-3 border-b border-zinc-100 px-4 py-3.5 dark:border-white/[0.05]">
                                    <div className="min-w-0">
                                        <h3 className="truncate text-sm font-semibold text-zinc-900 dark:text-white">{permiso.nombre}</h3>
                                        <p className="truncate font-mono text-xs text-zinc-500 dark:text-zinc-400">{permiso.clave}</p>
                                    </div>
                                    <label className="flex shrink-0 cursor-pointer items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                                        {marcadasModulo}/{ids.length}
                                        <Checkbox
                                            checked={todas ? true : marcadasModulo > 0 ? 'indeterminate' : false}
                                            disabled={bloqueado || ids.length === 0}
                                            onCheckedChange={() => cambiar(ids, !todas)}
                                            aria-label={`Marcar todas las acciones de ${permiso.nombre}`}
                                        />
                                    </label>
                                </header>
                                <div className="grid gap-0.5 p-2">
                                    {permiso.acciones.length === 0 && <p className="px-2 py-2 text-sm text-zinc-500 dark:text-zinc-400">Sin acciones disponibles.</p>}
                                    {permiso.acciones.map((accion) => (
                                        <label
                                            key={accion.id}
                                            className={cn(
                                                'flex cursor-pointer items-center gap-3 rounded-lg px-2.5 py-2 text-sm transition-colors hover:bg-zinc-50 dark:hover:bg-white/[0.03]',
                                                bloqueado && 'cursor-not-allowed opacity-60',
                                            )}
                                        >
                                            <Checkbox checked={bloqueado || marcadas.has(accion.id)} disabled={bloqueado} onCheckedChange={(valor) => cambiar([accion.id], valor === true)} />
                                            <span className="min-w-0 flex-1 truncate text-zinc-700 dark:text-zinc-200">{accion.nombre}</span>
                                            <span className="truncate font-mono text-[11px] text-zinc-400 dark:text-zinc-500">{accion.clave}</span>
                                        </label>
                                    ))}
                                </div>
                            </section>
                        );
                    })}
                </div>
            )}
        </div>
    );
}

RolesPermisos.layout = {
    titulo: 'Permisos del rol',
    subtitulo: 'Marque las acciones que pueden realizar los usuarios con este rol.',
    icono: 'roles',
    migas: [{ titulo: 'Inicio', href: inicio.url() }, { titulo: 'Seguridad' }, { titulo: 'Roles', href: roles.url() }, { titulo: 'Permisos del rol' }],
};
