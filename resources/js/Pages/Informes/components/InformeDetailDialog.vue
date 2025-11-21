<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        required: true
    },
    informe: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['close', 'edit']);

const closeDialog = () => {
    emit('close');
};

const editarInforme = () => {
    emit('edit');
};

// Formatear fecha
const formatearFecha = (fecha) => {
    if (!fecha) return '-';
    return new Date(fecha).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

// Obtener clase CSS para estado
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

// Obtener clase CSS para nivel de comprensión
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
    <!-- Overlay -->
    <Transition name="fade">
        <div
            v-if="show && informe"
            class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4"
            @click.self="closeDialog"
        >
            <!-- Dialog -->
            <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
                <!-- Header -->
                <div class="flex items-center justify-between p-6 border-b border-gray-200 bg-indigo-50">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900">Detalle del Informe</h3>
                        <p class="text-sm text-gray-600 mt-1">{{ formatearFecha(informe.fecha) }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <button
                            @click="editarInforme"
                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-indigo-600 bg-white rounded-lg hover:bg-indigo-50 border border-indigo-200"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Editar
                        </button>
                        <button
                            @click="closeDialog"
                            class="text-gray-400 hover:text-gray-600 transition"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Body - Scrollable -->
                <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)] space-y-6">
                    
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
                                <p class="text-sm font-medium text-gray-900 mt-1">{{ informe.alumno_nombre || '-' }}</p>
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

                </div>

                <!-- Footer -->
                <div class="flex justify-end gap-3 p-6 border-t border-gray-200 bg-gray-50">
                    <button
                        @click="closeDialog"
                        type="button"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                    >
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
}
</style>