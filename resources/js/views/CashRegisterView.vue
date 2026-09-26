<template>
  <div class="p-6 max-w-5xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-black text-slate-800 tracking-tight">Turnos & Cierre de Caja</h1>
        <p class="text-xs text-slate-500 mt-1">Apertura, control de entradas/salidas de efectivo y arqueo diario</p>
      </div>
    </div>

    <!-- State 1: No Open Session -->
    <div v-if="!currentSession" class="bg-white rounded-3xl p-8 border border-slate-200/80 shadow-sm text-center max-w-lg mx-auto space-y-4">
      <div class="w-16 h-16 bg-pink-100 text-pink-600 rounded-2xl flex items-center justify-center text-3xl mx-auto shadow-md">
        🔒
      </div>
      <h3 class="text-lg font-black text-slate-800">No hay turno de caja abierto</h3>
      <p class="text-xs text-slate-500">
        Inicia un turno de caja ingresando el saldo base en efectivo (gaveta) para comenzar a facturar.
      </p>

      <div class="pt-2 text-left space-y-3">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Monto Base de Apertura ($)</label>
          <input
            v-model.number="openingAmount"
            type="number"
            class="w-full text-base font-extrabold px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-pink-500 focus:outline-none"
            placeholder="ej. 50000"
          />
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Observaciones</label>
          <input
            v-model="openingNotes"
            type="text"
            class="w-full text-xs px-3 py-2 rounded-xl border border-slate-300 focus:outline-none"
            placeholder="Turno mañana / Cajero principal"
          />
        </div>

        <button
          @click="openRegister"
          class="w-full py-3.5 bg-pink-600 hover:bg-pink-700 text-white font-extrabold rounded-xl shadow-lg shadow-pink-600/20 text-xs transition"
        >
          🔓 Abrir Turno de Caja
        </button>
      </div>
    </div>

    <!-- State 2: Active Open Session -->
    <div v-else class="space-y-6">
      <!-- Session Card Status -->
      <div class="bg-slate-900 text-white p-6 rounded-3xl shadow-xl flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <div class="flex items-center space-x-2">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping"></span>
            <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">Turno Activo</span>
          </div>
          <h2 class="text-xl font-black mt-1">Caja Abierta por {{ currentSession.user?.name || 'Tú' }}</h2>
          <p class="text-xs text-slate-400 mt-0.5">Desde: {{ currentSession.opened_at }}</p>
        </div>

        <div class="flex items-center space-x-4">
          <div class="bg-slate-800/80 px-4 py-3 rounded-2xl border border-slate-700">
            <span class="text-[10px] uppercase font-bold text-slate-400 block">Base de Apertura</span>
            <span class="text-lg font-black text-pink-400">${{ formatMoney(currentSession.opening_balance) }}</span>
          </div>

          <button
            @click="isMovementModalOpen = true"
            class="px-4 py-3 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-2xl border border-slate-700 text-xs transition"
          >
            💸 Ingreso / Egreso
          </button>

          <button
            @click="isCloseModalOpen = true"
            class="px-4 py-3 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-2xl shadow-lg shadow-rose-600/30 text-xs transition"
          >
            🔒 Cerrar Turno
          </button>
        </div>
      </div>

      <!-- Movements Log -->
      <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm space-y-3">
        <h3 class="font-bold text-sm text-slate-800">Movimientos de Caja Menor (Gastos / Entradas)</h3>

        <div v-if="!currentSession.movements?.length" class="text-center py-6 text-slate-400 text-xs">
          No hay movimientos manuales registrados en este turno.
        </div>

        <div v-else class="divide-y divide-slate-100">
          <div v-for="mov in currentSession.movements" :key="mov.id" class="py-3 flex justify-between items-center text-xs">
            <div>
              <span class="font-bold text-slate-800 block">{{ mov.description }}</span>
              <span class="text-[10px] text-slate-400 uppercase font-bold">Cat: {{ mov.category }}</span>
            </div>
            <span
              class="font-black"
              :class="mov.type === 'cash_in' ? 'text-emerald-600' : 'text-rose-600'"
            >
              {{ mov.type === 'cash_in' ? '+' : '-' }}${{ formatMoney(mov.amount) }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Movement Modal -->
    <div v-if="isMovementModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
      <div class="bg-white rounded-2xl max-w-sm w-full p-5 shadow-2xl space-y-3">
        <h3 class="font-bold text-sm text-slate-800">Registrar Entrada / Salida</h3>

        <div>
          <label class="block text-[11px] font-semibold text-slate-600 mb-1">Tipo</label>
          <select v-model="newMovement.type" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-xl">
            <option value="cash_out">Salida / Egreso (Gasto/Compra)</option>
            <option value="cash_in">Entrada / Ingreso adicional</option>
          </select>
        </div>

        <div>
          <label class="block text-[11px] font-semibold text-slate-600 mb-1">Categoría</label>
          <select v-model="newMovement.category" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-xl">
            <option value="operating_expense">Gasto Operativo</option>
            <option value="purchase">Compra de Insumo urgente</option>
            <option value="payroll">Nómina / Anticipo</option>
            <option value="other">Otro</option>
          </select>
        </div>

        <div>
          <label class="block text-[11px] font-semibold text-slate-600 mb-1">Monto ($)</label>
          <input v-model.number="newMovement.amount" type="number" min="0" class="w-full text-xs font-bold px-3 py-2 border border-slate-300 rounded-xl" />
        </div>

        <div>
          <label class="block text-[11px] font-semibold text-slate-600 mb-1">Descripción / Justificación</label>
          <input v-model="newMovement.description" type="text" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-xl" placeholder="ej. Compra servilletas y vasos" />
        </div>

        <div class="flex justify-end space-x-2 pt-2">
          <button @click="isMovementModalOpen = false" class="px-3 py-1.5 bg-slate-100 text-slate-700 rounded-xl text-xs font-semibold">
            Cancelar
          </button>
          <button @click="submitMovement" class="px-4 py-1.5 bg-pink-600 text-white rounded-xl text-xs font-bold">
            Guardar
          </button>
        </div>
      </div>
    </div>

    <!-- Close Register Modal (Arqueo de cierre) -->
    <div v-if="isCloseModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
      <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4">
        <h3 class="text-base font-black text-slate-800">Arqueo y Cierre de Turno de Caja</h3>
        <p class="text-xs text-slate-500">
          Cuenta el dinero físico total en la gaveta en este momento e ingrésalo a continuación:
        </p>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Efectivo Físico en Gaveta ($)</label>
          <input
            v-model.number="actualCloseAmount"
            type="number"
            class="w-full text-lg font-black px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-rose-500 focus:outline-none"
            placeholder="0"
          />
        </div>

        <div class="flex justify-end space-x-2 pt-2">
          <button @click="isCloseModalOpen = false" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-xs font-semibold">
            Cancelar
          </button>
          <button @click="submitCloseRegister" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold">
            Confirmar Arqueo & Cerrar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
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

const formatMoney = (val) => Number(val || 0).toLocaleString('es-CO');

const loadCurrent = async () => {
  try {
    const res = await api.get('/cash-register/current');
    currentSession.value = res.data.data;
  } catch (err) {
    console.error(err);
  }
};

onMounted(() => {
  loadCurrent();
});

const openRegister = async () => {
  try {
    const res = await api.post('/cash-register/open', {
      opening_balance: openingAmount.value,
      notes: openingNotes.value,
    });
    currentSession.value = res.data.data;
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
    alert(`Caja cerrada. Saldo esperado: $${formatMoney(closed.expected_balance)}, Físico: $${formatMoney(closed.actual_balance)}, Diferencia: $${formatMoney(closed.difference)}`);
    isCloseModalOpen.value = false;
    currentSession.value = null;
  } catch (err) {
    alert(err.response?.data?.message || 'Error al cerrar caja');
  }
};
</script>
