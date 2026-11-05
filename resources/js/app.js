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
