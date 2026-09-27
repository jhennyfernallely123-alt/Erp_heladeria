import { defineStore } from 'pinia';
import api from '../api';

export const useProductStore = defineStore('products', {
    state: () => ({
        items: [],
        categories: [],
        loading: false,
        saving: false,
        uploadingImage: false,
        error: null,
    }),

    getters: {
        activeItems: (state) => state.items.filter((item) => item.is_active),
        byId: (state) => (id) => state.items.find((item) => item.id === id),
    },

    actions: {
        async fetchProducts(filters = {}) {
            this.loading = true;
            this.error = null;

            try {
                const params = {};
                if (filters.search) params.search = filters.search;
                if (filters.categoryId) params.category_id = filters.categoryId;

                const res = await api.get('/products', { params });
                this.items = res.data.data;
            } catch (err) {
                this.error = err.response?.data?.message || 'No se pudo cargar el catálogo';
                throw err;
            } finally {
                this.loading = false;
            }
        },

        async fetchCategories() {
            try {
                const res = await api.get('/categories');
                this.categories = res.data.data;
            } catch (err) {
                this.error = err.response?.data?.message || 'No se pudieron cargar las categorías';
                throw err;
            }
        },

        async createProduct(payload) {
            this.saving = true;

            try {
                const res = await api.post('/products', payload);
                await this.fetchProducts();
                return res.data.data;
            } finally {
                this.saving = false;
            }
        },

        async updateProduct(id, payload) {
            this.saving = true;

            try {
                const res = await api.put(`/products/${id}`, payload);
                await this.fetchProducts();
                return res.data.data;
            } finally {
                this.saving = false;
            }
        },

        async deleteProduct(id) {
            await api.delete(`/products/${id}`);
            this.items = this.items.filter((item) => item.id !== id);
        },

        async toggleActive(id) {
            const res = await api.patch(`/products/${id}/toggle-active`);
            const updated = res.data.data;
            const index = this.items.findIndex((item) => item.id === id);

            if (index !== -1) {
                this.items[index] = updated;
            }

            return updated;
        },

        async uploadImage(id, file) {
            this.uploadingImage = true;

            try {
                const form = new FormData();
                form.append('image', file);

                const res = await api.post(`/products/${id}/image`, form, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });

                const updated = res.data.data;
                const index = this.items.findIndex((item) => item.id === id);

                if (index !== -1) {
                    this.items[index] = updated;
                }

                return updated;
            } finally {
                this.uploadingImage = false;
            }
        },
    },
});
