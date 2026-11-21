import { CreditCard } from '@keyline-icons/react/fill';
import { CampoCasilla, CampoSelect, CampoTexto } from '@/components/campo';
import { FiltroLista } from '@/components/filtro-lista';
import { ListadoCatalogo } from '@/components/listado-catalogo';
import { Badge } from '@/components/ui/badge';
import { useCatalogo } from '@/hooks/use-catalogo';
import { inicio } from '@/routes';
import { destroy, estado, index, store, update } from '@/routes/reglas-pago';
import type { FiltrosCatalogo, Opcion, Paginado, Registro } from '@/types';

interface FormularioRegla {
    id_concepto_pago: string;
    id_proceso: string;
    id_modalidad_admision: string;
    monto_total: string;
    permite_cuotas: boolean;
    monto_minimo_primera_cuota: string;
    numero_maximo_cuotas: string;
    requiere_pago_completo: boolean;
    documento_requerido: string;
    vigente_desde: string;
    vigente_hasta: string;
}

interface ReglaPago {
    id: number;
    concepto: string | null;
    codigoConcepto: string | null;
    proceso: string | null;
    modalidad: string | null;
    montoTotal: number;
    permiteCuotas: boolean;
    cuotasMaximas: number;
    minimoPrimeraCuota: number;
    requierePagoCompleto: boolean;
    documentoRequerido: string | null;
    vigenteDesde: string | null;
    vigenteHasta: string | null;
    formulario: FormularioRegla;
    estado: string;
    registro: Registro | null;
}

interface PropsReglasPago {
    reglas: Paginado<ReglaPago>;
    filtros: FiltrosCatalogo & { proceso: string };
    procesosFiltro: Opcion[];
    procesos: (Opcion & { habilitado: boolean; inicio: string | null; fin: string | null })[];
    conceptos: (Opcion & { permiteCuotas: boolean })[];
    modalidades: Opcion[];
    documentos: string[];
}

const soles = (monto: number): string => `S/ ${monto.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const vacio: FormularioRegla = {
    id_concepto_pago: '',
    id_proceso: '',
    id_modalidad_admision: '',
    monto_total: '',
    permite_cuotas: false,
    monto_minimo_primera_cuota: '',
    numero_maximo_cuotas: '1',
    requiere_pago_completo: false,
    documento_requerido: '',
    vigente_desde: '',
    vigente_hasta: '',
};

export default function ReglasPago({ reglas, filtros, procesosFiltro, procesos, conceptos, modalidades, documentos }: PropsReglasPago) {
    const { formulario, control } = useCatalogo({
        vacio,
        aFormulario: (regla: ReglaPago) => regla.formulario,
        idDe: (regla) => regla.id,
        nombreDe: (regla) => `${regla.concepto ?? 'Regla'} · ${regla.proceso ?? ''}`,
        rutas: { store, update, estado, destroy },
        titulos: { nuevo: 'Nueva regla de pago', editar: 'Editar regla de pago', entidad: 'la regla de pago' },
    });

    const datos = formulario.data;
    const concepto = conceptos.find((opcion) => opcion.valor === datos.id_concepto_pago);
    const procesoElegido = procesos.find((opcion) => opcion.valor === datos.id_proceso);
    const procesosFormulario = procesos.filter((opcion) => opcion.habilitado || opcion.valor === datos.id_proceso);

    const alternarCuotas = (marcado: boolean): void => {
        formulario.setData({
            ...datos,
            permite_cuotas: marcado,
            requiere_pago_completo: marcado ? false : datos.requiere_pago_completo,
            numero_maximo_cuotas: marcado ? (Number(datos.numero_maximo_cuotas) >= 2 ? datos.numero_maximo_cuotas : '2') : '1',
            monto_minimo_primera_cuota: marcado ? datos.monto_minimo_primera_cuota : '',
        });
    };

    const alternarPagoCompleto = (marcado: boolean): void => {
        formulario.setData({
            ...datos,
            requiere_pago_completo: marcado,
            permite_cuotas: marcado ? false : datos.permite_cuotas,
            numero_maximo_cuotas: marcado ? '1' : datos.numero_maximo_cuotas,
            monto_minimo_primera_cuota: marcado ? '' : datos.monto_minimo_primera_cuota,
        });
    };

    return (
        <ListadoCatalogo
            permiso="regla_pago"
            paginado={reglas}
            filtros={filtros}
            catalogo={control}
            placeholderBusqueda="Buscar por concepto, proceso o modalidad"
            vacio={{ icono: CreditCard, titulo: 'No se encontraron reglas de pago registradas.' }}
            filtrosExtra={(cambiar, filtro) => (
                <FiltroLista valor={String(filtro.proceso ?? '')} alCambiar={(proceso) => cambiar({ proceso }, true)} opciones={procesosFiltro} textoTodos="Todos los procesos" className="sm:w-48" />
            )}
            columnas={[
                {
                    clave: 'concepto',
                    titulo: 'Concepto',
                    className: 'whitespace-normal',
                    celda: (regla) => (
                        <>
                            <span className="block font-medium text-zinc-900 dark:text-white">{regla.concepto}</span>
                            <span className="block font-mono text-xs text-zinc-500 dark:text-zinc-400">{regla.codigoConcepto}</span>
                        </>
                    ),
                },
                {
                    clave: 'contexto',
                    titulo: 'Contexto',
                    className: 'whitespace-normal',
                    celda: (regla) => (
                        <>
                            <span className="block font-medium text-zinc-800 dark:text-zinc-100">{regla.proceso}</span>
                            <Badge variant="secondary" className="mt-0.5 whitespace-normal">
                                {regla.modalidad ?? 'TODAS LAS MODALIDADES'}
                            </Badge>
                        </>
                    ),
                },
                {
                    clave: 'monto',
                    titulo: 'Monto',
                    className: 'whitespace-normal',
                    celda: (regla) => (
                        <>
                            <span className="block font-medium text-zinc-900 tabular-nums dark:text-white">{soles(regla.montoTotal)}</span>
                            {regla.requierePagoCompleto && <span className="block text-xs text-zinc-500 dark:text-zinc-400">Exige pago completo</span>}
                            {regla.documentoRequerido && <span className="block text-xs text-zinc-500 dark:text-zinc-400">Pide {regla.documentoRequerido}</span>}
                        </>
                    ),
                },
                {
                    clave: 'cuotas',
                    titulo: 'Cuotas',
                    className: 'whitespace-normal',
                    celda: (regla) =>
                        regla.permiteCuotas ? (
                            <>
                                <span className="block font-medium text-zinc-800 dark:text-zinc-100">Hasta {regla.cuotasMaximas}</span>
                                <span className="block text-xs text-zinc-500 tabular-nums dark:text-zinc-400">1ra desde {soles(regla.minimoPrimeraCuota)}</span>
                            </>
                        ) : (
                            <Badge variant="secondary">Pago único</Badge>
                        ),
                },
                {
                    clave: 'vigencia',
                    titulo: 'Vigencia',
                    celda: (regla) => (
                        <>
                            <span className="block tabular-nums">{regla.vigenteDesde}</span>
                            <span className="block text-xs text-zinc-500 tabular-nums dark:text-zinc-400">{regla.vigenteHasta ?? 'Sin fecha final'}</span>
                        </>
                    ),
                },
            ]}
            campos={
                <>
                    <CampoSelect
                        etiqueta="Concepto de pago"
                        value={datos.id_concepto_pago}
                        onChange={(evento) => {
                            const elegido = conceptos.find((opcion) => opcion.valor === evento.target.value);
                            formulario.setData({ ...datos, id_concepto_pago: evento.target.value, ...(elegido && !elegido.permiteCuotas ? { permite_cuotas: false, numero_maximo_cuotas: '1', monto_minimo_primera_cuota: '' } : {}) });
                        }}
                        error={formulario.errors.id_concepto_pago}
                        requerido
                    >
                        <option value="">Seleccionar</option>
                        {conceptos.map((opcion) => (
                            <option key={opcion.valor} value={opcion.valor}>
                                {opcion.nombre}
                            </option>
                        ))}
                    </CampoSelect>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <CampoSelect etiqueta="Proceso" value={datos.id_proceso} onChange={(evento) => formulario.setData('id_proceso', evento.target.value)} error={formulario.errors.id_proceso} requerido>
                            <option value="">Seleccionar</option>
                            {procesosFormulario.map((opcion) => (
                                <option key={opcion.valor} value={opcion.valor}>
                                    {opcion.nombre}
                                </option>
                            ))}
                        </CampoSelect>
                        <CampoSelect
                            etiqueta="Modalidad"
                            value={datos.id_modalidad_admision}
                            onChange={(evento) => formulario.setData('id_modalidad_admision', evento.target.value)}
                            error={formulario.errors.id_modalidad_admision}
                            ayuda="Vacío: aplica a todas."
                        >
                            <option value="">Todas las modalidades</option>
                            {modalidades.map((opcion) => (
                                <option key={opcion.valor} value={opcion.valor}>
                                    {opcion.nombre}
                                </option>
                            ))}
                        </CampoSelect>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <CampoTexto
                            etiqueta="Monto total (S/)"
                            inputMode="decimal"
                            value={datos.monto_total}
                            onChange={(evento) => formulario.setData('monto_total', evento.target.value.replace(/[^\d.]/g, ''))}
                            error={formulario.errors.monto_total}
                            ayuda="0 si la modalidad es gratuita."
                            requerido
                        />
                        <CampoSelect etiqueta="Documento requerido" value={datos.documento_requerido} onChange={(evento) => formulario.setData('documento_requerido', evento.target.value)} error={formulario.errors.documento_requerido}>
                            <option value="">Ninguno</option>
                            {documentos.map((documento) => (
                                <option key={documento} value={documento}>
                                    {documento}
                                </option>
                            ))}
                        </CampoSelect>
                    </div>

                    <div className="space-y-3">
                        <CampoCasilla
                            etiqueta="Permite cuotas"
                            descripcion={concepto && !concepto.permiteCuotas ? 'El concepto elegido no permite cuotas.' : 'Habilita pagos parciales hasta completar el monto.'}
                            marcado={datos.permite_cuotas}
                            alCambiar={alternarCuotas}
                            error={formulario.errors.permite_cuotas}
                        />
                        {datos.permite_cuotas && (
                            <div className="grid gap-4 sm:grid-cols-2">
                                <CampoTexto
                                    etiqueta="Número máximo de cuotas"
                                    inputMode="numeric"
                                    value={datos.numero_maximo_cuotas}
                                    onChange={(evento) => formulario.setData('numero_maximo_cuotas', evento.target.value.replace(/\D/g, ''))}
                                    error={formulario.errors.numero_maximo_cuotas}
                                    requerido
                                />
                                <CampoTexto
                                    etiqueta="Mínimo 1ra cuota (S/)"
                                    inputMode="decimal"
                                    value={datos.monto_minimo_primera_cuota}
                                    onChange={(evento) => formulario.setData('monto_minimo_primera_cuota', evento.target.value.replace(/[^\d.]/g, ''))}
                                    error={formulario.errors.monto_minimo_primera_cuota}
                                    requerido
                                />
                            </div>
                        )}
                        <CampoCasilla
                            etiqueta="Exige pago completo"
                            descripcion="El postulante debe pagar el total para poder inscribirse."
                            marcado={datos.requiere_pago_completo}
                            alCambiar={alternarPagoCompleto}
                            error={formulario.errors.requiere_pago_completo}
                        />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <CampoTexto
                            etiqueta="Vigente desde"
                            type="date"
                            min={procesoElegido?.inicio ?? undefined}
                            max={procesoElegido?.fin ?? undefined}
                            value={datos.vigente_desde}
                            onChange={(evento) => formulario.setData('vigente_desde', evento.target.value)}
                            error={formulario.errors.vigente_desde}
                            requerido
                        />
                        <CampoTexto
                            etiqueta="Vigente hasta"
                            type="date"
                            min={datos.vigente_desde || procesoElegido?.inicio || undefined}
                            max={procesoElegido?.fin ?? undefined}
                            value={datos.vigente_hasta}
                            onChange={(evento) => formulario.setData('vigente_hasta', evento.target.value)}
                            error={formulario.errors.vigente_hasta}
                            ayuda="Vacío: hasta el cierre del proceso."
                        />
                    </div>
                </>
            }
        />
    );
}

ReglasPago.layout = {
    titulo: 'Reglas de pago',
    subtitulo: 'Cuánto se paga por cada concepto en el proceso y si puede pagarse en cuotas.',
    icono: 'reglasPago',
    migas: [{ titulo: 'Inicio', href: inicio.url() }, { titulo: 'Configuración académica' }, { titulo: 'Reglas de pago', href: index.url() }],
};
