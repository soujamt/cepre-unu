<div
    x-data="{
        // Estado
        show: false,
        type: 'info',
        title: '',
        message: '',
        confirmText: 'Aceptar',
        cancelText: 'Cancelar',
        showCancel: false,
        showConfirm: true,
        resolveCallback: null,
        confirmEvent: null, // Evento al confirmar
        cancelEvent: null, // Evento al cancelar
        confirmParams: null, // Parámetros adicionales para el evento de confirmar
        cancelParams: null, // Parámetros adicionales para el evento de cancelar

        // Mostrar alerta
        alert(options) {
            this.type = options.type || 'info';
            this.title = options.title || '';
            this.message = options.message || '';
            this.confirmText = options.confirmText || 'Aceptar';
            this.cancelText = options.cancelText || 'Cancelar';
            this.showCancel = options.showCancel || false;
            this.showConfirm = options.showConfirm !== false;
            this.confirmEvent = options.confirmEvent || null;
            this.cancelEvent = options.cancelEvent || null;
            this.confirmParams = options.confirmParams || null;
            this.cancelParams = options.cancelParams || null;
            this.show = true;

            // Retornar una promesa para manejar la respuesta
            return new Promise((resolve) => {
                this.resolveCallback = resolve;
            });
        },

        // Confirmar
        confirm() {
            this.show = false;

            // Si hay un evento definido, dispararlo
            if (this.confirmEvent) {
                // También dispatch como evento de Alpine/JS
                window.dispatchEvent(
                    new CustomEvent(this.confirmEvent, {
                        detail: this.confirmParams || {},
                    }),
                );
            }

            // Si hay callback de promesa, ejecutarlo
            if (this.resolveCallback) {
                this.resolveCallback(true);
            }
        },

        // Cancelar
        cancel() {
            this.show = false;

            // Si hay un evento definido, dispararlo
            if (this.cancelEvent) {
                // También dispatch como evento de Alpine/JS
                window.dispatchEvent(
                    new CustomEvent(this.cancelEvent, {
                        detail: this.cancelParams || {},
                    }),
                );
            }

            // Si hay callback de promesa, ejecutarlo
            if (this.resolveCallback) {
                this.resolveCallback(false);
            }
        },

        // Cerrar con backdrop
        closeOnBackdrop() {
            if (this.showCancel) {
                this.cancel();
            }
        },

        // Helpers rápidos
        success(title, message, confirmEvent = null, confirmParams = null) {
            return this.alert({
                type: 'success',
                title,
                message,
                confirmEvent,
                confirmParams,
            });
        },

        error(title, message, confirmEvent = null, confirmParams = null) {
            return this.alert({
                type: 'error',
                title,
                message,
                confirmEvent,
                confirmParams,
            });
        },

        warning(title, message, confirmEvent = null, confirmParams = null) {
            return this.alert({
                type: 'warning',
                title,
                message,
                confirmEvent,
                confirmParams,
            });
        },

        info(title, message, confirmEvent = null, confirmParams = null) {
            return this.alert({
                type: 'info',
                title,
                message,
                confirmEvent,
                confirmParams,
            });
        },

        question(title, message, confirmEvent = null, cancelEvent = null, confirmParams = null, cancelParams = null) {
            return this.alert({
                type: 'question',
                title,
                message,
                showCancel: true,
                confirmText: 'Sí',
                cancelText: 'No',
                confirmEvent,
                cancelEvent,
                confirmParams,
                cancelParams,
            });
        },
    }"
    x-on:alert.window="alert($event.detail)"
    x-on:alert-success.window="
        success($event.detail.title, $event.detail.message, $event.detail.confirmEvent, $event.detail.confirmParams)
    "
    x-on:alert-error.window="
        error($event.detail.title, $event.detail.message, $event.detail.confirmEvent, $event.detail.confirmParams)
    "
    x-on:alert-warning.window="
        warning($event.detail.title, $event.detail.message, $event.detail.confirmEvent, $event.detail.confirmParams)
    "
    x-on:alert-info.window="
        info($event.detail.title, $event.detail.message, $event.detail.confirmEvent, $event.detail.confirmParams)
    "
    x-on:alert-question.window="
        question(
            $event.detail.title,
            $event.detail.message,
            $event.detail.confirmEvent,
            $event.detail.cancelEvent,
            $event.detail.confirmParams,
            $event.detail.cancelParams,
        )
    "
    x-on:keydown.escape.window="show && cancel()"
>
    <!-- Backdrop -->
    <div
        x-show="show"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="closeOnBackdrop()"
        class="fixed inset-0 z-50 bg-zinc-950/30 backdrop-blur-[3px] dark:bg-black/60"
        style="display: none"
    ></div>

    <!-- Modal -->
    <div
        x-show="show"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-2 scale-95"
        @click.self="closeOnBackdrop()"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display: none"
        role="alertdialog"
        aria-modal="true"
        :aria-label="title"
    >
        <div
            class="relative w-full max-w-sm overflow-hidden rounded-xl border border-zinc-200/80 bg-white shadow-[0_24px_48px_-12px_rgba(0,0,0,0.25)] dark:border-white/10 dark:bg-zinc-900 dark:shadow-[0_24px_48px_-12px_rgba(0,0,0,0.75)]"
        >
            <div
                class="pointer-events-none absolute inset-x-0 top-0 h-24"
                :class="{
                    'bg-linear-to-b from-green-50 to-transparent dark:from-green-500/10': type === 'success',
                    'bg-linear-to-b from-red-50 to-transparent dark:from-red-500/10': type === 'error',
                    'bg-linear-to-b from-amber-50 to-transparent dark:from-amber-500/10': type === 'warning',
                    'bg-linear-to-b from-index-50 to-transparent dark:from-index-500/10': type === 'info' || type === 'question',
                }"
            ></div>

            <div class="relative px-6 pt-7 pb-5 text-center">
                <!-- Icono -->
                <div
                    class="mx-auto flex size-14 items-center justify-center rounded-full ring-8"
                    :class="{
                        'bg-green-100 text-green-600 ring-green-50 dark:bg-green-500/15 dark:text-green-400 dark:ring-green-500/5': type === 'success',
                        'bg-red-100 text-red-600 ring-red-50 dark:bg-red-500/15 dark:text-red-400 dark:ring-red-500/5': type === 'error',
                        'bg-amber-100 text-amber-600 ring-amber-50 dark:bg-amber-500/15 dark:text-amber-400 dark:ring-amber-500/5': type === 'warning',
                        'bg-index-100 text-index-600 ring-index-50 dark:bg-index-500/15 dark:text-index-300 dark:ring-index-500/5': type === 'info' || type === 'question',
                    }"
                >
                    <template x-if="type === 'success'">
                        <x-ui.icon name="check-circle" size="7" />
                    </template>
                    <template x-if="type === 'error'">
                        <x-ui.icon name="x-circle" size="7" />
                    </template>
                    <template x-if="type === 'warning'">
                        <x-ui.icon name="alert-triangle" size="7" />
                    </template>
                    <template x-if="type === 'info'">
                        <x-ui.icon name="info-circle" size="7" />
                    </template>
                    <template x-if="type === 'question'">
                        <x-ui.icon name="help-circle" size="7" />
                    </template>
                </div>

                <!-- Contenido -->
                <h3
                    x-show="title"
                    x-text="title"
                    class="mt-5 text-lg font-semibold tracking-tight text-zinc-900 dark:text-white"
                ></h3>
                <p
                    x-show="message"
                    x-text="message"
                    class="mt-1.5 text-sm leading-6 text-zinc-500 dark:text-zinc-400"
                ></p>
            </div>

            <!-- Botones -->
            <div
                class="relative grid gap-2 border-t border-zinc-100 bg-zinc-50/70 px-6 py-4 dark:border-white/[0.06] dark:bg-white/[0.02]"
                :class="showCancel && showConfirm ? 'grid-cols-2' : 'grid-cols-1'"
            >
                <flux:button x-show="showCancel" @click="cancel()" class="w-full">
                    <span x-text="cancelText"></span>
                </flux:button>

                <flux:button
                    x-show="showConfirm && type === 'success'"
                    @click="confirm()"
                    variant="primary"
                    color="green"
                    class="w-full"
                >
                    <span x-text="confirmText"></span>
                </flux:button>

                <flux:button
                    x-show="showConfirm && type === 'error'"
                    @click="confirm()"
                    variant="primary"
                    color="red"
                    class="w-full"
                >
                    <span x-text="confirmText"></span>
                </flux:button>

                <flux:button
                    x-show="showConfirm && type === 'warning'"
                    @click="confirm()"
                    variant="primary"
                    color="amber"
                    class="w-full"
                >
                    <span x-text="confirmText"></span>
                </flux:button>

                <flux:button
                    x-show="showConfirm && (type === 'info' || type === 'question')"
                    @click="confirm()"
                    variant="primary"
                    class="w-full"
                >
                    <span x-text="confirmText"></span>
                </flux:button>
            </div>
        </div>
    </div>
</div>
