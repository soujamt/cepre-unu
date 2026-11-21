import { School } from '@keyline-icons/react';
import { useState } from 'react';
import { CampoArea, CampoSelect, CampoTexto } from '@/components/campo';
import { Combobox } from '@/components/combobox';
import { FiltroLista } from '@/components/filtro-lista';
import { ListadoCatalogo } from '@/components/listado-catalogo';
import { useCatalogo } from '@/hooks/use-catalogo';
import { buscarUbigeos } from '@/lib/busquedas';
import { inicio } from '@/routes';
import { destroy, estado, index, store, update } from '@/routes/mantenedor-colegios';
import type { FiltrosCatalogo, Opcion, Paginado, Registro } from '@/types';

interface Colegio {
    /** Código del colegio; es su llave y no cambia al editar. */
    id: string;
    codigoModular: string | null;
    nombre: string;
    idTipo: number | null;
    tipo: string | null;
    ubigeo: { codigo: string; etiqueta: string; ubicacion: string } | null;
    direccion: string | null;
    /** Inscripciones que registran el colegio. */
    uso: number;
    estado: string;
    registro: Registro | null;
}

interface PropsColegios {
    colegios: Paginado<Colegio>;
    filtros: FiltrosCatalogo & { tipo: string };
    tipos: Opcion[];
}

const textoTenue = 'text-zinc-400 dark:text-zinc-500';

export default function Colegios({ colegios, filtros, tipos }: PropsColegios) {
    const [etiquetaUbigeo, setEtiquetaUbigeo] = useState('');
    const { formulario, control } = useCatalogo({
        vacio: { id: '', codigo_modular: '', nombre: '', id_tipo_colegio: '', id_ubigeo: '', direccion: '' },
        aFormulario: (colegio: Colegio) => ({
            id: colegio.id,
            codigo_modular: colegio.codigoModular ?? '',
            nombre: colegio.nombre,
            id_tipo_colegio: colegio.idTipo === null ? '' : String(colegio.idTipo),
            id_ubigeo: colegio.ubigeo?.codigo ?? '',
            direccion: colegio.direccion ?? '',
        }),
        idDe: (colegio) => colegio.id,
        nombreDe: (colegio) => colegio.nombre,
        rutas: { store, update, estado, destroy },
        titulos: { nuevo: 'Nuevo colegio', editar: 'Editar colegio', entidad: 'el colegio' },
        alAbrir: (colegio) => setEtiquetaUbigeo(colegio?.ubigeo?.etiqueta ?? ''),
    });

    return (
        <ListadoCatalogo
            permiso="colegio"
            paginado={colegios}
            filtros={filtros}
            catalogo={control}
            placeholderBusqueda="Buscar por código, nombre o dirección"
            vacio={{ icono: School, titulo: 'No se encontraron colegios registrados.' }}
            filtrosExtra={(cambiar, filtro) => (
                <FiltroLista valor={String(filtro.tipo ?? '')} alCambiar={(tipo) => cambiar({ tipo }, true)} opciones={tipos} textoTodos="Todos los tipos" />
            )}
            columnas={[
                { clave: 'codigo', titulo: 'Código', celda: (colegio) => <span className="font-mono text-xs font-medium text-zinc-700 dark:text-zinc-200">{colegio.id}</span> },
                { clave: 'modular', titulo: 'Modular', celda: (colegio) => colegio.codigoModular ?? <span className={textoTenue}>Sin código</span> },
                { clave: 'nombre', titulo: 'Nombre', className: 'whitespace-normal font-medium text-zinc-800 dark:text-zinc-100', celda: (colegio) => colegio.nombre },
                { clave: 'tipo', titulo: 'Tipo', celda: (colegio) => colegio.tipo ?? <span className={textoTenue}>Sin tipo</span> },
                {
                    clave: 'ubicacion',
                    titulo: 'Ubicación',
                    className: 'whitespace-normal',
                    celda: (colegio) => (
                        <>
                            <span className="block">{colegio.ubigeo?.ubicacion ?? <span className={textoTenue}>Sin ubigeo</span>}</span>
                            {colegio.direccion && <span className={`block text-xs ${textoTenue}`}>{colegio.direccion}</span>}
                        </>
                    ),
                },
                { clave: 'uso', titulo: 'Uso', className: 'w-20 tabular-nums', celda: (colegio) => colegio.uso },
            ]}
            campos={
                <>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <CampoTexto
                            etiqueta="Código"
                            value={formulario.data.id}
                            onChange={(evento) => formulario.setData('id', evento.target.value)}
                            error={formulario.errors.id}
                            maxLength={10}
                            disabled={control.editando}
                            ayuda={control.editando ? 'El código no se puede cambiar.' : undefined}
                            requerido
                        />
                        <CampoTexto
                            etiqueta="Código modular"
                            value={formulario.data.codigo_modular}
                            onChange={(evento) => formulario.setData('codigo_modular', evento.target.value)}
                            error={formulario.errors.codigo_modular}
                        />
                    </div>
                    <CampoTexto
                        etiqueta="Nombre"
                        value={formulario.data.nombre}
                        onChange={(evento) => formulario.setData('nombre', evento.target.value)}
                        error={formulario.errors.nombre}
                        requerido
                    />
                    <CampoSelect
                        etiqueta="Tipo de colegio"
                        value={formulario.data.id_tipo_colegio}
                        onChange={(evento) => formulario.setData('id_tipo_colegio', evento.target.value)}
                        error={formulario.errors.id_tipo_colegio}
                    >
                        <option value="">Seleccionar</option>
                        {tipos.map((tipo) => (
                            <option key={tipo.valor} value={tipo.valor}>
                                {tipo.nombre}
                            </option>
                        ))}
                    </CampoSelect>
                    <Combobox
                        etiqueta="Ubigeo"
                        valor={formulario.data.id_ubigeo}
                        alCambiar={(valor, opcion) => {
                            formulario.setData('id_ubigeo', valor);
                            setEtiquetaUbigeo(opcion?.etiqueta ?? '');
                        }}
                        buscar={buscarUbigeos}
                        etiquetaSeleccionada={etiquetaUbigeo}
                        placeholderBusqueda="Escriba al menos 2 letras…"
                        error={formulario.errors.id_ubigeo}
                    />
                    <CampoArea
                        etiqueta="Dirección"
                        value={formulario.data.direccion}
                        onChange={(evento) => formulario.setData('direccion', evento.target.value)}
                        error={formulario.errors.direccion}
                        maxLength={255}
                    />
                </>
            }
        />
    );
}

Colegios.layout = {
    titulo: 'Colegios',
    subtitulo: 'Colegios de procedencia de los postulantes, con su tipo y ubicación.',
    icono: 'colegios',
    migas: [{ titulo: 'Inicio', href: inicio.url() }, { titulo: 'Mantenedores' }, { titulo: 'Colegios', href: index.url() }],
};
