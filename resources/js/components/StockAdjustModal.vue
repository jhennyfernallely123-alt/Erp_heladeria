<template>
    <AppModal
        :open="open"
        title="Ajustar stock"
        :subtitle="stock ? `${stock.product?.name}${stock.variant ? ` · ${stock.variant.name}` : ''}` : ''"
        size="sm"
        @close="emit('close')"
    >
        <div class="space-y-4">
            <div
                class="flex items-center justify-between rounded-xl bg-aguamarina-50 border border-aguamarina-100 px-4 py-3"
            >
                <div>
                    <p class="text-xs text-niebla-400">Cantidad actual</p>
                    <p class="text-lg font-bold text-petrol-800">{{ formatNumber(currentQuantity) }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-niebla-400">Stock mínimo</p>
                    <p class="text-lg font-bold text-petrol-700">{{ formatNumber(minAlert) }}</p>
                </div>
            </div>

            <AppInput
                v-model="newQuantity"
                label="Nueva cantidad"
                type="number"
                :required="true"
                :error="firstError('new_quantity')"
            />

            <AppSelect v-model="type" label="Tipo de movimiento" :required="true" :error="firstError('type')">
                <option value="in">Entrada</option>
                <option value="out">Salida</option>
                <option value="adjustment">Ajuste</option>
            </AppSelect>

            <AppInput
                v-model="reason"
                label="Motivo"
                placeholder="Recepción de mercadería"
                :required="true"
                :error="firstError('reason')"
            />

            <div
                v-if="delta !== 0"
                class="rounded-xl px-4 py-3 text-sm"
                :class="delta > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'"
            >
                El stock {{ delta > 0 ? 'aumentará' : 'disminuirá' }}
                {{ formatNumber(Math.abs(delta)) }} {{ unitLabel }}.
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <AppButton variant="secondary" label="Cancelar" @click="emit('close')" />
                <AppButton label="Guardar ajuste" :loading="inventoryStore.saving" @click="save" />
            </div>
        </template>
    </AppModal>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import AppModal from './ui/AppModal.vue';
import AppButton from './ui/AppButton.vue';
import AppInput from './ui/AppInput.vue';
import AppSelect from './ui/AppSelect.vue';
import { useInventoryStore } from '../stores/inventory';
import { useToastStore } from '../stores/toast';

const props = defineProps({
    open: { type: Boolean, default: false },
    stock: { type: Object, default: null },
});

const emit = defineEmits(['close', 'saved']);

const inventoryStore = useInventoryStore();
const toastStore = useToastStore();

const newQuantity = ref(0);
const type = ref('in');
const reason = ref('');
const errors = ref({});

const firstError = (field) => (errors.value[field]?.[0] || '');

const formatNumber = (value) =>
    Number(value || 0).toLocaleString('es-CO', { maximumFractionDigits: 3 });

const currentQuantity = computed(() => (props.stock ? Number(props.stock.quantity) : 0));
const minAlert = computed(() => (props.stock ? Number(props.stock.min_alert) : 0));
const unitLabel = computed(() => (props.stock?.stock_type === 'bulk_grams' ? 'kg' : 'unidades'));
const delta = computed(() => Number(newQuantity.value || 0) - currentQuantity.value);

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) {
            return;
        }

        errors.value = {};
        reason.value = '';
        type.value = 'in';
        newQuantity.value = currentQuantity.value;
    }
);

const save = async () => {
    errors.value = {};

    try {
        await inventoryStore.adjustStock(props.stock.id, {
            new_quantity: Number(newQuantity.value),
            type: type.value,
            reason: reason.value,
        });

        toastStore.success('Stock ajustado');
        emit('saved');
        emit('close');
    } catch (err) {
        if (err.response?.status === 422) {
            errors.value = err.response.data.errors || {};
        } else {
            toastStore.error(err.response?.data?.message || 'No se pudo ajustar el stock');
        }
    }
};
</script>
