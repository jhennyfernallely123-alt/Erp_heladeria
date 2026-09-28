import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useShiftStore } from '../stores/shifts';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('../views/LoginView.vue'),
        meta: { guestOnly: true },
    },
    {
        path: '/',
        name: 'pos',
        component: () => import('../views/PosView.vue'),
        // El mesero necesita turno abierto para trabajar: sin el, la comanda
        // quedaria sin atribucion.
        meta: { requiresAuth: true, requiresShift: true },
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
        path: '/caja',
        name: 'cash-register',
        component: () => import('../views/CashRegisterView.vue'),
        meta: { requiresAuth: true, roles: ['admin', 'cashier'] },
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
        return next({ name: 'pos' });
    }

    if (to.meta.roles && to.meta.roles.length > 0) {
        const hasRole = to.meta.roles.some(role => authStore.roles.includes(role));
        if (!hasRole) {
            return next({ name: 'pos' });
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
