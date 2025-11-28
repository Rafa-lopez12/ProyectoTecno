<script setup>
import { ref, onMounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useAuth } from '../composables/useAuth';
import { useTheme } from '../composables/useTheme';
import ThemeSelector from '../Components/ThemeSelector.vue';

const { user, logout, checkAuth } = useAuth();
const { initTheme } = useTheme();
const showMobileMenu = ref(false);

onMounted(async () => {
    initTheme(); // Inicializar tema
    const isAuth = await checkAuth();
    if (!isAuth) {
        router.visit('login');
    }
});

const handleLogout = async () => {
    await logout();
};
</script>

<template>
    <div class="min-h-screen theme-background transition-colors duration-300">
        <nav class="theme-surface theme-border border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <!-- Logo y Nav Desktop -->
                    <div class="flex">
                        <div class="shrink-0 flex items-center">
                            <Link :href="route('dashboard')" class="text-lg sm:text-xl font-bold theme-text">
                                Sistema Educativo
                            </Link>
                        </div>

                        <!-- Desktop Navigation -->
                        <div class="hidden md:flex space-x-4 lg:space-x-8 ml-6 lg:ml-10">
                            <Link 
                                :href="route('dashboard')" 
                                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition"
                                :class="$page.url.startsWith('/dashboard') ? 'theme-primary-border theme-text' : 'border-transparent theme-text-secondary hover:theme-text'"
                            >
                                Dashboard
                            </Link>
                            <Link 
                                v-if="user?.rol !== 'tutor'"
                                :href="route('usuarios.index')" 
                                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition"
                                :class="$page.url.startsWith('/usuarios') ? 'theme-primary-border theme-text' : 'border-transparent theme-text-secondary hover:theme-text'"
                            >
                                Usuarios
                            </Link>
                            <Link 
                                v-if="user?.rol !== 'tutor'"
                                :href="route('horarios.index')" 
                                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition"
                                :class="$page.url.startsWith('/horarios') ? 'theme-primary-border theme-text' : 'border-transparent theme-text-secondary hover:theme-text'"
                            >
                                Horarios
                            </Link>
                            <Link 
                                :href="route('inscripciones.index')" 
                                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition"
                                :class="$page.url.startsWith('/inscripciones') ? 'theme-primary-border theme-text' : 'border-transparent theme-text-secondary hover:theme-text'"
                            >
                                Inscripciones
                            </Link>
                            <Link 
                                :href="route('asistencias.index')" 
                                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition"
                                :class="$page.url.startsWith('/asistencias') ? 'theme-primary-border theme-text' : 'border-transparent theme-text-secondary hover:theme-text'"
                            >
                                Asistencias
                            </Link>
                            <Link 
                                v-if="user?.rol !== 'tutor'"
                                :href="route('ventas.index')" 
                                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition"
                                :class="$page.url.startsWith('/ventas') ? 'theme-primary-border theme-text' : 'border-transparent theme-text-secondary hover:theme-text'"
                            >
                                Ventas
                            </Link>
                            <Link 
                                v-if="user?.rol !== 'tutor'"
                                :href="route('reportes.index')" 
                                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition"
                                :class="$page.url.startsWith('/reportes') ? 'theme-primary-border theme-text' : 'border-transparent theme-text-secondary hover:theme-text'"
                            >
                                Reportes
                            </Link>
                        </div>
                    </div>

                    <!-- User Menu Desktop -->
                    <div class="hidden md:flex items-center space-x-4">
                        <ThemeSelector />
                        <span class="text-sm theme-text">
                            {{ user?.nombre }}
                        </span>
                        <button
                            @click="handleLogout"
                            class="text-sm theme-text hover:theme-primary-text px-3 py-2 rounded-md hover:theme-primary-bg transition"
                        >
                            Salir
                        </button>
                    </div>

                    <!-- Mobile menu button -->
                    <div class="md:hidden flex items-center">
                        <button
                            @click="showMobileMenu = !showMobileMenu"
                            class="theme-text hover:theme-primary-text p-2"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path v-if="!showMobileMenu" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Mobile Navigation -->
                <div v-show="showMobileMenu" class="md:hidden pb-4">
                    <div class="space-y-1">
                        <Link 
                            :href="route('dashboard')" 
                            class="block px-4 py-2 text-base font-medium rounded-md"
                            :class="$page.url.startsWith('/dashboard') ? 'theme-primary-bg theme-primary-text' : 'theme-text hover:theme-primary-bg'"
                        >
                            Dashboard
                        </Link>
                        <Link 
                            :href="route('usuarios.index')" 
                            class="block px-4 py-2 text-base font-medium rounded-md"
                            :class="$page.url.startsWith('/usuarios') ? 'theme-primary-bg theme-primary-text' : 'theme-text hover:theme-primary-bg'"
                        >
                            Usuarios
                        </Link>
                        <Link 
                            :href="route('horarios.index')" 
                            class="block px-4 py-2 text-base font-medium rounded-md"
                            :class="$page.url.startsWith('/horarios') ? 'theme-primary-bg theme-primary-text' : 'theme-text hover:theme-primary-bg'"
                        >
                            Horarios
                        </Link>
                        <Link 
                            :href="route('inscripciones.index')" 
                            class="block px-4 py-2 text-base font-medium rounded-md"
                            :class="$page.url.startsWith('/inscripciones') ? 'theme-primary-bg theme-primary-text' : 'theme-text hover:theme-primary-bg'"
                        >
                            Inscripciones
                        </Link>
                        <Link 
                            :href="route('asistencias.index')" 
                            class="block px-4 py-2 text-base font-medium rounded-md"
                            :class="$page.url.startsWith('/asistencias') ? 'theme-primary-bg theme-primary-text' : 'theme-text hover:theme-primary-bg'"
                        >
                            Asistencias
                        </Link>
                        <Link 
                            :href="route('ventas.index')" 
                            class="block px-4 py-2 text-base font-medium rounded-md"
                            :class="$page.url.startsWith('/ventas') ? 'theme-primary-bg theme-primary-text' : 'theme-text hover:theme-primary-bg'"
                        >
                            Ventas
                        </Link>
                        <Link 
                            :href="route('reportes.index')" 
                            class="block px-4 py-2 text-base font-medium rounded-md"
                            :class="$page.url.startsWith('/reportes') ? 'theme-primary-bg theme-primary-text' : 'theme-text hover:theme-primary-bg'"
                        >
                            Reportes
                        </Link>
                    </div>
                    <div class="border-t theme-border mt-4 pt-4">
                        <div class="px-4 py-2 flex items-center justify-between">
                            <span class="text-sm theme-text">{{ user?.nombre }} {{ user?.apellido }}</span>
                            <ThemeSelector />
                        </div>
                        <button
                            @click="handleLogout"
                            class="w-full text-left px-4 py-2 text-base font-medium theme-text hover:theme-primary-bg rounded-md"
                        >
                            Cerrar Sesión
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <main class="py-6 sm:py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <slot />
            </div>
        </main>
    </div>
</template>