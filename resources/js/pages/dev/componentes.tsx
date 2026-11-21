import { CircleCheck, ClipboardList, Download, Inbox, Mail, Plus, Search, Users, CircleX } from '@keyline-icons/react';
import { ClipboardList as ClipboardListRelleno } from '@keyline-icons/react/fill';
import { ClipboardList as ClipboardListDosTonos } from '@keyline-icons/react/two-tone';
import { useState } from 'react';
import { toast } from 'sonner';
import { CampoArea, CampoSelect, CampoTexto } from '@/components/campo';
import { Combobox } from '@/components/combobox';
import { useConfirmacion } from '@/components/dialogo-confirmacion';
import { InsigniaEstado } from '@/components/insignia-estado';
import { EstadoVacio, Panel } from '@/components/panel';
import { Resumen, ResumenItem } from '@/components/resumen';
import { Buscador, TablaDatos } from '@/components/tabla-datos';
import { Button } from '@/components/ui/button';
import { buscarUbigeos } from '@/lib/busquedas';

interface FilaEjemplo {
    codigo: string;
    postulante: string;
    carrera: string;
    estado: string;
}

const filas: FilaEjemplo[] = [
    { codigo: '2610003', postulante: 'RAMIREZ TORRES LUCIA FERNANDA', carrera: 'ENFERMERÍA', estado: 'ACTIVO' },
    { codigo: '2610029', postulante: 'YAHUARCANI RIOS MILAGROS DEL PILAR', carrera: 'CIENCIAS DE LA COMUNICACIÓN', estado: 'EN REVISIÓN' },
    { codigo: '2610033', postulante: 'NAJAR PISCO YESSICA KARINA', carrera: 'EDUCACIÓN SECUNDARIA', estado: 'ANULADO' },
];

/**
 * Catálogo de los componentes de la interfaz React (solo en desarrollo).
 */
export default function Componentes() {
    const confirmar = useConfirmacion();
    const [busqueda, setBusqueda] = useState('');
    const [sede, setSede] = useState('');
    const [turno, setTurno] = useState('');
    const [ubigeo, setUbigeo] = useState('');

    const filtradas = filas.filter((fila) => `${fila.codigo} ${fila.postulante}`.toLowerCase().includes(busqueda.toLowerCase()));

    return (
        <div className="space-y-6">
            <Resumen>
                <ResumenItem etiqueta="Total inscritas" valor={34} icono={Users} tono="info" iconoDetalle={CircleCheck} detalle="28 activas · 4 pendientes" destacado />
                <ResumenItem etiqueta="Activas" valor={28} icono={CircleCheck} tono="exito" detalle="Con pago validado" />
                <ResumenItem etiqueta="Pago pendiente" valor={6} icono={ClipboardList} tono="aviso" detalle="Con saldo por cobrar" />
                <ResumenItem etiqueta="Anuladas" valor={2} icono={CircleX} tono="peligro" detalle="Fuera del flujo activo" />
            </Resumen>

            <Panel titulo="Botones" descripcion="Variantes y tamaños. El grande mide 48 px, como los campos.">
                <div className="flex flex-wrap items-center gap-3">
                    <Button>
                        <Plus />
                        Nueva inscripción
                    </Button>
                    <Button variant="outline">
                        <Download />
                        Exportar Excel
                    </Button>
                    <Button variant="secondary">Volver</Button>
                    <Button variant="ghost">Cancelar</Button>
                    <Button variant="destructive">Anular</Button>
                    <Button variant="link">Ver detalle</Button>
                    <Button size="sm">Pequeño</Button>
                    <Button size="lg">
                        <Search />
                        Verificar pago
                    </Button>
                    <Button disabled>Deshabilitado</Button>
                </div>
            </Panel>

            <Panel titulo="Campos" descripcion="Etiqueta flotante de 48 px; la etiqueta sube al escribir o al elegir una opción.">
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <CampoTexto etiqueta="Correo electrónico" type="email" iconoInicio={Mail} requerido />
                    <CampoTexto etiqueta="Contraseña" type="password" requerido />
                    <CampoTexto etiqueta="DNI / CE del postulante" inputMode="numeric" maxLength={12} error="El DNI debe tener 8 dígitos." />
                    <CampoSelect etiqueta="Sede" value={sede} onChange={(evento) => setSede(evento.target.value)} requerido>
                        <option value="">Seleccionar sede</option>
                        <option value="1">SEDE CALLERIA</option>
                        <option value="2">FILIAL AGUAYTIA</option>
                    </CampoSelect>
                    <Combobox
                        etiqueta="Turno"
                        valor={turno}
                        alCambiar={(valor) => setTurno(valor)}
                        opciones={[
                            { valor: '1', etiqueta: 'MAÑANA', detalle: '07:00 – 12:00' },
                            { valor: '2', etiqueta: 'TARDE', detalle: '13:00 – 17:00' },
                            { valor: '3', etiqueta: 'NOCHE', detalle: '17:00 – 22:00 (virtual)' },
                        ]}
                    />
                    <Combobox etiqueta="Ubigeo de domicilio" valor={ubigeo} alCambiar={(valor) => setUbigeo(valor)} buscar={buscarUbigeos} placeholderBusqueda="Escriba al menos 2 letras…" />
                    <CampoArea etiqueta="Observación general" className="md:col-span-2 lg:col-span-3" ayuda="Opcional." />
                </div>
            </Panel>

            <Panel titulo="Tabla" descripcion="Encabezado, filas, insignias de estado y búsqueda." acciones={<Buscador valor={busqueda} alCambiar={setBusqueda} placeholder="Buscar por código o nombre" />}>
                <TablaDatos
                    columnas={[
                        { clave: 'codigo', titulo: 'Código', celda: (fila) => <span className="font-medium tabular-nums">{fila.codigo}</span> },
                        { clave: 'postulante', titulo: 'Postulante', celda: (fila) => fila.postulante },
                        { clave: 'carrera', titulo: 'Carrera', celda: (fila) => fila.carrera, className: 'hidden md:table-cell' },
                        { clave: 'estado', titulo: 'Estado', celda: (fila) => <InsigniaEstado estado={fila.estado} /> },
                    ]}
                    filas={filtradas}
                    claveFila={(fila) => fila.codigo}
                    vacio={<EstadoVacio icono={Inbox} titulo="Sin resultados" descripcion="Pruebe con otro código o nombre." />}
                />
            </Panel>

            <div className="grid gap-6 lg:grid-cols-2">
                <Panel titulo="Avisos y confirmación">
                    <div className="flex flex-wrap gap-3">
                        <Button variant="outline" onClick={() => toast.success('Inscripción registrada correctamente.')}>
                            Éxito
                        </Button>
                        <Button variant="outline" onClick={() => toast.error('No se pudo guardar la postulación.')}>
                            Error
                        </Button>
                        <Button variant="outline" onClick={() => toast.warning('El pago no alcanza el mínimo.')}>
                            Advertencia
                        </Button>
                        <Button variant="outline" onClick={() => toast.info('Los pagos quedan registrados al terminar.')}>
                            Información
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={async () => {
                                if (await confirmar({ titulo: '¿Anular la inscripción?', descripcion: 'El cupo del aula se libera y el carnet deja de ser válido.', textoConfirmar: 'Anular', peligro: true })) {
                                    toast.success('Inscripción anulada.');
                                }
                            }}
                        >
                            Confirmar anulación
                        </Button>
                    </div>
                </Panel>

                <Panel titulo="Íconos" descripcion="Keyline: trazo en la interfaz, relleno para lo activo y dos tonos en los encabezados.">
                    <div className="flex items-center gap-6 text-zinc-600 dark:text-zinc-300">
                        <span className="flex flex-col items-center gap-1 text-xs">
                            <ClipboardList className="size-6" /> Trazo
                        </span>
                        <span className="flex flex-col items-center gap-1 text-xs">
                            <ClipboardListRelleno className="size-6 text-index-500 dark:text-index-400" /> Relleno
                        </span>
                        <span className="flex flex-col items-center gap-1 text-xs">
                            <ClipboardListDosTonos className="size-6 text-index-500 dark:text-index-300" /> Dos tonos
                        </span>
                    </div>
                </Panel>
            </div>
        </div>
    );
}

Componentes.layout = {
    titulo: 'Componentes',
    icono: 'componentes',
    migas: [{ titulo: 'Desarrollo' }, { titulo: 'Componentes' }],
};
