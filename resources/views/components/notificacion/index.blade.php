<div
    x-data="{
        // Opciones
        autoClose: true,
        autoCloseDelay: 5000,
        maxNotifications: 5,

        // Estado
        notifications: [],
        nextId: 1,

        titulos: {
            success: 'Listo',
            error: 'Error',
            warning: 'Atención',
            info: 'Información',
            neutral: 'Aviso',
        },

        // Dispara una notificación
        toast(message, type) {
            const id = this.nextId++;

            // Limitar número de notificaciones (estricto, incluso en ráfagas)
            while (this.notifications.length >= this.maxNotifications) {
                const oldest = this.notifications.shift();
                if (oldest?.timeoutId) clearTimeout(oldest.timeoutId);
            }

            this.notifications.push({
                id,
                message,
                type: type || 'neutral',
                visible: false,
                closing: false,
                paused: false,
                timeoutId: null,
                remaining: this.autoCloseDelay,
                startedAt: null,
            });

            // Pequeño delay para permitir la animación de entrada
            setTimeout(() => {
                const notification = this.notifications.find((n) => n.id === id);
                if (notification) {
                    notification.visible = true;
                    this.startTimer(notification);
                }
            }, 50);
        },

        startTimer(notification) {
            if (! this.autoClose) return;

            notification.startedAt = Date.now();
            notification.timeoutId = setTimeout(() => this.dismissNotification(notification.id), notification.remaining);
        },

        // Pausar auto-close al hacer hover (conserva el tiempo restante)
        pauseAutoClose(id) {
            const notification = this.notifications.find((n) => n.id === id);
            if (! notification || ! notification.timeoutId) return;

            clearTimeout(notification.timeoutId);
            notification.timeoutId = null;
            notification.remaining = Math.max(0, notification.remaining - (Date.now() - notification.startedAt));
            notification.paused = true;
        },

        // Reanudar auto-close al quitar hover
        resumeAutoClose(id) {
            const notification = this.notifications.find((n) => n.id === id);
            if (! notification || ! notification.visible || notification.closing || ! notification.paused) return;

            notification.paused = false;
            this.startTimer(notification);
        },

        // Descartar una notificación
        dismissNotification(id) {
            const notification = this.notifications.find((n) => n.id === id);
            if (! notification || notification.closing) return;

            if (notification.timeoutId) {
                clearTimeout(notification.timeoutId);
                notification.timeoutId = null;
            }

            notification.closing = true;
            notification.visible = false;

            // Esperar a que termine la transición de salida antes de borrar del array
            setTimeout(() => {
                const idx = this.notifications.findIndex((n) => n.id === id);
                if (idx > -1) this.notifications.splice(idx, 1);
            }, 300);
        },

        // Cerrar todas las notificaciones
        dismissAll() {
            this.notifications.forEach((n) => {
                if (n.timeoutId) clearTimeout(n.timeoutId);
                n.timeoutId = null;
                n.closing = true;
                n.visible = false;
            });
            setTimeout(() => {
                this.notifications = [];
            }, 300);
        },
    }"
    x-on:toast.window="toast($event.detail.message, $event.detail.type)"
>
    <div
        x-cloak
        x-show="notifications.length > 0"
        role="region"
        aria-label="Notificaciones"
        class="fixed bottom-4 left-1/2 z-[60] flex w-[calc(100vw-2rem)] max-w-sm -translate-x-1/2 flex-col gap-2 sm:right-5 sm:bottom-5 sm:left-auto sm:translate-x-0"
    >
        <template x-for="notification in notifications" :key="notification.id">
            <div
                x-show="notification.visible"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-3 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                @mouseenter="pauseAutoClose(notification.id)"
                @mouseleave="resumeAutoClose(notification.id)"
                class="relative overflow-hidden rounded-xl border border-zinc-200/80 bg-white shadow-pop dark:border-white/10 dark:bg-zinc-900 dark:shadow-[0_16px_40px_-12px_rgba(0,0,0,0.7)]"
                role="alert"
                :aria-live="notification.type === 'error' ? 'assertive' : 'polite'"
            >
                <div class="flex items-start gap-3 p-3.5 pe-2.5">
                    <div
                        class="flex size-8 flex-none items-center justify-center rounded-full"
                        :class="{
                            'bg-green-50 text-green-600 dark:bg-green-500/10 dark:text-green-400': notification.type === 'success',
                            'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400': notification.type === 'error',
                            'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400': notification.type === 'warning',
                            'bg-index-50 text-index-600 dark:bg-index-500/10 dark:text-index-300': notification.type === 'info',
                            'bg-zinc-100 text-zinc-500 dark:bg-white/5 dark:text-zinc-400': notification.type === 'neutral',
                        }"
                    >
                        <template x-if="notification.type === 'success'">
                            <x-ui.icon name="check-circle" size="5" />
                        </template>
                        <template x-if="notification.type === 'error'">
                            <x-ui.icon name="x-circle" size="5" />
                        </template>
                        <template x-if="notification.type === 'warning'">
                            <x-ui.icon name="alert-triangle" size="5" />
                        </template>
                        <template x-if="notification.type === 'info'">
                            <x-ui.icon name="info-circle" size="5" />
                        </template>
                        <template x-if="notification.type === 'neutral'">
                            <x-ui.icon name="bell" size="5" />
                        </template>
                    </div>

                    <div class="min-w-0 flex-1 pt-0.5">
                        <p
                            class="text-sm font-semibold text-zinc-900 dark:text-white"
                            x-text="titulos[notification.type] ?? titulos.neutral"
                        ></p>
                        <p
                            class="mt-0.5 text-sm leading-5 text-zinc-600 dark:text-zinc-400"
                            x-text="notification.message"
                        ></p>
                    </div>

                    <button
                        type="button"
                        @click="dismissNotification(notification.id)"
                        class="flex size-7 flex-none items-center justify-center rounded-md text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-600 dark:text-zinc-500 dark:hover:bg-white/5 dark:hover:text-zinc-200"
                        aria-label="Cerrar notificación"
                    >
                        <x-ui.icon name="x" size="4" />
                    </button>
                </div>

                <div class="h-0.5 w-full bg-zinc-100 dark:bg-white/5" x-show="autoClose">
                    <div
                        class="ui-toast-progress h-full"
                        :class="{
                            'bg-green-500': notification.type === 'success',
                            'bg-red-500': notification.type === 'error',
                            'bg-amber-500': notification.type === 'warning',
                            'bg-index-500': notification.type === 'info',
                            'bg-zinc-400': notification.type === 'neutral',
                        }"
                        :style="`animation-duration: ${autoCloseDelay}ms; animation-play-state: ${notification.paused || ! notification.visible ? 'paused' : 'running'}`"
                    ></div>
                </div>
            </div>
        </template>

        <div
            x-show="notifications.filter((n) => n.visible).length > 1"
            x-transition
            class="flex justify-center sm:justify-end"
        >
            <button
                type="button"
                @click="dismissAll()"
                class="rounded-full border border-zinc-200/80 bg-white px-3 py-1 text-xs font-medium text-zinc-600 shadow-card transition-colors hover:text-zinc-900 dark:border-white/10 dark:bg-zinc-900 dark:text-zinc-400 dark:hover:text-white"
            >
                Cerrar todo
            </button>
        </div>
    </div>
</div>
