/**
 * Evita el parpadeo al cambiar entre tema claro y oscuro.
 *
 * Muchos elementos tienen transiciones de color; al alternar la clase "dark"
 * cada uno animaba su fondo y texto, mostrando tonos intermedios. Mientras se
 * aplica el cambio de tema se desactivan las transiciones, se fuerza el
 * cálculo de estilos y se reactivan enseguida, ya con los colores nuevos.
 */
const raiz = document.documentElement;
let temaOscuro = raiz.classList.contains('dark');

new MutationObserver(() => {
    const esOscuro = raiz.classList.contains('dark');

    if (esOscuro === temaOscuro) {
        return;
    }

    temaOscuro = esOscuro;
    raiz.classList.add('cambiando-tema');

    // Forzar el cálculo de estilos con los colores nuevos y sin transiciones
    void window.getComputedStyle(document.body).backgroundColor;

    setTimeout(() => raiz.classList.remove('cambiando-tema'), 1);
}).observe(raiz, { attributes: true, attributeFilter: ['class'] });

/**
 * Mientras dura la migración a Inertia, wire:navigate solo puede abrir otra pantalla
 * Livewire. Si el destino ya es una pantalla de React, se abre con una carga completa.
 */
document.addEventListener('livewire:navigate', (evento) => {
    const destino = evento.detail?.url;
    const rutas = (window.rutasLivewire ?? []).map((patron) => new RegExp(patron));

    if (!destino || rutas.some((ruta) => ruta.test(destino.pathname))) {
        return;
    }

    evento.preventDefault();
    window.location.href = destino.href;
});
