import { useHttp } from '@inertiajs/react';
import { ArrowLeft, FileText, IdCard, Search } from '@keyline-icons/react';
import type { FormEvent } from 'react';
import { CampoTexto } from '@/components/campo';
import { Enlace } from '@/components/enlace';
import { InsigniaEstado } from '@/components/insignia-estado';
import { Button } from '@/components/ui/button';
import { portal } from '@/routes/publico';
import { buscar } from '@/routes/publico/consulta';

interface Resultado {
    codigo: string;
    nombre: string;
    estado: string;
    aula: { nombre: string; detalle: string } | null;
    datos: { etiqueta: string; valor: string | null }[];
    enlaces: { ficha: string; carnet: string | null } | null;
}

/**
 * Consulta de la inscripción con DNI y N° de operación. Se pide como JSON (useHttp),
 * así el resultado no queda guardado en el historial del navegador.
 */
export default function Consulta() {
    const consulta = useHttp<{ documento: string; operacion: string }, Resultado>({ documento: '', operacion: '' });
    const resultado = consulta.hasErrors ? null : consulta.response;

    const enviar = (evento: FormEvent): void => {
        evento.preventDefault();
        void consulta.post(buscar.url()).catch(() => undefined);
    };

    return (
        <div className="space-y-5">
            <form onSubmit={enviar} noValidate className="rounded-xl border border-zinc-200/80 bg-white p-5 shadow-card dark:border-white/[0.06] dark:bg-zinc-900">
                <div className="grid gap-4 sm:grid-cols-12">
                    <CampoTexto
                        etiqueta="DNI / CE"
                        name="documento"
                        inputMode="numeric"
                        maxLength={12}
                        autoComplete="off"
                        value={consulta.data.documento}
                        onChange={(evento) => consulta.setData('documento', evento.target.value)}
                        error={consulta.errors.documento}
                        requerido
                        className="sm:col-span-5"
                    />
                    <CampoTexto
                        etiqueta="N° de operación"
                        name="operacion"
                        inputMode="numeric"
                        maxLength={20}
                        autoComplete="off"
                        value={consulta.data.operacion}
                        onChange={(evento) => consulta.setData('operacion', evento.target.value)}
                        error={consulta.errors.operacion}
                        requerido
                        className="sm:col-span-4"
                    />
                    <div className="sm:col-span-3">
                        <Button type="submit" size="lg" className="w-full font-normal" disabled={consulta.processing}>
                            <Search className="size-5" />
                            {consulta.processing ? 'Consultando…' : 'Consultar'}
                        </Button>
                    </div>
                </div>
            </form>

            {resultado && (
                <section aria-live="polite" className="rounded-xl border border-zinc-200/80 bg-white p-6 shadow-card dark:border-white/[0.06] dark:bg-zinc-900">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div className="min-w-0">
                            <p className="text-xs text-zinc-500 dark:text-zinc-400">Código {resultado.codigo}</p>
                            <h2 className="text-lg font-semibold text-zinc-900 dark:text-white">{resultado.nombre}</h2>
                        </div>
                        <InsigniaEstado estado={resultado.estado} />
                    </div>

                    <div className="mt-5 rounded-lg border border-zinc-200/80 bg-zinc-50/60 p-4 dark:border-white/10 dark:bg-zinc-950/40">
                        <p className="text-xs font-semibold tracking-[0.18em] text-zinc-500 uppercase dark:text-zinc-400">Su aula</p>
                        <p className="mt-1 text-xl font-semibold text-zinc-900 dark:text-white">{resultado.aula?.nombre ?? 'Por asignar'}</p>
                        {resultado.aula && <p className="text-sm text-zinc-500 dark:text-zinc-400">{resultado.aula.detalle}</p>}
                    </div>

                    <dl className="mt-5 grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">
                        {resultado.datos.map((dato) => (
                            <div key={dato.etiqueta} className="min-w-0">
                                <dt className="text-xs text-zinc-500 dark:text-zinc-400">{dato.etiqueta}</dt>
                                <dd className="text-sm font-medium break-words text-zinc-800 tabular-nums dark:text-zinc-100">{dato.valor || '-'}</dd>
                            </div>
                        ))}
                    </dl>

                    {resultado.enlaces && (
                        <div className="mt-6 flex flex-wrap gap-3">
                            <Button asChild>
                                <a href={resultado.enlaces.ficha} target="_blank" rel="noopener">
                                    <FileText className="size-5" />
                                    Descargar ficha
                                </a>
                            </Button>
                            {resultado.enlaces.carnet && (
                                <Button asChild variant="outline">
                                    <a href={resultado.enlaces.carnet} target="_blank" rel="noopener">
                                        <IdCard className="size-5" />
                                        Descargar carnet
                                    </a>
                                </Button>
                            )}
                        </div>
                    )}
                </section>
            )}

            <Button asChild variant="ghost" className="font-normal">
                <Enlace href={portal.url()}>
                    <ArrowLeft className="size-5" />
                    Volver al portal
                </Enlace>
            </Button>
        </div>
    );
}

Consulta.layout = {
    titulo: 'Consultar mi inscripción',
    subtitulo: 'Ingrese su DNI y el número de operación de cualquiera de sus pagos.',
};
