import axios from 'axios';
import { ref } from 'vue';

// Config base axios
axios.defaults.baseURL = '/api';
axios.defaults.headers.common['Content-Type'] = 'application/json';
axios.defaults.headers.common['Accept'] = 'application/json';

// Token
const token = localStorage.getItem('token');
if (token) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

// Interceptor 401
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

    const request = async (method, url, data = null, config = {}) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios({ method, url, data, ...config });
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

        logoutAll: () =>
            request('POST', '/auth/logout-all'),

        me: () =>
            request('GET', '/auth/me'),

        refresh: () =>
            request('POST', '/auth/refresh'),
    };

    // ============================================
    // ALUMNOS
    // ============================================
    const alumnos = {
        getAll: (params = {}) =>
            request('GET', '/v1/alumnos', null, { params }),

        getById: id =>
            request('GET', `/v1/alumnos/${id}`),

        create: data =>
            request('POST', '/v1/alumnos', data),

        update: (id, data) =>
            request('PUT', `/v1/alumnos/${id}`, data),

        delete: id =>
            request('DELETE', `/v1/alumnos/${id}`)
    };

    // ============================================
    // TUTORES
    // ============================================
    const tutores = {
        getAll: (params = {}) =>
            request('GET', '/v1/tutores', null, { params }),

        getById: id =>
            request('GET', `/v1/tutores/${id}`),

        create: data =>
            request('POST', '/v1/tutores', data),

        update: (id, data) =>
            request('PUT', `/v1/tutores/${id}`, data),

        delete: id =>
            request('DELETE', `/v1/tutores/${id}`),

        getHorarios: tutorId =>
            request('GET', `/v1/tutores/${tutorId}/horario`)
    };

    // ============================================
    // PROPIETARIOS
    // ============================================
    const propietarios = {
        getAll: (params = {}) =>
            request('GET', '/v1/propietarios', null, { params }),

        getById: id =>
            request('GET', `/v1/propietarios/${id}`),

        create: data =>
            request('POST', '/v1/propietarios', data),

        update: (id, data) =>
            request('PUT', `/v1/propietarios/${id}`, data),

        delete: id =>
            request('DELETE', `/v1/propietarios/${id}`)
    };

    // ============================================
    // HORARIOS
    // ============================================
    const horarios = {
        getAll: (params = {}) =>
            request('GET', '/v1/horario', null, { params }),

        getById: id =>
            request('GET', `/v1/horario/${id}`),

        create: data =>
            request('POST', '/v1/horario', data),

        update: (id, data) =>
            request('PUT', `/v1/horario/${id}`, data),

        delete: id =>
            request('DELETE', `/v1/horario/${id}`),

        asignarTutor: (horarioId, tutorId) =>
            request('POST', `/v1/horario/${horarioId}/asignar-tutor`, {
                tutor_id: tutorId
            }),

        desasignarTutor: (horarioId, tutorId) =>
            request('POST', `/v1/horario/${horarioId}/desasignar-tutor`, {
                tutor_id: tutorId
            }),

        getTutores: horarioId =>
            request('GET', `/v1/horario/${horarioId}/tutores`),
    };

    // ============================================
    // INSCRIPCIONES
    // ============================================
    const inscripciones = {
        getAll: (params = {}) =>
            request('GET', '/v1/inscripcion', null, { params }),

        getById: id =>
            request('GET', `/v1/inscripcion/${id}`),

        create: data =>
            request('POST', '/v1/inscripcion', data),

        update: (id, data) =>
            request('PUT', `/v1/inscripcion/${id}`, data),

        delete: id =>
            request('DELETE', `/v1/inscripcion/${id}`),

        getInformes: id =>
            request('GET', `/v1/inscripcion/${id}/informes`)
    };

    // ============================================
    // INFORMES DE CLASE
    // ============================================
    const informesClase = {
        getAll: (params = {}) =>
            request('GET', '/v1/informes-clase', null, { params }),

        getById: id =>
            request('GET', `/v1/informes-clase/${id}`),

        create: data =>
            request('POST', '/v1/informes-clase', data),

        update: (id, data) =>
            request('PUT', `/v1/informes-clase/${id}`, data),

        delete: id =>
            request('DELETE', `/v1/informes-clase/${id}`)
    };

    // ============================================
    // SERVICIOS
    // ============================================
    const servicios = {
        getAll: () =>
            request('GET', '/v1/servicios')
    };

    const asistencias = {
        getAll: (params = {}) =>
            request('GET', '/v1/asistencia', null, { params }),
        getById: id =>
            request('GET', `/v1/asistencia/${id}`),
        create: data =>
            request('POST', '/v1/asistencia', data),
        update: (id, data) =>
            request('PUT', `/v1/asistencia/${id}`, data),
        delete: id =>
            request('DELETE', `/v1/asistencia/${id}`),
        porInscripcion: inscripcionId =>
            request('GET', `/v1/asistencia/inscripcion/${inscripcionId}`)
    };

    const licencias = {
        getAll: (params = {}) =>
            request('GET', '/v1/licencia', null, { params }),
        getById: id =>
            request('GET', `/v1/licencia/${id}`),
        create: data =>
            request('POST', '/v1/licencia', data),
        update: (id, data) =>
            request('PUT', `/v1/licencia/${id}`, data),
        delete: id =>
            request('DELETE', `/v1/licencia/${id}`),
        aprobar: id =>
            request('POST', `/v1/licencia/${id}/aprobar`),
        rechazar: id =>
            request('POST', `/v1/licencia/${id}/rechazar`),
        getReprogramaciones: id =>
            request('GET', `/v1/licencia/${id}/reprogramacione`)
    };

    const reprogramaciones = {
        getAll: (params = {}) =>
            request('GET', '/v1/reprogramacione', null, { params }),
        getById: id =>
            request('GET', `/v1/reprogramacione/${id}`),
        create: data =>
            request('POST', '/v1/reprogramacione', data),
        update: (id, data) =>
            request('PUT', `/v1/reprogramacione/${id}`, data),
        delete: id =>
            request('DELETE', `/v1/reprogramacione/${id}`),
        marcarRealizada: id =>
            request('POST', `/v1/reprogramacione/${id}/marcar-realizada`),
        cancelar: id =>
            request('POST', `/v1/reprogramacione/${id}/cancelar`)
    };

    return {
        loading,
        error,
        auth,
        alumnos,
        tutores,
        propietarios,
        horarios,
        inscripciones,
        informesClase,
        servicios,
        asistencias,
        licencias,
        reprogramaciones
    };
}
