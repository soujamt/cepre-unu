import { Banknote } from '@keyline-icons/react';
import { CampoArea, CampoCasilla, CampoTexto } from '@/components/campo';
import { ListadoCatalogo } from '@/components/listado-catalogo';
import { Badge } from '@/components/ui/badge';
import { useCatalogo } from '@/hooks/use-catalogo';
import { inicio } from '@/routes';
import { destroy, estado, index, store, update } from '@/routes/conceptos-pago';
import type { FiltrosCatalogo, Paginado, Registro } from '@/types';

interface ConceptoPago {
    id: number;
    codigo: string;
    nombre: string;
    descripcion: string | null;
    permiteCuotas: boolean;
    reglas: number;
    estado: string;
    registro: Registro | null;
}

export default function ConceptosPago({ conceptos, filtros }: { conceptos: Paginado<ConceptoPago>; filtros: FiltrosCatalogo }) {
    const { formulario, control } = useCatalogo({
        vacio: { codigo: '', nombre: '', descripcion: '', permite_cuotas: false },
        aFormulario: (concepto: ConceptoPago) => ({
            codigo: concepto.codigo,
            nombre: concepto.nombre,
            descripcion: concepto.descripcion ?? '',
            permite_cuotas: concepto.permiteCuotas,
        }),
        idDe: (concepto) => concepto.id,
        nombreDe: (concepto) => concepto.nombre,
        rutas: { store, update, estado, destroy },
        titulos: { nuevo: 'Nuevo concepto de pago', editar: 'Editar concepto de pago', entidad: 'el concepto de pago' },
    });

    return (
        <ListadoCatalogo
            permiso="concepto_pago"
            paginado={conceptos}
            filtros={filtros}
            catalogo={control}
            placeholderBusqueda="Buscar por código o nombre"
            vacio={{ icono: Banknote, titulo: 'No se encontraron conceptos de pago registrados.' }}
            columnas={[
                { clave: 'codigo', titulo: 'Código', className: 'w-28', celda: (concepto) => <span className="font-mono text-xs font-medium text-zinc-600 dark:text-zinc-300">{concepto.codigo}</span> },
                {
                    clave: 'nombre',
                    titulo: 'Nombre',
                    className: 'whitespace-normal',
                    celda: (concepto) => (
                        <>
                            <span className="block font-medium text-zinc-800 dark:text-zinc-100">{concepto.nombre}</span>
                            {concepto.descripcion && <span className="block text-xs text-zinc-500 dark:text-zinc-400">{concepto.descripcion}</span>}
                        </>
                    ),
                },
                {
                    clave: 'cuotas',
                    titulo: 'Cuotas',
                    className: 'w-28',
                    celda: (concepto) => (concepto.permiteCuotas ? <Badge variant="info">Permite</Badge> : <Badge variant="secondary">Pago único</Badge>),
                },
                { clave: 'reglas', titulo: 'Reglas', className: 'w-24 tabular-nums', celda: (concepto) => concepto.reglas },
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
                    <CampoArea
                        etiqueta="Descripción"
                        value={formulario.data.descripcion}
                        onChange={(evento) => formulario.setData('descripcion', evento.target.value)}
                        error={formulario.errors.descripcion}
                        maxLength={1000}
                    />
                    <CampoCasilla
                        etiqueta="Permite cuotas"
                        descripcion="Las reglas de pago con este concepto podrán dividirse en cuotas."
                        marcado={formulario.data.permite_cuotas}
                        alCambiar={(marcado) => formulario.setData('permite_cuotas', marcado)}
                        error={formulario.errors.permite_cuotas}
                    />
                </>
            }
        />
    );
}

ConceptosPago.layout = {
    titulo: 'Conceptos de pago',
    icono: 'conceptos',
    migas: [{ titulo: 'Inicio', href: inicio.url() }, { titulo: 'Conceptos de pago', href: index.url() }],
};
