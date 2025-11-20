import axios from 'axios';
import { ref } from 'vue';

// Configuración base de axios
axios.defaults.baseURL = '/api';
axios.defaults.headers.common['Content-Type'] = 'application/json';
axios.defaults.headers.common['Accept'] = 'application/json';

// Configurar token si existe
const token = localStorage.getItem('token');
if (token) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

// Interceptor para manejar errores 401
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 401) {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);

export function useApi() {
    const loading = ref(false);
    const error = ref(null);

    // Método genérico para peticiones
    const request = async (method, url, data = null, config = {}) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios({
                method,
                url,
                data,
                ...config
            });
            return { success: true, data: response.data };
        } catch (err) {
            error.value = err.response?.data?.message || 'Error en la petición';
            return { 
                success: false, 
                error: error.value,
                errors: err.response?.data?.errors || {}
            };
        } finally {
            loading.value = false;
        }
    };

    // ============================================
    // AUTH
    // ============================================
    const auth = {
        login: (email, password) => 
            request('POST', '/auth/login', { email, password }),
        
        logout: () => 
            request('POST', '/auth/logout'),
        
        me: () => 
            request('GET', '/auth/me'),
        
        refresh: () => 
            request('POST', '/auth/refresh')
    };

    // ============================================
    // ALUMNOS
    // ============================================
    const alumnos = {
        getAll: (params = {}) => 
            request('GET', '/v1/alumnos', null, { params }),
        
        getById: (id) => 
            request('GET', `/v1/alumnos/${id}`),
        
        create: (data) => 
            request('POST', '/v1/alumnos', data),
        
        update: (id, data) => 
            request('PUT', `/v1/alumnos/${id}`, data),
        
        delete: (id) => 
            request('DELETE', `/v1/alumnos/${id}`)
    };

    // ============================================
    // TUTORES
    // ============================================
    const tutores = {
        getAll: (params = {}) => 
            request('GET', '/v1/tutores', null, { params }),
        
        getById: (id) => 
            request('GET', `/v1/tutores/${id}`),
        
        create: (data) => 
            request('POST', '/v1/tutores', data),
        
        update: (id, data) => 
            request('PUT', `/v1/tutores/${id}`, data),
        
        delete: (id) => 
            request('DELETE', `/v1/tutores/${id}`)
    };

    // ============================================
    // PROPIETARIOS (SubPropietarios)
    // ============================================
    const propietarios = {
        getAll: (params = {}) => 
            request('GET', '/v1/propietarios', null, { params }),
        
        getById: (id) => 
            request('GET', `/v1/propietarios/${id}`),
        
        create: (data) => 
            request('POST', '/v1/propietarios', data),
        
        update: (id, data) => 
            request('PUT', `/v1/propietarios/${id}`, data),
        
        delete: (id) => 
            request('DELETE', `/v1/propietarios/${id}`)
    };

    // ============================================
    // HORARIOS
    // ============================================
    const horarios = {
        getAll: (params = {}) => 
            request('GET', '/v1/horarios', null, { params }),
        
        getById: (id) => 
            request('GET', `/v1/horarios/${id}`),
        
        create: (data) => 
            request('POST', '/v1/horarios', data),
        
        update: (id, data) => 
            request('PUT', `/v1/horarios/${id}`, data),
        
        delete: (id) => 
            request('DELETE', `/v1/horarios/${id}`),
        
        asignarTutor: (horarioId, tutorId) => 
            request('POST', `/v1/horarios/${horarioId}/asignar-tutor`, { tutor_id: tutorId }),
        
        desasignarTutor: (horarioId, tutorId) => 
            request('POST', `/v1/horarios/${horarioId}/desasignar-tutor`, { tutor_id: tutorId }),
        
        getTutores: (horarioId) => 
            request('GET', `/v1/horarios/${horarioId}/tutores`),
        
        getHorariosDeTutor: (tutorId) => 
            request('GET', `/v1/tutores/${tutorId}/horarios`)
    };

    return {
        loading,
        error,
        auth,
        alumnos,
        tutores,
        propietarios,
        horarios
    };
}