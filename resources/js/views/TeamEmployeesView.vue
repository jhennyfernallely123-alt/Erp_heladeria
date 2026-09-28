<template>
    <div class="p-6 max-w-6xl mx-auto space-y-6">
        <PageHeader
            title="Equipo"
            subtitle="Fichas de los empleados, saldos de vacaciones y solicitudes por aprobar"
        >
            <template #actions>
                <AppButton variant="secondary" label="Actualizar" :icon="RefreshCw" @click="load" />
            </template>
        </PageHeader>

        <div v-if="team.loading" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div
                    v-for="n in 4"
                    :key="n"
                    class="h-20 rounded-2xl bg-aguamarina-50 animate-pulse"
                />
            </div>
        </div>

        <template v-else>
            <!-- Aviso: falta fecha de contratación -->
            <div
                v-if="team.missingHireDate.length"
                class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-start gap-3"
            >
                <AppIcon :name="AlertTriangle" :size="18" class="text-amber-600 mt-0.5 shrink-0" />
                <div class="text-sm text-amber-800">
                    <p class="font-semibold">
                        {{ team.missingHireDate.length }}
                        {{ team.missingHireDate.length === 1 ? 'empleado necesita' : 'empleados necesitan' }}
                        la fecha de contratación
                    </p>
                    <p class="mt-0.5">
                        Sin esa fecha no se puede calcular el saldo de vacaciones.
                        <strong>{{ team.missingHireDate.map((e) => e.name).join(', ') }}</strong>
                    </p>
                </div>
            </div>

            <!-- Solicitudes pendientes -->
            <div
                v-if="team.pending.length"
                class="bg-white rounded-2xl border border-aguamarina-100 shadow-sm p-5 space-y-4"
            >
                <h2 class="font-bold text-sm text-petrol-800 flex items-center gap-2">
                    <AppIcon :name="Inbox" :size="16" />
                    Solicitudes por revisar
                    <AppBadge tone="warning" :label="String(team.pending.length)" />
                </h2>

                <div class="divide-y divide-aguamarina-100">
                    <div v-for="r in team.pending" :key="r.id" class="py-3 space-y-2">
                        <div class="flex items-start justify-between gap-3 flex-wrap">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-petrol-800">
                                    {{ r.user_name }}
                                    <span class="font-normal text-niebla-400">
                                        pide {{ r.days }} día{{ r.days === 1 ? '' : 's' }} de
                                        {{ typeLabel(r.type) }}
                                    </span>
                                </p>
                                <p class="text-xs text-niebla-400 mt-0.5">
                                    {{ rangeLabel(r) }}
                                    <span v-if="r.reason"> · {{ r.reason }}</span>
                                </p>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <AppButton
                                    variant="secondary"
                                    label="Rechazar"
                                    :icon="X"
                                    :loading="team.saving"
                                    @click="review(r, 'rejected')"
                                />
                                <AppButton
                                    label="Aprobar"
                                    :icon="Check"
                                    :loading="team.saving"
                                    @click="review(r, 'approved')"
                                />
                            </div>
                        </div>

                        <p
                            v-if="r.type === 'vacation' && availableFor(r.user_name) !== null"
                            class="text-[11px] text-niebla-400"
                        >
                            Le quedarían {{ availableFor(r.user_name) }} días disponibles al aprobar.
                        </p>

                        <!-- Evidencia adjunta -->
                        <div v-if="r.attachments?.length" class="flex flex-wrap gap-1.5">
                            <button
                                v-for="a in r.attachments"
                                :key="a.id"
                                type="button"
                                class="inline-flex items-center gap-1.5 text-[11px] font-semibold px-2.5 py-1 rounded-full bg-aguamarina-50 text-aguamarina-700 hover:bg-aguamarina-100 transition"
                                :title="`Descargar ${a.name}`"
                                @click="openAttachment(a)"
                            >
                                <AppIcon :name="Paperclip" :size="12" />
                                {{ a.name }}
                                <span class="opacity-60">{{ a.size_label }}</span>
                            </button>
                        </div>

                        <p
                            v-else-if="r.requires_evidence"
                            class="text-[11px] text-rose-600 flex items-center gap-1"
                        >
                            <AppIcon :name="AlertTriangle" :size="12" />
                            Llegó sin documento de respaldo
                        </p>
                    </div>
                </div>
            </div>

            <!-- Tarjetas del equipo -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="e in team.employees"
                    :key="e.id"
                    class="bg-white rounded-2xl border border-aguamarina-100 shadow-sm p-4 space-y-3"
                >
                    <div class="flex items-start gap-3">
                        <div
                            class="h-11 w-11 rounded-xl bg-aguamarina-100 flex items-center justify-center shrink-0"
                        >
                            <AppIcon :name="User" :size="20" class="text-aguamarina-600" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-petrol-800 truncate">{{ e.name }}</p>
                            <p class="text-xs text-niebla-400 truncate">
                                {{ roleLabel(e.roles) }}
                            </p>
                        </div>
                        <AppButton
                            variant="ghost"
                            :icon="Pencil"
                            :icon-size="16"
                            aria-label="Editar ficha"
                            @click="openEditor(e)"
                        />
                    </div>

                    <div v-if="!e.has_pin" class="flex items-center gap-1.5 text-[11px] text-amber-700">
                        <AppIcon :name="AlertTriangle" :size="13" />
                        Sin PIN: no puede entrar al portal
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div>
                            <p class="text-niebla-400 uppercase text-[10px] font-bold">Contratado</p>
                            <p class="text-petrol-700 font-semibold mt-0.5">
                                {{ formatDate(e.hired_at) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-niebla-400 uppercase text-[10px] font-bold">Antigüedad</p>
                            <p class="text-petrol-700 font-semibold mt-0.5">
                                {{ e.tenure || '—' }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="e.vacation?.available"
                        class="rounded-xl px-3 py-2 bg-aguamarina-50"
                    >
                        <div class="flex items-baseline justify-between gap-2">
                            <span class="text-[10px] uppercase font-bold text-niebla-400">
                                Vacaciones
                            </span>
                            <span class="text-sm font-bold text-petrol-800">
                                {{ e.vacation.available_days }}d
                            </span>
                        </div>
                        <p class="text-[10px] text-niebla-400 mt-0.5">
                            de {{ e.vacation.accrued }} devengadas · {{ e.vacation.used }} usadas
                        </p>
                    </div>
                    <div v-else class="rounded-xl px-3 py-2 bg-amber-50">
                        <p class="text-[11px] text-amber-700">
                            Falta la fecha de contratación para calcular el saldo
                        </p>
                    </div>

                    <div v-if="e.upcoming?.length" class="flex flex-wrap gap-1.5">
                        <span
                            v-for="u in e.upcoming"
                            :key="u.type"
                            class="inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full"
                            :class="u.type === 'birthday' ? 'bg-rose-50 text-rose-700' : 'bg-aguamarina-50 text-aguamarina-700'"
                        >
                            {{ u.label }} en {{ u.days_away }}d
                        </span>
                    </div>
                </div>
            </div>
        </template>

        <EmployeeProfileModal
            :open="Boolean(editing)"
            :employee="editing"
            :saving="team.saving"
            @close="editing = null"
            @saved="onSaved"
        />
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import {
    AlertTriangle,
    Check,
    Inbox,
    Paperclip,
    Pencil,
    RefreshCw,
    User,
    X,
} from 'lucide-vue-next';
import PageHeader from '../components/ui/PageHeader.vue';
import AppBadge from '../components/ui/AppBadge.vue';
import AppButton from '../components/ui/AppButton.vue';
import AppIcon from '../components/ui/AppIcon.vue';
import EmployeeProfileModal from '../components/employees/EmployeeProfileModal.vue';
import { useTeamStore } from '../stores/team';
import { useToastStore } from '../stores/toast';
import api from '../api';

const team = useTeamStore();
const toast = useToastStore();

const editing = ref(null);

const ROLE_LABELS = {
    admin: 'Administrador',
    cashier: 'Cajero',
    waiter: 'Mesero',
    kitchen: 'Cocina y Barra',
};

const TYPE_LABELS = {
    vacation: 'vacaciones',
    family_day: 'día de familia',
    birthday: 'cumpleaños',
    unpaid: 'permiso sin sueldo',
    sick_leave: 'permiso por salud',
    incapacity: 'incapacidad médica',
};

const roleLabel = (roles) =>
    (roles || []).map((r) => ROLE_LABELS[r] || r).join(' · ') || 'Sin rol';

const typeLabel = (type) => TYPE_LABELS[type] || type;

const formatDate = (value) => {
    if (!value) return '—';
    const [y, m, d] = String(value).split('-');
    return `${d}/${m}/${y}`;
};

const rangeLabel = (r) => {
    const start = formatDate(r.start_date);
    return r.start_date === r.end_date
        ? start
        : `${start} al ${formatDate(r.end_date)}`;
};

const load = () => team.load();

onMounted(load);

/** Saldo disponible de un empleado concreto, para la ayuda al aprobar. */
const availableFor = (name) => {
    const employee = team.employees.find((e) => e.name === name);
    return employee?.vacation?.available ? employee.vacation.available_days : null;
};

const openEditor = (employee) => {
    editing.value = employee;
};

const onSaved = () => {
    editing.value = null;
    toast.success('Ficha actualizada.');
};

const review = async (request, status) => {
    try {
        await team.review(request.id, status);
        toast.success(status === 'approved' ? 'Solicitud aprobada.' : 'Solicitud rechazada.');
    } catch (err) {
        toast.error(team.error);
    }
};

/**
 * Descarga un documento. Va por fetch y no por un <a href> porque el endpoint
 * pide el token de Sanctum en la cabecera, y un link plano no lo manda.
 */
const openAttachment = async (attachment) => {
    try {
        const res = await api.get(`/team/attachments/${attachment.id}`, {
            responseType: 'blob',
        });

        const url = URL.createObjectURL(res.data);
        const link = document.createElement('a');
        link.href = url;
        link.download = attachment.name;
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
    } catch (err) {
        toast.error('No se pudo abrir el documento.');
    }
};
</script>
