<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0"
            leave-active-class="transition duration-100 ease-in"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-900/50 p-4 sm:p-6"
                @click.self="emit('close')"
            >
                <div class="w-full bg-white rounded-2xl shadow-2xl my-8" :class="sizes[size]">
                    <div class="flex items-start justify-between gap-4 px-6 pt-5 pb-4 border-b border-slate-100">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">{{ title }}</h2>
                            <p v-if="subtitle" class="text-xs text-slate-500 mt-0.5">{{ subtitle }}</p>
                        </div>
                        <button
                            type="button"
                            class="p-1.5 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition"
                            aria-label="Cerrar"
                            @click="emit('close')"
                        >
                            <AppIcon :name="X" :size="18" />
                        </button>
                    </div>
                    <div class="px-6 py-5">
                        <slot />
                    </div>
                    <div
                        v-if="$slots.footer"
                        class="px-6 py-4 bg-slate-50 rounded-b-2xl border-t border-slate-100"
                    >
                        <slot name="footer" />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { watch, onUnmounted, onMounted } from 'vue';
import AppIcon from './AppIcon.vue';
import { X } from 'lucide-vue-next';

const props = defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: '' },
    subtitle: { type: String, default: '' },
    size: { type: String, default: 'md' },
});

const emit = defineEmits(['close']);

const sizes = {
    sm: 'max-w-sm',
    md: 'max-w-lg',
    lg: 'max-w-2xl',
    xl: 'max-w-4xl',
};

const setScroll = (value) => {
    document.body.style.overflow = value;
};

const onKeydown = (event) => {
    if (event.key === 'Escape' && props.open) {
        emit('close');
    }
};

watch(
    () => props.open,
    (isOpen) => setScroll(isOpen ? 'hidden' : '')
);

onMounted(() => window.addEventListener('keydown', onKeydown));

onUnmounted(() => {
    window.removeEventListener('keydown', onKeydown);
    setScroll('');
});
</script>
