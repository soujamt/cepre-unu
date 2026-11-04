<nav {{ $attributes->merge(['class' => 'hidden sm:flex items-center text-xs text-zinc-500 dark:text-zinc-400']) }} aria-label="Ruta de navegación">
    {{ $slot }}
</nav>
