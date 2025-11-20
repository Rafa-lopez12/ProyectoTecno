<script setup>
import { ref, watch } from 'vue';
import { useApi } from '../../../composables/useApi';

const props = defineProps({
    tutor: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['saved', 'cancel']);

const { tutores: tutoresApi } = useApi();

const form = ref({
    nombre: '',
    apellido: '',
    telefono: '',
    fecha_nacimiento: '',
    direccion: '',
    email: '',
    password: '',
    grado: '',
    rol: 'tutor',
    estado: 'activo'
});

const loading = ref(false);
const errors = ref({});

const resetForm = () => {
    form.value = {
        nombre: '',
        apellido: '',
        telefono: '',
        fecha_nacimiento: '',
        direccion: '',
        email: '',
        password: '',
        grado: '',
        rol: 'tutor',
        estado: 'activo'
    };
    errors.value = {};
};

// Cargar datos si está editando
watch(() => props.tutor, (newVal) => {
    if (newVal) {
        form.value = {
            nombre: newVal.nombre || '',
            apellido: newVal.apellido || '',
            telefono: newVal.telefono || '',
            fecha_nacimiento: newVal.fecha_nacimiento || '',
            direccion: newVal.direccion || '',
            email: newVal.email || '',
            password: '',
            grado: newVal.grado || '',
            rol: newVal.rol || 'tutor',
            estado: newVal.estado || 'activo'
        };
    } else {
        resetForm();
    }
}, { immediate: true });

const handleSubmit = async () => {
    loading.value = true;
    errors.value = {};

    const result = props.tutor 
        ? await tutoresApi.update(props.tutor.id, form.value)
        : await tutoresApi.create(form.value);

    if (result.success) {
        emit('saved');
    } else {
        errors.value = result.errors;
        if (!Object.keys(errors.value).length) {
            alert('Error al guardar el tutor');
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
                {{ tutor ? 'Editar Tutor' : 'Nuevo Tutor' }}
            </h3>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleSubmit" class="px-4 py-5 sm:p-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
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

                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Email <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.email"
                        type="email"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                        :class="{ 'border-red-500': errors.email }"
                    />
                    <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email[0] }}</p>
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Contraseña {{ tutor ? '' : '*' }}
                    </label>
                    <input
                        v-model="form.password"
                        type="password"
                        :required="!tutor"
                        placeholder="********"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                        :class="{ 'border-red-500': errors.password }"
                    />
                    <p v-if="errors.password" class="mt-1 text-sm text-red-600">{{ errors.password[0] }}</p>
                    <p v-if="tutor" class="mt-1 text-xs text-gray-500">Dejar en blanco para mantener la actual</p>
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

                <!-- Rol -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Tipo de Tutor
                    </label>
                    <select
                        v-model="form.rol"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        <option value="tutor">Tutor</option>

                    </select>
                </div>

                <!-- Grado -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Grado/Especialidad
                    </label>
                    <input
                        v-model="form.grado"
                        type="text"
                        placeholder="ej: Licenciatura en Educación"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                    />
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