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
        router.visit('/dashboard');
    } else {
        error.value = result.message;
        loading.value = false;
    }
};
</script>

<template>
    <Head title="Iniciar Sesión" />

    <div class="min-h-screen flex items-center justify-center bg-gray-100">
        <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800">Iniciar Sesión</h1>
                <p class="text-gray-600 mt-2">Sistema de Gestión</p>
            </div>

            <form @submit.prevent="submitLogin" class="space-y-6">
                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Correo Electrónico
                    </label>
                    <input
                        v-model="form.email"
                        type="email"
                        required
                        placeholder="admin@sistema.com"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    />
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Contraseña
                    </label>
                    <input
                        v-model="form.password"
                        type="password"
                        required
                        placeholder="••••••••"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    />
                </div>

                <!-- Error -->
                <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                    {{ error }}
                </div>

                <!-- Submit -->
                <button
                    type="submit"
                    :disabled="loading"
                    class="w-full bg-indigo-600 text-white py-2 px-4 rounded-lg hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {{ loading ? 'Iniciando sesión...' : 'Iniciar Sesión' }}
                </button>
            </form>
        </div>
    </div>
</template>