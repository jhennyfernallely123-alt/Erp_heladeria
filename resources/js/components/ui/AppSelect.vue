<template>
    <div class="space-y-1.5">
        <label v-if="label" :for="selectId" class="block text-xs font-semibold text-petrol-700">
            {{ label }}<span v-if="required" class="text-rose-500 ml-0.5">*</span>
        </label>
        <select
            :id="selectId"
            v-model="model"
            :disabled="disabled"
            class="w-full rounded-xl border px-3.5 py-2.5 text-sm text-petrol-700 focus:outline-none focus:ring-2 focus:ring-aguamarina-500 focus:border-aguamarina-500 disabled:bg-aguamarina-50"
            :class="error ? 'border-rose-300 bg-rose-50/40' : 'border-aguamarina-200 bg-white'"
        >
            <slot />
        </select>
        <p v-if="error" class="text-xs text-rose-600 flex items-center gap-1">
            <AppIcon :name="AlertCircle" :size="13" />{{ error }}
        </p>
    </div>
</template>

<script setup>
import { useId } from 'vue';
import AppIcon from './AppIcon.vue';
import { AlertCircle } from 'lucide-vue-next';

defineProps({
    label: { type: String, default: '' },
    error: { type: String, default: '' },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
});

const model = defineModel({ type: [String, Number], default: null });
const selectId = useId();
</script>
