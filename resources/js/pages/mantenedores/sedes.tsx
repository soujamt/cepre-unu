import { MapPin } from '@keyline-icons/react';
import { useState } from 'react';
import { CampoArea, CampoTexto } from '@/components/campo';
import { Combobox } from '@/components/combobox';
import { ListadoCatalogo } from '@/components/listado-catalogo';
import { useCatalogo } from '@/hooks/use-catalogo';
import { buscarUbigeos } from '@/lib/busquedas';
import { inicio } from '@/routes';
import { destroy, estado, index, store, update } from '@/routes/sedes';
import type { FiltrosCatalogo, Paginado, Registro } from '@/types';

interface Sede {
    id: number;
    codigo: string;
    nombre: string;
    direccion: string | null;
    ubigeo: { codigo: string; etiqueta: string; distrito: string; provinciaDepartamento: string } | null;
    /** Inscripciones no anuladas en la sede. */
    inscritos: number;
    estado: string;
    registro: Registro | null;
}

export default function Sedes({ sedes, filtros }: { sedes: Paginado<Sede>; filtros: FiltrosCatalogo }) {
    const [etiquetaUbigeo, setEtiquetaUbigeo] = useState('');
    const { formulario, control } = useCatalogo({
        vacio: { codigo: '', nombre: '', id_ubigeo: '', direccion: '' },
        aFormulario: (sede: Sede) => ({ codigo: sede.codigo, nombre: sede.nombre, id_ubigeo: sede.ubigeo?.codigo ?? '', direccion: sede.direccion ?? '' }),
        idDe: (sede) => sede.id,
        nombreDe: (sede) => sede.nombre,
        rutas: { store, update, estado, destroy },
        titulos: { nuevo: 'Nueva sede', editar: 'Editar sede', entidad: 'la sede' },
        alAbrir: (sede) => setEtiquetaUbigeo(sede?.ubigeo?.etiqueta ?? ''),
    });

    return (
        <ListadoCatalogo
            permiso="sede"
            paginado={sedes}
            filtros={filtros}
            catalogo={control}
            placeholderBusqueda="Buscar por código, nombre o dirección"
            vacio={{ icono: MapPin, titulo: 'No se encontraron sedes registradas.' }}
            columnas={[
                { clave: 'codigo', titulo: 'Código', className: 'w-28', celda: (sede) => <span className="font-mono text-xs font-medium text-zinc-600 dark:text-zinc-300">{sede.codigo}</span> },
                { clave: 'nombre', titulo: 'Nombre', className: 'whitespace-normal font-medium text-zinc-800 dark:text-zinc-100', celda: (sede) => sede.nombre },
                {
                    clave: 'ubicacion',
                    titulo: 'Ubicación',
                    className: 'whitespace-normal',
                    celda: (sede) => (
                        <>
                            {sede.ubigeo && (
                                <>
                                    <span className="block">{sede.ubigeo.distrito}</span>
                                    <span className="block text-xs text-zinc-400 dark:text-zinc-500">{sede.ubigeo.provinciaDepartamento}</span>
                                </>
                            )}
                            {sede.direccion && <span className="block text-xs text-zinc-400 dark:text-zinc-500">{sede.direccion}</span>}
                        </>
                    ),
                },
                { clave: 'inscritos', titulo: 'Inscritos', className: 'w-24 tabular-nums', celda: (sede) => sede.inscritos },
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
                        requerido
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

Sedes.layout = {
    titulo: 'Sedes',
    icono: 'sedes',
    migas: [{ titulo: 'Inicio', href: inicio.url() }, { titulo: 'Sedes', href: index.url() }],
};
