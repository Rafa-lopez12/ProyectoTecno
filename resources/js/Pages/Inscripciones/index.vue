<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '../../Layout/AppLayout.vue';
import { useApi } from '../../composables/useApi';

const props = defineProps({
    inscripciones: Array,
    servicios: Array,
    filtros: Object,
    rol: String
});

const { inscripciones: inscripcionesApi } = useApi();
const inscripciones = ref([]);
const loading = ref(false);


// Filtros
const filtroEstado = ref(props.filtros?.estado || '');
const filtroServicio = ref(props.filtros?.servicio_id || '');

const esTutor = computed(() => props.rol === 'tutor');

const cargarInscripciones = async () => {
    loading.value = true;

    try {
        const response = await inscripcionesApi.getAll();

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
    cargarInscripciones();
};



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

// Ver informes
const verInformes = (inscripcionId) => {
    router.visit(`/inscripciones/${inscripcionId}/informes`);
};

onMounted(() => {
    inscripciones.value = props.inscripciones || [];
});
</script>

<template>
    <AppLayout>
        <Head title="Inscripciones" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Inscripciones</h2>
                    <p class="text-gray-600 mt-1">
                        {{ esTutor ? 'Mis inscripciones asignadas' : 'Gestiona las inscripciones del sistema' }}
                    </p>
                </div>
                <button
                    v-if="!esTutor"
                    @click="router.visit(route('inscripciones.create'))"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Nueva Inscripción
                </button>
            </div>

            <!-- Filtros -->
            <div class="bg-white rounded-lg shadow-sm p-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Filtro por Estado -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                        <select
                            v-model="filtroEstado"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
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
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="">Todos</option>
                            <option v-for="servicio in servicios" :key="servicio.id" :value="servicio.id">
                                {{ servicio.nombre }}
                            </option>
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

            <!-- Tabla de inscripciones -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div v-if="loading" class="p-8 text-center">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                    <p class="mt-2 text-gray-600">Cargando inscripciones...</p>
                </div>

                <div v-else-if="!inscripciones || inscripciones.length === 0" class="p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="mt-2 text-gray-600">No hay inscripciones registradas</p>
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
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="inscripcion in inscripciones" :key="inscripcion.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ inscripcion.alumno_nombre }}
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
                                    <span :class="getEstadoClass(inscripcion.estado)" class="px-2 py-1 text-xs font-semibold rounded-full">
                                        {{ getEstadoTexto(inscripcion.estado) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end gap-2">
                                        <!-- Ver informes -->
                                        <button
                                            @click="verInformes(inscripcion.id)"
                                            class="text-blue-600 hover:text-blue-900"
                                            title="Ver informes"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </button>

                                        <!-- Editar (solo propietario) -->
                                        <button
                                            v-if="!esTutor"
                                            @click="router.visit(route('inscripciones.edit', inscripcion.id))"
                                            class="text-indigo-600 hover:text-indigo-900"
                                            title="Editar"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>

                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal de confirmación de eliminación -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Confirmar Eliminación</h3>
                <p class="text-sm text-gray-500 mb-6">
                    ¿Estás seguro de que deseas eliminar esta inscripción? Esta acción no se puede deshacer.
                </p>
                <div class="flex justify-end gap-3">
                    <button
                        @click="showDeleteModal = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
                        :disabled="loading"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="eliminarInscripcion"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700"
                        :disabled="loading"
                    >
                        {{ loading ? 'Eliminando...' : 'Eliminar' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>