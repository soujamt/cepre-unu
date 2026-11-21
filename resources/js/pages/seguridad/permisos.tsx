import { ShieldKey } from '@keyline-icons/react';
import { CampoOpciones, CampoTexto } from '@/components/campo';
import { ListadoCatalogo } from '@/components/listado-catalogo';
import { Badge } from '@/components/ui/badge';
import { useCatalogo } from '@/hooks/use-catalogo';
import { inicio } from '@/routes';
import { destroy, estado, index, store, update } from '@/routes/permisos';
import type { FiltrosCatalogo, Opcion, Paginado, Registro } from '@/types';

interface Permiso {
    id: number;
    nombre: string;
    /** Clave generada a partir del nombre, base de las claves de sus acciones (usuario_crear…). */
    clave: string;
    acciones: { id: number; nombre: string }[];
    estado: string;
    registro: Registro | null;
}

interface PropsPermisos {
    permisos: Paginado<Permiso>;
    filtros: FiltrosCatalogo;
    acciones: Opcion[];
}

export default function Permisos({ permisos, filtros, acciones }: PropsPermisos) {
    const { formulario, control } = useCatalogo({
        vacio: { nombre: '', acciones: [] as string[] },
        aFormulario: (permiso: Permiso) => ({ nombre: permiso.nombre, acciones: permiso.acciones.map((accion) => String(accion.id)) }),
        idDe: (permiso) => permiso.id,
        nombreDe: (permiso) => permiso.nombre,
        rutas: { store, update, estado, destroy },
        titulos: { nuevo: 'Nuevo permiso', editar: 'Editar permiso', entidad: 'el permiso' },
    });

    return (
        <ListadoCatalogo
            permiso="permiso"
            paginado={permisos}
            filtros={filtros}
            catalogo={control}
            textoNuevo="Nuevo"
            placeholderBusqueda="Buscar por nombre o clave"
            vacio={{ icono: ShieldKey, titulo: 'No se encontraron permisos registrados.' }}
            columnas={[
                {
                    clave: 'nombre',
                    titulo: 'Permiso',
                    celda: (permiso) => (
                        <>
                            <span className="block font-medium text-zinc-900 dark:text-white">{permiso.nombre}</span>
                            <span className="block font-mono text-xs text-zinc-500 dark:text-zinc-400">{permiso.clave}</span>
                        </>
                    ),
                },
                {
                    clave: 'acciones',
                    titulo: 'Acciones',
                    className: 'whitespace-normal',
                    celda: (permiso) =>
                        permiso.acciones.length === 0 ? (
                            <span className="text-sm text-zinc-400 dark:text-zinc-500">Sin acciones</span>
                        ) : (
                            <span className="flex max-w-md flex-wrap gap-1.5">
                                {permiso.acciones.map((accion) => (
                                    <Badge key={accion.id} variant="secondary">
                                        {accion.nombre}
                                    </Badge>
                                ))}
                            </span>
                        ),
                },
            ]}
            campos={
                <>
                    <CampoTexto
                        etiqueta="Nombre"
                        value={formulario.data.nombre}
                        onChange={(evento) => formulario.setData('nombre', evento.target.value)}
                        error={formulario.errors.nombre}
                        ayuda="La clave se genera del nombre: «Reglas de pago» → reglas_de_pago."
                        requerido
                    />
                    <CampoOpciones
                        etiqueta="Acciones"
                        opciones={acciones}
                        seleccionadas={formulario.data.acciones}
                        alCambiar={(seleccionadas) => formulario.setData('acciones', seleccionadas)}
                        error={formulario.errors.acciones}
                        columnas={2}
                        requerido
                    />
                </>
            }
        />
    );
}

Permisos.layout = {
    titulo: 'Permisos',
    subtitulo: 'Módulos del sistema y las acciones que se pueden autorizar en cada uno.',
    icono: 'permisos',
    migas: [{ titulo: 'Inicio', href: inicio.url() }, { titulo: 'Seguridad' }, { titulo: 'Permisos', href: index.url() }],
};
