<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '../../Layout/AppLayout.vue';
import { useApi } from '../../composables/useApi';

const props = defineProps({
    ventaId: {
        type: [String, Number],
        required: true
    }
});

const { pagos: pagosApi, ventas: ventasApi } = useApi();

const venta = ref(null);
const pagos = ref([]);
const loading = ref(true);
const showModal = ref(false);
const procesando = ref(false);

const formPago = ref({
    monto: '',
    metodo_pago: 'efectivo',
    observaciones: ''
});

const montoTotal = computed(() => parseFloat(venta.value?.monto_total || 0));
const montoPagado = computed(() => parseFloat(venta.value?.monto_pagado || 0));
const saldoPendiente = computed(() => parseFloat(venta.value?.saldo_pendiente || 0));

const totalPagos = computed(() => {
    return pagos.value.reduce((sum, pago) => sum + parseFloat(pago.monto), 0);
});

const cargarDatos = async () => {
    loading.value = true;
    try {
        // Cargar venta
        const ventaResponse = await ventasApi.getById(props.ventaId);
        console.log('Venta Response:', ventaResponse);
        if (ventaResponse.success) {
            venta.value = ventaResponse.data.data;
        }

        // Cargar pagos de la venta
        const pagosResponse = await pagosApi.porVenta(props.ventaId);
        console.log('Pagos Response:', pagosResponse);
        if (pagosResponse.success) {
            pagos.value = pagosResponse.data.data;
        }
    } catch (error) {
        console.error('Error al cargar datos:', error);
        alert('Error al cargar los datos');
    } finally {
        loading.value = false;
    }
};

const abrirModal = () => {
    formPago.value = {
        monto: saldoPendiente.value.toFixed(2),
        metodo_pago: 'efectivo',
        observaciones: ''
    };
    showModal.value = true;
};

const cerrarModal = () => {
    showModal.value = false;
    formPago.value = {
        monto: '',
        metodo_pago: 'efectivo',
        observaciones: ''
    };
};

const registrarPago = async () => {
    if (!formPago.value.monto || parseFloat(formPago.value.monto) <= 0) {
        alert('El monto debe ser mayor a 0');
        return;
    }

    if (parseFloat(formPago.value.monto) > saldoPendiente.value) {
        alert('El monto no puede ser mayor al saldo pendiente');
        return;
    }

    procesando.value = true;
    try {
        const datos = {
            venta_id: props.ventaId,
            monto: parseFloat(formPago.value.monto),
            metodo_pago: formPago.value.metodo_pago,
            observaciones: formPago.value.observaciones
        };

        const response = await pagosApi.create(datos);
        
        if (response.success) {
            alert('Pago registrado exitosamente');
            cerrarModal();
            await cargarDatos();
        } else {
            alert('Error al registrar pago: ' + response.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al registrar el pago');
    } finally {
        procesando.value = false;
    }
};

const formatearFecha = (fecha) => {
    if (!fecha) return '-';
    return new Date(fecha).toLocaleDateString('es-BO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatearMoneda = (monto) => {
    return `Bs. ${parseFloat(monto).toFixed(2)}`;
};

const getEstadoBadge = (estado) => {
    const badges = {
        'pendiente': 'bg-yellow-100 text-yellow-800',
        'pagado': 'bg-green-100 text-green-800',
        'parcial': 'bg-blue-100 text-blue-800',
        'vencido': 'bg-red-100 text-red-800'
    };
    return badges[estado] || 'bg-gray-100 text-gray-800';
};

const getMetodoBadge = (metodo) => {
    const badges = {
        'efectivo': 'bg-green-100 text-green-800',
        'transferencia': 'bg-blue-100 text-blue-800',
        'qr': 'bg-purple-100 text-purple-800',
        'Completado': 'bg-indigo-100 text-indigo-800'
    };
    return badges[metodo] || 'bg-gray-100 text-gray-800';
};

const volver = () => {
    router.visit('ventas');
};

onMounted(() => {
    cargarDatos();
});
</script>

<template>
    <AppLayout>
        <Head title="Pagos de Venta" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6 flex justify-between items-center">
                    <div>
                        <button
                            @click="volver"
                            class="text-indigo-600 hover:text-indigo-700 mb-2 flex items-center"
                        >
                            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            Volver a Ventas
                        </button>
                        <h2 class="text-3xl font-bold text-gray-900">Pagos de Venta</h2>
                        <p class="text-gray-600 mt-1">Gestión de pagos y saldos</p>
                    </div>
                    <button
                        
                        @click="abrirModal"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg font-medium transition-colors duration-200 flex items-center"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Registrar Pago
                    </button>
                </div>

                <div v-if="loading" class="text-center py-12">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
                    <p class="mt-4 text-gray-600">Cargando...</p>
                </div>

                <div v-else>
                    <!-- Información de la Venta -->
                    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Información de la Venta</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <p class="text-sm text-gray-500">Alumno</p>
                                <p class="font-medium text-gray-900">{{ venta?.alumno_nombre }}</p>
                                <p class="text-sm text-gray-600">CI: {{ venta?.alumno_ci }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Mes/Año</p>
                                <p class="font-medium text-gray-900">{{ venta?.mes_correspondiente}}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Estado</p>
                                <span :class="getEstadoBadge(venta?.estado)" class="px-3 py-1 rounded-full text-sm font-medium">
                                    {{ venta?.estado }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Resumen Financiero -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
                            <p class="text-blue-100 text-sm font-medium">Monto Total</p>
                            <p class="text-3xl font-bold mt-2">{{ formatearMoneda(montoTotal) }}</p>
                        </div>
                        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
                            <p class="text-green-100 text-sm font-medium">Monto Pagado</p>
                            <p class="text-3xl font-bold mt-2">{{ formatearMoneda(montoPagado) }}</p>
                        </div>
                        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-lg shadow-lg p-6 text-white">
                            <p class="text-red-100 text-sm font-medium">Saldo Pendiente</p>
                            <p class="text-3xl font-bold mt-2">{{ formatearMoneda(saldoPendiente) }}</p>
                        </div>
                        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
                            <p class="text-purple-100 text-sm font-medium">Total de Pagos</p>
                            <p class="text-3xl font-bold mt-2">{{ pagos.length }}</p>
                        </div>
                    </div>

                    <!-- Tabla de Pagos -->
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Historial de Pagos</h3>
                        </div>

                        <div v-if="pagos.length === 0" class="text-center py-12">
                            <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="mt-4 text-gray-600">No hay pagos registrados</p>
                            <p class="text-sm text-gray-500 mt-1">Los pagos aparecerán aquí cuando se registren</p>
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Método</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Observaciones</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registrado por</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="pago in pagos" :key="pago.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ formatearFecha(pago.fecha_pago) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-lg font-semibold text-green-600">
                                                {{ formatearMoneda(pago.monto) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="getMetodoBadge(pago.metodo_pago)" class="px-3 py-1 rounded-full text-xs font-medium">
                                                {{ pago.metodo_pago }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            {{ pago.observaciones || '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {{ pago.registrado_por_nombre || 'Sistema' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Registrar Pago -->
        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg max-w-md w-full p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-900">Registrar Pago</h3>
                    <button @click="cerrarModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="registrarPago" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Monto a Pagar
                        </label>
                        <input
                            v-model="formPago.monto"
                            type="number"
                            step="0.01"
                            min="0.01"
                            :max="saldoPendiente"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            placeholder="0.00"
                        />
                        <p class="text-sm text-gray-500 mt-1">
                            Saldo pendiente: {{ formatearMoneda(saldoPendiente) }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Método de Pago
                        </label>
                        <select
                            v-model="formPago.metodo_pago"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia Bancaria</option>
                            <option value="qr">QR Manual</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Observaciones (Opcional)
                        </label>
                        <textarea
                            v-model="formPago.observaciones"
                            rows="3"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            placeholder="Detalles adicionales del pago..."
                        ></textarea>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button
                            type="button"
                            @click="cerrarModal"
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="procesando"
                            class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50"
                        >
                            {{ procesando ? 'Registrando...' : 'Registrar Pago' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>