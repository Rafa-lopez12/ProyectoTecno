<script setup>
import { ref, onMounted } from 'vue';
import { useApi } from '../../../composables/useApi';
import AlumnoForm from './AlumnoForm.vue';

const { alumnos: alumnosApi } = useApi();

const alumnos = ref([]);
const loading = ref(false);
const showDialog = ref(false);
const editingAlumno = ref(null);
const searchQuery = ref('');

const loadAlumnos = async () => {
    loading.value = true;
    const result = await alumnosApi.getAll({ search: searchQuery.value });
    if (result.success) {
        alumnos.value = result.data.data;
    }
    loading.value = false;
};

const openCreateDialog = () => {
    editingAlumno.value = null;
    showDialog.value = true;
};

const openEditDialog = (alumno) => {
    editingAlumno.value = { ...alumno };
    showDialog.value = true;
};

const closeDialog = () => {
    showDialog.value = false;
    editingAlumno.value = null;
};

const handleSaved = () => {
    closeDialog();
    loadAlumnos();
};

const deleteAlumno = async (id) => {
    if (!confirm('¿Estás seguro de eliminar este alumno?')) return;
    
    const result = await alumnosApi.delete(id);
    if (result.success) {
        loadAlumnos();
    } else {
        alert('Error al eliminar el alumno');
    }
};

onMounted(() => {
    loadAlumnos();
});
</script>

<template>
    <div>
        <!-- Header con búsqueda y botón crear -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex-1 max-w-md">
                <input
                    v-model="searchQuery"
                    @input="loadAlumnos"
                    type="text"
                    placeholder="Buscar por nombre o apellido..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                />
            </div>
            <button
                @click="openCreateDialog"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nuevo Alumno
            </button>
        </div>

        <!-- Tabla -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nombre
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Teléfono
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Grado Escolar
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Fecha Ingreso
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
                        <tr v-if="loading">
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                Cargando...
                            </td>
                        </tr>
                        <tr v-else-if="alumnos.length === 0">
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                No hay alumnos registrados
                            </td>
                        </tr>
                        <tr v-else v-for="alumno in alumnos" :key="alumno.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ alumno.nombre }} {{ alumno.apellido }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ alumno.telefono || '-' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ alumno.grado_escolar || '-' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ alumno.fecha_ingreso || '-' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="[
                                    'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                                    alumno.estado === 'activo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                ]">
                                    {{ alumno.estado }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button
                                    @click="openEditDialog(alumno)"
                                    class="text-indigo-600 hover:text-indigo-900 mr-4"
                                >
                                    Editar
                                </button>
                                <button
                                    @click="deleteAlumno(alumno.id)"
                                    class="text-red-600 hover:text-red-900"
                                >
                                    Eliminar
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Dialog -->
        <Teleport to="body">
            <div
                v-if="showDialog"
                class="fixed inset-0 z-50 overflow-y-auto"
                aria-labelledby="modal-title"
                role="dialog"
                aria-modal="true"
            >
                <!-- Overlay -->
                <div
                    class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                    @click="closeDialog"
                ></div>

                <!-- Modal Container -->
                <div class="flex min-h-screen items-center justify-center p-4">
                    <!-- Modal -->
                    <div class="relative bg-white rounded-lg shadow-xl max-w-lg w-full">
                        <AlumnoForm
                            :alumno="editingAlumno"
                            @saved="handleSaved"
                            @cancel="closeDialog"
                        />
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>