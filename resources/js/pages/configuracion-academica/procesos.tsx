import { CalendarDays } from '@keyline-icons/react';
import { CampoTexto } from '@/components/campo';
import { ListadoCatalogo } from '@/components/listado-catalogo';
import { CeldaRegistro } from '@/components/tabla-datos';
import { useCatalogo } from '@/hooks/use-catalogo';
import { inicio } from '@/routes';
import { destroy, estado, index, store, update } from '@/routes/procesos';
import type { FiltrosCatalogo, Paginado, Registro } from '@/types';

interface Proceso {
    id: number;
    nombre: string;
    codigo: string;
    apertura: Registro | null;
    cierre: Registro | null;
    /** Fechas en formato del campo datetime-local para editar. */
    aperturaValor: string;
    cierreValor: string;
    anioEgreso: number | null;
    /** Carreras, modalidades y reglas de pago configuradas. */
    uso: number;
    estado: string;
    registro: Registro | null;
}

export default function Procesos({ procesos, filtros }: { procesos: Paginado<Proceso>; filtros: FiltrosCatalogo }) {
    const { formulario, control } = useCatalogo({
        vacio: { nombre: '', fecha_inicio: '', fecha_fin: '', anio_egreso_max_ordinario: '' },
        aFormulario: (proceso: Proceso) => ({
            nombre: proceso.nombre,
            fecha_inicio: proceso.aperturaValor,
            fecha_fin: proceso.cierreValor,
            anio_egreso_max_ordinario: proceso.anioEgreso === null ? '' : String(proceso.anioEgreso),
        }),
        idDe: (proceso) => proceso.id,
        nombreDe: (proceso) => proceso.nombre,
        rutas: { store, update, estado, destroy },
        titulos: { nuevo: 'Nuevo proceso', editar: 'Editar proceso', entidad: 'el proceso' },
    });

    return (
        <ListadoCatalogo
            permiso="proceso"
            paginado={procesos}
            filtros={filtros}
            catalogo={control}
            textoNuevo="Nuevo"
            estadosFiltro={['HABILITADO', 'DESHABILITADO', 'CERRADO']}
            placeholderBusqueda="Buscar por nombre o código"
            vacio={{ icono: CalendarDays, titulo: 'No se encontraron procesos registrados.' }}
            ocultarAccion={(proceso, accion) => accion === 'estado' && proceso.estado === 'CERRADO'}
            columnas={[
                {
                    clave: 'nombre',
                    titulo: 'Proceso',
                    celda: (proceso) => (
                        <>
                            <span className="block font-medium text-zinc-900 dark:text-white">{proceso.nombre}</span>
                            <span className="block font-mono text-xs text-zinc-500 dark:text-zinc-400">{proceso.codigo}</span>
                        </>
                    ),
                },
                { clave: 'apertura', titulo: 'Apertura', celda: (proceso) => <CeldaRegistro registro={proceso.apertura} /> },
                { clave: 'cierre', titulo: 'Cierre', celda: (proceso) => <CeldaRegistro registro={proceso.cierre} /> },
                { clave: 'egreso', titulo: 'Egreso ordinario', className: 'tabular-nums', celda: (proceso) => proceso.anioEgreso ?? <span className="text-zinc-400 dark:text-zinc-500">—</span> },
                { clave: 'uso', titulo: 'Uso', className: 'w-20 tabular-nums', celda: (proceso) => proceso.uso },
            ]}
            campos={
                <>
                    <CampoTexto
                        etiqueta="Nombre"
                        value={formulario.data.nombre}
                        onChange={(evento) => formulario.setData('nombre', evento.target.value)}
                        error={formulario.errors.nombre}
                        ayuda="Formato AAAA-N, por ejemplo 2026-2."
                        maxLength={20}
                        requerido
                    />
                    <CampoTexto
                        etiqueta="Apertura (fecha y hora)"
                        type="datetime-local"
                        value={formulario.data.fecha_inicio}
                        onChange={(evento) => formulario.setData('fecha_inicio', evento.target.value)}
                        error={formulario.errors.fecha_inicio}
                        requerido
                    />
                    <CampoTexto
                        etiqueta="Cierre (fecha y hora)"
                        type="datetime-local"
                        value={formulario.data.fecha_fin}
                        onChange={(evento) => formulario.setData('fecha_fin', evento.target.value)}
                        error={formulario.errors.fecha_fin}
                        requerido
                    />
                    <CampoTexto
                        etiqueta="Año de egreso máximo para Ordinario"
                        inputMode="numeric"
                        maxLength={4}
                        value={formulario.data.anio_egreso_max_ordinario}
                        onChange={(evento) => formulario.setData('anio_egreso_max_ordinario', evento.target.value.replace(/\D/g, ''))}
                        error={formulario.errors.anio_egreso_max_ordinario}
                        ayuda="Los egresados hasta ese año postulan por Ordinario; vacío si no aplica."
                    />
                </>
            }
        />
    );
}

Procesos.layout = {
    titulo: 'Procesos',
    subtitulo: 'Procesos de admisión con sus fechas de inscripción; al vencer se cierran solos.',
    icono: 'procesos',
    migas: [{ titulo: 'Inicio', href: inicio.url() }, { titulo: 'Configuración académica' }, { titulo: 'Procesos', href: index.url() }],
};
