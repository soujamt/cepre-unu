import { Check, CircleCheck, FileText, GraduationCap, IdCard, Receipt, RotateCcw, School, ShieldCheck } from '@keyline-icons/react/fill';
import { useEffect } from 'react';
import { Aviso } from '@/components/aviso';
import { useConfirmacion } from '@/components/dialogo-confirmacion';
import { PasoGeneral } from '@/components/asistente/paso-general';
import { PasoPago } from '@/components/asistente/paso-pago';
import { PasoPersonal } from '@/components/asistente/paso-personal';
import { PasoPostulacion } from '@/components/asistente/paso-postulacion';
import type { DatosAsistente, PreguntaAsistente } from '@/components/asistente/tipos';
import { useAsistente } from '@/components/asistente/use-asistente';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

interface PropsAsistente {
    asistente: DatosAsistente;
    pregunta: PreguntaAsistente | null;
    rutaActualizar: string;
    rutaFoto?: string;
    alSalir: () => void;
}

/**
 * Indicador de los cuatro pasos: en fila en pantallas medianas y en columna, con una
 * línea que los une, en pantallas grandes. Los anteriores se pueden revisar con un clic.
 */
function Pasos({ asistente, alElegir }: { asistente: DatosAsistente; alElegir: (paso: number) => void }) {
    return (
        <ol className="grid gap-2 sm:grid-cols-4 lg:grid-cols-1 lg:gap-1 lg:rounded-2xl lg:border lg:border-zinc-200/80 lg:bg-white lg:p-2 lg:shadow-card dark:lg:border-white/[0.07] dark:lg:bg-white/[0.02] dark:lg:shadow-none">
            {asistente.pasos.map((paso, indice) => {
                const actual = paso.numero === asistente.paso;
                const hecho = paso.numero < asistente.paso;
                const ultimo = indice === asistente.pasos.length - 1;

                return (
                    <li key={paso.numero} className="relative">
                        {!ultimo && (
                            <span
                                aria-hidden
                                className={cn('absolute start-[27px] top-[50px] -bottom-[10px] hidden w-0.5 rounded-full lg:block', hecho ? 'bg-green-500/50' : 'bg-zinc-200 dark:bg-white/[0.08]')}
                            />
                        )}
                        <button
                            type="button"
                            onClick={() => alElegir(paso.numero)}
                            aria-current={actual ? 'step' : undefined}
                            className={cn(
                                'flex w-full items-center gap-3 rounded-xl border px-3.5 py-3 text-start transition-colors lg:border-transparent lg:px-2.5',
                                actual
                                    ? 'border-index-300 bg-white shadow-card ring-3 ring-index-500/10 lg:bg-index-50/70 lg:shadow-none lg:ring-0 dark:border-index-500/40 dark:bg-white/[0.04] dark:lg:bg-index-500/10'
                                    : 'border-zinc-200/80 bg-white/60 hover:bg-white lg:bg-transparent lg:hover:bg-zinc-50 dark:border-white/[0.07] dark:bg-white/[0.02] dark:lg:bg-transparent dark:lg:hover:bg-white/[0.03]',
                            )}
                        >
                            <span
                                className={cn(
                                    'relative z-10 flex size-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold',
                                    actual && 'bg-linear-to-b from-index-400 to-index-600 text-white shadow-[0_4px_10px_-2px_rgba(41,111,240,0.45),inset_0_1px_0_rgba(255,255,255,0.3)]',
                                    hecho && 'bg-green-500 text-white',
                                    !actual && !hecho && 'bg-zinc-100 text-zinc-500 ring-4 ring-white dark:bg-zinc-800 dark:text-zinc-400 dark:ring-zinc-900',
                                )}
                            >
                                {hecho ? <Check className="size-4" /> : paso.numero}
                            </span>
                            <span className="min-w-0">
                                <span className={cn('block truncate text-sm font-medium', actual ? 'text-zinc-900 dark:text-white' : 'text-zinc-600 dark:text-zinc-300')}>{paso.titulo}</span>
                                <span className="block truncate text-xs text-zinc-500 dark:text-zinc-400">{paso.descripcion}</span>
                            </span>
                        </button>
                    </li>
                );
            })}
        </ol>
    );
}

/**
 * Lo que el postulante necesita tener a la mano para inscribirse en línea.
 */
function TengaALaMano() {
    const requisitos = [
        { icono: IdCard, texto: 'DNI o carné de extranjería del postulante' },
        { icono: Receipt, texto: 'Número de operación del voucher del banco' },
        { icono: School, texto: 'Colegio de procedencia y año de egreso' },
        { icono: GraduationCap, texto: 'Sede, carrera y turno al que postula' },
    ];

    return (
        <div className="hidden rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-card lg:block dark:border-white/[0.07] dark:bg-white/[0.02] dark:shadow-none">
            <p className="text-sm font-semibold text-zinc-900 dark:text-white">Tenga a la mano</p>
            <ul className="mt-3 space-y-2.5">
                {requisitos.map(({ icono: Icono, texto }) => (
                    <li key={texto} className="flex items-start gap-2.5 text-[13px] text-zinc-600 dark:text-zinc-300">
                        <span className="flex size-7 shrink-0 items-center justify-center rounded-lg bg-index-50 text-index-500 dark:bg-index-500/10 dark:text-index-300">
                            <Icono className="size-4" />
                        </span>
                        <span className="pt-1">{texto}</span>
                    </li>
                ))}
            </ul>
            <p className="mt-4 flex items-start gap-2 rounded-xl bg-zinc-50 p-3 text-xs text-zinc-500 dark:bg-white/[0.03] dark:text-zinc-400">
                <ShieldCheck className="mt-px size-4 shrink-0 text-green-500" />
                El CEPRE-UNU revisa cada inscripción en línea antes de activarla.
            </p>
        </div>
    );
}

/**
 * Inscripción terminada en el portal: código, estado de revisión y documentos.
 */
function InscripcionRegistrada({ registrada, alSalir }: { registrada: NonNullable<DatosAsistente['registrada']>; alSalir: () => void }) {
    return (
        <div className="mx-auto max-w-2xl space-y-5 rounded-3xl border border-zinc-200/80 bg-white p-6 text-center shadow-card sm:p-10 dark:border-white/[0.07] dark:bg-zinc-900 dark:shadow-none">
            <span className="mx-auto flex size-14 items-center justify-center rounded-2xl bg-linear-to-b from-[#34d399] to-[#10b981] text-white shadow-[0_6px_14px_-4px_rgba(16,185,129,0.5),inset_0_1px_0_rgba(255,255,255,0.35)]">
                <CircleCheck className="size-7" />
            </span>
            <div className="space-y-1">
                <h2 className="text-xl font-semibold text-zinc-900 dark:text-white">Inscripción registrada</h2>
                <p className="text-sm text-zinc-500 dark:text-zinc-400">El CEPRE-UNU revisará sus datos y su pago. Guarde su código de inscripción.</p>
            </div>
            <div className="mx-auto max-w-md space-y-3 rounded-2xl border border-zinc-200/80 p-5 text-start dark:border-white/[0.08]">
                <div className="flex items-center justify-between">
                    <span className="font-mono text-lg font-semibold text-zinc-900 dark:text-white">{registrada.codigo}</span>
                    <Badge variant="aviso">{registrada.estado}</Badge>
                </div>
                <dl className="grid grid-cols-2 gap-3 text-sm">
                    {[
                        ['Proceso', registrada.proceso],
                        ['Sede', registrada.sede],
                        ['Carrera', registrada.carrera],
                        ['Turno', registrada.turno],
                        ['Aula', registrada.aula],
                    ].map(([etiqueta, valor]) => (
                        <div key={etiqueta}>
                            <dt className="text-xs text-zinc-500">{etiqueta}</dt>
                            <dd className="font-medium text-zinc-800 dark:text-zinc-100">{valor ?? '—'}</dd>
                        </div>
                    ))}
                </dl>
            </div>
            <div className="flex flex-wrap justify-center gap-2.5">
                <Button asChild>
                    <a href={registrada.documentos.ficha} target="_blank" rel="noopener">
                        <FileText className="size-4.5" />
                        Descargar ficha
                    </a>
                </Button>
                {registrada.documentos.carnet && (
                    <Button variant="outline" asChild>
                        <a href={registrada.documentos.carnet} target="_blank" rel="noopener">
                            <IdCard className="size-4.5" />
                            Descargar carnet
                        </a>
                    </Button>
                )}
                <Button variant="outline" onClick={alSalir}>
                    Volver al portal
                </Button>
            </div>
        </div>
    );
}

export function AsistenteInscripcion({ asistente, pregunta, rutaActualizar, rutaFoto, alSalir }: PropsAsistente) {
    const control = useAsistente(asistente, rutaActualizar, rutaFoto);
    const confirmar = useConfirmacion();

    useEffect(() => {
        if (!pregunta) {
            return;
        }

        void confirmar({ titulo: pregunta.titulo, descripcion: pregunta.mensaje, textoConfirmar: pregunta.textoConfirmar, textoCancelar: pregunta.textoCancelar }).then((aceptado) => {
            const accion = aceptado ? pregunta.confirmar : pregunta.cancelar;

            if (accion) {
                control.accion(accion);
            }
        });
        // Solo al llegar una pregunta nueva del servidor.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [pregunta]);

    if (asistente.registrada) {
        return <InscripcionRegistrada registrada={asistente.registrada} alSalir={alSalir} />;
    }

    const reiniciar = async (): Promise<void> => {
        if (await confirmar({ titulo: 'Reiniciar formulario', descripcion: 'Se borrarán los datos ingresados en el asistente.', textoConfirmar: 'Reiniciar', peligro: true })) {
            control.accion('cancelarYReiniciarWizard');
        }
    };

    const pasoActual = asistente.pasos[asistente.paso - 1];
    const avance = Math.round(((asistente.paso - 1) / asistente.pasos.length) * 100);

    return (
        <div className="grid items-start gap-6 lg:grid-cols-[272px_minmax(0,1fr)]">
            <aside className="space-y-4 lg:sticky lg:top-6">
                <Pasos asistente={asistente} alElegir={(paso) => paso !== asistente.paso && control.accion('irPaso', paso)} />
                {asistente.modoPublico && <TengaALaMano />}
            </aside>

            <div className="min-w-0 space-y-5">
                {asistente.inicioBloqueado && (
                    <Aviso tono="aviso" titulo="Registro temporalmente no disponible">
                        {asistente.proceso.mensaje}
                    </Aviso>
                )}

                <section className="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-card dark:border-white/[0.07] dark:bg-zinc-900 dark:shadow-none">
                    <div className="h-1 bg-zinc-100 dark:bg-white/[0.05]">
                        <div className="h-full rounded-e-full bg-linear-to-r from-index-400 to-index-600 transition-[width] duration-500" style={{ width: `${Math.max(avance, 4)}%` }} />
                    </div>
                    <div className="p-5 sm:p-7">
                        <header className="mb-7 flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p className="text-xs font-semibold tracking-wide text-index-600 uppercase dark:text-index-300">
                                    Paso {asistente.paso} de {asistente.pasos.length}
                                </p>
                                <h2 className="mt-1 text-xl font-semibold tracking-tight text-zinc-900 dark:text-white">{pasoActual?.titulo}</h2>
                                <p className="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">{pasoActual?.descripcion}</p>
                            </div>
                            {(asistente.pagoValidado || asistente.personaGuardada) && !asistente.editandoInscripcion && (
                                <Button variant="ghost" size="sm" onClick={() => void reiniciar()}>
                                    <RotateCcw className="size-4" />
                                    Reiniciar
                                </Button>
                            )}
                        </header>

                        {asistente.paso === 1 && <PasoPago asistente={asistente} control={control} alSalir={alSalir} />}
                        {asistente.paso === 2 && <PasoPersonal asistente={asistente} control={control} />}
                        {asistente.paso === 3 && <PasoGeneral asistente={asistente} control={control} />}
                        {asistente.paso === 4 && <PasoPostulacion asistente={asistente} control={control} alSalir={alSalir} />}
                    </div>
                </section>
            </div>
        </div>
    );
}
