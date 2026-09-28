<template>
    <AppModal
        :open="open"
        title="¿Qué le corresponde a este día?"
        :subtitle="date ? longDate(date) : ''"
        size="sm"
        @close="emit('close')"
    >
        <div class="space-y-2">
            <button
                v-for="option in options"
                :key="option.type"
                type="button"
                class="w-full text-left rounded-xl border px-4 py-3 flex items-center gap-3 transition"
                :class="
                    currentType === option.type
                        ? 'border-aguamarina-500 bg-aguamarina-50'
                        : 'border-aguamarina-200 hover:border-aguamarina-400 hover:bg-aguamarina-50/50'
                "
                @click="emit('pick', option.type)"
            >
                <span class="h-9 w-9 rounded-xl flex items-center justify-center shrink-0" :class="option.iconBg">
                    <AppIcon :name="option.icon" :size="18" :class="option.iconColor" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-bold text-petrol-800">{{ option.label }}</span>
                    <span class="block text-xs text-niebla-400">{{ option.hint }}</span>
                </span>
                <AppIcon
                    v-if="currentType === option.type"
                    :name="Check"
                    :size="18"
                    class="text-aguamarina-600 shrink-0"
                />
            </button>
        </div>

        <p class="text-[11px] text-niebla-300 mt-4">
            Podés cambiarlo después: tocá de nuevo el día en el calendario.
        </p>
    </AppModal>
</template>

<script setup>
import { Cake, FileHeart, Heart, Palmtree, Stethoscope } from 'lucide-vue-next';
import AppIcon from '../ui/AppIcon.vue';
import AppModal from '../ui/AppModal.vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    date: { type: String, default: null },
    currentType: { type: String, default: null },
});

const emit = defineEmits(['close', 'pick']);

const options = [
    {
        type: 'vacation',
        label: 'Vacaciones',
        hint: 'Descuenta del saldo que acumulaste',
        icon: Palmtree,
        iconBg: 'bg-petrol-50',
        iconColor: 'text-petrol-600',
    },
    {
        type: 'birthday',
        label: 'Cumpleaños',
        hint: 'Tu día de cumpleaños',
        icon: Cake,
        iconBg: 'bg-rose-50',
        iconColor: 'text-rose-600',
    },
    {
        type: 'family_day',
        label: 'Día de familia',
        hint: 'Tu día de familia',
        icon: Heart,
        iconBg: 'bg-aguamarina-50',
        iconColor: 'text-aguamarina-600',
    },
    {
        type: 'sick_leave',
        label: 'Permiso por salud',
        hint: 'Un día por motivo de salud',
        icon: Stethoscope,
        iconBg: 'bg-amber-50',
        iconColor: 'text-amber-600',
    },
    {
        type: 'incapacity',
        label: 'Incapacidad médica',
        hint: 'Con incapacidad del médico',
        icon: FileHeart,
        iconBg: 'bg-violet-50',
        iconColor: 'text-violet-600',
    },
];

const longDate = (key) => {
    const [y, m, d] = key.split('-').map(Number);
    return new Date(y, m - 1, d).toLocaleDateString('es-CO', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
    });
};
</script>
