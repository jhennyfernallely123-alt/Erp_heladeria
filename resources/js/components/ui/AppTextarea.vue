<template>
    <div class="space-y-1.5">
        <label v-if="label" :for="textareaId" class="block text-xs font-semibold text-petrol-700">
            {{ label }}<span v-if="required" class="text-rose-500 ml-0.5">*</span>
        </label>
        <textarea
            :id="textareaId"
            v-model="model"
            :rows="rows"
            :placeholder="placeholder"
            :required="required"
            class="w-full rounded-xl border px-3.5 py-2.5 text-sm text-petrol-700 placeholder:text-niebla-300 focus:outline-none focus:ring-2 focus:ring-aguamarina-500 focus:border-aguamarina-500 resize-y"
            :class="error ? 'border-rose-300 bg-rose-50/40' : 'border-aguamarina-200 bg-white'"
        />
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
    placeholder: { type: String, default: '' },
    rows: { type: Number, default: 3 },
    error: { type: String, default: '' },
    required: { type: Boolean, default: false },
});

const model = defineModel({ type: String, default: '' });
const textareaId = useId();
</script>
