<template>
    <div class="min-h-screen bg-aguamarina-50 flex flex-col">
        <PortalHeader />

        <main class="flex-1 p-4 sm:p-6">
            <div class="max-w-4xl mx-auto space-y-6">
                <!-- Elegir empleado -->
                <div v-if="!store.isAuthenticated" class="space-y-5">
                    <div class="text-center pt-4 pb-2 space-y-2">
                        <h1 class="text-2xl font-bold text-petrol-800">Portal del Empleado</h1>
                        <p class="text-sm text-niebla-400 max-w-md mx-auto">
                            Elegí tu nombre e ingresá tu PIN de 4 dígitos para ver tu ficha, tus
                            vacaciones y tus solicitudes.
                        </p>
                    </div>

                    <div v-if="store.loading" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div
                            v-for="n in 4"
                            :key="n"
                            class="h-20 rounded-2xl bg-white animate-pulse shadow-sm"
                        />
                    </div>

                    <div
                        v-else-if="!store.employees.length"
                        class="bg-white rounded-2xl border border-aguamarina-100 shadow-sm p-10 text-center space-y-2"
                    >
                        <AppIcon
                            :name="Users"
                            :size="30"
                            class="text-aguamarina-400 mx-auto"
                        />
                        <p class="text-sm text-niebla-400">
                            Todavía no hay empleados con PIN configurado. Pídele al administrador que
                            los registre.
                        </p>
                    </div>

                    <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <button
                            v-for="e in store.employees"
                            :key="e.id"
                            type="button"
                            class="text-left bg-white rounded-2xl border border-aguamarina-200 hover:border-aguamarina-500 hover:shadow-card transition p-4 flex items-center gap-3"
                            @click="pickEmployee(e)"
                        >
                            <div
                                class="h-12 w-12 rounded-xl bg-aguamarina-100 flex items-center justify-center shrink-0"
                            >
                                <AppIcon :name="User" :size="22" class="text-aguamarina-600" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-bold text-petrol-800 truncate">{{ e.name }}</p>
                                <p class="text-xs text-niebla-400 truncate">
                                    {{ roleLabel(e.roles) }}
                                </p>
                            </div>
                            <AppIcon
                                :name="ChevronRight"
                                :size="18"
                                class="text-aguamarina-400 shrink-0"
                            />
                        </button>
                    </div>
                </div>

                <!-- Ficha -->
                <EmployeeProfile v-else />
            </div>
        </main>
        <PinDialog
            :employee="selected"
            @accepted="onPinAccepted"
        />
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { ChevronRight, User, Users } from 'lucide-vue-next';
import PortalHeader from '../components/employees/PortalHeader.vue';
import EmployeeProfile from '../components/employees/EmployeeProfile.vue';
import PinDialog from '../components/employees/PinDialog.vue';
import AppIcon from '../components/ui/AppIcon.vue';
import { useEmployeeStore } from '../stores/employees';

const store = useEmployeeStore();

const selected = ref(null);

const ROLE_LABELS = {
    admin: 'Administrador',
    cashier: 'Cajero',
    waiter: 'Mesero',
    kitchen: 'Cocina y Barra',
};

const roleLabel = (roles) =>
    (roles || []).map((r) => ROLE_LABELS[r] || r).join(' · ') || 'Empleado';

const pickEmployee = (employee) => {
    selected.value = employee;
};

const onPinAccepted = () => {
    selected.value = null;
};

onMounted(() => {
    if (!store.isAuthenticated) {
        store.loadEmployees();
    }
});
</script>
