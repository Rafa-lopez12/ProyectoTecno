<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '../../Layout/AppLayout.vue';
import { useApi } from '../../composables/useApi';

const props = defineProps({
    licenciaId: [String, Number]
});

const { licencias: licenciasApi, reprogramaciones: reprogramacionesApi } = useApi();

const licencia = ref(null);
const reprogramaciones = ref([]);
const loading = ref(false);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingReprogramacion = ref(null);

// Formulario para crear/editar reprogramación
const formReprogramacion = ref({
    licencia_id: props.licenciaId,
    fecha_original: '',
    fecha_nueva: '',
    observaciones: ''
});

const cargarDatos = async () => {
    loading.value = true;

    try {
        // Cargar licencia
        const respLicencia = await licenciasApi.getById(props.licenciaId);
        if (respLicencia.success) {
            licencia.value = respLicencia.data.data;
            
            // Pre-cargar fecha original
            formReprogramacion.value.fecha_original = licencia.value.fecha_asistencia;
        }

        // Cargar reprogramaciones
        const respReprogramaciones = await licenciasApi.getReprogramaciones(props.licenciaId);
        if (respReprogramaciones.success) {
            reprogramaciones.value = respReprogramaciones.data.data;
        }
    } catch (error) {
        console.error("Error al cargar datos:", error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    cargarDatos();
});

// Verificar si puede crear reprogramación
const puedeCrearReprogramacion = computed(() => {
    return licencia.value?.estado === 'aprobada';
});

// Obtener clase CSS para estado de licencia
const getLicenciaEstadoClass = (estado) => {
    const classes = {
        'pendiente': 'bg-yellow-100 text-yellow-800 border-yellow-300',
        'aprobada': 'bg-green-100 text-green-800 border-green-300',
        'rechazada': 'bg-red-100 text-red-800 border-red-300'
    };
    return classes[estado] || 'bg-gray-100 text-gray-800 border-gray-300';
};

// Obtener clase CSS para estado de reprogramación
const getReprogramacionEstadoClass = (estado) => {
    const classes = {
        'programada': 'bg-blue-100 text-blue-800 border-blue-300',
        'realizada': 'bg-green-100 text-green-800 border-green-300',
        'cancelada': 'bg-red-100 text-red-800 border-red-300'
    };
    return classes[estado] || 'bg-gray-100 text-gray-800 border-gray-300';
};

// Obtener texto del estado de licencia
const getLicenciaEstadoTexto = (estado) => {
    const textos = {
        'pendiente': 'Pendiente',
        'aprobada': 'Aprobada',
        'rechazada': 'Rechazada'
    };
    return textos[estado] || estado;
};

// Obtener texto del estado de reprogramación
const getReprogramacionEstadoTexto = (estado) => {
    const textos = {
        'programada': 'Programada',
        'realizada': 'Realizada',
        'cancelada': 'Cancelada'
    };
    return textos[estado] || estado;
};

// Formatear fecha
const formatearFecha = (fecha) => {
    return new Date(fecha).toLocaleDateString('es-ES', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const formatearFechaCorta = (fecha) => {
    return new Date(fecha).toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
};

const formatearFechaHora = (fecha) => {
    return new Date(fecha).toLocaleString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Abrir modal para crear reprogramación
const abrirModalCrear = () => {
    formReprogramacion.value = {
        licencia_id: props.licenciaId,
        fecha_original: licencia.value.fecha_asistencia,
        fecha_nueva: '',
        observaciones: ''
    };
    editingReprogramacion.value = null;
    showCreateModal.value = true;
};

// Abrir modal para editar reprogramación
const abrirModalEditar = (reprogramacion) => {
    formReprogramacion.value = {
        licencia_id: props.licenciaId,
        fecha_original: reprogramacion.fecha_original,
        fecha_nueva: reprogramacion.fecha_nueva,
        observaciones: reprogramacion.observaciones || ''
    };
    editingReprogramacion.value = reprogramacion;
    showEditModal.value = true;
};

// Crear reprogramación
const crearReprogramacion = async () => {
    loading.value = true;
    try {
        const response = await reprogramacionesApi.create(formReprogramacion.value);
        
        if (response.success) {
            showCreateModal.value = false;
            await cargarDatos();
        } else {
            alert(response.error || 'Error al crear reprogramación');
        }
    } catch (error) {
        console.error("Error:", error);
        alert('Error al crear reprogramación');
    } finally {
        loading.value = false;
    }
};

// Actualizar reprogramación
const actualizarReprogramacion = async () => {
    loading.value = true;
    try {
        const response = await reprogramacionesApi.update(
            editingReprogramacion.value.id, 
            formReprogramacion.value
        );
        
        if (response.success) {
            showEditModal.value = false;
            editingReprogramacion.value = null;
            await cargarDatos();
        } else {
            alert(response.error || 'Error al actualizar reprogramación');
        }
    } catch (error) {
        console.error("Error:", error);
        alert('Error al actualizar reprogramación');
    } finally {
        loading.value = false;
    }
};

// Marcar como realizada
const marcarRealizada = async (reprogramacionId) => {
    if (!confirm('¿Estás seguro de marcar esta reprogramación como realizada?')) return;
    
    loading.value = true;
    try {
        const response = await reprogramacionesApi.marcarRealizada(reprogramacionId);
        
        if (response.success) {
            await cargarDatos();
        } else {
            alert(response.error || 'Error al marcar como realizada');
        }
    } catch (error) {
        console.error("Error:", error);
        alert('Error al marcar como realizada');
    } finally {
        loading.value = false;
    }
};

// Cancelar reprogramación
const cancelarReprogramacion = async (reprogramacionId) => {
    if (!confirm('¿Estás seguro de cancelar esta reprogramación?')) return;
    
    loading.value = true;
    try {
        const response = await reprogramacionesApi.cancelar(reprogramacionId);
        
        if (response.success) {
            await cargarDatos();
        } else {
            alert(response.error || 'Error al cancelar reprogramación');
        }
    } catch (error) {
        console.error("Error:", error);
        alert('Error al cancelar reprogramación');
    } finally {
        loading.value = false;
    }
};

// Eliminar reprogramación
const eliminarReprogramacion = async (reprogramacionId) => {
    if (!confirm('¿Estás seguro de eliminar esta reprogramación?')) return;
    
    loading.value = true;
    try {
        const response = await reprogramacionesApi.delete(reprogramacionId);
        
        if (response.success) {
            await cargarDatos();
        } else {
            alert(response.error || 'Error al eliminar reprogramación');
        }
    } catch (error) {
        console.error("Error:", error);
        alert('Error al eliminar reprogramación');
    } finally {
        loading.value = false;
    }
};

// Volver al detalle de asistencia
const volver = () => {
    if (licencia.value?.asistencia_id) {
        router.visit(`asistencias/${licencia.value.asistencia_id}`);
    } else {
        router.visit('asistencias');
    }
};

// Obtener fecha mínima para reprogramación (mañana)
const fechaMinima = computed(() => {
    const mañana = new Date();
    mañana.setDate(mañana.getDate() + 1);
    return mañana.toISOString().split('T')[0];
});
</script>

<template>
    <AppLayout>
        <Head title="Reprogramación de Clase" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center space-x-4">
                <button
                    @click="volver"
                    class="text-gray-400 hover:text-gray-600 transition"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Reprogramación de Clase</h2>
                    <p class="text-sm text-gray-600 mt-1">Gestiona las reprogramaciones para esta licencia</p>
                </div>
            </div>

            <div v-if="loading && !licencia" class="bg-white rounded-lg shadow-sm p-8 text-center">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                <p class="mt-2 text-gray-600">Cargando información...</p>
            </div>

            <template v-else-if="licencia">
                <!-- Card de información de la licencia -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-50 to-purple-100 px-6 py-4 border-b border-purple-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <h3 class="text-lg font-semibold text-gray-900">Información de la Licencia</h3>
                            </div>
                            <span :class="getLicenciaEstadoClass(licencia.estado)" class="px-3 py-1 text-sm font-semibold rounded-full border-2">
                                {{ getLicenciaEstadoTexto(licencia.estado) }}
                            </span>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <h5 class="text-sm font-medium text-gray-500 mb-1">Alumno</h5>
                                <p class="text-gray-900 font-medium">{{ licencia.alumno_nombre }}</p>
                            </div>
                            <div>
                                <h5 class="text-sm font-medium text-gray-500 mb-1">Tutor</h5>
                                <p class="text-gray-900 font-medium">{{ licencia.tutor_nombre }}</p>
                            </div>
                            <div>
                                <h5 class="text-sm font-medium text-gray-500 mb-1">Fecha Original</h5>
                                <p class="text-gray-900 font-medium">{{ formatearFechaCorta(licencia.fecha_asistencia) }}</p>
                            </div>
                        </div>

                        <div class="mt-4">
                            <h5 class="text-sm font-medium text-gray-500 mb-2">Motivo</h5>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-gray-900">{{ licencia.motivo }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reprogramaciones -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="px-6 py-4 bg-gradient-to-r from-indigo-50 to-indigo-100 border-b border-indigo-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <h3 class="text-lg font-semibold text-gray-900">Reprogramaciones</h3>
                                <span class="px-2 py-1 bg-indigo-600 text-white text-xs font-semibold rounded-full">
                                    {{ reprogramaciones.length }}
                                </span>
                            </div>
                            <button
                                v-if="puedeCrearReprogramacion"
                                @click="abrirModalCrear"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium"
                            >
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                Nueva Reprogramación
                            </button>
                        </div>
                    </div>

                    <div class="p-6">
                        <!-- No puede crear reprogramación -->
                        <div v-if="!puedeCrearReprogramacion" class="text-center py-8">
                            <div class="inline-flex items-center justify-center w-16 h-16 bg-yellow-100 rounded-full mb-4">
                                <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <h4 class="text-lg font-semibold text-gray-900 mb-2">Licencia no aprobada</h4>
                            <p class="text-gray-600">La licencia debe estar aprobada para poder crear reprogramaciones.</p>
                        </div>

                        <!-- Sin reprogramaciones -->
                        <div v-else-if="reprogramaciones.length === 0" class="text-center py-8">
                            <div class="inline-flex items-center justify-center w-16 h-16 bg-indigo-100 rounded-full mb-4">
                                <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <h4 class="text-lg font-semibold text-gray-900 mb-2">No hay reprogramaciones</h4>
                            <p class="text-gray-600 mb-4">Crea una reprogramación para esta clase.</p>
                            <button
                                @click="abrirModalCrear"
                                class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium"
                            >
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                Crear Primera Reprogramación
                            </button>
                        </div>

                        <!-- Lista de reprogramaciones -->
                        <div v-else class="space-y-4">
                            <div
                                v-for="reprogramacion in reprogramaciones"
                                :key="reprogramacion.id"
                                class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <!-- Header de la reprogramación -->
                                        <div class="flex items-center space-x-3 mb-4">
                                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                            </svg>
                                            <span :class="getReprogramacionEstadoClass(reprogramacion.estado)" class="px-3 py-1 text-xs font-semibold rounded-full border-2">
                                                {{ getReprogramacionEstadoTexto(reprogramacion.estado) }}
                                            </span>
                                        </div>

                                        <!-- Fechas -->
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                                <div class="flex items-center space-x-2 mb-2">
                                                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                    <span class="text-xs font-medium text-red-600 uppercase">Fecha Original</span>
                                                </div>
                                                <p class="text-red-900 font-semibold">{{ formatearFecha(reprogramacion.fecha_original) }}</p>
                                            </div>

                                            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                                <div class="flex items-center space-x-2 mb-2">
                                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <span class="text-xs font-medium text-green-600 uppercase">Nueva Fecha</span>
                                                </div>
                                                <p class="text-green-900 font-semibold">{{ formatearFecha(reprogramacion.fecha_nueva) }}</p>
                                            </div>
                                        </div>

                                        <!-- Observaciones -->
                                        <div v-if="reprogramacion.observaciones" class="bg-gray-50 rounded-lg p-4 mb-4">
                                            <p class="text-sm text-gray-500 font-medium mb-1">Observaciones:</p>
                                            <p class="text-gray-900">{{ reprogramacion.observaciones }}</p>
                                        </div>

                                        <!-- Fechas de registro -->
                                        <div class="text-xs text-gray-500">
                                            Creada: {{ formatearFechaHora(reprogramacion.created_at) }}
                                        </div>
                                    </div>

                                    <!-- Acciones -->
                                    <div class="ml-4 flex flex-col space-y-2">
                                        <button
                                            v-if="reprogramacion.estado === 'programada'"
                                            @click="marcarRealizada(reprogramacion.id)"
                                            class="px-3 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition"
                                            :disabled="loading"
                                            title="Marcar como realizada"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>

                                        <button
                                            v-if="reprogramacion.estado === 'programada'"
                                            @click="abrirModalEditar(reprogramacion)"
                                            class="px-3 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition"
                                            :disabled="loading"
                                            title="Editar"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Modal para crear reprogramación -->
        <Teleport to="body">
            <div v-if="showCreateModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Crear Reprogramación</h3>
                        <button @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="crearReprogramacion" class="space-y-4">
                        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        Selecciona una nueva fecha para reprogramar la clase del 
                                        <strong>{{ formatearFechaCorta(formReprogramacion.fecha_original) }}</strong>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Fecha Original
                            </label>
                            <input
                                v-model="formReprogramacion.fecha_original"
                                type="date"
                                disabled
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 cursor-not-allowed"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Nueva Fecha <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="formReprogramacion.fecha_nueva"
                                type="date"
                                required
                                :min="fechaMinima"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            />
                            <p class="mt-1 text-xs text-gray-500">
                                La fecha debe ser posterior a hoy
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Observaciones
                            </label>
                            <textarea
                                v-model="formReprogramacion.observaciones"
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                placeholder="Notas adicionales sobre la reprogramación..."
                            ></textarea>
                        </div>

                        <div class="flex justify-end space-x-3 pt-4">
                            <button
                                type="button"
                                @click="showCreateModal = false"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
                                :disabled="loading"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700"
                                :disabled="loading"
                            >
                                {{ loading ? 'Creando...' : 'Crear Reprogramación' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Modal para editar reprogramación -->
        <Teleport to="body">
            <div v-if="showEditModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Editar Reprogramación</h3>
                        <button @click="showEditModal = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="actualizarReprogramacion" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Fecha Original
                            </label>
                            <input
                                v-model="formReprogramacion.fecha_original"
                                type="date"
                                disabled
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 cursor-not-allowed"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Nueva Fecha <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="formReprogramacion.fecha_nueva"
                                type="date"
                                required
                                :min="fechaMinima"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Observaciones
                            </label>
                            <textarea
                                v-model="formReprogramacion.observaciones"
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                placeholder="Notas adicionales sobre la reprogramación..."
                            ></textarea>
                        </div>

                        <div class="flex justify-end space-x-3 pt-4">
                            <button
                                type="button"
                                @click="showEditModal = false"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
                                :disabled="loading"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700"
                                :disabled="loading"
                            >
                                {{ loading ? 'Guardando...' : 'Guardar Cambios' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
