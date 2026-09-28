<template>
    <div class="p-6 max-w-5xl mx-auto space-y-6">
        <PageHeader
            title="Turnos & Cierre de Caja"
            subtitle="Apertura, control de entradas/salidas de efectivo y arqueo diario"
        />

        <!-- Sin turno abierto -->
        <div
            v-if="!currentSession"
            class="bg-white rounded-2xl p-8 border border-aguamarina-100 shadow-sm text-center max-w-lg mx-auto space-y-4"
        >
            <div
                class="w-16 h-16 bg-aguamarina-50 text-aguamarina-600 rounded-2xl flex items-center justify-center mx-auto"
            >
                <AppIcon :name="Lock" :size="30" />
            </div>
            <h3 class="text-lg font-bold text-petrol-800">No hay turno de caja abierto</h3>
            <p class="text-xs text-niebla-400">
                Contá el efectivo que hay en la gaveta e iniciá el turno. Ese monto es la base sobre
                la que se calcula el arqueo.
            </p>

            <p
                v-if="drawerBalance > 0"
                class="text-xs text-petrol-700 bg-aguamarina-50 rounded-xl px-3 py-2 text-left"
            >
                El último conteo dejó <span class="font-bold">${{ formatMoney(drawerBalance) }}</span>
                en la gaveta. Confirmalo o corregilo abajo según lo que contaste.
            </p>

            <div class="pt-2 text-left space-y-3">
                <AppInput
                    v-model.number="openingAmount"
                    label="Efectivo contado en la gaveta ($)"
                    type="number"
                    placeholder="ej. 50000"
                />
                <AppInput
                    v-model="openingNotes"
                    label="Observaciones"
                    placeholder="Turno mañana / Cajero principal"
                />

                <AppButton
                    class="w-full"
                    label="Abrir turno de caja"
                    :icon="LockOpen"
                    @click="openRegister"
                />
            </div>
        </div>

        <!-- Turno activo -->
        <div v-else class="space-y-6">
            <div
                class="bg-petrol-800 text-white p-6 rounded-2xl shadow-xl flex flex-col md:flex-row md:items-center md:justify-between gap-4"
            >
                <div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping" />
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">
                            Turno activo
                        </span>
                    </div>
                    <h2 class="text-xl font-bold mt-1">
                        Caja abierta por {{ currentSession.user?.name || 'Tú' }}
                    </h2>
                    <p class="text-xs text-niebla-400 mt-0.5">
                        Desde: {{ currentSession.opened_at }}
                    </p>
                </div>

                <div class="flex items-center gap-3 flex-wrap">
                    <div class="bg-petrol-700 px-4 py-3 rounded-xl border border-petrol-600">
                        <span class="text-[10px] uppercase font-bold text-niebla-400 block">
                            Saldo en gaveta
                        </span>
                        <span class="text-lg font-bold text-aguamarina-300">
                            ${{ formatMoney(drawerBalance) }}
                        </span>
                    </div>

                    <div class="bg-petrol-700 px-4 py-3 rounded-xl border border-petrol-600">
                        <span class="text-[10px] uppercase font-bold text-niebla-400 block">
                            Base de apertura
                        </span>
                        <span class="text-lg font-bold text-aguamarina-300">
                            ${{ formatMoney(currentSession.opening_balance) }}
                        </span>
                    </div>

                    <AppButton
                        variant="secondary"
                        label="Ingreso / Egreso"
                        :icon="ArrowLeftRight"
                        @click="isMovementModalOpen = true"
                    />

                    <AppButton
                        variant="danger"
                        label="Cerrar turno"
                        :icon="Lock"
                        @click="isCloseModalOpen = true"
                    />
                </div>
            </div>

            <!-- Movimientos -->
            <div class="bg-white rounded-2xl p-5 border border-aguamarina-100 shadow-sm space-y-3">
                <h3 class="font-bold text-sm text-petrol-800">
                    Movimientos de caja menor (gastos / entradas)
                </h3>

                <div v-if="!currentSession.movements?.length" class="text-center py-6">
                    <AppEmptyState
                        :icon="Receipt"
                        title="Sin movimientos"
                        description="No hay movimientos manuales registrados en este turno."
                    />
                </div>

                <div v-else class="divide-y divide-aguamarina-100">
                    <div
                        v-for="mov in currentSession.movements"
                        :key="mov.id"
                        class="py-3 flex justify-between items-center text-xs gap-3"
                    >
                        <div class="min-w-0">
                            <span class="font-semibold text-petrol-700 block">{{ mov.description }}</span>
                            <span class="text-[10px] text-niebla-300 uppercase font-bold">
                                Cat: {{ mov.category }}
                            </span>
                        </div>
                        <span
                            class="font-bold shrink-0"
                            :class="mov.type === 'cash_in' ? 'text-emerald-600' : 'text-rose-600'"
                        >
                            {{ mov.type === 'cash_in' ? '+' : '-' }}${{ formatMoney(mov.amount) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de movimiento -->
        <AppModal
            :open="isMovementModalOpen"
            title="Registrar entrada / salida"
            size="sm"
            @close="isMovementModalOpen = false"
        >
            <div class="space-y-4">
                <AppSelect v-model="newMovement.type" label="Tipo" required>
                    <option value="cash_out">Salida / egreso (gasto o compra)</option>
                    <option value="cash_in">Entrada / ingreso adicional</option>
                </AppSelect>

                <AppSelect v-model="newMovement.category" label="Categoría" required>
                    <option value="operating_expense">Gasto operativo</option>
                    <option value="purchase">Compra de insumo urgente</option>
                    <option value="payroll">Nómina / anticipo</option>
                    <option value="other">Otro</option>
                </AppSelect>

                <AppInput v-model.number="newMovement.amount" label="Monto ($)" type="number" />
                <AppInput
                    v-model="newMovement.description"
                    label="Descripción / justificación"
                    placeholder="ej. Compra servilletas y vasos"
                />
            </div>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <AppButton
                        variant="secondary"
                        label="Cancelar"
                        @click="isMovementModalOpen = false"
                    />
                    <AppButton label="Guardar" @click="submitMovement" />
                </div>
            </template>
        </AppModal>

        <!-- Modal de arqueo -->
        <AppModal
            :open="isCloseModalOpen"
            title="Arqueo y cierre de turno de caja"
            subtitle="Cuenta el dinero físico total en la gaveta e ingrésalo a continuación"
            size="sm"
            @close="isCloseModalOpen = false"
        >
            <AppInput
                v-model.number="actualCloseAmount"
                label="Efectivo físico en gaveta ($)"
                type="number"
                placeholder="0"
            />

            <template #footer>
                <div class="flex justify-end gap-2">
                    <AppButton
                        variant="secondary"
                        label="Cancelar"
                        @click="isCloseModalOpen = false"
                    />
                    <AppButton
                        variant="danger"
                        label="Confirmar arqueo & cerrar"
                        @click="submitCloseRegister"
                    />
                </div>
            </template>
        </AppModal>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { ArrowLeftRight, Lock, LockOpen, Receipt } from 'lucide-vue-next';
import PageHeader from '../components/ui/PageHeader.vue';
import AppButton from '../components/ui/AppButton.vue';
import AppEmptyState from '../components/ui/AppEmptyState.vue';
import AppIcon from '../components/ui/AppIcon.vue';
import AppInput from '../components/ui/AppInput.vue';
import AppModal from '../components/ui/AppModal.vue';
import AppSelect from '../components/ui/AppSelect.vue';
import api from '../api';

const currentSession = ref(null);
const openingAmount = ref(50000);
const openingNotes = ref('');

const isMovementModalOpen = ref(false);
const newMovement = ref({
    type: 'cash_out',
    category: 'purchase',
    amount: 0,
    description: '',
});

const isCloseModalOpen = ref(false);
const actualCloseAmount = ref(0);

/** Saldo real de la gaveta: base + efectivo cobrado + entradas - salidas - retiros. */
const drawerBalance = ref(0);

const formatMoney = (val) => Number(val || 0).toLocaleString('es-CO');

const loadBalance = async () => {
    try {
        const res = await api.get('/cash-register/balance');
        drawerBalance.value = res.data.data.balance;
    } catch (err) {
        console.error(err);
    }
};

const loadCurrent = async () => {
    try {
        const res = await api.get('/cash-register/current');
        currentSession.value = res.data.data;
    } catch (err) {
        console.error(err);
    } finally {
        await loadBalance();
    }
};

onMounted(async () => {
    await loadCurrent();
    // El conteo previo se propone como base: el cajero confirma o corrige.
    if (!currentSession.value && drawerBalance.value > 0) {
        openingAmount.value = drawerBalance.value;
    }
});

const openRegister = async () => {
    try {
        const res = await api.post('/cash-register/open', {
            opening_balance: openingAmount.value,
            notes: openingNotes.value,
        });
        currentSession.value = res.data.data;
        await loadBalance();
    } catch (err) {
        alert(err.response?.data?.message || 'Error al abrir caja');
    }
};

const submitMovement = async () => {
    try {
        await api.post('/cash-register/movements', newMovement.value);
        isMovementModalOpen.value = false;
        newMovement.value = { type: 'cash_out', category: 'purchase', amount: 0, description: '' };
        loadCurrent();
    } catch (err) {
        alert(err.response?.data?.message || 'Error al registrar movimiento');
    }
};

const submitCloseRegister = async () => {
    try {
        const res = await api.post('/cash-register/close', {
            actual_balance: actualCloseAmount.value,
        });
        const closed = res.data.data;
        alert(
            `Caja cerrada. Saldo esperado: $${formatMoney(closed.expected_balance)}, ` +
                `Físico: $${formatMoney(closed.actual_balance)}, ` +
                `Diferencia: $${formatMoney(closed.difference)}`
        );
        isCloseModalOpen.value = false;
        currentSession.value = null;
    } catch (err) {
        alert(err.response?.data?.message || 'Error al cerrar caja');
    }
};
</script>
