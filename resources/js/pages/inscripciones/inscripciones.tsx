import { router, useForm } from '@inertiajs/react';
import { Ban, BadgeCheck, Building, CircleCheck, CircleDollarSign, CircleX, ClipboardList, Download, Eye, FileText, SlidersHorizontal, IdCard, PenLine, Plus, Repeat, ShieldCheck, Users } from '@keyline-icons/react/fill';
import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';
import { Aviso } from '@/components/aviso';
import { AvatarPostulante } from '@/components/avatar-postulante';
import { CampoArea } from '@/components/campo';
import { Enlace } from '@/components/enlace';
import { ContadorFiltro, FiltroEstado, claseBotonFiltro } from '@/components/filtro-estado';
import { FiltroLista } from '@/components/filtro-lista';
import { InsigniaEstado } from '@/components/insignia-estado';
import { MenuAcciones } from '@/components/menu-acciones';
import { EstadoVacio } from '@/components/panel';
import { Resumen, ResumenItem } from '@/components/resumen';
import { Buscador, TablaDatos, useFiltros } from '@/components/tabla-datos';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { usePermiso } from '@/hooks/use-permiso';
import { cn } from '@/lib/utils';
import { inicio } from '@/routes';
import { anular, aprobar, aula, aulas as aulasDisponibles, carnet, carnets, crear, continuar, detalle, editar, exportar, ficha, index } from '@/routes/inscripciones';
import { validar } from '@/routes/pagos';
import type { Opcion, Paginado } from '@/types';

interface Inscripcion {
    /** Id codificado, el mismo de las URL de detalle, ficha y carnet. */
    id: string;
    codigo: string;
    postulante: string;
    iniciales: string;
    foto: string | null;
    dni: string | null;
    proceso: string | null;
    carrera: string | null;
    sede: string | null;
    modalidad: string | null;
    turno: string | null;
    aula: string | null;
    estado: string;
    enRevision: boolean;
    pago: string;
    fecha: string | null;
}

type Filtros = {
    buscar: string;
    proceso: string;
    procesoElegido: string | null;
    modalidad: string;
    sede: string;
    turno: string;
    aula: string;
    estado: string[];
    pago: string;
    revision: boolean;
    porPagina: number;
};

interface PropsInscripciones {
    inscripciones: Paginado<Inscripcion>;
    filtros: Filtros;
    resumen: { total: number; activas: number; pendientes: number; pagoPendiente: number; anuladas: number };
    totalEnRevision: number;
    opciones: {
        procesos: Opcion[];
        modalidades: Opcion[];
        sedes: Opcion[];
        turnos: Opcion[];
        aulas: Opcion[];
        estados: Opcion[];
        pagos: Opcion[];
    };
}

interface AulaDisponible {
    id: number;
    nombre: string | null;
    piso: string | null;
    lugar: string | null;
    ocupados: number;
    capacidad: number;
}

const TODOS = 'todos';

function DialogoAnular({ inscripcion, alCerrar }: { inscripcion: Inscripcion | null; alCerrar: () => void }) {
    const formulario = useForm({ motivo: '' });

    const enviar = (): void => {
        if (inscripcion) {
            formulario.post(anular.url(inscripcion.id), {
                preserveScroll: true,
                onSuccess: () => {
                    formulario.reset();
                    alCerrar();
                },
            });
        }
    };

    return (
        <Dialog open={inscripcion !== null} onOpenChange={(abierto) => !abierto && alCerrar()}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Anular inscripción {inscripcion?.codigo}</DialogTitle>
                    <DialogDescription>Se libera el aula asignada y el carnet deja de ser válido. Esta acción no se puede deshacer.</DialogDescription>
                </DialogHeader>
                <CampoArea
                    etiqueta="Motivo de anulación"
                    value={formulario.data.motivo}
                    onChange={(evento) => formulario.setData('motivo', evento.target.value)}
                    error={formulario.errors.motivo}
                    maxLength={500}
                    ayuda="Mínimo 10 caracteres."
                    requerido
                />
                <DialogFooter>
                    <Button variant="outline" onClick={alCerrar}>
                        Cancelar
                    </Button>
                    <Button variant="destructive" onClick={enviar} disabled={formulario.processing}>
                        <Ban className="size-4.5" />
                        Anular
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function DialogoAula({ inscripcion, alCerrar }: { inscripcion: Inscripcion | null; alCerrar: () => void }) {
    const [datos, setDatos] = useState<{ turno: string | null; actual: number | null; aulas: AulaDisponible[] } | null>(null);
    const [elegida, setElegida] = useState<number | null>(null);
    const formulario = useForm({ aula: '' });
    const id = inscripcion?.id ?? null;

    useEffect(() => {
        if (id === null) {
            return;
        }

        let vigente = true;
        setDatos(null);

        fetch(aulasDisponibles.url(id), { headers: { Accept: 'application/json' } }).then(async (respuesta) => {
            const cuerpo = await respuesta.json();

            if (!vigente) {
                return;
            }

            if (!respuesta.ok) {
                toast.error(cuerpo.message ?? 'No se pudieron cargar las aulas.');
                alCerrar();

                return;
            }

            setDatos(cuerpo);
            setElegida(cuerpo.actual);
        });

        return () => {
            vigente = false;
        };
    }, [id, alCerrar]);

    const cerrar = alCerrar;

    const guardar = (): void => {
        if (inscripcion && elegida !== null) {
            formulario.transform(() => ({ aula: String(elegida) }));
            formulario.put(aula.url(inscripcion.id), { preserveScroll: true, onSuccess: cerrar });
        }
    };

    return (
        <Dialog open={inscripcion !== null} onOpenChange={(abierto) => !abierto && cerrar()}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Asignar aula · {inscripcion?.codigo}</DialogTitle>
                    <DialogDescription>Aulas del turno {datos?.turno ?? inscripcion?.turno ?? '—'} en el proceso.</DialogDescription>
                </DialogHeader>
                {datos === null ? (
                    <p className="py-6 text-center text-sm text-zinc-500">Cargando aulas…</p>
                ) : datos.aulas.length === 0 ? (
                    <Aviso tono="aviso">No hay aulas habilitadas para el turno de la inscripción.</Aviso>
                ) : (
                    <div className="scrollbar-fino grid max-h-80 gap-2 overflow-y-auto">
                        {datos.aulas.map((opcion) => {
                            const llena = opcion.ocupados >= opcion.capacidad && opcion.id !== datos.actual;

                            return (
                                <label
                                    key={opcion.id}
                                    className={cn(
                                        'flex cursor-pointer items-center gap-3 rounded-xl border p-3 transition-colors',
                                        elegida === opcion.id ? 'border-index-300 bg-index-50/50 dark:border-index-500/40 dark:bg-index-500/[0.07]' : 'border-zinc-200/80 dark:border-white/[0.08]',
                                        llena && 'cursor-not-allowed opacity-60',
                                    )}
                                >
                                    <input type="radio" name="aula" className="accent-index-500 size-4" disabled={llena} checked={elegida === opcion.id} onChange={() => setElegida(opcion.id)} />
                                    <span className="min-w-0 flex-1">
                                        <span className="block text-sm font-medium text-zinc-900 dark:text-white">{opcion.nombre}</span>
                                        <span className="block text-xs text-zinc-500 dark:text-zinc-400">
                                            {opcion.piso} · {opcion.lugar}
                                        </span>
                                    </span>
                                    <span className={cn('text-xs tabular-nums', llena ? 'text-red-500' : 'text-zinc-500 dark:text-zinc-400')}>
                                        {opcion.ocupados} / {opcion.capacidad}
                                    </span>
                                </label>
                            );
                        })}
                    </div>
                )}
                {formulario.errors.aula && <p className="text-sm text-red-600">{formulario.errors.aula}</p>}
                <DialogFooter>
                    <Button variant="outline" onClick={cerrar}>
                        Cancelar
                    </Button>
                    <Button onClick={guardar} disabled={elegida === null || formulario.processing}>
                        <Building className="size-4.5" />
                        Asignar aula
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

export default function Inscripciones({ inscripciones, filtros, resumen, totalEnRevision, opciones }: PropsInscripciones) {
    const puede = usePermiso();
    const [filtro, cambiarFiltro] = useFiltros<Filtros>(filtros);
    const [anulando, setAnulando] = useState<Inscripcion | null>(null);
    const [asignando, setAsignando] = useState<Inscripcion | null>(null);
    const cerrarAula = useCallback(() => setAsignando(null), []);
    const procesoVisible = filtro.procesoElegido === TODOS ? '' : filtro.proceso;
    const secundariosActivos = [filtro.modalidad, filtro.sede, filtro.turno, filtro.aula, filtro.pago].filter(Boolean).length;

    const consultaActual = (): Record<string, string | string[]> => ({
        buscar: filtro.buscar,
        proceso: filtro.procesoElegido === TODOS ? TODOS : filtro.proceso,
        modalidad: filtro.modalidad,
        sede: filtro.sede,
        turno: filtro.turno,
        aula: filtro.aula,
        estado: filtro.estado,
        pago: filtro.pago,
        revision: filtro.revision ? '1' : '',
    });

    const conConsulta = (url: string): string => {
        const parametros = new URLSearchParams();
        Object.entries(consultaActual()).forEach(([clave, valor]) => (Array.isArray(valor) ? valor.forEach((item) => parametros.append(`${clave}[]`, item)) : valor && parametros.set(clave, valor)));

        return `${url}?${parametros.toString()}`;
    };

    const aprobarRevision = (inscripcion: Inscripcion): void => {
        router.post(aprobar.url(inscripcion.id), {}, { preserveScroll: true });
    };

    return (
        <div className="space-y-6">
            <Resumen>
                <ResumenItem etiqueta="Total" valor={resumen.total} icono={Users} tono="info" detalle={`${resumen.pendientes} pendientes`} />
                <ResumenItem etiqueta="Activas" valor={resumen.activas} icono={CircleCheck} tono="exito" detalle="Con pago validado" />
                <ResumenItem etiqueta="Pago pendiente" valor={resumen.pagoPendiente} icono={CircleDollarSign} tono={resumen.pagoPendiente > 0 ? 'aviso' : 'exito'} detalle="Con saldo por cobrar" />
                <ResumenItem etiqueta="Anuladas" valor={resumen.anuladas} icono={CircleX} tono={resumen.anuladas > 0 ? 'peligro' : 'neutro'} detalle="Fuera del flujo activo" />
            </Resumen>

            {totalEnRevision > 0 && (
                <Aviso
                    tono="aviso"
                    titulo={`En revisión (${totalEnRevision})`}
                    accion={
                        <Button variant="outline" size="sm" onClick={() => cambiarFiltro({ revision: !filtro.revision }, true)}>
                            {filtro.revision ? 'Ver todas' : 'Ver solo en revisión'}
                        </Button>
                    }
                >
                    Inscripciones hechas en el portal que esperan la aprobación del personal.
                </Aviso>
            )}

            <TablaDatos
                barra={
                    <>
                        <Buscador valor={filtro.buscar} alCambiar={(buscar) => cambiarFiltro({ buscar })} placeholder="Código, DNI, apellidos o carrera" className="min-w-48 flex-1 sm:max-w-md" />
                        <FiltroLista
                            valor={procesoVisible}
                            alCambiar={(proceso) =>
                                cambiarFiltro(
                                    {
                                        proceso: proceso === '' ? TODOS : proceso,
                                        procesoElegido: proceso === '' ? TODOS : null,
                                        modalidad: '',
                                        sede: '',
                                        turno: '',
                                        aula: '',
                                    },
                                    true,
                                )
                            }
                            opciones={opciones.procesos}
                            textoTodos="Todos los procesos"
                            resaltar={false}
                            className="sm:w-44"
                        />
                        <FiltroEstado seleccionados={filtro.estado} alCambiar={(estado) => cambiarFiltro({ estado }, true)} valores={['PENDIENTE', 'ACTIVO', 'ANULADO']} />
                        <Popover>
                            <PopoverTrigger className={claseBotonFiltro(secundariosActivos > 0)}>
                                <SlidersHorizontal className={cn('size-4', secundariosActivos > 0 ? 'text-index-500' : 'text-zinc-400')} />
                                Más filtros
                                <ContadorFiltro cantidad={secundariosActivos} />
                            </PopoverTrigger>
                            <PopoverContent align="start" className="grid w-72 gap-2.5">
                                <FiltroLista
                                    valor={filtro.modalidad}
                                    alCambiar={(modalidad) => cambiarFiltro({ modalidad }, true)}
                                    opciones={opciones.modalidades}
                                    textoTodos="Todas las modalidades"
                                    className="sm:w-full"
                                />
                                <FiltroLista valor={filtro.sede} alCambiar={(sede) => cambiarFiltro({ sede }, true)} opciones={opciones.sedes} textoTodos="Todas las sedes" className="sm:w-full" />
                                <FiltroLista valor={filtro.turno} alCambiar={(turno) => cambiarFiltro({ turno, aula: '' }, true)} opciones={opciones.turnos} textoTodos="Todos los turnos" className="sm:w-full" />
                                <FiltroLista valor={filtro.aula} alCambiar={(valor) => cambiarFiltro({ aula: valor }, true)} opciones={opciones.aulas} textoTodos="Todas las aulas" className="sm:w-full" />
                                <FiltroLista valor={filtro.pago} alCambiar={(pago) => cambiarFiltro({ pago }, true)} opciones={opciones.pagos} textoTodos="Cualquier estado de pago" className="sm:w-full" />
                                {secundariosActivos > 0 && (
                                    <Button variant="ghost" size="sm" onClick={() => cambiarFiltro({ modalidad: '', sede: '', turno: '', aula: '', pago: '' }, true)}>
                                        Limpiar filtros
                                    </Button>
                                )}
                            </PopoverContent>
                        </Popover>
                        <div className="ms-auto flex flex-wrap gap-2">
                            {puede('inscripcion_exportar_excel') && (
                                <Button variant="outline" asChild>
                                    <a href={conConsulta(exportar.url())}>
                                        <Download className="size-4.5" />
                                        Excel
                                    </a>
                                </Button>
                            )}
                            <Button variant="outline" asChild>
                                <a href={conConsulta(carnets.url())}>
                                    <IdCard className="size-4.5" />
                                    Carnets
                                </a>
                            </Button>
                            {puede('inscripcion_crear') && (
                                <Button asChild>
                                    <Enlace href={crear.url()}>
                                        <Plus className="size-4.5" />
                                        Nueva inscripción
                                    </Enlace>
                                </Button>
                            )}
                        </div>
                    </>
                }
                paginado={inscripciones}
                columnas={[
                    {
                        clave: 'codigo',
                        titulo: 'Código',
                        celda: (inscripcion) => (
                            <>
                                <Enlace href={detalle.url(inscripcion.id)} className="text-index-600 dark:text-index-300 block font-mono font-medium hover:underline">
                                    {inscripcion.codigo}
                                </Enlace>
                                <span className="block text-xs text-zinc-400 tabular-nums dark:text-zinc-500">{inscripcion.fecha}</span>
                            </>
                        ),
                    },
                    {
                        clave: 'postulante',
                        titulo: 'Postulante',
                        className: 'whitespace-normal',
                        celda: (inscripcion) => (
                            <span className="flex items-center gap-3">
                                <AvatarPostulante foto={inscripcion.foto} iniciales={inscripcion.iniciales} nombre={inscripcion.postulante} />
                                <span className="min-w-0">
                                    <span className="block font-medium text-zinc-900 dark:text-white">{inscripcion.postulante}</span>
                                    <span className="block font-mono text-xs text-zinc-500 dark:text-zinc-400">DNI {inscripcion.dni}</span>
                                </span>
                            </span>
                        ),
                    },
                    {
                        clave: 'postulacion',
                        titulo: 'Postulación',
                        className: 'whitespace-normal',
                        celda: (inscripcion) => (
                            <>
                                <span className="block text-zinc-800 dark:text-zinc-100">{inscripcion.carrera ?? 'Sin carrera'}</span>
                                <span className="block text-xs text-zinc-500 dark:text-zinc-400">
                                    {inscripcion.sede} · {inscripcion.modalidad}
                                </span>
                            </>
                        ),
                    },
                    {
                        clave: 'turno',
                        titulo: 'Turno / aula',
                        celda: (inscripcion) => (
                            <>
                                <span className="block">{inscripcion.turno ?? '—'}</span>
                                <span className="block text-xs text-zinc-500 dark:text-zinc-400">{inscripcion.aula ?? 'Sin aula'}</span>
                            </>
                        ),
                    },
                    {
                        clave: 'estado',
                        titulo: 'Estado',
                        celda: (inscripcion) => (
                            <span className="flex flex-col items-start gap-1">
                                <InsigniaEstado estado={inscripcion.estado} />
                                {inscripcion.enRevision && <Badge variant="aviso">En revisión</Badge>}
                            </span>
                        ),
                    },
                    {
                        clave: 'pago',
                        titulo: 'Pago',
                        celda: (inscripcion) => <InsigniaEstado estado={inscripcion.pago} />,
                    },
                    {
                        clave: 'acciones',
                        titulo: <span className="sr-only">Acciones</span>,
                        className: 'w-14 text-end',
                        celda: (inscripcion) => {
                            const vigente = inscripcion.estado === 'ACTIVO' || inscripcion.estado === 'PENDIENTE';
                            const conSaldo = inscripcion.pago === 'PENDIENTE' || inscripcion.pago === 'PARCIAL';

                            return (
                                <MenuAcciones
                                    acciones={[
                                        {
                                            etiqueta: 'Ver detalle',
                                            icono: Eye,
                                            alSeleccionar: () => router.visit(detalle.url(inscripcion.id)),
                                        },
                                        {
                                            etiqueta: 'Continuar registro',
                                            icono: PenLine,
                                            alSeleccionar: () => (window.location.href = continuar.url(inscripcion.id)),
                                            visible: inscripcion.estado === 'PENDIENTE' && puede('inscripcion_crear'),
                                        },
                                        {
                                            etiqueta: 'Aprobar revisión',
                                            icono: ShieldCheck,
                                            alSeleccionar: () => aprobarRevision(inscripcion),
                                            visible: inscripcion.enRevision && puede('inscripcion_estado'),
                                        },
                                        {
                                            etiqueta: 'Validar pago',
                                            icono: BadgeCheck,
                                            alSeleccionar: () => router.visit(validar.url({ query: { buscar: inscripcion.codigo } })),
                                            visible: conSaldo && vigente && puede('pago_crear'),
                                        },
                                        {
                                            etiqueta: 'Editar postulación',
                                            icono: Repeat,
                                            alSeleccionar: () => router.visit(editar.url(inscripcion.id)),
                                            visible: vigente && puede('inscripcion_editar'),
                                        },
                                        {
                                            etiqueta: 'Asignar aula',
                                            icono: Building,
                                            alSeleccionar: () => setAsignando(inscripcion),
                                            visible: vigente && puede('inscripcion_editar'),
                                        },
                                        {
                                            etiqueta: 'Ficha (PDF)',
                                            icono: FileText,
                                            alSeleccionar: () => window.open(ficha.url(inscripcion.id), '_blank'),
                                        },
                                        {
                                            etiqueta: 'Carnet (PDF)',
                                            icono: IdCard,
                                            alSeleccionar: () => window.open(carnet.url(inscripcion.id), '_blank'),
                                            visible: inscripcion.estado === 'ACTIVO',
                                        },
                                        {
                                            etiqueta: 'Anular',
                                            icono: Ban,
                                            alSeleccionar: () => setAnulando(inscripcion),
                                            peligro: true,
                                            visible: (inscripcion.estado === 'ACTIVO' || inscripcion.enRevision) && puede('inscripcion_estado'),
                                        },
                                    ]}
                                />
                            );
                        },
                    },
                ]}
                filas={inscripciones.data}
                claveFila={(inscripcion) => inscripcion.id}
                vacio={<EstadoVacio icono={ClipboardList} titulo="No se encontraron inscripciones con los filtros actuales." />}
                pie={
                    <FiltroLista
                        valor={filtro.porPagina === 10 ? '' : String(filtro.porPagina)}
                        alCambiar={(valor) => cambiarFiltro({ porPagina: Number(valor) || 10 }, true)}
                        opciones={[25, 50, 100].map((cantidad) => ({
                            valor: String(cantidad),
                            nombre: `${cantidad} por página`,
                        }))}
                        textoTodos="10 por página"
                        resaltar={false}
                        className="h-8! sm:w-36"
                    />
                }
            />

            <DialogoAnular inscripcion={anulando} alCerrar={() => setAnulando(null)} />
            <DialogoAula inscripcion={asignando} alCerrar={cerrarAula} />
        </div>
    );
}

Inscripciones.layout = {
    titulo: 'Inscripciones',
    subtitulo: 'Postulantes inscritos por proceso, con su estado, pago, aula y documentos.',
    icono: 'inscripciones',
    migas: [
        { titulo: 'Inicio', href: inicio.url() },
        { titulo: 'Inscripciones', href: index.url() },
    ],
};
