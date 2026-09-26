import { defineStore } from 'pinia';
import api from '../api';

export const useTableStore = defineStore('tables', {
    state: () => ({
        tables: [],
        loading: false,
        error: null,
    }),

    getters: {
        availableTables: (state) => state.tables.filter(t => t.status === 'available'),
        occupiedTables: (state) => state.tables.filter(t => t.status === 'occupied'),
        reservedTables: (state) => state.tables.filter(t => t.status === 'reserved'),
    },

    actions: {
        async fetchTables() {
            this.loading = true;
            try {
                const res = await api.get('/tables');
                this.tables = res.data.data;
            } catch (err) {
                this.error = err.message;
            } finally {
                this.loading = false;
            }
        },

        async updateTableStatus(tableId, status) {
            try {
                const res = await api.put(`/tables/${tableId}`, { status });
                const idx = this.tables.findIndex(t => t.id === tableId);
                if (idx !== -1) {
                    this.tables[idx] = { ...this.tables[idx], ...res.data.data };
                }
            } catch (err) {
                throw err;
            }
        }
    }
});
