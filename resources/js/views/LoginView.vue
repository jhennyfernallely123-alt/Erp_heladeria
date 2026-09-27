<template>
    <div class="min-h-screen flex bg-aguamarina-100 font-sans">
        <!-- ============ PANEL IZQUIERDO DECORATIVO ============ -->
        <div
            class="hidden lg:flex lg:w-1/2 relative overflow-hidden bg-aguamarina-100 select-none"
        >
            <!-- Formas orgánicas de fondo -->
            <div class="absolute -top-28 -left-20 w-[26rem] h-[26rem] rounded-full bg-aguamarina-200/45" />
            <div class="absolute -bottom-40 -right-24 w-[30rem] h-[30rem] rounded-full bg-aguamarina-200/35" />
            <div class="absolute top-1/4 -left-16 w-72 h-72 rounded-full bg-aguamarina-50/50" />
            <svg
                class="absolute bottom-0 left-0 w-full text-aguamarina-200/40"
                viewBox="0 0 600 200"
                preserveAspectRatio="none"
                aria-hidden="true"
            >
                <path
                    d="M0 140c80-30 150 20 230 0s120-70 200-40 110 60 170 40v60H0z"
                    fill="currentColor"
                    opacity="0.5"
                />
            </svg>

            <div class="relative flex-1 flex flex-col items-center px-10 pt-10 pb-8">
                <!-- Ilustración superior: helado de cono -->
                <div class="h-28 w-24 shrink-0">
                    <IceCreamConeArt />
                </div>

                <!-- Nombre de la heladería -->
                <h1 class="font-script text-petrol-700 text-[2.75rem] leading-none mt-4">
                    Dulce Helado
                </h1>

                <!-- Eslogan en dos líneas -->
                <p class="mt-3 text-center text-petrol-500/85 text-[1.0625rem] font-medium leading-relaxed">
                    Más que helados,<br />
                    momentos felices
                </p>

                <!-- Corazón turquesa -->
                <AppIcon :name="Heart" :size="22" class="mt-3 text-aguamarina-400 fill-aguamarina-300" />

                <!-- Ilustración central: vaso con corazón -->
                <div class="mt-6 w-[19rem] max-w-full shrink-0">
                    <IceCreamCupArt />
                </div>

                <!-- Mensaje manuscrito en la zona derecha -->
                <div class="absolute right-[6%] top-[54%] max-w-[11rem]">
                    <p
                        class="font-caveat text-[1.375rem] leading-tight text-aguamarina-700 -rotate-6"
                    >
                        ¡Bienvenido<br />
                        a tu heladería<br />
                        favorita!
                    </p>
                    <div class="mt-1.5 flex items-center gap-1.5 pl-3">
                        <span class="h-px w-8 bg-aguamarina-400/70" />
                        <AppIcon :name="Heart" :size="15" class="text-aguamarina-400 fill-aguamarina-300" />
                        <span class="h-px w-5 bg-aguamarina-400/70" />
                    </div>
                </div>

                <!-- Mensaje manuscrito superior derecho -->
                <div class="absolute right-[7%] top-[12%] max-w-[10rem] text-right">
                    <p class="font-caveat text-[1.125rem] leading-tight text-aguamarina-600/90 rotate-3">
                        ¡El sabor también<br />
                        se administra!
                    </p>
                </div>
            </div>

            <!-- Elementos informativos del pie, con separadores verticales -->
            <div class="relative px-10 pb-9">
                <div class="flex items-stretch justify-center">
                    <div
                        v-for="(feature, index) in features"
                        :key="feature.label"
                        class="flex items-center gap-2 px-7"
                        :class="index > 0 ? 'border-l border-aguamarina-300/50' : ''"
                    >
                        <AppIcon :name="feature.icon" :size="19" class="text-petrol-500 shrink-0" />
                        <span class="text-[0.6875rem] font-semibold text-petrol-600/90 leading-tight max-w-[4.5rem]">
                            {{ feature.label }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============ LADO DERECHO: TARJETA ============ -->
        <div
            class="relative w-full lg:w-1/2 flex items-center justify-center bg-aguamarina-50 px-5 pb-8 pt-28 sm:px-8 lg:py-8"
        >
            <div
                class="w-full max-w-[23rem] bg-papel rounded-2xl shadow-card px-7 py-8 sm:px-8 sm:py-9"
            >
                <!-- Encabezado -->
                <h2 class="text-center text-[1.5rem] font-semibold text-petrol-700 leading-tight">
                    Inicia sesión
                </h2>
                <p class="mt-1.5 text-center text-[0.6875rem] leading-relaxed text-niebla-400">
                    Selecciona tu perfil e ingresa tus credenciales<br class="hidden sm:block" />
                    para continuar.
                </p>

                <!-- Selector de perfiles -->
                <div class="mt-6 grid grid-cols-3 gap-2.5">
                    <button
                        v-for="role in roles"
                        :key="role.email"
                        type="button"
                        class="group rounded-[10px] border px-2 py-3 text-center transition-all"
                        :class="
                            email === role.email
                                ? 'bg-aguamarina-50 border-aguamarina-400 shadow-sm'
                                : 'bg-white border-slate-200 hover:border-aguamarina-300'
                        "
                        :aria-pressed="email === role.email"
                        @click="selectRole(role)"
                    >
                        <span
                            class="mx-auto mb-1.5 flex h-8 w-8 items-center justify-center rounded-lg transition-colors"
                            :class="
                                email === role.email
                                    ? 'bg-aguamarina-500 text-white'
                                    : 'bg-petrol-50 text-petrol-500 group-hover:bg-aguamarina-50 group-hover:text-aguamarina-600'
                            "
                        >
                            <AppIcon :name="role.icon" :size="17" />
                        </span>
                        <p class="text-[0.8125rem] font-semibold leading-tight text-petrol-700">
                            {{ role.name }}
                        </p>
                        <p class="mt-0.5 text-[0.625rem] leading-tight text-niebla-400">
                            {{ role.description }}
                        </p>
                    </button>
                </div>

                <!-- Error -->
                <p
                    v-if="error"
                    class="mt-4 flex items-start gap-1.5 rounded-[10px] border border-rose-200 bg-rose-50 px-3 py-2 text-[0.6875rem] font-medium leading-snug text-rose-600"
                >
                    <AppIcon :name="AlertCircle" :size="14" class="shrink-0 mt-px" />
                    {{ error }}
                </p>

                <!-- Campos -->
                <form class="mt-5 space-y-3" @submit.prevent="submitLogin">
                    <!-- Usuario -->
                    <div
                        class="flex items-center gap-2.5 rounded-[10px] border bg-white px-3 py-2 transition-colors"
                        :class="
                            error && !email ? 'border-rose-300' : 'border-slate-200 focus-within:border-aguamarina-400 focus-within:ring-2 focus-within:ring-aguamarina-200'
                        "
                    >
                        <span
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-petrol-50 text-petrol-600"
                        >
                            <AppIcon :name="User" :size="17" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <input
                                v-model="email"
                                type="email"
                                required
                                autocomplete="username"
                                placeholder="Usuario"
                                class="w-full border-0 bg-transparent p-0 text-[0.8125rem] text-petrol-700 placeholder:text-petrol-400 focus:outline-none focus:ring-0"
                            />
                            <p class="text-[0.625rem] leading-tight text-niebla-300">
                                Ingresa tu usuario
                            </p>
                        </div>
                    </div>

                    <!-- Contraseña -->
                    <div
                        class="flex items-center gap-2.5 rounded-[10px] border bg-white px-3 py-2 transition-colors"
                        :class="
                            error && !password
                                ? 'border-rose-300'
                                : 'border-slate-200 focus-within:border-aguamarina-400 focus-within:ring-2 focus-within:ring-aguamarina-200'
                        "
                    >
                        <span
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-petrol-50 text-petrol-600"
                        >
                            <AppIcon :name="Lock" :size="17" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <input
                                v-model="password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                autocomplete="current-password"
                                placeholder="Contraseña"
                                class="w-full border-0 bg-transparent p-0 text-[0.8125rem] text-petrol-700 placeholder:text-petrol-400 focus:outline-none focus:ring-0"
                            />
                            <p class="text-[0.625rem] leading-tight text-niebla-300">
                                Ingresa tu contraseña
                            </p>
                        </div>
                        <button
                            type="button"
                            class="shrink-0 p-1 rounded-md text-niebla-300 hover:text-petrol-500 transition-colors"
                            :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                            @click="showPassword = !showPassword"
                        >
                            <AppIcon :name="showPassword ? EyeOff : Eye" :size="16" />
                        </button>
                    </div>

                    <AppButton
                        type="submit"
                        class="w-full"
                        label="Ingresar"
                        :icon="ArrowRight"
                        :icon-size="17"
                        :loading="loading"
                        :loading-text="'Ingresando...'"
                        variant="primary"
                        tone="aguamarina"
                    />
                </form>

                <!-- Pie de tarjeta -->
                <div class="mt-6 flex items-center gap-3">
                    <span class="h-px flex-1 bg-slate-200" />
                    <span class="flex items-center gap-1.5 text-[0.6875rem] font-medium text-niebla-400">
                        <AppIcon :name="IceCreamBowl" :size="15" class="text-petrol-500" />
                        Dulce Helado
                    </span>
                    <span class="h-px flex-1 bg-slate-200" />
                </div>
            </div>

            <!-- Marca solo en móvil -->
            <div class="absolute left-0 right-0 top-6 flex justify-center lg:hidden">
                <div class="flex flex-col items-center">
                    <div class="h-11 w-11 rounded-xl bg-white shadow-suave flex items-center justify-center">
                        <AppIcon :name="IceCreamCone" :size="22" class="text-aguamarina-600" />
                    </div>
                    <p class="font-script text-[1.5rem] text-petrol-700 mt-1.5">Dulce Helado</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import {
    AlertCircle,
    ArrowRight,
    Eye,
    EyeOff,
    Heart,
    IceCreamBowl,
    IceCreamCone,
    Leaf,
    ShieldCheck,
    Smile,
    User,
    UserRound,
    Wallet,
} from 'lucide-vue-next';
import AppButton from '../components/ui/AppButton.vue';
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
        description: 'Toma de pedidos y atención en sala',
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
    { label: 'Helados de calidad', icon: IceCreamBowl },
    { label: 'Ingredientes frescos', icon: Leaf },
    { label: 'Clientes felices', icon: Smile },
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
        // El destino lo decide el guard de rutas según los roles del usuario.
        router.push('/');
    } catch (err) {
        error.value = err.response?.data?.message || 'Credenciales inválidas';
    } finally {
        loading.value = false;
    }
};
</script>
