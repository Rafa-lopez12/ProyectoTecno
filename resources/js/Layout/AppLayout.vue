<script setup>
import { ref, onMounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useAuth } from '../composables/useAuth';

const { user, logout, checkAuth } = useAuth();
const showMobileMenu = ref(false);

onMounted(async () => {
    const isAuth = await checkAuth();
    if (!isAuth) {
        router.visit('/login');
    }
});

const handleLogout = async () => {
    await logout();
};
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <nav class="bg-white border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <!-- Logo y Nav Desktop -->
                    <div class="flex">
                        <div class="shrink-0 flex items-center">
                            <Link :href="route('dashboard')" class="text-lg sm:text-xl font-bold text-gray-800">
                                Sistema
                            </Link>
                        </div>

                        <!-- Desktop Navigation -->
                        <div class="hidden md:flex space-x-4 lg:space-x-8 ml-6 lg:ml-10">
                            <Link 
                                :href="route('dashboard')" 
                                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition"
                                :class="$page.url.startsWith('/dashboard') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            >
                                Dashboard
                            </Link>
                            <Link 
                                :href="route('usuarios.index')" 
                                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition"
                                :class="$page.url.startsWith('/usuarios') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            >
                                Usuarios
                            </Link>
                            <Link 
                                :href="route('inscripciones.index')" 
                                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition"
                                :class="$page.url.startsWith('/inscripciones') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            >
                                Inscripciones
                            </Link>
                            <Link 
                                :href="route('asistencias.index')" 
                                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition"
                                :class="$page.url.startsWith('/asistencias') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            >
                                Asistencias
                            </Link>
                            <Link 
                                :href="route('ventas.index')" 
                                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition"
                                :class="$page.url.startsWith('/ventas') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            >
                                Ventas
                            </Link>
                            <Link 
                                :href="route('propietarios.index')" 
                                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition"
                                :class="$page.url.startsWith('/propietarios') ? 'border-indigo-400 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            >
                                Propietarios
                            </Link>
                        </div>
                    </div>

                    <!-- User Menu Desktop -->
                    <div class="hidden md:flex items-center space-x-4">
                        <span class="text-sm text-gray-700">
                            {{ user?.nombre }}
                        </span>
                        <button
                            @click="handleLogout"
                            class="text-sm text-gray-700 hover:text-gray-900 px-3 py-2 rounded-md hover:bg-gray-100"
                        >
                            Salir
                        </button>
                    </div>

                    <!-- Mobile menu button -->
                    <div class="md:hidden flex items-center">
                        <button
                            @click="showMobileMenu = !showMobileMenu"
                            class="text-gray-700 hover:text-gray-900 p-2"
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
                            :class="$page.url.startsWith('/dashboard') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50'"
                        >
                            Dashboard
                        </Link>
                        <Link 
                            :href="route('usuarios.index')" 
                            class="block px-4 py-2 text-base font-medium rounded-md"
                            :class="$page.url.startsWith('/usuarios') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50'"
                        >
                            Usuarios
                        </Link>
                        <Link 
                            :href="route('inscripciones.index')" 
                            class="block px-4 py-2 text-base font-medium rounded-md"
                            :class="$page.url.startsWith('/inscripciones') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50'"
                        >
                            Inscripciones
                        </Link>
                        <Link 
                            :href="route('asistencias.index')" 
                            class="block px-4 py-2 text-base font-medium rounded-md"
                            :class="$page.url.startsWith('/asistencias') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50'"
                        >
                            Asistencias
                        </Link>
                        <Link 
                            :href="route('ventas.index')" 
                            class="block px-4 py-2 text-base font-medium rounded-md"
                            :class="$page.url.startsWith('/ventas') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50'"
                        >
                            Ventas
                        </Link>
                        <Link 
                            :href="route('propietarios.index')" 
                            class="block px-4 py-2 text-base font-medium rounded-md"
                            :class="$page.url.startsWith('/propietarios') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50'"
                        >
                            Propietarios
                        </Link>
                    </div>
                    <div class="border-t border-gray-200 mt-4 pt-4">
                        <div class="px-4 py-2 text-sm text-gray-700">
                            {{ user?.nombre }} {{ user?.apellido }}
                        </div>
                        <button
                            @click="handleLogout"
                            class="w-full text-left px-4 py-2 text-base font-medium text-gray-700 hover:bg-gray-50 rounded-md"
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