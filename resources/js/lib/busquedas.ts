import type { OpcionCombobox } from '@/components/combobox';
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
