import { router } from '@inertiajs/react';
import { DoubleCheck, Eye, FileArrowUp, Receipt, Wallet } from '@keyline-icons/react';
import { useRef } from 'react';
import { Aviso } from '@/components/aviso';
import { Enlace } from '@/components/enlace';
import { FiltroEstado } from '@/components/filtro-estado';
import { InsigniaEstado } from '@/components/insignia-estado';
import { MenuAcciones } from '@/components/menu-acciones';
import { EstadoVacio, Panel } from '@/components/panel';
import { Buscador, CeldaRegistro, Paginacion, TablaDatos, useFiltros } from '@/components/tabla-datos';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useConfirmacion } from '@/components/dialogo-confirmacion';
import { usePermiso } from '@/hooks/use-permiso';
import { inicio } from '@/routes';
import { index as pagos, importaciones } from '@/routes/pagos';
import { cancelar, confirmar, previsualizar, validarPendientes } from '@/routes/pagos/importaciones';
import type { FiltrosCatalogo, Paginado, Registro } from '@/types';

interface Importacion {
    id: number;
    archivo: string;
    fechaLote: string | null;
    registros: number;
    /** Pagos aún sin asociar a una inscripción. */
    pendientes: number;
    monto: number;
    moneda: string | null;
    estado: string;
    importado: Registro | null;
}

interface PagoLote {
    id: number;
    documento: string;
    depositante: string | null;
    operacion: string | null;
    oficina: string;
    fecha: string | null;
    monto: number;
    estado: string;
}

interface DetalleTxt {
    numero_documento?: string | null;
    numero_documento_normalizado?: string | null;
    nombre_depositante?: string | null;
    numero_operacion_visible?: string | null;
    fecha_pago?: string | null;
    monto?: number | string | null;
}

interface Previsualizacion {
    nombre_archivo: string;
    moneda: string | null;
    fecha_lote: string | null;
    total_registros: number;
    total_registros_reportado: number;
    total_monto: number | string;
    total_monto_reportado: number | string;
    observaciones: string[];
    duplicados: { tipo: string; documento: string | null; operacion: string | null; fecha_pago: string | null; monto: number }[];
    muestra: DetalleTxt[];
    valido: boolean;
}

interface PropsImportaciones {
    importaciones: Paginado<Importacion>;
    filtros: FiltrosCatalogo;
    detalle: { id: number; archivo: string; fechaLote: string | null; pagos: PagoLote[] } | null;
    previsualizacion: Previsualizacion | null;
}

const soles = (monto: number | string): string => `S/ ${Number(monto).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

function Dato({ etiqueta, valor, alerta = false }: { etiqueta: string; valor: string; alerta?: boolean }) {
    return (
        <div className="rounded-xl border border-zinc-200/80 px-3.5 py-2.5 dark:border-white/[0.08]">
            <p className="text-xs text-zinc-500 dark:text-zinc-400">{etiqueta}</p>
            <p className={alerta ? 'text-sm font-medium text-red-600 tabular-nums dark:text-red-400' : 'text-sm font-medium text-zinc-900 tabular-nums dark:text-white'}>{valor}</p>
        </div>
    );
}

export default function Importaciones({ importaciones: lista, filtros, detalle, previsualizacion }: PropsImportaciones) {
    const puede = usePermiso();
    const confirmarAccion = useConfirmacion();
    const archivo = useRef<HTMLInputElement>(null);
    const [filtro, cambiarFiltro] = useFiltros<FiltrosCatalogo>(filtros);

    const subir = (lista: FileList | null): void => {
        const txt = lista?.[0];

        if (txt) {
            router.post(previsualizar.url(), { archivo: txt }, { forceFormData: true, preserveScroll: true });
        }

        if (archivo.current) {
            archivo.current.value = '';
        }
    };

    const validar = async (importacion: Importacion): Promise<void> => {
        const aceptado = await confirmarAccion({
            titulo: 'Asociar pagos pendientes',
            descripcion: `Se buscarán inscripciones con el mismo DNI y saldo para los ${importacion.pendientes} pago(s) libres de "${importacion.archivo}".`,
            textoConfirmar: 'Asociar',
        });

        if (aceptado) {
            router.post(validarPendientes.url(importacion.id), {}, { preserveScroll: true });
        }
    };

    return (
        <div className="space-y-6">
            <div className="flex flex-wrap items-center gap-3">
                <Buscador valor={String(filtro.buscar)} alCambiar={(buscar) => cambiarFiltro({ buscar })} placeholder="Buscar por archivo" />
                <FiltroEstado seleccionados={filtro.estado as string[]} alCambiar={(estado) => cambiarFiltro({ estado }, true)} valores={['PENDIENTE', 'PROCESADO', 'OBSERVADO']} />
                <div className="flex flex-wrap gap-2 sm:ms-auto">
                    <Button variant="outline" asChild>
                        <Enlace href={pagos.url()}>
                            <Wallet className="size-4.5" />
                            Ver pagos
                        </Enlace>
                    </Button>
                    {puede('pago_crear') && (
                        <>
                            <Button onClick={() => archivo.current?.click()}>
                                <FileArrowUp className="size-4.5" />
                                Importar TXT
                            </Button>
                            <input ref={archivo} type="file" accept=".txt,.TXT" className="sr-only" onChange={(evento) => subir(evento.target.files)} />
                        </>
                    )}
                </div>
            </div>

            <TablaDatos
                columnas={[
                    {
                        clave: 'archivo',
                        titulo: 'Archivo',
                        celda: (importacion) => (
                            <>
                                <span className="block font-medium text-zinc-900 dark:text-white">{importacion.archivo}</span>
                                <span className="block text-xs text-zinc-500 dark:text-zinc-400">Lote del {importacion.fechaLote ?? '—'}</span>
                            </>
                        ),
                    },
                    {
                        clave: 'registros',
                        titulo: 'Pagos',
                        className: 'tabular-nums',
                        celda: (importacion) => (
                            <>
                                <span className="block">{importacion.registros}</span>
                                {importacion.pendientes > 0 && <span className="block text-xs text-amber-600 dark:text-amber-400">{importacion.pendientes} libres</span>}
                            </>
                        ),
                    },
                    { clave: 'monto', titulo: 'Monto', className: 'tabular-nums font-medium', celda: (importacion) => soles(importacion.monto) },
                    { clave: 'estado', titulo: 'Estado', celda: (importacion) => <InsigniaEstado estado={importacion.estado} /> },
                    { clave: 'importado', titulo: 'Importado', celda: (importacion) => <CeldaRegistro registro={importacion.importado} /> },
                    {
                        clave: 'acciones',
                        titulo: <span className="sr-only">Acciones</span>,
                        className: 'w-14 text-end',
                        celda: (importacion) => (
                            <MenuAcciones
                                acciones={[
                                    {
                                        etiqueta: 'Ver pagos del lote',
                                        icono: Eye,
                                        alSeleccionar: () => router.get(importaciones.url(), { ...filtros, importacion: String(importacion.id) }, { preserveScroll: true }),
                                    },
                                    { etiqueta: 'Asociar pendientes', icono: DoubleCheck, alSeleccionar: () => void validar(importacion), visible: puede('pago_crear') && importacion.pendientes > 0 },
                                ]}
                            />
                        ),
                    },
                ]}
                filas={lista.data}
                claveFila={(importacion) => importacion.id}
                vacio={<EstadoVacio icono={Receipt} titulo="Aún no se importaron archivos del banco." />}
            />
            <Paginacion paginado={lista} />

            {detalle && (
                <Panel titulo={`Pagos de ${detalle.archivo}`} descripcion={`Lote del ${detalle.fechaLote ?? '—'} · ${detalle.pagos.length} pago(s)`}>
                    <TablaDatos
                        className="shadow-none"
                        columnas={[
                            {
                                clave: 'documento',
                                titulo: 'DNI / depositante',
                                celda: (pago) => (
                                    <>
                                        <span className="block font-mono font-medium text-zinc-900 dark:text-white">{pago.documento}</span>
                                        <span className="block text-xs text-zinc-500 dark:text-zinc-400">{pago.depositante ?? '—'}</span>
                                    </>
                                ),
                            },
                            {
                                clave: 'operacion',
                                titulo: 'Operación',
                                celda: (pago) => (
                                    <>
                                        <span className="block font-mono">{pago.operacion ?? 'SIN OPERACIÓN'}</span>
                                        <span className="block text-xs text-zinc-500 dark:text-zinc-400">{pago.oficina}</span>
                                    </>
                                ),
                            },
                            { clave: 'fecha', titulo: 'Fecha', className: 'tabular-nums', celda: (pago) => pago.fecha ?? '—' },
                            { clave: 'monto', titulo: 'Monto', className: 'tabular-nums font-medium', celda: (pago) => soles(pago.monto) },
                            { clave: 'estado', titulo: 'Estado', celda: (pago) => <InsigniaEstado estado={pago.estado} /> },
                        ]}
                        filas={detalle.pagos}
                        claveFila={(pago) => pago.id}
                    />
                </Panel>
            )}

            <Dialog open={previsualizacion !== null} onOpenChange={(abierto) => !abierto && router.delete(cancelar.url(), { preserveScroll: true })}>
                {previsualizacion && (
                    <DialogContent className="max-h-[90dvh] overflow-y-auto sm:max-w-3xl">
                        <DialogHeader>
                            <DialogTitle>Revisión del TXT bancario</DialogTitle>
                            <DialogDescription>{previsualizacion.nombre_archivo}</DialogDescription>
                        </DialogHeader>

                        {previsualizacion.valido ? (
                            <Aviso tono="exito" titulo="El archivo cuadra con los totales reportados por el banco">
                                Puede confirmar la importación.
                            </Aviso>
                        ) : (
                            <Aviso tono="peligro" titulo="El archivo tiene observaciones y no se importará">
                                <ul className="mt-1 list-disc space-y-0.5 ps-4">
                                    {previsualizacion.observaciones.map((observacion) => (
                                        <li key={observacion}>{observacion}</li>
                                    ))}
                                </ul>
                            </Aviso>
                        )}

                        <div className="grid grid-cols-2 gap-2.5 sm:grid-cols-4">
                            <Dato etiqueta="Moneda" valor={previsualizacion.moneda ?? '—'} />
                            <Dato etiqueta="Fecha del lote" valor={previsualizacion.fecha_lote ?? '—'} />
                            <Dato
                                etiqueta="Operaciones (leídas / banco)"
                                valor={`${previsualizacion.total_registros} / ${previsualizacion.total_registros_reportado}`}
                                alerta={previsualizacion.total_registros !== previsualizacion.total_registros_reportado}
                            />
                            <Dato
                                etiqueta="Monto (leído / banco)"
                                valor={`${soles(previsualizacion.total_monto)} / ${soles(previsualizacion.total_monto_reportado)}`}
                                alerta={Number(previsualizacion.total_monto) !== Number(previsualizacion.total_monto_reportado)}
                            />
                        </div>

                        {previsualizacion.duplicados.length > 0 && (
                            <div className="space-y-2">
                                <h3 className="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Pagos duplicados</h3>
                                <TablaDatos
                                    className="shadow-none"
                                    columnas={[
                                        { clave: 'tipo', titulo: 'Tipo', celda: (fila) => <InsigniaEstado estado={fila.tipo === 'Ya importado' ? 'OBSERVADO' : 'PENDIENTE'} className="normal-case" /> },
                                        { clave: 'detalleTipo', titulo: 'Motivo', celda: (fila) => fila.tipo },
                                        { clave: 'documento', titulo: 'DNI', className: 'font-mono', celda: (fila) => fila.documento ?? '—' },
                                        { clave: 'operacion', titulo: 'Operación', className: 'font-mono', celda: (fila) => fila.operacion ?? '—' },
                                        { clave: 'fecha', titulo: 'Fecha', celda: (fila) => fila.fecha_pago ?? '—' },
                                        { clave: 'monto', titulo: 'Monto', className: 'tabular-nums', celda: (fila) => soles(fila.monto) },
                                    ]}
                                    filas={previsualizacion.duplicados}
                                    claveFila={(fila) => `${fila.tipo}-${fila.documento}-${fila.operacion}`}
                                />
                            </div>
                        )}

                        <div className="space-y-2">
                            <h3 className="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Primeras operaciones</h3>
                            <TablaDatos
                                className="shadow-none"
                                columnas={[
                                    { clave: 'documento', titulo: 'DNI', className: 'font-mono', celda: (fila) => fila.numero_documento_normalizado ?? fila.numero_documento ?? '—' },
                                    { clave: 'depositante', titulo: 'Depositante', className: 'whitespace-normal', celda: (fila) => fila.nombre_depositante ?? '—' },
                                    { clave: 'operacion', titulo: 'Operación', className: 'font-mono', celda: (fila) => fila.numero_operacion_visible ?? '—' },
                                    { clave: 'fecha', titulo: 'Fecha', celda: (fila) => fila.fecha_pago ?? '—' },
                                    { clave: 'monto', titulo: 'Monto', className: 'tabular-nums', celda: (fila) => soles(fila.monto ?? 0) },
                                ]}
                                filas={previsualizacion.muestra}
                                claveFila={(fila) => `${fila.numero_documento}-${fila.numero_operacion_visible}-${fila.fecha_pago}`}
                            />
                        </div>

                        <DialogFooter>
                            <Button variant="outline" onClick={() => router.delete(cancelar.url(), { preserveScroll: true })}>
                                {previsualizacion.valido ? 'Cancelar' : 'Entendido'}
                            </Button>
                            {previsualizacion.valido && (
                                <Button onClick={() => router.post(confirmar.url(), {}, { preserveScroll: true })}>
                                    <DoubleCheck className="size-4.5" />
                                    Confirmar importación
                                </Button>
                            )}
                        </DialogFooter>
                    </DialogContent>
                )}
            </Dialog>
        </div>
    );
}

Importaciones.layout = {
    titulo: 'Importaciones del banco',
    subtitulo: 'Archivos TXT de pagos del banco: se revisan antes de importarlos y luego se asocian a las inscripciones.',
    icono: 'pagos',
    migas: [{ titulo: 'Inicio', href: inicio.url() }, { titulo: 'Inscripciones' }, { titulo: 'Pagos', href: pagos.url() }, { titulo: 'Importaciones' }],
};
