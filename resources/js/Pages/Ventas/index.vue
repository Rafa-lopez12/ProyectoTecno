<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '../../Layout/AppLayout.vue';
import { useApi } from '../../composables/useApi';

const { ventas: ventasApi } = useApi();
const ventas = ref([]);
const loading = ref(false);

// Filtros
const filtroEstado = ref('');
const filtroTipoVenta = ref('');
const filtroMes = ref(new Date().getMonth() + 1);
const filtroAnio = ref(new Date().getFullYear());

// Estadísticas
const estadisticas = ref({
    total_ventas: 0,
    monto_total: 0,
    monto_pagado: 0,
    saldo_pendiente: 0
});

const verPagos = (ventaId) => {
    router.visit(`/ventas/${ventaId}/pagos`);
};

const cargarVentas = async () => {
    loading.value = true;

    try {
        const params = {};
        if (filtroEstado.value) params.estado = filtroEstado.value;
        if (filtroTipoVenta.value) params.tipo_venta = filtroTipoVenta.value;

        const response = await ventasApi.getAll(params);

        if (response.success) {
            ventas.value = response.data.data;
            calcularEstadisticas();
        }
    } catch (error) {
        console.error("Error:", error);
    } finally {
        loading.value = false;
    }
};

const cargarReporteMensual = async () => {
    loading.value = true;

    try {
        const response = await ventasApi.reporteMensual({
            mes: filtroMes.value,
            anio: filtroAnio.value
        });

        if (response.success) {
            ventas.value = response.data.data;
            calcularEstadisticas();
        }
    } catch (error) {
        console.error("Error:", error);
    } finally {
        loading.value = false;
    }
};

const calcularEstadisticas = () => {
    estadisticas.value = {
        total_ventas: ventas.value.length,
        monto_total: ventas.value.reduce((sum, v) => sum + parseFloat(v.monto_total || 0), 0),
        monto_pagado: ventas.value.reduce((sum, v) => sum + parseFloat(v.monto_pagado || 0), 0),
        saldo_pendiente: ventas.value.reduce((sum, v) => sum + parseFloat(v.saldo_pendiente || 0), 0)
    };
};

onMounted(() => {
    cargarVentas();
});

// Aplicar filtros
const aplicarFiltros = () => {
    cargarVentas();
};

// Limpiar filtros
const limpiarFiltros = () => {
    filtroEstado.value = '';
    filtroTipoVenta.value = '';
    cargarVentas();
};

// Ver reporte mensual
const verReporteMensual = () => {
    cargarReporteMensual();
};

// Obtener clase CSS para estado
const getEstadoClass = (estado) => {
    const classes = {
        'pendiente': 'bg-yellow-100 text-yellow-800',
        'pagado': 'bg-green-100 text-green-800',
        'vencido': 'bg-red-100 text-red-800',
        'cancelado': 'bg-gray-100 text-gray-800'
    };
    return classes[estado] || 'bg-gray-100 text-gray-800';
};

// Obtener texto del estado
const getEstadoTexto = (estado) => {
    const textos = {
        'pendiente': 'Pendiente',
        'pagado': 'Pagado',
        'vencido': 'Vencido',
        'cancelado': 'Cancelado'
    };
    return textos[estado] || estado;
};

// Obtener clase CSS para tipo de venta
const getTipoVentaClass = (tipo) => {
    return tipo === 'contado' ? 'text-green-600' : 'text-blue-600';
};

// Formatear fecha
const formatearFecha = (fecha) => {
    if (!fecha) return '-';
    return new Date(fecha).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

// Formatear moneda
const formatearMoneda = (monto) => {
    return `Bs. ${parseFloat(monto || 0).toFixed(2)}`;
};

// Lista de meses
const meses = [
    { value: 1, label: 'Enero' },
    { value: 2, label: 'Febrero' },
    { value: 3, label: 'Marzo' },
    { value: 4, label: 'Abril' },
    { value: 5, label: 'Mayo' },
    { value: 6, label: 'Junio' },
    { value: 7, label: 'Julio' },
    { value: 8, label: 'Agosto' },
    { value: 9, label: 'Septiembre' },
    { value: 10, label: 'Octubre' },
    { value: 11, label: 'Noviembre' },
    { value: 12, label: 'Diciembre' }
];

// Generar años (últimos 5 años)
const anios = computed(() => {
    const currentYear = new Date().getFullYear();
    return Array.from({ length: 5 }, (_, i) => currentYear - i);
});
</script>

<template>
    <AppLayout>
        <Head title="Ventas" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Ventas</h2>
                    <p class="text-gray-600 mt-1">Gestiona los pagos y ventas del sistema</p>
                </div>
            </div>

            <!-- Estadísticas -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Total Ventas -->
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100 text-sm font-medium">Total Ventas</p>
                            <p class="text-3xl font-bold mt-2">{{ estadisticas.total_ventas }}</p>
                        </div>
                        <div class="bg-white bg-opacity-20 rounded-full p-3">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Monto Total -->
                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-100 text-sm font-medium">Monto Total</p>
                            <p class="text-2xl font-bold mt-2">{{ formatearMoneda(estadisticas.monto_total) }}</p>
                        </div>
                        <div class="bg-white bg-opacity-20 rounded-full p-3">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Monto Pagado -->
                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-100 text-sm font-medium">Monto Pagado</p>
                            <p class="text-2xl font-bold mt-2">{{ formatearMoneda(estadisticas.monto_pagado) }}</p>
                        </div>
                        <div class="bg-white bg-opacity-20 rounded-full p-3">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Saldo Pendiente -->
                <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-2xl shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-amber-100 text-sm font-medium">Saldo Pendiente</p>
                            <p class="text-2xl font-bold mt-2">{{ formatearMoneda(estadisticas.saldo_pendiente) }}</p>
                        </div>
                        <div class="bg-white bg-opacity-20 rounded-full p-3">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="bg-white rounded-lg shadow-sm p-4">
                <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                    <!-- Filtro por Estado -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                        <select
                            v-model="filtroEstado"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="">Todos</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="pagado">Pagado</option>
                            <option value="vencido">Vencido</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>

                    <!-- Filtro por Tipo -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
                        <select
                            v-model="filtroTipoVenta"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="">Todos</option>
                            <option value="contado">Contado</option>
                            <option value="credito">Crédito</option>
                        </select>
                    </div>

                    <!-- Mes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mes</label>
                        <select
                            v-model="filtroMes"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                        >
                            <option v-for="mes in meses" :key="mes.value" :value="mes.value">
                                {{ mes.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Año -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Año</label>
                        <select
                            v-model="filtroAnio"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                        >
                            <option v-for="anio in anios" :key="anio" :value="anio">
                                {{ anio }}
                            </option>
                        </select>
                    </div>

                    <!-- Botones de filtro -->
                    <div class="flex items-end gap-2 md:col-span-2">
                        <button
                            @click="aplicarFiltros"
                            class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
                        >
                            Filtrar
                        </button>
                        <button
                            @click="verReporteMensual"
                            class="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition"
                        >
                            Reporte Mes
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

            <!-- Tabla de ventas -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div v-if="loading" class="p-8 text-center">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                    <p class="mt-2 text-gray-600">Cargando ventas...</p>
                </div>

                <div v-else-if="!ventas || ventas.length === 0" class="p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="mt-2 text-gray-600">No hay ventas registradas</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Alumno
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    CI
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Servicio
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Mes
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tipo
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Monto Total
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Pagado
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Saldo
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Estado
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Fecha
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="venta in ventas" :key="venta.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ venta.alumno_nombre }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-600">
                                        {{ venta.alumno_ci }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ venta.servicio_nombre }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ venta.mes_correspondiente }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium" :class="getTipoVentaClass(venta.tipo_venta)">
                                        {{ venta.tipo_venta === 'contado' ? '💵 Contado' : '📅 Crédito' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm font-semibold text-gray-900">
                                        {{ formatearMoneda(venta.monto_total) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm text-green-600 font-medium">
                                        {{ formatearMoneda(venta.monto_pagado) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm font-medium" :class="parseFloat(venta.saldo_pendiente) > 0 ? 'text-amber-600' : 'text-gray-400'">
                                        {{ formatearMoneda(venta.saldo_pendiente) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="getEstadoClass(venta.estado)" class="px-2 py-1 text-xs font-semibold rounded-full">
                                        {{ getEstadoTexto(venta.estado) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-600">
                                        {{ formatearFecha(venta.fecha_venta) }}
                                    </div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <button
                                        @click="verPagos(venta.id)"
                                        class="text-indigo-600 hover:text-indigo-900 font-medium flex items-center"
                                    >
                                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                        Ver Pagos
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>