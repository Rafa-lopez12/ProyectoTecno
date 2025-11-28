<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '../../Layout/AppLayout.vue';
import { useApi } from '../../composables/useApi';
import { useAuth } from '../../composables/useAuth';

const { inscripciones: inscripcionesApi } = useApi();
const { user } = useAuth();

const inscripciones = ref([]);
const loading = ref(false);

// Filtros
const filtroEstado = ref('');
const filtroServicio = ref('');
const searchQuery = ref('');

// Verificar si es tutor
const esTutor = computed(() => user.value?.rol === 'tutor');

const cargarInscripciones = async () => {
    loading.value = true;

    try {
        const filtros = {};
        
        if (filtroEstado.value) {
            filtros.estado = filtroEstado.value;
        }
        
        if (filtroServicio.value) {
            filtros.servicio_id = filtroServicio.value;
        }

        const response = await inscripcionesApi.getAll(filtros);

        if (response.success) {
            inscripciones.value = response.data.data;
        } else {
            console.error("Error al obtener inscripciones");
        }

    } catch (error) {
        console.error("Error:", error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    cargarInscripciones();
});

// Aplicar filtros
const aplicarFiltros = () => {
    cargarInscripciones();
};

// Limpiar filtros
const limpiarFiltros = () => {
    filtroEstado.value = '';
    filtroServicio.value = '';
    searchQuery.value = '';
    cargarInscripciones();
};

// Inscripciones filtradas por búsqueda
const inscripcionesFiltradas = computed(() => {
    if (!searchQuery.value) {
        return inscripciones.value;
    }

    const query = searchQuery.value.toLowerCase();
    return inscripciones.value.filter(inscripcion => 
        inscripcion.alumno_nombre?.toLowerCase().includes(query) ||
        inscripcion.tutor_nombre?.toLowerCase().includes(query) ||
        inscripcion.servicio_nombre?.toLowerCase().includes(query)
    );
});

// Obtener clase CSS para estado
const getEstadoClass = (estado) => {
    const classes = {
        'activo': 'bg-green-100 text-green-800',
        'retirado': 'bg-red-100 text-red-800',
        'finalizado': 'bg-blue-100 text-blue-800'
    };
    return classes[estado] || 'bg-gray-100 text-gray-800';
};

// Obtener texto del estado
const getEstadoTexto = (estado) => {
    const textos = {
        'activo': 'Activo',
        'retirado': 'Retirado',
        'finalizado': 'Finalizado'
    };
    return textos[estado] || estado;
};

// Ver asistencias de una inscripción
const verAsistencias = (inscripcionId) => {
    router.visit(`asistencias/inscripcion/${inscripcionId}`);
};
</script>

<template>
    <AppLayout>
        <Head title="Asistencias" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Gestión de Asistencias</h2>
                    <p class="text-gray-600 mt-1">
                        {{ esTutor ? 'Selecciona una inscripción para gestionar asistencias' : 'Selecciona una inscripción para ver y gestionar asistencias' }}
                    </p>
                </div>
            </div>

            <!-- Buscador y Filtros -->
            <div class="bg-white rounded-lg shadow-sm p-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Búsqueda -->
                    <div class="md:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                        <div class="relative">
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Alumno, tutor o servicio..."
                                class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            />
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Filtro por Estado -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                        <select
                            v-model="filtroEstado"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                            <option value="">Todos</option>
                            <option value="activo">Activo</option>
                            <option value="retirado">Retirado</option>
                            <option value="finalizado">Finalizado</option>
                        </select>
                    </div>

                    <!-- Filtro por Servicio -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Servicio</label>
                        <select
                            v-model="filtroServicio"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                            <option value="">Todos</option>
                            <!-- Aquí deberías cargar los servicios disponibles -->
                        </select>
                    </div>

                    <!-- Botones de filtro -->
                    <div class="flex items-end gap-2">
                        <button
                            @click="aplicarFiltros"
                            class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
                        >
                            Filtrar
                        </button>
                        <button
                            @click="limpiarFiltros"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition"
                        >
                            Limpiar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Info Card -->
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            Haz clic en "Ver Asistencias" para gestionar las asistencias de cada inscripción.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Tabla de inscripciones -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div v-if="loading" class="p-8 text-center">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                    <p class="mt-2 text-gray-600">Cargando inscripciones...</p>
                </div>

                <div v-else-if="!inscripcionesFiltradas || inscripcionesFiltradas.length === 0" class="p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <p class="mt-2 text-gray-600">
                        {{ searchQuery ? 'No se encontraron inscripciones' : 'No hay inscripciones registradas' }}
                    </p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Alumno
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tutor
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Servicio
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Fecha Inscripción
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Estado
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="inscripcion in inscripcionesFiltradas" :key="inscripcion.id" class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-indigo-100 rounded-full flex items-center justify-center">
                                            <span class="text-indigo-600 font-medium text-sm">
                                                {{ inscripcion.alumno_nombre?.charAt(0) }}
                                            </span>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ inscripcion.alumno_nombre }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ inscripcion.tutor_nombre }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ inscripcion.servicio_nombre }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ new Date(inscripcion.fecha_inscripcion).toLocaleDateString('es-ES') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="getEstadoClass(inscripcion.estado)" class="px-3 py-1 text-xs font-semibold rounded-full">
                                        {{ getEstadoTexto(inscripcion.estado) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <button
                                        @click="verAsistencias(inscripcion.id)"
                                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition"
                                        title="Ver asistencias"
                                    >
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                        </svg>
                                        Ver Asistencias
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Resumen (opcional) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-green-500">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Inscripciones Activas</p>
                            <p class="text-2xl font-bold text-gray-900">
                                {{ inscripciones.filter(i => i.estado === 'activo').length }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-blue-500">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Total Inscripciones</p>
                            <p class="text-2xl font-bold text-gray-900">
                                {{ inscripciones.length }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-red-500">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Finalizadas/Retiradas</p>
                            <p class="text-2xl font-bold text-gray-900">
                                {{ inscripciones.filter(i => i.estado === 'finalizado' || i.estado === 'retirado').length }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>