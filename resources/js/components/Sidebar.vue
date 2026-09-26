<template>
  <aside class="w-64 bg-slate-900 text-slate-100 flex flex-col min-h-screen shrink-0 select-none shadow-xl">
    <!-- Brand -->
    <div class="p-5 border-b border-slate-800 flex items-center space-x-3">
      <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-pink-500 to-rose-400 flex items-center justify-center text-white shadow-lg shadow-pink-500/30 font-extrabold text-xl">
        🍨
      </div>
      <div>
        <h1 class="font-bold text-base leading-tight tracking-wide text-white">Nieve Real ERP</h1>
        <p class="text-xs text-pink-400 font-medium">Heladería & POS</p>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
      <router-link
        to="/"
        class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors group"
        :class="$route.name === 'pos' ? 'bg-pink-600 text-white font-semibold shadow-md' : 'text-slate-400 hover:text-white hover:bg-slate-800'"
      >
        <span class="mr-3 text-lg">🪑</span>
        Mesas & Pedidos (POS)
      </router-link>

      <router-link
        v-if="authStore.isCashier || authStore.isAdmin"
        to="/productos"
        class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors group"
        :class="$route.name === 'products' ? 'bg-pink-600 text-white font-semibold shadow-md' : 'text-slate-400 hover:text-white hover:bg-slate-800'"
      >
        <span class="mr-3 text-lg">🍦</span>
        Productos & Stock
      </router-link>

      <router-link
        v-if="authStore.isCashier || authStore.isAdmin"
        to="/caja"
        class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors group"
        :class="$route.name === 'cash-register' ? 'bg-pink-600 text-white font-semibold shadow-md' : 'text-slate-400 hover:text-white hover:bg-slate-800'"
      >
        <span class="mr-3 text-lg">💵</span>
        Turno de Caja
      </router-link>

      <router-link
        v-if="authStore.isAdmin"
        to="/reportes"
        class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors group"
        :class="$route.name === 'reports' ? 'bg-pink-600 text-white font-semibold shadow-md' : 'text-slate-400 hover:text-white hover:bg-slate-800'"
      >
        <span class="mr-3 text-lg">📊</span>
        Finanzas & Reportes
      </router-link>

      <router-link
        v-if="authStore.isAdmin"
        to="/configuracion"
        class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors group"
        :class="$route.name === 'settings' ? 'bg-pink-600 text-white font-semibold shadow-md' : 'text-slate-400 hover:text-white hover:bg-slate-800'"
      >
        <span class="mr-3 text-lg">⚙️</span>
        Configuración & DIAN
      </router-link>
    </nav>

    <!-- User & Logout -->
    <div class="p-4 border-t border-slate-800 bg-slate-950/40">
      <div class="flex items-center justify-between">
        <div class="truncate mr-2">
          <p class="text-xs font-semibold text-slate-200 truncate">{{ authStore.user?.name }}</p>
          <span class="inline-block mt-0.5 px-2 py-0.5 text-[10px] font-bold uppercase rounded bg-pink-500/20 text-pink-300 border border-pink-500/30">
            {{ authStore.user?.roles?.[0] || 'Usuario' }}
          </span>
        </div>
        <button
          @click="handleLogout"
          class="p-2 text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition"
          title="Cerrar sesión"
        >
          🚪
        </button>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';

const authStore = useAuthStore();
const router = useRouter();

const handleLogout = async () => {
  await authStore.logout();
  router.push('/login');
};
</script>
