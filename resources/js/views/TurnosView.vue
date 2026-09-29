<template>
    <div class="p-6 max-w-6xl mx-auto space-y-6">
        <PageHeader
            :title="isAdmin ? 'Control de Meseros' : 'Turnos de Mesero'"
            :subtitle="
                isAdmin
                    ? 'Supervisión: quién está trabajando, qué rindió cada uno y gestión de PINs'
                    : 'Marca la entrada y la salida de cada persona, y controla lo que hizo en el turno'
            "
        >
            <template #actions>
                <AppButton
                    variant="secondary"
                    label="Actualizar"
                    :icon="RefreshCw"
                    :icon-size="18"
                    title="Actualizar"
                    @click="reload"
                />
            </template>
        </PageHeader>

        <!--
            El bloque de marcar turno es del mesero, no del admin: el admin no
            opera el dispositivo, supervisa. El apartado de arriba ya le alcanza
            para ver quién está trabajando y cerrarle el turno si se quedó
            abierto.
        -->
        <template v-if="!isAdmin">
            <!-- Turno de este dispositivo -->
            <div
                v-if="shiftStore.activeShift"
                class="bg-white rounded-2xl border border-aguamarina-200 shadow-card p-6"
            >
                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <div class="flex items-start gap-3">
                        <div
                            class="h-12 w-12 rounded-2xl bg-aguamarina-100 flex items-center justify-center shrink-0"
                        >
                            <AppIcon :name="UserCheck" :size="24" class="text-aguamarina-600" />
                        </div>
                        <div>
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-aguamarina-600"
                            >
                                Turno activo en este dispositivo
                            </p>
                            <h2 class="text-xl font-bold text-petrol-800">
                                {{ shiftStore.activeWorkerName }}
                            </h2>
                            <p class="text-sm text-niebla-400 mt-0.5">
                                Entrada {{ formatTime(shiftStore.activeShift.opened_at) }} ·
                                {{ formatMinutes(shiftStore.activeShift.worked_minutes) }}
                                trabajando
                            </p>
                        </div>
                    </div>

                    <AppButton
                        variant="secondary"
                        label="Cerrar turno"
                        :icon="LogOut"
                        :loading="shiftStore.saving"
                        @click="confirmClose"
                    />
                </div>
            </div>

            <!-- Sin turno: elegir persona -->
            <div v-else class="space-y-4">
                <div
                    class="bg-aguamarina-50 border border-aguamarina-200 rounded-2xl p-4 flex items-start gap-3"
                >
                    <AppIcon
                        :name="Info"
                        :size="18"
                        class="text-aguamarina-600 mt-0.5 shrink-0"
                    />
                    <p class="text-sm text-petrol-700">
                        Este dispositivo no tiene turno abierto. Elegí quién está trabajando e
                        ingresá su PIN de 4 dígitos para empezar. Nadie más puede marcar su turno sin
                        ese PIN.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <button
                        v-for="w in shiftStore.workers"
                        :key="w.id"
                        type="button"
                        class="text-left bg-white rounded-2xl border border-aguamarina-200 hover:border-aguamarina-500 hover:shadow-card transition p-4 flex items-center gap-3"
                        :disabled="shiftStore.saving"
                        @click="pickWorker(w)"
                    >
                        <div
                            class="h-11 w-11 rounded-xl bg-aguamarina-100 flex items-center justify-center shrink-0"
                        >
                            <AppIcon :name="User" :size="20" class="text-aguamarina-600" />
                        </div>
                        <div class="min-w-0">
                            <p class="font-bold text-petrol-800 truncate">{{ w.name }}</p>
                            <p class="text-xs text-niebla-400 truncate">
                                {{ w.phone || w.email }}
                            </p>
                        </div>
                    </button>
                </div>

                <p
                    v-if="!shiftStore.workers.length"
                    class="text-sm text-niebla-400 text-center py-8"
                >
                    Todavía no hay meseros con PIN configurado. Pídele al administrador que los
                    agregue.
                </p>
            </div>
        </template>

        <!-- Modal de PIN -->
        <AppModal
            :open="Boolean(worker)"
            :title="`Turno de ${worker?.name ?? ''}`"
            subtitle="Ingresá tu PIN de 4 dígitos"
            size="sm"
            @close="worker = null"
        >
            <div class="space-y-4">
                <div class="text-center py-2">
                    <p class="text-sm text-niebla-400">
                        Marcar el turno de <strong class="text-petrol-800">{{ worker?.name }}</strong>
                    </p>
                </div>

                <input
                    v-model="pin"
                    type="password"
                    inputmode="numeric"
                    maxlength="4"
                    placeholder="••••"
                    class="w-full text-center text-3xl tracking-[0.6em] rounded-xl border border-aguamarina-200 px-3 py-3 text-petrol-800 focus:outline-none focus:ring-2 focus:ring-aguamarina-500"
                    @keyup.enter="confirmOpen"
                />

                <p
                    v-if="shiftStore.pinError"
                    class="text-sm text-rose-600 bg-rose-50 border border-rose-200 rounded-lg px-3 py-2"
                >
                    {{ shiftStore.pinError }}
                </p>

                <AppButton
                    class="w-full"
                    label="Iniciar turno"
                    :icon="LogIn"
                    :loading="shiftStore.saving"
                    :disabled="pin.length !== 4"
                    @click="confirmOpen"
                />
            </div>
        </AppModal>

        <!-- Solo admin: control -->
        <template v-if="isAdmin">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Resumen por mesero -->
                <div class="bg-white rounded-2xl border border-aguamarina-100 shadow-sm">
                    <div class="px-5 py-4 border-b border-aguamarina-100">
                        <h3 class="font-bold text-petrol-800">Hoy por mesero</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="text-left text-niebla-400 border-b border-aguamarina-100">
                                    <th class="px-5 py-2.5 font-semibold">Mesero</th>
                                    <th class="px-3 py-2.5 font-semibold text-right">Horas</th>
                                    <th class="px-3 py-2.5 font-semibold text-right">Comandas</th>
                                    <th class="px-3 py-2.5 font-semibold text-right">Facturas</th>
                                    <th class="px-5 py-2.5 font-semibold text-right">Ventas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="row in summaryRows"
                                    :key="row.user_id"
                                    class="border-b border-aguamarina-50 last:border-0"
                                >
                                    <td class="px-5 py-2.5 text-petrol-800">
                                        {{ row.name }}
                                        <span
                                            v-if="row.turno_abierto"
                                            class="ml-1.5 inline-block h-1.5 w-1.5 rounded-full bg-aguamarina-500 align-middle"
                                        />
                                    </td>
                                    <td class="px-3 py-2.5 text-right text-niebla-400 tabular-nums">
                                        {{ row.horas }}h
                                    </td>
                                    <td class="px-3 py-2.5 text-right text-petrol-700 tabular-nums">
                                        {{ row.comandas }}
                                    </td>
                                    <td class="px-3 py-2.5 text-right text-petrol-700 tabular-nums">
                                        {{ row.facturas }}
                                    </td>
                                    <td class="px-5 py-2.5 text-right text-petrol-800 font-semibold tabular-nums">
                                        $ {{ money(row.ventas) }}
                                    </td>
                                </tr>
                                <tr v-if="!summaryRows.length">
                                    <td colspan="5" class="px-5 py-6 text-center text-niebla-300">
                                        Nadie ha marcado turno hoy.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Meseros -->
                <div class="bg-white rounded-2xl border border-aguamarina-100 shadow-sm">
                    <div class="px-5 py-4 border-b border-aguamarina-100 flex items-center justify-between">
                        <h3 class="font-bold text-petrol-800">Meseros</h3>
                        <AppButton
                            size="sm"
                            label="Agregar mesero"
                            :icon="UserPlus"
                            @click="showCreate = true"
                        />
                    </div>

                    <div class="divide-y divide-aguamarina-50">
                        <div
                            v-for="u in waiters"
                            :key="u.id"
                            class="px-5 py-3 flex items-center justify-between gap-3"
                        >
                            <div class="min-w-0">
                                <p class="font-semibold text-petrol-800 text-sm truncate">{{ u.name }}</p>
                                <p class="text-xs text-niebla-400 truncate">
                                    {{ u.email }}
                                    <span v-if="!u.has_pin" class="text-amber-600"> · sin PIN</span>
                                </p>
                            </div>
                            <AppButton
                                size="sm"
                                variant="secondary"
                                label="Cambiar PIN"
                                :icon="KeyRound"
                                @click="pickReset(u)"
                            />
                        </div>
                        <p v-if="!waiters.length" class="px-5 py-6 text-center text-sm text-niebla-300">
                            Todavía no hay meseros registrados.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Turnos abiertos del día -->
            <div v-if="openShifts.length" class="bg-white rounded-2xl border border-aguamarina-100 shadow-sm">
                <div class="px-5 py-4 border-b border-aguamarina-100">
                    <h3 class="font-bold text-petrol-800">Turnos abiertos ahora</h3>
                    <p class="text-xs text-niebla-400">
                        Si alguien se quedó con el turno abierto, podés cerrarlo desde acá.
                    </p>
                </div>
                <div class="divide-y divide-aguamarina-50">
                    <div
                        v-for="s in openShifts"
                        :key="s.id"
                        class="px-5 py-3 flex items-center justify-between gap-3"
                    >
                        <div>
                            <p class="font-semibold text-petrol-800 text-sm">{{ s.user?.name }}</p>
                            <p class="text-xs text-niebla-400">
                                Desde {{ formatTime(s.opened_at) }} ·
                                {{ formatMinutes(s.worked_minutes) }}
                            </p>
                        </div>
                        <AppButton
                            size="sm"
                            variant="secondary"
                            label="Cerrar"
                            :icon="Lock"
                            @click="forceClose(s)"
                        />
                    </div>
                </div>
            </div>
        </template>

        <!-- Modal agregar mesero -->
        <AppModal
            :open="showCreate"
            title="Agregar mesero"
            subtitle="El email se arma a partir del nombre"
            size="sm"
            @close="showCreate = false"
        >
            <div class="space-y-3">
                <AppInput v-model="form.name" label="Nombre" placeholder="Juan Pérez" />
                <AppInput
                    v-model="form.pin"
                    label="PIN de 4 dígitos"
                    placeholder="1234"
                    inputmode="numeric"
                    maxlength="4"
                />
                <AppInput v-model="form.phone" label="Teléfono (opcional)" placeholder="300 123 4567" />

                <p
                    v-if="createError"
                    class="text-sm text-rose-600 bg-rose-50 border border-rose-200 rounded-lg px-3 py-2"
                >
                    {{ createError }}
                </p>

                <AppButton
                    class="w-full"
                    label="Crear mesero"
                    :icon="UserPlus"
                    :loading="creating"
                    :disabled="!form.name || form.pin.length !== 4"
                    @click="submitCreate"
                />
            </div>
        </AppModal>

        <!-- Modal cambiar PIN -->
        <AppModal
            :open="Boolean(resetTarget)"
            :title="`Cambiar PIN de ${resetTarget?.name ?? ''}`"
            size="sm"
            @close="resetTarget = null"
        >
            <div class="space-y-3">
                <AppInput
                    v-model="newPin"
                    label="Nuevo PIN de 4 dígitos"
                    placeholder="1234"
                    inputmode="numeric"
                    maxlength="4"
                />
                <p
                    v-if="resetError"
                    class="text-sm text-rose-600 bg-rose-50 border border-rose-200 rounded-lg px-3 py-2"
                >
                    {{ resetError }}
                </p>
                <AppButton
                    class="w-full"
                    label="Guardar PIN"
                    :icon="KeyRound"
                    :loading="resetting"
                    :disabled="newPin.length !== 4"
                    @click="submitReset"
                />
            </div>
        </AppModal>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import {
    Info,
    KeyRound,
    Lock,
    LogIn,
    LogOut,
    RefreshCw,
    User,
    UserCheck,
    UserPlus,
} from 'lucide-vue-next';
import PageHeader from '../components/ui/PageHeader.vue';
import AppButton from '../components/ui/AppButton.vue';
import AppIcon from '../components/ui/AppIcon.vue';
import AppInput from '../components/ui/AppInput.vue';
import AppModal from '../components/ui/AppModal.vue';
import { useAuthStore } from '../stores/auth';
import { useShiftStore } from '../stores/shifts';

const authStore = useAuthStore();
const shiftStore = useShiftStore();

const isAdmin = computed(() => authStore.isAdmin);

const worker = ref(null);
const pin = ref('');

const showCreate = ref(false);
const creating = ref(false);
const createError = ref('');
const form = ref({ name: '', pin: '', phone: '' });

const resetTarget = ref(null);
const resetting = ref(false);
const resetError = ref('');
const newPin = ref('');

const waiters = computed(() => shiftStore.users.filter(u => u.roles.includes('waiter')));

// Solo los que tienen algo que mostrar. El admin ve a todos.
const summaryRows = computed(() => shiftStore.summary.filter(r => r.turnos > 0 || r.comandas > 0));
const openShifts = computed(() => shiftStore.shifts.filter(s => s.status === 'open'));

const money = (val) => Number(val || 0).toLocaleString('es-CO');
const formatTime = (iso) => new Date(iso).toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' });
const formatMinutes = (mins) => {
    const h = Math.floor(mins / 60);
    const m = mins % 60;
    return h > 0 ? `${h}h ${m}m` : `${m}m`;
};

const reload = async () => {
    await shiftStore.loadActive();
    await shiftStore.loadShifts();
    await shiftStore.loadWorkers();

    if (isAdmin.value) {
        await shiftStore.loadSummary();
        await shiftStore.loadUsers();
    }
};

const pickWorker = (w) => {
    pin.value = '';
    shiftStore.pinError = '';
    worker.value = w;
};

const confirmOpen = async () => {
    if (!worker.value || pin.value.length !== 4) return;

    try {
        await shiftStore.open(worker.value.id, pin.value);
        worker.value = null;
        pin.value = '';
        await reload();
    } catch (e) {
        // El mensaje ya quedo en shiftStore.pinError.
    }
};

const confirmClose = async () => {
    try {
        await shiftStore.close();
        await reload();
    } catch (e) {
        // Mensaje en shiftStore.error.
    }
};

const forceClose = async (s) => {
    try {
        await shiftStore.closeOther(s.id, 'Cerrado por el administrador');
    } catch (e) {
        // Silencioso: la tabla se refresca igual.
    }
};

const submitCreate = async () => {
    creating.value = true;
    createError.value = '';

    try {
        await shiftStore.createWorker({
            name: form.value.name.trim(),
            pin: form.value.pin,
            phone: form.value.phone.trim() || null,
        });
        showCreate.value = false;
        form.value = { name: '', pin: '', phone: '' };
    } catch (err) {
        const first = Object.values(err.response?.data?.errors || {})[0];
        createError.value = Array.isArray(first) ? first[0] : (err.response?.data?.message || 'No se pudo crear el mesero.');
    } finally {
        creating.value = false;
    }
};

const pickReset = (u) => {
    newPin.value = '';
    resetError.value = '';
    resetTarget.value = u;
};

const submitReset = async () => {
    if (!resetTarget.value || newPin.value.length !== 4) return;

    resetting.value = true;
    resetError.value = '';

    try {
        await shiftStore.resetPin(resetTarget.value.id, newPin.value);
        resetTarget.value = null;
    } catch (err) {
        const first = Object.values(err.response?.data?.errors || {})[0];
        resetError.value = Array.isArray(first) ? first[0] : (err.response?.data?.message || 'No se pudo cambiar el PIN.');
    } finally {
        resetting.value = false;
    }
};

onMounted(reload);
</script>
