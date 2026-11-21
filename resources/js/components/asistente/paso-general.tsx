import { Aviso } from '@/components/aviso';
import { CampoArea, CampoSelect, CampoTexto } from '@/components/campo';
import { Combobox } from '@/components/combobox';
import { Opciones, PiePaso, Seccion } from '@/components/asistente/comunes';
import type { ControlAsistente } from '@/components/asistente/use-asistente';
import type { DatosAsistente } from '@/components/asistente/tipos';
import { Badge } from '@/components/ui/badge';
import { buscarColegios } from '@/lib/busquedas';

export function PasoGeneral({ asistente, control }: { asistente: DatosAsistente; control: ControlAsistente }) {
    const { formularios, etiquetas, errores, cambiar, cambiarEtiqueta, accion, procesando } = control;
    const general = formularios.generalForm;
    const texto = (campo: string): string => String(general[campo] ?? '');
    const pais = texto('id_pais_colegio');
    const esPeru = pais !== '' && pais === asistente.catalogos.paisPeru;
    const tipoColegio = texto('id_tipo_colegio');
    const modalidad = asistente.postulacion.modalidad;

    return (
        <div className="space-y-6">
            <Seccion titulo="Egreso y colegio" descripcion="El año de egreso define la modalidad de admisión.">
                <div className="grid gap-4 sm:grid-cols-3">
                    <div className="space-y-2">
                        <CampoTexto
                            etiqueta="Año de egreso (secundaria)"
                            inputMode="numeric"
                            maxLength={4}
                            value={texto('anio_graduacion')}
                            onChange={(evento) => cambiar('generalForm', 'anio_graduacion', evento.target.value.replace(/\D/g, ''))}
                            onBlur={(evento) => evento.target.value !== String(asistente.formularios.generalForm.anio_graduacion) && cambiar('generalForm', 'anio_graduacion', evento.target.value, true)}
                            error={errores['generalForm.anio_graduacion']}
                            ayuda={modalidad ? undefined : 'Año en que terminó o terminará la secundaria.'}
                            requerido
                        />
                        {modalidad && (
                            <p className="flex items-center gap-1.5 ps-1 text-xs text-zinc-500" aria-live="polite">
                                Modalidad: <Badge variant="info">{modalidad.nombre}</Badge>
                            </p>
                        )}
                    </div>
                    <CampoSelect etiqueta="País del colegio" value={pais} onChange={(evento) => cambiar('generalForm', 'id_pais_colegio', evento.target.value, true)} error={errores['generalForm.id_pais_colegio']} requerido>
                        <Opciones opciones={asistente.catalogos.paises} vacio="Seleccionar país" />
                    </CampoSelect>
                    <CampoSelect etiqueta="Tipo de colegio" value={tipoColegio} onChange={(evento) => cambiar('generalForm', 'id_tipo_colegio', evento.target.value, true)} error={errores['generalForm.id_tipo_colegio']} requerido>
                        <Opciones opciones={asistente.catalogos.tiposColegio} vacio="Seleccionar tipo" />
                    </CampoSelect>
                </div>

                {pais === '' ? (
                    <Aviso tono="info">Seleccione el país del colegio para registrar el colegio de procedencia.</Aviso>
                ) : esPeru ? (
                    <Combobox
                        etiqueta="Colegio"
                        valor={texto('id_colegio')}
                        alCambiar={(valor, opcion) => {
                            cambiarEtiqueta('colegio', opcion?.etiqueta ?? '');
                            cambiar('generalForm', 'id_colegio', valor, true);
                        }}
                        buscar={(busqueda) => buscarColegios(busqueda, tipoColegio)}
                        etiquetaSeleccionada={etiquetas.colegio}
                        placeholderBusqueda={tipoColegio ? 'Código o nombre del colegio' : 'Primero seleccione el tipo de colegio'}
                        error={errores['generalForm.id_colegio']}
                        deshabilitado={tipoColegio === ''}
                        requerido
                    />
                ) : (
                    <CampoTexto
                        etiqueta="Nombre del colegio extranjero"
                        value={texto('nombre_colegio_extranjero')}
                        onChange={(evento) => cambiar('generalForm', 'nombre_colegio_extranjero', evento.target.value)}
                        error={errores['generalForm.nombre_colegio_extranjero']}
                        requerido
                    />
                )}
            </Seccion>

            <Seccion titulo="Postulaciones anteriores">
                <div className="grid gap-4 sm:grid-cols-2">
                    <CampoTexto
                        etiqueta="Veces que postuló a la UNU"
                        type="number"
                        min={0}
                        value={texto('veces_postula_unu')}
                        onChange={(evento) => cambiar('generalForm', 'veces_postula_unu', Number(evento.target.value || 0))}
                        error={errores['generalForm.veces_postula_unu']}
                    />
                    <CampoTexto
                        etiqueta="Veces que postuló a otras universidades"
                        type="number"
                        min={0}
                        value={texto('veces_postula_otras')}
                        onChange={(evento) => cambiar('generalForm', 'veces_postula_otras', Number(evento.target.value || 0))}
                        error={errores['generalForm.veces_postula_otras']}
                    />
                </div>
                <CampoArea etiqueta="Observación general" value={texto('observacion')} onChange={(evento) => cambiar('generalForm', 'observacion', evento.target.value)} error={errores['generalForm.observacion']} />
            </Seccion>

            <PiePaso alVolver={() => accion('irPasoAnterior')} textoAccion="Guardar y continuar" alAccion={() => accion('guardarPasoGeneral')} procesando={procesando} />
        </div>
    );
}
