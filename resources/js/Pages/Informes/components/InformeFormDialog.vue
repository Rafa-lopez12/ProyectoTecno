<script setup>
import { ref, watch, computed } from 'vue';
import { useApi } from '../../../composables/useApi';
import { useFormValidation } from '../../../composables/useFormValidation';



const props = defineProps({
    show: {
        type: Boolean,
        required: true
    },
    asistenciaId: { 
        type: [String, Number],
        required: true
    },
    informe: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['close', 'saved']);

const { informesClase } = useApi();
const loading = ref(false);
const errors = ref({});

const form = ref({
    fecha: '',
    temas_vistos: '',
    tareas_asignadas: '',
    nivel_comprension: '',
    participacion: '',
    cumplimiento_tareas: '',
    calificacion: '',
    resumen: '',
    logros: '',
    dificultades: '',
    recomendaciones: '',
    observaciones: '',
    estado: 'realizada'
});

const isEditMode = computed(() => !!props.informe);

// Inicializar formulario cuando se abre el dialog
watch(() => props.show, (newVal) => {
    if (newVal) {
        if (props.informe) {
            // Modo edición
            form.value = {
                fecha: props.informe.fecha,
                temas_vistos: props.informe.temas_vistos || '',
                tareas_asignadas: props.informe.tareas_asignadas || '',
                nivel_comprension: props.informe.nivel_comprension || '',
                participacion: props.informe.participacion || '',
                cumplimiento_tareas: props.informe.cumplimiento_tareas || '',
                calificacion: props.informe.calificacion || '',
                resumen: props.informe.resumen || '',
                logros: props.informe.logros || '',
                dificultades: props.informe.dificultades || '',
                recomendaciones: props.informe.recomendaciones || '',
                observaciones: props.informe.observaciones || '',
                estado: props.informe.estado || 'realizada'
            };
        } else {
            // Modo creación - fecha actual por defecto
            const today = new Date().toISOString().split('T')[0];
            form.value = {
                fecha: today,
                temas_vistos: '',
                tareas_asignadas: '',
                nivel_comprension: '',
                participacion: '',
                cumplimiento_tareas: '',
                calificacion: '',
                resumen: '',
                logros: '',
                dificultades: '',
                recomendaciones: '',
                observaciones: '',
                estado: 'realizada'
            };
        }
        errors.value = {};
    }
});

const closeDialog = () => {
    emit('close');
};

const guardarInforme = async () => {
    loading.value = true;
    errors.value = {};

    const datos = {
        asistencia_id: props.asistenciaId,
        ...form.value
    };

    // Convertir valores vacíos a null para campos opcionales
    if (!datos.tareas_asignadas) datos.tareas_asignadas = null;
    if (!datos.nivel_comprension) datos.nivel_comprension = null;
    if (!datos.participacion) datos.participacion = null;
    if (!datos.cumplimiento_tareas) datos.cumplimiento_tareas = null;
    if (!datos.calificacion) datos.calificacion = null;
    if (!datos.resumen) datos.resumen = null;
    if (!datos.logros) datos.logros = null;
    if (!datos.dificultades) datos.dificultades = null;
    if (!datos.recomendaciones) datos.recomendaciones = null;
    if (!datos.observaciones) datos.observaciones = null;

    let response;
    if (isEditMode.value) {
        response = await informesClase.update(props.informe.id, datos);
    } else {
        console.log(datos)
        response = await informesClase.create(datos);
    }

    if (response.success) {
        emit('saved');
        closeDialog();
    } else {
        if (response.errors) {
            errors.value = response.errors;
        } else {
            errors.value = { general: response.error };
        }
    }

    loading.value = false;
};
</script>

<template>
    <!-- Overlay -->
    <Transition name="fade">
        <div
            v-if="show"
            class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4"
            @click.self="closeDialog"
        >
            <!-- Dialog -->
            <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
                <!-- Header -->
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h3 class="text-xl font-semibold text-gray-900">
                        {{ isEditMode ? 'Editar Informe de Clase' : 'Nuevo Informe de Clase' }}
                    </h3>
                    <button
                        @click="closeDialog"
                        class="text-gray-400 hover:text-gray-600 transition"
                        :disabled="loading"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Body - Scrollable -->
                <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)]">
                    <!-- Error general -->
                    <div v-if="errors.general" class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                        {{ errors.general }}
                    </div>

                    <form @submit.prevent="guardarInforme" class="space-y-6">
                        <!-- Información básica -->
                        <div class="bg-gray-50 p-4 rounded-lg space-y-4">
                            <h4 class="font-semibold text-gray-900">Información Básica</h4>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Fecha -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Fecha <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.fecha"
                                        type="date"
                                        required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                        :class="{ 'border-red-500': errors.fecha }"
                                    />
                                    <p v-if="errors.fecha" class="mt-1 text-sm text-red-600">{{ errors.fecha[0] }}</p>
                                </div>

                                <!-- Estado -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Estado <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.estado"
                                        required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                    >
                                        <option value="realizada">Realizada</option>
                                        <option value="cancelada">Cancelada</option>
                                        <option value="reprogramada">Reprogramada</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Temas vistos -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Temas Vistos <span class="text-red-500">*</span>
                                </label>
                                <textarea
                                    v-model="form.temas_vistos"
                                    required
                                    rows="3"
                                    placeholder="Describe los temas tratados en la clase..."
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                    :class="{ 'border-red-500': errors.temas_vistos }"
                                ></textarea>
                                <p v-if="errors.temas_vistos" class="mt-1 text-sm text-red-600">{{ errors.temas_vistos[0] }}</p>
                            </div>

                            <!-- Tareas asignadas -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Tareas Asignadas
                                </label>
                                <textarea
                                    v-model="form.tareas_asignadas"
                                    rows="2"
                                    placeholder="Tareas o ejercicios para realizar en casa..."
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                ></textarea>
                            </div>
                        </div>

                        <!-- Evaluación del alumno -->
                        <div class="bg-gray-50 p-4 rounded-lg space-y-4">
                            <h4 class="font-semibold text-gray-900">Evaluación del Alumno</h4>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Nivel de comprensión -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Nivel de Comprensión
                                    </label>
                                    <select
                                        v-model="form.nivel_comprension"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                    >
                                        <option value="">Seleccionar...</option>
                                        <option value="excelente">Excelente</option>
                                        <option value="bueno">Bueno</option>
                                        <option value="regular">Regular</option>
                                        <option value="necesita_refuerzo">Necesita Refuerzo</option>
                                    </select>
                                </div>

                                <!-- Participación -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Participación
                                    </label>
                                    <select
                                        v-model="form.participacion"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                    >
                                        <option value="">Seleccionar...</option>
                                        <option value="alta">Alta</option>
                                        <option value="media">Media</option>
                                        <option value="baja">Baja</option>
                                    </select>
                                </div>

                                <!-- Cumplimiento de tareas -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Cumplimiento de Tareas
                                    </label>
                                    <select
                                        v-model="form.cumplimiento_tareas"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                    >
                                        <option value="">Seleccionar...</option>
                                        <option value="completo">Completo</option>
                                        <option value="parcial">Parcial</option>
                                        <option value="no_cumplido">No Cumplido</option>
                                    </select>
                                </div>

                                <!-- Calificación -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Calificación (0-100)
                                    </label>
                                    <input
                                        v-model.number="form.calificacion"
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        placeholder="Ej: 85.5"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Informe detallado -->
                        <div class="bg-gray-50 p-4 rounded-lg space-y-4">
                            <h4 class="font-semibold text-gray-900">Informe Detallado</h4>
                            
                            <!-- Resumen -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Resumen de la Clase
                                </label>
                                <textarea
                                    v-model="form.resumen"
                                    rows="3"
                                    placeholder="Resumen general de cómo se desarrolló la clase..."
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                ></textarea>
                            </div>

                            <!-- Logros -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Logros del Alumno
                                </label>
                                <textarea
                                    v-model="form.logros"
                                    rows="2"
                                    placeholder="Aspectos positivos y logros destacados..."
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                ></textarea>
                            </div>

                            <!-- Dificultades -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Dificultades Encontradas
                                </label>
                                <textarea
                                    v-model="form.dificultades"
                                    rows="2"
                                    placeholder="Áreas donde el alumno presentó dificultades..."
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                ></textarea>
                            </div>

                            <!-- Recomendaciones -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Recomendaciones
                                </label>
                                <textarea
                                    v-model="form.recomendaciones"
                                    rows="2"
                                    placeholder="Recomendaciones para mejorar el desempeño..."
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                ></textarea>
                            </div>

                            <!-- Observaciones -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Observaciones Adicionales
                                </label>
                                <textarea
                                    v-model="form.observaciones"
                                    rows="2"
                                    placeholder="Cualquier otra observación relevante..."
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                ></textarea>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Footer -->
                <div class="flex justify-end gap-3 p-6 border-t border-gray-200 bg-gray-50">
                    <button
                        @click="closeDialog"
                        type="button"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                        :disabled="loading"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="guardarInforme"
                        type="button"
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50"
                        :disabled="loading"
                    >
                        {{ loading ? 'Guardando...' : (isEditMode ? 'Actualizar' : 'Crear') }}
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