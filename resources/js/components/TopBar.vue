<template>
    <header
        class="h-16 bg-white border-b border-slate-200 flex items-center justify-between gap-4 px-6 shrink-0"
    >
        <p class="text-sm font-semibold text-slate-900 truncate">{{ currentTitle }}</p>

        <div ref="menuRoot" class="relative">
            <button
                type="button"
                class="flex items-center gap-2.5 pl-1.5 pr-2.5 py-1.5 rounded-xl hover:bg-slate-50 transition"
                aria-haspopup="menu"
                :aria-expanded="menuOpen"
                @click="menuOpen = !menuOpen"
            >
                <span
                    class="h-8 w-8 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center shrink-0"
                >
                    {{ initials }}
                </span>
                <span class="hidden sm:block text-left">
                    <span class="block text-xs font-semibold text-slate-800 leading-tight">
                        {{ authStore.user?.name }}
                    </span>
                    <span
                        class="block text-[10px] uppercase tracking-wide text-slate-400 leading-tight"
                    >
                        {{ (authStore.user?.roles || [])[0] || 'Usuario' }}
                    </span>
                </span>
                <AppIcon :name="ChevronDown" :size="15" class="text-slate-400" />
            </button>

            <Transition
                enter-active-class="transition duration-100 ease-out"
                enter-from-class="opacity-0 scale-95"
                leave-active-class="transition duration-75 ease-in"
                leave-to-class="opacity-0 scale-95"
            >
                <div
                    v-if="menuOpen"
                    class="absolute right-0 mt-2 w-56 bg-white rounded-xl border border-slate-200 shadow-lg py-1 z-50"
                    role="menu"
                >
                    <div class="px-4 py-2.5 border-b border-slate-100">
                        <p class="text-xs font-semibold text-slate-800 truncate">
                            {{ authStore.user?.name }}
                        </p>
                        <p class="text-[11px] text-slate-500 truncate">{{ authStore.user?.email }}</p>
                    </div>
                    <button
                        type="button"
                        class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition"
                        role="menuitem"
                        @click="handleLogout"
                    >
                        <AppIcon :name="LogOut" :size="16" class="text-slate-400" />
                        Cerrar sesión
                    </button>
                </div>
            </Transition>
        </div>
    </header>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { ChevronDown, LogOut } from 'lucide-vue-next';
import AppIcon from './ui/AppIcon.vue';
import { useAuthStore } from '../stores/auth';
import { navItems } from '../config/navigation';

const authStore = useAuthStore();
const route = useRoute();
const router = useRouter();

const menuOpen = ref(false);
const menuRoot = ref(null);

const initials = computed(() =>
    (authStore.user?.name || '?')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0].toUpperCase())
        .join('')
);

const currentTitle = computed(() => navItems.find((item) => item.name === route.name)?.label || '');

const handleLogout = async () => {
    menuOpen.value = false;
    await authStore.logout();
    router.push('/login');
};

const onClickOutside = (event) => {
    if (menuRoot.value && !menuRoot.value.contains(event.target)) {
        menuOpen.value = false;
    }
};

onMounted(() => document.addEventListener('click', onClickOutside));
onUnmounted(() => document.removeEventListener('click', onClickOutside));
</script>
