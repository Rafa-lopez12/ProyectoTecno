<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '../../Layout/AppLayout.vue';
import { useApi } from '../../composables/useApi';
import { useAuth } from '../../composables/useAuth';
import { useFormValidation } from '../../composables/useFormValidation';

const { inscripciones: inscripcionesApi, alumnos, tutores, servicios, horarios: horariosApi } = useApi();
const { errors, rules, validateField, validateForm, clearErrors, setBackendErrors } = useFormValidation();
const { user } = useAuth();

const alumnosList = ref([]);
const tutoresList = ref([]);
const serviciosList = ref([]);
const horariosDisponibles = ref([]);
const loading = ref(false);

const form = ref({
    alumno_id: '',
    tutor_id: '',
    id_servicio: '',
    horarios: [],
    fecha_inscripcion: new Date().toISOString().split('T')[0],
    estado: 'activo',
    observaciones: '',
    crear_venta: true,
    propietario_id: user.value?.id || null,
    tipo_venta: 'contado',
    monto_total: 0,
    monto_pagado: 0,
    mes_correspondiente: new Date().toLocaleString('es-ES', { month: 'long', year: 'numeric' }),
    fecha_venta: new Date().toISOString().split('T')[0],
    fecha_vencimiento: null,
});


const validationRules = {
    monto_total: [
        (value) => rules.required(value, 'monto_total'),
        (value) => rules.integer(value, 'monto_total'),
        (value) => rules.noSpecialChars(value, 'monto_total'),
        
    ],
    id_servicio: [
        (value) => rules.required(value, 'servicio'),
    ]
};


const handleBlur = (fieldName) => {
    if (validationRules[fieldName]) {
        validateField(fieldName, form.value[fieldName], validationRules[fieldName]);
    }
};

const handleInput = (fieldName) => {
    if (errors.value[fieldName]) {
        delete errors.value[fieldName];
    }
};

const saldoPendiente = computed(() => {
    return form.value.monto_total - form.value.monto_pagado;
});

const cargarDatos = async () => {
    const [resAlumnos, resTutores, resServicios] = await Promise.all([
        alumnos.getAll(),
        tutores.getAll(),
        servicios.getAll()
    ]);

    if (resAlumnos.success) alumnosList.value = resAlumnos.data.data;
    if (resTutores.success) tutoresList.value = resTutores.data.data;
    if (resServicios.success) serviciosList.value = resServicios.data.data;
};

// Cargar horarios cuando se selecciona un tutor
watch(() => form.value.tutor_id, async (tutorId) => {
    form.value.horarios = [];
    horariosDisponibles.value = [];
    
    if (tutorId) {
        const response = await horariosApi.obtenerHorariosDeTutor(tutorId);
        if (response.success) {
            horariosDisponibles.value = response.data.data;
        }
    }
});

// Toggle selección de horario
const toggleHorario = (horario) => {
    const horarioData = {
        dia: horario.dia_semana,
        hora_inicio: horario.hora_inicio,
        hora_fin: horario.hora_fin
    };
    
    const index = form.value.horarios.findIndex(h => 
        h.dia === horarioData.dia && 
        h.hora_inicio === horarioData.hora_inicio
    );
    
    if (index > -1) {
        form.value.horarios.splice(index, 1);
    } else {
        form.value.horarios.push(horarioData);
    }
};

// Verificar si un horario está seleccionado
const isHorarioSelected = (horario) => {
    return form.value.horarios.some(h => 
        h.dia === horario.dia_semana && 
        h.hora_inicio === horario.hora_inicio
    );
};

const handleSubmit = async () => {
    if (form.value.horarios.length === 0) {
        alert('Debes seleccionar al menos un horario');
        return;
    }
    
    loading.value = true;
    
    const result = await inscripcionesApi.create(form.value);
    
    if (result.success) {
        router.visit('inscripciones');
    } else {
        alert('Error al crear inscripción: ' + (result.error || 'Error desconocido'));
    }
    
    loading.value = false;
};

onMounted(() => {
    cargarDatos();
});
</script>

<template>
    <AppLayout>
        <Head title="Nueva Inscripción" />

        <div class="max-w-5xl mx-auto">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex items-center gap-2 text-sm text-gray-600 mb-2">
                    <button @click="router.visit('inscripciones')" class="hover:text-indigo-600">
                        Inscripciones
                    </button>
                    <span>/</span>
                    <span class="text-gray-900">Nueva</span>
                </div>
                <h1 class="text-3xl font-bold text-gray-900">Nueva Inscripción</h1>
                <p class="text-gray-600 mt-1">Complete los datos para registrar la inscripción</p>
            </div>

            <form @submit.prevent="handleSubmit" class="space-y-6">
                <!-- Card: Formulario completo -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-4">
                        <h2 class="text-lg font-semibold text-white flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Datos de Inscripción
                        </h2>
                    </div>

                    <div class="p-6 space-y-6">
                        <!-- Sección: Datos principales -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Alumno <span class="text-red-500">*</span>
                                </label>
                                <select v-model="form.alumno_id" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                                    <option value="">Seleccione un alumno</option>
                                    <option v-for="alumno in alumnosList" :key="alumno.id" :value="alumno.id">
                                        {{ alumno.nombre }} {{ alumno.apellido }} - CI: {{ alumno.ci }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Tutor <span class="text-red-500">*</span>
                                </label>
                                <select v-model="form.tutor_id" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                                    <option value="">Seleccione un tutor</option>
                                    <option v-for="tutor in tutoresList" :key="tutor.id" :value="tutor.id">
                                        {{ tutor.nombre }} {{ tutor.apellido }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Servicio <span class="text-red-500">*</span>
                                </label>
                                <select v-model="form.id_servicio" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                                    <option value="">Seleccione un servicio</option>
                                    <option v-for="servicio in serviciosList" :key="servicio.id" :value="servicio.id">
                                        {{ servicio.nombre }}
                                    </option>
                                </select>
                                <p v-if="errors.id_servicio" class="mt-1 text-sm text-red-600 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    {{ errors.id_servicio }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Inscripción</label>
                                <input v-model="form.fecha_inscripcion" type="date" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                            </div>
                        </div>

                        <!-- Horarios Disponibles -->
                        <div v-if="form.tutor_id" class="border-t pt-6">
                            <label class="block text-sm font-medium text-gray-700 mb-3">
                                Horarios del Tutor <span class="text-red-500">*</span>
                            </label>
                            
                            <div v-if="horariosDisponibles.length === 0" class="text-center py-8 bg-gray-50 rounded-lg">
                                <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="mt-2 text-gray-600">Este tutor no tiene horarios asignados</p>
                            </div>

                            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                <div
                                    v-for="horario in horariosDisponibles"
                                    :key="horario.id"
                                    @click="toggleHorario(horario)"
                                    class="border rounded-lg p-4 cursor-pointer transition-all"
                                    :class="isHorarioSelected(horario) 
                                        ? 'border-indigo-500 bg-indigo-50 shadow-md' 
                                        : 'border-gray-300 hover:border-indigo-300 hover:shadow'"
                                >
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center mb-2">
                                                <svg class="w-5 h-5 mr-2" :class="isHorarioSelected(horario) ? 'text-indigo-600' : 'text-gray-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                <span class="font-semibold" :class="isHorarioSelected(horario) ? 'text-indigo-900' : 'text-gray-900'">
                                                    {{ horario.dia_semana }}
                                                </span>
                                            </div>
                                            <div class="flex items-center text-sm" :class="isHorarioSelected(horario) ? 'text-indigo-700' : 'text-gray-600'">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                {{ horario.hora_inicio }} - {{ horario.hora_fin }}
                                            </div>
                                        </div>
                                        <div v-if="isHorarioSelected(horario)" class="ml-2">
                                            <svg class="w-6 h-6 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="form.horarios.length > 0" class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                                <p class="text-sm font-medium text-green-800">
                                    {{ form.horarios.length }} horario(s) seleccionado(s)
                                </p>
                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Observaciones</label>
                            <textarea v-model="form.observaciones" rows="3" placeholder="Añade notas o comentarios adicionales..." class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"></textarea>
                        </div>

                        <!-- Divider -->
                        <div class="relative">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-200"></div>
                            </div>
                            <div class="relative flex justify-center">
                                <label class="bg-white px-4 py-2 rounded-full border border-gray-200 flex items-center cursor-pointer hover:bg-gray-50 transition">
                                    <input v-model="form.crear_venta" type="checkbox" class="mr-2">
                                    <span class="text-sm font-medium text-gray-700">Registrar pago</span>
                                </label>
                            </div>
                        </div>

                      
                        <div v-if="form.crear_venta" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Tipo de Venta <span class="text-red-500">*</span>
                                    </label>
                                    <select v-model="form.tipo_venta" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                                        <option value="contado">💵 Contado</option>
                                        <option value="credito">📅 Crédito</option>
                                    </select>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Mes Correspondiente</label>
                                    <input v-model="form.mes_correspondiente" type="text" placeholder="Enero 2024" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Monto Total <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">Bs.</span>
                                        <input v-model.number="form.monto_total" type="number" @blur="handleBlur('monto_total')" @input="handleInput('monto_total')" step="0.01" min="0" class="w-full pl-12 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                                        <p v-if="errors.monto_total" class="mt-1 text-sm text-red-600 flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                            {{ errors.monto_total }}
                                        </p>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Monto Pagado</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">Bs.</span>
                                        <input v-model.number="form.monto_pagado" type="number" step="0.01" min="0" :max="form.monto_total" class="w-full pl-12 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Saldo Pendiente</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">Bs.</span>
                                        <input :value="saldoPendiente.toFixed(2)" type="text" disabled class="w-full pl-12 pr-4 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-gray-700 font-semibold">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Venta</label>
                                    <input v-model="form.fecha_venta" type="date" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                                </div>

                                <div v-if="form.tipo_venta === 'credito'" class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Fecha de Vencimiento <span class="text-red-500">*</span>
                                    </label>
                                    <input v-model="form.fecha_vencimiento" type="date" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                                </div>
                            </div>

                            <div v-if="saldoPendiente > 0" class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-amber-600 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    <div>
                                        <p class="text-sm font-medium text-amber-800">
                                            Hay un saldo pendiente de <strong>Bs. {{ saldoPendiente.toFixed(2) }}</strong>
                                        </p>
                                        <p class="text-xs text-amber-700 mt-1">
                                            Esta venta quedará con estado "pendiente" hasta completar el pago.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div class="flex items-center justify-between bg-gray-50 rounded-xl p-6 border border-gray-200">
                    <button type="button" @click="router.visit('inscripciones')" class="px-6 py-2.5 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition font-medium">
                        Cancelar
                    </button>
                    <button type="submit" :disabled="loading" class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center">
                        <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ loading ? 'Guardando...' : 'Guardar Inscripción' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>