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
                    <AppIcon :name="Heart" :size="24" class="fill-aguamarina-500 text-aguamarina-500" />
                </div>

                <div class="illustration-area">
                    <!-- Ilustracion: recipiente con bolas de helado -->
                    <div class="icecream-art">
                        <div class="icecream-bowl">
                            <div class="cone" />

                            <div class="icecream-scoops">
                                <div class="scoop chocolate" />
                                <div class="scoop strawberry" />
                                <div class="scoop vanilla" />

                                <div class="topping">
                                    <svg viewBox="0 0 24 24" class="h-full w-full" aria-hidden="true">
                                        <path
                                            d="M12 8c-3-2.2-6.5.6-6.5 4.1C5.5 15.6 8.6 19 12 22c3.4-3 6.5-6.4 6.5-9.9C18.5 8.6 15 5.8 12 8z"
                                            fill="#E4685D"
                                        />
                                        <path
                                            d="M12 8c-.6-1.6-.2-3.4 1.2-4.4 1.2-.8 2.6-.7 3.6-.2-1 .9-1.4 2.1-1.2 3.2.2 1 .8 1.6 1.6 2z"
                                            fill="#6FA25F"
                                        />
                                        <ellipse
                                            cx="9.4"
                                            cy="12.4"
                                            rx="1.3"
                                            ry="1.9"
                                            fill="#FFFFFF"
                                            opacity="0.5"
                                            transform="rotate(-24 9.4 12.4)"
                                        />
                                    </svg>
                                </div>
                            </div>

                            <div class="bowl">
                                <svg viewBox="0 0 24 22" class="h-full w-full" aria-hidden="true">
                                    <path
                                        d="M12 20.5C10.5 19 3 12.6 3 8.3 3 5.1 5.5 3 8.4 3c1.9 0 3 .9 3.6 2 .6-1.1 1.7-2 3.6-2 2.9 0 5.4 2.1 5.4 5.3 0 4.3-7.5 10.7-9 12.2z"
                                        fill="none"
                                        stroke="#FFFFFF"
                                        stroke-width="2"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Mensaje decorativo -->
                    <div class="welcome-note">
                        ¡Bienvenido<br />
                        a tu heladería<br />
                        favorita!
                        <span class="note-hearts">♡ ─ ♥ ─</span>
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
            <div class="top-note">
                ¡El sabor<br />
                también se<br />
                administra!
                <AppIcon
                    :name="Heart"
                    :size="13"
                    class="fill-aguamarina-600 text-aguamarina-600 inline-block align-baseline"
                />
            </div>

            <div class="login-card">
                <header class="form-header">
                    <h2>
                        Inicia sesión
                        <AppIcon
                            :name="Sparkles"
                            :size="16"
                            class="inline-block align-baseline text-aguamarina-300"
                        />
                    </h2>
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
    Sparkles,
    Smile,
    User,
    UserRound,
    Wallet,
} from 'lucide-vue-next';
import AppIcon from '../components/ui/AppIcon.vue';
import IceCreamConeArt from '../components/login/IceCreamConeArt.vue';
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

/* ============ PANEL IZQUIERDO ============ */
.brand-panel {
    position: relative;
    min-height: 100vh;
    padding: 35px 45px 30px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: space-between;
    overflow: hidden;
}

/* Formas decorativas del fondo */
.brand-panel::before {
    content: '';
    position: absolute;
    width: 580px;
    height: 350px;
    top: -120px;
    right: -170px;
    background: rgba(115, 207, 196, 0.15);
    border-radius: 48% 52% 64% 36%;
    transform: rotate(-20deg);
}

.brand-panel::after {
    content: '';
    position: absolute;
    width: 750px;
    height: 240px;
    bottom: -130px;
    left: -170px;
    background: #a7dfd9;
    border-radius: 48% 52% 0 0;
    transform: rotate(-7deg);
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
    width: 78px;
    height: 84px;
    margin-bottom: 5px;
    filter: drop-shadow(0 4px 2px rgba(35, 107, 115, 0.12));
}

/* ============ LOGO Y NOMBRE ============ */
.brand-name {
    font-family: Pacifico, 'Brush Script MT', 'Segoe Script', cursive;
    font-size: clamp(38px, 4vw, 58px);
    font-weight: 400;
    font-style: italic;
    color: #236b73;
    line-height: 1.1;
    text-align: center;
    margin: 0;
}

.brand-subtitle {
    font-size: 17px;
    line-height: 1.4;
    color: #5e858b;
    text-align: center;
    margin-top: 10px;
}

.heart-divider {
    margin-top: 5px;
    display: flex;
    justify-content: center;
}

/* ============ ZONA DE ILUSTRACIONES ============ */
.illustration-area {
    width: 100%;
    min-height: 310px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    align-items: center;
    gap: 5px;
    margin-top: 10px;
}

.icecream-art {
    position: relative;
    display: flex;
    justify-content: center;
    align-items: center;
    min-width: 0;
}

.icecream-bowl {
    position: relative;
    width: 220px;
    height: 220px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-end;
}

.icecream-scoops {
    position: relative;
    z-index: 2;
    width: 100%;
    height: 125px;
    display: flex;
    align-items: flex-end;
    justify-content: center;
}

.scoop {
    width: 100px;
    height: 105px;
    border-radius: 50% 50% 42% 42%;
    position: relative;
    margin: 0 -13px;
    box-shadow: inset -7px -8px 0 rgba(0, 0, 0, 0.05);
}

/* Reflejo de cada bola */
.scoop::after {
    content: '';
    position: absolute;
    width: 18px;
    height: 13px;
    top: 24px;
    left: 24px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.35);
}

.scoop.chocolate {
    background: #73503e;
    transform: rotate(-10deg);
}

.scoop.strawberry {
    background: #ffaaa9;
    transform: translateY(-20px);
}

.scoop.vanilla {
    background: #f7e3b9;
    transform: rotate(10deg);
}

.topping {
    position: absolute;
    z-index: 3;
    top: 2px;
    left: 35%;
    width: 30px;
    height: 30px;
}

.cone {
    position: absolute;
    z-index: 1;
    width: 38px;
    height: 110px;
    top: 100px;
    left: 17px;
    background: repeating-linear-gradient(135deg, #d99b55 0px, #d99b55 7px, #f1c58b 8px, #f1c58b 12px);
    clip-path: polygon(0 0, 100% 0, 50% 100%);
    transform: rotate(-12deg);
}

.bowl {
    position: relative;
    z-index: 4;
    width: 190px;
    height: 100px;
    margin-top: -10px;
    background: linear-gradient(120deg, #64c6be, #43b2aa);
    border-radius: 10px 10px 45% 45%;
    box-shadow: 0 10px 18px rgba(43, 121, 118, 0.16);
    border-top: 7px solid #9fe0d9;
    display: flex;
    align-items: center;
    justify-content: center;
}

.bowl svg {
    width: 52px;
    height: 48px;
}

/* Mensaje decorativo */
.welcome-note {
    font-family: Caveat, 'Brush Script MT', 'Segoe Script', cursive;
    font-size: 25px;
    line-height: 1.25;
    font-style: italic;
    color: #236b73;
    transform: rotate(-5deg);
    text-align: center;
    padding: 10px;
}

.note-hearts {
    display: block;
    font-size: 22px;
    margin-top: 8px;
    color: #236b73;
}

/* ============ BENEFICIOS INFERIORES ============ */
.brand-footer {
    width: 100%;
    max-width: 490px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-top: 20px;
    padding-bottom: 5px;
}

.feature {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 10px;
    line-height: 1.4;
    color: #245c65;
}

.feature-icon {
    display: flex;
    color: #159b9b;
}

/* ============ PANEL DERECHO ============ */
.form-panel {
    min-height: 100vh;
    padding: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    z-index: 1;
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
    font-size: 25px;
    font-weight: 700;
    color: #204f59;
    margin: 0 0 5px;
}

.form-header p {
    font-size: 12px;
    line-height: 1.5;
    color: #718995;
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
    margin-bottom: 6px;
    color: #236b73;
}

.role-name {
    font-size: 12px;
    font-weight: 500;
    margin-bottom: 4px;
}

.role-description {
    font-size: 9px;
    line-height: 1.3;
    color: #718995;
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
    height: 50px;
    border: 1px solid #dce8eb;
    border-radius: 10px;
    overflow: hidden;
    background: #fff;
    transition: border 0.2s ease, box-shadow 0.2s ease;
}

.input-group:focus-within {
    border-color: #4ba9a6;
    box-shadow: 0 0 0 3px rgba(75, 169, 166, 0.08);
}

.input-group.invalid {
    border-color: #e8a99a;
}

.input-icon {
    width: 45px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f4f8f9;
    border-right: 1px solid #edf1f2;
    color: #236b73;
}

.input-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 0 10px;
    min-width: 0;
}

.input-content input {
    width: 100%;
    border: none;
    outline: none;
    background: transparent;
    font-size: 12px;
    color: #245c65;
    padding: 0;
}

.input-content input::placeholder {
    color: #879ca7;
}

.input-content small {
    font-size: 9px;
    color: #718995;
    margin-top: 3px;
}

.toggle-password {
    border: none;
    background: transparent;
    cursor: pointer;
    padding: 0 12px;
    color: #78919b;
    display: flex;
    align-items: center;
}

/* ============ BOTON ============ */
.login-button {
    width: 100%;
    height: 43px;
    margin-top: 8px;
    border: none;
    border-radius: 9px;
    background: linear-gradient(90deg, #48a9a6, #4eaaa6);
    color: #fff;
    font-size: 13px;
    font-weight: 600;
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

/* ============ PIE ============ */
.card-footer {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-top: 23px;
    color: #718995;
    font-size: 10px;
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

/* ============ DECORACIONES ============ */
.top-note {
    position: absolute;
    top: 28px;
    right: 28px;
    font-family: Caveat, 'Brush Script MT', cursive;
    color: #236b73;
    font-size: 14px;
    transform: rotate(-8deg);
    text-align: center;
}

/* ============ RESPONSIVE ============ */
@media (max-width: 1000px) {
    .brand-panel {
        padding: 30px 20px;
    }

    .form-panel {
        padding: 20px;
    }

    .illustration-area {
        grid-template-columns: 1fr;
    }

    .welcome-note {
        display: none;
    }

    .icecream-bowl {
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
        min-height: 100vh;
        padding: 22px 15px;
    }

    .login-card {
        max-width: 430px;
        padding: 30px 20px;
    }

    .top-note {
        display: none;
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
