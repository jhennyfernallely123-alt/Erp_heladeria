<template>
    <AppModal
        :open="open"
        :title="employee?.name ?? ''"
        subtitle="Ficha del empleado"
        @close="emit('close')"
    >
        <div v-if="employee" class="space-y-4">
            <div
                v-if="!employee.vacation?.available"
                class="bg-amber-50 border border-amber-200 rounded-xl px-3 py-2 flex items-start gap-2"
            >
                <AppIcon :name="AlertTriangle" :size="16" class="text-amber-600 mt-0.5 shrink-0" />
                <p class="text-xs text-amber-800">
                    Cargá la fecha de contratación para que el sistema calcule el saldo de
                    vacaciones. Sin ella el empleado ve cero.
                </p>
            </div>

            <AppInput v-model="form.name" label="Nombre" required />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <AppInput v-model="form.phone" label="Teléfono (opcional)" />
                <AppInput v-model="form.hired_at" label="Fecha de contratación" type="date" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <AppInput v-model="form.birth_date" label="Fecha de cumpleaños" type="date" />
                <AppInput
                    v-model="form.family_day"
                    label="Día de familia"
                    type="date"
                    :hint="'Solo importan el mes y el día'"
                />
            </div>

            <div
                v-if="!employee.has_pin"
                class="bg-rose-50 border border-rose-200 rounded-xl px-3 py-2"
            >
                <p class="text-xs text-rose-800">
                    Este empleado no tiene PIN, así que no puede entrar al portal. Asignáselo desde
                    el módulo de turnos o desde la lista de meseros.
                </p>
            </div>

            <p v-if="error" class="text-xs text-rose-600 flex items-start gap-1">
                <AppIcon :name="AlertCircle" :size="13" class="mt-0.5 shrink-0" />
                {{ error }}
            </p>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <AppButton variant="secondary" label="Cancelar" @click="emit('close')" />
                <AppButton label="Guardar" :loading="saving" @click="submit" />
            </div>
        </template>
    </AppModal>
</template>

<script setup>
import { ref, watch } from 'vue';
import { AlertCircle, AlertTriangle } from 'lucide-vue-next';
import AppButton from '../ui/AppButton.vue';
import AppIcon from '../ui/AppIcon.vue';
import AppInput from '../ui/AppInput.vue';
import AppModal from '../ui/AppModal.vue';
import { useTeamStore } from '../../stores/team';

const props = defineProps({
    open: { type: Boolean, default: false },
    employee: { type: Object, default: null },
    saving: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'saved']);

const team = useTeamStore();

const form = ref({ name: '', phone: '', hired_at: '', birth_date: '', family_day: '' });
const error = ref('');

watch(
    () => props.employee,
    (employee) => {
        error.value = '';
        if (!employee) return;

        form.value = {
            name: employee.name || '',
            phone: employee.phone || '',
            hired_at: employee.hired_at || '',
            birth_date: employee.birth_date || '',
            family_day: employee.family_day_full || '',
        };
    }
);

const submit = async () => {
    error.value = '';

    if (!form.value.name.trim()) {
        error.value = 'El nombre no puede quedar vacío.';
        return;
    }

    try {
        await team.updateEmployee(props.employee.id, {
            name: form.value.name.trim(),
            phone: form.value.phone || null,
            hired_at: form.value.hired_at || null,
            birth_date: form.value.birth_date || null,
            family_day: form.value.family_day || null,
        });

        emit('saved');
    } catch (err) {
        error.value = team.error;
    }
};
</script>
