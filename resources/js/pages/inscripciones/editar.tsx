import { useForm } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, CircleCheck, Repeat } from '@keyline-icons/react/fill';
import { useState } from 'react';
import { Aviso } from '@/components/aviso';
import { CampoArea, CampoSelect } from '@/components/campo';
import { Enlace } from '@/components/enlace';
import { InsigniaEstado } from '@/components/insignia-estado';
import { Panel } from '@/components/panel';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { inicio } from '@/routes';
import { detalle, index, postulacion } from '@/routes/inscripciones';

interface OpcionSede {
    valor: string;
    nombre: string;
    carreras: { valor: string; nombre: string }[];
}

interface OpcionTurno {
    valor: string;
    nombre: string;
    libres: number;
}

interface PropsEditar {
    inscripcion: {
        id: string;
        codigo: string;
        estado: string;
        enRevision: boolean;
        postulante: string;
        documento: string | null;
        proceso: string | null;
        modalidad: string | null;
        aula: string | null;
        sede: string;
        carrera: string;
        turno: string;
    };
    sedes: OpcionSede[];
    turnos: OpcionTurno[];
}

interface Cambio {
    campo: string;
    antes: string;
    despues: string;
}

export default function Editar({ inscripcion, sedes, turnos }: PropsEditar) {
    const [confirmando, setConfirmando] = useState(false);
    const formulario = useForm({ sede: inscripcion.sede, carrera: inscripcion.carrera, turno: inscripcion.turno, motivo: '' });
    const { data } = formulario;

    const nombreSede = (valor: string): string => sedes.find((sede) => sede.valor === valor)?.nombre ?? '—';
    const carrerasDeSede = (valor: string): OpcionSede['carreras'] => sedes.find((sede) => sede.valor === valor)?.carreras ?? [];
    const nombreCarrera = (sede: string, valor: string): string => carrerasDeSede(sede).find((carrera) => carrera.valor === valor)?.nombre ?? '—';
    const nombreTurno = (valor: string): string => turnos.find((turno) => turno.valor === valor)?.nombre ?? '—';

    const cambios: Cambio[] = [
        { campo: 'Sede', antes: nombreSede(inscripcion.sede), despues: nombreSede(data.sede), distinto: data.sede !== inscripcion.sede },
        {
            campo: 'Carrera',
            antes: nombreCarrera(inscripcion.sede, inscripcion.carrera),
            despues: nombreCarrera(data.sede, data.carrera),
            distinto: data.carrera !== inscripcion.carrera,
        },
        { campo: 'Turno', antes: nombreTurno(inscripcion.turno), despues: nombreTurno(data.turno), distinto: data.turno !== inscripcion.turno },
    ]
        .filter((cambio) => cambio.distinto)
        .map(({ campo, antes, despues }) => ({ campo, antes, despues }));

    const cambiaTurno = data.turno !== inscripcion.turno;
    /** Error general del servidor: no hubo cambios o el nuevo turno no tiene cupo. */
    const errorCambios = (formulario.errors as Partial<Record<string, string>>).cambios;
    const completo = data.sede !== '' && data.carrera !== '' && data.turno !== '';

    const cambiarSede = (sede: string): void => {
        const conservaCarrera = carrerasDeSede(sede).some((carrera) => carrera.valor === data.carrera);
        formulario.setData({ ...data, sede, carrera: conservaCarrera ? data.carrera : '' });
    };

    const confirmar = (): void => {
        formulario.put(postulacion.url(inscripcion.id), {
            preserveScroll: true,
            onError: (errores) => {
                if (errores.sede || errores.carrera || errores.turno) {
                    setConfirmando(false);
                }
            },
        });
    };

    return (
        <div className="space-y-6">
            <div className="to-index-50/50 shadow-card dark:to-index-500/[0.05] flex flex-col gap-4 rounded-2xl border border-zinc-200/80 bg-linear-to-br from-white p-5 sm:p-6 lg:flex-row lg:items-center lg:justify-between dark:border-white/[0.07] dark:from-white/[0.03] dark:shadow-none">
                <div className="min-w-0 space-y-2">
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge variant="default" className="font-mono text-[13px]">
                            {inscripcion.codigo}
                        </Badge>
                        <InsigniaEstado estado={inscripcion.estado} />
                        {inscripcion.enRevision && <Badge variant="aviso">En revisión</Badge>}
                    </div>
                    <h2 className="text-xl font-semibold tracking-tight text-zinc-900 dark:text-white">{inscripcion.postulante}</h2>
                    <p className="text-sm text-zinc-500 dark:text-zinc-400">
                        DNI <span className="font-mono">{inscripcion.documento}</span> · Proceso {inscripcion.proceso} · {inscripcion.modalidad}
                    </p>
                </div>
                <Button variant="outline" asChild>
                    <Enlace href={detalle.url(inscripcion.id)}>
                        <ArrowLeft className="size-4.5" />
                        Volver al detalle
                    </Enlace>
                </Button>
            </div>

            <div className="grid items-start gap-5 xl:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)]">
                <Panel titulo="Nueva postulación" descripcion="Cambie solo lo que corresponda. Antes de guardar se le pedirá confirmar y escribir el motivo.">
                    <div className="space-y-5">
                        <CampoSelect etiqueta="Sede" value={data.sede} onChange={(evento) => cambiarSede(evento.target.value)} error={formulario.errors.sede} requerido>
                            <option value="">Seleccione una sede</option>
                            {sedes.map((sede) => (
                                <option key={sede.valor} value={sede.valor}>
                                    {sede.nombre}
                                </option>
                            ))}
                        </CampoSelect>
                        <CampoSelect
                            etiqueta="Carrera"
                            value={data.carrera}
                            onChange={(evento) => formulario.setData('carrera', evento.target.value)}
                            error={formulario.errors.carrera}
                            ayuda={data.sede === '' ? 'Primero elija la sede.' : undefined}
                            disabled={data.sede === ''}
                            requerido
                        >
                            <option value="">Seleccione una carrera</option>
                            {carrerasDeSede(data.sede).map((carrera) => (
                                <option key={carrera.valor} value={carrera.valor}>
                                    {carrera.nombre}
                                </option>
                            ))}
                        </CampoSelect>
                        <CampoSelect
                            etiqueta="Turno"
                            value={data.turno}
                            onChange={(evento) => formulario.setData('turno', evento.target.value)}
                            error={formulario.errors.turno}
                            ayuda={cambiaTurno ? 'Al cambiar de turno se asigna un aula con cupo del nuevo turno.' : undefined}
                            requerido
                        >
                            {turnos.map((turno) => (
                                <option key={turno.valor} value={turno.valor} disabled={turno.valor !== inscripcion.turno && turno.libres === 0}>
                                    {turno.valor === inscripcion.turno ? `${turno.nombre} · actual` : `${turno.nombre} · ${turno.libres === 0 ? 'sin cupos' : `${turno.libres} cupos libres`}`}
                                </option>
                            ))}
                        </CampoSelect>

                        {errorCambios && !confirmando && (
                            <Aviso tono="peligro" titulo="No se guardó el cambio">
                                {errorCambios}
                            </Aviso>
                        )}

                        <div className="flex flex-wrap justify-end gap-2.5 border-t border-zinc-200/70 pt-5 dark:border-white/[0.06]">
                            <Button variant="outline" asChild>
                                <Enlace href={detalle.url(inscripcion.id)}>Cancelar</Enlace>
                            </Button>
                            <Button onClick={() => setConfirmando(true)} disabled={cambios.length === 0 || !completo}>
                                <Repeat className="size-4.5" />
                                Revisar y confirmar
                            </Button>
                        </div>
                    </div>
                </Panel>

                <Panel titulo="Postulación actual" className="xl:sticky xl:top-0">
                    <dl className="space-y-4 text-sm">
                        {[
                            ['Sede', nombreSede(inscripcion.sede)],
                            ['Carrera', nombreCarrera(inscripcion.sede, inscripcion.carrera)],
                            ['Turno', nombreTurno(inscripcion.turno)],
                            ['Aula', inscripcion.aula ?? 'Sin aula asignada'],
                            ['Modalidad', inscripcion.modalidad],
                        ].map(([etiqueta, valor]) => (
                            <div key={etiqueta} className="space-y-0.5">
                                <dt className="text-xs text-zinc-500 dark:text-zinc-400">{etiqueta}</dt>
                                <dd className="font-medium text-zinc-800 dark:text-zinc-100">{valor}</dd>
                            </div>
                        ))}
                    </dl>
                    <p className="mt-5 border-t border-zinc-200/70 pt-4 text-xs text-pretty text-zinc-500 dark:border-white/[0.06] dark:text-zinc-400">
                        La modalidad depende del año de egreso y no cambia aquí. Los pagos registrados se mantienen.
                    </p>
                </Panel>
            </div>

            <Dialog open={confirmando} onOpenChange={(abierto) => !abierto && !formulario.processing && setConfirmando(false)}>
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Confirmar cambio · {inscripcion.codigo}</DialogTitle>
                        <DialogDescription>Revise los datos que van a cambiar. El cambio y su motivo quedan registrados en el historial de la inscripción.</DialogDescription>
                    </DialogHeader>

                    <ul className="divide-y divide-zinc-100 overflow-hidden rounded-xl border border-zinc-200/80 dark:divide-white/[0.05] dark:border-white/[0.08]">
                        {cambios.map((cambio) => (
                            <li key={cambio.campo} className="grid gap-1.5 px-3.5 py-3 text-sm">
                                <span className="text-xs font-medium text-zinc-500 dark:text-zinc-400">{cambio.campo}</span>
                                <span className="flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <span className="text-zinc-500 line-through decoration-zinc-300 dark:text-zinc-400 dark:decoration-zinc-600">{cambio.antes}</span>
                                    <ArrowRight className="size-4 shrink-0 text-zinc-400" />
                                    <span className="font-medium text-zinc-900 dark:text-white">{cambio.despues}</span>
                                </span>
                            </li>
                        ))}
                    </ul>

                    {cambiaTurno && (
                        <Aviso tono="info" titulo="Se reasigna el aula">
                            Se libera {inscripcion.aula ? `el aula ${inscripcion.aula}` : 'el aula actual'} y se asigna una con cupo del nuevo turno.
                        </Aviso>
                    )}

                    <CampoArea
                        etiqueta="Motivo del cambio"
                        value={data.motivo}
                        onChange={(evento) => formulario.setData('motivo', evento.target.value)}
                        error={formulario.errors.motivo}
                        maxLength={500}
                        ayuda="Por ejemplo: solicitud del postulante con documento presentado. Mínimo 10 caracteres."
                        requerido
                    />

                    {errorCambios && (
                        <Aviso tono="peligro" titulo="No se guardó el cambio">
                            {errorCambios}
                        </Aviso>
                    )}

                    <p className="text-xs text-zinc-500 dark:text-zinc-400">Después de guardar, vuelva a imprimir la ficha y el carnet.</p>

                    <DialogFooter>
                        <Button variant="outline" onClick={() => setConfirmando(false)} disabled={formulario.processing}>
                            Cancelar
                        </Button>
                        <Button onClick={confirmar} disabled={formulario.processing}>
                            <CircleCheck className="size-4.5" />
                            {formulario.processing ? 'Guardando…' : 'Confirmar cambio'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}

Editar.layout = {
    titulo: 'Editar postulación',
    subtitulo: 'Cambio de sede, carrera o turno de una inscripción registrada.',
    icono: 'editarInscripcion',
    migas: [{ titulo: 'Inicio', href: inicio.url() }, { titulo: 'Inscripciones', href: index.url() }, { titulo: 'Editar postulación' }],
};
