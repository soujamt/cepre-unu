import { router } from '@inertiajs/react';
import { Layers } from '@keyline-icons/react/fill';
import { CampoTexto } from '@/components/campo';
import { FiltroLista } from '@/components/filtro-lista';
import { ListadoCatalogo } from '@/components/listado-catalogo';
import type { OpcionProceso } from '@/components/selector-proceso';
import { Badge } from '@/components/ui/badge';
import { useCatalogo } from '@/hooks/use-catalogo';
import { inicio } from '@/routes';
import { asignar, destroy, estado, index, update } from '@/routes/aulas';
import type { FiltrosCatalogo, Opcion, Paginado, Registro } from '@/types';

interface Asignacion {
    id: number;
    proceso: string | null;
    aula: string | null;
    piso: string | null;
    lugar: string | null;
    turno: string | null;
    capacidad: number;
    inscritos: number;
    estado: string;
    registro: Registro | null;
}

interface PropsAulasProceso {
    asignaciones: Paginado<Asignacion>;
    filtros: FiltrosCatalogo & { proceso: string; turno: string };
    procesos: OpcionProceso[];
    turnos: Opcion[];
}

export default function AulasProceso({ asignaciones, filtros, procesos, turnos }: PropsAulasProceso) {
    const { formulario, control } = useCatalogo({
        vacio: { capacidad: '' },
        aFormulario: (asignacion: Asignacion) => ({ capacidad: String(asignacion.capacidad) }),
        idDe: (asignacion) => asignacion.id,
        nombreDe: (asignacion) => `${asignacion.aula ?? 'Aula'} · ${asignacion.turno ?? ''}`,
        rutas: { update, estado, destroy },
        titulos: { nuevo: 'Asignar aulas', editar: 'Editar aula asignada', entidad: 'la asignación' },
    });

    return (
        <ListadoCatalogo
            permiso="aula_proceso"
            paginado={asignaciones}
            filtros={filtros}
            catalogo={control}
            textoNuevo="Asignar aulas"
            alNuevo={() => router.visit(asignar.url(filtros.proceso ? { query: { proceso: filtros.proceso } } : undefined))}
            placeholderBusqueda="Buscar por aula o proceso"
            vacio={{ icono: Layers, titulo: 'No se encontraron aulas asignadas.' }}
            filtrosExtra={(cambiar, filtro) => (
                <>
                    <FiltroLista
                        valor={String(filtro.proceso ?? '')}
                        alCambiar={(proceso) => cambiar({ proceso, turno: '' }, true)}
                        opciones={procesos.map((proceso) => ({ valor: proceso.valor, nombre: proceso.nombre }))}
                        textoTodos="Todos los procesos"
                        className="sm:w-48"
                    />
                    <FiltroLista valor={String(filtro.turno ?? '')} alCambiar={(turno) => cambiar({ turno }, true)} opciones={turnos} textoTodos="Todos los turnos" className="sm:w-44" />
                </>
            )}
            columnas={[
                { clave: 'proceso', titulo: 'Proceso', className: 'font-medium text-zinc-900 dark:text-white', celda: (asignacion) => asignacion.proceso ?? '—' },
                {
                    clave: 'aula',
                    titulo: 'Aula',
                    celda: (asignacion) => (
                        <>
                            <span className="block font-medium text-zinc-800 dark:text-zinc-100">{asignacion.aula ?? '—'}</span>
                            <span className="block text-xs text-zinc-500 dark:text-zinc-400">{asignacion.piso}</span>
                        </>
                    ),
                },
                { clave: 'lugar', titulo: 'Lugar', celda: (asignacion) => (asignacion.lugar ? <Badge variant={asignacion.lugar === 'UNU' ? 'info' : 'destructive'}>{asignacion.lugar}</Badge> : '—') },
                { clave: 'turno', titulo: 'Turno', celda: (asignacion) => asignacion.turno ?? '—' },
                { clave: 'capacidad', titulo: 'Capacidad', className: 'w-24 tabular-nums', celda: (asignacion) => asignacion.capacidad },
                {
                    clave: 'inscritos',
                    titulo: 'Inscritos',
                    className: 'w-28 tabular-nums',
                    celda: (asignacion) => (
                        <span className={asignacion.inscritos >= asignacion.capacidad ? 'font-medium text-amber-600 dark:text-amber-400' : undefined}>
                            {asignacion.inscritos} / {asignacion.capacidad}
                        </span>
                    ),
                },
            ]}
            campos={
                <CampoTexto
                    etiqueta="Capacidad"
                    type="number"
                    inputMode="numeric"
                    min={1}
                    value={formulario.data.capacidad}
                    onChange={(evento) => formulario.setData('capacidad', evento.target.value)}
                    error={formulario.errors.capacidad}
                    ayuda="Cantidad máxima de postulantes en el aula para este turno."
                    requerido
                />
            }
        />
    );
}

AulasProceso.layout = {
    titulo: 'Aulas por proceso',
    subtitulo: 'Aulas asignadas a cada proceso y turno, con su capacidad e inscritos.',
    icono: 'aulasProceso',
    migas: [{ titulo: 'Inicio', href: inicio.url() }, { titulo: 'Configuración académica' }, { titulo: 'Aulas por proceso', href: index.url() }],
};
