<script setup>
import { ref, watch, computed } from 'vue';
import { useApi } from '../../../composables/useApi';
import { useFormValidation } from '../../../composables/useFormValidation';

const props = defineProps({
    alumno: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['saved', 'cancel']);

const { alumnos: alumnosApi } = useApi();
const { errors, rules, validateField, validateForm, clearErrors, setBackendErrors } = useFormValidation();

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

// Reglas de validación para cada campo
const validationRules = {
    ci: [
        (value) => rules.required(value, 'CI'),
        (value) => rules.numeric(value, 'CI'),
        (value) => rules.noSpecialChars(value, 'CI'),
    ],
    nombre: [
        (value) => rules.required(value, 'nombre'),
        (value) => rules.string(value, 'nomobre'),
        (value) => rules.noSpecialChars(value, 'nombre'),
        (value) => rules.onlyLetters(value, 'nombre'),
    ],
    apellido: [
        (value) => rules.required(value, 'apellido'),
        (value) => rules.string(value, 'apellido'),
        (value) => rules.noSpecialChars(value, 'apellido'),
        (value) => rules.onlyLetters(value, 'apellido'),
    ],
    telefono: [
        (value) => rules.required(value, 'telefono'),
        (value) => rules.numeric(value, 'telefono'),
        (value) => rules.noSpecialChars(value, 'telefono'),
        (value) => rules.onlyNumbers(value, 'telefono'),
    ],
    fecha_nacimiento: [
        (value) => rules.required(value, 'fecha de nacimiento'),
        (value) => value ? rules.date(value, 'fecha de nacimiento') : null,
    ],
    grado_escolar: [
       (value) => rules.required(value, 'grado escolar'),
       (value) => rules.alphanumeric(value, 'grado escolar'),
    ],
    fecha_ingreso: [
        (value) => rules.required(value, 'fecha de ingreso'),
        (value) => value ? rules.date(value, 'fecha de ingreso') : null,
    ]
};

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
    clearErrors();
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
        clearErrors();
    } else {
        resetForm();
    }
}, { immediate: true });

// Validar campo individual cuando pierde el foco
const handleBlur = (fieldName) => {
    if (validationRules[fieldName]) {
        validateField(fieldName, form.value[fieldName], validationRules[fieldName]);
    }
};

// Limpiar error cuando el usuario empieza a escribir
const handleInput = (fieldName) => {
    if (errors.value[fieldName]) {
        delete errors.value[fieldName];
    }
};

const handleSubmit = async () => {
    // Validar formulario
    if (!validateForm(form.value, validationRules)) {
        return;
    }

    loading.value = true;

    const result = props.alumno 
        ? await alumnosApi.update(props.alumno.id, form.value)
        : await alumnosApi.create(form.value);

    if (result.success) {
        emit('saved');
    } else {
        // Establecer errores del backend
        if (result.errors) {
            setBackendErrors(result.errors);
        } else {
            alert('Error al guardar el alumno. Por favor, intente nuevamente.');
        }
    }
    
    loading.value = false;
};

const handleCancel = () => {
    resetForm();
    emit('cancel');
};

// Computed para verificar si hay errores
const hasErrors = computed(() => Object.keys(errors.value).length > 0);
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
                        placeholder="Ej: 12345678"
                        @blur="handleBlur('ci')"
                        @input="handleInput('ci')"
                        class="w-full px-3 py-2 border rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition"
                        :class="{ 
                            'border-red-500 focus:border-red-500 focus:ring-red-500': errors.ci,
                            'border-gray-300': !errors.ci
                        }"
                    />
                    <p v-if="errors.ci" class="mt-1 text-sm text-red-600 flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        {{ errors.ci }}
                    </p>
                </div>

                <!-- Nombre -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Nombre <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.nombre"
                        type="text"
                        @blur="handleBlur('nombre')"
                        @input="handleInput('nombre')"
                        class="w-full px-3 py-2 border rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition"
                        :class="{ 
                            'border-red-500 focus:border-red-500 focus:ring-red-500': errors.nombre,
                            'border-gray-300': !errors.nombre
                        }"
                    />
                    <p v-if="errors.nombre" class="mt-1 text-sm text-red-600 flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        {{ errors.nombre }}
                    </p>
                </div>

                <!-- Apellido -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Apellido <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.apellido"
                        type="text"
                        @blur="handleBlur('apellido')"
                        @input="handleInput('apellido')"
                        class="w-full px-3 py-2 border rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition"
                        :class="{ 
                            'border-red-500 focus:border-red-500 focus:ring-red-500': errors.apellido,
                            'border-gray-300': !errors.apellido
                        }"
                    />
                    <p v-if="errors.apellido" class="mt-1 text-sm text-red-600 flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        {{ errors.apellido }}
                    </p>
                </div>

                <!-- Teléfono -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Teléfono
                    </label>
                    <input
                        v-model="form.telefono"
                        type="text"
                        placeholder="Ej: 78451234"
                        @blur="handleBlur('telefono')"
                        @input="handleInput('telefono')"
                        class="w-full px-3 py-2 border rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition"
                        :class="{ 
                            'border-red-500 focus:border-red-500 focus:ring-red-500': errors.telefono,
                            'border-gray-300': !errors.telefono
                        }"
                    />
                    <p v-if="errors.telefono" class="mt-1 text-sm text-red-600 flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        {{ errors.telefono }}
                    </p>
                </div>

                <!-- Fecha de Nacimiento -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Fecha de Nacimiento
                    </label>
                    <input
                        v-model="form.fecha_nacimiento"
                        type="date"
                        @blur="handleBlur('fecha_nacimiento')"
                        @input="handleInput('fecha_nacimiento')"
                        class="w-full px-3 py-2 border rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition"
                        :class="{ 
                            'border-red-500 focus:border-red-500 focus:ring-red-500': errors.fecha_nacimiento,
                            'border-gray-300': !errors.fecha_nacimiento
                        }"
                    />
                    <p v-if="errors.fecha_nacimiento" class="mt-1 text-sm text-red-600 flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        {{ errors.fecha_nacimiento }}
                    </p>
                </div>

                <!-- Grado Escolar -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Grado Escolar
                    </label>
                    <input
                        v-model="form.grado_escolar"
                        type="text"
                        placeholder="Ej: 1ro Primaria, 5to Secundaria"
                        @blur="handleBlur('grado_escolar')"
                        @input="handleInput('grado_escolar')"
                        class="w-full px-3 py-2 border rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition"
                        :class="{ 
                            'border-red-500 focus:border-red-500 focus:ring-red-500': errors.grado_escolar,
                            'border-gray-300': !errors.grado_escolar
                        }"
                    />
                    <p v-if="errors.grado_escolar" class="mt-1 text-sm text-red-600 flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        {{ errors.grado_escolar }}
                    </p>
                </div>

                <!-- Fecha de Ingreso -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Fecha de Ingreso <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.fecha_ingreso"
                        type="date"
                        @blur="handleBlur('fecha_ingreso')"
                        @input="handleInput('fecha_ingreso')"
                        class="w-full px-3 py-2 border rounded-md focus:ring-indigo-500 focus:border-indigo-500 transition"
                        :class="{ 
                            'border-red-500 focus:border-red-500 focus:ring-red-500': errors.fecha_ingreso,
                            'border-gray-300': !errors.fecha_ingreso
                        }"
                    />
                    <p v-if="errors.fecha_ingreso" class="mt-1 text-sm text-red-600 flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        {{ errors.fecha_ingreso }}
                    </p>
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

            <!-- Mensaje de error general -->
            <div v-if="hasErrors" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-md">
                <p class="text-sm text-red-800 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                    Por favor, corrija los errores antes de continuar
                </p>
            </div>

            <!-- Botones -->
            <div class="mt-6 flex justify-end gap-3">
                <button
                    type="button"
                    @click="handleCancel"
                    class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition"
                >
                    Cancelar
                </button>
                <button
                    type="submit"
                    :disabled="loading"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
                >
                    <span v-if="loading" class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Guardando...
                    </span>
                    <span v-else>Guardar</span>
                </button>
            </div>
        </form>
    </div>
</template>