<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AlumnoLayout from '../../Layout/AlumnoLayout.vue';
import { useApi } from '../../composables/useApi';

const { ventas: ventasApi, pagos: pagosApi } = useApi();

const ventas = ref([]);
const loading = ref(true);
const generandoQR = ref(false);
const showQRModal = ref(false);
const showMontoModal = ref(false); // Nuevo modal para monto
const qrData = ref(null);
const ventaSeleccionada = ref(null);

// Formulario para monto a pagar
const formMonto = ref({
    monto: 0,
    email: ''
});

const cargarVentas = async () => {
    loading.value = true;
    try {
        const response = await ventasApi.misVentas();
        if (response.success) {
            console.log(response)
            ventas.value = response.data.data;
        }
    } catch (error) {
        console.error('Error al cargar ventas:', error);
        alert('Error al cargar las ventas');
    } finally {
        loading.value = false;
    }
};

const formatearMoneda = (monto) => {
    return `Bs. ${parseFloat(monto).toFixed(2)}`;
};

const formatearFecha = (fecha) => {
    return new Date(fecha).toLocaleDateString('es-BO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const getEstadoBadge = (estado) => {
    const badges = {
        'pendiente': 'bg-yellow-100 text-yellow-800',
        'pagado': 'bg-green-100 text-green-800',
        'parcial': 'bg-blue-100 text-blue-800'
    };
    return badges[estado] || 'bg-gray-100 text-gray-800';
};

// Abrir modal para ingresar monto
const abrirModalMonto = (venta) => {
    ventaSeleccionada.value = venta;
    formMonto.value = {
        monto: parseFloat(venta.saldo_pendiente),
        email: ''
    };
    showMontoModal.value = true;
};

// Cerrar modal de monto
const cerrarModalMonto = () => {
    showMontoModal.value = false;
    ventaSeleccionada.value = null;
    formMonto.value = {
        monto: 0,
        email: ''
    };
};

// Generar QR con el monto especificado
const generarQR = async () => {
    if (!formMonto.value.monto || formMonto.value.monto <= 0) {
        alert('El monto debe ser mayor a 0');
        return;
    }

    if (formMonto.value.monto > parseFloat(ventaSeleccionada.value.saldo_pendiente)) {
        alert('El monto no puede ser mayor al saldo pendiente');
        return;
    }

    generandoQR.value = true;

    try {
        const datos = {
            venta_id: ventaSeleccionada.value.id,
            monto: parseFloat(formMonto.value.monto),
            email: formMonto.value.email || ''
        };

        const response = await pagosApi.generarQR(datos);

        if (response.success) {
            qrData.value = response.data.data;
            showMontoModal.value = false;
            showQRModal.value = true;
            await cargarVentas();
        } else {
            alert('Error al generar QR: ' + (response.message || 'Error desconocido'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al generar QR de pago');
    } finally {
        generandoQR.value = false;
    }
};

const cerrarQRModal = () => {
    showQRModal.value = false;
    qrData.value = null;
};

const descargarQR = () => {
    if (!qrData.value?.qrBase64) return;

    const link = document.createElement('a');
    link.href = `data:image/png;base64,${qrData.value.qrBase64}`;
    link.download = `QR-Pago-${qrData.value.transactionId}.png`;
    link.click();
};

const verificarPago = async () => {
    if (!qrData.value?.transactionId) return;

    try {
        // Buscar el pago por company_transaction_id
        const response = await pagosApi.consultarEstado(qrData.value.transactionId);
        console.log(response)
        if (response.success && response.data.data.paymentStatus === 2) {
            alert('¡Pago confirmado exitosamente!');
            cerrarQRModal();
            await cargarVentas();
        } else {
            alert('El pago aún no ha sido confirmado. Por favor, intenta nuevamente en unos momentos.');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al verificar el pago');
    }
};

onMounted(() => {
    cargarVentas();
});
</script>

<template>
    <AlumnoLayout>
        <Head title="Mis Ventas" />

        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Mis Ventas</h1>
            <p class="text-gray-600 mt-2">Gestiona tus pagos y revisa tu historial</p>
        </div>

        <div v-if="loading" class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
            <p class="mt-4 text-gray-600">Cargando ventas...</p>
        </div>

        <div v-else-if="ventas.length === 0" class="text-center py-12 bg-white rounded-lg shadow">
            <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="mt-4 text-gray-600">No tienes ventas registradas</p>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div 
                v-for="venta in ventas" 
                :key="venta.id"
                class="bg-white rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300 overflow-hidden"
            >
                <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-4">
                    <h3 class="text-white font-semibold text-lg">{{ venta.servicio_nombre }}</h3>
                    <p class="text-indigo-100 text-sm">{{ venta.mes_correspondiente }}</p>
                </div>

                <div class="p-6 space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Monto Total:</span>
                        <span class="text-xl font-bold text-gray-900">{{ formatearMoneda(venta.monto_total) }}</span>
                    </div>

                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Pagado:</span>
                        <span class="text-lg font-semibold text-green-600">{{ formatearMoneda(venta.monto_pagado) }}</span>
                    </div>

                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Saldo Pendiente:</span>
                        <span class="text-lg font-semibold text-red-600">{{ formatearMoneda(venta.saldo_pendiente) }}</span>
                    </div>

                    <div class="pt-4 border-t border-gray-200">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm text-gray-500">Tipo:</span>
                            <span class="text-sm font-medium text-gray-900">{{ venta.tipo_venta }}</span>
                        </div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm text-gray-500">Fecha:</span>
                            <span class="text-sm font-medium text-gray-900">{{ formatearFecha(venta.fecha_venta) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-500">Estado:</span>
                            <span :class="getEstadoBadge(venta.estado)" class="px-3 py-1 rounded-full text-xs font-medium">
                                {{ venta.estado }}
                            </span>
                        </div>
                    </div>

                    <button
                        v-if="parseFloat(venta.saldo_pendiente) > 0 && venta.estado !== 'pagado'"
                        @click="abrirModalMonto(venta)"
                        class="w-full mt-4 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold py-3 px-4 rounded-lg transition-all duration-300 flex items-center justify-center"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                        </svg>
                        Pagar con QR
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal para Especificar Monto -->
        <div v-if="showMontoModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg max-w-md w-full p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-900">Especificar Monto a Pagar</h3>
                    <button @click="cerrarModalMonto" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600">Monto Total:</span>
                        <span class="font-semibold">{{ formatearMoneda(ventaSeleccionada?.monto_total) }}</span>
                    </div>
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600">Ya Pagado:</span>
                        <span class="font-semibold text-green-600">{{ formatearMoneda(ventaSeleccionada?.monto_pagado) }}</span>
                    </div>
                    <div class="flex justify-between pt-2 border-t border-gray-300">
                        <span class="text-gray-900 font-medium">Saldo Pendiente:</span>
                        <span class="font-bold text-red-600">{{ formatearMoneda(ventaSeleccionada?.saldo_pendiente) }}</span>
                    </div>
                </div>

                <form @submit.prevent="generarQR" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Monto a Pagar (Bs.)
                        </label>
                        <input
                            v-model="formMonto.monto"
                            type="number"
                            step="0.01"
                            min="0.01"
                            :max="ventaSeleccionada?.saldo_pendiente"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-lg font-semibold"
                            placeholder="0.00"
                        />
                        <p class="text-sm text-gray-500 mt-1">
                            Máximo: {{ formatearMoneda(ventaSeleccionada?.saldo_pendiente) }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Email (Opcional)
                        </label>
                        <input
                            v-model="formMonto.email"
                            type="email"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            placeholder="tu@email.com"
                        />
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button
                            type="button"
                            @click="cerrarModalMonto"
                            class="flex-1 px-4 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="generandoQR"
                            class="flex-1 px-4 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 transition-colors font-medium disabled:opacity-50"
                        >
                            {{ generandoQR ? 'Generando...' : 'Generar QR' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal QR (sin cambios, el mismo que tenías antes) -->
        <div v-if="showQRModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg max-w-md w-full p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-900">Código QR de Pago</h3>
                    <button @click="cerrarQRModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="text-center mb-6">
                    <img 
                        v-if="qrData?.qrBase64"
                        :src="`data:image/png;base64,${qrData.qrBase64}`" 
                        alt="QR Code"
                        class="mx-auto w-64 h-64 border-4 border-indigo-500 rounded-lg"
                    />
                </div>

                <div class="bg-gray-50 rounded-lg p-4 mb-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Monto:</span>
                        <span class="font-semibold">{{ formatearMoneda(formMonto.monto) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Código de Transacción:</span>
                        <span class="font-mono text-sm">{{ qrData?.transactionId }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Vence:</span>
                        <span class="text-sm">{{ qrData?.expirationDate }}</span>
                    </div>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <p class="text-sm text-blue-800">
                        <strong>Instrucciones:</strong><br>
                        1. Escanea el código QR con tu app de PagoFácil<br>
                        2. Confirma el pago<br>
                        3. Haz clic en "Verificar Pago" para confirmar
                    </p>
                </div>

                <div class="flex gap-3">
                    <button
                        @click="descargarQR"
                        class="flex-1 px-4 py-2 border border-indigo-600 text-indigo-600 rounded-lg hover:bg-indigo-50 transition-colors font-medium flex items-center justify-center"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Descargar QR
                    </button>
                    <button
                        @click="verificarPago"
                        class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium flex items-center justify-center"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Verificar Pago
                    </button>
                </div>
            </div>
        </div>
    </AlumnoLayout>
</template>