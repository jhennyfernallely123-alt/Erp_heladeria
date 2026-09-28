<template>
    <div class="space-y-5">
        <!-- Saludo y datos -->
        <div class="bg-white rounded-2xl border border-aguamarina-100 shadow-card p-5 sm:p-6">
            <div class="flex items-start gap-4">
                <div
                    class="h-14 w-14 rounded-2xl bg-aguamarina-100 flex items-center justify-center shrink-0"
                >
                    <AppIcon :name="User" :size="26" class="text-aguamarina-600" />
                </div>
                <div class="min-w-0 flex-1">
                    <h1 class="text-xl font-bold text-petrol-800">{{ profile.name }}</h1>
                    <p class="text-xs text-niebla-400 mt-0.5">
                        {{ roleLabel }} · {{ profile.tenure.label }} en la heladería
                    </p>
                </div>
                <button
                    type="button"
                    class="p-2 rounded-lg text-niebla-300 hover:text-aguamarina-600 hover:bg-aguamarina-50 transition shrink-0"
                    aria-label="Actualizar"
                    title="Actualizar"
                    @click="refresh"
                >
                    <AppIcon :name="RefreshCw" :size="18" />
                </button>
            </div>

            <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-5 pt-5 border-t border-aguamarina-50">
                <div>
                    <dt class="text-[11px] uppercase font-bold text-niebla-400">Contratado</dt>
                    <dd class="text-sm font-semibold text-petrol-800 mt-0.5">
                        {{ formatDate(profile.hired_at) }}
                    </dd>
                </div>
                <div>
                    <dt class="text-[11px] uppercase font-bold text-niebla-400">Cumpleaños</dt>
                    <dd class="text-sm font-semibold text-petrol-800 mt-0.5">
                        {{ formatDate(profile.birth_date) }}
                    </dd>
                </div>
                <div>
                    <dt class="text-[11px] uppercase font-bold text-niebla-400">Día de familia</dt>
                    <dd class="text-sm font-semibold text-petrol-800 mt-0.5">
                        {{ formatMonthDay(profile.family_day) }}
                    </dd>
                </div>
                <div>
                    <dt class="text-[11px] uppercase font-bold text-niebla-400">Antigüedad</dt>
                    <dd class="text-sm font-semibold text-petrol-800 mt-0.5">
                        {{ profile.tenure.label }}
                    </dd>
                </div>
            </dl>
        </div>

        <!-- Saldo de vacaciones -->
        <div
            v-if="store.vacation?.available"
            class="rounded-2xl p-5 sm:p-6 bg-petrol-800 text-white shadow-xl"
        >
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-aguamarina-300">
                        Días de vacaciones disponibles
                    </p>
                    <p class="text-4xl font-bold mt-1 text-aguamarina-200">
                        {{ store.vacation.available_days }}
                    </p>
                    <p class="text-xs text-niebla-400 mt-1">
                        Devengadas {{ store.vacation.accrued }} · usadas {{ store.vacation.used }}
                    </p>
                </div>
            </div>
        </div>

        <div
            v-else
            class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-start gap-3"
        >
            <AppIcon :name="Info" :size="18" class="text-amber-600 mt-0.5 shrink-0" />
            <p class="text-sm text-amber-800">
                {{ store.vacation?.message || 'Falta cargar tu fecha de contratación.' }}
                Pedile al administrador que la complete en la vista de equipo.
            </p>
        </div>

        <!-- Próximos eventos -->
        <div
            v-if="profile.upcoming?.length"
            class="grid grid-cols-1 sm:grid-cols-2 gap-3"
        >
            <div
                v-for="u in profile.upcoming"
                :key="u.type"
                class="bg-white rounded-2xl border border-aguamarina-100 shadow-sm p-4 flex items-center gap-3"
            >
                <div
                    class="h-11 w-11 rounded-xl flex items-center justify-center shrink-0"
                    :class="u.type === 'birthday' ? 'bg-rose-50 text-rose-600' : 'bg-aguamarina-50 text-aguamarina-600'"
                >
                    <AppIcon :name="u.type === 'birthday' ? Cake : Heart" :size="20" />
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-petrol-800">{{ u.label }}</p>
                    <p class="text-xs text-niebla-400">
                        {{ formatDate(u.date) }} ·
                        <span v-if="u.days_away === 0">es hoy</span>
                        <span v-else>en {{ u.days_away }} días</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Calendario para pedir -->
        <TimeOffCalendar @saved="onSaved" />

        <!-- Solicitudes enviadas -->
        <div class="bg-white rounded-2xl border border-aguamarina-100 shadow-sm p-5 space-y-4">
            <h2 class="font-bold text-sm text-petrol-800 flex items-center gap-2">
                <AppIcon :name="ClipboardList" :size="16" />
                Mis solicitudes enviadas
            </h2>

            <AppEmptyState
                v-if="!store.requests.length"
                :icon="ClipboardList"
                title="Sin solicitudes"
                description="Todavía no pediste días de vacaciones ni permisos."
            />

            <div v-else class="divide-y divide-aguamarina-100">
                <div
                    v-for="r in store.requests"
                    :key="r.id"
                    class="py-3 flex items-start justify-between gap-3"
                >
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-petrol-800">
                            {{ typeLabel(r.type) }} ·
                            <span class="font-normal text-niebla-500">
                                {{ rangeLabel(r) }}
                            </span>
                        </p>
                        <p v-if="r.reason" class="text-xs text-niebla-400 mt-0.5">
                            {{ r.reason }}
                        </p>
                        <p v-if="r.response_note" class="text-xs text-niebla-400 mt-0.5 italic">
                            Respuesta: {{ r.response_note }}
                        </p>
                    </div>

                    <div class="text-right shrink-0">
                        <p class="text-sm font-bold text-petrol-800">{{ r.days }}d</p>
                        <AppBadge :tone="statusTone(r.status)" :label="statusLabel(r.status)" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Cake, ClipboardList, Heart, Info, RefreshCw, User } from 'lucide-vue-next';
import AppBadge from '../ui/AppBadge.vue';
import AppEmptyState from '../ui/AppEmptyState.vue';
import AppIcon from '../ui/AppIcon.vue';
import TimeOffCalendar from './TimeOffCalendar.vue';
import { useEmployeeStore } from '../../stores/employees';
import { useToastStore } from '../../stores/toast';

const store = useEmployeeStore();
const toast = useToastStore();

const profile = computed(() => store.profile || {});

const ROLE_LABELS = {
    admin: 'Administrador',
    cashier: 'Cajero',
    waiter: 'Mesero',
    kitchen: 'Cocina y Barra',
};

const TYPE_LABELS = {
    vacation: 'Vacaciones',
    family_day: 'Día de familia',
    birthday: 'Cumpleaños',
    unpaid: 'Permiso sin sueldo',
    sick_leave: 'Permiso por salud',
    incapacity: 'Incapacidad médica',
};

const STATUS = {
    pending: { label: 'Pendiente', tone: 'warning' },
    approved: { label: 'Aprobada', tone: 'success' },
    rejected: { label: 'Rechazada', tone: 'danger' },
};

const roleLabel = computed(() => {
    const roles = profile.value.roles || [];
    return roles.map((r) => ROLE_LABELS[r] || r).join(' · ') || 'Empleado';
});

const typeLabel = (type) => TYPE_LABELS[type] || type;
const statusLabel = (status) => STATUS[status]?.label || status;
const statusTone = (status) => STATUS[status]?.tone || 'neutral';

const formatDate = (value) => {
    if (!value) return 'No cargada';
    const [y, m, d] = String(value).split('-');
    return `${d}/${m}/${y}`;
};

/**
 * El dia de familia llega como "m-d" desde la API, no como fecha completa: de
 * ese dato solo interesa el mes y el dia. Por eso no se puede usar el mismo
 * formateador que el cumpleaños, que si es YYYY-MM-DD.
 */
const formatMonthDay = (value) => {
    if (!value) return 'No cargado';

    const parts = String(value).split('-');

    if (parts.length < 2) return 'No cargado';

    const [m, d] = parts.length === 2 ? parts : parts.slice(1);

    return `${d}/${m}`;
};

const rangeLabel = (r) =>
    r.start_date === r.end_date ? formatDate(r.start_date) : `${formatDate(r.start_date)} al ${formatDate(r.end_date)}`;

const refresh = async () => {
    try {
        await store.refreshProfile();
    } catch (err) {
        store.forget();
    }
};

const onSaved = () => {
    toast.success('Solicitud enviada. Te avisamos cuando el administrador la revise.');
};
</script>
