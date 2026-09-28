<template>
    <AppModal
        :open="open"
        :title="title"
        :subtitle="subtitle"
        size="sm"
        @close="emit('close')"
    >
        <div class="space-y-4">
            <!-- Dias -->
            <div>
                <p class="text-xs font-semibold text-petrol-700 mb-2">
                    ¿Qué día querés tomar?
                </p>

                <div class="grid grid-cols-2 gap-3">
                    <AppInput v-model="startDate" label="Desde" type="date" required />
                    <AppInput
                        v-model="endDate"
                        label="Hasta"
                        type="date"
                        :hint="multiDayHint"
                    />
                </div>

                <div class="flex items-center justify-between gap-3 mt-3">
                    <label class="flex items-center gap-2 text-xs text-petrol-700 cursor-pointer">
                        <input
                            v-model="multiDay"
                            type="checkbox"
                            class="h-4 w-4 rounded border-aguamarina-300 text-aguamarina-600 focus:ring-aguamarina-500"
                        />
                        Tomar varios días seguidos
                    </label>

                    <p v-if="computedDays !== null" class="text-xs text-niebla-400">
                        <strong class="text-petrol-700">{{ computedDays }}</strong>
                        {{ computedDays === 1 ? 'día' : 'días' }}
                    </p>
                </div>
            </div>

            <!-- Evidencia -->
            <div v-if="requiresEvidence" class="space-y-2">
                <div class="flex items-start gap-2">
                    <AppIcon :name="ShieldAlert" :size="15" class="text-amber-600 mt-0.5 shrink-0" />
                    <p class="text-xs text-amber-800">
                        <strong>{{ evidenceLabel }}</strong> es obligatorio. Subí una foto o un PDF
                        legible.
                    </p>
                </div>

                <label
                    class="block rounded-xl border-2 border-dashed px-4 py-6 text-center cursor-pointer transition"
                    :class="
                        files.length
                            ? 'border-aguamarina-400 bg-aguamarina-50'
                            : 'border-aguamarina-200 hover:border-aguamarina-400 hover:bg-aguamarina-50/50'
                    "
                >
                    <input
                        type="file"
                        class="sr-only"
                        accept="image/jpeg,image/png,image/webp,application/pdf"
                        multiple
                        :max="maxFiles"
                        @change="onFiles"
                    />
                    <AppIcon
                        :name="files.length ? CheckCircle2 : UploadCloud"
                        :size="24"
                        :class="files.length ? 'text-aguamarina-600 mx-auto' : 'text-niebla-300 mx-auto'"
                    />
                    <p class="text-xs font-semibold text-petrol-800 mt-2">
                        {{ files.length ? `${files.length} archivo(s) listo(s)` : 'Elegí el archivo' }}
                    </p>
                    <p class="text-[11px] text-niebla-400 mt-0.5">
                        PDF, JPG, PNG o WEBP · hasta 8 MB cada uno
                    </p>
                </label>

                <ul v-if="files.length" class="space-y-1.5">
                    <li
                        v-for="(file, index) in files"
                        :key="`${file.name}-${index}`"
                        class="flex items-center gap-2 rounded-lg bg-aguamarina-50 px-3 py-2"
                    >
                        <AppIcon :name="FileText" :size="15" class="text-aguamarina-600 shrink-0" />
                        <span class="text-xs text-petrol-700 truncate flex-1">
                            {{ file.name }}
                        </span>
                        <span class="text-[10px] text-niebla-400">{{ sizeLabel(file.size) }}</span>
                        <button
                            type="button"
                            class="p-1 rounded hover:bg-white text-niebla-400 hover:text-rose-600 transition"
                            :aria-label="`Quitar ${file.name}`"
                            @click="removeFile(index)"
                        >
                            <AppIcon :name="X" :size="13" />
                        </button>
                    </li>
                </ul>
            </div>

            <div v-else class="bg-aguamarina-50 rounded-xl px-3 py-2">
                <p class="text-xs text-petrol-700">
                    Este tipo de permiso no necesita ningún documento.
                </p>
            </div>

            <!-- Motivo -->
            <AppTextarea
                v-model="reason"
                label="Motivo (opcional)"
                :rows="2"
                placeholder="Por ejemplo: cita médica, viaje, celebration"
            />

            <p v-if="error" class="text-xs text-rose-600 flex items-start gap-1">
                <AppIcon :name="AlertCircle" :size="13" class="mt-0.5 shrink-0" />
                {{ error }}
            </p>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <AppButton variant="secondary" label="Cancelar" @click="emit('close')" />
                <AppButton
                    label="Agregar al calendario"
                    :loading="store.saving"
                    :disabled="!startDate"
                    @click="confirm"
                />
            </div>
        </template>
    </AppModal>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import {
    AlertCircle,
    CheckCircle2,
    FileText,
    ShieldAlert,
    UploadCloud,
    X,
} from 'lucide-vue-next';
import AppButton from '../ui/AppButton.vue';
import AppIcon from '../ui/AppIcon.vue';
import AppInput from '../ui/AppInput.vue';
import AppModal from '../ui/AppModal.vue';
import AppTextarea from '../ui/AppTextarea.vue';
import { useEmployeeStore } from '../../stores/employees';

const props = defineProps({
    open: { type: Boolean, default: false },
    type: { type: String, default: 'vacation' },
    label: { type: String, default: '' },
    requiresEvidence: { type: Boolean, default: false },
    evidenceLabel: { type: String, default: 'El documento' },
    maxFiles: { type: Number, default: 1 },
    /** Primer día del tramo anterior, para continuar la selección al elegir otro. */
    seedDate: { type: String, default: null },
});

const emit = defineEmits(['close', 'confirm']);

const store = useEmployeeStore();

const startDate = ref('');
const endDate = ref('');
const multiDay = ref(false);
const files = ref([]);
const reason = ref('');
const error = ref('');

const TITLES = {
    vacation: 'Pedir vacaciones',
    birthday: 'Pedir el día de cumpleaños',
    family_day: 'Pedir el día de familia',
    sick_leave: 'Pedir permiso por salud',
    incapacity: 'Pedir incapacidad médica',
};

const EVIDENCE = {
    birthday: 'La cédula',
    sick_leave: 'El certificado médico',
    incapacity: 'La incapacidad médica',
};

const title = computed(() => TITLES[props.type] || 'Solicitar tiempo libre');
const subtitle = computed(() => {
    if (props.type === 'vacation') {
        return 'Podés tomar varios días seguidos en un solo tramo';
    }
    if (props.requiresEvidence) {
        return 'Necesitás un documento que respalde el permiso';
    }
    return 'Elegí el día que querés tomar';
});

const multiDayHint = computed(() =>
    multiDay.value ? 'Incluye los dos días' : 'Solo un día'
);

const computedDays = computed(() => {
    if (!startDate.value) return null;

    const start = new Date(`${startDate.value}T00:00:00`);
    if (Number.isNaN(start.getTime())) return null;

    if (!multiDay.value || !endDate.value) return 1;

    const end = new Date(`${endDate.value}T00:00:00`);
    if (Number.isNaN(end.getTime()) || end < start) return 1;

    let count = 0;
    for (const d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
        const day = d.getDay();
        if (day !== 0 && day !== 6) count++;
    }

    return count;
});

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) return;

        startDate.value = props.seedDate || todayKey();
        endDate.value = props.seedDate || '';
        multiDay.value = false;
        files.value = [];
        reason.value = '';
        error.value = '';
    }
);

const todayKey = () => {
    const now = new Date();
    const m = String(now.getMonth() + 1).padStart(2, '0');
    const d = String(now.getDate()).padStart(2, '0');
    return `${now.getFullYear()}-${m}-${d}`;
};

const onFiles = (event) => {
    const picked = Array.from(event.target.files || []);
    error.value = '';

    if (picked.length > props.maxFiles) {
        error.value = `Podés adjuntar hasta ${props.maxFiles} archivo(s).`;
        event.target.value = '';
        return;
    }

    const tooBig = picked.find((f) => f.size > 8 * 1024 * 1024);
    if (tooBig) {
        error.value = `"${tooBig.name}" pesa más de 8 MB.`;
        event.target.value = '';
        return;
    }

    files.value = picked;
};

const removeFile = (index) => {
    files.value = files.value.filter((_, i) => i !== index);
};

const sizeLabel = (bytes) => {
    if (!bytes) return '';
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};

const confirm = () => {
    error.value = '';

    if (!startDate.value) {
        error.value = 'Elegí una fecha.';
        return;
    }

    if (props.requiresEvidence && files.value.length === 0) {
        error.value = `${props.evidenceLabel} es obligatorio para este permiso.`;
        return;
    }

    const start = multiDay.value && endDate.value ? endDate.value : startDate.value;

    emit('confirm', {
        start_date: startDate.value,
        end_date: start,
        type: props.type,
        files: files.value,
        reason: reason.value || null,
    });
};
</script>
