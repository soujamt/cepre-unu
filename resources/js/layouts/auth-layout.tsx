import { Head } from '@inertiajs/react';
import { ChartBar, CircleCheck, ClipboardList, School, Users, Wallet } from '@keyline-icons/react';
import type { ReactNode } from 'react';
import { BotonTema } from '@/components/boton-tema';
import { Marca } from '@/components/marca';
import type { DatosLayout } from '@/types';

const indicadores = [
    { icono: Users, etiqueta: 'Inscritos', valor: '1 248', color: 'text-index-500 bg-index-50 dark:bg-index-500/10' },
    { icono: Wallet, etiqueta: 'Pagos validados', valor: '1 102', color: 'text-green-600 bg-green-50 dark:bg-green-500/10' },
    { icono: School, etiqueta: 'Aulas asignadas', valor: '986', color: 'text-purple-600 bg-purple-50 dark:bg-purple-500/10' },
];

const barras = [35, 52, 44, 68, 58, 82, 74, 95, 66, 88];

const filas = [
    { ancho: 'w-20', estado: 'Activo', color: 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400' },
    { ancho: 'w-16', estado: 'Pagado', color: 'bg-index-50 text-index-600 dark:bg-index-500/10 dark:text-index-300' },
    { ancho: 'w-24', estado: 'Revisión', color: 'bg-yellow-50 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400' },
];

const modulos = [
    { icono: ClipboardList, texto: 'Inscripciones' },
    { icono: Wallet, texto: 'Pagos' },
    { icono: School, texto: 'Aulas' },
    { icono: ChartBar, texto: 'Reportes' },
];

/**
 * Ilustración decorativa del panel del sistema, a la derecha del formulario.
 */
function PanelIlustrativo() {
    return (
        <section className="relative hidden p-3 lg:block" aria-hidden="true">
            <div className="relative h-full overflow-hidden rounded-[1.75rem] bg-[linear-gradient(160deg,var(--color-index-400)_0%,var(--color-index-500)_45%,var(--color-index-700)_100%)] dark:bg-[linear-gradient(160deg,var(--color-index-800)_0%,var(--color-index-900)_50%,var(--color-index-950)_100%)]">
                <div className="absolute inset-0 [background-image:linear-gradient(to_right,rgba(255,255,255,0.08)_1px,transparent_1px),linear-gradient(to_bottom,rgba(255,255,255,0.08)_1px,transparent_1px)] [background-size:40px_40px] [mask-image:radial-gradient(ellipse_at_center,black_35%,transparent_80%)]" />
                <div className="absolute -top-24 -right-24 size-96 rounded-full bg-white/20 blur-3xl dark:bg-index-500/20" />
                <div className="absolute -bottom-32 -left-20 size-96 rounded-full bg-index-300/30 blur-3xl dark:bg-index-600/20" />

                <div className="absolute inset-x-10 top-[14%] xl:inset-x-16">
                    <div className="relative rounded-2xl border border-white/60 bg-white/95 p-4 shadow-[0_30px_60px_-20px_rgba(10,25,80,0.55)] dark:border-white/10 dark:bg-zinc-900/95">
                        <div className="flex items-center justify-between border-b border-zinc-100 pb-3 dark:border-white/[0.06]">
                            <div className="flex items-center gap-2">
                                <span className="size-2.5 rounded-full bg-red-400" />
                                <span className="size-2.5 rounded-full bg-yellow-400" />
                                <span className="size-2.5 rounded-full bg-green-400" />
                                <span className="ms-2 text-xs font-medium text-zinc-500 dark:text-zinc-400">Panel CEPRE · Inscripciones</span>
                            </div>
                            <span className="inline-flex items-center gap-1 rounded-full bg-green-50 px-2 py-0.5 text-[10px] font-medium text-green-700 dark:bg-green-500/10 dark:text-green-400">
                                <span className="size-1.5 rounded-full bg-green-500" />
                                Proceso activo
                            </span>
                        </div>

                        <div className="mt-3 grid grid-cols-3 gap-2.5">
                            {indicadores.map(({ icono: Icono, etiqueta, valor, color }) => (
                                <div key={etiqueta} className="rounded-xl border border-zinc-100 p-2.5 dark:border-white/[0.06]">
                                    <div className="flex items-center justify-between">
                                        <span className="text-[10px] text-zinc-500 dark:text-zinc-400">{etiqueta}</span>
                                        <span className={`flex size-5 items-center justify-center rounded-md ${color}`}>
                                            <Icono className="size-3" />
                                        </span>
                                    </div>
                                    <p className="mt-1.5 text-lg font-semibold tracking-tight text-zinc-900 tabular-nums dark:text-white">{valor}</p>
                                </div>
                            ))}
                        </div>

                        <div className="mt-2.5 grid grid-cols-[1.1fr_1fr] gap-2.5">
                            <div className="rounded-xl border border-zinc-100 p-2.5 dark:border-white/[0.06]">
                                <p className="text-[10px] text-zinc-500 dark:text-zinc-400">Inscripciones por día</p>
                                <div className="mt-2 flex h-20 items-end gap-1.5">
                                    {barras.map((altura, indice) => (
                                        <span
                                            key={indice}
                                            className={`flex-1 rounded-t-[3px] ${indice === 7 ? 'bg-index-500' : 'bg-index-100 dark:bg-index-500/25'}`}
                                            style={{ height: `${altura}%` }}
                                        />
                                    ))}
                                </div>
                            </div>

                            <div className="space-y-1.5 rounded-xl border border-zinc-100 p-2.5 dark:border-white/[0.06]">
                                <p className="text-[10px] text-zinc-500 dark:text-zinc-400">Últimas inscripciones</p>
                                {filas.map((fila) => (
                                    <div key={fila.estado} className="flex items-center gap-2">
                                        <span className="size-5 shrink-0 rounded-full bg-zinc-100 dark:bg-white/10" />
                                        <span className={`h-1.5 ${fila.ancho} rounded-full bg-zinc-200 dark:bg-white/15`} />
                                        <span className={`ms-auto rounded-full px-1.5 py-0.5 text-[9px] font-medium ${fila.color}`}>{fila.estado}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>

                    <div className="absolute -bottom-10 -left-6 flex items-center gap-2.5 rounded-xl border border-white/60 bg-white p-2.5 pe-4 shadow-[0_20px_40px_-15px_rgba(10,25,80,0.5)] dark:border-white/10 dark:bg-zinc-900">
                        <span className="flex size-8 items-center justify-center rounded-lg bg-green-50 text-green-600 dark:bg-green-500/10">
                            <CircleCheck className="size-5" />
                        </span>
                        <div className="leading-tight">
                            <p className="text-xs font-semibold text-zinc-900 dark:text-white">Pago validado</p>
                            <p className="text-[10px] text-zinc-500 dark:text-zinc-400">Conciliación bancaria</p>
                        </div>
                    </div>

                    <div className="absolute -top-6 -right-6 rounded-xl border border-white/60 bg-white p-3 shadow-[0_20px_40px_-15px_rgba(10,25,80,0.5)] dark:border-white/10 dark:bg-zinc-900">
                        <div className="flex items-center gap-2.5">
                            <div className="relative size-10 rounded-full bg-[conic-gradient(var(--color-index-500)_0_92%,var(--color-index-100)_92%_100%)] dark:bg-[conic-gradient(var(--color-index-400)_0_92%,rgba(255,255,255,0.1)_92%_100%)]">
                                <span className="absolute inset-1 flex items-center justify-center rounded-full bg-white text-[10px] font-semibold text-zinc-900 dark:bg-zinc-900 dark:text-white">
                                    92%
                                </span>
                            </div>
                            <div className="leading-tight">
                                <p className="text-xs font-semibold text-zinc-900 dark:text-white">Aulas asignadas</p>
                                <p className="text-[10px] text-zinc-500 dark:text-zinc-400">Turnos mañana y tarde</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="absolute inset-x-6 bottom-6 xl:inset-x-8 xl:bottom-8">
                    <div className="rounded-2xl border border-white/20 bg-white/10 p-6 text-white backdrop-blur-xl dark:border-white/10 dark:bg-white/[0.04]">
                        <p className="text-xs font-medium tracking-[0.18em] text-white/75 uppercase">Plataforma institucional</p>
                        <p className="mt-2 max-w-md text-2xl leading-snug font-semibold tracking-tight">Gestión académica del proceso de admisión, en un solo lugar.</p>
                        <div className="mt-5 flex flex-wrap gap-2">
                            {modulos.map(({ icono: Icono, texto }) => (
                                <span key={texto} className="inline-flex items-center gap-1.5 rounded-full border border-white/25 bg-white/10 px-3 py-1 text-xs font-medium">
                                    <Icono className="size-4" />
                                    {texto}
                                </span>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}

/**
 * Layout de acceso: formulario a la izquierda e ilustración del sistema a la derecha.
 */
export default function AuthLayout({ titulo, children }: DatosLayout & { children: ReactNode }) {
    return (
        <div className="grid min-h-dvh w-full bg-white text-zinc-800 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)] dark:bg-zinc-950 dark:text-zinc-100">
            {titulo && <Head title={titulo} />}

            <section className="flex min-h-dvh flex-col px-6 py-8 sm:px-12 lg:px-16 xl:px-20">
                <div className="flex items-center justify-between">
                    <Marca tamano="lg" />
                    <BotonTema />
                </div>

                <div className="flex flex-1 items-center py-10">
                    <div className="mx-auto w-full max-w-sm">{children}</div>
                </div>

                <div className="flex flex-wrap items-center justify-between gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                    <p>¿Problemas para ingresar? Comunícate con la oficina del CEPRE.</p>
                    <p>© {new Date().getFullYear()} Universidad Nacional de Ucayali</p>
                </div>
            </section>

            <PanelIlustrativo />
        </div>
    );
}
