import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

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
        meta: { requiresAuth: true },
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
        path: '/:pathMatch(.*)*',
        redirect: '/',
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to, from, next) => {
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

    next();
});

export default router;
