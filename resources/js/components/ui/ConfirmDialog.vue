<template>
    <AppModal :open="open" :title="title" size="sm" @close="emit('cancel')">
        <p class="text-sm text-petrol-600">{{ message }}</p>

        <template #footer>
            <div class="flex justify-end gap-2">
                <AppButton variant="secondary" :label="cancelText" @click="emit('cancel')" />
                <AppButton
                    :variant="danger ? 'danger' : 'primary'"
                    :label="confirmText"
                    :loading="loading"
                    @click="emit('confirm')"
                />
            </div>
        </template>
    </AppModal>
</template>

<script setup>
import AppModal from './AppModal.vue';
import AppButton from './AppButton.vue';

defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: 'Confirmar acción' },
    message: { type: String, default: '' },
    confirmText: { type: String, default: 'Confirmar' },
    cancelText: { type: String, default: 'Cancelar' },
    danger: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['confirm', 'cancel']);
</script>
