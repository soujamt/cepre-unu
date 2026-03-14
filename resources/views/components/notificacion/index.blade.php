<div
    x-data="{
        // Opciones
        autoClose: true,
        autoCloseDelay: 5000,
        maxNotifications: 5,

        // Helpers
        notifications: [],
        nextId: 1,

        // Clases de transición (posición fija: bottom-center)
        transitionClasses: {
            'x-transition:enter-start'() {
                return 'opacity-0 translate-y-12 scale-90';
            },
            'x-transition:leave-end'() {
                return 'opacity-0 translate-y-12 scale-90';
            },
        },

        // Dispara una notificación
        toast(message, type) {
            const id = this.nextId++;

            // Limitar número de notificaciones (estricto, incluso en ráfagas)
            while (this.notifications.length >= this.maxNotifications) {
                const oldest = this.notifications.shift();
                if (oldest?.timeoutId) clearTimeout(oldest.timeoutId);
            }

            this.notifications.push({ id, message, type, visible: false, timeoutId: null, closing: false });

            // Pequeño delay para permitir la animación de entrada
            setTimeout(() => {
                const index = this.notifications.findIndex((n) => n.id === id);
                if (index > -1) {
                    this.notifications[index].visible = true;
                }
            }, 50);

            if (this.autoClose) {
                const timeoutId = setTimeout(() => this.dismissNotification(id), this.autoCloseDelay);
                const notification = this.notifications[this.notifications.length - 1];
                if (notification) notification.timeoutId = timeoutId;
            }
        },

        // Pausar auto-close al hacer hover
        pauseAutoClose(id) {
            const notification = this.notifications.find((n) => n.id === id);
            if (notification && notification.timeoutId) {
                clearTimeout(notification.timeoutId);
                notification.timeoutId = null;
            }
        },

        // Reanudar auto-close al quitar hover
        resumeAutoClose(id) {
            const notification = this.notifications.find((n) => n.id === id);
            if (notification && this.autoClose && notification.visible && !notification.closing) {
                if (notification.timeoutId) clearTimeout(notification.timeoutId);
                notification.timeoutId = setTimeout(() => this.dismissNotification(id), this.autoCloseDelay);
            }
        },

        // Descartar una notificación
        dismissNotification(id) {
            const index = this.notifications.findIndex((n) => n.id === id);

            if (index > -1) {
                if (this.notifications[index].closing) return;

                if (this.notifications[index].timeoutId) {
                    clearTimeout(this.notifications[index].timeoutId);
                    this.notifications[index].timeoutId = null;
                }

                this.notifications[index].closing = true;
                this.notifications[index].visible = false;

                // Esperar a que termine la transición de salida antes de borrar del array
                setTimeout(() => {
                    const idx = this.notifications.findIndex((n) => n.id === id);
                    if (idx > -1) this.notifications.splice(idx, 1);
                }, 400);
            }
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
            }, 400);
        },
    }"
    x-on:toast.window="toast($event.detail.message, $event.detail.type)"
>
    <div
        x-cloak
        x-show="notifications.length > 0"
        role="region"
        aria-label="Notifications"
        class="fixed bottom-4 left-1/2 z-50 flex w-[calc(100vw-2rem)] max-w-80 -translate-x-1/2 flex-col gap-2 transition-all duration-300"
    >
        <template x-for="notification in notifications" :key="notification.id">
            <div
                x-show="notification.visible"
                x-bind="transitionClasses"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-end="opacity-100 translate-x-0 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-x-0 translate-y-0 scale-100"
                @mouseenter="pauseAutoClose(notification.id)"
                @mouseleave="resumeAutoClose(notification.id)"
                class="flex transform-gpu cursor-pointer items-center justify-between gap-2 rounded-xl border border-zinc-200 bg-white px-3 py-4 text-zinc-800 shadow-[0_8px_30px_rgb(0,0,0,0.12)] transition-all duration-200 ease-out hover:shadow-[0_12px_40px_rgb(0,0,0,0.18)] dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-100 dark:shadow-[0_8px_30px_rgb(0,0,0,0.4)] dark:hover:shadow-[0_12px_40px_rgb(0,0,0,0.5)]"
                role="alert"
                :aria-live="notification.type === 'error' ? 'assertive' : 'polite'"
            >
                <template x-if="notification.type !== 'neutral'">
                    <div class="flex size-6 flex-none items-center justify-center rounded-full">
                        <template x-if="notification.type === 'success'">
                            <i class="ki-solid ki-check-squared text-xl text-green-500"></i>
                        </template>

                        <template x-if="notification.type === 'error'">
                            <i class="ki-solid ki-cross-square text-xl text-rose-500"></i>
                        </template>

                        <template x-if="notification.type === 'warning'">
                            <i class="ki-solid ki-information-1 text-xl text-yellow-500"></i>
                        </template>

                        <template x-if="notification.type === 'info'">
                            <i class="ki-solid ki-information-2 text-xl text-sky-500"></i>
                        </template>
                    </div>
                </template>

                <div class="flex-1 text-left">
                    <div
                        x-text="notification.message"
                        class="text-sm font-normal text-zinc-800 dark:text-zinc-200"
                    ></div>
                </div>

                <flux:button
                    @click="dismissNotification(notification.id)"
                    size="sm"
                    variant="subtle"
                    icon="x-mark"
                    class="flex-none! cursor-pointer"
                    square
                />
            </div>
        </template>

        <div x-show="notifications.filter((n) => n.visible).length > 1" x-transition class="flex justify-center">
            <flux:button @click="dismissAll()" size="sm" class="font-normal"> Cerrar todo </flux:button>
        </div>
    </div>
</div>
