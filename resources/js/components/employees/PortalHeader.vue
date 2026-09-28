<template>
    <header class="bg-petrol-800 text-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 h-16 flex items-center gap-3">
            <div
                class="h-9 w-9 rounded-xl bg-aguamarina-500 flex items-center justify-center shrink-0"
            >
                <AppIcon :name="IceCreamBowl" :size="19" class="text-white" />
            </div>

            <div class="min-w-0 flex-1">
                <p class="font-script text-[1.0625rem] text-aguamarina-100 truncate leading-tight">
                    Dulce Helado
                </p>
                <p class="text-[11px] text-niebla-400">Portal del empleado</p>
            </div>

            <div v-if="store.isAuthenticated" class="flex items-center gap-3">
                <span class="hidden sm:block text-xs text-niebla-300 truncate max-w-[10rem]">
                    {{ store.employeeName }}
                </span>
                <button
                    type="button"
                    class="p-2 rounded-lg text-niebla-300 hover:text-white hover:bg-petrol-700 transition"
                    aria-label="Cerrar sesión"
                    title="Cerrar sesión"
                    @click="closeSession"
                >
                    <AppIcon :name="LogOut" :size="18" />
                </button>
            </div>
        </div>
    </header>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { IceCreamBowl, LogOut } from 'lucide-vue-next';
import AppIcon from '../ui/AppIcon.vue';
import { useEmployeeStore } from '../../stores/employees';

const store = useEmployeeStore();
const router = useRouter();

/**
 * Cierra la sesión y vuelve a la pantalla de inicio de sesión del sistema.
 *
 * Se va a /login y no al listado de nombres del portal porque el dispositivo
 * es compartido: el siguiente en usarlo puede ser un empleado entrando con su
 * PIN o alguien del sistema entrando con usuario y contraseña. La pantalla de
 * nombres es del portal, no el inicio de sesión.
 */
const closeSession = async () => {
    await store.logout();

    // replace y no push: el nombre de la persona no debe quedar en el historial
    // del navegador, porque el siguiente usuario lo veria al darle "atrás".
    router.replace('/login');
};
</script>
