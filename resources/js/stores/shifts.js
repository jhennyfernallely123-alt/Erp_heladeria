import { defineStore } from 'pinia';
import api from '../api';

/**
 * Turno activo del dispositivo.
 *
 * El servidor no guarda estado de dispositivo: el id del turno abierto vive en
 * localStorage y viaja en cada pedido. Cada dispositivo tiene su propio turno,
 * asi que dos dispositivos pueden tener meseros distintos al mismo tiempo.
 */
const STORAGE_KEY = 'erp_work_shift_id';

export const useShiftStore = defineStore('shifts', {
    state: () => ({
        activeShiftId: Number(localStorage.getItem(STORAGE_KEY)) || null,
        activeShift: null,
        workers: [],
        shifts: [],
        summary: [],
        users: [],
        loading: false,
        saving: false,
        error: '',
        pinError: '',
    }),

    getters: {
        /** El dispositivo tiene un turno abierto y verificado. */
        hasActiveShift: (state) => Boolean(state.activeShiftId && state.activeShift?.status === 'open'),

        /** Nombre de quien esta de turno en este dispositivo. */
        activeWorkerName: (state) => state.activeShift?.user?.name ?? null,
    },

    actions: {
        /** Confirma con el servidor que el turno guardado siga abierto. */
        async loadActive() {
            if (!this.activeShiftId) {
                this.activeShift = null;
                return null;
            }

            try {
                const res = await api.get(`/shifts/${this.activeShiftId}`);
                this.activeShift = res.data.data;
            } catch (err) {
                // El turno ya no existe o lo cerro el admin: este dispositivo
                // queda sin turno y vuelve al listado.
                this.forget();
                return null;
            }

            return this.activeShift;
        },

        async loadWorkers() {
            this.loading = true;
            try {
                const res = await api.get('/shifts/workers');
                this.workers = res.data.data;
            } finally {
                this.loading = false;
            }
        },

        async loadShifts() {
            const res = await api.get('/shifts');
            this.shifts = res.data.data;
        },

        async loadSummary() {
            const res = await api.get('/shifts/summary');
            this.summary = res.data.data;
        },

        async loadUsers() {
            const res = await api.get('/users');
            this.users = res.data.data;
        },

        async open(userId, pin) {
            this.saving = true;
            this.pinError = '';

            try {
                const res = await api.post('/shifts/open', { user_id: userId, pin });
                this.setActive(res.data.data);
                this.error = '';
                return res.data.data;
            } catch (err) {
                this.pinError = err.response?.data?.message || 'No se pudo iniciar el turno.';
                throw err;
            } finally {
                this.saving = false;
            }
        },

        async close(notes = '') {
            if (!this.activeShiftId) return null;

            this.saving = true;
            try {
                const res = await api.post(`/shifts/${this.activeShiftId}/close`, { notes });
                this.forget();
                return res.data.data;
            } catch (err) {
                this.error = err.response?.data?.message || 'No se pudo cerrar el turno.';
                throw err;
            } finally {
                this.saving = false;
            }
        },

        /** Cierra un turno que quedo abierto (el admin lo puede hacer). */
        async closeOther(shiftId, notes = '') {
            const res = await api.post(`/shifts/${shiftId}/close`, { notes });
            await this.loadShifts();
            await this.loadSummary();
            return res.data.data;
        },

        async createWorker(payload) {
            const res = await api.post('/users', payload);
            await this.loadUsers();
            await this.loadWorkers();
            return res.data.data;
        },

        async resetPin(userId, pin) {
            const res = await api.post(`/users/${userId}/reset-pin`, { pin });
            await this.loadUsers();
            return res.data.data;
        },

        setActive(shift) {
            this.activeShift = shift;
            this.activeShiftId = shift?.id ?? null;

            if (this.activeShiftId) {
                localStorage.setItem(STORAGE_KEY, String(this.activeShiftId));
            }
        },

        forget() {
            this.activeShift = null;
            this.activeShiftId = null;
            localStorage.removeItem(STORAGE_KEY);
        },
    },
});
