<!-- resources/js/Layout/AlumnoLayout.vue -->
<script setup>
import { ref, onMounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useAuth } from '../composables/useAuth';

const { user, logout, checkAuth } = useAuth();
const showMobileMenu = ref(false);

onMounted(async () => {
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
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
        <nav class="bg-white border-b border-gray-200 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <!-- Logo y Nombre -->
                    <div class="flex items-center">
                        <div class="shrink-0 flex items-center">
                            <Link :href="route('alumno.mis-ventas')" class="text-lg sm:text-xl font-bold text-indigo-600">
                                Mi Portal Estudiantil
                            </Link>
                        </div>

                        <!-- Desktop Navigation -->
                        <div class="hidden md:flex space-x-4 lg:space-x-8 ml-6 lg:ml-10">
                            <Link 
                                :href="route('alumno.mis-ventas')" 
                                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition"
                                :class="$page.url.startsWith('/alumno/mis-ventas') ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            >
                                Mis Ventas
                            </Link>
                            <Link 
                                :href="route('alumno.mis-clases')" 
                                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition"
                                :class="$page.url.startsWith('/alumno/mis-clases') ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            >
                                Mis Clases
                            </Link>
                        </div>
                    </div>

                    <!-- User Menu Desktop -->
                    <div class="hidden md:flex items-center space-x-4">
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-700">
                                {{ user?.nombre }} {{ user?.apellido }}
                            </p>
                            <p class="text-xs text-gray-500">
                                CI: {{ user?.ci }}
                            </p>
                        </div>
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
                            :href="route('alumno.mis-ventas')" 
                            class="block px-4 py-2 text-base font-medium rounded-md"
                            :class="$page.url.startsWith('/alumno/mis-ventas') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50'"
                        >
                            Mis Ventas
                        </Link>
                        <Link 
                            :href="route('alumno.mis-clases')" 
                            class="block px-4 py-2 text-base font-medium rounded-md"
                            :class="$page.url.startsWith('/alumno/mis-clases') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50'"
                        >
                            Mis Clases
                        </Link>
                    </div>
                    <div class="border-t border-gray-200 mt-4 pt-4">
                        <div class="px-4 py-2">
                            <p class="text-sm font-medium text-gray-700">
                                {{ user?.nombre }} {{ user?.apellido }}
                            </p>
                            <p class="text-xs text-gray-500">
                                CI: {{ user?.ci }}
                            </p>
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

        <!-- Footer -->
        <footer class="bg-white border-t border-gray-200 mt-auto">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <p class="text-center text-sm text-gray-500">
                    © 2024 Portal Estudiantil. Todos los derechos reservados.
                </p>
            </div>
        </footer>
    </div>
</template>