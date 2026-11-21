import { CircleCheck, Clock, Download, FileArrowUp, Link as Enlazado, PenLine, Bin, Plus, Wallet } from '@keyline-icons/react';
import { useState } from 'react';
import { CampoArea, CampoSelect, CampoTexto } from '@/components/campo';
import { Enlace } from '@/components/enlace';
import { FiltroLista } from '@/components/filtro-lista';
import { HojaFormulario } from '@/components/hoja-formulario';
import { InsigniaEstado } from '@/components/insignia-estado';
import { MenuAcciones } from '@/components/menu-acciones';
import { Aviso } from '@/components/aviso';
import { EstadoVacio } from '@/components/panel';
import { Resumen, ResumenItem } from '@/components/resumen';
import { Buscador, Paginacion, TablaDatos, useFiltros } from '@/components/tabla-datos';
import { Button } from '@/components/ui/button';
import { useCatalogo } from '@/hooks/use-catalogo';
import { usePermiso } from '@/hooks/use-permiso';
import { inicio } from '@/routes';
import { detalle } from '@/routes/inscripciones';
import { destroy, exportar, importaciones, index, store, update } from '@/routes/pagos';
import type { Opcion, Paginado } from '@/types';

interface FormularioPago {
    fecha: string;
    dni: string;
    referencia: string;
    importe: string;
    numero_operacion: string;
    ubicacion: string;
    observacion: string;
}

interface Pago {
    id: number;
    fecha: string | null;
    dni: string | null;
    referencia: string | null;
    importe: number;
    operacion: string | null;
    ubicacion: string | null;
    manual: boolean;
    estado: string;
    inscripcion: { id: string; codigo: string; sede: string | null } | null;
    observacion: string | null;
    /** Pago a mano que sigue libre: se puede corregir o eliminar. */
    editable: boolean;
    formulario: FormularioPago;
}

interface PropsPagos {
    pagos: Paginado<Pago>;
    filtros: { buscar: string; estado: string };
    resumen: { total: number; libres: number; registrados: number; monto: number };
    estados: Opcion[];
    ubicaciones: string[];
    hoy: string;
}

const soles = (monto: number): string => `S/ ${monto.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

export default function Pagos({ pagos, filtros, resumen, estados, ubicaciones, hoy }: PropsPagos) {
    const puede = usePermiso();
    const [filtro, cambiarFiltro] = useFiltros(filtros);
    const [soloObservacion, setSoloObservacion] = useState(false);
    const { formulario, control } = useCatalogo({
        vacio: { fecha: hoy, dni: '', referencia: '', importe: '', numero_operacion: '', ubicacion: ubicaciones[0] ?? '', observacion: '' } as FormularioPago,
        aFormulario: (pago: Pago) => pago.formulario,
        idDe: (pago) => pago.id,
        nombreDe: (pago) => `${pago.operacion ?? ''} del DNI ${pago.dni ?? ''}`,
        rutas: { store, update, destroy },
        titulos: { nuevo: 'Registrar pago', editar: 'Editar pago', entidad: 'el pago' },
        alAbrir: (pago) => setSoloObservacion(pago !== null && !pago.editable),
    });

    return (
        <div className="space-y-6">
            <Resumen columnas={3}>
                <ResumenItem etiqueta="Pagos" valor={resumen.total} icono={Wallet} tono="info" detalle={soles(resumen.monto)} />
                <ResumenItem etiqueta="Libres" valor={resumen.libres} icono={Clock} tono="aviso" detalle="Aún sin inscripción" />
                <ResumenItem etiqueta="Registrados" valor={resumen.registrados} icono={CircleCheck} tono="exito" detalle="Usados en una inscripción" />
            </Resumen>

            <div className="flex flex-wrap items-center gap-3">
                <Buscador valor={filtro.buscar} alCambiar={(buscar) => cambiarFiltro({ buscar })} placeholder="Buscar por DNI, operación o referencia" />
                <FiltroLista valor={filtro.estado} alCambiar={(estado) => cambiarFiltro({ estado }, true)} opciones={estados} textoTodos="Todos los estados" className="sm:w-48" />
                <div className="flex flex-wrap gap-2 sm:ms-auto">
                    {puede('pago_exportar_excel') && (
                        <Button variant="outline" asChild>
                            <a href={exportar.url({ query: { buscar: filtro.buscar || undefined, estado: filtro.estado || undefined } })}>
                                <Download className="size-4.5" />
                                Excel
                            </a>
                        </Button>
                    )}
                    <Button variant="outline" asChild>
                        <Enlace href={importaciones.url()}>
                            <FileArrowUp className="size-4.5" />
                            Importaciones TXT
                        </Enlace>
                    </Button>
                    {puede('pago_crear') && (
                        <Button onClick={control.abrirNuevo}>
                            <Plus className="size-4.5" />
                            Registrar pago
                        </Button>
                    )}
                </div>
            </div>

            <TablaDatos
                columnas={[
                    { clave: 'fecha', titulo: 'Fecha', className: 'tabular-nums', celda: (pago) => pago.fecha ?? '—' },
                    {
                        clave: 'dni',
                        titulo: 'DNI / referencia',
                        celda: (pago) => (
                            <>
                                <span className="block font-mono font-medium text-zinc-900 dark:text-white">{pago.dni}</span>
                                <span className="block max-w-56 truncate text-xs text-zinc-500 dark:text-zinc-400" title={pago.referencia ?? undefined}>
                                    {pago.referencia ?? '—'}
                                </span>
                            </>
                        ),
                    },
                    { clave: 'importe', titulo: 'Importe', className: 'text-end tabular-nums font-medium', celda: (pago) => soles(pago.importe) },
                    { clave: 'operacion', titulo: 'N° operación', className: 'font-mono', celda: (pago) => pago.operacion ?? '—' },
                    {
                        clave: 'ubicacion',
                        titulo: 'Ubicación',
                        className: 'whitespace-normal',
                        celda: (pago) => (
                            <>
                                <span className="block">{pago.ubicacion ?? '—'}</span>
                                {pago.manual && <span className="block text-xs text-zinc-400 dark:text-zinc-500">Registrado a mano</span>}
                            </>
                        ),
                    },
                    { clave: 'estado', titulo: 'Estado', celda: (pago) => <InsigniaEstado estado={pago.estado} /> },
                    {
                        clave: 'inscripcion',
                        titulo: 'Inscripción',
                        celda: (pago) =>
                            pago.inscripcion ? (
                                <>
                                    <Enlace href={detalle.url(pago.inscripcion.id)} className="inline-flex items-center gap-1 font-medium text-index-600 tabular-nums hover:underline dark:text-index-300">
                                        <Enlazado className="size-3.5" />
                                        {pago.inscripcion.codigo}
                                    </Enlace>
                                    <span className="block text-xs text-zinc-400 dark:text-zinc-500">{pago.inscripcion.sede}</span>
                                </>
                            ) : (
                                <span className="text-zinc-400">—</span>
                            ),
                    },
                    { clave: 'observacion', titulo: 'Observación', className: 'max-w-48 truncate', celda: (pago) => pago.observacion ?? <span className="text-zinc-400">—</span> },
                    {
                        clave: 'acciones',
                        titulo: <span className="sr-only">Acciones</span>,
                        className: 'w-14 text-end',
                        celda: (pago) => (
                            <MenuAcciones
                                acciones={[
                                    { etiqueta: pago.editable ? 'Editar' : 'Editar observación', icono: PenLine, alSeleccionar: () => control.abrirEdicion(pago), visible: puede('pago_editar') },
                                    { etiqueta: 'Eliminar', icono: Bin, alSeleccionar: () => void control.eliminar(pago), peligro: true, visible: puede('pago_eliminar') && pago.editable },
                                ]}
                            />
                        ),
                    },
                ]}
                filas={pagos.data}
                claveFila={(pago) => pago.id}
                vacio={<EstadoVacio icono={Wallet} titulo="No se encontraron pagos." />}
            />
            <Paginacion paginado={pagos} />

            <HojaFormulario abierta={control.hoja.abierta} alCerrar={control.hoja.cerrar} titulo={control.hoja.titulo} alEnviar={control.guardar} procesando={control.procesando}>
                {soloObservacion && (
                    <Aviso tono="info" titulo="Solo se puede editar la observación">
                        El pago vino del banco o ya se usó en una inscripción.
                    </Aviso>
                )}
                <div className="grid gap-4 sm:grid-cols-2">
                    <CampoTexto etiqueta="Fecha" type="date" max={hoy} value={formulario.data.fecha} onChange={(evento) => formulario.setData('fecha', evento.target.value)} error={formulario.errors.fecha} disabled={soloObservacion} requerido />
                    <CampoTexto
                        etiqueta="DNI"
                        inputMode="numeric"
                        maxLength={12}
                        autoComplete="off"
                        value={formulario.data.dni}
                        onChange={(evento) => formulario.setData('dni', evento.target.value.replace(/\D/g, ''))}
                        error={formulario.errors.dni}
                        disabled={soloObservacion}
                        requerido
                    />
                </div>
                <CampoTexto
                    etiqueta="Referencia"
                    value={formulario.data.referencia}
                    onChange={(evento) => formulario.setData('referencia', evento.target.value)}
                    error={formulario.errors.referencia}
                    ayuda="Nombre o dato que figura en el voucher."
                    disabled={soloObservacion}
                />
                <div className="grid gap-4 sm:grid-cols-2">
                    <CampoTexto
                        etiqueta="Importe (S/)"
                        inputMode="decimal"
                        value={formulario.data.importe}
                        onChange={(evento) => formulario.setData('importe', evento.target.value.replace(/[^\d.]/g, ''))}
                        error={formulario.errors.importe}
                        ayuda="Parentesco UNU se registra con 0."
                        disabled={soloObservacion}
                        requerido
                    />
                    <CampoTexto
                        etiqueta="N° de operación"
                        inputMode="numeric"
                        autoComplete="off"
                        value={formulario.data.numero_operacion}
                        onChange={(evento) => formulario.setData('numero_operacion', evento.target.value.replace(/\D/g, ''))}
                        error={formulario.errors.numero_operacion}
                        ayuda="Para parentesco, el N° de constancia."
                        disabled={soloObservacion}
                        requerido
                    />
                </div>
                <CampoSelect etiqueta="Ubicación" value={formulario.data.ubicacion} onChange={(evento) => formulario.setData('ubicacion', evento.target.value)} error={formulario.errors.ubicacion} disabled={soloObservacion} requerido>
                    {ubicaciones.map((ubicacion) => (
                        <option key={ubicacion} value={ubicacion}>
                            {ubicacion}
                        </option>
                    ))}
                </CampoSelect>
                <CampoArea
                    etiqueta="Observación"
                    value={formulario.data.observacion}
                    onChange={(evento) => formulario.setData('observacion', evento.target.value)}
                    error={formulario.errors.observacion}
                    ayuda="Por ejemplo: SOLICITÓ DEVOLUCIÓN."
                    maxLength={255}
                />
            </HojaFormulario>
        </div>
    );
}

Pagos.layout = {
    titulo: 'Pagos',
    subtitulo: 'Pagos del banco y registrados a mano, y la inscripción en que se usaron.',
    icono: 'pagos',
    migas: [{ titulo: 'Inicio', href: inicio.url() }, { titulo: 'Inscripciones' }, { titulo: 'Pagos', href: index.url() }],
};
