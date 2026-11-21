import { useForm } from '@inertiajs/react';
import { Building, Filter, Save } from '@keyline-icons/react';
import type { ReactNode } from 'react';
import { Aviso } from '@/components/aviso';
import { Enlace } from '@/components/enlace';
import { EstadoVacio, Panel } from '@/components/panel';
import { SelectorProceso } from '@/components/selector-proceso';
import type { OpcionProceso } from '@/components/selector-proceso';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { usePermiso } from '@/hooks/use-permiso';
import { cn } from '@/lib/utils';
import { inicio } from '@/routes';
import { index as aulasProceso } from '@/routes/aulas';
import { index, update } from '@/routes/ofertas';

interface Proceso {
    id: number;
    nombre: string;
    estado: string;
    cerrado: boolean;
    apertura: string | null;
    cierre: string | null;
    anioEgresoOrdinario: number;
}

interface PropsOfertas {
    procesos: OpcionProceso[];
    proceso: Proceso | null;
    modalidades: { id: number; nombre: string; condicion: string | null; condicionValor: string | null }[];
    sedes: { id: number; nombre: string }[];
    carreras: { id: number; codigo: string; nombre: string }[];
    seleccion: { modalidades: number[]; carreras: Record<string, number[]> };
    turnos: { nombre: string; aulas: number; capacidad: number }[];
}

function Opcion({ marcado, deshabilitado, alCambiar, children }: { marcado: boolean; deshabilitado: boolean; alCambiar: (marcado: boolean) => void; children: ReactNode }) {
    return (
        <label
            className={cn(
                'flex min-w-0 cursor-pointer items-start gap-3 rounded-xl border p-3 transition-colors',
                marcado ? 'border-index-300 bg-index-50/50 dark:border-index-500/40 dark:bg-index-500/[0.07]' : 'border-zinc-200/80 hover:bg-zinc-50 dark:border-white/[0.08] dark:hover:bg-white/[0.02]',
                deshabilitado && 'cursor-not-allowed opacity-70',
            )}
        >
            <Checkbox checked={marcado} disabled={deshabilitado} onCheckedChange={(valor) => alCambiar(valor === true)} className="mt-0.5" />
            <span className="min-w-0 flex-1">{children}</span>
        </label>
    );
}

export default function Ofertas({ procesos, proceso, modalidades, sedes, carreras, seleccion, turnos }: PropsOfertas) {
    const puede = usePermiso();
    const puedeEditar = puede('oferta_editar') && proceso !== null && !proceso.cerrado;
    const formulario = useForm({
        modalidades: seleccion.modalidades,
        carreras: Object.fromEntries(sedes.map((sede) => [String(sede.id), seleccion.carreras[String(sede.id)] ?? []])) as Record<string, number[]>,
    });

    const alternar = (lista: number[], id: number, marcar: boolean): number[] => (marcar ? [...new Set([...lista, id])] : lista.filter((actual) => actual !== id));
    const carrerasDe = (sede: number): number[] => formulario.data.carreras[String(sede)] ?? [];
    const cambiarCarreras = (sede: number, ids: number[]): void => formulario.setData('carreras', { ...formulario.data.carreras, [String(sede)]: ids });

    const guardar = (): void => {
        if (proceso) {
            formulario.put(update.url(proceso.id), { preserveScroll: true });
        }
    };

    return (
        <div className="space-y-6">
            <div className="flex flex-wrap items-center gap-3">
                <SelectorProceso procesos={procesos} valor={proceso?.id} />
                {proceso?.cerrado && <Badge variant="secondary">Proceso cerrado: solo lectura</Badge>}
            </div>

            {proceso === null ? (
                <EstadoVacio icono={Filter} titulo="Seleccione un proceso para configurar su oferta." />
            ) : (
                <div className="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_20rem]">
                    <div className="space-y-5">
                        <Panel
                            titulo="Modalidades"
                            descripcion={`Se asigna sola según el año de egreso: hasta ${proceso.anioEgresoOrdinario} es egresado; desde ${proceso.anioEgresoOrdinario + 1}, estudiante en colegio.`}
                        >
                            {modalidades.length === 0 ? (
                                <Aviso tono="aviso">No hay modalidades habilitadas con «Aplica a» definido. Regístrelas en Mantenedores › Modalidades.</Aviso>
                            ) : (
                                <div className="grid gap-3 sm:grid-cols-2">
                                    {modalidades.map((modalidad) => (
                                        <Opcion
                                            key={modalidad.id}
                                            marcado={formulario.data.modalidades.includes(modalidad.id)}
                                            deshabilitado={!puedeEditar}
                                            alCambiar={(marcado) => formulario.setData('modalidades', alternar(formulario.data.modalidades, modalidad.id, marcado))}
                                        >
                                            <span className="block text-sm font-medium text-zinc-800 dark:text-zinc-100">{modalidad.nombre}</span>
                                            {modalidad.condicion && (
                                                <Badge variant={modalidad.condicionValor === 'EGRESADO' ? 'info' : 'aviso'} className="mt-1">
                                                    {modalidad.condicion}
                                                </Badge>
                                            )}
                                        </Opcion>
                                    ))}
                                </div>
                            )}
                            {formulario.errors.modalidades && <p className="mt-3 text-sm text-red-600 dark:text-red-400">{formulario.errors.modalidades}</p>}
                        </Panel>

                        {sedes.length === 0 && <Aviso tono="aviso">No hay sedes habilitadas. Regístrelas en Mantenedores › Sedes.</Aviso>}

                        {sedes.map((sede) => {
                            const marcadas = carrerasDe(sede.id);

                            return (
                                <Panel
                                    key={sede.id}
                                    titulo={sede.nombre}
                                    descripcion={`${marcadas.length} de ${carreras.length} carreras ofrecidas en esta sede`}
                                    acciones={
                                        puedeEditar && (
                                            <>
                                                <Button variant="ghost" size="sm" onClick={() => cambiarCarreras(sede.id, carreras.map((carrera) => carrera.id))}>
                                                    Marcar todas
                                                </Button>
                                                <Button variant="ghost" size="sm" onClick={() => cambiarCarreras(sede.id, [])}>
                                                    Quitar todas
                                                </Button>
                                            </>
                                        )
                                    }
                                >
                                    <div className="grid gap-2.5 sm:grid-cols-2 2xl:grid-cols-3">
                                        {carreras.map((carrera) => (
                                            <Opcion
                                                key={carrera.id}
                                                marcado={marcadas.includes(carrera.id)}
                                                deshabilitado={!puedeEditar}
                                                alCambiar={(marcado) => cambiarCarreras(sede.id, alternar(marcadas, carrera.id, marcado))}
                                            >
                                                <span className="block text-sm text-zinc-800 dark:text-zinc-100">{carrera.nombre}</span>
                                                <span className="block font-mono text-xs text-zinc-500 dark:text-zinc-400">{carrera.codigo}</span>
                                            </Opcion>
                                        ))}
                                    </div>
                                </Panel>
                            );
                        })}
                    </div>

                    <aside className="space-y-4 xl:sticky xl:top-0">
                        <Panel titulo={`Proceso ${proceso.nombre}`} descripcion={`${proceso.apertura ?? '—'} al ${proceso.cierre ?? '—'}`}>
                            <div className="space-y-3">
                                <p className="text-sm font-medium text-zinc-700 dark:text-zinc-200">Turnos con aulas</p>
                                {turnos.length === 0 ? (
                                    <Aviso tono="aviso">El proceso no tiene aulas asignadas; no se podrá inscribir en ningún turno.</Aviso>
                                ) : (
                                    <ul className="divide-y divide-zinc-100 dark:divide-white/[0.05]">
                                        {turnos.map((turno) => (
                                            <li key={turno.nombre} className="flex items-center justify-between gap-3 py-2 text-sm">
                                                <span className="text-zinc-800 dark:text-zinc-100">{turno.nombre}</span>
                                                <span className="text-xs text-zinc-500 tabular-nums dark:text-zinc-400">
                                                    {turno.aulas} aula(s) · {turno.capacidad} cupos
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                                {puede('aula_proceso_indice') && (
                                    <Button variant="outline" size="sm" asChild className="w-full">
                                        <Enlace href={aulasProceso.url({ query: { proceso: String(proceso.id) } })}>
                                            <Building className="size-4" />
                                            Ver aulas por proceso
                                        </Enlace>
                                    </Button>
                                )}
                            </div>
                        </Panel>

                        {puedeEditar && (
                            <Button className="w-full" size="lg" onClick={guardar} disabled={formulario.processing}>
                                <Save className="size-4.5" />
                                {formulario.processing ? 'Guardando…' : 'Guardar oferta'}
                            </Button>
                        )}
                    </aside>
                </div>
            )}
        </div>
    );
}

Ofertas.layout = {
    titulo: 'Oferta del proceso',
    subtitulo: 'Modalidades habilitadas y carreras que se ofrecen en cada sede del proceso.',
    icono: 'oferta',
    migas: [{ titulo: 'Inicio', href: inicio.url() }, { titulo: 'Configuración académica' }, { titulo: 'Oferta del proceso', href: index.url() }],
};
