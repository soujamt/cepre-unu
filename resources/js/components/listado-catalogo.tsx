import { Bin, PenLine, Plus, RefreshCw } from '@keyline-icons/react';
import type { ReactNode } from 'react';
import { FiltroEstado } from '@/components/filtro-estado';
import { HojaFormulario } from '@/components/hoja-formulario';
import { InsigniaEstado } from '@/components/insignia-estado';
import { MenuAcciones } from '@/components/menu-acciones';
import type { AccionFila } from '@/components/menu-acciones';
import { EstadoVacio } from '@/components/panel';
import { Buscador, CeldaRegistro, Paginacion, TablaDatos, useFiltros } from '@/components/tabla-datos';
import type { Columna } from '@/components/tabla-datos';
import { Button } from '@/components/ui/button';
import type { ControlCatalogo, LlaveCatalogo } from '@/hooks/use-catalogo';
import { usePermiso } from '@/hooks/use-permiso';
import type { Icono } from '@/lib/iconos';
import type { FiltrosCatalogo, Paginado, Registro } from '@/types';

interface FilaCatalogo {
    id: LlaveCatalogo;
    estado: string;
    registro: Registro | null;
}

interface PropsListadoCatalogo<TFila extends FilaCatalogo> {
    /** Prefijo de los permisos: "carrera" para carrera_crear, carrera_editar… */
    permiso: string;
    paginado: Paginado<TFila>;
    filtros: FiltrosCatalogo;
    catalogo: ControlCatalogo<TFila>;
    /** Columnas propias del catálogo; N°, estado, registro y acciones se agregan solas. */
    columnas: Columna<TFila>[];
    placeholderBusqueda: string;
    vacio: { icono: Icono; titulo: string };
    /** Filtros adicionales junto al de estado (por ejemplo, el tipo de colegio). */
    filtrosExtra?: (cambiar: (cambios: Partial<FiltrosCatalogo>, inmediato?: boolean) => void, filtro: FiltrosCatalogo) => ReactNode;
    /** Campos del formulario lateral. */
    campos: ReactNode;
    /** Acciones propias del catálogo, entre Estado y Eliminar (por ejemplo, "Permisos" en roles). */
    accionesExtra?: (fila: TFila) => AccionFila[];
    /** Oculta una acción estándar en una fila (por ejemplo, el estado del propio usuario). */
    ocultarAccion?: (fila: TFila, accion: 'editar' | 'estado' | 'eliminar') => boolean;
    /** Texto del botón para registrar; por defecto "Nueva". */
    textoNuevo?: string;
}

/**
 * Pantalla estándar de un mantenedor: búsqueda, filtro por estado, botón Nueva,
 * tabla paginada con acciones por fila y formulario lateral para registrar o editar.
 */
export function ListadoCatalogo<TFila extends FilaCatalogo>({
    permiso,
    paginado,
    filtros,
    catalogo,
    columnas,
    placeholderBusqueda,
    vacio,
    filtrosExtra,
    campos,
    accionesExtra,
    ocultarAccion,
    textoNuevo = 'Nueva',
}: PropsListadoCatalogo<TFila>) {
    const puede = usePermiso();
    const [filtro, cambiarFiltro] = useFiltros<FiltrosCatalogo>(filtros);

    return (
        <div className="space-y-5">
            <div className="flex flex-wrap items-center gap-3">
                <Buscador valor={String(filtro.buscar)} alCambiar={(buscar) => cambiarFiltro({ buscar })} placeholder={placeholderBusqueda} />
                {filtrosExtra?.(cambiarFiltro, filtro)}
                <FiltroEstado seleccionados={filtro.estado as string[]} alCambiar={(estados) => cambiarFiltro({ estado: estados }, true)} />
                {puede(`${permiso}_crear`) && (
                    <Button className="w-full sm:ms-auto sm:w-auto" onClick={catalogo.abrirNuevo}>
                        <Plus className="size-4.5" />
                        {textoNuevo}
                    </Button>
                )}
            </div>

            <TablaDatos
                columnas={[
                    { clave: 'numero', titulo: 'N°', className: 'w-16 tabular-nums', celda: (_fila, indice) => (paginado.from ?? 1) + indice },
                    ...columnas,
                    { clave: 'estado', titulo: 'Estado', celda: (fila) => <InsigniaEstado estado={fila.estado} /> },
                    { clave: 'registro', titulo: 'Registro', celda: (fila) => <CeldaRegistro registro={fila.registro} /> },
                    {
                        clave: 'acciones',
                        titulo: <span className="sr-only">Acciones</span>,
                        className: 'w-14 text-end',
                        celda: (fila) => (
                            <MenuAcciones
                                acciones={[
                                    { etiqueta: 'Editar', icono: PenLine, alSeleccionar: () => catalogo.abrirEdicion(fila), visible: puede(`${permiso}_editar`) && !ocultarAccion?.(fila, 'editar') },
                                    { etiqueta: 'Estado', icono: RefreshCw, alSeleccionar: () => void catalogo.cambiarEstado(fila), visible: puede(`${permiso}_estado`) && !ocultarAccion?.(fila, 'estado') },
                                    ...(accionesExtra?.(fila) ?? []),
                                    { etiqueta: 'Eliminar', icono: Bin, alSeleccionar: () => void catalogo.eliminar(fila), peligro: true, visible: puede(`${permiso}_eliminar`) && !ocultarAccion?.(fila, 'eliminar') },
                                ]}
                            />
                        ),
                    },
                ]}
                filas={paginado.data}
                claveFila={(fila) => fila.id}
                vacio={<EstadoVacio icono={vacio.icono} titulo={vacio.titulo} />}
            />

            <Paginacion paginado={paginado} />

            <HojaFormulario
                abierta={catalogo.hoja.abierta}
                alCerrar={catalogo.hoja.cerrar}
                titulo={catalogo.hoja.titulo}
                alEnviar={catalogo.guardar}
                procesando={catalogo.procesando}
            >
                {campos}
            </HojaFormulario>
        </div>
    );
}
