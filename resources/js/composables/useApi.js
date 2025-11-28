// resources/js/composables/useApi.js - Actualizar

import axios from 'axios';
import { ref } from 'vue';

// Config base axios
axios.defaults.baseURL = '/inf513/grupo16sa/proyecto2/public/api';
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
            localStorage.removeItem('pagofacil_token');
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
        
        obtenerHorariosDeTutor: tutorId =>
            request('GET', `/v1/tutores/${tutorId}/horario`)
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

        cambiarEstado: (id, data) =>
            request('POST', `/v1/inscripcion/${id}/cambiar-estado`, data),

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
            request('GET', '/v1/informe-clase', null, { params }),

        getById: id =>
            request('GET', `/v1/informe-clase/${id}`),

        create: data =>
            request('POST', '/v1/informe-clase', data),

        porAsistencia: (asistenciaId) => 
            request('GET', `/v1/informe-clase/asistencia/${asistenciaId}`),

        update: (id, data) =>
            request('PUT', `/v1/informe-clase/${id}`, data),

        delete: id =>
            request('DELETE', `/v1/informe-clase/${id}`)
    };

    // ============================================
    // SERVICIOS
    // ============================================
    const servicios = {
        getAll: () =>
            request('GET', '/v1/servicios')
    };

    // ============================================
    // ASISTENCIAS
    // ============================================
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

    // ============================================
    // LICENCIAS
    // ============================================
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

    // ============================================
    // REPROGRAMACIONES
    // ============================================
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

    // ============================================
    // VENTAS
    // ============================================
    const ventas = {
        getAll: (params = {}) =>
            request('GET', '/v1/venta', null, { params }),

        misVentas: () =>
            request('GET', '/v1/venta/mis-ventas'),

        reporteMensual: (params = {}) =>
            request('GET', '/v1/venta/reporte-mensual', null, { params }),

        reportePorEstado: () =>
            request('GET', '/v1/venta/reporte-estado'),

        getById: id =>
            request('GET', `/v1/venta/${id}`),
    };

    // ============================================
    // PAGOS
    // ============================================
    const pagos = {
        getAll: (params = {}) =>
            request('GET', '/v1/pago', null, { params }),

        getById: id =>
            request('GET', `/v1/pago/${id}`),

        create: data =>
            request('POST', '/v1/pago', data),

        generarQR: data =>
            request('POST', '/v1/pago/generar-qr', data),

        consultarEstado: id =>
            request('GET', `/v1/pago/${id}/consultar-estado`),

        porVenta: ventaId =>
            request('GET', `/v1/pago/venta/${ventaId}`),

        metodosHabilitados: () =>
            request('GET', '/v1/pago/metodos-habilitados')
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
        reprogramaciones,
        ventas,
        pagos
    };
}