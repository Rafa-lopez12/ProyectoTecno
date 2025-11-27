<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '../../Layout/AppLayout.vue';
import { useApi } from '../../composables/useApi';
import { useAuth } from '../../composables/useAuth';

const { user, logout, checkAuth } = useAuth();
const props = defineProps({
    asistenciaId: [String, Number]
});

const { asistencias: asistenciasApi, licencias: licenciasApi, informesClase: informesClaseApi } = useApi();

const informe = ref(null);
const asistencia = ref(null);
const licencia = ref(null);
const loading = ref(false);
const showLicenciaModal = ref(false);
const showEditModal = ref(false);

// Formulario para crear licencia
const formLicencia = ref({
    asistencia_id: props.asistenciaId,
    motivo: '',
    estado: 'pendiente'
});

// Formulario para editar asistencia
const formEditAsistencia = ref({
    fecha: '',
    estado: '',
    observaciones: ''
});

const cargarDatos = async () => {
    loading.value = true;

    try {
        // Cargar asistencia
        const respAsistencia = await asistenciasApi.getById(props.asistenciaId);
        if (respAsistencia.success) {
            asistencia.value = respAsistencia.data.data;
            
            // Si tiene licencia, cargarla
            if (asistencia.value.licencia_id) {
                const respLicencia = await licenciasApi.getById(asistencia.value.licencia_id);
                if (respLicencia.success) {
                    licencia.value = respLicencia.data.data;
                }
            }
            
            // Cargar informe si existe
            const respInforme = await informesClaseApi.porAsistencia(props.asistenciaId);
            if (respInforme.success && respInforme.data.data) {
                informe.value = respInforme.data.data;
            }
        }
    } catch (error) {
        console.error("Error al cargar datos:", error);
    } finally {
        loading.value = false;
    }
};

const crearInforme = () => {
    router.visit(`/asistencias/${props.asistenciaId}/informe/create`);
};

const verInforme = () => {
    router.visit(`/asistencias/${props.asistenciaId}/informe`);
};

onMounted(() => {
    cargarDatos();
});

// Verificar si puede crear licencia
const puedeCrearLicencia = computed(() => {
    return asistencia.value?.estado === 'ausente' && !asistencia.value?.licencia_id;
});

// Obtener clase CSS para estado
const getEstadoClass = (estado) => {
    const classes = {
        'presente': 'bg-green-100 text-green-800 border-green-300',
        'ausente': 'bg-red-100 text-red-800 border-red-300',
        'tardanza': 'bg-yellow-100 text-yellow-800 border-yellow-300',
        'justificado': 'bg-blue-100 text-blue-800 border-blue-300',
        'recuperada': 'bg-purple-100 text-purple-800 border-purple-300'
    };
    return classes[estado] || 'bg-gray-100 text-gray-800 border-gray-300';
};

// Obtener clase CSS para estado de licencia
const getLicenciaEstadoClass = (estado) => {
    const classes = {
        'pendiente': 'bg-yellow-100 text-yellow-800 border-yellow-300',
        'aprobada': 'bg-green-100 text-green-800 border-green-300',
        'rechazada': 'bg-red-100 text-red-800 border-red-300'
    };
    return classes[estado] || 'bg-gray-100 text-gray-800 border-gray-300';
};

// Obtener texto del estado
const getEstadoTexto = (estado) => {
    const textos = {
        'presente': 'Presente',
        'ausente': 'Ausente',
        'tardanza': 'Tardanza',
        'justificado': 'Justificado',
        'recuperada': 'Recuperada'
    };
    return textos[estado] || estado;
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

// Formatear fecha
const formatearFecha = (fecha) => {
    return new Date(fecha).toLocaleDateString('es-ES', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
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

// Abrir modal para crear licencia
const abrirModalLicencia = () => {
    formLicencia.value = {
        asistencia_id: props.asistenciaId,
        motivo: '',
        estado: 'pendiente'
    };
    showLicenciaModal.value = true;
};

// Crear licencia
const crearLicencia = async () => {
    loading.value = true;
    try {
        const response = await licenciasApi.create(formLicencia.value);
        
        if (response.success) {
            showLicenciaModal.value = false;
            await cargarDatos();
        } else {
            alert(response.error || 'Error al crear licencia');
        }
    } catch (error) {
        console.error("Error:", error);
        alert('Error al crear licencia');
    } finally {
        loading.value = false;
    }
};

// Abrir modal para editar asistencia
const abrirModalEditar = () => {
    formEditAsistencia.value = {
        fecha: asistencia.value.fecha,
        estado: asistencia.value.estado,
        observaciones: asistencia.value.observaciones || ''
    };
    showEditModal.value = true;
};

// Actualizar asistencia
const actualizarAsistencia = async () => {
    loading.value = true;
    try {
        const response = await asistenciasApi.update(props.asistenciaId, formEditAsistencia.value);
        
        if (response.success) {
            showEditModal.value = false;
            await cargarDatos();
        } else {
            alert(response.error || 'Error al actualizar asistencia');
        }
    } catch (error) {
        console.error("Error:", error);
        alert('Error al actualizar asistencia');
    } finally {
        loading.value = false;
    }
};

// Aprobar licencia
const aprobarLicencia = async () => {
    if (!confirm('¿Estás seguro de aprobar esta licencia?')) return;
    
    loading.value = true;
    try {
        const response = await licenciasApi.aprobar(licencia.value.id);
        
        if (response.success) {
            await cargarDatos();
        } else {
            alert(response.error || 'Error al aprobar licencia');
        }
    } catch (error) {
        console.error("Error:", error);
        alert('Error al aprobar licencia');
    } finally {
        loading.value = false;
    }
};

// Rechazar licencia
const rechazarLicencia = async () => {
    if (!confirm('¿Estás seguro de rechazar esta licencia?')) return;
    
    loading.value = true;
    try {
        const response = await licenciasApi.rechazar(licencia.value.id);
        
        if (response.success) {
            await cargarDatos();
        } else {
            alert(response.error || 'Error al rechazar licencia');
        }
    } catch (error) {
        console.error("Error:", error);
        alert('Error al rechazar licencia');
    } finally {
        loading.value = false;
    }
};

// Ver reprogramación
const verReprogramacion = () => {
    router.visit(`/asistencias/licencia/${licencia.value.id}/reprogramacion`);
};

// Volver
const volver = () => {
    if (asistencia.value?.inscripcion_id) {
        router.visit(`/asistencias/inscripcion/${asistencia.value.inscripcion_id}`);
    } else {
        router.visit('/asistencias');
    }
};
</script>

<template>
    <AppLayout>
        <Head title="Detalle de Asistencia" />

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
                <h2 class="text-2xl font-bold text-gray-900">Detalle de Asistencia</h2>
            </div>

            <div v-if="loading && !asistencia" class="bg-white rounded-lg shadow-sm p-8 text-center">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                <p class="mt-2 text-gray-600">Cargando información...</p>
            </div>

            <template v-else-if="asistencia">
                <!-- Card de Asistencia -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <!-- Header con estado -->
                    <div :class="getEstadoClass(asistencia.estado)" class="px-6 py-4 border-b-2">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="bg-white p-3 rounded-full">
                                    <svg class="w-8 h-8" :class="asistencia.estado === 'presente' ? 'text-green-600' : asistencia.estado === 'ausente' ? 'text-red-600' : asistencia.estado === 'tardanza' ? 'text-yellow-600' : 'text-blue-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path v-if="asistencia.estado === 'presente'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        <path v-else-if="asistencia.estado === 'ausente'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        <path v-else-if="asistencia.estado === 'tardanza'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-2xl font-bold">{{ getEstadoTexto(asistencia.estado) }}</h3>
                                    <p class="text-sm opacity-90">{{ formatearFecha(asistencia.fecha) }}</p>
                                </div>
                            </div>
                            <button
                                @click="abrirModalEditar"
                                class="px-4 py-2 bg-white text-gray-700 rounded-lg hover:bg-gray-50 transition font-medium"
                            >
                                Editar
                            </button>
                        </div>
                    </div>

                    <!-- Información del alumno y tutor -->
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <h4 class="font-semibold text-gray-900 text-lg">Información del Alumno</h4>
                            <div class="space-y-3">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-gray-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <div>
                                        <p class="text-sm text-gray-500">Alumno</p>
                                        <p class="font-medium text-gray-900">{{ asistencia.alumno_nombre }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <h4 class="font-semibold text-gray-900 text-lg">Información del Tutor</h4>
                            <div class="space-y-3">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-gray-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <div>
                                        <p class="text-sm text-gray-500">Tutor</p>
                                        <p class="font-medium text-gray-900">{{ asistencia.tutor_nombre }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Observaciones -->
                    <div v-if="asistencia.observaciones" class="px-6 pb-6">
                        <h4 class="font-semibold text-gray-900 mb-2">Observaciones</h4>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-gray-700">{{ asistencia.observaciones }}</p>
                        </div>
                    </div>
                </div>

                <!-- Sección de Licencia -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="px-6 py-4 bg-gradient-to-r from-purple-50 to-purple-100 border-b border-purple-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <h3 class="text-lg font-semibold text-gray-900">Gestión de Licencia</h3>
                            </div>
                            <button
                                v-if="puedeCrearLicencia"
                                @click="abrirModalLicencia"
                                class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition font-medium"
                            >
                                Crear Licencia
                            </button>
                        </div>
                    </div>

                    <div class="p-6">
                        <!-- No hay licencia y puede crearla -->
                        <div v-if="puedeCrearLicencia" class="text-center py-8">
                            <div class="inline-flex items-center justify-center w-16 h-16 bg-purple-100 rounded-full mb-4">
                                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h4 class="text-lg font-semibold text-gray-900 mb-2">Esta ausencia no tiene licencia</h4>
                            <p class="text-gray-600 mb-4">Puedes crear una licencia para justificar esta ausencia y programar una reposición.</p>
                            <button
                                @click="abrirModalLicencia"
                                class="inline-flex items-center px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition font-medium"
                            >
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                Crear Licencia
                            </button>
                        </div>

                        <!-- No puede crear licencia -->
                        <div v-else-if="!asistencia.licencia_id" class="text-center py-8">
                            <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h4 class="text-lg font-semibold text-gray-900 mb-2">No requiere licencia</h4>
                            <p class="text-gray-600">Las licencias solo aplican para asistencias marcadas como "Ausente".</p>
                        </div>

                        <!-- Tiene licencia -->
                        <div v-else-if="licencia" class="space-y-6">
                            <!-- Estado de la licencia -->
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <span class="text-sm font-medium text-gray-700">Estado de la licencia:</span>
                                    <span :class="getLicenciaEstadoClass(licencia.estado)" class="px-3 py-1 text-sm font-semibold rounded-full border-2">
                                        {{ getLicenciaEstadoTexto(licencia.estado) }}
                                    </span>
                                </div>
                                <div v-if="licencia.estado === 'pendiente' && user?.rol !== 'tutor'" class="flex space-x-2">
                                    <button
                                        @click="aprobarLicencia"
                                        class="px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition"
                                        :disabled="loading"
                                    >
                                        Aprobar
                                    </button>
                                    <button
                                        @click="rechazarLicencia"
                                        class="px-4 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 transition"
                                        :disabled="loading"
                                    >
                                        Rechazar
                                    </button>
                                </div>
                            </div>

                            <!-- Información de la licencia -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h5 class="text-sm font-medium text-gray-500 mb-1">Fecha de Solicitud</h5>
                                    <p class="text-gray-900">{{ formatearFechaHora(licencia.fecha_solicitud) }}</p>
                                </div>
                                <div>
                                    <h5 class="text-sm font-medium text-gray-500 mb-1">Fecha de Asistencia</h5>
                                    <p class="text-gray-900">{{ formatearFecha(licencia.fecha_asistencia) }}</p>
                                </div>
                            </div>

                            <!-- Motivo -->
                            <div>
                                <h5 class="text-sm font-medium text-gray-500 mb-2">Motivo de la Licencia</h5>
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <p class="text-gray-900">{{ licencia.motivo }}</p>
                                </div>
                            </div>

                            <!-- Botón para ver reprogramación -->
                            <div v-if="licencia.estado === 'aprobada'" class="border-t pt-6">
                                <button
                                    @click="verReprogramacion"
                                    class="w-full flex items-center justify-center px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-medium"
                                >
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    Gestionar Reprogramación
                                </button>
                            </div>

                            <!-- Info si está pendiente -->
                            <div v-else-if="licencia.estado === 'pendiente'" class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-yellow-700">
                                            La licencia está pendiente de aprobación. Una vez aprobada, podrás crear la reprogramación de esta clase.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Info si está rechazada -->
                            <div v-else-if="licencia.estado === 'rechazada'" class="bg-red-50 border-l-4 border-red-400 p-4 rounded">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-red-700">
                                            La licencia ha sido rechazada. No se puede crear una reprogramación para esta asistencia.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6">
                    <button
                        @click="router.visit(`/asistencias/${asistenciaId}/informe`)"
                        class="w-full flex items-center justify-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Ver/Gestionar Informe de Clase
                    </button>
                </div>
 
            </template>
        </div>

        <!-- Modal para crear licencia -->
        <Teleport to="body">
            <div v-if="showLicenciaModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Crear Licencia</h3>
                        <button @click="showLicenciaModal = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="crearLicencia" class="space-y-4">
                        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        Una vez aprobada la licencia, podrás programar la reposición de esta clase.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Motivo de la Licencia <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                v-model="formLicencia.motivo"
                                rows="4"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                placeholder="Explica el motivo de la ausencia..."
                            ></textarea>
                            <p class="mt-1 text-xs text-gray-500">
                                Describe detalladamente el motivo de la ausencia
                            </p>
                        </div>

                        <div class="flex justify-end space-x-3 pt-4">
                            <button
                                type="button"
                                @click="showLicenciaModal = false"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
                                :disabled="loading"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700"
                                :disabled="loading"
                            >
                                {{ loading ? 'Creando...' : 'Crear Licencia' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Modal para editar asistencia -->
        <Teleport to="body">
            <div v-if="showEditModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Editar Asistencia</h3>
                        <button @click="showEditModal = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="actualizarAsistencia" class="space-y-4">
                        <div v-if="asistencia?.licencia_id" class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">
                                        Esta asistencia tiene una licencia asociada. No puedes cambiar el estado.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Fecha</label>
                            <input
                                v-model="formEditAsistencia.fecha"
                                type="date"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                            <select
                                v-model="formEditAsistencia.estado"
                                required
                                :disabled="asistencia?.licencia_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
                            >
                                <option value="presente">Presente</option>
                                <option value="ausente">Ausente</option>
                                <option value="tardanza">Tardanza</option>
                                <option value="justificado">Justificado</option>
                                <option value="recuperada">Recuperada</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Observaciones</label>
                            <textarea
                                v-model="formEditAsistencia.observaciones"
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                placeholder="Opcional..."
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