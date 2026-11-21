import { useSyncExternalStore } from 'react';

export type Tema = 'light' | 'dark' | 'system';

const CLAVE = 'tema';

/** Clave que usaba la versión anterior del sistema; se lee una vez y se reemplaza. */
const CLAVE_ANTERIOR = 'flux.appearance';

const oyentes = new Set<() => void>();
let temaActual: Tema = 'system';

const prefiereOscuro = (): boolean => window.matchMedia('(prefers-color-scheme: dark)').matches;

const leerTema = (): Tema => {
    const guardado = localStorage.getItem(CLAVE) ?? localStorage.getItem(CLAVE_ANTERIOR);

    return guardado === 'light' || guardado === 'dark' ? guardado : 'system';
};

const esOscuro = (tema: Tema): boolean => tema === 'dark' || (tema === 'system' && prefiereOscuro());

/**
 * Aplica el tema sin animar los colores: mientras cambia la clase "dark" se
 * desactivan las transiciones para no mostrar tonos intermedios.
 */
const aplicarTema = (tema: Tema): void => {
    const raiz = document.documentElement;
    const oscuro = esOscuro(tema);

    if (raiz.classList.contains('dark') === oscuro) {
        return;
    }

    raiz.classList.add('cambiando-tema');
    raiz.classList.toggle('dark', oscuro);
    raiz.style.colorScheme = oscuro ? 'dark' : 'light';
    void window.getComputedStyle(document.body).backgroundColor;
    setTimeout(() => raiz.classList.remove('cambiando-tema'), 1);
};

const notificar = (): void => oyentes.forEach((oyente) => oyente());

export function iniciarTema(): void {
    temaActual = leerTema();
    aplicarTema(temaActual);

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        aplicarTema(temaActual);
        notificar();
    });
}

export function useTema(): { tema: Tema; oscuro: boolean; cambiarTema: (tema: Tema) => void; alternarTema: () => void } {
    const tema = useSyncExternalStore(
        (oyente) => {
            oyentes.add(oyente);

            return () => oyentes.delete(oyente);
        },
        () => temaActual,
        () => 'system' as Tema,
    );

    const cambiarTema = (nuevo: Tema): void => {
        temaActual = nuevo;
        localStorage.removeItem(CLAVE_ANTERIOR);

        if (nuevo === 'system') {
            localStorage.removeItem(CLAVE);
        } else {
            localStorage.setItem(CLAVE, nuevo);
        }

        aplicarTema(nuevo);
        notificar();
    };

    const oscuro = esOscuro(tema);

    return { tema, oscuro, cambiarTema, alternarTema: () => cambiarTema(oscuro ? 'light' : 'dark') };
}
