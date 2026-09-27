import { defineStore } from 'pinia';
import api from '../api';

const emptyStats = () => ({
    total_products: 0,
    total_units: 0,
    low_count: 0,
    critical_count: 0,
});

export const useInventoryStore = defineStore('inventory', {
    state: () => ({
        items: [],
        stats: emptyStats(),
        loading: false,
        saving: false,
        error: null,
    }),

    actions: {
        async fetchInventory(filters = {}) {
            this.loading = true;
            this.error = null;

            try {
                const params = {};
                if (filters.search) params.search = filters.search;
                if (filters.categoryId) params.category_id = filters.categoryId;
                if (filters.status && filters.status !== 'all') params.status = filters.status;

                const res = await api.get('/inventory', { params });
                this.items = res.data.data;
                this.stats = res.data.meta?.stats || emptyStats();
            } catch (err) {
                this.error = err.response?.data?.message || 'No se pudo cargar el inventario';
                throw err;
            } finally {
                this.loading = false;
            }
        },

        async fetchLowStock() {
            const res = await api.get('/inventory/low-stock');
            return res.data.data;
        },

        async adjustStock(id, payload) {
            this.saving = true;

            try {
                const res = await api.post(`/inventory/${id}/adjust`, payload);
                const index = this.items.findIndex((item) => item.id === id);

                if (index !== -1) {
                    this.items[index] = res.data.data;
                }

                await this.fetchInventory();
                return res.data.data;
            } finally {
                this.saving = false;
            }
        },
    },
});
