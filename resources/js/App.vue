<template>
  <div class="min-h-screen flex bg-slate-100 font-sans">
    <Sidebar v-if="authStore.isAuthenticated" />
    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto max-h-screen">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { useAuthStore } from './stores/auth';
import Sidebar from './components/Sidebar.vue';

const authStore = useAuthStore();
</script>
