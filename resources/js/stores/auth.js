import { defineStore } from 'pinia';
import api from '../api';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: localStorage.getItem('erp_token') || null,
        user: JSON.parse(localStorage.getItem('erp_user') || 'null'),
        loading: false,
        error: null,
    }),

    getters: {
        isAuthenticated: (state) => !!state.token,
        roles: (state) => state.user?.roles || [],
        permissions: (state) => state.user?.permissions || [],
        isAdmin: (state) => state.user?.roles?.includes('admin'),
        isCashier: (state) => state.user?.roles?.includes('cashier') || state.user?.roles?.includes('admin'),
        isWaiter: (state) => state.user?.roles?.includes('waiter') || state.user?.roles?.includes('admin'),
        isKitchen: (state) => state.user?.roles?.includes('kitchen') || state.user?.roles?.includes('admin'),
    },

    actions: {
        async login(email, password) {
            this.loading = true;
            this.error = null;
            try {
                const res = await api.post('/auth/login', { email, password });
                const { token, user } = res.data.data;

                this.token = token;
                this.user = user;

                localStorage.setItem('erp_token', token);
                localStorage.setItem('erp_user', JSON.stringify(user));

                return user;
            } catch (err) {
                this.error = err.response?.data?.message || 'Error al iniciar sesión';
                throw err;
            } finally {
                this.loading = false;
            }
        },

        async logout() {
            try {
                if (this.token) {
                    await api.post('/auth/logout');
                }
            } catch (e) {
                // ignore
            } finally {
                this.token = null;
                this.user = null;
                localStorage.removeItem('erp_token');
                localStorage.removeItem('erp_user');
            }
        },

        async fetchMe() {
            if (!this.token) return null;
            try {
                const res = await api.get('/auth/me');
                this.user = res.data.data.user;
                localStorage.setItem('erp_user', JSON.stringify(this.user));
                return this.user;
            } catch (e) {
                this.logout();
                return null;
            }
        },

        can(permission) {
            if (this.isAdmin) return true;
            return this.permissions.includes(permission);
        }
    }
});
