<template>
    <Teleport to="body">
        <div class="fixed top-4 right-4 z-[60] w-full max-w-sm space-y-2">
            <TransitionGroup
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 translate-x-4"
                leave-active-class="transition duration-150 ease-in"
                leave-to-class="opacity-0 translate-x-4"
            >
                <div
                    v-for="item in toastStore.items"
                    :key="item.id"
                    class="flex items-start gap-3 rounded-xl border bg-white p-3.5 shadow-lg"
                    :class="borders[item.type]"
                >
                    <AppIcon
                        :name="icons[item.type]"
                        :size="18"
                        class="shrink-0 mt-0.5"
                        :class="texts[item.type]"
                    />
                    <p class="text-sm text-slate-700 flex-1">{{ item.message }}</p>
                    <button
                        type="button"
                        class="text-slate-400 hover:text-slate-600"
                        aria-label="Cerrar"
                        @click="toastStore.dismiss(item.id)"
                    >
                        <AppIcon :name="X" :size="16" />
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<script setup>
import AppIcon from './AppIcon.vue';
import { useToastStore } from '../../stores/toast';
import { CheckCircle2, AlertCircle, Info, X } from 'lucide-vue-next';

const toastStore = useToastStore();

const icons = { success: CheckCircle2, error: AlertCircle, info: Info };
const texts = { success: 'text-emerald-600', error: 'text-rose-600', info: 'text-indigo-600' };
const borders = {
    success: 'border-emerald-200',
    error: 'border-rose-200',
    info: 'border-indigo-200',
};
</script>
