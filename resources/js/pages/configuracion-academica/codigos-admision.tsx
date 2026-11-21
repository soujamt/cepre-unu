import { router, useForm } from '@inertiajs/react';
import { Download, FileSpreadsheet, MapPin, Save, Upload } from '@keyline-icons/react';
import { useRef } from 'react';
import { Aviso } from '@/components/aviso';
import { EstadoVacio } from '@/components/panel';
import { SelectorProceso } from '@/components/selector-proceso';
import type { OpcionProceso } from '@/components/selector-proceso';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { usePermiso } from '@/hooks/use-permiso';
import { cn } from '@/lib/utils';
import { inicio } from '@/routes';
import { cargarPlantilla, index, plantilla, update } from '@/routes/codigos-admision';

interface Fila {
    /** Clave sede-modalidad(-carrera) con la que se guarda el código. */
    clave: string;
    esLugar: boolean;
    sede: string;
    modalidad: string;
    carrera: string | null;
    codigo: string;
}

interface PropsCodigos {
    procesos: OpcionProceso[];
    proceso: { id: number; nombre: string; cerrado: boolean } | null;
    filas: Fila[];
    avance: { total: number; conCodigo: number };
    erroresPlantilla: string[];
}

export default function CodigosAdmision({ procesos, proceso, filas, avance, erroresPlantilla }: PropsCodigos) {
    const puedeEditar = usePermiso()('codigo_admision_editar');
    const archivo = useRef<HTMLInputElement>(null);
    const formulario = useForm({ codigos: Object.fromEntries(filas.map((fila) => [fila.clave, fila.codigo])) as Record<string, string> });
    const porcentaje = avance.total === 0 ? 0 : Math.round((avance.conCodigo / avance.total) * 100);

    const guardar = (): void => {
        if (proceso) {
            formulario.put(update.url(proceso.id), { preserveScroll: true });
        }
    };

    const subirPlantilla = (lista: FileList | null): void => {
        const plantillaLlena = lista?.[0];

        if (proceso && plantillaLlena) {
            router.post(cargarPlantilla.url(proceso.id), { plantilla: plantillaLlena }, { forceFormData: true, preserveScroll: true });
        }

        if (archivo.current) {
            archivo.current.value = '';
        }
    };

    return (
        <div className="space-y-6">
            <div className="flex flex-wrap items-center gap-3">
                <SelectorProceso procesos={procesos} valor={proceso?.id} />
                {proceso && (
                    <div className="flex flex-wrap gap-2 sm:ms-auto">
                        <Button variant="outline" asChild>
                            <a href={plantilla.url(proceso.id)}>
                                <Download className="size-4.5" />
                                Descargar plantilla
                            </a>
                        </Button>
                        {puedeEditar && (
                            <>
                                <Button variant="outline" onClick={() => archivo.current?.click()}>
                                    <Upload className="size-4.5" />
                                    Cargar plantilla
                                </Button>
                                <input ref={archivo} type="file" accept=".xlsx,.xls" className="sr-only" onChange={(evento) => subirPlantilla(evento.target.files)} />
                                <Button onClick={guardar} disabled={formulario.processing || !formulario.isDirty}>
                                    <Save className="size-4.5" />
                                    {formulario.processing ? 'Guardando…' : 'Guardar códigos'}
                                </Button>
                            </>
                        )}
                    </div>
                )}
            </div>

            {erroresPlantilla.length > 0 && (
                <Aviso tono="aviso" titulo="Filas de la plantilla que no se usaron">
                    <ul className="mt-1 list-disc space-y-0.5 ps-4">
                        {erroresPlantilla.map((error) => (
                            <li key={error}>{error}</li>
                        ))}
                    </ul>
                </Aviso>
            )}

            {proceso === null ? (
                <EstadoVacio icono={FileSpreadsheet} titulo="Seleccione un proceso para registrar sus códigos." />
            ) : filas.length === 0 ? (
                <Aviso tono="aviso" titulo="El proceso no tiene oferta">
                    Configure las modalidades y carreras en Oferta del proceso para registrar sus códigos.
                </Aviso>
            ) : (
                <>
                    <div className="flex items-center gap-4 rounded-2xl border border-zinc-200/80 bg-white px-5 py-4 shadow-card dark:border-white/[0.07] dark:bg-white/[0.02] dark:shadow-none">
                        <div className="min-w-0 flex-1 space-y-2">
                            <div className="flex items-center justify-between text-sm">
                                <span className="font-medium text-zinc-800 dark:text-zinc-100">Avance de códigos</span>
                                <span className="text-zinc-500 tabular-nums dark:text-zinc-400">
                                    {avance.conCodigo} / {avance.total}
                                </span>
                            </div>
                            <div className="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-white/[0.06]">
                                <div className="h-full rounded-full bg-linear-to-r from-index-400 to-index-600 transition-[width] duration-500" style={{ width: `${porcentaje}%` }} />
                            </div>
                        </div>
                        <span className="text-2xl font-semibold text-zinc-900 tabular-nums dark:text-white">{porcentaje}%</span>
                    </div>

                    <div className="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-card dark:border-white/[0.07] dark:bg-white/[0.02] dark:shadow-none">
                        <table className="w-full text-sm">
                            <thead className="bg-zinc-50/80 text-xs font-medium text-zinc-500 dark:bg-white/[0.03] dark:text-zinc-400">
                                <tr>
                                    <th className="h-11 px-4 text-start font-medium">Lugar / carrera</th>
                                    <th className="h-11 px-4 text-start font-medium">Modalidad</th>
                                    <th className="h-11 w-44 px-4 text-start font-medium">Código de Admisión</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-zinc-100 dark:divide-white/[0.04]">
                                {filas.map((fila) => (
                                    <tr key={fila.clave} className={cn(fila.esLugar && 'bg-zinc-50/70 dark:bg-white/[0.025]')}>
                                        <td className="px-4 py-2.5">
                                            {fila.esLugar ? (
                                                <span className="flex items-center gap-2 font-medium text-zinc-900 dark:text-white">
                                                    <MapPin className="size-4 text-index-500" />
                                                    Lugar: {fila.sede}
                                                </span>
                                            ) : (
                                                <span className="ps-6 text-zinc-700 dark:text-zinc-300">{fila.carrera}</span>
                                            )}
                                        </td>
                                        <td className="px-4 py-2.5">{fila.esLugar ? <Badge variant="secondary">{fila.modalidad}</Badge> : <span className="text-xs text-zinc-500">{fila.modalidad}</span>}</td>
                                        <td className="px-4 py-2">
                                            <input
                                                aria-label={fila.esLugar ? `Código del lugar ${fila.sede} ${fila.modalidad}` : `Código de ${fila.carrera} en ${fila.modalidad}`}
                                                value={formulario.data.codigos[fila.clave] ?? ''}
                                                onChange={(evento) => formulario.setData('codigos', { ...formulario.data.codigos, [fila.clave]: evento.target.value })}
                                                disabled={!puedeEditar}
                                                maxLength={20}
                                                className="h-9 w-full rounded-lg border border-zinc-200 bg-white px-3 font-mono text-sm tabular-nums focus:border-index-400 focus:ring-3 focus:ring-index-500/15 focus:outline-hidden disabled:bg-zinc-50 dark:border-white/10 dark:bg-white/[0.04]"
                                            />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </>
            )}
        </div>
    );
}

CodigosAdmision.layout = {
    titulo: 'Códigos de Admisión',
    subtitulo: 'Códigos del lugar y de cada carrera que se usan en el reporte para Admisión.',
    icono: 'codigos',
    migas: [{ titulo: 'Inicio', href: inicio.url() }, { titulo: 'Configuración académica' }, { titulo: 'Códigos de Admisión', href: index.url() }],
};
