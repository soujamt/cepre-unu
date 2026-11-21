import { router, useForm } from '@inertiajs/react';
import { Bin, Camera, Download, ImagePlus, Upload, X } from '@keyline-icons/react/fill';
import { useEffect, useMemo, useRef, useState } from 'react';
import { useConfirmacion } from '@/components/dialogo-confirmacion';
import { FiltroLista } from '@/components/filtro-lista';
import { EstadoVacio } from '@/components/panel';
import type { OpcionProceso } from '@/components/selector-proceso';
import { Buscador, Paginacion, useFiltros } from '@/components/tabla-datos';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetDescription, SheetTitle } from '@/components/ui/sheet';
import { usePermiso } from '@/hooks/use-permiso';
import { cn } from '@/lib/utils';
import { inicio } from '@/routes';
import { destroy, exportar, index, store } from '@/routes/fotos';
import type { Paginado } from '@/types';

interface Foto {
    id: number;
    documento: string;
    nombre: string | null;
    url: string;
    actualizado: string | null;
}

interface PropsFotos {
    fotos: Paginado<Foto>;
    filtros: { buscar: string; proceso: string };
    total: number;
    procesos: OpcionProceso[];
}

/** DNI tomado del nombre del archivo: "47940211.jpg" → "47940211". */
const dniDelArchivo = (archivo: File): string => archivo.name.replace(/\.[^.]+$/, '').replace(/\D+/g, '');

function SubirFotos({ abierta, alCerrar }: { abierta: boolean; alCerrar: () => void }) {
    const selector = useRef<HTMLInputElement>(null);
    const formulario = useForm<{ archivos: File[]; documentos: string[] }>({ archivos: [], documentos: [] });
    const vistas = useMemo(() => formulario.data.archivos.map((archivo) => URL.createObjectURL(archivo)), [formulario.data.archivos]);

    useEffect(() => () => vistas.forEach((vista) => URL.revokeObjectURL(vista)), [vistas]);

    const agregar = (lista: FileList | null): void => {
        const nuevos = Array.from(lista ?? []);
        formulario.setData({ archivos: [...formulario.data.archivos, ...nuevos], documentos: [...formulario.data.documentos, ...nuevos.map(dniDelArchivo)] });
        formulario.clearErrors();

        if (selector.current) {
            selector.current.value = '';
        }
    };

    const quitar = (indice: number): void => {
        formulario.setData({
            archivos: formulario.data.archivos.filter((_, posicion) => posicion !== indice),
            documentos: formulario.data.documentos.filter((_, posicion) => posicion !== indice),
        });
    };

    const errores = formulario.errors as Record<string, string | undefined>;

    const guardar = (): void => {
        formulario.post(store.url(), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                formulario.reset();
                alCerrar();
            },
        });
    };

    return (
        <Sheet open={abierta} onOpenChange={(abrir) => !abrir && alCerrar()}>
            <SheetContent side="right" className="flex w-full flex-col gap-0 p-0 sm:inset-y-2.5 sm:right-2.5 sm:h-auto sm:max-w-lg sm:rounded-2xl sm:border">
                <div className="border-b border-zinc-200/70 px-6 py-5 dark:border-white/[0.06]">
                    <SheetTitle className="text-lg font-semibold">Subir fotos</SheetTitle>
                    <SheetDescription className="text-sm text-zinc-500 dark:text-zinc-400">El DNI se toma del nombre del archivo; corríjalo si hace falta. Si ya existe, la foto se reemplaza.</SheetDescription>
                </div>

                <div className="scrollbar-fino flex-1 space-y-4 overflow-y-auto px-6 py-5">
                    <button
                        type="button"
                        onClick={() => selector.current?.click()}
                        className="flex w-full flex-col items-center gap-2 rounded-2xl border border-dashed border-zinc-300 bg-zinc-50/60 px-4 py-8 text-center transition-colors hover:border-index-300 hover:bg-index-50/40 dark:border-white/15 dark:bg-white/[0.02] dark:hover:border-index-500/40"
                    >
                        <span className="flex size-11 items-center justify-center rounded-xl bg-white text-index-500 shadow-card ring-1 ring-zinc-200/80 dark:bg-white/[0.06] dark:ring-white/10">
                            <ImagePlus className="size-5" />
                        </span>
                        <span className="text-sm font-medium text-zinc-800 dark:text-zinc-100">Elegir fotos (JPG o PNG)</span>
                        <span className="text-xs text-zinc-500 dark:text-zinc-400">Hasta 50 a la vez, 2 MB cada una. Se recortan al tamaño del carnet.</span>
                    </button>
                    <input ref={selector} type="file" accept="image/jpeg,image/png" multiple className="sr-only" onChange={(evento) => agregar(evento.target.files)} />
                    {errores.archivos && <p className="text-sm text-red-600 dark:text-red-400">{errores.archivos}</p>}

                    <ul className="space-y-2">
                        {formulario.data.archivos.map((archivo, indice) => {
                            const error = errores[`documentos.${indice}`] ?? errores[`archivos.${indice}`];

                            return (
                                <li key={`${archivo.name}-${indice}`} className={cn('flex items-center gap-3 rounded-xl border p-2.5', error ? 'border-red-300 dark:border-red-500/40' : 'border-zinc-200/80 dark:border-white/[0.08]')}>
                                    <img src={vistas[indice]} alt="" className="size-12 rounded-lg object-cover" />
                                    <div className="min-w-0 flex-1 space-y-1">
                                        <p className="truncate text-xs text-zinc-500 dark:text-zinc-400">{archivo.name}</p>
                                        <input
                                            aria-label={`DNI de ${archivo.name}`}
                                            inputMode="numeric"
                                            maxLength={12}
                                            placeholder="DNI"
                                            value={formulario.data.documentos[indice] ?? ''}
                                            onChange={(evento) => formulario.setData('documentos', formulario.data.documentos.map((dni, posicion) => (posicion === indice ? evento.target.value.replace(/\D/g, '') : dni)))}
                                            className="h-8 w-full rounded-lg border border-zinc-200 bg-white px-2.5 font-mono text-sm focus:border-index-400 focus:ring-3 focus:ring-index-500/15 focus:outline-hidden dark:border-white/10 dark:bg-white/[0.04]"
                                        />
                                        {error && <p className="text-xs text-red-600 dark:text-red-400">{error}</p>}
                                    </div>
                                    <Button variant="ghost" size="icon-sm" onClick={() => quitar(indice)} aria-label={`Quitar ${archivo.name}`}>
                                        <X className="size-4" />
                                    </Button>
                                </li>
                            );
                        })}
                    </ul>
                </div>

                <div className="flex justify-end gap-2.5 rounded-b-2xl border-t border-zinc-200/70 bg-zinc-50/60 px-6 py-4 dark:border-white/[0.06] dark:bg-white/[0.02]">
                    <Button variant="outline" onClick={alCerrar}>
                        Cancelar
                    </Button>
                    <Button onClick={guardar} disabled={formulario.processing || formulario.data.archivos.length === 0}>
                        <Upload className="size-4.5" />
                        {formulario.processing ? 'Subiendo…' : `Guardar ${formulario.data.archivos.length || ''} foto(s)`}
                    </Button>
                </div>
            </SheetContent>
        </Sheet>
    );
}

export default function Fotos({ fotos, filtros, total, procesos }: PropsFotos) {
    const puede = usePermiso();
    const confirmar = useConfirmacion();
    const [filtro, cambiarFiltro] = useFiltros(filtros);
    const [subiendo, setSubiendo] = useState(false);

    const eliminar = async (foto: Foto): Promise<void> => {
        if (await confirmar({ titulo: 'Eliminar foto', descripcion: `Se eliminará la foto del DNI ${foto.documento}.`, textoConfirmar: 'Eliminar', peligro: true })) {
            router.delete(destroy.url(foto.id), { preserveScroll: true });
        }
    };

    return (
        <div className="space-y-6">
            <div className="flex flex-wrap items-center gap-3">
                <Buscador valor={filtro.buscar} alCambiar={(buscar) => cambiarFiltro({ buscar: buscar.replace(/\D/g, '') })} placeholder="Buscar por DNI" />
                <FiltroLista valor={filtro.proceso} alCambiar={(proceso) => cambiarFiltro({ proceso }, true)} opciones={procesos.map((proceso) => ({ valor: proceso.valor, nombre: proceso.nombre }))} textoTodos="Todos los procesos" className="sm:w-48" />
                <Badge variant="secondary" className="h-7 px-2.5">
                    Total fotos: {total}
                </Badge>
                <div className="flex flex-wrap gap-2 sm:ms-auto">
                    {puede('foto_exportar_excel') && total > 0 && (
                        <Button variant="outline" asChild>
                            <a href={exportar.url({ query: { buscar: filtro.buscar || undefined, proceso: filtro.proceso || undefined } })}>
                                <Download className="size-4.5" />
                                Descargar ZIP
                            </a>
                        </Button>
                    )}
                    {puede('foto_crear') && (
                        <Button onClick={() => setSubiendo(true)}>
                            <Upload className="size-4.5" />
                            Subir fotos
                        </Button>
                    )}
                </div>
            </div>

            {fotos.data.length === 0 ? (
                <EstadoVacio icono={Camera} titulo="No hay fotos que coincidan." descripcion="Suba las fotos de los postulantes nombradas con su DNI." />
            ) : (
                <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-6">
                    {fotos.data.map((foto) => (
                        <figure key={foto.id} className="group overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-card dark:border-white/[0.07] dark:bg-white/[0.02] dark:shadow-none">
                            <div className="relative aspect-[3/4] bg-zinc-100 dark:bg-white/[0.04]">
                                <img src={foto.url} alt={`Foto del DNI ${foto.documento}`} loading="lazy" className="size-full object-cover" />
                                {puede('foto_eliminar') && (
                                    <Button
                                        variant="outline"
                                        size="icon-sm"
                                        onClick={() => void eliminar(foto)}
                                        aria-label={`Eliminar foto del DNI ${foto.documento}`}
                                        className="absolute end-2 top-2 opacity-0 backdrop-blur transition-opacity group-hover:opacity-100 focus-visible:opacity-100"
                                    >
                                        <Bin className="size-4 text-red-500" />
                                    </Button>
                                )}
                            </div>
                            <figcaption className="space-y-0.5 px-3 py-2.5">
                                <p className="font-mono text-sm font-medium text-zinc-900 dark:text-white">{foto.documento}</p>
                                <p className="truncate text-xs text-zinc-500 dark:text-zinc-400">{foto.nombre ?? 'Sin inscripción'}</p>
                                <p className="text-[11px] text-zinc-400 dark:text-zinc-500">{foto.actualizado}</p>
                            </figcaption>
                        </figure>
                    ))}
                </div>
            )}

            <Paginacion paginado={fotos} />
            <SubirFotos abierta={subiendo} alCerrar={() => setSubiendo(false)} />
        </div>
    );
}

Fotos.layout = {
    titulo: 'Fotos',
    subtitulo: 'Fotos de los postulantes para el carnet, nombradas con su DNI.',
    icono: 'fotos',
    migas: [{ titulo: 'Inicio', href: inicio.url() }, { titulo: 'Inscripciones' }, { titulo: 'Fotos', href: index.url() }],
};
