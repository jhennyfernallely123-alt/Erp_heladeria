<template>
    <div class="p-6 max-w-4xl mx-auto space-y-6">
        <PageHeader
            title="Configuración del Sistema"
            subtitle="Datos fiscales del establecimiento y modo de facturación"
        />

        <div v-if="loading" class="bg-white rounded-2xl border border-aguamarina-100 shadow-sm">
            <div class="p-5 space-y-3">
                <div class="h-4 w-56 rounded bg-aguamarina-50 animate-pulse" />
                <div class="h-3 w-full rounded bg-aguamarina-50 animate-pulse" />
                <div class="h-3 w-4/5 rounded bg-aguamarina-50 animate-pulse" />
            </div>
        </div>

        <form
            v-else
            class="bg-white rounded-2xl p-6 border border-aguamarina-100 shadow-sm space-y-6"
            @submit.prevent="saveSettings"
        >
            <!-- Datos del establecimiento -->
            <section>
                <h3
                    class="font-bold text-sm text-petrol-800 mb-3 border-b border-aguamarina-100 pb-2 flex items-center gap-2"
                >
                    <AppIcon :name="Store" :size="17" class="text-aguamarina-600" />
                    Datos del establecimiento
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <AppInput v-model="settings.shop_name" label="Nombre Comercial" />
                    <AppInput v-model="settings.shop_nit" label="NIT / Identificación Fiscal" />
                    <AppInput v-model="settings.shop_address" label="Dirección del Local" />
                    <AppInput v-model="settings.shop_phone" label="Teléfono / WhatsApp" />
                </div>
            </section>

            <!-- Modo de facturación -->
            <section>
                <h3
                    class="font-bold text-sm text-petrol-800 mb-3 border-b border-aguamarina-100 pb-2 flex items-center gap-2"
                >
                    <AppIcon :name="Receipt" :size="17" class="text-aguamarina-600" />
                    Modo de facturación activo
                </h3>
                <div class="space-y-3">
                    <label
                        v-for="mode in billingModes"
                        :key="mode.value"
                        class="flex items-start gap-3 p-3.5 border rounded-xl cursor-pointer transition-colors"
                        :class="
                            settings.billing_mode === mode.value
                                ? 'border-aguamarina-400 bg-aguamarina-50'
                                : 'border-aguamarina-100 hover:bg-aguamarina-50/50'
                        "
                    >
                        <input
                            v-model="settings.billing_mode"
                            type="radio"
                            :value="mode.value"
                            class="mt-0.5 text-aguamarina-600 focus:ring-aguamarina-500"
                        />
                        <div>
                            <span class="font-semibold text-xs text-petrol-800 block">
                                {{ mode.title }}
                            </span>
                            <span class="text-[11px] text-niebla-400">{{ mode.description }}</span>
                        </div>
                    </label>
                </div>
            </section>

            <!-- Consecutivos -->
            <section>
                <h3
                    class="font-bold text-sm text-petrol-800 mb-3 border-b border-aguamarina-100 pb-2 flex items-center gap-2"
                >
                    <AppIcon :name="Hash" :size="17" class="text-aguamarina-600" />
                    Consecutivos y prefijos
                </h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <AppInput v-model="settings.invoice_prefix" label="Prefijo interno" />
                    <AppInput
                        v-model="settings.invoice_consecutive"
                        label="Consecutivo actual"
                        type="number"
                    />
                    <AppInput v-model="settings.dian_prefix" label="Prefijo DIAN" />
                    <AppInput
                        v-model="settings.dian_consecutive"
                        label="Consecutivo DIAN"
                        type="number"
                    />
                </div>
            </section>

            <div class="pt-3 border-t border-aguamarina-100 flex justify-end">
                <AppButton
                    type="submit"
                    label="Guardar configuraciones"
                    :loading="saving"
                    :loading-text="'Guardando...'"
                />
            </div>
        </form>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { Hash, Receipt, Store } from 'lucide-vue-next';
import PageHeader from '../components/ui/PageHeader.vue';
import AppButton from '../components/ui/AppButton.vue';
import AppIcon from '../components/ui/AppIcon.vue';
import AppInput from '../components/ui/AppInput.vue';
import api from '../api';

const settings = ref({
    shop_name: '',
    shop_nit: '',
    shop_address: '',
    shop_phone: '',
    billing_mode: 'internal',
    invoice_prefix: 'FAC-',
    invoice_consecutive: '1',
    dian_prefix: 'SETP-',
    dian_consecutive: '1',
});

const billingModes = [
    {
        value: 'internal',
        title: 'Facturación interna (recomendada para operar de inmediato)',
        description:
            'Emisión de ticket térmico PDF (80mm) con consecutivo interno (ej. FAC-0001). Sin envío fiscal.',
    },
    {
        value: 'dian',
        title: 'Facturación electrónica DIAN Colombia (UBL 2.1)',
        description:
            'Generación automática de CUFE criptográfico (SHA-384) y estructura preparada para proveedor tecnológico.',
    },
];

const loading = ref(true);
const saving = ref(false);

const loadSettings = async () => {
    loading.value = true;
    try {
        const res = await api.get('/settings');
        settings.value = { ...settings.value, ...res.data.data };
    } catch (err) {
        console.error(err);
    } finally {
        loading.value = false;
    }
};

onMounted(loadSettings);

const saveSettings = async () => {
    saving.value = true;
    try {
        await api.post('/settings', { settings: settings.value });
        alert('Configuraciones guardadas exitosamente.');
    } catch (err) {
        alert('Error al guardar configuraciones: ' + err.message);
    } finally {
        saving.value = false;
    }
};
</script>
