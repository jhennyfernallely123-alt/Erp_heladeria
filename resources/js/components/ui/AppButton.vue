<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        class="inline-flex items-center justify-center gap-2 font-semibold rounded-xl transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed active:scale-[0.98]"
        :class="[sizes[size], tones[tone][variant], focusTones[tone]]"
    >
        <template v-if="loading">
            <svg class="animate-spin h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
            </svg>
            <span>{{ loadingText || label }}</span>
        </template>
        <template v-else>
            <AppIcon v-if="icon" :name="icon" :size="iconSize" />
            <span v-if="label">{{ label }}</span>
        </template>
    </button>
</template>

<script setup>
import AppIcon from './AppIcon.vue';

defineProps({
    label: { type: String, default: '' },
    icon: { type: [Object, Function], default: null },
    iconSize: { type: Number, default: 16 },
    variant: { type: String, default: 'primary' },
    tone: { type: String, default: 'indigo' },
    size: { type: String, default: 'md' },
    type: { type: String, default: 'button' },
    disabled: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
    loadingText: { type: String, default: '' },
});

const sizes = {
    sm: 'px-3 py-1.5 text-xs',
    md: 'px-4 py-2.5 text-sm',
};

// `tone` elige la paleta y `variant` la forma. Separarlos permite que el login
// use el verde de la marca sin duplicar el componente.
const tones = {
    indigo: {
        primary: 'bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm',
        secondary: 'bg-white text-slate-700 border border-slate-300 hover:bg-slate-50',
        ghost: 'text-slate-600 hover:bg-slate-100',
        danger: 'bg-rose-600 text-white hover:bg-rose-700',
    },
    aguamarina: {
        primary: 'bg-aguamarina-500 text-white hover:bg-aguamarina-600 shadow-sm shadow-aguamarina-600/20',
        secondary: 'bg-white text-slate-700 border border-slate-300 hover:bg-slate-50',
        ghost: 'text-petrol-700 hover:bg-aguamarina-50',
        danger: 'bg-rose-600 text-white hover:bg-rose-700',
    },
};

const focusTones = {
    indigo: 'focus:ring-indigo-500',
    aguamarina: 'focus:ring-aguamarina-500',
};
</script>
