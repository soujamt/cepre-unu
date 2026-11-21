import { Building } from '@keyline-icons/react';
import { CampoSelect, CampoTexto } from '@/components/campo';
import { ListadoCatalogo } from '@/components/listado-catalogo';
import { Badge } from '@/components/ui/badge';
import { useCatalogo } from '@/hooks/use-catalogo';
import { inicio } from '@/routes';
import { destroy, estado, index, store, update } from '@/routes/mantenedor-aulas';
import type { FiltrosCatalogo, Opcion, Paginado, Registro } from '@/types';

interface Aula {
    id: number;
    nombre: string;
    piso: string;
    lugar: string;
    orden: number;
    /** Asignaciones del aula en los procesos. */
    uso: number;
    estado: string;
    registro: Registro | null;
}

interface PropsAulas {
    aulas: Paginado<Aula>;
    filtros: FiltrosCatalogo;
    pisos: Opcion[];
    lugares: Opcion[];
}

/** Color de cada local, el mismo del sistema anterior. */
const claseLugar: Record<string, string> = {
    UNU: 'bg-sky-400/15 text-sky-800 dark:text-sky-300',
    UAP: 'bg-rose-400/15 text-rose-700 dark:text-rose-300',
};

export default function Aulas({ aulas, filtros, pisos, lugares }: PropsAulas) {
    const { formulario, control } = useCatalogo({
        vacio: { nombre: '', piso: '', lugar: '', orden: '' },
        aFormulario: (aula: Aula) => ({ nombre: aula.nombre, piso: aula.piso, lugar: aula.lugar, orden: String(aula.orden) }),
        idDe: (aula) => aula.id,
        nombreDe: (aula) => aula.nombre,
        rutas: { store, update, estado, destroy },
        titulos: { nuevo: 'Nueva aula', editar: 'Editar aula', entidad: 'el aula' },
    });

    return (
        <ListadoCatalogo
            permiso="aula"
            paginado={aulas}
            filtros={filtros}
            catalogo={control}
            placeholderBusqueda="Buscar por nombre, piso o lugar"
            vacio={{ icono: Building, titulo: 'No se encontraron aulas registradas.' }}
            columnas={[
                { clave: 'nombre', titulo: 'Nombre', className: 'font-medium text-zinc-800 dark:text-zinc-100', celda: (aula) => aula.nombre },
                { clave: 'piso', titulo: 'Piso', celda: (aula) => aula.piso },
                {
                    clave: 'lugar',
                    titulo: 'Lugar',
                    celda: (aula) => (
                        <Badge variant="secondary" className={claseLugar[aula.lugar]}>
                            {aula.lugar}
                        </Badge>
                    ),
                },
                { clave: 'orden', titulo: 'Orden', className: 'w-20 tabular-nums', celda: (aula) => aula.orden },
                { clave: 'uso', titulo: 'Uso', className: 'w-24 tabular-nums', celda: (aula) => aula.uso },
            ]}
            campos={
                <>
                    <CampoTexto
                        etiqueta="Nombre"
                        value={formulario.data.nombre}
                        onChange={(evento) => formulario.setData('nombre', evento.target.value)}
                        error={formulario.errors.nombre}
                        requerido
                    />
                    <div className="grid gap-4 sm:grid-cols-2">
                        <CampoSelect
                            etiqueta="Piso"
                            value={formulario.data.piso}
                            onChange={(evento) => formulario.setData('piso', evento.target.value)}
                            error={formulario.errors.piso}
                            requerido
                        >
                            <option value="">Seleccionar</option>
                            {pisos.map((piso) => (
                                <option key={piso.valor} value={piso.valor}>
                                    {piso.nombre}
                                </option>
                            ))}
                        </CampoSelect>
                        <CampoSelect
                            etiqueta="Lugar"
                            value={formulario.data.lugar}
                            onChange={(evento) => formulario.setData('lugar', evento.target.value)}
                            error={formulario.errors.lugar}
                            requerido
                        >
                            <option value="">Seleccionar</option>
                            {lugares.map((lugar) => (
                                <option key={lugar.valor} value={lugar.valor}>
                                    {lugar.nombre}
                                </option>
                            ))}
                        </CampoSelect>
                    </div>
                    <CampoTexto
                        etiqueta="Orden"
                        type="number"
                        inputMode="numeric"
                        min={1}
                        value={formulario.data.orden}
                        onChange={(evento) => formulario.setData('orden', evento.target.value)}
                        error={formulario.errors.orden}
                        requerido
                    />
                </>
            }
        />
    );
}

Aulas.layout = {
    titulo: 'Aulas',
    icono: 'aulas',
    migas: [{ titulo: 'Inicio', href: inicio.url() }, { titulo: 'Aulas', href: index.url() }],
};
