import { defineStore } from 'pinia';
import portalApi from '../portalApi';

const TOKEN_KEY = 'erp_portal_token';
const USER_KEY = 'erp_portal_user';

/**
 * Portal del empleado: elegir nombre, entrar con PIN y ver la ficha.
 *
 * La sesion es aparte de la del admin a proposito. El PIN de 4 digitos abre un
 * token opaco que solo habilita /portal/*, nunca el resto de la API. Por eso
 * este store no usa `api` sino `portalApi`.
 *
 * Fuera de esta pantalla no se necesita nada mas del portal, asi que la
 * sesion vive solo en localStorage y se pierde al cerrar el navegador.
 */
export const useEmployeeStore = defineStore('employee', {
    state: () => ({
        employees: [],
        profile: (() => {
            const raw = localStorage.getItem(USER_KEY);
            return raw ? JSON.parse(raw) : null;
        })(),
        loading: false,
        saving: false,
        error: '',
        pinError: '',
    }),

    getters: {
        /** El empleado ya se identifico con su PIN. */
        isAuthenticated: (state) => Boolean(state.profile),

        /** Datos de la sesion actual. */
        employeeId: (state) => state.profile?.id ?? null,

        employeeName: (state) => state.profile?.name ?? null,

        vacation: (state) => state.profile?.vacation ?? null,

        requests: (state) => state.profile?.requests ?? [],

        pendingRequests: (state) =>
            (state.profile?.requests ?? []).filter((r) => r.status === 'pending'),

        approvedRequests: (state) =>
            (state.profile?.requests ?? []).filter((r) => r.status === 'approved'),
    },

    actions: {
        async loadEmployees() {
            this.loading = true;
            try {
                const res = await portalApi.get('/portal/employees');
                this.employees = res.data.data || [];
            } catch (err) {
                this.error = 'No se pudo cargar el listado de empleados.';
                throw err;
            } finally {
                this.loading = false;
            }
        },

        /** Valida el PIN y abre la sesion del portal. */
        async login(userId, pin) {
            this.saving = true;
            this.pinError = '';

            try {
                const res = await portalApi.post('/portal/login', {
                    user_id: userId,
                    pin,
                });

                const data = res.data.data;

                localStorage.setItem(TOKEN_KEY, data.portal_token);
                localStorage.setItem(USER_KEY, JSON.stringify(data.profile));

                this.profile = data.profile;
                return data.profile;
            } catch (err) {
                this.pinError = err.response?.data?.message || 'No se pudo iniciar sesión.';
                throw err;
            } finally {
                this.saving = false;
            }
        },

        /** Refresca la ficha: saldos y solicitudes al día. */
        async refreshProfile() {
            const res = await portalApi.get('/portal/profile');
            this.setProfile(res.data.data);
            return res.data.data;
        },

        /**
         * Crea una solicitud desde el calendario.
         *
         * Acepta un objeto normal o un FormData. El FormData es lo que se usa
         * cuando el motivo exige evidencia, porque los archivos van en el
         * multipart y no se pueden mandar dentro del JSON.
         *
         * El saldo no se descuenta acá: eso pasa cuando el administrador aprueba.
         */
        async requestTimeOff(payload) {
            this.saving = true;
            this.error = '';

            try {
                await portalApi.post('/portal/requests', payload);
                await this.refreshProfile();
                return true;
            } catch (err) {
                this.error = err.response?.data?.message || 'No se pudo enviar la solicitud.';
                throw err;
            } finally {
                this.saving = false;
            }
        },

        setProfile(profile) {
            this.profile = profile;
            if (profile) {
                localStorage.setItem(USER_KEY, JSON.stringify(profile));
            }
        },

        /**
         * Cierra la sesión del portal.
         *
         * Borra el token y el perfil guardados, y también la lista de nombres:
         * si queda en memoria, al volver al portal se verían los compañeros
         * aunque la sesión esté cerrada.
         */
        async logout() {
            try {
                await portalApi.post('/portal/logout');
            } catch (err) {
                // Si la sesion ya expiro en el servidor, cerrarla localmente
                // igual es lo correcto.
            } finally {
                this.forget();
            }
        },

        forget() {
            this.profile = null;
            this.employees = [];
            this.error = '';
            this.pinError = '';
            localStorage.removeItem(TOKEN_KEY);
            localStorage.removeItem(USER_KEY);
        },
    },
});
