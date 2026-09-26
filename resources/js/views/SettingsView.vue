<template>
  <div class="p-6 max-w-4xl mx-auto space-y-6">
    <div>
      <h1 class="text-2xl font-black text-slate-800 tracking-tight">Configuración del Sistema</h1>
      <p class="text-xs text-slate-500 mt-1">Datos fiscales del establecimiento y modo de facturación</p>
    </div>

    <div v-if="loading" class="text-center py-20 text-slate-400">
      <span class="text-3xl animate-spin inline-block">🔄</span>
    </div>

    <form v-else @submit.prevent="saveSettings" class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm space-y-6">
      <!-- Shop Details -->
      <div>
        <h3 class="font-bold text-sm text-slate-800 mb-3 border-b border-slate-100 pb-2">
          🏢 Datos del Establecimiento
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
          <div>
            <label class="block font-semibold text-slate-600 mb-1">Nombre Comercial</label>
            <input v-model="settings.shop_name" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl" />
          </div>
          <div>
            <label class="block font-semibold text-slate-600 mb-1">NIT / Identificación Fiscal</label>
            <input v-model="settings.shop_nit" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl" />
          </div>
          <div>
            <label class="block font-semibold text-slate-600 mb-1">Dirección del Local</label>
            <input v-model="settings.shop_address" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl" />
          </div>
          <div>
            <label class="block font-semibold text-slate-600 mb-1">Teléfono / WhatsApp</label>
            <input v-model="settings.shop_phone" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl" />
          </div>
        </div>
      </div>

      <!-- Invoicing Mode Settings -->
      <div>
        <h3 class="font-bold text-sm text-slate-800 mb-3 border-b border-slate-100 pb-2">
          🧾 Modo de Facturación Activo
        </h3>
        <div class="space-y-3">
          <label class="flex items-start space-x-3 p-3 border border-slate-200 rounded-2xl cursor-pointer hover:bg-slate-50">
            <input
              type="radio"
              value="internal"
              v-model="settings.billing_mode"
              class="mt-1 text-pink-600 focus:ring-pink-500"
            />
            <div>
              <span class="font-bold text-xs text-slate-800 block">Facturación Interna (Recomendada para operar de inmediato)</span>
              <span class="text-[11px] text-slate-500">
                Emisión de ticket térmico PDF (80mm) con consecutivo consecutivo interno (ej. FAC-0001). Sin envío fiscal.
              </span>
            </div>
          </label>

          <label class="flex items-start space-x-3 p-3 border border-slate-200 rounded-2xl cursor-pointer hover:bg-slate-50">
            <input
              type="radio"
              value="dian"
              v-model="settings.billing_mode"
              class="mt-1 text-pink-600 focus:ring-pink-500"
            />
            <div>
              <span class="font-bold text-xs text-slate-800 block">Facturación Electrónica DIAN Colombia (UBL 2.1)</span>
              <span class="text-[11px] text-slate-500">
                Generación automática de CUFE criptográfico (SHA-384) y estructura preparada para proveedor tecnológico.
              </span>
            </div>
          </label>
        </div>
      </div>

      <!-- Consecutives -->
      <div>
        <h3 class="font-bold text-sm text-slate-800 mb-3 border-b border-slate-100 pb-2">
          🔢 Consecutivos y Prefijos
        </h3>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
          <div>
            <label class="block font-semibold text-slate-600 mb-1">Prefijo Interno</label>
            <input v-model="settings.invoice_prefix" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl" />
          </div>
          <div>
            <label class="block font-semibold text-slate-600 mb-1">Consecutivo Actual</label>
            <input v-model="settings.invoice_consecutive" type="number" class="w-full px-3 py-2 border border-slate-300 rounded-xl" />
          </div>
          <div>
            <label class="block font-semibold text-slate-600 mb-1">Prefijo DIAN</label>
            <input v-model="settings.dian_prefix" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl" />
          </div>
          <div>
            <label class="block font-semibold text-slate-600 mb-1">Consecutivo DIAN</label>
            <input v-model="settings.dian_consecutive" type="number" class="w-full px-3 py-2 border border-slate-300 rounded-xl" />
          </div>
        </div>
      </div>

      <div class="pt-3 border-t border-slate-100 flex justify-end">
        <button
          type="submit"
          :disabled="saving"
          class="px-6 py-3 bg-pink-600 hover:bg-pink-700 text-white font-bold rounded-xl shadow-lg shadow-pink-600/20 text-xs transition"
        >
          {{ saving ? 'Guardando...' : 'Guardar Configuraciones' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
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

onMounted(() => {
  loadSettings();
});

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
