<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AlumnoLayout from '../../Layout/AlumnoLayout.vue';
import { useApi } from '../../composables/useApi';

const { ventas: ventasApi, pagos: pagosApi } = useApi();

const ventas = ref([]);
const cuotasPendientes = ref({});
const loading = ref(true);
const generandoQR = ref(false);
const showQRModal = ref(false);
const qrData = ref(null);
const cuotaSeleccionada = ref(null);

const formMonto = ref({
    monto: 0,
    email: ''
});

const cargarVentas = async () => {
    loading.value = true;
    try {
        const response = await ventasApi.misVentas();
        if (response.success) {
            ventas.value = response.data.data;
            
            for (const venta of ventas.value) {
                const cuotasRes = await pagosApi.porVenta(venta.id);
                if (cuotasRes.success) {
                    const todosPagos = cuotasRes.data.data;
                    
                    // Si tiene cuotas definidas, mostrar solo esas cuotas
                    if (venta.cuotas) {
                        const cuotasAgrupadas = [];
                        const totalCuotas = parseInt(venta.cuotas);
                        
                        for (let i = 1; i <= totalCuotas; i++) {
                            // Buscar pagos de esta cuota (pendientes o pagados)
                            const pagosDeEstaCuota = todosPagos.filter(p => 
                                p.observaciones && p.observaciones.includes(`Cuota ${i} de`)
                            );
                            
                            if (pagosDeEstaCuota.length > 0) {
                                // Verificar si todos los pagos de esta cuota están pagados
                                const todoPagado = pagosDeEstaCuota.every(p => p.estado === 'pagado');
                                const algoPagado = pagosDeEstaCuota.some(p => p.estado === 'pagado');
                                
                                // Calcular monto total de la cuota
                                const montoTotal = pagosDeEstaCuota.reduce((sum, p) => sum + parseFloat(p.monto), 0);
                                
                                // Calcular monto pagado de la cuota
                                const montoPagado = pagosDeEstaCuota
                                    .filter(p => p.estado === 'pagado')
                                    .reduce((sum, p) => sum + parseFloat(p.monto), 0);
                                
                                cuotasAgrupadas.push({
                                    numero: i,
                                    monto: montoTotal,
                                    monto_pagado: montoPagado,
                                    observaciones: `Cuota ${i} de ${totalCuotas}`,
                                    pagos_ids: pagosDeEstaCuota.map(p => p.id),
                                    id: pagosDeEstaCuota[0].id,
                                    estado: todoPagado ? 'pagado' : (algoPagado ? 'parcial' : 'pendiente')
                                });
                            }
                        }
                        
                        cuotasPendientes.value[venta.id] = cuotasAgrupadas;
                    } else {
                        // Si no tiene cuotas definidas, mostrar todos los pagos
                        cuotasPendientes.value[venta.id] = todosPagos;
                    }
                }
            }
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

const abrirModalPago = (cuota, venta) => {
    cuotaSeleccionada.value = { 
        ...cuota, 
        venta_id: venta.id,
        // Si es cuota agrupada, usar el primer ID de pago
        pago_id: cuota.pagos_ids ? cuota.pagos_ids[0] : cuota.id

    };
    console.log('Datos de cuotaSeleccionada:', cuotaSeleccionada.value);
    formMonto.value = {
        monto: parseFloat(cuota.monto),
        email: ''
    };
};

const generarQR = async () => {
    if (!formMonto.value.monto || formMonto.value.monto <= 0) {
        alert('El monto debe ser mayor a 0');
        return;
    }

    generandoQR.value = true;

    try {
        const datos = {
            venta_id: cuotaSeleccionada.value.venta_id,
            monto: parseFloat(formMonto.value.monto),
            email: formMonto.value.email || '',
            pago_id: cuotaSeleccionada.value.pago_id
        };
        console.log(datos)
        const response = await pagosApi.generarQR(datos);
 

        if (response.success) {
            qrData.value = response.data.data;
            cuotaSeleccionada.value = null;
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
        const response = await pagosApi.consultarEstado(qrData.value.transactionId);
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
            <h1 class="text-3xl font-bold text-gray-900">Mis Pagos</h1>
            <p class="text-gray-600 mt-2">Gestiona tus cuotas pendientes</p>
        </div>

        <div v-if="loading" class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
            <p class="mt-4 text-gray-600">Cargando...</p>
        </div>

        <div v-else-if="ventas.length === 0" class="text-center py-12 bg-white rounded-lg shadow">
            <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="mt-4 text-gray-600">No tienes pagos pendientes</p>
        </div>

        <div v-else class="space-y-6">
            <div 
                v-for="venta in ventas" 
                :key="venta.id"
                class="bg-white rounded-xl shadow-md overflow-hidden"
            >
                <!-- Header de la venta -->
                <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-6">
                    <h3 class="text-white font-semibold text-xl">{{ venta.servicio_nombre }}</h3>
                    <p class="text-indigo-100 text-sm mt-1">{{ venta.mes_correspondiente }}</p>
                    <div class="mt-4 grid grid-cols-3 gap-4 text-white">
                        <div>
                            <p class="text-indigo-100 text-xs">Total</p>
                            <p class="text-lg font-bold">{{ formatearMoneda(venta.monto_total) }}</p>
                        </div>
                        <div>
                            <p class="text-indigo-100 text-xs">Pagado</p>
                            <p class="text-lg font-bold">{{ formatearMoneda(venta.monto_pagado) }}</p>
                        </div>
                        <div>
                            <p class="text-indigo-100 text-xs">Pendiente</p>
                            <p class="text-lg font-bold">{{ formatearMoneda(venta.saldo_pendiente) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Lista de cuotas -->
                <div class="p-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-4">
                        Cuotas Pendientes 
                        <span v-if="venta.cuotas" class="text-sm text-gray-500">({{ cuotasPendientes[venta.id]?.length || 0 }}/{{ venta.cuotas }})</span>
                    </h4>
                    
                    <div v-if="!cuotasPendientes[venta.id] || cuotasPendientes[venta.id].length === 0" class="text-center py-8 text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="mt-2">¡Todas las cuotas están pagadas!</p>
                    </div>

                    <div v-else class="space-y-3">
                        <div 
                            v-for="cuota in cuotasPendientes[venta.id]" 
                            :key="cuota.numero || cuota.id"
                            class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
                        >
                            <div class="flex items-center flex-1">
                                <div class="bg-indigo-100 rounded-full p-3 mr-4">
                                    <svg v-if="cuota.estado === 'pagado'" class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <svg v-else class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="font-semibold text-gray-900">{{ cuota.observaciones }}</p>
                                    <p class="text-2xl font-bold text-indigo-600 mt-1">{{ formatearMoneda(cuota.monto) }}</p>
                                    <p v-if="cuota.estado === 'parcial'" class="text-sm text-gray-600 mt-1">
                                        Pagado: {{ formatearMoneda(cuota.monto_pagado) }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span 
                                    :class="{
                                        'bg-yellow-100 text-yellow-800': cuota.estado === 'pendiente',
                                        'bg-green-100 text-green-800': cuota.estado === 'pagado',
                                        'bg-blue-100 text-blue-800': cuota.estado === 'parcial'
                                    }" 
                                    class="px-3 py-1 text-sm rounded-full font-medium"
                                >
                                    {{ cuota.estado === 'pagado' ? 'Pagado' : (cuota.estado === 'parcial' ? 'Parcial' : 'Pendiente') }}
                                </span>
                                <button
                                    v-if="cuota.estado !== 'pagado'"
                                    @click="abrirModalPago(cuota, venta)"
                                    class="bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-300 flex items-center"
                                >
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                    </svg>
                                    Pagar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Pago con QR -->
        <div v-if="cuotaSeleccionada" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-lg max-w-md w-full p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-900">Pagar Cuota</h3>
                    <button @click="cuotaSeleccionada = null" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="mb-6 p-4 bg-indigo-50 rounded-lg">
                    <p class="text-sm font-medium text-indigo-900">{{ cuotaSeleccionada.observaciones }}</p>
                    <p class="text-3xl font-bold text-indigo-600 mt-2">{{ formatearMoneda(cuotaSeleccionada.monto) }}</p>
                </div>

                <form @submit.prevent="generarQR" class="space-y-4">
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
                            @click="cuotaSeleccionada = null"
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

        <!-- Modal QR -->
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