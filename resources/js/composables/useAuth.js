import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useApi } from './useApi';
import axios from 'axios';

const token = ref(localStorage.getItem('token'));
const user = ref(JSON.parse(localStorage.getItem('user') || 'null'));

export function useAuth() {
    const { auth: authApi } = useApi();
    const isAuthenticated = computed(() => !!token.value);

    const login = async (email, password) => {
        const result = await authApi.login(email, password);

        if (result.success) {
            token.value = result.data.token;
            user.value = result.data.user;

            localStorage.setItem('token', result.data.token);
            localStorage.setItem('user', JSON.stringify(result.data.user));

            // Configurar token en axios para futuras peticiones
            axios.defaults.headers.common['Authorization'] = `Bearer ${result.data.token}`;

            return { success: true, data: result.data };
        }

        return {
            success: false,
            message: result.error || 'Error al iniciar sesión'
        };
    };

    const logout = async () => {
        try {
            if (token.value) {
                await authApi.logout();
            }
        } catch (error) {
            console.error('Error al hacer logout:', error);
        } finally {
            token.value = null;
            user.value = null;
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            delete axios.defaults.headers.common['Authorization'];
            router.visit('/login');
        }
    };

    const checkAuth = async () => {
        if (!token.value) {
            return false;
        }

        const result = await authApi.me();
        if (result.success) {
            user.value = result.data.user;
            localStorage.setItem('user', JSON.stringify(result.data.user));
            return true;
        }

        // Token inválido o expirado
        await logout();
        return false;
    };

    // Si hay token, configurarlo en axios
    if (token.value) {
        axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`;
    }

    return {
        token,
        user,
        isAuthenticated,
        login,
        logout,
        checkAuth
    };
}