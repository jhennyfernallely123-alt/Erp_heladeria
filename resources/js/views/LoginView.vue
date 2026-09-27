<template>
    <div class="min-h-screen flex bg-mint-100 font-sans">
        <!-- Panel izquierdo: marca -->
        <div class="hidden lg:flex lg:w-[54%] relative overflow-hidden bg-gradient-to-br from-mint-200 via-mint-100 to-brand-200">
            <!-- Círculos decorativos -->
            <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full bg-white/25" />
            <div class="absolute -bottom-32 -right-20 w-[28rem] h-[28rem] rounded-full bg-brand-300/25" />
            <div class="absolute top-1/3 right-16 w-40 h-40 rounded-full bg-white/20" />

            <div class="relative flex-1 flex flex-col items-center justify-center px-10 py-12 text-center">
                <!-- Marca -->
                <div class="flex flex-col items-center">
                    <div
                        class="h-16 w-16 rounded-2xl bg-white/70 flex items-center justify-center shadow-lg shadow-brand-900/10"
                    >
                        <AppIcon :name="IceCreamCone" :size="34" class="text-brand-600" />
                    </div>

                    <h1 class="font-script text-brand-700 text-5xl mt-4 leading-none">Dulce Helado</h1>

                    <p class="mt-3 text-brand-800/80 font-medium text-lg">
                        Más que helados,
                        <br />
                        momentos felices
                    </p>

                    <div class="mt-4 flex items-center gap-2 text-brand-600/70">
                        <span class="h-px w-10 bg-brand-500/40" />
                        <AppIcon :name="Heart" :size="18" class="fill-brand-400 text-brand-400" />
                        <span class="h-px w-10 bg-brand-500/40" />
                    </div>
                </div>

                <!-- Ilustración central -->
                <div class="relative mt-10 flex items-center justify-center">
                    <div
                        class="h-52 w-52 rounded-full bg-white/35 backdrop-blur-sm flex items-center justify-center shadow-inner"
                    >
                        <AppIcon :name="IceCreamBowl" :size="112" :stroke-width="1.25" class="text-brand-600" />
                    </div>
                </div>

                <!-- Textos manuscritos -->
                <p
                    class="absolute left-[18%] bottom-[26%] font-caveat text-brand-700/80 text-2xl -rotate-6 select-none"
                >
                    ¡Bienvenido a tu heladería favorita!
                </p>
                <p
                    class="absolute right-[10%] top-[14%] font-caveat text-brand-700/70 text-xl rotate-6 select-none"
                >
                    ¡El sabor también se administra!
                </p>
            </div>

            <!-- Pie de funcionalidades -->
            <div class="relative pb-10 flex items-center justify-center gap-10">
                <div
                    v-for="feature in features"
                    :key="feature.label"
                    class="flex items-center gap-2 text-brand-800/75"
                >
                    <AppIcon :name="feature.icon" :size="18" class="text-brand-600" />
                    <span class="text-[11px] font-semibold leading-tight max-w-[5.5rem]">
                        {{ feature.label }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Panel derecho: formulario -->
        <div class="flex-1 flex items-center justify-center p-6 sm:p-10 bg-mint-50">
            <div class="w-full max-w-md">
                <div class="mb-8 flex justify-center lg:hidden">
                    <div class="flex flex-col items-center">
                        <div class="h-14 w-14 rounded-2xl bg-white shadow-md flex items-center justify-center">
                            <AppIcon :name="IceCreamCone" :size="28" class="text-brand-600" />
                        </div>
                        <p class="font-script text-brand-700 text-3xl mt-2">Dulce Helado</p>
                    </div>
                </div>

                <div class="text-center mb-8">
                    <h2 class="text-2xl font-extrabold text-slate-900">Inicia sesión</h2>
                    <p class="text-sm text-slate-500 mt-1.5">
                        Selecciona tu perfil e ingresa tus credenciales
                        <br class="hidden sm:block" />
                        para continuar.
                    </p>
                </div>

                <!-- Perfiles -->
                <div class="grid grid-cols-3 gap-3 mb-6">
                    <button
                        v-for="role in roles"
                        :key="role.email"
                        type="button"
                        class="group rounded-2xl border-2 p-3 text-center transition-all"
                        :class="
                            email === role.email
                                ? 'border-brand-400 bg-brand-50 shadow-sm'
                                : 'border-slate-200 bg-white hover:border-brand-300 hover:bg-brand-50/40'
                        "
                        :aria-pressed="email === role.email"
                        @click="selectRole(role)"
                    >
                        <span
                            class="mx-auto mb-2 h-11 w-11 rounded-xl flex items-center justify-center transition-colors"
                            :class="
                                email === role.email
                                    ? 'bg-brand-500 text-white'
                                    : 'bg-slate-100 text-slate-500 group-hover:bg-brand-100 group-hover:text-brand-600'
                            "
                        >
                            <AppIcon :name="role.icon" :size="22" />
                        </span>
                        <p class="text-sm font-bold text-slate-800">{{ role.name }}</p>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-tight">
                            {{ role.description }}
                        </p>
                    </button>
                </div>

                <p
                    v-if="error"
                    class="mb-4 flex items-start gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-2.5 text-xs font-medium text-rose-700"
                >
                    <AppIcon :name="AlertCircle" :size="16" class="shrink-0 mt-px" />
                    {{ error }}
                </p>

                <form class="space-y-4" @submit.prevent="submitLogin">
                    <div>
                        <label for="login-user" class="block text-sm font-semibold text-slate-800">
                            Usuario
                        </label>
                        <div class="mt-1.5 relative">
                            <AppIcon
                                :name="User"
                                :size="17"
                                class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"
                            />
                            <input
                                id="login-user"
                                v-model="email"
                                type="email"
                                required
                                autocomplete="username"
                                placeholder="Ingresa tu usuario"
                                class="w-full rounded-xl border border-slate-200 bg-white py-3 pl-11 pr-4 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition"
                            />
                        </div>
                    </div>

                    <div>
                        <label for="login-password" class="block text-sm font-semibold text-slate-800">
                            Contraseña
                        </label>
                        <div class="mt-1.5 relative">
                            <AppIcon
                                :name="Lock"
                                :size="17"
                                class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"
                            />
                            <input
                                id="login-password"
                                v-model="password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                autocomplete="current-password"
                                placeholder="Ingresa tu contraseña"
                                class="w-full rounded-xl border border-slate-200 bg-white py-3 pl-11 pr-11 text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition"
                            />
                            <button
                                type="button"
                                class="absolute right-3 top-1/2 -translate-y-1/2 p-1 rounded-lg text-slate-400 hover:text-slate-600 transition"
                                :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                                @click="showPassword = !showPassword"
                            >
                                <AppIcon :name="showPassword ? EyeOff : Eye" :size="17" />
                            </button>
                        </div>
                    </div>

                    <AppButton
                        type="submit"
                        class="w-full"
                        size="md"
                        :label="loading ? 'Ingresando...' : 'Ingresar'"
                        :icon="ArrowRight"
                        :icon-size="18"
                        :loading="loading"
                        variant="primary"
                        tone="brand"
                    />
                </form>

                <div class="mt-8 flex items-center gap-3">
                    <span class="h-px flex-1 bg-slate-200" />
                    <span class="flex items-center gap-1.5 text-xs font-semibold text-slate-500">
                        <AppIcon :name="Lightbulb" :size="15" class="text-brand-500" />
                        Dulce Helado
                    </span>
                    <span class="h-px flex-1 bg-slate-200" />
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
    Lightbulb,
    Lock,
    ShieldCheck,
    Smile,
    User,
    UserRound,
    Wallet,
} from 'lucide-vue-next';
import AppButton from '../components/ui/AppButton.vue';
import AppIcon from '../components/ui/AppIcon.vue';
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
        description: 'Toma pedidos y atención en sala',
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
        router.push('/');
    } catch (err) {
        error.value = err.response?.data?.message || 'Credenciales inválidas';
    } finally {
        loading.value = false;
    }
};
</script>
