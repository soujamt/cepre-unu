import { ArrowRight, Search } from '@keyline-icons/react/fill';
import { Aviso } from '@/components/aviso';
import { CampoSelect, CampoTexto } from '@/components/campo';
import type { ControlAsistente } from '@/components/asistente/use-asistente';
import type { DatosAsistente } from '@/components/asistente/tipos';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { cn } from '@/lib/utils';

const soles = (monto: number): string => `S/ ${monto.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

export function PasoPago({ asistente, control, alSalir }: { asistente: DatosAsistente; control: ControlAsistente; alSalir: () => void }) {
    const { formularios, errores, cambiar, accion, marcarPago, procesando } = control;
    const pago = formularios.pagoForm;
    const textoSalir = asistente.modoPublico ? 'Salir' : 'Volver al listado';

    if (!asistente.proceso.disponible) {
        return (
            <div className="space-y-4">
                <Aviso tono="aviso" titulo={asistente.proceso.mensaje}>
                    No se puede inscribir hasta que exista un proceso habilitado con carreras y modalidades activas.
                </Aviso>
                <Button variant="outline" onClick={alSalir}>
                    {textoSalir}
                </Button>
            </div>
        );
    }

    if (asistente.pago.reglas.length === 0) {
        return (
            <div className="space-y-4">
                <Aviso tono="aviso" titulo="No hay modalidades de pago vigentes en el proceso activo.">
                    {asistente.modoPublico ? 'La inscripción en línea aún no está disponible. Consulte en el CEPRE-UNU.' : 'Registre las reglas de pago del proceso en Configuración académica › Reglas de pago.'}
                </Aviso>
                <Button variant="outline" onClick={alSalir}>
                    {textoSalir}
                </Button>
            </div>
        );
    }

    if (asistente.editandoInscripcion && asistente.tienePagoValidado) {
        return (
            <div className="space-y-4">
                <Aviso tono="exito" titulo="Esta inscripción ya tiene su pago registrado.">
                    Las cuotas siguientes se registran desde Inscripciones › Validar pagos.
                </Aviso>
                <Button onClick={() => accion('irPaso', 2)}>
                    Continuar con los datos
                    <ArrowRight className="size-4.5" />
                </Button>
            </div>
        );
    }

    const { montoMinimo, montoMarcado } = asistente.pago;
    const alcanza = montoMinimo !== null && montoMarcado >= montoMinimo;

    return (
        <div className="space-y-6">
            <form
                onSubmit={(evento) => {
                    evento.preventDefault();
                    accion('buscarPagosPasoPago');
                }}
                noValidate
                className="space-y-4"
            >
                <p className="text-sm text-zinc-500 dark:text-zinc-400">
                    Proceso {asistente.proceso.nombre}.{' '}
                    {asistente.modoPublico
                        ? 'Use el DNI del postulante y el número de operación del voucher. Si pagó hoy, el pago puede tardar en aparecer.'
                        : 'El pago debe estar cargado desde el TXT del banco o registrado en Pagos.'}
                </p>
                <div className="grid gap-4 sm:grid-cols-[1fr_1fr_auto]">
                    <CampoTexto
                        etiqueta="DNI / CE del postulante"
                        inputMode="numeric"
                        maxLength={12}
                        autoComplete="off"
                        value={String(pago.numero_documento)}
                        onChange={(evento) => cambiar('pagoForm', 'numero_documento', evento.target.value.replace(/\D/g, ''))}
                        onBlur={(evento) => evento.target.value !== asistente.formularios.pagoForm.numero_documento && cambiar('pagoForm', 'numero_documento', evento.target.value, true)}
                        error={errores['pagoForm.numero_documento']}
                        disabled={asistente.personaGuardada}
                        requerido
                    />
                    <CampoTexto
                        etiqueta="N° de operación"
                        inputMode="numeric"
                        autoComplete="off"
                        value={String(pago.numero_operacion)}
                        onChange={(evento) => cambiar('pagoForm', 'numero_operacion', evento.target.value.replace(/\D/g, ''))}
                        error={errores['pagoForm.numero_operacion']}
                        requerido
                    />
                    <Button type="submit" size="lg" disabled={procesando}>
                        <Search className="size-4.5" />
                        Verificar pago
                    </Button>
                </div>
            </form>

            {errores.busquedaPago && (
                <Aviso tono="aviso" titulo="No encontramos un pago libre con esos datos">
                    {errores.busquedaPago}
                </Aviso>
            )}

            {asistente.busquedaPagoRealizada && (
                <div className="space-y-5">
                    <fieldset className="space-y-2">
                        <legend className="text-sm font-medium text-zinc-800 dark:text-zinc-100">Pagos libres del postulante</legend>
                        <p className="text-sm text-zinc-500 dark:text-zinc-400">Marque los pagos que se usan para esta inscripción; se suman para alcanzar el mínimo.</p>
                        <div className="grid gap-2 sm:grid-cols-2">
                            {asistente.pago.encontrados.map((detalle) => {
                                const marcado = asistente.pagosSeleccionados.includes(detalle.id);

                                return (
                                    <label
                                        key={detalle.id}
                                        className={cn(
                                            'flex cursor-pointer items-center gap-3 rounded-xl border p-3.5 transition-colors',
                                            marcado ? 'border-index-300 bg-index-50/50 dark:border-index-500/40 dark:bg-index-500/[0.07]' : 'border-zinc-200/80 dark:border-white/[0.08]',
                                        )}
                                    >
                                        <Checkbox checked={marcado} onCheckedChange={(valor) => marcarPago(detalle.id, valor === true)} />
                                        <span className="min-w-0 flex-1">
                                            <span className="block text-sm font-semibold text-zinc-900 tabular-nums dark:text-white">{soles(detalle.monto)}</span>
                                            <span className="block text-xs text-zinc-500 dark:text-zinc-400">
                                                Op. <span className="font-mono">{detalle.operacion}</span> · {detalle.fecha} · {detalle.ubicacion}
                                            </span>
                                        </span>
                                    </label>
                                );
                            })}
                        </div>
                    </fieldset>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <CampoSelect
                            etiqueta="Modalidad de pago"
                            value={String(pago.id_regla_pago)}
                            onChange={(evento) => cambiar('pagoForm', 'id_regla_pago', evento.target.value, true)}
                            error={errores['pagoForm.id_regla_pago']}
                            requerido
                        >
                            <option value="">Seleccionar modalidad de pago</option>
                            {asistente.pago.reglas.map((regla) => (
                                <option key={regla.valor} value={regla.valor}>
                                    {regla.nombre}
                                </option>
                            ))}
                        </CampoSelect>
                        {asistente.pago.documentoRequerido && (
                            <CampoTexto
                                etiqueta={asistente.pago.documentoRequerido}
                                autoComplete="off"
                                value={String(pago.numero_documento_beneficio)}
                                onChange={(evento) => cambiar('pagoForm', 'numero_documento_beneficio', evento.target.value)}
                                error={errores['pagoForm.numero_documento_beneficio']}
                                requerido
                            />
                        )}
                    </div>

                    {montoMinimo !== null && (
                        <Aviso tono={alcanza ? 'exito' : 'aviso'} titulo={`Marcado ${soles(montoMarcado)} de un mínimo de ${soles(montoMinimo)}`}>
                            {alcanza
                                ? 'El pago alcanza para inscribir.'
                                : `Falta ${soles(montoMinimo - montoMarcado)}. ${asistente.modoPublico ? 'Marque otro pago suyo o acérquese al CEPRE-UNU.' : 'Marque otro pago del postulante o registre el pago en Pagos.'}`}
                        </Aviso>
                    )}
                    {errores.pagosSeleccionados && <p className="text-sm text-red-600 dark:text-red-400">{errores.pagosSeleccionados}</p>}
                </div>
            )}

            <div className="flex flex-col-reverse gap-3 border-t border-zinc-100 pt-5 sm:flex-row sm:items-center sm:justify-between dark:border-white/[0.06]">
                <p className="text-xs text-zinc-500 dark:text-zinc-400">Los pagos quedan REGISTRADOS recién al terminar la inscripción.</p>
                <div className="flex gap-2.5">
                    <Button variant="outline" onClick={alSalir}>
                        Cancelar
                    </Button>
                    <Button onClick={() => accion('confirmarPasoPago')} disabled={procesando}>
                        Validar y continuar
                        <ArrowRight className="size-4.5" />
                    </Button>
                </div>
            </div>
        </div>
    );
}
