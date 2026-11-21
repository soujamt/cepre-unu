import { router } from '@inertiajs/react';
import { BadgeCheck, ClipboardList, Search } from '@keyline-icons/react';
import { useState } from 'react';
import { Aviso } from '@/components/aviso';
import { CampoTexto } from '@/components/campo';
import { InsigniaEstado } from '@/components/insignia-estado';
import { EstadoVacio, Panel } from '@/components/panel';
import { Buscador, useFiltros } from '@/components/tabla-datos';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { inicio } from '@/routes';
import { index as pagos, validar } from '@/routes/pagos';
import { confirmar } from '@/routes/pagos/validar';

interface Obligacion {
    id: number;
    codigo: string | null;
    dni: string | null;
    postulante: string;
    concepto: string | null;
    total: number;
    pagado: number;
    saldo: number;
    estado: string;
}

interface PagoBanco {
    id: number;
    documento: string | null;
    depositante: string | null;
    operacion: string | null;
    fecha: string | null;
    monto: number;
    disponible: boolean;
}

interface PropsValidar {
    filtros: { buscar: string; obligacion: string; dni: string; operacion: string; fecha: string };
    obligaciones: Obligacion[];
    seleccionada: (Obligacion & { pagos: { id: number; operacion: string | null; fecha: string | null; monto: number }[] }) | null;
    busquedaRealizada: boolean;
    pagosEncontrados: PagoBanco[];
}

const soles = (monto: number): string => `S/ ${monto.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

function Paso({ numero, titulo, children }: { numero: number; titulo: string; children: React.ReactNode }) {
    return (
        <span className="flex items-center gap-2.5">
            <span className="flex size-6 items-center justify-center rounded-full bg-index-500 text-xs font-semibold text-white">{numero}</span>
            <span className="text-[15px] font-semibold text-zinc-900 dark:text-white">{titulo}</span>
            {children}
        </span>
    );
}

export default function Validar({ filtros, obligaciones, seleccionada, busquedaRealizada, pagosEncontrados }: PropsValidar) {
    const [filtro, cambiarFiltro] = useFiltros({ buscar: filtros.buscar });
    const [busqueda, setBusqueda] = useState({ dni: filtros.dni, operacion: filtros.operacion, fecha: filtros.fecha });
    const [elegido, setElegido] = useState<number | null>(null);
    const [enviando, setEnviando] = useState(false);

    const visitar = (consulta: Record<string, string>): void => {
        router.get(validar.url(), Object.fromEntries(Object.entries(consulta).filter(([, valor]) => valor !== '')), { preserveScroll: true });
    };

    const elegirObligacion = (obligacion: Obligacion): void => {
        setElegido(null);
        visitar({ buscar: String(filtro.buscar), obligacion: String(obligacion.id), dni: obligacion.dni ?? '', fecha: busqueda.fecha });
    };

    const buscarPagos = (evento: React.FormEvent): void => {
        evento.preventDefault();
        setElegido(null);
        visitar({ buscar: String(filtro.buscar), obligacion: filtros.obligacion, ...busqueda });
    };

    const confirmarPago = (): void => {
        if (seleccionada && elegido !== null) {
            router.post(confirmar.url(), { obligacion: seleccionada.id, detalle: elegido }, { preserveScroll: true, onStart: () => setEnviando(true), onFinish: () => setEnviando(false), onSuccess: () => setElegido(null) });
        }
    };

    return (
        <div className="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]">
            <Panel titulo={<Paso numero={1} titulo="Obligación de pago">{null}</Paso>} descripcion="Busque por código de inscripción, DNI o apellidos.">
                <div className="space-y-4">
                    <Buscador valor={String(filtro.buscar)} alCambiar={(buscar) => cambiarFiltro({ buscar })} placeholder="Código, DNI o apellidos" className="sm:max-w-none" />
                    {obligaciones.length === 0 ? (
                        <EstadoVacio icono={ClipboardList} titulo="No hay obligaciones pendientes que coincidan." />
                    ) : (
                        <ul className="space-y-2">
                            {obligaciones.map((obligacion) => {
                                const activa = seleccionada?.id === obligacion.id;

                                return (
                                    <li key={obligacion.id}>
                                        <button
                                            type="button"
                                            onClick={() => elegirObligacion(obligacion)}
                                            className={cn(
                                                'w-full rounded-xl border p-3.5 text-start transition-colors',
                                                activa ? 'border-index-300 bg-index-50/50 dark:border-index-500/40 dark:bg-index-500/[0.07]' : 'border-zinc-200/80 hover:bg-zinc-50 dark:border-white/[0.08] dark:hover:bg-white/[0.02]',
                                            )}
                                        >
                                            <span className="flex items-start justify-between gap-3">
                                                <span className="min-w-0">
                                                    <span className="block truncate text-sm font-medium text-zinc-900 dark:text-white">{obligacion.postulante || 'Sin datos del postulante'}</span>
                                                    <span className="block text-xs text-zinc-500 dark:text-zinc-400">
                                                        <span className="font-mono">{obligacion.codigo}</span> · DNI {obligacion.dni} · {obligacion.concepto}
                                                    </span>
                                                </span>
                                                <InsigniaEstado estado={obligacion.estado} />
                                            </span>
                                            <span className="mt-2 flex gap-4 text-xs text-zinc-500 tabular-nums dark:text-zinc-400">
                                                <span>Total {soles(obligacion.total)}</span>
                                                <span>Pagado {soles(obligacion.pagado)}</span>
                                                <span className="font-medium text-zinc-800 dark:text-zinc-100">Saldo {soles(obligacion.saldo)}</span>
                                            </span>
                                        </button>
                                    </li>
                                );
                            })}
                        </ul>
                    )}
                </div>
            </Panel>

            <div className="space-y-5 xl:sticky xl:top-0">
                <Panel titulo={<Paso numero={2} titulo="Pago del banco">{null}</Paso>} descripcion="Busque el pago por DNI, número de operación y fecha.">
                    {seleccionada === null ? (
                        <Aviso tono="info">Seleccione primero una obligación de pago.</Aviso>
                    ) : (
                        <div className="space-y-5">
                            <div className="flex flex-wrap items-center gap-2 rounded-xl bg-zinc-50 px-3.5 py-2.5 text-sm dark:bg-white/[0.03]">
                                <Badge variant="default" className="font-mono">
                                    {seleccionada.codigo}
                                </Badge>
                                <span className="font-medium text-zinc-800 dark:text-zinc-100">{seleccionada.postulante}</span>
                                <span className="ms-auto text-zinc-500 tabular-nums dark:text-zinc-400">Saldo {soles(seleccionada.saldo)}</span>
                            </div>

                            <form onSubmit={buscarPagos} className="grid gap-3 sm:grid-cols-[1fr_1fr_1fr_auto]">
                                <CampoTexto etiqueta="DNI" inputMode="numeric" value={busqueda.dni} onChange={(evento) => setBusqueda({ ...busqueda, dni: evento.target.value.replace(/\D/g, '') })} requerido />
                                <CampoTexto etiqueta="N° de operación" inputMode="numeric" value={busqueda.operacion} onChange={(evento) => setBusqueda({ ...busqueda, operacion: evento.target.value.replace(/\D/g, '') })} requerido />
                                <CampoTexto etiqueta="Fecha de pago" type="date" value={busqueda.fecha} onChange={(evento) => setBusqueda({ ...busqueda, fecha: evento.target.value })} requerido />
                                <Button type="submit" size="lg" disabled={!busqueda.dni || !busqueda.operacion || !busqueda.fecha}>
                                    <Search className="size-4.5" />
                                    Buscar
                                </Button>
                            </form>

                            {busquedaRealizada &&
                                (pagosEncontrados.length === 0 ? (
                                    <Aviso tono="aviso" titulo="No se encontraron pagos">
                                        Revise el DNI, el número de operación y la fecha del voucher.
                                    </Aviso>
                                ) : (
                                    <div className="space-y-2">
                                        {pagosEncontrados.map((pago) => (
                                            <label
                                                key={pago.id}
                                                className={cn(
                                                    'flex cursor-pointer items-center gap-3 rounded-xl border p-3.5 transition-colors',
                                                    elegido === pago.id ? 'border-index-300 bg-index-50/50 dark:border-index-500/40 dark:bg-index-500/[0.07]' : 'border-zinc-200/80 dark:border-white/[0.08]',
                                                    !pago.disponible && 'cursor-not-allowed opacity-60',
                                                )}
                                            >
                                                <input type="radio" name="pago" className="size-4 accent-index-500" disabled={!pago.disponible} checked={elegido === pago.id} onChange={() => setElegido(pago.id)} />
                                                <span className="min-w-0 flex-1">
                                                    <span className="block text-sm font-medium text-zinc-900 dark:text-white">{pago.depositante ?? 'Sin nombre'}</span>
                                                    <span className="block text-xs text-zinc-500 dark:text-zinc-400">
                                                        DNI {pago.documento} · Op. <span className="font-mono">{pago.operacion}</span> · {pago.fecha}
                                                    </span>
                                                </span>
                                                <span className="text-sm font-semibold text-zinc-900 tabular-nums dark:text-white">{soles(pago.monto)}</span>
                                                {!pago.disponible && <Badge variant="secondary">Ya usado</Badge>}
                                            </label>
                                        ))}
                                    </div>
                                ))}

                            <div className="flex justify-end">
                                <Button onClick={confirmarPago} disabled={elegido === null || enviando}>
                                    <BadgeCheck className="size-4.5" />
                                    {enviando ? 'Validando…' : 'Validar y asociar pago'}
                                </Button>
                            </div>
                        </div>
                    )}
                </Panel>

                {seleccionada && seleccionada.pagos.length > 0 && (
                    <Panel titulo="Pagos ya asociados">
                        <ul className="divide-y divide-zinc-100 text-sm dark:divide-white/[0.05]">
                            {seleccionada.pagos.map((pago) => (
                                <li key={pago.id} className="flex items-center justify-between py-2">
                                    <span className="text-zinc-600 dark:text-zinc-300">
                                        Op. <span className="font-mono">{pago.operacion}</span> · {pago.fecha}
                                    </span>
                                    <span className="font-medium tabular-nums">{soles(pago.monto)}</span>
                                </li>
                            ))}
                        </ul>
                    </Panel>
                )}
            </div>
        </div>
    );
}

Validar.layout = {
    titulo: 'Validar pagos',
    subtitulo: 'Asocie a mano un pago del banco a la obligación de una inscripción.',
    icono: 'validarPagos',
    migas: [{ titulo: 'Inicio', href: inicio.url() }, { titulo: 'Inscripciones' }, { titulo: 'Pagos', href: pagos.url() }, { titulo: 'Validar' }],
};
