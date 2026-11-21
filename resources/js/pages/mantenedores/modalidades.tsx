import { ListChecks } from '@keyline-icons/react/fill';
import { CampoArea, CampoSelect, CampoTexto } from '@/components/campo';
import { ListadoCatalogo } from '@/components/listado-catalogo';
import { useCatalogo } from '@/hooks/use-catalogo';
import { inicio } from '@/routes';
import { destroy, estado, index, store, update } from '@/routes/modalidades';
import type { FiltrosCatalogo, Opcion, Paginado, Registro } from '@/types';

interface Modalidad {
    id: number;
    codigo: string;
    nombre: string;
    descripcion: string | null;
    condicionEgreso: string;
    /** Procesos en los que la modalidad está habilitada. */
    procesos: number;
    estado: string;
    registro: Registro | null;
}

interface PropsModalidades {
    modalidades: Paginado<Modalidad>;
    filtros: FiltrosCatalogo;
    condiciones: Opcion[];
}

export default function Modalidades({ modalidades, filtros, condiciones }: PropsModalidades) {
    const { formulario, control } = useCatalogo({
        vacio: { codigo: '', nombre: '', condicion_egreso: '', descripcion: '' },
        aFormulario: (modalidad: Modalidad) => ({
            codigo: modalidad.codigo,
            nombre: modalidad.nombre,
            condicion_egreso: modalidad.condicionEgreso,
            descripcion: modalidad.descripcion ?? '',
        }),
        idDe: (modalidad) => modalidad.id,
        nombreDe: (modalidad) => modalidad.nombre,
        rutas: { store, update, estado, destroy },
        titulos: { nuevo: 'Nueva modalidad', editar: 'Editar modalidad', entidad: 'la modalidad' },
    });

    return (
        <ListadoCatalogo
            permiso="modalidad"
            paginado={modalidades}
            filtros={filtros}
            catalogo={control}
            placeholderBusqueda="Buscar por código o nombre"
            vacio={{ icono: ListChecks, titulo: 'No se encontraron modalidades registradas.' }}
            columnas={[
                { clave: 'codigo', titulo: 'Código', className: 'w-28', celda: (modalidad) => <span className="font-mono text-xs font-medium text-zinc-600 dark:text-zinc-300">{modalidad.codigo}</span> },
                {
                    clave: 'nombre',
                    titulo: 'Nombre',
                    className: 'whitespace-normal',
                    celda: (modalidad) => (
                        <>
                            <span className="block font-medium text-zinc-800 dark:text-zinc-100">{modalidad.nombre}</span>
                            {modalidad.descripcion && <span className="block text-xs text-zinc-500 dark:text-zinc-400">{modalidad.descripcion}</span>}
                        </>
                    ),
                },
                { clave: 'procesos', titulo: 'Procesos', className: 'w-24 tabular-nums', celda: (modalidad) => modalidad.procesos },
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
                    <CampoSelect
                        etiqueta="Aplica a"
                        value={formulario.data.condicion_egreso}
                        onChange={(evento) => formulario.setData('condicion_egreso', evento.target.value)}
                        error={formulario.errors.condicion_egreso}
                        requerido
                    >
                        <option value="">Seleccionar</option>
                        {condiciones.map((condicion) => (
                            <option key={condicion.valor} value={condicion.valor}>
                                {condicion.nombre}
                            </option>
                        ))}
                    </CampoSelect>
                    <CampoArea
                        etiqueta="Descripción"
                        value={formulario.data.descripcion}
                        onChange={(evento) => formulario.setData('descripcion', evento.target.value)}
                        error={formulario.errors.descripcion}
                        maxLength={1000}
                    />
                </>
            }
        />
    );
}

Modalidades.layout = {
    titulo: 'Modalidades',
    subtitulo: 'Modalidades de admisión y a quién aplican según su situación escolar.',
    icono: 'modalidades',
    migas: [{ titulo: 'Inicio', href: inicio.url() }, { titulo: 'Mantenedores' }, { titulo: 'Modalidades', href: index.url() }],
};
