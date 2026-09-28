<template>
    <div class="bg-white rounded-2xl border border-aguamarina-100 shadow-sm p-5 space-y-4">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <h2 class="font-bold text-sm text-petrol-800 flex items-center gap-2">
                <AppIcon :name="CalendarDays" :size="16" />
                Calendario de solicitudes
            </h2>

            <div class="flex items-center gap-2">
                <AppButton
                    variant="secondary"
                    :icon="ChevronLeft"
                    aria-label="Mes anterior"
                    @click="shiftMonth(-1)"
                />
                <span class="text-sm font-bold text-petrol-800 min-w-[7.5rem] text-center">
                    {{ monthLabel }}
                </span>
                <AppButton
                    variant="secondary"
                    :icon="ChevronRight"
                    aria-label="Mes siguiente"
                    :disabled="isCurrentMonth"
                    @click="shiftMonth(1)"
                />
            </div>
        </div>

        <p class="text-xs text-niebla-400">
            Tocá un día para elegir qué le corresponde. Podés marcar varios antes de
            enviarlos.
        </p>

        <!-- Leyenda -->
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 justify-center">
            <span
                v-for="legend in legends"
                :key="legend.type"
                class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-niebla-500"
            >
                <span class="h-2.5 w-2.5 rounded-full" :class="legend.dot" />
                {{ legend.label }}
            </span>
            <span
                class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-niebla-500"
            >
                <span class="h-2.5 w-2.5 rounded-full bg-niebla-200" />
                Ya lo pediste
            </span>
        </div>

        <!-- Días de la semana -->
        <div class="grid grid-cols-7 gap-1 max-w-[31rem] mx-auto text-center">
            <span
                v-for="label in WEEKDAYS"
                :key="label"
                class="text-xs uppercase font-bold text-niebla-400 py-1"
            >
                {{ label }}
            </span>
        </div>

        <!-- Días -->
        <div class="grid grid-cols-7 gap-1 max-w-[31rem] mx-auto">
            <div v-for="cell in cells" :key="cell.key" class="aspect-square">
                <button
                    v-if="cell.day"
                    type="button"
                    class="w-full h-full rounded-lg text-[13px] font-semibold flex flex-col items-center justify-center gap-0.5 border transition"
                    :class="dayClasses(cell)"
                    :disabled="!cell.selectable"
                    @click="pick(cell)"
                >
                    <span>{{ cell.day }}</span>
                    <span
                        v-if="cell.request"
                        class="h-1 w-1 rounded-full"
                        :class="statusDots[cell.request.status]"
                    />
                </button>
            </div>
        </div>

        <!-- Resumen de lo marcado -->
        <div
            v-if="selection.length"
            class="rounded-xl bg-aguamarina-50 px-4 py-3 space-y-2"
        >
            <div class="flex items-center justify-between gap-3">
                <p class="text-xs font-bold text-petrol-800">
                    {{ totalSelectedDays }} días marcados ·
                    {{ totalVacationDays }} de vacaciones
                </p>
                <button
                    type="button"
                    class="text-[11px] font-semibold text-aguamarina-700 hover:underline"
                    @click="clearSelection"
                >
                    Borrar todo
                </button>
            </div>

            <div class="flex flex-wrap gap-1.5">
                <span
                    v-for="(item, index) in selection"
                    :key="`${item.start_date}-${index}`"
                    class="inline-flex items-center gap-1.5 text-[11px] font-semibold pl-2 pr-1 py-1 rounded-full"
                    :class="legendByType[item.type].chip"
                >
                    {{ item.start_date === item.end_date
                        ? formatShort(item.start_date)
                        : `${formatShort(item.start_date)} al ${formatShort(item.end_date)}` }}
                    <span class="opacity-70">{{ legendByType[item.type].label }}</span>
                    <AppIcon
                        v-if="item.files?.length"
                        :name="Paperclip"
                        :size="11"
                        class="opacity-70"
                    />
                    <button
                        type="button"
                        class="hover:bg-black/10 rounded-full p-0.5"
                        :aria-label="`Quitar ${formatShort(item.start_date)}`"
                        @click="removeSegment(index)"
                    >
                        <AppIcon :name="X" :size="11" />
                    </button>
                </span>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <AppButton
                v-if="selection.length"
                variant="secondary"
                label="Limpiar"
                @click="clearSelection"
            />
            <AppButton
                label="Enviar solicitudes"
                :icon="Send"
                :loading="store.saving"
                :disabled="!selection.length"
                @click="submit"
            />
        </div>

        <p v-if="error" class="text-xs text-rose-600 flex items-start gap-1">
            <AppIcon :name="AlertCircle" :size="13" class="mt-0.5 shrink-0" />
            {{ error }}
        </p>
    </div>

    <!-- Lightbox 1: qué le corresponde al día tocado -->
    <DayTypeDialog
        :open="Boolean(pickedDay)"
        :date="pickedDay"
        :current-type="pickedDay ? typeAt(pickedDay) : null"
        @close="pickedDay = null"
        @pick="onTypePicked"
    />

    <!-- Lightbox 2: días, evidencia y motivo -->
    <DayDetailsDialog
        :open="Boolean(detailType)"
        :type="detailType || 'vacation'"
        :requires-evidence="requiresEvidence(detailType)"
        :evidence-label="evidenceLabel(detailType)"
        :max-files="maxFiles(detailType)"
        :seed-date="detailSeed"
        @close="detailType = null"
        @confirm="onDetailsConfirmed"
    />

    <!-- Lightbox: motivo, una vez marcados los días -->
    <AppModal
        :open="isReasonOpen"
        title="Motivo de la solicitud"
        subtitle="Opcional, pero ayuda al administrador a entender el pedido"
        size="sm"
        @close="isReasonOpen = false"
    >
        <AppTextarea
            v-model="reason"
            label="Motivo"
            :rows="3"
            placeholder="Por ejemplo: viaje familiar"
        />

        <template #footer>
            <div class="flex justify-end gap-2">
                <AppButton variant="secondary" label="Cancelar" @click="isReasonOpen = false" />
                <AppButton label="Enviar" :loading="store.saving" @click="send" />
            </div>
        </template>
    </AppModal>
</template>

<script setup>
import { computed, ref } from 'vue';
import {
    AlertCircle,
    CalendarDays,
    ChevronLeft,
    ChevronRight,
    Paperclip,
    Send,
    X,
} from 'lucide-vue-next';
import AppButton from '../ui/AppButton.vue';
import AppIcon from '../ui/AppIcon.vue';
import AppModal from '../ui/AppModal.vue';
import AppTextarea from '../ui/AppTextarea.vue';
import DayDetailsDialog from './DayDetailsDialog.vue';
import DayTypeDialog from './DayTypeDialog.vue';
import { useEmployeeStore } from '../../stores/employees';

const emit = defineEmits(['saved']);

const store = useEmployeeStore();

const WEEKDAYS = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

/**
 * Colores por motivo. Los tres primeros usan la paleta de la marca: petróleo
 * para vacaciones, rose para cumpleaños y aguamarina para el día de familia.
 * Los médicos van en ámbar y violeta para que se distingan de un vistazo de
 * los que sí consumen vacaciones.
 */
const TYPES = [
    { type: 'vacation', label: 'Vacaciones', dot: 'bg-petrol-500', chip: 'bg-petrol-100 text-petrol-800', day: 'bg-petrol-50 text-petrol-800 border-petrol-300' },
    { type: 'birthday', label: 'Cumpleaños', dot: 'bg-rose-500', chip: 'bg-rose-100 text-rose-800', day: 'bg-rose-50 text-rose-800 border-rose-300' },
    { type: 'family_day', label: 'Día de familia', dot: 'bg-aguamarina-500', chip: 'bg-aguamarina-100 text-aguamarina-800', day: 'bg-aguamarina-50 text-aguamarina-800 border-aguamarina-300' },
    { type: 'sick_leave', label: 'Permiso por salud', dot: 'bg-amber-500', chip: 'bg-amber-100 text-amber-800', day: 'bg-amber-50 text-amber-800 border-amber-300' },
    { type: 'incapacity', label: 'Incapacidad médica', dot: 'bg-violet-500', chip: 'bg-violet-100 text-violet-800', day: 'bg-violet-50 text-violet-800 border-violet-300' },
    { type: 'unpaid', label: 'Permiso sin sueldo', dot: 'bg-niebla-400', chip: 'bg-niebla-100 text-niebla-700', day: 'bg-niebla-50 text-niebla-700 border-niebla-300' },
];

const legendByType = Object.fromEntries(TYPES.map((t) => [t.type, t]));

/** La leyenda solo muestra los motivos que se pueden pedir desde el calendario. */
const LEGEND_TYPES = ['vacation', 'birthday', 'family_day', 'sick_leave', 'incapacity'];
const legends = TYPES.filter((t) => LEGEND_TYPES.includes(t.type));

const statusDots = {
    pending: 'bg-amber-500',
    approved: 'bg-emerald-500',
    rejected: 'bg-niebla-300',
};

/** Fecha local en YYYY-MM-DD, sin desfase de zona horaria. */
const toKey = (date) => {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
};

const fromKey = (key) => {
    const [y, m, d] = key.split('-').map(Number);
    return new Date(y, m - 1, d);
};

const todayKey = toKey(new Date());

/**
 * Motivos que exigen un documento y cuántos archivos aceptan cada uno. Tiene
 * que coincidir con AttachmentService: el backend es quien manda, pero la
 * vista avisa antes para que el empleado no descubra el faltante al enviar.
 */
const EVIDENCE = {
    birthday: { label: 'La cédula', max: 1 },
    sick_leave: { label: 'El certificado médico', max: 3 },
    incapacity: { label: 'La incapacidad médica', max: 3 },
};

const cursor = ref(new Date());
const pickedDay = ref(null);
const detailType = ref(null);
const detailSeed = ref(null);
const isReasonOpen = ref(false);
const reason = ref('');
const error = ref('');

/**
 * Tramos marcados. Cada uno trae sus archivos y su motivo, así que dos días
 * del mismo motivo pero con documentos distintos son dos tramos separados.
 */
const selection = ref([]);

const requiresEvidence = (type) => Boolean(EVIDENCE[type]);
const evidenceLabel = (type) => EVIDENCE[type]?.label || 'El documento';
const maxFiles = (type) => EVIDENCE[type]?.max || 1;

const monthLabel = computed(() =>
    cursor.value.toLocaleDateString('es-CO', { month: 'long', year: 'numeric' })
);

const isCurrentMonth = computed(() => {
    const now = new Date();
    return (
        cursor.value.getFullYear() === now.getFullYear() &&
        cursor.value.getMonth() === now.getMonth()
    );
});

/**
 * Días ya enviados, indexados por fecha, para pintar el calendario. Se marca el
 * día aunque la solicitud cubra un rango entero: así se ve de un vistazo qué
 * está tomado.
 */
const requestsByDate = computed(() => {
    const map = {};
    for (const r of store.requests) {
        if (r.status === 'rejected') continue;
        const start = fromKey(r.start_date);
        const end = fromKey(r.end_date);
        for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
            const key = toKey(d);
            if (!map[key] || r.status === 'approved') map[key] = r;
        }
    }
    return map;
});

/** Tipo pintado en cada día del tramo marcado. */
const selectionByDate = computed(() => {
    const map = {};

    for (const segment of selection.value) {
        for (const key of expandRange(segment.start_date, segment.end_date)) {
            map[key] = segment.type;
        }
    }

    return map;
});

/** Días que cubre un tramo, inclusive. */
const expandRange = (startKey, endKey) => {
    const keys = [];
    const d = fromKey(startKey);
    const last = fromKey(endKey);

    while (d <= last) {
        keys.push(toKey(d));
        d.setDate(d.getDate() + 1);
    }

    return keys;
};

const cells = computed(() => {
    const year = cursor.value.getFullYear();
    const month = cursor.value.getMonth();
    const first = new Date(year, month, 1);
    // getDay() da 0 para domingo; la semana arranca en lunes.
    const offset = (first.getDay() + 6) % 7;
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    const list = [];

    for (let i = 0; i < offset; i++) {
        list.push({ key: `pad-${i}`, day: null });
    }

    for (let d = 1; d <= daysInMonth; d++) {
        const date = new Date(year, month, d);
        const key = toKey(date);
        const weekday = date.getDay() === 0 || date.getDay() === 6;

        list.push({
            key,
            day: d,
            isToday: key === todayKey,
            selectable: key >= todayKey,
            weekend: weekday,
            request: requestsByDate.value[key] || null,
            selectedType: selectionByDate.value[key] || null,
        });
    }

    return list;
});

/** Días hábiles marcados de vacaciones, que es lo que descuenta el saldo. */
const totalVacationDays = computed(() => {
    let total = 0;

    for (const segment of selection.value) {
        if (segment.type === 'vacation') {
            for (const key of expandRange(segment.start_date, segment.end_date)) {
                const day = fromKey(key).getDay();
                if (day !== 0 && day !== 6) total++;
            }
        }
    }

    return total;
});

/** Total de días marcados, de cualquier motivo. */
const totalSelectedDays = computed(() =>
    selection.value.reduce(
        (acc, s) => acc + expandRange(s.start_date, s.end_date).length,
        0
    )
);

const dayClasses = (cell) => {
    // Hoy gana sobre todo lo demás: si no se ve de un vistazo en qué día
    // estamos, el calendario no sirve para elegir bien.
    if (cell.isToday) {
        return 'bg-aguamarina-600 text-white border-aguamarina-600 shadow-sm font-bold';
    }

    if (!cell.selectable) {
        return 'text-niebla-300 border-transparent cursor-default';
    }

    if (cell.request) {
        // Un día ya pedido se ve gris y no se puede tocar. El puntito de abajo
        // dice si el administrador todavía no lo revisó o si ya lo aprobó.
        return 'bg-niebla-100 text-niebla-500 border-niebla-200 cursor-not-allowed';
    }

    if (cell.selectedType) {
        return legendByType[cell.selectedType].day;
    }

    if (cell.weekend) {
        return 'bg-niebla-50 text-niebla-400 border-transparent hover:bg-aguamarina-50';
    }

    return 'bg-white text-petrol-700 border-aguamarina-200 hover:border-aguamarina-500';
};

const shiftMonth = (delta) => {
    cursor.value = new Date(
        cursor.value.getFullYear(),
        cursor.value.getMonth() + delta,
        1
    );
};

/** Motivo ya asignado a un día, para Pintarlo y para offercer cambiarlo. */
const typeAt = (key) => selectionByDate.value[key] || null;

/** Índice del tramo que cubre un día, para poder quitarlo entero. */
const segmentIndexAt = (key) =>
    selection.value.findIndex((s) => expandRange(s.start_date, s.end_date).includes(key));

const pick = (cell) => {
    if (!cell.selectable || cell.request) return;

    const index = segmentIndexAt(cell.key);

    if (index !== -1) {
        // Tocar un día ya marcado saca el tramo entero: en vacaciones son varios
        // días y tiene que irse el bloque, no un día suelto.
        removeSegment(index);
        return;
    }

    pickedDay.value = cell.key;
};

/** Primer lightbox: eligió el motivo, ahora se pide el detalle. */
const onTypePicked = (type) => {
    detailSeed.value = pickedDay.value;
    detailType.value = type;
    pickedDay.value = null;
};

/** Segundo lightbox: confirmó días, evidencia y motivo. */
const onDetailsConfirmed = (segment) => {
    selection.value.push({
        start_date: segment.start_date,
        end_date: segment.end_date,
        type: segment.type,
        files: segment.files,
        reason: segment.reason,
    });
    detailType.value = null;
    error.value = '';
};

const removeSegment = (index) => {
    selection.value = selection.value.filter((_, i) => i !== index);
};

const clearSelection = () => {
    selection.value = [];
    reason.value = '';
    error.value = '';
};

const formatShort = (key) => {
    const d = fromKey(key);
    return String(d.getDate()).padStart(2, '0');
};

const submit = () => {
    if (!selection.value.length) return;
    isReasonOpen.value = true;
};

const send = async () => {
    error.value = '';

    // Cada tramo va por separado porque puede llevar sus propios documentos.
    // Los archivos se suben con un FormData por tramo: meterlos todos juntos
    // en un solo pedido no se puede saber a qué solicitud va cada uno.
    try {
        for (const segment of selection.value) {
            const form = new FormData();

            form.append('items[0][start_date]', segment.start_date);
            form.append('items[0][end_date]', segment.end_date);
            form.append('items[0][type]', segment.type);

            if (segment.reason) {
                form.append('reason', segment.reason);
            }

            for (const file of segment.files || []) {
                form.append('documents[]', file, file.name);
            }

            await store.requestTimeOff(form);
        }

        clearSelection();
        isReasonOpen.value = false;
        emit('saved');
    } catch (err) {
        error.value = store.error;
    }
};
</script>
