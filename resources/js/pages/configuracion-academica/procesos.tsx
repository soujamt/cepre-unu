import { CalendarDays } from '@keyline-icons/react/fill';
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
    /** Primer año de egreso que es EXTRAORDINARIO (el configurado o el automático). */
    extraordinarioDesde: number;
    /** No se configuró: se usa el año de apertura del proceso. */
    extraordinarioAutomatico: boolean;
    /** Carreras, modalidades y reglas de pago configuradas. */
    uso: number;
    estado: string;
    registro: Registro | null;
}

/**
 * Explica el corte de modalidad con el año escrito o, si está vacío, con el de apertura.
 */
function ayudaExtraordinario(desde: string, apertura: string): string {
    const anio = desde.length === 4 ? Number(desde) : Number(apertura.slice(0, 4)) || null;

    if (anio === null) {
        return 'Quien egresa desde ese año postula como EXTRAORDINARIO O RESERVA; antes, ORDINARIO. Vacío: el año de apertura.';
    }

    const origen = desde.length === 4 ? '' : ' (vacío: se usa el año de apertura)';

    return `Egresados hasta ${anio - 1}: ORDINARIO. Desde ${anio}: EXTRAORDINARIO O RESERVA${origen}.`;
}

export default function Procesos({ procesos, filtros }: { procesos: Paginado<Proceso>; filtros: FiltrosCatalogo }) {
    const { formulario, control } = useCatalogo({
        vacio: { nombre: '', fecha_inicio: '', fecha_fin: '', anio_extraordinario_desde: '' },
        aFormulario: (proceso: Proceso) => ({
            nombre: proceso.nombre,
            fecha_inicio: proceso.aperturaValor,
            fecha_fin: proceso.cierreValor,
            anio_extraordinario_desde: proceso.extraordinarioAutomatico ? '' : String(proceso.extraordinarioDesde),
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
                {
                    clave: 'egreso',
                    titulo: 'Extraordinario desde',
                    className: 'tabular-nums',
                    celda: (proceso) => (
                        <>
                            <span className="block font-medium text-zinc-900 dark:text-white">{proceso.extraordinarioDesde}</span>
                            <span className="block text-xs text-zinc-500 dark:text-zinc-400">
                                {proceso.extraordinarioAutomatico ? 'Automático (año de apertura)' : `Ordinario hasta ${proceso.extraordinarioDesde - 1}`}
                            </span>
                        </>
                    ),
                },
                { clave: 'uso', titulo: 'Uso', className: 'w-20 tabular-nums', celda: (proceso) => proceso.uso },
            ]}
            campos={
                <>
                    <CampoTexto
                        etiqueta="Nombre"
                        value={formulario.data.nombre}
                        onChange={(evento) => formulario.setData('nombre', evento.target.value)}
                        error={formulario.errors.nombre}
                        ayuda="Año y periodo en romanos: 2026-I, 2026-II o 2026-III."
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
                        etiqueta="Extraordinario desde el año de egreso"
                        inputMode="numeric"
                        maxLength={4}
                        value={formulario.data.anio_extraordinario_desde}
                        onChange={(evento) => formulario.setData('anio_extraordinario_desde', evento.target.value.replace(/\D/g, ''))}
                        error={formulario.errors.anio_extraordinario_desde}
                        ayuda={ayudaExtraordinario(formulario.data.anio_extraordinario_desde, formulario.data.fecha_inicio)}
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
