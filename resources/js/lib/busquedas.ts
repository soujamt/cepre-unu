import type { OpcionCombobox } from '@/components/combobox';
import { search as buscarColegio } from '@/routes/api/colegio';
import { search as buscarUbigeo } from '@/routes/api/ubigeo';

/**
 * Busca ubigeos por código o nombre (mínimo 2 caracteres) para un <Combobox buscar={...} />.
 */
export async function buscarUbigeos(texto: string): Promise<OpcionCombobox[]> {
    if (texto.trim().length < 2) {
        return [];
    }

    const respuesta = await fetch(buscarUbigeo.url({ query: { query: texto } }), { headers: { Accept: 'application/json' } });

    if (!respuesta.ok) {
        return [];
    }

    const datos = (await respuesta.json()) as { value: string; label: string }[];

    return datos.map((dato) => ({ valor: dato.value, etiqueta: dato.label }));
}

/**
 * Busca colegios habilitados por código o nombre (mínimo 2 caracteres), del tipo indicado.
 */
export async function buscarColegios(texto: string, idTipoColegio?: string): Promise<OpcionCombobox[]> {
    if (texto.trim().length < 2) {
        return [];
    }

    const consulta: Record<string, string> = { query: texto };

    if (idTipoColegio) {
        consulta.id_tipo_colegio = idTipoColegio;
    }

    const respuesta = await fetch(buscarColegio.url({ query: consulta }), { headers: { Accept: 'application/json' } });

    if (!respuesta.ok) {
        return [];
    }

    const datos = (await respuesta.json()) as { value: string; label: string }[];

    return datos.map((dato) => ({ valor: dato.value, etiqueta: dato.label }));
}
