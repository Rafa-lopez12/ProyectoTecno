<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '../../Layout/AppLayout.vue';
import { useApi } from '../../composables/useApi';
import InformeFormDialog from './components/InformeFormDialog.vue';

const props = defineProps({
    asistenciaId: {
        type: [String, Number],
        required: true
    }
});

const { informesClase, asistencias } = useApi();
const asistencia = ref(null);
const informe = ref(null);
const loading = ref(false);

// Modal
const showFormDialog = ref(false);
const informeToEdit = ref(null);

const cargarAsistencia = async () => {
    const response = await asistencias.getById(props.asistenciaId);
    if (response.success) {
        asistencia.value = response.data.data;
    }
};

// CAMBIADO: Solo se llama después de crear/editar
const cargarInforme = async () => {
    loading.value = true;
    try {
        const response = await informesClase.porAsistencia(props.asistenciaId);
        if (response.success && response.data.data) {
            informe.value = response.data.data;
        }
    } catch (error) {
        console.error("Error:", error);
    } finally {
        loading.value = false;
    }
};

// CAMBIADO: Solo carga asistencia, NO informe
onMounted(async () => {
    await cargarAsistencia();
});

const crearInforme = () => {
    informeToEdit.value = null;
    showFormDialog.value = true;
};

const editarInforme = () => {
    informeToEdit.value = informe.value;
    showFormDialog.value = true;
};

// CAMBIADO: Ahora SÍ carga el informe después de guardarlo
const handleInformeSaved = async () => {
    showFormDialog.value = false;
    informeToEdit.value = null;
    await cargarInforme(); // Cargar el informe que se acaba de crear/editar
};

const volverAsistencias = () => {
    if (asistencia.value?.inscripcion_id) {
        router.visit(`asistencias/inscripcion/${asistencia.value.inscripcion_id}`);
    } else {
        router.visit('asistencias');
    }
};

// NUEVO: Computed para saber si puede crear informe
const puedeCrearInforme = computed(() => {
    return !informe.value && asistencia.value?.estado === 'presente';
});

// Funciones de formateo y clases CSS (igual que antes)
const formatearFecha = (fecha) => {
    return new Date(fecha).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const getEstadoAsistenciaClass = (estado) => {
    const classes = {
        'presente': 'bg-green-100 text-green-800',
        'ausente': 'bg-red-100 text-red-800',
        'tardanza': 'bg-yellow-100 text-yellow-800',
        'justificado': 'bg-blue-100 text-blue-800',
        'recuperada': 'bg-purple-100 text-purple-800'
    };
    return classes[estado] || 'bg-gray-100 text-gray-800';
};

const getEstadoAsistenciaTexto = (estado) => {
    const textos = {
        'presente': 'Presente',
        'ausente': 'Ausente',
        'tardanza': 'Tardanza',
        'justificado': 'Justificado',
        'recuperada': 'Recuperada'
    };
    return textos[estado] || estado;
};

const getEstadoClass = (estado) => {
    const classes = {
        'realizada': 'bg-green-100 text-green-800',
        'cancelada': 'bg-red-100 text-red-800',
        'reprogramada': 'bg-yellow-100 text-yellow-800'
    };
    return classes[estado] || 'bg-gray-100 text-gray-800';
};

const getEstadoTexto = (estado) => {
    const textos = {
        'realizada': 'Realizada',
        'cancelada': 'Cancelada',
        'reprogramada': 'Reprogramada'
    };
    return textos[estado] || estado;
};

const getNivelComprensionClass = (nivel) => {
    const classes = {
        'excelente': 'bg-green-100 text-green-800',
        'bueno': 'bg-blue-100 text-blue-800',
        'regular': 'bg-yellow-100 text-yellow-800',
        'necesita_refuerzo': 'bg-red-100 text-red-800'
    };
    return classes[nivel] || 'bg-gray-100 text-gray-800';
};

const getNivelComprensionTexto = (nivel) => {
    const textos = {
        'excelente': 'Excelente',
        'bueno': 'Bueno',
        'regular': 'Regular',
        'necesita_refuerzo': 'Necesita Refuerzo'
    };
    return textos[nivel] || nivel;
};

const getParticipacionTexto = (participacion) => {
    const textos = {
        'alta': 'Alta',
        'media': 'Media',
        'baja': 'Baja'
    };
    return textos[participacion] || participacion;
};

const getCumplimientoTexto = (cumplimiento) => {
    const textos = {
        'completo': 'Completo',
        'parcial': 'Parcial',
        'no_cumplido': 'No Cumplido'
    };
    return textos[cumplimiento] || cumplimiento;
};
</script>

<template>
    <AppLayout>
        <Head :title="`Informe de Clase - ${asistencia?.alumno_nombre || ''}`" />

        <div class="space-y-6">
            <!-- Header con información de la asistencia -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-start justify-between mb-4">
                    <button
                        @click="volverAsistencias"
                        class="inline-flex items-center text-gray-600 hover:text-gray-900 transition"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Volver a Asistencias
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Alumno</p>
                        <p class="text-lg font-semibold text-gray-900">{{ asistencia?.alumno_nombre }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Tutor</p>
                        <p class="text-lg font-semibold text-gray-900">{{ asistencia?.tutor_nombre }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Fecha Asistencia</p>
                        <p class="text-lg font-semibold text-gray-900">
                            {{ asistencia?.fecha ? formatearFecha(asistencia.fecha) : '-' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Estado Asistencia</p>
                        <span v-if="asistencia" :class="getEstadoAsistenciaClass(asistencia.estado)" class="px-3 py-1 text-xs font-semibold rounded-full">
                            {{ getEstadoAsistenciaTexto(asistencia.estado) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Alerta si no es presente -->
            <div v-if="asistencia && asistencia.estado !== 'presente'" class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            Los informes solo se pueden crear para asistencias marcadas como "Presente".
                        </p>
                    </div>
                </div>
            </div>

            <!-- Header con título y botón -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Informe de Clase</h2>
                    <p class="text-gray-600 mt-1">Documenta los detalles de esta clase</p>
                </div>
                <button
                    v-if="!informe && asistencia?.estado === 'presente' && puedeCrearInforme"
                    @click="crearInforme"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Crear Informe
                </button>
            </div>

            <!-- Contenido principal -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div v-if="loading" class="p-8 text-center">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                    <p class="mt-2 text-gray-600">Cargando informe...</p>
                </div>

                <!-- Sin informe -->
                <div v-if="!informe" class="p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="mt-2 text-gray-600">No hay informe para esta asistencia</p>
                    <!-- CAMBIADO: Usa puedeCrearInforme -->
                    <button
                        v-if="puedeCrearInforme"
                        @click="crearInforme"
                        class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
                    >
                        Crear Informe
                    </button>
                </div>

                <!-- Con informe - Vista de detalle -->
                <div v-else class="p-6 space-y-6">
                    
                    <!-- Información General -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Información General
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Fecha</p>
                                <p class="text-sm font-medium text-gray-900 mt-1">{{ formatearFecha(informe.fecha) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Estado</p>
                                <span :class="getEstadoClass(informe.estado)" class="inline-block px-2 py-1 text-xs font-semibold rounded-full mt-1">
                                    {{ getEstadoTexto(informe.estado) }}
                                </span>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Alumno</p>
                                <p class="text-sm font-medium text-gray-900 mt-1">{{ informe.alumno_nombre || asistencia?.alumno_nombre || '-' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Contenido de la Clase -->
                    <div class="border-l-4 border-indigo-500 pl-4">
                        <h4 class="font-semibold text-gray-900 mb-3">Temas Vistos</h4>
                        <p class="text-gray-700 whitespace-pre-wrap">{{ informe.temas_vistos || 'No especificado' }}</p>
                    </div>

                    <div v-if="informe.tareas_asignadas" class="border-l-4 border-blue-500 pl-4">
                        <h4 class="font-semibold text-gray-900 mb-3">Tareas Asignadas</h4>
                        <p class="text-gray-700 whitespace-pre-wrap">{{ informe.tareas_asignadas }}</p>
                    </div>

                    <!-- Evaluación -->
                    <div class="bg-blue-50 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Evaluación del Alumno
                        </h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div v-if="informe.nivel_comprension">
                                <p class="text-xs text-gray-600 mb-2">Nivel de Comprensión</p>
                                <span :class="getNivelComprensionClass(informe.nivel_comprension)" class="inline-block px-2 py-1 text-xs font-semibold rounded-full">
                                    {{ getNivelComprensionTexto(informe.nivel_comprension) }}
                                </span>
                            </div>
                            <div v-if="informe.participacion">
                                <p class="text-xs text-gray-600 mb-2">Participación</p>
                                <p class="text-sm font-medium text-gray-900">{{ getParticipacionTexto(informe.participacion) }}</p>
                            </div>
                            <div v-if="informe.cumplimiento_tareas">
                                <p class="text-xs text-gray-600 mb-2">Cumplimiento Tareas</p>
                                <p class="text-sm font-medium text-gray-900">{{ getCumplimientoTexto(informe.cumplimiento_tareas) }}</p>
                            </div>
                            <div v-if="informe.calificacion">
                                <p class="text-xs text-gray-600 mb-2">Calificación</p>
                                <p class="text-2xl font-bold text-indigo-600">{{ informe.calificacion }}/100</p>
                            </div>
                        </div>
                    </div>

                    <!-- Informe Detallado -->
                    <div class="space-y-4">
                        <h4 class="font-semibold text-gray-900 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Informe Detallado
                        </h4>

                        <div v-if="informe.resumen" class="bg-gray-50 rounded-lg p-4">
                            <h5 class="text-sm font-semibold text-gray-700 mb-2">Resumen</h5>
                            <p class="text-gray-700 text-sm whitespace-pre-wrap">{{ informe.resumen }}</p>
                        </div>

                        <div v-if="informe.logros" class="bg-green-50 rounded-lg p-4">
                            <h5 class="text-sm font-semibold text-green-800 mb-2 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Logros
                            </h5>
                            <p class="text-gray-700 text-sm whitespace-pre-wrap">{{ informe.logros }}</p>
                        </div>

                        <div v-if="informe.dificultades" class="bg-yellow-50 rounded-lg p-4">
                            <h5 class="text-sm font-semibold text-yellow-800 mb-2 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                Dificultades
                            </h5>
                            <p class="text-gray-700 text-sm whitespace-pre-wrap">{{ informe.dificultades }}</p>
                        </div>

                        <div v-if="informe.recomendaciones" class="bg-blue-50 rounded-lg p-4">
                            <h5 class="text-sm font-semibold text-blue-800 mb-2 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                </svg>
                                Recomendaciones
                            </h5>
                            <p class="text-gray-700 text-sm whitespace-pre-wrap">{{ informe.recomendaciones }}</p>
                        </div>

                        <div v-if="informe.observaciones" class="bg-gray-50 rounded-lg p-4">
                            <h5 class="text-sm font-semibold text-gray-700 mb-2">Observaciones Adicionales</h5>
                            <p class="text-gray-700 text-sm whitespace-pre-wrap">{{ informe.observaciones }}</p>
                        </div>
                    </div>

                    <!-- Botón de editar -->
                    <div class="flex justify-end pt-4 border-t">
                        <button
                            @click="editarInforme"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Editar Informe
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dialog para crear/editar informe -->
        <InformeFormDialog
            :show="showFormDialog"
            :asistencia-id="asistenciaId"
            :informe="informeToEdit"
            @close="showFormDialog = false"
            @saved="handleInformeSaved"
        />

    </AppLayout>
</template>