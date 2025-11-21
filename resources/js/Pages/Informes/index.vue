<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '../../Layout/AppLayout.vue';
import { useApi } from '../../composables/useApi';
import InformeFormDialog from './components/InformeFormDialog.vue';
import InformeDetailDialog from './components/InformeDetailDialog.vue';

const props = defineProps({
    inscripcionId: {
        type: [String, Number],
        required: true
    }
});

const { informesClase, inscripciones } = useApi();
const informes = ref([]);
const inscripcion = ref(null);
const loading = ref(false);

// Filtros
const filtroFechaDesde = ref('');
const filtroFechaHasta = ref('');
const filtroEstado = ref('');

// Modal de eliminación
const showDeleteModal = ref(false);
const informeToDelete = ref(null);

// Dialogs
const showFormDialog = ref(false);
const showDetailDialog = ref(false);
const informeToEdit = ref(null);
const informeToView = ref(null);

const cargarInscripcion = async () => {
    const response = await inscripciones.getById(props.inscripcionId);
    if (response.success) {
        inscripcion.value = response.data.data;
    }
};

const cargarInformes = async () => {
    loading.value = true;

    try {
        const params = {
            inscripcion_id: props.inscripcionId
        };

        if (filtroFechaDesde.value) params.fecha_desde = filtroFechaDesde.value;
        if (filtroFechaHasta.value) params.fecha_hasta = filtroFechaHasta.value;
        if (filtroEstado.value) params.estado = filtroEstado.value;

        const response = await informesClase.getAll(params);

        if (response.success) {
            informes.value = response.data.data;
        }
    } catch (error) {
        console.error("Error:", error);
    } finally {
        loading.value = false;
    }
};

onMounted(async () => {
    await cargarInscripcion();
    await cargarInformes();
});

// Aplicar filtros
const aplicarFiltros = () => {
    cargarInformes();
};

// Limpiar filtros
const limpiarFiltros = () => {
    filtroFechaDesde.value = '';
    filtroFechaHasta.value = '';
    filtroEstado.value = '';
    cargarInformes();
};

// Confirmar eliminación
const confirmarEliminacion = (informe) => {
    informeToDelete.value = informe;
    showDeleteModal.value = true;
};

// Eliminar informe
const eliminarInforme = async () => {
    if (!informeToDelete.value) return;

    loading.value = true;
    const response = await informesClase.delete(informeToDelete.value.id);

    if (response.success) {
        await cargarInformes();
        showDeleteModal.value = false;
        informeToDelete.value = null;
    }

    loading.value = false;
};

// Ver detalle del informe
const verDetalle = async (informeId) => {
    const response = await informesClase.getById(informeId);
    if (response.success) {
        informeToView.value = response.data.data;
        showDetailDialog.value = true;
    }
};

// Editar informe
const editarInforme = async (informeId) => {
    const response = await informesClase.getById(informeId);
    if (response.success) {
        informeToEdit.value = response.data.data;
        showFormDialog.value = true;
    }
};

// Crear nuevo informe
const crearInforme = () => {
    informeToEdit.value = null;
    showFormDialog.value = true;
};

// Manejar guardado de informe
const handleInformeSaved = () => {
    cargarInformes();
    showFormDialog.value = false;
    informeToEdit.value = null;
};

// Manejar edición desde dialog de detalle
const handleEditFromDetail = () => {
    informeToEdit.value = informeToView.value;
    showDetailDialog.value = false;
    showFormDialog.value = true;
};

// Volver a inscripciones
const volverInscripciones = () => {
    router.visit('/inscripciones');
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

// Obtener texto del estado
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

// Formatear fecha
const formatearFecha = (fecha) => {
    return new Date(fecha).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};
</script>

<template>
    <AppLayout>
        <Head :title="`Informes de Clase - ${inscripcion?.alumno_nombre || ''}`" />

        <div class="space-y-6">
            <!-- Header con información de la inscripción -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-start justify-between mb-4">
                    <button
                        @click="volverInscripciones"
                        class="inline-flex items-center text-gray-600 hover:text-gray-900 transition"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Volver a Inscripciones
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Alumno</p>
                        <p class="text-lg font-semibold text-gray-900">{{ inscripcion?.alumno_nombre }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Tutor</p>
                        <p class="text-lg font-semibold text-gray-900">{{ inscripcion?.tutor_nombre }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Servicio</p>
                        <p class="text-lg font-semibold text-gray-900">{{ inscripcion?.servicio_nombre }}</p>
                    </div>
                </div>
            </div>

            <!-- Header con título y botón -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Informes de Clase</h2>
                    <p class="text-gray-600 mt-1">Gestiona los informes de cada clase</p>
                </div>
                <button
                    @click="crearInforme"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Nuevo Informe
                </button>
            </div>

            <!-- Filtros -->
            <div class="bg-white rounded-lg shadow-sm p-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Fecha Desde -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Desde</label>
                        <input
                            v-model="filtroFechaDesde"
                            type="date"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>

                    <!-- Fecha Hasta -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Hasta</label>
                        <input
                            v-model="filtroFechaHasta"
                            type="date"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>

                    <!-- Estado -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                        <select
                            v-model="filtroEstado"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="">Todos</option>
                            <option value="realizada">Realizada</option>
                            <option value="cancelada">Cancelada</option>
                            <option value="reprogramada">Reprogramada</option>
                        </select>
                    </div>

                    <!-- Botones -->
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

            <!-- Tabla de informes -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div v-if="loading" class="p-8 text-center">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                    <p class="mt-2 text-gray-600">Cargando informes...</p>
                </div>

                <div v-else-if="!informes || informes.length === 0" class="p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="mt-2 text-gray-600">No hay informes registrados para esta inscripción</p>
                    <button
                        @click="crearInforme"
                        class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
                    >
                        Crear primer informe
                    </button>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Fecha
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Temas Vistos
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Nivel Comprensión
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Calificación
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
                            <tr v-for="informe in informes" :key="informe.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ formatearFecha(informe.fecha) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 max-w-xs truncate" :title="informe.temas_vistos">
                                        {{ informe.temas_vistos }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span 
                                        v-if="informe.nivel_comprension"
                                        :class="getNivelComprensionClass(informe.nivel_comprension)" 
                                        class="px-2 py-1 text-xs font-semibold rounded-full"
                                    >
                                        {{ getNivelComprensionTexto(informe.nivel_comprension) }}
                                    </span>
                                    <span v-else class="text-sm text-gray-400">-</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ informe.calificacion ? `${informe.calificacion}/100` : '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="getEstadoClass(informe.estado)" class="px-2 py-1 text-xs font-semibold rounded-full">
                                        {{ getEstadoTexto(informe.estado) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end gap-2">
                                        <!-- Ver detalle -->
                                        <button
                                            @click="verDetalle(informe.id)"
                                            class="text-blue-600 hover:text-blue-900"
                                            title="Ver detalle"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>

                                        <!-- Editar -->
                                        <button
                                            @click="editarInforme(informe.id)"
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

        <!-- Dialogs -->
        <InformeFormDialog
            :show="showFormDialog"
            :inscripcion-id="inscripcionId"
            :informe="informeToEdit"
            @close="showFormDialog = false"
            @saved="handleInformeSaved"
        />

        <InformeDetailDialog
            :show="showDetailDialog"
            :informe="informeToView"
            @close="showDetailDialog = false"
            @edit="handleEditFromDetail"
        />

    </AppLayout>
</template>