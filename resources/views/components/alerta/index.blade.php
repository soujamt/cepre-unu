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
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="closeOnBackdrop()"
        class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm"
        style="display: none"
    ></div>

    <!-- Modal -->
    <div
        x-show="show"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-90"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-90"
        @click.stop
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display: none"
    >
        <div
            class="w-full max-w-md overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-2xl dark:border-zinc-800 dark:bg-zinc-900"
        >
            <!-- Icono -->
            <div class="flex justify-center pt-8 pb-4">
                <div class="relative">
                    <!-- Success -->
                    <template x-if="type === 'success'">
                        <x-ui.icon name="check-circle" size="16" class="text-green-500" />
                    </template>

                    <!-- Error -->
                    <template x-if="type === 'error'">
                        <x-ui.icon name="close-circle" size="16" class="text-red-500" />
                    </template>

                    <!-- Warning -->
                    <template x-if="type === 'warning'">
                        <x-ui.icon name="alert-circle" size="16" class="text-yellow-500" />
                    </template>

                    <!-- Info -->
                    <template x-if="type === 'info'">
                        <x-ui.icon name="info-circle" size="16" class="text-sky-600" />
                    </template>

                    <!-- Question -->
                    <template x-if="type === 'question'">
                        <x-ui.icon name="question-circle" size="16" class="text-purple-500" />
                    </template>
                </div>
            </div>

            <!-- Contenido -->
            <div class="px-8 pb-6 text-center">
                <h3
                    x-show="title"
                    x-text="title"
                    class="mb-2 text-xl font-semibold text-zinc-900 dark:text-zinc-100"
                ></h3>
                <p
                    x-show="message"
                    x-text="message"
                    class="text-normal text-sm leading-relaxed text-zinc-600 dark:text-zinc-400"
                ></p>
            </div>

            <!-- Botones -->
            <div class="flex gap-3 px-6 pb-6" :class="{ 'justify-center': !showCancel, 'justify-end': showCancel }">
                <flux:button x-show="showCancel" @click="cancel()" variant="ghost" class="min-w-25 font-normal">
                    <span x-text="cancelText"></span>
                </flux:button>

                <!-- Success -->
                <flux:button
                    x-show="showConfirm && type === 'success'"
                    @click="confirm()"
                    variant="primary"
                    color="green"
                    class="min-w-25 font-normal"
                >
                    <span x-text="confirmText"></span>
                </flux:button>

                <!-- Error -->
                <flux:button
                    x-show="showConfirm && type === 'error'"
                    @click="confirm()"
                    variant="primary"
                    color="red"
                    class="min-w-25 font-normal"
                >
                    <span x-text="confirmText"></span>
                </flux:button>

                <!-- Warning -->
                <flux:button
                    x-show="showConfirm && type === 'warning'"
                    @click="confirm()"
                    variant="primary"
                    color="yellow"
                    class="min-w-25 font-normal"
                >
                    <span x-text="confirmText"></span>
                </flux:button>

                <!-- Info -->
                <flux:button
                    x-show="showConfirm && type === 'info'"
                    @click="confirm()"
                    variant="primary"
                    color="sky"
                    class="min-w-25 font-normal"
                >
                    <span x-text="confirmText"></span>
                </flux:button>

                <!-- Question -->
                <flux:button
                    x-show="showConfirm && type === 'question'"
                    @click="confirm()"
                    variant="primary"
                    color="purple"
                    class="min-w-25 font-normal"
                >
                    <span x-text="confirmText"></span>
                </flux:button>
            </div>
        </div>
    </div>
</div>
