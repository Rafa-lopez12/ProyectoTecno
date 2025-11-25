<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '../../Layout/AppLayout.vue';
import { useApi } from '../../composables/useApi';
import AsignarHorarioModal from './components/AsignarHorarioModal.vue';

const { horarios, tutores } = useApi();

const listaTutores = ref([]);
const loading = ref(false);
const showAsignarModal = ref(false);

const diasSemana = {
    'lunes': 'Lunes',
    'martes': 'Martes',
    'miércoles': 'Miércoles',
    'jueves': 'Jueves',
    'viernes': 'Viernes',
    'sábado': 'Sábado',
    'domingo': 'Domingo'
};

const coloresDia = {
    'lunes': 'bg-blue-50 border-blue-200',
    'martes': 'bg-green-50 border-green-200',
    'miércoles': 'bg-purple-50 border-purple-200',
    'jueves': 'bg-yellow-50 border-yellow-200',
    'viernes': 'bg-pink-50 border-pink-200',
    'sábado': 'bg-indigo-50 border-indigo-200',
    'domingo': 'bg-red-50 border-red-200'
};

const cargarDatos = async () => {
    loading.value = true;
    try {
        // Obtener todos los tutores
        const resultTutores = await tutores.getAll();
        
        if (resultTutores.success) {
            // Para cada tutor, obtener sus horarios
            const tutoresConHorarios = await Promise.all(
                resultTutores.data.data.map(async (tutor) => {
                    const resultHorarios = await horarios.obtenerHorariosDeTutor(tutor.id);
                    
                    // Agrupar horarios por día
                    const horariosPorDia = {};
                    if (resultHorarios.success && resultHorarios.data.data) {
                        resultHorarios.data.data.forEach(horario => {
                            const dia = horario.dia_semana.toLowerCase();
                            if (!horariosPorDia[dia]) {
                                horariosPorDia[dia] = [];
                            }
                            horariosPorDia[dia].push(horario);
                        });
                    }
                    
                    return {
                        ...tutor,
                        horariosPorDia
                    };
                })
            );
            
            listaTutores.value = tutoresConHorarios;
        }
    } catch (error) {
        console.error('Error al cargar datos:', error);
    } finally {
        loading.value = false;
    }
};

const handleAsignacionExitosa = () => {
    showAsignarModal.value = false;
    cargarDatos();
};

const eliminarHorario = async (tutorId, horarioId) => {
    if (!confirm('¿Estás seguro de desasignar este horario del tutor?')) {
        return;
    }
    
    const result = await horarios.desasignarTutor(horarioId, tutorId);
    
    if (result.success) {
        cargarDatos();
    } else {
        alert('Error al desasignar horario: ' + (result.error || 'Error desconocido'));
    }
};

const formatearHora = (hora) => {
    return hora.substring(0, 5); // HH:MM
};

onMounted(() => {
    cargarDatos();
});
</script>

<template>
    <AppLayout>
        <Head title="Horarios de Tutores" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Horarios de Tutores</h2>
                        <p class="mt-1 text-sm text-gray-500">
                            Gestiona los horarios asignados a cada tutor
                        </p>
                    </div>
                    <button
                        @click="showAsignarModal = true"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring focus:ring-indigo-300 disabled:opacity-25 transition"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Nueva Asignación
                    </button>
                </div>
            </div>

            <!-- Loading -->
            <div v-if="loading" class="text-center py-12">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
                <p class="mt-4 text-gray-600">Cargando horarios...</p>
            </div>

            <!-- Lista de Tutores con sus Horarios -->
            <div v-else class="space-y-6">
                <!-- Mensaje si no hay tutores -->
                <div v-if="listaTutores.length === 0" class="bg-white rounded-lg shadow-sm p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No hay tutores registrados</h3>
                    <p class="mt-1 text-sm text-gray-500">Comienza agregando tutores al sistema.</p>
                </div>

                <!-- Card por cada Tutor -->
                <div
                    v-for="tutor in listaTutores"
                    :key="tutor.id"
                    class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow"
                >
                    <!-- Header del Tutor -->
                    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    <div class="h-12 w-12 rounded-full bg-white flex items-center justify-center">
                                        <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-white">
                                        {{ tutor.nombre }} {{ tutor.apellido }}
                                    </h3>
                                    <p class="text-indigo-100 text-sm">
                                        {{ tutor.email }}
                                    </p>
                                </div>
                            </div>
                            <div class="hidden sm:block">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white text-indigo-600">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ Object.keys(tutor.horariosPorDia).length }} días asignados
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Horarios del Tutor -->
                    <div class="p-6">
                        <div v-if="Object.keys(tutor.horariosPorDia).length === 0" class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">Sin horarios asignados</p>
                        </div>

                        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <!-- Card por cada día -->
                            <div
                                v-for="(horariosDia, dia) in tutor.horariosPorDia"
                                :key="dia"
                                :class="['rounded-lg border-2 p-4 transition-all hover:shadow-md', coloresDia[dia] || 'bg-gray-50 border-gray-200']"
                            >
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="font-bold text-gray-800 flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        {{ diasSemana[dia] || dia }}
                                    </h4>
                                    <span class="text-xs font-medium text-gray-500">
                                        {{ horariosDia.length }} {{ horariosDia.length === 1 ? 'horario' : 'horarios' }}
                                    </span>
                                </div>

                                <div class="space-y-2">
                                    <div
                                        v-for="horario in horariosDia"
                                        :key="horario.id"
                                        class="bg-white rounded-md p-3 border border-gray-200 shadow-sm hover:shadow transition-shadow"
                                    >
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-2">
                                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span class="text-sm font-semibold text-gray-700">
                                                    {{ formatearHora(horario.hora_inicio) }} - {{ formatearHora(horario.hora_fin) }}
                                                </span>
                                            </div>
                                            <button
                                                @click="eliminarHorario(tutor.id, horario.id)"
                                                class="text-red-600 hover:text-red-800 p-1 rounded hover:bg-red-50 transition-colors"
                                                title="Desasignar horario"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para Asignar Horario -->
        <AsignarHorarioModal
            v-if="showAsignarModal"
            @close="showAsignarModal = false"
            @success="handleAsignacionExitosa"
        />
    </AppLayout>
</template>