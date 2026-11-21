import { Clock } from '@keyline-icons/react';
import { CampoTexto } from '@/components/campo';
import { ListadoCatalogo } from '@/components/listado-catalogo';
import { useCatalogo } from '@/hooks/use-catalogo';
import { inicio } from '@/routes';
import { destroy, estado, index, store, update } from '@/routes/turnos';
import type { FiltrosCatalogo, Paginado, Registro } from '@/types';

interface Turno {
    id: number;
    codigo: string;
    nombre: string;
    /** Nombre con el horario, por ejemplo "MAÑANA (07:00 am - 12:00 pm)". */
    descripcion: string;
    horaInicio: string;
    horaFin: string;
    aulas: number;
    estado: string;
    registro: Registro | null;
}

export default function Turnos({ turnos, filtros }: { turnos: Paginado<Turno>; filtros: FiltrosCatalogo }) {
    const { formulario, control } = useCatalogo({
        vacio: { codigo: '', nombre: '', hora_inicio: '', hora_fin: '' },
        aFormulario: (turno: Turno) => ({ codigo: turno.codigo, nombre: turno.nombre, hora_inicio: turno.horaInicio, hora_fin: turno.horaFin }),
        idDe: (turno) => turno.id,
        nombreDe: (turno) => turno.nombre,
        rutas: { store, update, estado, destroy },
        titulos: { nuevo: 'Nuevo turno', editar: 'Editar turno', entidad: 'el turno' },
    });

    return (
        <ListadoCatalogo
            permiso="turno"
            paginado={turnos}
            filtros={filtros}
            catalogo={control}
            placeholderBusqueda="Buscar por código o nombre"
            vacio={{ icono: Clock, titulo: 'No se encontraron turnos registrados.' }}
            columnas={[
                { clave: 'codigo', titulo: 'Código', className: 'w-28', celda: (turno) => <span className="font-mono text-xs font-medium text-zinc-600 dark:text-zinc-300">{turno.codigo}</span> },
                { clave: 'nombre', titulo: 'Nombre', className: 'whitespace-normal font-medium text-zinc-800 dark:text-zinc-100', celda: (turno) => turno.descripcion },
                { clave: 'aulas', titulo: 'Aulas', className: 'w-24 tabular-nums', celda: (turno) => turno.aulas },
            ]}
            campos={
                <>
                    <CampoTexto
                        etiqueta="Código"
                        value={formulario.data.codigo}
                        onChange={(evento) => formulario.setData('codigo', evento.target.value)}
                        error={formulario.errors.codigo}
                        maxLength={20}
                        requerido
                    />
                    <CampoTexto
                        etiqueta="Nombre"
                        value={formulario.data.nombre}
                        onChange={(evento) => formulario.setData('nombre', evento.target.value)}
                        error={formulario.errors.nombre}
                        requerido
                    />
                    <div className="grid gap-4 sm:grid-cols-2">
                        <CampoTexto
                            etiqueta="Hora de inicio"
                            type="time"
                            value={formulario.data.hora_inicio}
                            onChange={(evento) => formulario.setData('hora_inicio', evento.target.value)}
                            error={formulario.errors.hora_inicio}
                        />
                        <CampoTexto
                            etiqueta="Hora de fin"
                            type="time"
                            value={formulario.data.hora_fin}
                            onChange={(evento) => formulario.setData('hora_fin', evento.target.value)}
                            error={formulario.errors.hora_fin}
                        />
                    </div>
                </>
            }
        />
    );
}

Turnos.layout = {
    titulo: 'Turnos',
    icono: 'turnos',
    migas: [{ titulo: 'Inicio', href: inicio.url() }, { titulo: 'Turnos', href: index.url() }],
};
