import { GraduationCap } from '@keyline-icons/react';
import { CampoTexto } from '@/components/campo';
import { ListadoCatalogo } from '@/components/listado-catalogo';
import { useCatalogo } from '@/hooks/use-catalogo';
import { inicio } from '@/routes';
import { destroy, estado, index, store, update } from '@/routes/carreras';
import type { FiltrosCatalogo, Paginado, Registro } from '@/types';

interface Carrera {
    id: number;
    codigo: string;
    nombre: string;
    procesos: number;
    estado: string;
    registro: Registro | null;
}

export default function Carreras({ carreras, filtros }: { carreras: Paginado<Carrera>; filtros: FiltrosCatalogo }) {
    const { formulario, control } = useCatalogo({
        vacio: { codigo: '', nombre: '' },
        aFormulario: (carrera: Carrera) => ({ codigo: carrera.codigo, nombre: carrera.nombre }),
        idDe: (carrera) => carrera.id,
        nombreDe: (carrera) => carrera.nombre,
        rutas: { store, update, estado, destroy },
        titulos: { nuevo: 'Nueva carrera', editar: 'Editar carrera', entidad: 'la carrera' },
    });

    return (
        <ListadoCatalogo
            permiso="carrera"
            paginado={carreras}
            filtros={filtros}
            catalogo={control}
            placeholderBusqueda="Buscar por código o nombre"
            vacio={{ icono: GraduationCap, titulo: 'No se encontraron carreras registradas.' }}
            columnas={[
                { clave: 'codigo', titulo: 'Código', className: 'w-28', celda: (carrera) => <span className="font-mono text-xs font-medium text-zinc-600 dark:text-zinc-300">{carrera.codigo}</span> },
                { clave: 'nombre', titulo: 'Nombre', className: 'whitespace-normal font-medium text-zinc-800 dark:text-zinc-100', celda: (carrera) => carrera.nombre },
                { clave: 'procesos', titulo: 'Procesos', className: 'w-24 tabular-nums', celda: (carrera) => carrera.procesos },
            ]}
            campos={
                <>
                    <CampoTexto
                        etiqueta="Código"
                        value={formulario.data.codigo}
                        onChange={(evento) => formulario.setData('codigo', evento.target.value)}
                        error={formulario.errors.codigo}
                        maxLength={20}
                        requerido
                    />
                    <CampoTexto
                        etiqueta="Nombre"
                        value={formulario.data.nombre}
                        onChange={(evento) => formulario.setData('nombre', evento.target.value)}
                        error={formulario.errors.nombre}
                        requerido
                    />
                </>
            }
        />
    );
}

Carreras.layout = {
    titulo: 'Carreras',
    subtitulo: 'Carreras profesionales que se ofrecen en los procesos de admisión.',
    icono: 'carreras',
    migas: [{ titulo: 'Inicio', href: inicio.url() }, { titulo: 'Mantenedores' }, { titulo: 'Carreras', href: index.url() }],
};
