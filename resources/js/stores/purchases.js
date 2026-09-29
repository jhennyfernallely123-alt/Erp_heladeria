import { defineStore } from 'pinia';
import api from '../api';

/**
 * Explica un error de la API con palabras útiles.
 *
 * Antes todas las acciones caían en un texto genérico cuando la respuesta no
 * venía en JSON (por ejemplo un 500 con la página de error de Laravel), así
 * que el admin veía "No se pudo agregar a la lista" sin ninguna pista de por
 * qué. Con el status y el motivo de siempre alcanza.
 */
const describe = (err, fallback) => {
    const status = err.response?.status;
    const message = err.response?.data?.message;

    if (message) {
        return message;
    }

    if (status) {
        return `${fallback} (error ${status})`;
    }

    // Sin respuesta: se cortó la conexión o el servidor no respondió.
    return `${fallback}. No hubo respuesta del servidor, revisá la conexión.`;
};

export { describe as describeApiError };

/**
 * Lista de compras.
 *
 * El admin la arma desde Inventario. Cuando marca una compra como hecha, la
 * cantidad se suma sola al stock del insumo.
 */
export const usePurchaseStore = defineStore('purchases', {
    state: () => ({
        purchases: [],
        stats: { pending_count: 0, bought_today: 0, supplies: 0 },
        statusFilter: 'pending',
        loading: false,
        saving: false,
        error: '',
    }),

    getters: {
        pending: (state) => state.purchases.filter((p) => p.status === 'pending'),

        bought: (state) => state.purchases.filter((p) => p.status === 'bought'),
    },

    actions: {
        async load(status = null) {
            this.loading = true;
            const wanted = status ?? this.statusFilter;

            try {
                const res = await api.get('/purchases', { params: { status: wanted } });

                this.purchases = res.data.data || [];
                this.stats = res.data.meta?.stats || this.stats;
                this.error = '';
            } catch (err) {
                this.error = describe(err, 'No se pudo cargar la lista de compras.');
                throw err;
            } finally {
                this.loading = false;
            }
        },

        /**
         * Pasa un insumo a la lista. Si ya está, el backend suma la cantidad.
         *
         * La cantidad se manda como texto con 3 decimales. El backend exige
         * min:0.001, asi que un faltante de 0.0005 (que sale al restar
         * 1.5 - 1.4995) se rechazaba con un error de validacion que no decía
         * nada util. Ahi el problema es que casi no falta nada, no la app.
         */
        async add(purchaseId, quantity, note = null) {
            this.saving = true;

            try {
                const res = await api.post('/purchases', {
                    supply_id: purchaseId,
                    quantity: Number(Number(quantity).toFixed(3)),
                    note,
                });

                await this.load();
                return res.data.data;
            } catch (err) {
                this.error = describe(err, 'No se pudo agregar a la lista.');
                throw err;
            } finally {
                this.saving = false;
            }
        },

        async update(id, payload) {
            this.saving = true;

            try {
                await api.patch(`/purchases/${id}`, payload);
                await this.load();
                return true;
            } catch (err) {
                this.error = describe(err, 'No se pudo actualizar la compra.');
                throw err;
            } finally {
                this.saving = false;
            }
        },

        /** Marca como hecha: suma la cantidad al stock del insumo. */
        async markBought(id, receivedQuantity = null) {
            this.saving = true;

            try {
                const res = await api.post(`/purchases/${id}/bought`, {
                    received_quantity: receivedQuantity,
                });

                await this.load();
                return res.data.data;
            } catch (err) {
                this.error = describe(err, 'No se pudo marcar la compra.');
                throw err;
            } finally {
                this.saving = false;
            }
        },

        /** Cancela: saca la compra de la lista y descuenta del stock. */
        async cancel(id) {
            this.saving = true;

            try {
                await api.delete(`/purchases/${id}`);
                await this.load();
                return true;
            } catch (err) {
                this.error = describe(err, 'No se pudo cancelar la compra.');
                throw err;
            } finally {
                this.saving = false;
            }
        },
    },
});
