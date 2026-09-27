<template>
    <div class="space-y-1.5">
        <label v-if="label" :for="inputId" class="block text-xs font-semibold text-slate-700">
            {{ label }}<span v-if="required" class="text-rose-500 ml-0.5">*</span>
        </label>
        <div class="relative">
            <AppIcon
                v-if="icon"
                :name="icon"
                :size="16"
                class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"
            />
            <input
                :id="inputId"
                v-model="model"
                :type="type"
                :placeholder="placeholder"
                :required="required"
                :disabled="disabled"
                class="w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-slate-50"
                :class="[
                    icon ? 'pl-9' : '',
                    error ? 'border-rose-300 bg-rose-50/40' : 'border-slate-300 bg-white',
                ]"
            />
        </div>
        <p v-if="error" class="text-xs text-rose-600 flex items-center gap-1">
            <AppIcon :name="AlertCircle" :size="13" />{{ error }}
        </p>
        <p v-else-if="hint" class="text-xs text-slate-500">{{ hint }}</p>
    </div>
</template>

<script setup>
import { useId } from 'vue';
import AppIcon from './AppIcon.vue';
import { AlertCircle } from 'lucide-vue-next';

defineProps({
    label: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    icon: { type: [Object, Function], default: null },
    type: { type: String, default: 'text' },
    error: { type: String, default: '' },
    hint: { type: String, default: '' },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
});

const model = defineModel({ type: [String, Number], default: '' });
const inputId = useId();
</script>
