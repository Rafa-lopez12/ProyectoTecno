<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useAuth } from '../composables/useAuth';

const { login } = useAuth();

const form = ref({
    email: '',
    password: ''
});

const loading = ref(false);
const error = ref('');

const submitLogin = async () => {
    loading.value = true;
    error.value = '';

    const result = await login(form.value.email, form.value.password);

    if (result.success) {
        // Redirigir según tipo de usuario
        const tipoUsuario = result.data.user.tipo_usuario;
        
        if (tipoUsuario === 'alumno') {
            router.visit('/alumno/dashboard');
        } else {
            router.visit('/dashboard');
        }
    } else {
        error.value = result.message;
        loading.value = false;
    }
};
</script>

<template>
    <Head title="Iniciar Sesión" />

    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-indigo-50 via-white to-purple-50">
        <div class="max-w-md w-full mx-4">
            <!-- Card -->
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <!-- Logo/Header -->
                <div class="text-center mb-8">
                    <div class="mx-auto w-20 h-20 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl flex items-center justify-center mb-4 shadow-lg">
                        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-800">Bienvenido</h1>
                    <p class="text-gray-600 mt-2">Sistema de Gestión Educativa</p>
                </div>

                <!-- Form -->
                <form @submit.prevent="submitLogin" class="space-y-6">
                    <!-- Email/CI Input -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Email o CI
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <input
                                v-model="form.email"
                                type="text"
                                required
                                placeholder="admin@sistema.com o 12345678"
                                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                                :class="{ 'border-red-500': error }"
                            />
                        </div>
                     
                    </div>

                    <!-- Password Input -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Contraseña
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input
                                v-model="form.password"
                                type="password"
                                required
                                placeholder="••••••••"
                                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                                :class="{ 'border-red-500': error }"
                            />
                        </div>

                    </div>

                    <!-- Error Message -->
                    <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-start">
                        <svg class="w-5 h-5 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-sm">{{ error }}</span>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        :disabled="loading || !form.email || !form.password"
                        class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-3 px-4 rounded-lg hover:from-indigo-700 hover:to-purple-700 transition disabled:opacity-50 disabled:cursor-not-allowed font-medium shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all"
                    >
                        <span v-if="!loading" class="flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                            </svg>
                            Ingresar al Sistema
                        </span>
                        <span v-else class="flex items-center justify-center">
                            <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Ingresando...
                        </span>
                    </button>
                </form>


            </div>

            <!-- Footer -->
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    ¿Problemas para acceder? Contacta con administración
                </p>
            </div>
        </div>
    </div>
</template>