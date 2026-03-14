<div
    id="sidebar-container"
    class="fixed inset-y-0 left-0 z-40 flex h-full -translate-x-full transform transition-transform duration-300 ease-in-out md:static md:inset-auto md:translate-x-0 md:transform-none"
    :class="sidebarOpen ? 'translate-x-0!' : ''"
>
    {{ $rail ?? '' }} {{ $slot }}
</div>
