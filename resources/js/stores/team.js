import { defineStore } from 'pinia';
import api from '../api';

/**
 * Vista de equipo del administrador: fichas, saldos y solicitudes.
 *
 * A diferencia de `stores/employees.js`, aca si se usa `api` con el token de
 * Sanctum: el admin se loguea con email y contrasena y gestiona todo el
 * equipo desde adentro del sistema.
 */
export const useTeamStore = defineStore('team', {
    state: () => ({
        employees: [],
        requests: [],
        loading: false,
        saving: false,
        error: '',
    }),

    getters: {
        /** Solicitudes que esperan una decisión del administrador. */
        pending: (state) => state.requests.filter((r) => r.status === 'pending'),

        /** Empleados a los que el admin todavia no les cargo la fecha de alta. */
        missingHireDate: (state) => state.employees.filter((e) => !e.hired_at),

        /** Proximos cumpleaños y dias de familia, para celebrarlos. */
        upcomingCelebrations: (state) =>
            state.employees
                .flatMap((e) =>
                    (e.upcoming || []).map((u) => ({
                        ...u,
                        employee_id: e.id,
                        employee_name: e.name,
                    }))
                )
                .sort((a, b) => a.days_away - b.days_away),
    },

    actions: {
        async load() {
            this.loading = true;
            try {
                const [team, requests] = await Promise.all([
                    api.get('/team'),
                    api.get('/team/requests', { params: { status: 'all' } }),
                ]);

                this.employees = team.data.data || [];
                this.requests = requests.data.data || [];
                this.error = '';
            } catch (err) {
                this.error = 'No se pudo cargar el equipo.';
                throw err;
            } finally {
                this.loading = false;
            }
        },

        async loadEmployee(userId) {
            const res = await api.get(`/team/${userId}`);
            return res.data.data;
        },

        /** Guarda los datos de la ficha. hired_at dispara el saldo. */
        async updateEmployee(userId, payload) {
            this.saving = true;
            try {
                const res = await api.patch(`/team/${userId}`, payload);
                const updated = res.data.data;

                const index = this.employees.findIndex((e) => e.id === userId);
                if (index !== -1) {
                    this.employees.splice(index, 1, { ...this.employees[index], ...updated });
                }

                return updated;
            } catch (err) {
                this.error = err.response?.data?.message || 'No se pudo guardar la ficha.';
                throw err;
            } finally {
                this.saving = false;
            }
        },

        /** Aprueba o rechaza una solicitud. */
        async review(requestId, status, note = null) {
            this.saving = true;
            try {
                const res = await api.post(`/team/requests/${requestId}/review`, {
                    status,
                    response_note: note,
                });

                const index = this.requests.findIndex((r) => r.id === requestId);
                if (index !== -1) {
                    this.requests.splice(index, 1, {
                        ...this.requests[index],
                        ...res.data.data,
                    });
                }

                // El saldo del empleado cambia al aprobar, asi que hay que
                // recargar el equipo entero: no sabemos de antemano a quien
                // afecta.
                await this.load();

                return res.data.data;
            } catch (err) {
                this.error = err.response?.data?.message || 'No se pudo revisar la solicitud.';
                throw err;
            } finally {
                this.saving = false;
            }
        },
    },
});
