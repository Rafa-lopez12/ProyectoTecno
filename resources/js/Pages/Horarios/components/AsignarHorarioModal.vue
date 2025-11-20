<script setup>
import { ref, onMounted, computed } from 'vue';
import { useApi } from '../../../composables/useApi';

const emit = defineEmits(['close', 'success']);

const { tutores, horarios: horariosApi } = useApi();

const paso = ref(1); // 1: Seleccionar tutor, 2: Seleccionar/crear horario
const tutorSeleccionado = ref(null);
const horarioSeleccionado = ref(null);
const listaHorarios = ref([]);
const listaTutores = ref([]);
const loading = ref(false);
const mostrarFormNuevoHorario = ref(false);

// Formulario para nuevo horario
const nuevoHorario = ref({
    dia_semana: '',
    hora_inicio: '',
    hora_fin: '',
    estado: 'activo'
});

const diasSemana = [
    { value: 'lunes', label: 'Lunes' },
    { value: 'martes', label: 'Martes' },
    { value: 'miércoles', label: 'Miércoles' },
    { value: 'jueves', label: 'Jueves' },
    { value: 'viernes', label: 'Viernes' },
    { value: 'sábado', label: 'Sábado' },
    { value: 'domingo', label: 'Domingo' }
];

const horariosDisponibles = computed(() => {
    if (!tutorSeleccionado.value) return listaHorarios.value;
    
    // Obtener IDs de horarios ya asignados al tutor
    const horariosAsignadosIds = tutorSeleccionado.value.horariosAsignados || [];
    
    // Filtrar horarios disponibles (no asignados a este tutor)
    return listaHorarios.value.filter(h => !horariosAsignadosIds.includes(h.id));
});

const cargarTutores = async () => {
    loading.value = true;
    const result = await tutores.getAll();
    if (result.success) {
        // Cargar horarios asignados para cada tutor
        const tutoresConHorarios = await Promise.all(
            result.data.data.map(async (tutor) => {
                const resultHorarios = await horariosApi.getHorariosDeTutor(tutor.id);
                return {
                    ...tutor,
                    horariosAsignados: resultHorarios.success 
                        ? resultHorarios.data.data.map(h => h.id)
                        : []
                };
            })
        );
        listaTutores.value = tutoresConHorarios;
    }
    loading.value = false;
};

const cargarHorarios = async () => {
    loading.value = true;
    const result = await horariosApi.getAll();
    if (result.success) {
        listaHorarios.value = result.data.data;
    }
    loading.value = false;
};

const seleccionarTutor = (tutor) => {
    tutorSeleccionado.value = tutor;
    paso.value = 2;
};

const volverPaso1 = () => {
    paso.value = 1;
    tutorSeleccionado.value = null;
    horarioSeleccionado.value = null;
    mostrarFormNuevoHorario.value = false;
};

const toggleNuevoHorario = () => {
    mostrarFormNuevoHorario.value = !mostrarFormNuevoHorario.value;
    if (mostrarFormNuevoHorario.value) {
        horarioSeleccionado.value = null;
    }
};

const crearYAsignarHorario = async () => {
    if (!nuevoHorario.value.dia_semana || !nuevoHorario.value.hora_inicio || !nuevoHorario.value.hora_fin) {
        alert('Por favor completa todos los campos');
        return;
    }
    
    loading.value = true;
    
    // Primero crear el horario
    const resultCrear = await horariosApi.create(nuevoHorario.value);
    
    if (resultCrear.success) {
        const nuevoHorarioId = resultCrear.data.data.id;
        
        // Luego asignarlo al tutor
        const resultAsignar = await horariosApi.asignarTutor(nuevoHorarioId, tutorSeleccionado.value.id);
        
        if (resultAsignar.success) {
            emit('success');
        } else {
            alert('Error al asignar horario: ' + (resultAsignar.error || 'Error desconocido'));
        }
    } else {
        alert('Error al crear horario: ' + (resultCrear.error || 'Error desconocido'));
    }
    
    loading.value = false;
};

const asignarHorario = async () => {
    if (!horarioSeleccionado.value) {
        alert('Por favor selecciona un horario');
        return;
    }
    
    loading.value = true;
    
    const result = await horariosApi.asignarTutor(horarioSeleccionado.value.id, tutorSeleccionado.value.id);
    
    if (result.success) {
        emit('success');
    } else {
        alert('Error al asignar horario: ' + (result.error || 'Error desconocido'));
    }
    
    loading.value = false;
};

const formatearHora = (hora) => {
    return hora ? hora.substring(0, 5) : '';
};

onMounted(() => {
    cargarTutores();
    cargarHorarios();
});
</script>

<template>
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col">
            <!-- Header -->
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-4 flex items-center justify-between">
                <h3 class="text-xl font-bold text-white">
                    {{ paso === 1 ? 'Seleccionar Tutor' : 'Asignar Horario' }}
                </h3>
                <button
                    @click="emit('close')"
                    class="text-white hover:text-gray-200 transition-colors"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Progress Steps -->
            <div class="px-6 py-4 bg-gray-50 border-b">
                <div class="flex items-center justify-center space-x-4">
                    <div class="flex items-center">
                        <div :class="['w-8 h-8 rounded-full flex items-center justify-center font-bold', paso === 1 ? 'bg-indigo-600 text-white' : 'bg-green-600 text-white']">
                            <svg v-if="paso > 1" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span v-else>1</span>
                        </div>
                        <span class="ml-2 text-sm font-medium text-gray-700">Seleccionar Tutor</span>
                    </div>
                    <div class="flex-1 h-1 bg-gray-200 rounded">
                        <div :class="['h-full rounded transition-all duration-300', paso > 1 ? 'bg-green-600' : 'bg-gray-200']" :style="{ width: paso > 1 ? '100%' : '0%' }"></div>
                    </div>
                    <div class="flex items-center">
                        <div :class="['w-8 h-8 rounded-full flex items-center justify-center font-bold', paso === 2 ? 'bg-indigo-600 text-white' : 'bg-gray-300 text-gray-600']">
                            2
                        </div>
                        <span class="ml-2 text-sm font-medium text-gray-700">Asignar Horario</span>
                    </div>
                </div>
            </div>

            <!-- Body -->
            <div class="flex-1 overflow-y-auto p-6">
                <!-- Paso 1: Seleccionar Tutor -->
                <div v-if="paso === 1">
                    <div v-if="loading" class="text-center py-12">
                        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
                        <p class="mt-4 text-gray-600">Cargando tutores...</p>
                    </div>

                    <div v-else-if="listaTutores.length === 0" class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <p class="mt-2 text-gray-600">No hay tutores disponibles</p>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <button
                            v-for="tutor in listaTutores"
                            :key="tutor.id"
                            @click="seleccionarTutor(tutor)"
                            class="text-left p-4 border-2 border-gray-200 rounded-lg hover:border-indigo-500 hover:shadow-md transition-all"
                        >
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    <div class="h-12 w-12 rounded-full bg-indigo-100 flex items-center justify-center">
                                        <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-gray-900 truncate">
                                        {{ tutor.nombre }} {{ tutor.apellido }}
                                    </p>
                                    <p class="text-sm text-gray-500 truncate">
                                        {{ tutor.email }}
                                    </p>
                                    <p class="text-xs text-indigo-600 mt-1">
                                        {{ tutor.horariosAsignados.length }} horarios asignados
                                    </p>
                                </div>
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Paso 2: Asignar Horario -->
                <div v-if="paso === 2" class="space-y-6">
                    <!-- Tutor Seleccionado -->
                    <div class="bg-indigo-50 border-2 border-indigo-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="h-10 w-10 rounded-full bg-indigo-600 flex items-center justify-center">
                                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900">
                                        {{ tutorSeleccionado.nombre }} {{ tutorSeleccionado.apellido }}
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        {{ tutorSeleccionado.email }}
                                    </p>
                                </div>
                            </div>
                            <button
                                @click="volverPaso1"
                                class="text-indigo-600 hover:text-indigo-800 text-sm font-medium"
                            >
                                Cambiar tutor
                            </button>
                        </div>
                    </div>

                    <!-- Toggle entre horarios existentes y crear nuevo -->
                    <div class="flex items-center justify-center space-x-4">
                        <button
                            @click="mostrarFormNuevoHorario = false"
                            :class="[
                                'px-4 py-2 rounded-lg font-medium transition-colors',
                                !mostrarFormNuevoHorario 
                                    ? 'bg-indigo-600 text-white' 
                                    : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                            ]"
                        >
                            Horarios Existentes
                        </button>
                        <button
                            @click="toggleNuevoHorario"
                            :class="[
                                'px-4 py-2 rounded-lg font-medium transition-colors',
                                mostrarFormNuevoHorario 
                                    ? 'bg-indigo-600 text-white' 
                                    : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                            ]"
                        >
                            Crear Nuevo Horario
                        </button>
                    </div>

                    <!-- Horarios Existentes -->
                    <div v-if="!mostrarFormNuevoHorario">
                        <div v-if="loading" class="text-center py-12">
                            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
                            <p class="mt-4 text-gray-600">Cargando horarios...</p>
                        </div>

                        <div v-else-if="horariosDisponibles.length === 0" class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="mt-2 text-gray-600">No hay horarios disponibles para asignar</p>
                            <p class="text-sm text-gray-500 mt-1">Todos los horarios ya están asignados a este tutor</p>
                        </div>

                        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <button
                                v-for="horario in horariosDisponibles"
                                :key="horario.id"
                                @click="horarioSeleccionado = horario"
                                :class="[
                                    'text-left p-4 border-2 rounded-lg transition-all',
                                    horarioSeleccionado?.id === horario.id
                                        ? 'border-indigo-500 bg-indigo-50 shadow-md'
                                        : 'border-gray-200 hover:border-indigo-300 hover:shadow'
                                ]"
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-semibold text-gray-900 capitalize">
                                            {{ horario.dia_semana }}
                                        </p>
                                        <p class="text-sm text-gray-600 mt-1">
                                            {{ formatearHora(horario.hora_inicio) }} - {{ formatearHora(horario.hora_fin) }}
                                        </p>
                                    </div>
                                    <div v-if="horarioSeleccionado?.id === horario.id" class="flex-shrink-0">
                                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Formulario Nuevo Horario -->
                    <div v-else class="bg-gray-50 rounded-lg p-6 space-y-4">
                        <h4 class="font-semibold text-gray-900">Crear Nuevo Horario</h4>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Día de la Semana</label>
                            <select
                                v-model="nuevoHorario.dia_semana"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            >
                                <option value="">Seleccionar día</option>
                                <option v-for="dia in diasSemana" :key="dia.value" :value="dia.value">
                                    {{ dia.label }}
                                </option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Hora Inicio</label>
                                <input
                                    v-model="nuevoHorario.hora_inicio"
                                    type="time"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Hora Fin</label>
                                <input
                                    v-model="nuevoHorario.hora_fin"
                                    type="time"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-3 border-t">
                <button
                    @click="emit('close')"
                    class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    Cancelar
                </button>
                <button
                    v-if="paso === 2"
                    @click="mostrarFormNuevoHorario ? crearYAsignarHorario() : asignarHorario()"
                    :disabled="loading || (!mostrarFormNuevoHorario && !horarioSeleccionado)"
                    class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span v-if="loading">Procesando...</span>
                    <span v-else>{{ mostrarFormNuevoHorario ? 'Crear y Asignar' : 'Asignar Horario' }}</span>
                </button>
            </div>
        </div>
    </div>
</template>