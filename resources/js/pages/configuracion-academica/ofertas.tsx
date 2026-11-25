import { useForm } from '@inertiajs/react';
import { Building, Filter, Save } from '@keyline-icons/react/fill';
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
    /** Carreras marcadas por sede y modalidad: carreras[sede][modalidad] = ids. */
    seleccion: { modalidades: number[]; carreras: Record<string, Record<string, number[]>> };
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
        carreras: Object.fromEntries(
            sedes.map((sede) => [String(sede.id), Object.fromEntries(modalidades.map((modalidad) => [String(modalidad.id), seleccion.carreras[String(sede.id)]?.[String(modalidad.id)] ?? []]))]),
        ) as Record<string, Record<string, number[]>>,
    });

    const alternar = (lista: number[], id: number, marcar: boolean): number[] => (marcar ? [...new Set([...lista, id])] : lista.filter((actual) => actual !== id));
    const carrerasDe = (sede: number, modalidad: number): number[] => formulario.data.carreras[String(sede)]?.[String(modalidad)] ?? [];
    const cambiarCarreras = (sede: number, modalidad: number, ids: number[]): void =>
        formulario.setData('carreras', {
            ...formulario.data.carreras,
            [String(sede)]: { ...formulario.data.carreras[String(sede)], [String(modalidad)]: ids },
        });
    const habilitadas = modalidades.filter((modalidad) => formulario.data.modalidades.includes(modalidad.id));
    const todas = carreras.map((carrera) => carrera.id);

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

                        {sedes.length > 0 && habilitadas.length === 0 && <Aviso tono="aviso">Habilite al menos una modalidad para elegir sus carreras en cada sede.</Aviso>}

                        {habilitadas.length > 0 &&
                            sedes.map((sede) => (
                                <Panel
                                    key={sede.id}
                                    titulo={sede.nombre}
                                    descripcion={habilitadas.map((modalidad) => `${modalidad.nombre}: ${carrerasDe(sede.id, modalidad.id).length}`).join(' · ') + ' carreras'}
                                    acciones={
                                        puedeEditar && (
                                            <>
                                                <Button variant="ghost" size="sm" onClick={() => habilitadas.forEach((modalidad) => cambiarCarreras(sede.id, modalidad.id, todas))}>
                                                    Marcar todas
                                                </Button>
                                                <Button variant="ghost" size="sm" onClick={() => habilitadas.forEach((modalidad) => cambiarCarreras(sede.id, modalidad.id, []))}>
                                                    Quitar todas
                                                </Button>
                                            </>
                                        )
                                    }
                                >
                                    <div className="-mx-1 overflow-x-auto">
                                        <table className="w-full min-w-[28rem] border-separate border-spacing-0 text-sm">
                                            <thead>
                                                <tr>
                                                    <th className="border-b border-zinc-200/80 px-3 py-2 text-start text-xs font-medium text-zinc-500 dark:border-white/[0.08] dark:text-zinc-400">Carrera</th>
                                                    {habilitadas.map((modalidad) => {
                                                        const marcadas = carrerasDe(sede.id, modalidad.id);
                                                        const completa = marcadas.length === carreras.length && carreras.length > 0;

                                                        return (
                                                            <th
                                                                key={modalidad.id}
                                                                className="w-40 border-b border-zinc-200/80 px-3 py-2 text-center text-xs font-medium text-zinc-600 dark:border-white/[0.08] dark:text-zinc-300"
                                                            >
                                                                <label className={cn('inline-flex flex-col items-center gap-1.5', puedeEditar && 'cursor-pointer')}>
                                                                    <span className="leading-tight">{modalidad.nombre}</span>
                                                                    <Checkbox
                                                                        aria-label={`Todas las carreras en ${modalidad.nombre} · ${sede.nombre}`}
                                                                        checked={completa ? true : marcadas.length > 0 ? 'indeterminate' : false}
                                                                        disabled={!puedeEditar}
                                                                        onCheckedChange={(valor) => cambiarCarreras(sede.id, modalidad.id, valor === true ? todas : [])}
                                                                    />
                                                                </label>
                                                            </th>
                                                        );
                                                    })}
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {carreras.map((carrera) => (
                                                    <tr key={carrera.id} className="hover:bg-zinc-50/70 dark:hover:bg-white/[0.02]">
                                                        <td className="border-b border-zinc-100 px-3 py-2 dark:border-white/[0.05]">
                                                            <span className="block text-zinc-800 dark:text-zinc-100">{carrera.nombre}</span>
                                                            <span className="block font-mono text-xs text-zinc-500 dark:text-zinc-400">{carrera.codigo}</span>
                                                        </td>
                                                        {habilitadas.map((modalidad) => {
                                                            const marcadas = carrerasDe(sede.id, modalidad.id);

                                                            return (
                                                                <td key={modalidad.id} className="border-b border-zinc-100 px-3 py-2 text-center dark:border-white/[0.05]">
                                                                    <Checkbox
                                                                        aria-label={`${carrera.nombre} en ${modalidad.nombre} · ${sede.nombre}`}
                                                                        checked={marcadas.includes(carrera.id)}
                                                                        disabled={!puedeEditar}
                                                                        onCheckedChange={(valor) => cambiarCarreras(sede.id, modalidad.id, alternar(marcadas, carrera.id, valor === true))}
                                                                    />
                                                                </td>
                                                            );
                                                        })}
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </Panel>
                            ))}
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
    subtitulo: 'Modalidades habilitadas y carreras que se ofrecen en cada sede y modalidad del proceso.',
    icono: 'oferta',
    migas: [{ titulo: 'Inicio', href: inicio.url() }, { titulo: 'Configuración académica' }, { titulo: 'Oferta del proceso', href: index.url() }],
};
