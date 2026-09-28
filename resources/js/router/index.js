import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useShiftStore } from '../stores/shifts';
import { homeRouteFor } from '../config/navigation';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('../views/LoginView.vue'),
        meta: { guestOnly: true },
    },
    {
        // Portal del empleado: elige su nombre e ingresa su PIN. No requiere
        // sesion de Sanctum a proposito, por eso no lleva requiresAuth. La
        // sesion del portal es aparte y vive en su propio store.
        path: '/empleados',
        name: 'employee-portal',
        component: () => import('../views/EmployeePortalView.vue'),
    },
    {
        // Vista de equipo del administrador. Esta si va dentro del sistema.
        path: '/empleados/equipo',
        name: 'team',
        component: () => import('../views/TeamEmployeesView.vue'),
        meta: { requiresAuth: true, roles: ['admin'] },
    },
    {
        path: '/',
        name: 'pos',
        component: () => import('../views/PosView.vue'),
        // El mesero necesita turno abierto para trabajar: sin el, la comanda
        // quedaria sin atribucion. El admin no entra al POS: administra.
        meta: { requiresAuth: true, requiresShift: true, roles: ['cashier', 'waiter', 'kitchen'] },
    },
    {
        path: '/cobro/:orderId',
        name: 'checkout',
        component: () => import('../views/CheckoutView.vue'),
        meta: { requiresAuth: true, roles: ['admin', 'cashier'] },
    },
    {
        path: '/productos',
        name: 'products',
        component: () => import('../views/ProductsView.vue'),
        meta: { requiresAuth: true, roles: ['admin', 'cashier'] },
    },
    {
        path: '/inventario',
        name: 'inventory',
        component: () => import('../views/InventoryView.vue'),
        meta: { requiresAuth: true, roles: ['admin'] },
    },
    {
        // Operacion de gaveta: abrir, movimientos y arqueo. Solo el cajero.
        path: '/caja',
        name: 'cash-register',
        component: () => import('../views/CashRegisterView.vue'),
        meta: { requiresAuth: true, roles: ['cashier'] },
    },
    {
        // Caja de supervision: el admin mira el saldo de la gaveta y registra
        // retiros. No abre ni cierra turnos, eso es del cajero.
        path: '/caja/resumen',
        name: 'cash-overview',
        component: () => import('../views/CashOverviewView.vue'),
        meta: { requiresAuth: true, roles: ['admin'] },
    },
    {
        path: '/reportes',
        name: 'reports',
        component: () => import('../views/ReportsView.vue'),
        meta: { requiresAuth: true, roles: ['admin'] },
    },
    {
        path: '/configuracion',
        name: 'settings',
        component: () => import('../views/SettingsView.vue'),
        meta: { requiresAuth: true, roles: ['admin'] },
    },
    {
        path: '/turnos',
        name: 'shifts',
        component: () => import('../views/TurnosView.vue'),
        meta: { requiresAuth: true, roles: ['admin', 'waiter'] },
    },
    {
        // Cualquier ruta desconocida devuelve la SPA. En el dev server eso
        // significa un 200 con el index.html, igual que en produccion.
        path: '/:pathMatch(.*)*',
        redirect: '/',
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

/**
 * El mesero no puede trabajar sin un turno abierto en este dispositivo, asi
 * que se lo manda al modulo de turnos. El bloqueo es solo para el rol waiter:
 * el admin es quien resetea PINes y corrige problemas, asi que nunca puede
 * quedar fuera del sistema. El cajero y la cocina no usan turnos.
 */
const SHIFT_LOCKED_ROLES = ['waiter'];

router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        return next({ name: 'login' });
    }

    if (to.meta.guestOnly && authStore.isAuthenticated) {
        return next(homeRouteFor(authStore.roles));
    }

    if (to.meta.roles && to.meta.roles.length > 0) {
        const hasRole = to.meta.roles.some(role => authStore.roles.includes(role));
        if (!hasRole) {
            return next(homeRouteFor(authStore.roles));
        }
    }

    if (to.meta.requiresShift && SHIFT_LOCKED_ROLES.some(r => authStore.roles.includes(r))) {
        const shiftStore = useShiftStore();
        const shift = await shiftStore.loadActive();

        if (!shift) {
            return next({ name: 'shifts' });
        }
    }

    next();
});

export default router;
