import { router, useForm } from '@inertiajs/react';
import { ArrowLeft, Building, Save } from '@keyline-icons/react';
import { useMemo, useState } from 'react';
import { Aviso } from '@/components/aviso';
import { Enlace } from '@/components/enlace';
import { FiltroLista } from '@/components/filtro-lista';
import { EstadoVacio } from '@/components/panel';
import { SelectorProceso } from '@/components/selector-proceso';
import type { OpcionProceso } from '@/components/selector-proceso';
import { Buscador } from '@/components/tabla-datos';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { cn } from '@/lib/utils';
import { inicio } from '@/routes';
import { asignar, index, store } from '@/routes/aulas';
import type { Opcion } from '@/types';

interface AulaDisponible {
    id: number;
    nombre: string;
    piso: string;
    lugar: string;
    /** Ya está asignada al proceso y turno elegidos. */
    asignada: boolean;
}

interface PropsAsignar {
    procesos: OpcionProceso[];
    turnos: Opcion[];
    proceso: number | null;
    turno: number | null;
    aulas: AulaDisponible[];
}

const CAPACIDAD_INICIAL = '40';

export default function AulasProcesoAsignar({ procesos, turnos, proceso, turno, aulas }: PropsAsignar) {
    const [buscar, setBuscar] = useState('');
    const formulario = useForm({ proceso, turno, aulas: [] as { id: number; capacidad: string }[] });
    const listo = proceso !== null && turno !== null;

    const visibles = useMemo(() => {
        const texto = buscar.trim().toUpperCase();

        return aulas.filter((aula) => texto === '' || `${aula.nombre} ${aula.piso} ${aula.lugar}`.toUpperCase().includes(texto));
    }, [aulas, buscar]);

    const grupos = useMemo(
        () =>
            Object.entries(
                visibles.reduce<Record<string, AulaDisponible[]>>((grupo, aula) => {
                    const clave = `${aula.lugar} · ${aula.piso}`;
                    (grupo[clave] ??= []).push(aula);

                    return grupo;
                }, {}),
            ),
        [visibles],
    );

    const seleccion = new Map(formulario.data.aulas.map((aula) => [aula.id, aula.capacidad]));
    const cupos = formulario.data.aulas.reduce((suma, aula) => suma + (Number(aula.capacidad) || 0), 0);

    const marcar = (ids: number[], marcar: boolean): void => {
        const actual = new Map(seleccion);
        ids.forEach((id) => (marcar ? actual.set(id, actual.get(id) ?? CAPACIDAD_INICIAL) : actual.delete(id)));
        formulario.setData('aulas', [...actual].map(([id, capacidad]) => ({ id, capacidad })));
    };

    const cambiarCapacidad = (id: number, capacidad: string): void => {
        formulario.setData(
            'aulas',
            formulario.data.aulas.map((aula) => (aula.id === id ? { ...aula, capacidad: capacidad.replace(/\D/g, '') } : aula)),
        );
    };

    const cambiarTurno = (nuevo: string): void => {
        router.get(asignar.url(), { ...(proceso ? { proceso: String(proceso) } : {}), ...(nuevo ? { turno: nuevo } : {}) }, { preserveScroll: true });
    };

    const guardar = (): void => {
        formulario.transform((datos) => ({ ...datos, proceso, turno }));
        formulario.post(store.url(), { preserveScroll: true, onSuccess: () => formulario.setData('aulas', []) });
    };

    const errorAulas = formulario.errors.aulas ?? Object.entries(formulario.errors).find(([clave]) => clave.startsWith('aulas.'))?.[1];

    return (
        <div className="space-y-6">
            <div className="flex flex-wrap items-center gap-3">
                <SelectorProceso procesos={procesos} valor={proceso} consultaExtra={turno ? { turno: String(turno) } : {}} />
                <FiltroLista valor={turno ? String(turno) : ''} alCambiar={cambiarTurno} opciones={turnos} textoTodos="Seleccione un turno" className="sm:w-72" />
                <Buscador valor={buscar} alCambiar={setBuscar} placeholder="Buscar aula, piso o lugar" />
                <Button variant="outline" asChild className="sm:ms-auto">
                    <Enlace href={index.url()}>
                        <ArrowLeft className="size-4.5" />
                        Volver
                    </Enlace>
                </Button>
            </div>

            {!listo ? (
                <Aviso tono="info" titulo="Elija el proceso y el turno">
                    Luego marque las aulas que se usarán en ese turno e indique su capacidad.
                </Aviso>
            ) : grupos.length === 0 ? (
                <EstadoVacio icono={Building} titulo="No hay aulas habilitadas que coincidan con la búsqueda." />
            ) : (
                <>
                    <div className="flex flex-wrap items-center gap-2">
                        <Button variant="ghost" size="sm" onClick={() => marcar(visibles.filter((aula) => !aula.asignada).map((aula) => aula.id), true)}>
                            Seleccionar todas
                        </Button>
                        <Button variant="ghost" size="sm" onClick={() => formulario.setData('aulas', [])}>
                            Limpiar selección
                        </Button>
                    </div>

                    <div className="space-y-6">
                        {grupos.map(([grupo, aulasDelGrupo]) => (
                            <section key={grupo} className="space-y-3">
                                <h2 className="text-sm font-semibold text-zinc-700 dark:text-zinc-200">{grupo}</h2>
                                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
                                    {aulasDelGrupo.map((aula) => {
                                        const marcada = seleccion.has(aula.id);

                                        return (
                                            <div
                                                key={aula.id}
                                                className={cn(
                                                    'flex items-center gap-3 rounded-xl border bg-white p-3 transition-colors dark:bg-white/[0.02]',
                                                    marcada ? 'border-index-300 bg-index-50/40 dark:border-index-500/40 dark:bg-index-500/[0.07]' : 'border-zinc-200/80 dark:border-white/[0.08]',
                                                    aula.asignada && 'opacity-60',
                                                )}
                                            >
                                                <Checkbox
                                                    id={`aula-${aula.id}`}
                                                    checked={aula.asignada || marcada}
                                                    disabled={aula.asignada}
                                                    onCheckedChange={(valor) => marcar([aula.id], valor === true)}
                                                />
                                                <label htmlFor={`aula-${aula.id}`} className="min-w-0 flex-1 cursor-pointer">
                                                    <span className="block truncate text-sm font-medium text-zinc-800 dark:text-zinc-100">{aula.nombre}</span>
                                                    {aula.asignada && <Badge variant="secondary">Ya asignada</Badge>}
                                                </label>
                                                {marcada && (
                                                    <input
                                                        aria-label={`Capacidad de ${aula.nombre}`}
                                                        inputMode="numeric"
                                                        value={seleccion.get(aula.id)}
                                                        onChange={(evento) => cambiarCapacidad(aula.id, evento.target.value)}
                                                        className="h-9 w-16 rounded-lg border border-zinc-200 bg-white text-center text-sm tabular-nums focus:border-index-400 focus:ring-3 focus:ring-index-500/15 focus:outline-hidden dark:border-white/10 dark:bg-white/[0.04]"
                                                    />
                                                )}
                                            </div>
                                        );
                                    })}
                                </div>
                            </section>
                        ))}
                    </div>

                    <div className="sticky bottom-0 -mx-1 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-zinc-200/80 bg-white/90 px-4 py-3 shadow-[0_-8px_24px_-16px_rgba(16,24,40,0.2)] backdrop-blur dark:border-white/[0.08] dark:bg-zinc-900/90">
                        <div className="text-sm">
                            <p className="font-medium text-zinc-800 dark:text-zinc-100">
                                {formulario.data.aulas.length} aula(s) seleccionada(s) · {cupos} cupos
                            </p>
                            {errorAulas && <p className="text-red-600 dark:text-red-400">{errorAulas}</p>}
                        </div>
                        <Button onClick={guardar} disabled={formulario.processing || formulario.data.aulas.length === 0}>
                            <Save className="size-4.5" />
                            {formulario.processing ? 'Asignando…' : 'Asignar aulas'}
                        </Button>
                    </div>
                </>
            )}
        </div>
    );
}

AulasProcesoAsignar.layout = {
    titulo: 'Asignar aulas',
    subtitulo: 'Marque las aulas que se usarán en el turno del proceso e indique su capacidad.',
    icono: 'aulasProceso',
    migas: [{ titulo: 'Inicio', href: inicio.url() }, { titulo: 'Configuración académica' }, { titulo: 'Aulas por proceso', href: index.url() }, { titulo: 'Asignar' }],
};
