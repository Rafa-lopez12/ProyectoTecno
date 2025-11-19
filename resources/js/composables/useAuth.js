import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const token = ref(localStorage.getItem('token'));
const user = ref(JSON.parse(localStorage.getItem('user') || 'null'));

export function useAuth() {
    const isAuthenticated = computed(() => !!token.value);

    const login = async (email, password) => {
        try {
            const response = await axios.post('/api/auth/login', {
                email,
                password
            });

            token.value = response.data.token;
            user.value = response.data.user;

            localStorage.setItem('token', response.data.token);
            localStorage.setItem('user', JSON.stringify(response.data.user));

            // Configurar token para futuras peticiones
            axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`;

            return { success: true, data: response.data };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Error al iniciar sesión'
            };
        }
    };

    const logout = async () => {
        try {
            if (token.value) {
                await axios.post('/api/auth/logout');
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

        try {
            const response = await axios.get('/api/auth/me');
            user.value = response.data.user;
            localStorage.setItem('user', JSON.stringify(response.data.user));
            return true;
        } catch (error) {
            // Token inválido o expirado
            await logout();
            return false;
        }
    };

    // Configurar interceptor para manejar errores 401
    axios.interceptors.response.use(
        response => response,
        error => {
            if (error.response?.status === 401) {
                logout();
            }
            return Promise.reject(error);
        }
    );

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