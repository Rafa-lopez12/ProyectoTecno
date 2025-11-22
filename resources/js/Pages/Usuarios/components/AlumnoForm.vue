<script setup>
import { ref, watch } from 'vue';
import { useApi } from '../../../composables/useApi';

const props = defineProps({
    alumno: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['saved', 'cancel']);

const { alumnos: alumnosApi } = useApi();

const form = ref({
    ci: '',
    nombre: '',
    apellido: '',
    telefono: '',
    fecha_nacimiento: '',
    direccion: '',
    grado_escolar: '',
    fecha_ingreso: '',
    estado: 'activo'
});

const loading = ref(false);
const errors = ref({});

const resetForm = () => {
    form.value = {
        ci: '',
        nombre: '',
        apellido: '',
        telefono: '',
        fecha_nacimiento: '',
        direccion: '',
        grado_escolar: '',
        fecha_ingreso: '',
        estado: 'activo'
    };
    errors.value = {};
};

watch(() => props.alumno, (newVal) => {
    if (newVal) {
        form.value = {
            ci: newVal.ci || '',
            nombre: newVal.nombre || '',
            apellido: newVal.apellido || '',
            telefono: newVal.telefono || '',
            fecha_nacimiento: newVal.fecha_nacimiento || '',
            direccion: newVal.direccion || '',
            grado_escolar: newVal.grado_escolar || '',
            fecha_ingreso: newVal.fecha_ingreso || '',
            estado: newVal.estado || 'activo'
        };
    } else {
        resetForm();
    }
}, { immediate: true });

const handleSubmit = async () => {
    loading.value = true;
    errors.value = {};
    console.log(form.value)
    const result = props.alumno 
        ? await alumnosApi.update(props.alumno.id, form.value)
        : await alumnosApi.create(form.value);

    if (result.success) {
        emit('saved');
    } else {
        errors.value = result.errors;
        if (!Object.keys(errors.value).length) {
            alert('Error al guardar el alumno');
        }
    }
    
    loading.value = false;
};

const handleCancel = () => {
    resetForm();
    emit('cancel');
};
</script>

<template>
    <div>
        <!-- Header -->
        <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">
                {{ alumno ? 'Editar Alumno' : 'Nuevo Alumno' }}
            </h3>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleSubmit" class="px-4 py-5 sm:p-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <!-- CI -->
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        CI (Cédula de Identidad) <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.ci"
                        type="text"
                        required
                        placeholder="Ej: 12345678"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                        :class="{ 'border-red-500': errors.ci }"
                    />
                    <p v-if="errors.ci" class="mt-1 text-sm text-red-600">{{ errors.ci[0] }}</p>

                </div>

                <!-- Nombre -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Nombre <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.nombre"
                        type="text"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                        :class="{ 'border-red-500': errors.nombre }"
                    />
                    <p v-if="errors.nombre" class="mt-1 text-sm text-red-600">{{ errors.nombre[0] }}</p>
                </div>

                <!-- Apellido -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Apellido <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.apellido"
                        type="text"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                        :class="{ 'border-red-500': errors.apellido }"
                    />
                    <p v-if="errors.apellido" class="mt-1 text-sm text-red-600">{{ errors.apellido[0] }}</p>
                </div>

                <!-- Teléfono -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Teléfono
                    </label>
                    <input
                        v-model="form.telefono"
                        type="text"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                    />
                </div>

                <!-- Fecha de Nacimiento -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Fecha de Nacimiento
                    </label>
                    <input
                        v-model="form.fecha_nacimiento"
                        type="date"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                    />
                </div>

                <!-- Grado Escolar -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Grado Escolar
                    </label>
                    <input
                        v-model="form.grado_escolar"
                        type="text"
                        placeholder="ej: 1ro Primaria, 5to Secundaria"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                    />
                </div>

                <!-- Fecha de Ingreso -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Fecha de Ingreso <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.fecha_ingreso"
                        type="date"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                        :class="{ 'border-red-500': errors.fecha_ingreso }"
                    />
                    <p v-if="errors.fecha_ingreso" class="mt-1 text-sm text-red-600">{{ errors.fecha_ingreso[0] }}</p>
                </div>

                <!-- Estado -->
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Estado
                    </label>
                    <select
                        v-model="form.estado"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                        <option value="suspendido">Suspendido</option>
                    </select>
                </div>

                <!-- Dirección -->
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Dirección
                    </label>
                    <textarea
                        v-model="form.direccion"
                        rows="2"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                    ></textarea>
                </div>
            </div>

            <!-- Botones -->
            <div class="mt-6 flex justify-end gap-3">
                <button
                    type="button"
                    @click="handleCancel"
                    class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                >
                    Cancelar
                </button>
                <button
                    type="submit"
                    :disabled="loading"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50"
                >
                    {{ loading ? 'Guardando...' : 'Guardar' }}
                </button>
            </div>
        </form>
    </div>
</template>