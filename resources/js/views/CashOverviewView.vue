<template>
    <div class="p-6 max-w-6xl mx-auto space-y-6">
        <PageHeader
            title="Estado de Caja"
            subtitle="Saldo de la gaveta del local, movimientos del día e historial de arqueos"
        >
            <template #actions>
                <AppButton variant="secondary" label="Actualizar" :icon="RefreshCw" @click="load" />
                <AppButton
                    label="Registrar retiro"
                    :icon="ArrowDownToLine"
                    @click="openWithdrawalModal"
                />
            </template>
        </PageHeader>

        <div v-if="loading" class="space-y-4">
            <div class="bg-white rounded-2xl border border-aguamarina-100 shadow-sm p-6">
                <div class="h-4 w-56 rounded bg-aguamarina-50 animate-pulse" />
                <div class="h-8 w-40 rounded bg-aguamarina-50 animate-pulse mt-3" />
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div
                    v-for="n in 4"
                    :key="n"
                    class="h-20 rounded-2xl bg-aguamarina-50 animate-pulse"
                />
            </div>
        </div>

        <div v-else class="space-y-6">
            <!-- Saldo de la gaveta -->
            <div
                class="rounded-2xl p-6 text-white shadow-xl flex flex-col md:flex-row md:items-center md:justify-between gap-5"
                :class="isOpen ? 'bg-petrol-800' : 'bg-petrol-700'"
            >
                <div>
                    <div class="flex items-center gap-2">
                        <AppIcon :name="isOpen ? Lock : LockOpen" :size="14" />
                        <span class="text-xs font-bold uppercase tracking-wider text-aguamarina-300">
                            {{ isOpen ? 'Gaveta abierta' : 'Gaveta cerrada' }}
                        </span>
                    </div>
                    <p class="text-xs text-niebla-400 mt-1">
                        {{
                            isOpen
                                ? `En turno de ${holderName || 'un cajero'}`
                                : 'Sin turno abierto. El saldo es el último conteo del arqueo.'
                        }}
                    </p>
                    <p class="text-4xl font-bold mt-2 text-aguamarina-200">
                        ${{ formatMoney(balance) }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-petrol-700 px-4 py-3 rounded-xl border border-petrol-600">
                        <span class="text-[10px] uppercase font-bold text-niebla-400 block">
                            Efectivo hoy
                        </span>
                        <span class="text-lg font-bold text-emerald-400">
                            ${{ formatMoney(today.cash_sales) }}
                        </span>
                    </div>
                    <div class="bg-petrol-700 px-4 py-3 rounded-xl border border-petrol-600">
                        <span class="text-[10px] uppercase font-bold text-niebla-400 block">
                            Salió hoy
                        </span>
                        <span class="text-lg font-bold text-rose-400">
                            ${{ formatMoney(totalOutToday) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- KPIs del día -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <StatCard
                    label="Ventas en efectivo"
                    :value="`$${formatMoney(today.cash_sales)}`"
                    :icon="Banknote"
                    tone="emerald"
                />
                <StatCard
                    label="Ventas por datáfono / digital"
                    :value="`$${formatMoney(today.card_sales)}`"
                    :icon="CreditCard"
                    tone="petrol"
                />
                <StatCard
                    label="Gastos menores"
                    :value="`$${formatMoney(today.cash_out)}`"
                    :icon="TrendingDown"
                    tone="rose"
                />
                <StatCard
                    label="Retiros de gaveta"
                    :value="`$${formatMoney(today.withdrawals)}`"
                    :icon="ArrowDownToLine"
                    tone="amber"
                />
            </div>

            <!-- Movimientos y retiros del día -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-2xl border border-aguamarina-100 shadow-sm p-5 space-y-4">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="font-bold text-sm text-petrol-800 flex items-center gap-2">
                            <AppIcon :name="ArrowLeftRight" :size="16" />
                            Gastos menores del día
                        </h3>
                        <AppButton
                            variant="secondary"
                            label="Registrar"
                            :icon="Plus"
                            :disabled="!isOpen"
                            @click="openMovementModal"
                        />
                    </div>

                    <p v-if="!isOpen" class="text-xs text-amber-700 bg-amber-50 rounded-xl px-3 py-2">
                        No hay turno de caja abierto. Para mover efectivo con la gaveta cerrada usá un
                        retiro de gaveta.
                    </p>

                    <AppEmptyState
                        v-if="!movements.length"
                        :icon="Receipt"
                        title="Sin movimientos"
                        description="No hay gastos ni ingresos menores registrados hoy."
                    />

                    <div v-else class="divide-y divide-aguamarina-100">
                        <div
                            v-for="mov in movements"
                            :key="mov.id"
                            class="py-2.5 flex items-center justify-between gap-3 text-xs"
                        >
                            <div class="min-w-0">
                                <span class="font-semibold text-petrol-700 block truncate">
                                    {{ mov.description }}
                                </span>
                                <span class="text-[10px] text-niebla-300 uppercase font-bold">
                                    {{ movementCategoryLabel(mov.category) }} ·
                                    {{ mov.user?.name || 'Sin autor' }}
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

                <div class="bg-white rounded-2xl border border-aguamarina-100 shadow-sm p-5 space-y-4">
                    <h3 class="font-bold text-sm text-petrol-800 flex items-center gap-2">
                        <AppIcon :name="ArrowDownToLine" :size="16" />
                        Retiros de gaveta del día
                    </h3>

                    <AppEmptyState
                        v-if="!withdrawals.length"
                        :icon="ArrowDownToLine"
                        title="Sin retiros"
                        description="No sacaste dinero de la gaveta hoy."
                    />

                    <div v-else class="divide-y divide-aguamarina-100">
                        <div
                            v-for="wd in withdrawals"
                            :key="wd.id"
                            class="py-2.5 flex items-center justify-between gap-3 text-xs"
                        >
                            <div class="min-w-0">
                                <span class="font-semibold text-petrol-700 block truncate">
                                    {{ wd.reason }}
                                </span>
                                <span class="text-[10px] text-niebla-300 uppercase font-bold">
                                    {{ withdrawalCategoryLabel(wd.category) }} ·
                                    {{ wd.user?.name || 'Administrador' }}
                                    <template v-if="wd.receipt_number">
                                        · Recibo {{ wd.receipt_number }}
                                    </template>
                                </span>
                            </div>
                            <span class="font-bold shrink-0 text-amber-600">
                                -${{ formatMoney(wd.amount) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Historial de arqueos -->
            <div class="bg-white rounded-2xl border border-aguamarina-100 shadow-sm p-5 space-y-4">
                <h3 class="font-bold text-sm text-petrol-800 flex items-center gap-2">
                    <AppIcon :name="ClipboardList" :size="16" />
                    Historial de arqueos
                </h3>

                <AppEmptyState
                    v-if="!history.length"
                    :icon="ClipboardList"
                    title="Sin arqueos registrados"
                    description="Todavía no se ha cerrado ningún turno de caja."
                />

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="text-left text-niebla-400 uppercase text-[10px] font-bold">
                                <th class="py-2 pr-3">Cerró</th>
                                <th class="py-2 pr-3">Cajero</th>
                                <th class="py-2 pr-3 text-right">Base</th>
                                <th class="py-2 pr-3 text-right">Esperado</th>
                                <th class="py-2 pr-3 text-right">Físico</th>
                                <th class="py-2 text-right">Diferencia</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-aguamarina-100">
                            <tr v-for="h in history" :key="h.id">
                                <td class="py-2.5 pr-3 text-niebla-500 whitespace-nowrap">
                                    {{ formatDate(h.closed_at) }}
                                </td>
                                <td class="py-2.5 pr-3 text-petrol-700 font-medium">
                                    {{ h.user_name }}
                                </td>
                                <td class="py-2.5 pr-3 text-right text-niebla-500">
                                    ${{ formatMoney(h.opening_balance) }}
                                </td>
                                <td class="py-2.5 pr-3 text-right text-petrol-700 font-medium">
                                    ${{ formatMoney(h.expected_balance) }}
                                </td>
                                <td class="py-2.5 pr-3 text-right text-petrol-700 font-medium">
                                    ${{ formatMoney(h.actual_balance) }}
                                </td>
                                <td
                                    class="py-2.5 text-right font-bold"
                                    :class="differenceClass(h.difference)"
                                >
                                    {{ signedMoney(h.difference) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal de retiro de gaveta -->
        <AppModal
            :open="isWithdrawalModalOpen"
            title="Retiro de gaveta"
            subtitle="Dinero que sale de la gaveta para pagos del local. Queda registrado con monto y motivo."
            size="sm"
            @close="isWithdrawalModalOpen = false"
        >
            <div class="space-y-4">
                <p class="text-xs text-niebla-400 bg-aguamarina-50 rounded-xl px-3 py-2">
                    Saldo disponible en la gaveta:
                    <span class="font-bold text-petrol-800">${{ formatMoney(balance) }}</span>
                </p>

                <AppInput
                    v-model.number="withdrawal.amount"
                    label="Monto a retirar ($)"
                    type="number"
                    placeholder="0"
                    required
                />

                <AppSelect v-model="withdrawal.category" label="Motivo del pago" required>
                    <option value="payroll">Nómina / pago de empleados</option>
                    <option value="vacation">Vacaciones / prestaciones</option>
                    <option value="utilities">Recibos de la heladería (luz, agua, internet)</option>
                    <option value="merchandise">Pago de mercancía / proveedores</option>
                    <option value="other">Otro</option>
                </AppSelect>

                <AppInput
                    v-model="withdrawal.reason"
                    label="Detalle del pago"
                    placeholder="ej. Nómina primera quincena de los empleados"
                    required
                />

                <AppInput
                    v-model="withdrawal.receipt_number"
                    label="Recibo / factura (opcional)"
                    placeholder="ej. R-004512"
                />

                <AppTextarea
                    v-model="withdrawal.notes"
                    label="Notas (opcional)"
                    :rows="2"
                    placeholder="Observaciones del pago"
                />
            </div>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <AppButton
                        variant="secondary"
                        label="Cancelar"
                        @click="isWithdrawalModalOpen = false"
                    />
                    <AppButton
                        label="Registrar retiro"
                        :loading="saving"
                        @click="submitWithdrawal"
                    />
                </div>
            </template>
        </AppModal>

        <!-- Modal de gasto menor -->
        <AppModal
            :open="isMovementModalOpen"
            title="Gasto menor de caja"
            subtitle="Se registra sobre el turno que tiene abierto el cajero"
            size="sm"
            @close="isMovementModalOpen = false"
        >
            <div class="space-y-4">
                <AppSelect v-model="movement.type" label="Tipo" required>
                    <option value="cash_out">Salida / egreso</option>
                    <option value="cash_in">Entrada / ingreso</option>
                </AppSelect>

                <AppSelect v-model="movement.category" label="Categoría" required>
                    <option value="operating_expense">Gasto operativo</option>
                    <option value="purchase">Compra de insumo urgente</option>
                    <option value="payroll">Nómina / anticipo</option>
                    <option value="other">Otro</option>
                </AppSelect>

                <AppInput v-model.number="movement.amount" label="Monto ($)" type="number" />

                <AppInput
                    v-model="movement.description"
                    label="Descripción"
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
                    <AppButton label="Guardar" :loading="saving" @click="submitMovement" />
                </div>
            </template>
        </AppModal>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import {
    ArrowDownToLine,
    ArrowLeftRight,
    Banknote,
    ClipboardList,
    CreditCard,
    Lock,
    LockOpen,
    Plus,
    Receipt,
    RefreshCw,
    TrendingDown,
} from 'lucide-vue-next';
import PageHeader from '../components/ui/PageHeader.vue';
import AppButton from '../components/ui/AppButton.vue';
import AppEmptyState from '../components/ui/AppEmptyState.vue';
import AppIcon from '../components/ui/AppIcon.vue';
import AppInput from '../components/ui/AppInput.vue';
import AppModal from '../components/ui/AppModal.vue';
import AppSelect from '../components/ui/AppSelect.vue';
import AppTextarea from '../components/ui/AppTextarea.vue';
import StatCard from '../components/ui/StatCard.vue';
import { useCashStore } from '../stores/cash';
import { useToastStore } from '../stores/toast';

const cashStore = useCashStore();
const toast = useToastStore();

const loading = computed(() => cashStore.loading);
const saving = computed(() => cashStore.saving);
const balance = computed(() => cashStore.balance);
const isOpen = computed(() => cashStore.isOpen);
const holderName = computed(() => cashStore.holderName);
const today = computed(() => cashStore.today);
const movements = computed(() => cashStore.movements);
const withdrawals = computed(() => cashStore.withdrawals);
const history = computed(() => cashStore.history);
const totalOutToday = computed(() => cashStore.totalOutToday);

const isWithdrawalModalOpen = ref(false);
const isMovementModalOpen = ref(false);

const withdrawal = ref({
    amount: 0,
    category: 'payroll',
    reason: '',
    receipt_number: '',
    notes: '',
});

const movement = ref({
    type: 'cash_out',
    category: 'operating_expense',
    amount: 0,
    description: '',
});

const formatMoney = (val) => Number(val || 0).toLocaleString('es-CO');

const formatDate = (iso) => {
    if (!iso) return '';
    return new Date(iso).toLocaleString('es-CO', {
        day: '2-digit',
        month: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const signedMoney = (val) => {
    const n = Number(val || 0);
    return `${n > 0 ? '+' : ''}$${formatMoney(n)}`;
};

const differenceClass = (val) => {
    const n = Number(val || 0);
    if (n === 0) return 'text-niebla-400';
    return n > 0 ? 'text-emerald-600' : 'text-rose-600';
};

const movementCategoryLabel = (cat) =>
    ({
        operating_expense: 'Gasto operativo',
        purchase: 'Compra',
        payroll: 'Nómina',
        other: 'Otro',
    }[cat] || cat);

const withdrawalCategoryLabel = (cat) =>
    ({
        payroll: 'Nómina',
        vacation: 'Vacaciones',
        utilities: 'Servicios',
        merchandise: 'Mercancía',
        other: 'Otro',
    }[cat] || cat);

const load = () => cashStore.load();

onMounted(load);

const openWithdrawalModal = () => {
    withdrawal.value = {
        amount: 0,
        category: 'payroll',
        reason: '',
        receipt_number: '',
        notes: '',
    };
    isWithdrawalModalOpen.value = true;
};

const submitWithdrawal = async () => {
    if (!withdrawal.value.reason.trim()) {
        toast.error('Escribí para qué se sacó el dinero.');
        return;
    }
    if (Number(withdrawal.value.amount) <= 0) {
        toast.error('El monto debe ser mayor a cero.');
        return;
    }

    try {
        await cashStore.withdraw({
            amount: Number(withdrawal.value.amount),
            category: withdrawal.value.category,
            reason: withdrawal.value.reason.trim(),
            receipt_number: withdrawal.value.receipt_number || null,
            notes: withdrawal.value.notes || null,
        });
        isWithdrawalModalOpen.value = false;
        toast.success('Retiro registrado. El saldo de la gaveta se actualizó.');
    } catch (err) {
        toast.error(cashStore.error || 'No se pudo registrar el retiro.');
    }
};

const openMovementModal = () => {
    movement.value = {
        type: 'cash_out',
        category: 'operating_expense',
        amount: 0,
        description: '',
    };
    isMovementModalOpen.value = true;
};

const submitMovement = async () => {
    try {
        await cashStore.addMovement({
            ...movement.value,
            amount: Number(movement.value.amount),
        });
        isMovementModalOpen.value = false;
        toast.success('Movimiento registrado.');
    } catch (err) {
        toast.error(cashStore.error || 'No se pudo registrar el movimiento.');
    }
};
</script>
