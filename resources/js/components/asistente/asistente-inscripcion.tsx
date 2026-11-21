import { Check, CircleCheck, FileText, IdCard, RotateCcw } from '@keyline-icons/react';
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
 * Indicador de los cuatro pasos; los anteriores se pueden revisar con un clic.
 */
function Pasos({ asistente, alElegir }: { asistente: DatosAsistente; alElegir: (paso: number) => void }) {
    return (
        <ol className="grid gap-2 sm:grid-cols-4">
            {asistente.pasos.map((paso) => {
                const actual = paso.numero === asistente.paso;
                const hecho = paso.numero < asistente.paso;

                return (
                    <li key={paso.numero}>
                        <button
                            type="button"
                            onClick={() => alElegir(paso.numero)}
                            aria-current={actual ? 'step' : undefined}
                            className={cn(
                                'flex w-full items-center gap-3 rounded-xl border px-3.5 py-3 text-start transition-colors',
                                actual ? 'border-index-300 bg-white shadow-card ring-3 ring-index-500/10 dark:border-index-500/40 dark:bg-white/[0.04]' : 'border-zinc-200/80 bg-white/60 hover:bg-white dark:border-white/[0.07] dark:bg-white/[0.02]',
                            )}
                        >
                            <span
                                className={cn(
                                    'flex size-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold',
                                    actual && 'bg-linear-to-b from-index-400 to-index-600 text-white shadow-[inset_0_1px_0_rgba(255,255,255,0.3)]',
                                    hecho && 'bg-green-500/15 text-green-600 dark:text-green-400',
                                    !actual && !hecho && 'bg-zinc-100 text-zinc-500 dark:bg-white/[0.06] dark:text-zinc-400',
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
 * Inscripción terminada en el portal: código, estado de revisión y documentos.
 */
function InscripcionRegistrada({ registrada, alSalir }: { registrada: NonNullable<DatosAsistente['registrada']>; alSalir: () => void }) {
    return (
        <div className="space-y-5 text-center">
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

    return (
        <div className="space-y-6">
            <Pasos asistente={asistente} alElegir={(paso) => paso !== asistente.paso && control.accion('irPaso', paso)} />

            {asistente.inicioBloqueado && (
                <Aviso tono="aviso" titulo="Registro temporalmente no disponible">
                    {asistente.proceso.mensaje}
                </Aviso>
            )}

            <section className="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-card sm:p-6 dark:border-white/[0.07] dark:bg-white/[0.02] dark:shadow-none">
                <header className="mb-6 flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p className="text-xs font-medium text-index-600 dark:text-index-300">
                            Paso {asistente.paso} de {asistente.pasos.length}
                        </p>
                        <h2 className="text-lg font-semibold text-zinc-900 dark:text-white">{asistente.pasos[asistente.paso - 1]?.titulo}</h2>
                        <p className="text-sm text-zinc-500 dark:text-zinc-400">{asistente.pasos[asistente.paso - 1]?.descripcion}</p>
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
            </section>
        </div>
    );
}
