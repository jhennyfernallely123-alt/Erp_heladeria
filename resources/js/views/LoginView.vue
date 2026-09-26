<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-900 p-4">
    <div class="max-w-md w-full bg-slate-800/90 rounded-2xl shadow-2xl border border-slate-700/60 p-8 backdrop-blur-md">
      <div class="text-center mb-8">
        <div class="w-16 h-16 bg-gradient-to-tr from-pink-500 to-rose-400 rounded-2xl flex items-center justify-center text-3xl mx-auto shadow-lg shadow-pink-500/30 mb-3">
          🍨
        </div>
        <h2 class="text-2xl font-black text-white tracking-wide">Heladería Nieve Real</h2>
        <p class="text-xs text-pink-400 font-medium mt-1">Sistema ERP & Punto de Venta</p>
      </div>

      <div v-if="error" class="mb-4 p-3 bg-rose-500/20 border border-rose-500/40 rounded-xl text-rose-300 text-xs text-center font-medium">
        {{ error }}
      </div>

      <form @submit.prevent="submitLogin" class="space-y-4">
        <div>
          <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Correo Electrónico</label>
          <input
            v-model="email"
            type="email"
            required
            class="w-full px-4 py-3 rounded-xl bg-slate-900/80 border border-slate-700 text-slate-100 placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition"
            placeholder="ej. admin@heladeria.com"
          />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">Contraseña</label>
          <input
            v-model="password"
            type="password"
            required
            class="w-full px-4 py-3 rounded-xl bg-slate-900/80 border border-slate-700 text-slate-100 placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition"
            placeholder="••••••••"
          />
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full py-3.5 px-4 bg-gradient-to-r from-pink-600 to-rose-500 hover:from-pink-500 hover:to-rose-400 text-white font-bold rounded-xl shadow-lg shadow-pink-600/30 transition transform active:scale-[0.98] disabled:opacity-50 text-sm tracking-wide mt-2"
        >
          {{ loading ? 'Ingresando...' : 'Iniciar Sesión' }}
        </button>
      </form>

      <!-- Quick credentials help -->
      <div class="mt-8 pt-6 border-t border-slate-700/60 text-slate-400 text-xs">
        <p class="font-bold text-slate-300 mb-2">Accesos Rápidos de Prueba:</p>
        <div class="grid grid-cols-2 gap-2 text-[11px]">
          <button @click="fillCreds('admin@heladeria.com')" class="p-1.5 bg-slate-700/40 rounded hover:bg-slate-700 text-left">
            👑 <strong>Admin</strong>
          </button>
          <button @click="fillCreds('cajero@heladeria.com')" class="p-1.5 bg-slate-700/40 rounded hover:bg-slate-700 text-left">
            💵 <strong>Cajero</strong>
          </button>
          <button @click="fillCreds('mesero@heladeria.com')" class="p-1.5 bg-slate-700/40 rounded hover:bg-slate-700 text-left">
            🧑‍🍳 <strong>Mesero</strong>
          </button>
          <button @click="fillCreds('cocina@heladeria.com')" class="p-1.5 bg-slate-700/40 rounded hover:bg-slate-700 text-left">
            🍦 <strong>Cocina</strong>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';

const email = ref('admin@heladeria.com');
const password = ref('password');
const error = ref(null);
const loading = ref(false);

const authStore = useAuthStore();
const router = useRouter();

const fillCreds = (em) => {
  email.value = em;
  password.value = 'password';
};

const submitLogin = async () => {
  error.value = null;
  loading.value = true;
  try {
    await authStore.login(email.value, password.value);
    router.push('/');
  } catch (err) {
    error.value = err.response?.data?.message || 'Credenciales inválidas';
  } finally {
    loading.value = false;
  }
};
</script>
