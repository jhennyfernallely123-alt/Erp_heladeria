<template>
    <div class="login-page">
        <!-- ============ PANEL IZQUIERDO ============ -->
        <section class="brand-panel">
            <div class="brand-content">
                <div class="logo-icon">
                    <IceCreamConeArt />
                </div>

                <h1 class="brand-name">Dulce Helado</h1>

                <p class="brand-subtitle">
                    Más que helados,<br />
                    momentos felices
                </p>

                <div class="heart-divider">
                    <span class="divider-bar" />
                    <AppIcon :name="Heart" :size="26" class="fill-petrol-600 text-petrol-600" />
                    <span class="divider-bar" />
                </div>

                <div class="illustration-area">
                    <!-- Ilustracion: galera de helado con frutos y hoja de menta -->
                    <div class="icecream-art">
                        <div class="cup-art">
                            <IceCreamCupArt />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Beneficios inferiores -->
            <div class="brand-footer">
                <div
                    v-for="feature in features"
                    :key="feature.label"
                    class="feature"
                >
                    <span class="feature-icon">
                        <AppIcon :name="feature.icon" :size="21" />
                    </span>
                    <span v-html="feature.label" />
                </div>
            </div>
        </section>

        <!-- ============ PANEL DERECHO ============ -->
        <section class="form-panel">
            <div class="login-card">
                <header class="form-header">
                    <h2>Inicia sesión</h2>
                    <p>
                        Selecciona tu perfil e ingresa tus credenciales<br />
                        para continuar.
                    </p>
                </header>

                <!-- Perfiles -->
                <div class="roles">
                    <button
                        v-for="role in roles"
                        :key="role.email"
                        type="button"
                        class="role-card"
                        :class="{ active: email === role.email }"
                        :aria-pressed="email === role.email"
                        @click="selectRole(role)"
                    >
                        <span class="role-icon">
                            <AppIcon :name="role.icon" :size="27" />
                        </span>
                        <span class="role-name">{{ role.name }}</span>
                        <span class="role-description" v-html="role.description" />
                    </button>
                </div>

                <!-- Formulario -->
                <form class="login-form" @submit.prevent="submitLogin">
                    <div class="input-group" :class="{ invalid: error && !email }">
                        <span class="input-icon">
                            <AppIcon :name="User" :size="18" />
                        </span>
                        <span class="input-content">
                            <input
                                v-model="email"
                                type="email"
                                name="username"
                                placeholder="Usuario"
                                autocomplete="username"
                                required
                            />
                            <small>Ingresa tu usuario</small>
                        </span>
                    </div>

                    <div class="input-group" :class="{ invalid: error && !password }">
                        <span class="input-icon">
                            <AppIcon :name="Lock" :size="18" />
                        </span>
                        <span class="input-content">
                            <input
                                v-model="password"
                                :type="showPassword ? 'text' : 'password'"
                                name="password"
                                placeholder="Contraseña"
                                autocomplete="current-password"
                                required
                            />
                            <small>Ingresa tu contraseña</small>
                        </span>
                        <button
                            type="button"
                            class="toggle-password"
                            :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                            @click="showPassword = !showPassword"
                        >
                            <AppIcon :name="showPassword ? EyeOff : Eye" :size="17" />
                        </button>
                    </div>

                <p v-if="error" class="message show">{{ error }}</p>

                <button type="submit" class="login-button" :disabled="loading">
                    <AppIcon :name="ArrowRight" :size="19" />
                    {{ loading ? 'Ingresando...' : 'Ingresar' }}
                </button>

                <!-- El portal del empleado es otra puerta: entra con nombre y
                     PIN, no con usuario y contrasena. -->
                <router-link to="/empleados" class="employee-portal-link">
                    <AppIcon :name="Users" :size="16" />
                    Soy empleado y quiero ver mi ficha
                </router-link>
            </form>

                <footer class="card-footer">
                    <div class="footer-brand">
                        <AppIcon :name="IceCreamBowl" :size="16" class="text-petrol-600" />
                        Dulce Helado
                    </div>
                </footer>
            </div>
        </section>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import {
    ArrowRight,
    Eye,
    EyeOff,
    Heart,
    IceCreamBowl,
    Leaf,
    Lock,
    ShieldCheck,
    Smile,
    User,
    UserRound,
    Users,
    Wallet,
} from 'lucide-vue-next';
import AppIcon from '../components/ui/AppIcon.vue';
import IceCreamConeArt from '../components/login/IceCreamConeArt.vue';
import IceCreamCupArt from '../components/login/IceCreamCupArt.vue';
import { useAuthStore } from '../stores/auth';

const authStore = useAuthStore();
const router = useRouter();

const email = ref('admin@heladeria.com');
const password = ref('password');
const error = ref(null);
const loading = ref(false);
const showPassword = ref(false);

const roles = [
    {
        name: 'Administrador',
        email: 'admin@heladeria.com',
        description: 'Acceso total al sistema',
        icon: ShieldCheck,
    },
    {
        name: 'Mesero',
        email: 'mesero@heladeria.com',
        description: 'Toma de pedidos<br>y atención en sala',
        icon: UserRound,
    },
    {
        name: 'Cajero',
        email: 'cajero@heladeria.com',
        description: 'Ventas y caja',
        icon: Wallet,
    },
];

const features = [
    { label: 'Helados<br>de calidad', icon: IceCreamBowl },
    { label: 'Ingredientes<br>frescos', icon: Leaf },
    { label: 'Clientes<br>felices', icon: Smile },
];

const selectRole = (role) => {
    email.value = role.email;
    password.value = 'password';
    error.value = null;
};

const submitLogin = async () => {
    error.value = null;
    loading.value = true;

    try {
        await authStore.login(email.value, password.value);
        // El destino lo decide el guard de rutas segun los roles del usuario.
        router.push('/');
    } catch (err) {
        error.value = err.response?.data?.message || 'Credenciales inválidas';
    } finally {
        loading.value = false;
    }
};
</script>

<style scoped>
.login-page {
    min-height: 100vh;
    display: grid;
    grid-template-columns: 1fr 1fr;
    overflow: hidden;
    position: relative;
    background: linear-gradient(120deg, #e1f4f1 0%, #d5f1ed 100%);
    font-family: 'Plus Jakarta Sans', 'Segoe UI', Arial, sans-serif;
    color: #245c65;
}

/*
 * Formas decorativas del fondo: banda diagonal arriba y ola abajo.
 *
 * Van en .login-page y no en el panel izquierdo porque el fondo es uno solo:
 * con las formas en el panel, la banda terminaba justo en la mitad de la
 * pantalla y el diseño se veía partido. Acá ocupan el ancho completo y pasan
 * por debajo del formulario, que va con z-index 1.
 */
.login-page::before {
    content: '';
    position: absolute;
    width: 1100px;
    height: 460px;
    top: -230px;
    right: -180px;
    background: #cdeeea;
    border-radius: 0 0 0 46%;
    transform: rotate(-18deg);
}

.login-page::after {
    content: '';
    position: absolute;
    width: 1500px;
    height: 300px;
    bottom: -150px;
    left: -300px;
    background: #a7dfd9;
    border-radius: 46% 54% 0 0;
    transform: rotate(-6deg);
}

/* ============ PANEL IZQUIERDO ============ */
.brand-panel {
    position: relative;
    z-index: 1;
    /* height y no min-height: el panel se ajusta a la ventana y el contenido
       se comprime, en vez de crecer y empujar los beneficios de abajo fuera de
       la pantalla. Con min-height, en ventanas bajas habia que hacer scroll
       solo para ver "Helados de calidad". */
    height: 100vh;
    padding: 24px 38px 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: space-between;
    overflow: hidden;
}

.brand-content,
.brand-footer {
    position: relative;
    z-index: 1;
}

.brand-content {
    width: 100%;
    max-width: 510px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.logo-icon {
    width: 74px;
    height: 84px;
    margin-bottom: 6px;
    filter: drop-shadow(0 4px 2px rgba(35, 107, 115, 0.12));
}

/* ============ LOGO Y NOMBRE ============ */
.brand-name {
    font-family: Pacifico, 'Brush Script MT', 'Segoe Script', cursive;
    font-size: clamp(34px, 3.6vw, 52px);
    font-weight: 400;
    font-style: italic;
    color: #1a4a52;
    line-height: 1.1;
    text-align: center;
    margin: 0;
}

.brand-subtitle {
    font-size: 18px;
    line-height: 1.4;
    color: #1f4e57;
    font-weight: 500;
    text-align: center;
    margin-top: 8px;
}

.heart-divider {
    margin-top: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
}

.divider-bar {
    width: 46px;
    height: 5px;
    border-radius: 3px;
    background: #4ba9a6;
}

/* ============ ZONA DE ILUSTRACIONES ============ */
.illustration-area {
    width: 100%;
    /* min-height: 0 + flex: 1 deja que el vaso se encoja en vez de empujar. */
    min-height: 0;
    flex: 1 1 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    /* Contenedor propio con overflow escondido: los corazones decorativos se
       posicionan en porcentajes y antes se salían de la caja. */
    overflow: hidden;
    margin-top: 14px;
}

.icecream-art {
    position: relative;
    display: flex;
    justify-content: center;
    align-items: center;
    /* Ancho acotado al del vaso. Antes esta caja tomaba todo el ancho del
       panel, y los corazones decorativos, que se posicionan en porcentajes
       respecto de ella, quedaban lejos del vaso, casi en el borde de la
       pantalla. */
    width: 100%;
    max-width: 300px;
    min-width: 0;
    min-height: 0;
}

.cup-art {
    width: 100%;
    max-width: 300px;
    max-height: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 0;
}

.cup-art :deep(svg) {
    max-height: 100%;
    max-width: 100%;
    height: auto;
    width: auto;
}

/* ============ BENEFICIOS INFERIORES ============ */
.brand-footer {
    width: 100%;
    max-width: 490px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-top: 14px;
    padding-bottom: 2px;
    flex-shrink: 0;
}

.feature {
    display: flex;
    align-items: center;
    gap: 9px;
    font-size: 12px;
    line-height: 1.35;
    font-weight: 500;
    color: #1f4e57;
}

.feature-icon {
    display: flex;
    color: #159b9b;
}

/* ============ PANEL DERECHO ============ */
.form-panel {
    height: 100vh;
    padding: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    z-index: 1;
    overflow: hidden;
}

.login-card {
    width: 100%;
    max-width: 430px;
    background: rgba(255, 255, 255, 0.94);
    border-radius: 13px;
    padding: 35px 25px 28px;
    box-shadow: 0 12px 35px rgba(47, 117, 117, 0.09);
}

/* ============ ENCABEZADO ============ */
.form-header {
    text-align: center;
    margin-bottom: 24px;
}

.form-header h2 {
    font-size: 30px;
    font-weight: 800;
    color: #1a4a52;
    margin: 0 0 8px;
    letter-spacing: -0.01em;
}

.form-header p {
    font-size: 14px;
    line-height: 1.5;
    color: #6d8894;
}

/* ============ PERFILES ============ */
.roles {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    margin-bottom: 23px;
}

.role-card {
    min-height: 105px;
    border: 1px solid #dce8eb;
    background: #fff;
    border-radius: 10px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 10px 5px;
    cursor: pointer;
    color: #245c65;
    transition: all 0.2s ease;
}

.role-card:hover {
    border-color: #4ba9a6;
    background: #f3fbfa;
    transform: translateY(-2px);
}

.role-card.active {
    border: 1px solid #4ba9a6;
    background: #dff5f1;
    box-shadow: 0 3px 10px rgba(75, 169, 166, 0.08);
}

.role-icon {
    display: flex;
    margin-bottom: 8px;
    color: #1f4e57;
}

.role-name {
    font-size: 14px;
    font-weight: 700;
    margin-bottom: 4px;
    color: #1f4e57;
}

.role-description {
    font-size: 10.5px;
    line-height: 1.35;
    color: #6d8894;
    text-align: center;
}

/* ============ CAMPOS ============ */
.login-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.input-group {
    display: flex;
    align-items: stretch;
    width: 100%;
    height: 58px;
    border: 1px solid #dfe8ea;
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
    transition: border 0.2s ease, box-shadow 0.2s ease;
}

.input-group:focus-within {
    border-color: #4ba9a6;
    box-shadow: 0 0 0 3px rgba(75, 169, 166, 0.10);
}

.input-group.invalid {
    border-color: #e8a99a;
}

.input-icon {
    width: 52px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f6f9fa;
    border-right: 1px solid #eef2f3;
    color: #1f4e57;
}

.input-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 0 12px;
    min-width: 0;
}

.input-content input {
    width: 100%;
    border: none;
    outline: none;
    background: transparent;
    font-size: 14.5px;
    color: #1f4e57;
    padding: 0;
}

.input-content input::placeholder {
    color: #8ba1ab;
}

.input-content small {
    font-size: 11.5px;
    color: #8ba1ab;
    margin-top: 2px;
}

.toggle-password {
    border: none;
    background: transparent;
    cursor: pointer;
    padding: 0 14px;
    color: #8ba1ab;
    display: flex;
    align-items: center;
}

/* ============ BOTON ============ */
.login-button {
    width: 100%;
    height: 50px;
    margin-top: 8px;
    border: none;
    border-radius: 10px;
    background: linear-gradient(90deg, #4caaa7, #3f9f9c);
    color: #fff;
    font-size: 15.5px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: all 0.2s ease;
}

.login-button:hover:not(:disabled) {
    background: #328f8c;
    transform: translateY(-1px);
    box-shadow: 0 5px 12px rgba(75, 169, 166, 0.2);
}

.login-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Puerta secundaria al portal del empleado. */
.employee-portal-link {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 14px;
    padding: 10px;
    border: 1px dashed #b9dcd9;
    border-radius: 10px;
    color: #3f9f9c;
    font-size: 12.5px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
}

.employee-portal-link:hover {
    background: #f0faf9;
    border-color: #4caaa7;
}

/* ============ PIE ============ */
.card-footer {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-top: 26px;
    color: #6d8894;
    font-size: 12.5px;
}

.card-footer::before,
.card-footer::after {
    content: '';
    height: 1px;
    background: #e3ecee;
    flex: 1;
}

.footer-brand {
    display: flex;
    align-items: center;
    gap: 7px;
    white-space: nowrap;
}

/* ============ MENSAJES ============ */
.message {
    padding: 10px;
    border-radius: 8px;
    font-size: 12px;
    text-align: center;
    margin-top: 5px;
    background: #fff0ed;
    color: #a74635;
}

/* ============ RESPONSIVE ============ */
@media (max-width: 1000px) {
    .brand-panel {
        padding: 20px;
    }

    .form-panel {
        padding: 20px;
    }

    .icecream-art {
        transform: scale(0.9);
    }
}

@media (max-width: 760px) {
    .login-page {
        grid-template-columns: 1fr;
    }

    .brand-panel {
        display: none;
    }

    .form-panel {
        height: 100vh;
        padding: 22px 15px;
    }

    .login-card {
        max-width: 430px;
        padding: 30px 20px;
    }
}

@media (max-width: 380px) {
    .roles {
        gap: 6px;
    }

    .role-card {
        padding: 8px 3px;
    }

    .role-name {
        font-size: 11px;
    }

    .role-description {
        font-size: 8px;
    }

    .login-card {
        padding: 25px 15px;
    }
}
</style>
