import { defineStore } from 'pinia';
import api from '../api';

/**
 * Vista de caja del administrador.
 *
 * La heladeria tiene una sola gaveta fisica y el dinero es del local, no del
 * cajero: el saldo se deriva de los turnos, de los movimientos y de los
 * retiros. Aca vive solo la lectura de eso y el registro de retiros, que es lo
 * unico que el admin escribe sobre la caja.
 */
export const useCashStore = defineStore('cash', {
    state: () => ({
        overview: null,
        history: [],
        loading: false,
        saving: false,
        error: '',
    }),

    getters: {
        /** Saldo en vivo de la gaveta. */
        balance: (state) => state.overview?.drawer?.balance ?? 0,

        /** La gaveta esta abierta y sabemos quien la tiene. */
        isOpen: (state) => Boolean(state.overview?.drawer?.is_open),

        /** Nombre de quien tiene el turno abierto de caja. */
        holderName: (state) => state.overview?.drawer?.session?.user_name ?? null,

        today: (state) => state.overview?.today ?? {},

        movements: (state) => state.overview?.movements ?? [],

        withdrawals: (state) => state.overview?.withdrawals ?? [],

        /**
         * Total de efectivo que salio de la gaveta hoy entre gastos menores y
         * retiros. Es el numero que el admin necesita para saber cuanto dinero
         * dejo de controlar.
         */
        totalOutToday: (state) => {
            const t = state.overview?.today ?? {};
            return Number(t.cash_out || 0) + Number(t.withdrawals || 0);
        },
    },

    actions: {
        async loadOverview() {
            this.loading = true;
            try {
                const res = await api.get('/cash-register/overview');
                this.overview = res.data.data;
                this.error = '';
            } catch (err) {
                this.error = err.response?.data?.message || 'No se pudo cargar el estado de caja.';
                throw err;
            } finally {
                this.loading = false;
            }
        },

        async loadHistory() {
            const res = await api.get('/cash-register/history');
            this.history = res.data.data || [];
        },

        async load() {
            await Promise.all([this.loadOverview(), this.loadHistory()]);
        },

        /**
         * Registra un retiro de gaveta. No exige turno abierto: la gaveta
         * existe aunque este cerrada.
         */
        async withdraw(payload) {
            this.saving = true;
            try {
                const res = await api.post('/cash-register/withdrawals', payload);
                await this.loadOverview();
                this.error = '';
                return res.data.data;
            } catch (err) {
                this.error = err.response?.data?.message || 'No se pudo registrar el retiro.';
                throw err;
            } finally {
                this.saving = false;
            }
        },

        /**
         * Gasto menor registrado por el admin sobre el turno abierto del
         * cajero. Falla con 422 si no hay turno abierto.
         */
        async addMovement(payload) {
            this.saving = true;
            try {
                const res = await api.post('/cash-register/movements', payload);
                await this.loadOverview();
                this.error = '';
                return res.data.data;
            } catch (err) {
                this.error = err.response?.data?.message || 'No se pudo registrar el movimiento.';
                throw err;
            } finally {
                this.saving = false;
            }
        },
    },
});
