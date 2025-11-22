<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '../../Layout/AppLayout.vue';
import { useApi } from '../../composables/useApi';

const props = defineProps({
    inscripcionId: [String, Number]
});

const { asistencias: asistenciasApi, inscripciones: inscripcionesApi } = useApi();

const asistencias = ref([]);
const inscripcion = ref(null);
const loading = ref(false);
const showCreateModal = ref(false);

// Filtros
const filtroEstado = ref('');
const filtroMes = ref('');
const vistaActual = ref('lista'); // 'lista' o 'calendario'

// Formulario para nueva asistencia
const formAsistencia = ref({
    inscripcion_id: props.inscripcionId,
    fecha: '',
    estado: 'presente',
    observaciones: ''
});

const cargarDatos = async () => {
    loading.value = true;

    try {
        // Cargar inscripción
        const respInscripcion = await inscripcionesApi.getById(props.inscripcionId);
        if (respInscripcion.success) {
            inscripcion.value = respInscripcion.data.data;
        }

        // Cargar asistencias
        const respAsistencias = await asistenciasApi.porInscripcion(props.inscripcionId);
        if (respAsistencias.success) {
            asistencias.value = respAsistencias.data.data;
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

// Asistencias filtradas
const asistenciasFiltradas = computed(() => {
    let resultado = [...asistencias.value];

    if (filtroEstado.value) {
        resultado = resultado.filter(a => a.estado === filtroEstado.value);
    }

    if (filtroMes.value) {
        resultado = resultado.filter(a => {
            const fecha = new Date(a.fecha);
            const mes = `${fecha.getFullYear()}-${String(fecha.getMonth() + 1).padStart(2, '0')}`;
            return mes === filtroMes.value;
        });
    }

    return resultado;
});

// Estadísticas
const estadisticas = computed(() => {
    const total = asistencias.value.length;
    const presente = asistencias.value.filter(a => a.estado === 'presente').length;
    const ausente = asistencias.value.filter(a => a.estado === 'ausente').length;
    const tardanza = asistencias.value.filter(a => a.estado === 'tardanza').length;
    const justificado = asistencias.value.filter(a => a.estado === 'justificado').length;
    const recuperada = asistencias.value.filter(a => a.estado === 'recuperada').length;

    const porcentajeAsistencia = total > 0 ? ((presente + tardanza + recuperada) / total * 100).toFixed(1) : 0;

    return {
        total,
        presente,
        ausente,
        tardanza,
        justificado,
        recuperada, // ← NUEVO
        porcentajeAsistencia
    };
});

// Obtener meses únicos de las asistencias
const mesesDisponibles = computed(() => {
    const meses = new Set();
    asistencias.value.forEach(a => {
        const fecha = new Date(a.fecha);
        const mes = `${fecha.getFullYear()}-${String(fecha.getMonth() + 1).padStart(2, '0')}`;
        meses.add(mes);
    });
    return Array.from(meses).sort().reverse();
});

// Obtener clase CSS para estado
const getEstadoClass = (estado) => {
    const classes = {
        'presente': 'bg-green-100 text-green-800 border-green-200',
        'ausente': 'bg-red-100 text-red-800 border-red-200',
        'tardanza': 'bg-yellow-100 text-yellow-800 border-yellow-200',
        'justificado': 'bg-blue-100 text-blue-800 border-blue-200',
        'recuperada': 'bg-purple-100 text-purple-800 border-purple-200' 
    };
    return classes[estado] || 'bg-gray-100 text-gray-800 border-gray-200';
};

// Obtener icono para estado
const getEstadoIcon = (estado) => {
    const icons = {
        'presente': 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'ausente': 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
        'tardanza': 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        'justificado': 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'recuperada': 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15'
    };
    return icons[estado] || '';
};


const getEstadoTexto = (estado) => {
    const textos = {
        'presente': 'Presente',
        'ausente': 'Ausente',
        'tardanza': 'Tardanza',
        'justificado': 'Justificado',
        'recuperada': 'Recuperada' // ← NUEVO
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

// Formatear fecha corta
const formatearFechaCorta = (fecha) => {
    return new Date(fecha).toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
};

// Abrir modal para crear asistencia
const abrirModalCrear = () => {
    formAsistencia.value = {
        inscripcion_id: props.inscripcionId,
        fecha: new Date().toISOString().split('T')[0],
        estado: 'presente',
        observaciones: ''
    };
    showCreateModal.value = true;
};

// Crear asistencia
const crearAsistencia = async () => {
    loading.value = true;
    try {
        const response = await asistenciasApi.create(formAsistencia.value);
        
        if (response.success) {
            showCreateModal.value = false;
            await cargarDatos();
        } else {
            alert(response.error || 'Error al crear asistencia');
        }
    } catch (error) {
        console.error("Error:", error);
        alert('Error al crear asistencia');
    } finally {
        loading.value = false;
    }
};

// Ver detalle de asistencia
const verDetalle = (asistenciaId) => {
    router.visit(`/asistencias/${asistenciaId}`);
};

// Limpiar filtros
const limpiarFiltros = () => {
    filtroEstado.value = '';
    filtroMes.value = '';
};

// Volver
const volver = () => {
    router.visit('/asistencias');
};
</script>

<template>
    <AppLayout>
        <Head title="Asistencias" />

        <div class="space-y-6">
            <!-- Header con info de inscripción -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-start justify-between">
                    <div class="flex items-start space-x-4">
                        <button
                            @click="volver"
                            class="mt-1 text-gray-400 hover:text-gray-600 transition"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <div v-if="inscripcion">
                            <h2 class="text-2xl font-bold text-gray-900">
                                Asistencias - {{ inscripcion.alumno_nombre }}
                            </h2>
                            <div class="mt-2 flex flex-wrap gap-4 text-sm text-gray-600">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    Tutor: {{ inscripcion.tutor_nombre }}
                                </div>
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                    {{ inscripcion.servicio_nombre }}
                                </div>
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    Desde: {{ formatearFechaCorta(inscripcion.fecha_inscripcion) }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <button
                        @click="abrirModalCrear"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Registrar Asistencia
                    </button>
                </div>
            </div>

            <!-- Estadísticas -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-gray-400">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase">Total</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ estadisticas.total }}</p>
                        </div>
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-green-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase">Presente</p>
                            <p class="text-2xl font-bold text-green-600 mt-1">{{ estadisticas.presente }}</p>
                        </div>
                        <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-red-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase">Ausente</p>
                            <p class="text-2xl font-bold text-red-600 mt-1">{{ estadisticas.ausente }}</p>
                        </div>
                        <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-yellow-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase">Tardanza</p>
                            <p class="text-2xl font-bold text-yellow-600 mt-1">{{ estadisticas.tardanza }}</p>
                        </div>
                        <svg class="w-8 h-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-purple-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase">Recuperada</p>
                            <p class="text-2xl font-bold text-purple-600 mt-1">{{ estadisticas.recuperada }}</p>
                        </div>
                        <svg class="w-8 h-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-indigo-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase">% Asistencia</p>
                            <p class="text-2xl font-bold text-indigo-600 mt-1">{{ estadisticas.porcentajeAsistencia }}%</p>
                        </div>
                        <svg class="w-8 h-8 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="bg-white rounded-lg shadow-sm p-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Filtrar por Estado</label>
                        <select
                            v-model="filtroEstado"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="">Todos</option>
                            <option value="presente">Presente</option>
                            <option value="ausente">Ausente</option>
                            <option value="tardanza">Tardanza</option>
                            <option value="justificado">Justificado</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Filtrar por Mes</label>
                        <select
                            v-model="filtroMes"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="">Todos</option>
                            <option v-for="mes in mesesDisponibles" :key="mes" :value="mes">
                                {{ new Date(mes + '-01').toLocaleDateString('es-ES', { month: 'long', year: 'numeric' }) }}
                            </option>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button
                            @click="limpiarFiltros"
                            class="w-full px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition"
                        >
                            Limpiar Filtros
                        </button>
                    </div>
                </div>
            </div>

            <!-- Lista de asistencias -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div v-if="loading" class="p-8 text-center">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                    <p class="mt-2 text-gray-600">Cargando asistencias...</p>
                </div>

                <div v-else-if="asistenciasFiltradas.length === 0" class="p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <p class="mt-2 text-gray-600">No hay asistencias registradas</p>
                    <button
                        @click="abrirModalCrear"
                        class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                    >
                        Registrar primera asistencia
                    </button>
                </div>

                <div v-else class="divide-y divide-gray-200">
                    <div
                        v-for="asistencia in asistenciasFiltradas"
                        :key="asistencia.id"
                        class="p-6 hover:bg-gray-50 transition cursor-pointer"
                        @click="verDetalle(asistencia.id)"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4 flex-1">
                                <!-- Icono de estado -->
                                <div :class="getEstadoClass(asistencia.estado)" class="p-3 rounded-full border-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getEstadoIcon(asistencia.estado)" />
                                    </svg>
                                </div>

                                <!-- Info -->
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3">
                                        <h3 class="text-lg font-semibold text-gray-900">
                                            {{ formatearFecha(asistencia.fecha) }}
                                        </h3>
                                        <span :class="getEstadoClass(asistencia.estado)" class="px-3 py-1 text-xs font-semibold rounded-full border">
                                            {{ getEstadoTexto(asistencia.estado) }}
                                        </span>
                                        <span v-if="asistencia.licencia_id" class="px-3 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800 border border-purple-200">
                                            <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            Con Licencia
                                        </span>
                                    </div>
                                    <p v-if="asistencia.observaciones" class="mt-1 text-sm text-gray-600">
                                        {{ asistencia.observaciones }}
                                    </p>
                                </div>
                            </div>

                            <!-- Flecha -->
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para crear asistencia -->
        <Teleport to="body">
            <div v-if="showCreateModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Registrar Asistencia</h3>
                        <button @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="crearAsistencia" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Fecha</label>
                            <input
                                v-model="formAsistencia.fecha"
                                type="date"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                            <select
                                v-model="formAsistencia.estado"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
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
                                v-model="formAsistencia.observaciones"
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                placeholder="Opcional..."
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
                                {{ loading ? 'Guardando...' : 'Guardar' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>