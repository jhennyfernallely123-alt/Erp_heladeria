<template>
    <AppShell v-if="authStore.isAuthenticated" />
    <router-view v-else />
    <AppToast />
</template>

<script setup>
import { useAuthStore } from './stores/auth';
import AppShell from './components/AppShell.vue';
import AppToast from './components/ui/AppToast.vue';

const authStore = useAuthStore();
</script>
