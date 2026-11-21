{{-- Aplica el tema guardado antes de pintar, para que la página no parpadee al cargar. --}}
<script>
    (function () {
        const tema = localStorage.getItem('tema') || localStorage.getItem('flux.appearance') || 'system';
        const oscuro = tema === 'dark' || (tema === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);

        document.documentElement.classList.toggle('dark', oscuro);
        document.documentElement.style.colorScheme = oscuro ? 'dark' : 'light';
    })();
</script>
