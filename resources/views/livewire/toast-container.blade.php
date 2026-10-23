<div 
    x-data="toastManager" 
    x-init="initToasts(@entangle('toasts'))"
    class="fixed top-4 right-4 z-[9999] flex flex-col gap-3 max-w-md w-full pointer-events-none"
    style="pointer-events: none;"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div 
            x-show="toast.visible"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="translate-x-full opacity-0"
            x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="translate-x-0 opacity-100"
            x-transition:leave-end="translate-x-full opacity-0"
            @click="toast.permanent = false"
            class="pointer-events-auto"
        >
            <div 
                :class="{
                    'border-l-green-500': toast.type === 'success',
                    'border-l-red-500': toast.type === 'error',
                    'border-l-amber-500': toast.type === 'warning',
                    'border-l-blue-500': toast.type === 'info',
                }"
                class="flex items-start gap-3 bg-white rounded-lg shadow-lg border-l-4 p-4 border border-gray-100"
            >
                <!-- Icon -->
                <div 
                    :class="{
                        'bg-green-50 text-green-500': toast.type === 'success',
                        'bg-red-50 text-red-500': toast.type === 'error',
                        'bg-amber-50 text-amber-500': toast.type === 'warning',
                        'bg-blue-50 text-blue-500': toast.type === 'info',
                    }"
                    class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0"
                >
                    <!-- Success Icon -->
                    <template x-if="toast.type === 'success'">
                        <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </template>
                    
                    <!-- Error Icon -->
                    <template x-if="toast.type === 'error'">
                        <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </template>
                    
                    <!-- Warning Icon -->
                    <template x-if="toast.type === 'warning'">
                        <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </template>
                    
                    <!-- Info Icon -->
                    <template x-if="toast.type === 'info'">
                        <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                    </template>
                </div>

                <!-- Content -->
                <div class="flex-1 min-w-0">
                    <p 
                        :class="{
                            'text-green-700': toast.type === 'success',
                            'text-red-700': toast.type === 'error',
                            'text-amber-700': toast.type === 'warning',
                            'text-blue-700': toast.type === 'info',
                        }"
                        class="text-sm font-semibold"
                        x-text="toast.title"
                    ></p>
                    <p 
                        x-show="toast.message"
                        class="text-sm text-gray-600 mt-0.5"
                        x-text="toast.message"
                    ></p>
                </div>

                <!-- Close Button -->
                <button 
                    type="button"
                    @click="dismissToast(toast.id)"
                    class="text-gray-400 hover:text-gray-600 transition-colors flex-shrink-0"
                >
                    <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>
    </template>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('toastManager', () => ({
        toasts: [],
        
        initToasts(toasts) {
            if (toasts && toasts.length > 0) {
                this.toasts = toasts.map(t => ({
                    ...t,
                    visible: true,
                    permanent: false,
                }));
                
                // Auto-dismiss after duration
                this.toasts.forEach(toast => {
                    if (toast.duration > 0) {
                        setTimeout(() => {
                            if (!toast.permanent) {
                                this.dismissToast(toast.id);
                            }
                        }, toast.duration);
                    }
                });
            }
        },
        
        dismissToast(id) {
            const toast = this.toasts.find(t => t.id === id);
            if (toast) {
                toast.visible = false;
                setTimeout(() => {
                    this.toasts = this.toasts.filter(t => t.id !== id);
                    this.$wire.dismissToast(id);
                }, 200);
            }
        }
    }));
});
</script>
