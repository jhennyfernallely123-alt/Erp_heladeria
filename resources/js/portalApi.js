import axios from 'axios';

/**
 * Cliente del portal de empleados.
 *
 * A proposito NO es el `api` de resources/js/api.js: ese manda el token de
 * Sanctum, que es un login con email y contrasena. Aca viaja la sesion del
 * portal, que abrio un PIN de 4 digitos y solo habilita las rutas del portal.
 *
 * Mezclarlos seria el error grave: un PIN adivinado daria acceso a toda la
 * API. Por eso van en clientes separados y en stores separados.
 */
const portalApi = axios.create({
    baseURL: '/api/v1',
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    },
});

portalApi.interceptors.request.use((config) => {
    const token = localStorage.getItem('erp_portal_token');
    if (token) {
        config.headers['X-Portal-Token'] = token;
    }

    // Con FormData el navegador pone el boundary del multipart. Si se deja el
    // Content-Type fijo, axios no lo agrega y el servidor no parsea el archivo.
    if (config.data instanceof FormData) {
        delete config.headers['Content-Type'];
    }

    return config;
});

portalApi.interceptors.response.use(
    (response) => response,
    (error) => {
        // 401 aca significa sesion de portal vencida, no login perdido. No se
        // toca erp_token: el empleado nunca tuvo uno y el admin que entro por
        // su cuenta sigue con su sesion.
        if (error.response && error.response.status === 401) {
            localStorage.removeItem('erp_portal_token');
            localStorage.removeItem('erp_portal_user');
        }
        return Promise.reject(error);
    }
);

export default portalApi;
