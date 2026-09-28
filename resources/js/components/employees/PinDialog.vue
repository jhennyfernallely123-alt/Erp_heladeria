<template>
    <AppModal
        :open="Boolean(employee)"
        :title="employee?.name ?? ''"
        subtitle="Ingresá tu PIN de 4 dígitos"
        size="sm"
        @close="close"
    >
        <div class="space-y-4">
            <p class="text-sm text-niebla-400 text-center">
                Entrando como <strong class="text-petrol-800">{{ employee?.name }}</strong>
            </p>

            <input
                v-model="pin"
                type="password"
                inputmode="numeric"
                maxlength="4"
                placeholder="••••"
                class="w-full text-center text-3xl tracking-[0.6em] rounded-xl border border-aguamarina-200 px-3 py-3 text-petrol-800 focus:outline-none focus:ring-2 focus:ring-aguamarina-500"
                @keyup.enter="submit"
            />

            <p v-if="store.pinError" class="text-xs text-rose-600 text-center flex items-center justify-center gap-1">
                <AppIcon :name="AlertCircle" :size="13" />
                {{ store.pinError }}
            </p>

            <p v-else class="text-[11px] text-niebla-300 text-center">
                Tu PIN es personal. Después de 5 intentos fallidos se bloquea unos minutos.
            </p>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <AppButton variant="secondary" label="Cancelar" @click="close" />
                <AppButton label="Entrar" :loading="store.saving" @click="submit" />
            </div>
        </template>
    </AppModal>
</template>

<script setup>
import { ref, watch } from 'vue';
import { AlertCircle } from 'lucide-vue-next';
import AppButton from '../ui/AppButton.vue';
import AppIcon from '../ui/AppIcon.vue';
import AppModal from '../ui/AppModal.vue';
import { useEmployeeStore } from '../../stores/employees';

const props = defineProps({
    employee: { type: Object, default: null },
});

const emit = defineEmits(['accepted']);

const store = useEmployeeStore();
const pin = ref('');

watch(
    () => props.employee,
    (employee) => {
        pin.value = '';
        store.pinError = '';
    }
);

const close = () => emit('accepted');

const submit = async () => {
    if (pin.value.length !== 4) {
        store.pinError = 'El PIN tiene 4 dígitos.';
        return;
    }

    try {
        await store.login(props.employee.id, pin.value);
        emit('accepted');
    } catch (err) {
        pin.value = '';
    }
};
</script>
