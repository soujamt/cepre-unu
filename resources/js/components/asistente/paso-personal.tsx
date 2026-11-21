import { IdCard, Search } from '@keyline-icons/react/fill';
import { Aviso } from '@/components/aviso';
import type { TonoAviso } from '@/components/aviso';
import { CampoArea, CampoSelect, CampoTexto } from '@/components/campo';
import { Combobox } from '@/components/combobox';
import { Interruptor, Opciones, PiePaso, Seccion } from '@/components/asistente/comunes';
import type { ControlAsistente } from '@/components/asistente/use-asistente';
import type { DatosAsistente } from '@/components/asistente/tipos';
import { Button } from '@/components/ui/button';
import { buscarUbigeos } from '@/lib/busquedas';

const tonosReniec: Record<string, TonoAviso> = { success: 'exito', warning: 'aviso', error: 'peligro', info: 'info' };

export function PasoPersonal({ asistente, control }: { asistente: DatosAsistente; control: ControlAsistente }) {
    const { formularios, etiquetas, errores, cambiar, cambiarEtiqueta, accion, procesando } = control;
    const persona = formularios.personaForm;
    const catalogos = asistente.catalogos;
    const texto = (campo: string): string => String(persona[campo] ?? '');
    const bandera = (campo: string): boolean => persona[campo] === true;
    const esDni = catalogos.tipoDocumentoDni !== '' && texto('id_tipo_documento') === catalogos.tipoDocumentoDni;
    const campo = (nombre: string, etiqueta: string, propiedades: Partial<React.ComponentProps<typeof CampoTexto>> = {}) => (
        <CampoTexto etiqueta={etiqueta} value={texto(nombre)} onChange={(evento) => cambiar('personaForm', nombre, evento.target.value)} error={errores[`personaForm.${nombre}`]} {...propiedades} />
    );

    return (
        <div className="space-y-6">
            <Seccion titulo="Identificación" descripcion={asistente.modoPublico ? 'El número es el del pago validado. Con 8 dígitos se elige DNI; si es otro documento, cámbielo.' : 'Seleccione el documento, registre el número y busque los datos cuando aplique.'}>
                <div className="grid gap-4 sm:grid-cols-[1fr_1fr_auto]">
                    <CampoSelect
                        etiqueta="Tipo documento"
                        value={texto('id_tipo_documento')}
                        onChange={(evento) => cambiar('personaForm', 'id_tipo_documento', evento.target.value, true)}
                        error={errores['personaForm.id_tipo_documento']}
                        requerido
                    >
                        <Opciones opciones={catalogos.tiposDocumento} vacio="Seleccionar tipo" />
                    </CampoSelect>
                    <CampoTexto
                        etiqueta="Número documento"
                        inputMode={esDni ? 'numeric' : 'text'}
                        maxLength={12}
                        value={texto('numero_documento')}
                        onChange={(evento) => cambiar('personaForm', 'numero_documento', evento.target.value)}
                        onBlur={(evento) => evento.target.value !== asistente.formularios.personaForm.numero_documento && cambiar('personaForm', 'numero_documento', evento.target.value, true)}
                        error={errores['personaForm.numero_documento']}
                        disabled={asistente.modoPublico}
                        requerido
                    />
                    <Button size="lg" variant="outline" onClick={() => accion('buscarDatosPersona')} disabled={procesando}>
                        {esDni ? <IdCard className="size-4.5" /> : <Search className="size-4.5" />}
                        Buscar datos
                    </Button>
                </div>
                {asistente.reniec && (
                    <Aviso tono={tonosReniec[asistente.reniec.tipo] ?? 'info'} titulo="Búsqueda de datos">
                        {asistente.reniec.mensaje}
                    </Aviso>
                )}
            </Seccion>

            <Seccion titulo="Datos personales" descripcion="Apellidos, nombres, nacimiento, sexo y estado civil.">
                <div className="flex flex-wrap gap-6">
                    <Interruptor etiqueta="Solo un apellido" marcado={bandera('solo_un_apellido')} alCambiar={(valor) => cambiar('personaForm', 'solo_un_apellido', valor, true)} />
                    <Interruptor etiqueta="Tiene apellido de casado(a)" marcado={bandera('tiene_apellido_casada')} alCambiar={(valor) => cambiar('personaForm', 'tiene_apellido_casada', valor, true)} />
                </div>
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {campo('apellido_paterno', 'Primer apellido', { requerido: true })}
                    {!bandera('solo_un_apellido') && campo('apellido_materno', 'Segundo apellido', { requerido: true })}
                    {bandera('tiene_apellido_casada') && campo('apellido_casada', 'Apellido de casado(a)', { requerido: true })}
                </div>
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {campo('nombres', 'Nombres', { requerido: true, className: 'lg:col-span-2' })}
                    {campo('fecha_nacimiento', 'Fecha nacimiento', { type: 'date', requerido: true })}
                    <CampoSelect etiqueta="Sexo" value={texto('sexo')} onChange={(evento) => cambiar('personaForm', 'sexo', evento.target.value)} error={errores['personaForm.sexo']} requerido>
                        <Opciones opciones={catalogos.sexos} vacio="Seleccionar sexo" />
                    </CampoSelect>
                    <CampoSelect etiqueta="Estado civil" value={texto('id_estado_civil')} onChange={(evento) => cambiar('personaForm', 'id_estado_civil', evento.target.value)} error={errores['personaForm.id_estado_civil']} requerido>
                        <Opciones opciones={catalogos.estadosCiviles} vacio="Seleccionar estado civil" />
                    </CampoSelect>
                </div>
            </Seccion>

            <Seccion titulo="Nacimiento, domicilio y contacto">
                <div className="grid gap-4 sm:grid-cols-2">
                    <CampoSelect etiqueta="País nacimiento" value={texto('id_pais_nacimiento')} onChange={(evento) => cambiar('personaForm', 'id_pais_nacimiento', evento.target.value)} error={errores['personaForm.id_pais_nacimiento']} requerido>
                        <Opciones opciones={catalogos.paises} vacio="Seleccionar país" />
                    </CampoSelect>
                    <CampoSelect etiqueta="Nacionalidad" value={texto('id_nacionalidad')} onChange={(evento) => cambiar('personaForm', 'id_nacionalidad', evento.target.value)} error={errores['personaForm.id_nacionalidad']} requerido>
                        <Opciones opciones={catalogos.nacionalidades} vacio="Seleccionar nacionalidad" />
                    </CampoSelect>
                    <Combobox
                        etiqueta="Ubigeo nacimiento"
                        valor={texto('id_ubigeo_nacimiento')}
                        alCambiar={(valor, opcion) => {
                            cambiarEtiqueta('ubigeoNacimiento', opcion?.etiqueta ?? '');
                            cambiar('personaForm', 'id_ubigeo_nacimiento', valor);
                        }}
                        buscar={buscarUbigeos}
                        etiquetaSeleccionada={etiquetas.ubigeoNacimiento}
                        placeholderBusqueda="Distrito, provincia o código…"
                        error={errores['personaForm.id_ubigeo_nacimiento']}
                        requerido
                    />
                    <Combobox
                        etiqueta="Ubigeo domicilio"
                        valor={texto('id_ubigeo_domicilio')}
                        alCambiar={(valor, opcion) => {
                            cambiarEtiqueta('ubigeoDomicilio', opcion?.etiqueta ?? '');
                            cambiar('personaForm', 'id_ubigeo_domicilio', valor);
                        }}
                        buscar={buscarUbigeos}
                        etiquetaSeleccionada={etiquetas.ubigeoDomicilio}
                        placeholderBusqueda="Distrito, provincia o código…"
                        error={errores['personaForm.id_ubigeo_domicilio']}
                        requerido
                    />
                </div>
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {campo('direccion', 'Dirección', { requerido: true, className: 'lg:col-span-3' })}
                    {campo('correo_electronico', 'Correo electrónico', { type: 'email', requerido: true })}
                    {campo('celular', 'Celular', { inputMode: 'numeric', maxLength: 9, requerido: true })}
                    {campo('telefono', 'Teléfono', { inputMode: 'numeric' })}
                    {campo('nombre_apoderado', 'Nombre completo del apoderado', { requerido: true, className: 'lg:col-span-2' })}
                    {campo('celular_apoderado', 'Celular del apoderado', { inputMode: 'numeric', maxLength: 9, requerido: true })}
                </div>
            </Seccion>

            <Seccion titulo="Perfil complementario" descripcion="Discapacidad, identidad étnica y lengua materna.">
                <Interruptor etiqueta="Tiene discapacidad" marcado={bandera('discapacidad')} alCambiar={(valor) => cambiar('personaForm', 'discapacidad', valor, true)} />
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {bandera('discapacidad') && (
                        <CampoSelect etiqueta="Tipo discapacidad" value={texto('id_tipo_discapacidad')} onChange={(evento) => cambiar('personaForm', 'id_tipo_discapacidad', evento.target.value)} error={errores['personaForm.id_tipo_discapacidad']} requerido>
                            <Opciones opciones={catalogos.tiposDiscapacidad} vacio="Seleccionar tipo" />
                        </CampoSelect>
                    )}
                    <CampoSelect etiqueta="Identidad étnica" value={texto('id_identidad_etnica')} onChange={(evento) => cambiar('personaForm', 'id_identidad_etnica', evento.target.value)} error={errores['personaForm.id_identidad_etnica']} requerido>
                        <Opciones opciones={catalogos.identidadesEtnicas} vacio="Seleccionar identidad" />
                    </CampoSelect>
                    <CampoSelect etiqueta="Lengua materna" value={texto('tipo_lengua_materna')} onChange={(evento) => cambiar('personaForm', 'tipo_lengua_materna', evento.target.value, true)} error={errores['personaForm.tipo_lengua_materna']} requerido>
                        <Opciones opciones={catalogos.lenguasMaternas} vacio="Seleccionar tipo" />
                    </CampoSelect>
                    {texto('tipo_lengua_materna') === 'NATIVA' && (
                        <CampoSelect etiqueta="Lengua nativa" value={texto('id_lengua_nativa')} onChange={(evento) => cambiar('personaForm', 'id_lengua_nativa', evento.target.value)} error={errores['personaForm.id_lengua_nativa']} requerido>
                            <Opciones opciones={catalogos.lenguasNativas} vacio="Seleccionar lengua" />
                        </CampoSelect>
                    )}
                    {texto('tipo_lengua_materna') === 'EXTRANJERA' && (
                        <CampoSelect etiqueta="Lengua extranjera" value={texto('id_lengua_extranjera')} onChange={(evento) => cambiar('personaForm', 'id_lengua_extranjera', evento.target.value)} error={errores['personaForm.id_lengua_extranjera']} requerido>
                            <Opciones opciones={catalogos.lenguasExtranjeras} vacio="Seleccionar lengua" />
                        </CampoSelect>
                    )}
                </div>
                <CampoArea etiqueta="Observación" value={texto('observacion')} onChange={(evento) => cambiar('personaForm', 'observacion', evento.target.value)} error={errores['personaForm.observacion']} />
            </Seccion>

            <PiePaso alVolver={() => accion('irPasoAnterior')} textoAccion="Guardar y continuar" alAccion={() => accion('guardarPasoPersonal')} procesando={procesando} />
        </div>
    );
}
