import { Camera } from '@keyline-icons/react';
import { useRef } from 'react';
import { Aviso } from '@/components/aviso';
import { CampoSelect } from '@/components/campo';
import { Opciones, PiePaso, Seccion } from '@/components/asistente/comunes';
import type { ControlAsistente } from '@/components/asistente/use-asistente';
import type { DatosAsistente } from '@/components/asistente/tipos';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { cn } from '@/lib/utils';

function Dato({ etiqueta, valor }: { etiqueta: string; valor: string | null | undefined }) {
    return (
        <div>
            <dt className="text-xs text-zinc-500 dark:text-zinc-400">{etiqueta}</dt>
            <dd className="text-sm font-medium text-zinc-800 dark:text-zinc-100">{valor || '—'}</dd>
        </div>
    );
}

export function PasoPostulacion({ asistente, control, alSalir }: { asistente: DatosAsistente; control: ControlAsistente; alSalir: () => void }) {
    const { formularios, etiquetas, errores, cambiar, accion, procesando, confirmacion, setConfirmacion, subirFoto } = control;
    const postulacion = formularios.postulacionForm;
    const persona = formularios.personaForm;
    const general = formularios.generalForm;
    const archivo = useRef<HTMLInputElement>(null);
    const { modalidad, sedes, carreras, turnos } = asistente.postulacion;
    const nombre = [persona.apellido_paterno, persona.apellido_materno, persona.apellido_casada, persona.nombres].filter(Boolean).join(' ');
    const nombreDe = (lista: { valor: string; nombre: string }[], valor: string): string | undefined => lista.find((opcion) => opcion.valor === valor)?.nombre;

    if (!asistente.proceso.disponible) {
        return (
            <div className="space-y-4">
                <Aviso tono="aviso" titulo={asistente.proceso.mensaje}>
                    No se puede registrar la postulación hasta que exista un proceso habilitado.
                </Aviso>
                <div className="flex gap-2.5">
                    <Button variant="outline" onClick={() => accion('irPasoAnterior')}>
                        Anterior
                    </Button>
                    <Button variant="outline" onClick={alSalir}>
                        {asistente.modoPublico ? 'Salir' : 'Volver al listado'}
                    </Button>
                </div>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <Seccion titulo={`Proceso ${asistente.proceso.nombre ?? ''}`} descripcion={`Inscripciones del ${asistente.proceso.apertura ?? '—'} al ${asistente.proceso.cierre ?? '—'}`}>
                {modalidad ? (
                    <Aviso tono="exito" titulo={modalidad.nombre}>
                        Modalidad que corresponde al año de egreso {String(general.anio_graduacion)}.
                    </Aviso>
                ) : (
                    <Aviso tono="aviso" titulo={`Sin modalidad para el año de egreso ${String(general.anio_graduacion)}`}>
                        Revise el año de egreso o la oferta del proceso.
                    </Aviso>
                )}
                <div className="grid gap-4 sm:grid-cols-2">
                    <CampoSelect etiqueta="Sede" value={String(postulacion.id_sede)} onChange={(evento) => cambiar('postulacionForm', 'id_sede', evento.target.value, true)} error={errores['postulacionForm.id_sede']} requerido>
                        <Opciones opciones={sedes} vacio="Seleccionar sede" />
                    </CampoSelect>
                    <CampoSelect
                        etiqueta="Carrera profesional"
                        value={String(postulacion.id_carrera)}
                        onChange={(evento) => cambiar('postulacionForm', 'id_carrera', evento.target.value, true)}
                        error={errores['postulacionForm.id_carrera']}
                        disabled={postulacion.id_sede === ''}
                        requerido
                    >
                        <Opciones opciones={carreras} vacio={postulacion.id_sede === '' ? 'Primero elija la sede' : 'Seleccionar carrera'} />
                    </CampoSelect>
                </div>
                <fieldset className="space-y-2">
                    <legend className="text-[13px] font-medium text-zinc-700 dark:text-zinc-200">
                        Turno <span className="text-red-500">*</span>
                    </legend>
                    <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        {turnos.map((turno) => (
                            <label
                                key={turno.valor}
                                className={cn(
                                    'flex cursor-pointer items-center gap-3 rounded-xl border p-3 text-sm transition-colors',
                                    postulacion.id_turno === turno.valor ? 'border-index-300 bg-index-50/50 dark:border-index-500/40 dark:bg-index-500/[0.07]' : 'border-zinc-200/80 dark:border-white/[0.08]',
                                )}
                            >
                                <input type="radio" name="turno" className="size-4 accent-index-500" checked={postulacion.id_turno === turno.valor} onChange={() => cambiar('postulacionForm', 'id_turno', turno.valor, true)} />
                                {turno.nombre}
                            </label>
                        ))}
                    </div>
                    {errores['postulacionForm.id_turno'] && <p className="text-xs font-medium text-red-500">{errores['postulacionForm.id_turno']}</p>}
                </fieldset>
            </Seccion>

            <Seccion titulo="Revisión" descripcion="Revise los datos antes de registrar la inscripción.">
                <div className="flex flex-col gap-5 sm:flex-row">
                    {(asistente.foto.puedeCambiar || asistente.foto.actual) && (
                        <div className="flex shrink-0 flex-col items-center gap-2">
                            <div className="flex aspect-[3/4] w-28 items-center justify-center overflow-hidden rounded-xl border border-zinc-200/80 bg-zinc-50 dark:border-white/[0.08] dark:bg-white/[0.03]">
                                {asistente.foto.actual ? <img src={asistente.foto.actual} alt="Foto del postulante" className="size-full object-cover" /> : <Camera className="size-7 text-zinc-300" />}
                            </div>
                            {asistente.foto.puedeCambiar && (
                                <>
                                    <Button variant="outline" size="sm" onClick={() => archivo.current?.click()} disabled={procesando}>
                                        {asistente.foto.actual ? 'Cambiar foto' : 'Subir foto'}
                                    </Button>
                                    <input ref={archivo} type="file" accept="image/jpeg,image/png" className="sr-only" onChange={(evento) => evento.target.files?.[0] && subirFoto(evento.target.files[0])} />
                                    {errores.fotoPostulante && <p className="max-w-36 text-center text-xs text-red-500">{errores.fotoPostulante}</p>}
                                </>
                            )}
                        </div>
                    )}
                    <dl className="grid flex-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <Dato etiqueta="Postulante" valor={nombre} />
                        <Dato etiqueta="Documento" valor={String(persona.numero_documento)} />
                        <Dato etiqueta="Celular" valor={String(persona.celular)} />
                        <Dato etiqueta="Colegio" valor={etiquetas.colegio || String(general.nombre_colegio_extranjero)} />
                        <Dato etiqueta="Año de egreso" valor={String(general.anio_graduacion)} />
                        <Dato etiqueta="Modalidad" valor={modalidad?.nombre} />
                        <Dato etiqueta="Sede" valor={nombreDe(sedes, String(postulacion.id_sede))} />
                        <Dato etiqueta="Carrera" valor={nombreDe(carreras, String(postulacion.id_carrera))} />
                        <Dato etiqueta="Turno" valor={nombreDe(turnos, String(postulacion.id_turno))} />
                    </dl>
                </div>
                <label className={cn('flex cursor-pointer items-start gap-3 rounded-xl border px-4 py-3', errores.confirmacionDatos ? 'border-red-300 dark:border-red-500/40' : 'border-zinc-200/80 dark:border-white/[0.08]')}>
                    <Checkbox checked={confirmacion} onCheckedChange={(valor) => setConfirmacion(valor === true)} className="mt-0.5" aria-invalid={Boolean(errores.confirmacionDatos) || undefined} />
                    <span className="text-sm text-zinc-700 dark:text-zinc-200">Confirmo que los datos son correctos.</span>
                </label>
                {errores.confirmacionDatos && (
                    <p role="alert" className="text-sm text-red-600 dark:text-red-400">
                        {errores.confirmacionDatos}
                    </p>
                )}
            </Seccion>

            <PiePaso alVolver={() => accion('irPasoAnterior')} textoAccion="Registrar inscripción" alAccion={() => accion('guardarPasoPostulacion')} procesando={procesando} />
        </div>
    );
}
