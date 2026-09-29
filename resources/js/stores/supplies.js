import { defineStore } from 'pinia';
import api from '../api';
import { describeApiError as describe } from './purchases';

/**
 * Insumos de la heladería.
 *
 * No es un stock que se descuenta solo: el admin cuenta lo que queda al cierre
 * y lo carga acá. La cantidad se REEMPLAZA, no se suma.
 */
export const useSupplyStore = defineStore('supplies', {
    state: () => ({
        supplies: [],
        stats: { total_supplies: 0, pending_purchases: 0, empty_count: 0, total_items: 0 },
        loading: false,
        saving: false,
        error: '',
        deleteTarget: null,
    }),

    getters: {
        /** Insumos con existencias, que son los que se cuentan a diario. */
        active: (state) => state.supplies.filter((s) => s.is_active),

        /** Los que todavía no se contaron hoy. */
        pendingToday: (state) => state.supplies.filter((s) => s.is_active && !s.counted_today),
    },

    actions: {
        async load(search = null) {
            this.loading = true;

            try {
                const res = await api.get('/supplies', { params: search ? { search } : {} });

                this.supplies = res.data.data || [];
                this.stats = res.data.meta?.stats || this.stats;
                this.error = '';
            } catch (err) {
                this.error = 'No se pudo cargar el inventario.';
                throw err;
            } finally {
                this.loading = false;
            }
        },

        async createSupply(payload) {
            this.saving = true;

            try {
                await api.post('/supplies', payload);
                await this.load();
                return true;
            } catch (err) {
                this.error = describe(err, 'No se pudo agregar el insumo.');
                throw err;
            } finally {
                this.saving = false;
            }
        },

        async updateSupply(id, payload) {
            this.saving = true;

            try {
                await api.patch(`/supplies/${id}`, payload);
                await this.load();
                return true;
            } catch (err) {
                this.error = describe(err, 'No se pudo actualizar el insumo.');
                throw err;
            } finally {
                this.saving = false;
            }
        },

        /** Guarda el conteo del día. Reemplaza la cantidad anterior. */
        async countSupply(id, quantity, note = null) {
            this.saving = true;

            try {
                await api.post(`/supplies/${id}/count`, { quantity, note });
                await this.load();
                return true;
            } catch (err) {
                this.error = describe(err, 'No se pudo guardar la cantidad.');
                throw err;
            } finally {
                this.saving = false;
            }
        },

        askDelete(supply) {
            this.deleteTarget = supply;
        },

        cancelDelete() {
            this.deleteTarget = null;
        },

        async confirmDelete() {
            const target = this.deleteTarget;
            if (!target) return;

            this.saving = true;

            try {
                await api.delete(`/supplies/${target.id}`);
                this.deleteTarget = null;
                await this.load();
                return true;
            } catch (err) {
                this.error = describe(err, 'No se pudo eliminar el insumo.');
                throw err;
            } finally {
                this.saving = false;
            }
        },
    },
});
