import { router } from '@inertiajs/react';
import { ArrowLeft, BadgeCheck, FileText, IdCard, ShieldCheck } from '@keyline-icons/react';
import type { ReactNode } from 'react';
import { Aviso } from '@/components/aviso';
import { Enlace } from '@/components/enlace';
import { InsigniaEstado } from '@/components/insignia-estado';
import { Panel } from '@/components/panel';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { usePermiso } from '@/hooks/use-permiso';
import { inicio } from '@/routes';
import { aprobar, carnet, ficha, index } from '@/routes/inscripciones';
import { validar } from '@/routes/pagos';

interface PropsDetalle {
    inscripcion: {
        id: string;
        codigo: string;
        estado: string;
        enRevision: boolean;
        origen: string | null;
        fecha: string | null;
        motivoAnulacion: string | null;
        postulante: string;
        proceso: string | null;
        pago: { estado: string; pagado: number; total: number };
    };
    persona: Record<'tipoDocumento' | 'documento' | 'correo' | 'celular' | 'telefono' | 'apoderado' | 'celularApoderado' | 'nacimiento' | 'sexo' | 'direccion' | 'ubigeoNacimiento' | 'ubigeoDomicilio', string | null>;
    postulacion: Record<'carrera' | 'sede' | 'modalidad' | 'turno' | 'aula' | 'colegio' | 'tipoColegio' | 'paisColegio', string | null> & { anioGraduacion: number | null };
    obligaciones: {
        id: number;
        concepto: string | null;
        total: number;
        pagado: number;
        saldo: number;
        estado: string;
        pagos: { id: number; operacion: string | null; documento: string | null; fecha: string | null; monto: number; estado: string; validador: string | null }[];
    }[];
}

const soles = (monto: number): string => `S/ ${monto.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

function Dato({ etiqueta, children }: { etiqueta: string; children: ReactNode }) {
    return (
        <div className="space-y-0.5">
            <dt className="text-xs text-zinc-500 dark:text-zinc-400">{etiqueta}</dt>
            <dd className="text-sm font-medium text-zinc-800 dark:text-zinc-100">{children ?? <span className="font-normal text-zinc-400 dark:text-zinc-500">No registrado</span>}</dd>
        </div>
    );
}

export default function Detalle({ inscripcion, persona, postulacion, obligaciones }: PropsDetalle) {
    const puede = usePermiso();
    const conSaldo = inscripcion.pago.estado === 'PENDIENTE' || inscripcion.pago.estado === 'PARCIAL';
    const porcentaje = inscripcion.pago.total === 0 ? 100 : Math.min(100, Math.round((inscripcion.pago.pagado / inscripcion.pago.total) * 100));

    return (
        <div className="space-y-6">
            <div className="flex flex-col gap-5 rounded-2xl border border-zinc-200/80 bg-linear-to-br from-white to-index-50/50 p-5 shadow-card sm:p-6 lg:flex-row lg:items-center lg:justify-between dark:border-white/[0.07] dark:from-white/[0.03] dark:to-index-500/[0.05] dark:shadow-none">
                <div className="min-w-0 space-y-2">
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge variant="default" className="font-mono text-[13px]">
                            {inscripcion.codigo}
                        </Badge>
                        <InsigniaEstado estado={inscripcion.estado} />
                        {inscripcion.enRevision && <Badge variant="aviso">En revisión</Badge>}
                        {inscripcion.origen && <Badge variant="secondary">{inscripcion.origen}</Badge>}
                    </div>
                    <h2 className="text-xl font-semibold tracking-tight text-zinc-900 dark:text-white">{inscripcion.postulante}</h2>
                    <p className="text-sm text-zinc-500 dark:text-zinc-400">
                        Proceso {inscripcion.proceso} · inscrito el {inscripcion.fecha}
                    </p>
                </div>
                <div className="flex flex-wrap gap-2">
                    <Button variant="outline" asChild>
                        <Enlace href={index.url()}>
                            <ArrowLeft className="size-4.5" />
                            Volver
                        </Enlace>
                    </Button>
                    <Button variant="outline" asChild>
                        <a href={ficha.url(inscripcion.id)} target="_blank" rel="noopener">
                            <FileText className="size-4.5" />
                            Ficha
                        </a>
                    </Button>
                    {inscripcion.estado === 'ACTIVO' && (
                        <Button variant="outline" asChild>
                            <a href={carnet.url(inscripcion.id)} target="_blank" rel="noopener">
                                <IdCard className="size-4.5" />
                                Carnet
                            </a>
                        </Button>
                    )}
                    {inscripcion.enRevision && puede('inscripcion_estado') && (
                        <Button onClick={() => router.post(aprobar.url(inscripcion.id), {}, { preserveScroll: true })}>
                            <ShieldCheck className="size-4.5" />
                            Aprobar revisión
                        </Button>
                    )}
                </div>
            </div>

            {inscripcion.estado === 'ANULADO' && inscripcion.motivoAnulacion && (
                <Aviso tono="peligro" titulo="Inscripción anulada">
                    {inscripcion.motivoAnulacion}
                </Aviso>
            )}

            <div className="grid items-start gap-5 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)]">
                <div className="space-y-5">
                    <Panel titulo="Datos del postulante" acciones={persona.tipoDocumento && <Badge variant="secondary">{persona.tipoDocumento}</Badge>}>
                        <dl className="grid gap-4 sm:grid-cols-2">
                            <Dato etiqueta="Número de documento">{persona.documento && <span className="font-mono">{persona.documento}</span>}</Dato>
                            <Dato etiqueta="Fecha de nacimiento">{persona.nacimiento}</Dato>
                            <Dato etiqueta="Sexo">{persona.sexo}</Dato>
                            <Dato etiqueta="Correo electrónico">{persona.correo}</Dato>
                            <Dato etiqueta="Celular">{persona.celular}</Dato>
                            <Dato etiqueta="Teléfono">{persona.telefono}</Dato>
                            <Dato etiqueta="Apoderado">
                                {persona.apoderado && (
                                    <>
                                        {persona.apoderado}
                                        {persona.celularApoderado && <span className="block text-xs font-normal text-zinc-500 tabular-nums">{persona.celularApoderado}</span>}
                                    </>
                                )}
                            </Dato>
                            <Dato etiqueta="Dirección">{persona.direccion}</Dato>
                            <Dato etiqueta="Ubigeo de nacimiento">{persona.ubigeoNacimiento}</Dato>
                            <Dato etiqueta="Ubigeo de domicilio">{persona.ubigeoDomicilio}</Dato>
                        </dl>
                    </Panel>

                    <Panel titulo="Postulación">
                        <dl className="grid gap-4 sm:grid-cols-2">
                            <Dato etiqueta="Carrera">{postulacion.carrera}</Dato>
                            <Dato etiqueta="Sede">{postulacion.sede}</Dato>
                            <Dato etiqueta="Modalidad">{postulacion.modalidad}</Dato>
                            <Dato etiqueta="Turno">{postulacion.turno}</Dato>
                            <Dato etiqueta="Aula asignada">{postulacion.aula ?? 'Sin aula asignada'}</Dato>
                            <Dato etiqueta="Año de egreso">{postulacion.anioGraduacion}</Dato>
                            <Dato etiqueta="Colegio">{postulacion.colegio}</Dato>
                            <Dato etiqueta="Tipo de colegio">{postulacion.tipoColegio ?? postulacion.paisColegio}</Dato>
                        </dl>
                    </Panel>
                </div>

                <div className="space-y-5 xl:sticky xl:top-0">
                    <Panel titulo="Pago" descripcion={`${soles(inscripcion.pago.pagado)} de ${soles(inscripcion.pago.total)}`} acciones={<InsigniaEstado estado={inscripcion.pago.estado} />}>
                        <div className="space-y-4">
                            <div className="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-white/[0.06]">
                                <div className="h-full rounded-full bg-linear-to-r from-[#34d399] to-[#10b981]" style={{ width: `${porcentaje}%` }} />
                            </div>
                            {obligaciones.length === 0 && <p className="text-sm text-zinc-500">No existen obligaciones registradas.</p>}
                            {obligaciones.map((obligacion) => (
                                <div key={obligacion.id} className="space-y-2.5 rounded-xl border border-zinc-200/80 p-3.5 dark:border-white/[0.08]">
                                    <div className="flex items-start justify-between gap-3">
                                        <div>
                                            <p className="text-sm font-medium text-zinc-900 dark:text-white">{obligacion.concepto}</p>
                                            <p className="text-xs text-zinc-500 tabular-nums dark:text-zinc-400">
                                                Total {soles(obligacion.total)} · Saldo {soles(obligacion.saldo)}
                                            </p>
                                        </div>
                                        <InsigniaEstado estado={obligacion.estado} />
                                    </div>
                                    {obligacion.pagos.length > 0 && (
                                        <ul className="divide-y divide-zinc-100 border-t border-zinc-100 text-sm dark:divide-white/[0.05] dark:border-white/[0.05]">
                                            {obligacion.pagos.map((pago) => (
                                                <li key={pago.id} className="flex items-center justify-between gap-3 pt-2">
                                                    <span className="min-w-0 text-zinc-600 dark:text-zinc-300">
                                                        Op. <span className="font-mono">{pago.operacion}</span> · {pago.fecha}
                                                        {pago.validador && <span className="block text-xs text-zinc-400">Validó {pago.validador}</span>}
                                                    </span>
                                                    <span className="font-medium tabular-nums">{soles(pago.monto)}</span>
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </div>
                            ))}
                            {conSaldo && inscripcion.estado !== 'ANULADO' && puede('pago_crear') && (
                                <Button variant="outline" asChild className="w-full">
                                    <Enlace href={validar.url({ query: { buscar: inscripcion.codigo } })}>
                                        <BadgeCheck className="size-4.5" />
                                        Validar un pago
                                    </Enlace>
                                </Button>
                            )}
                        </div>
                    </Panel>
                </div>
            </div>
        </div>
    );
}

Detalle.layout = {
    titulo: 'Detalle de inscripción',
    subtitulo: 'Datos del postulante, su postulación y el estado de sus pagos.',
    icono: 'detalle',
    migas: [{ titulo: 'Inicio', href: inicio.url() }, { titulo: 'Inscripciones', href: index.url() }, { titulo: 'Detalle' }],
};
