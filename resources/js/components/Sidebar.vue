<template>
    <aside class="w-64 bg-slate-900 text-slate-300 flex flex-col min-h-screen shrink-0 select-none">
        <div class="h-16 px-5 flex items-center gap-3 border-b border-slate-800 shrink-0">
            <div class="h-9 w-9 rounded-xl bg-indigo-600 flex items-center justify-center shrink-0">
                <AppIcon :name="IceCreamBowl" :size="19" class="text-white" />
            </div>
            <div class="min-w-0">
                <p class="text-sm font-bold text-white truncate leading-tight">Nieve Real</p>
                <p class="text-[11px] text-slate-400">Heladería & POS</p>
            </div>
        </div>

        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
            <router-link
                v-for="item in items"
                :key="item.name"
                :to="item.route"
                class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
                :class="
                    isActive(item)
                        ? 'bg-indigo-600 text-white'
                        : 'text-slate-400 hover:text-white hover:bg-slate-800'
                "
            >
                <span
                    class="w-1 h-5 rounded-r-full shrink-0"
                    :class="isActive(item) ? 'bg-white' : 'bg-transparent'"
                />
                <AppIcon :name="item.icon" :size="17" class="shrink-0" />
                <span class="truncate">{{ item.label }}</span>
            </router-link>
        </nav>
    </aside>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { IceCreamBowl } from 'lucide-vue-next';
import AppIcon from './ui/AppIcon.vue';
import { useAuthStore } from '../stores/auth';
import { visibleNavItems } from '../config/navigation';

const authStore = useAuthStore();
const route = useRoute();

const items = computed(() => visibleNavItems(authStore.roles));
const isActive = (item) => route.name === item.name;
</script>
