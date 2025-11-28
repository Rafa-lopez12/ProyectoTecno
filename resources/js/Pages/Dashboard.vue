<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '../Layout/AppLayout.vue';
import { useApi } from '../composables/useApi';

const { ventas, inscripciones, asistencias, alumnos, tutores } = useApi();

const ventasPorEstado = ref([]);
const inscripcionesPorServicio = ref([]);
const asistenciasPorEstado = ref([]);
const totalAlumnos = ref(0);
const totalTutores = ref(0);
const totalInscripciones = ref(0);
const totalInscripcionesActivas = ref(0);
const ingresosTotales = ref(0);
const loading = ref(true);

onMounted(async () => {
    await cargarDatos();
});

const cargarDatos = async () => {
    loading.value = true;
    
    // Ventas por estado
    const resEstado = await ventas.reportePorEstado();
    if (resEstado.success) {
        ventasPorEstado.value = resEstado.data.data;
        ingresosTotales.value = resEstado.data.data.reduce((acc, v) => acc + parseFloat(v.monto_pagado || 0), 0);
    }

    // Inscripciones
    const resInscripciones = await inscripciones.getAll();
    if (resInscripciones.success) {
        const inscrip = resInscripciones.data.data;
        totalInscripciones.value = inscrip.length;
        
        const inscripcionesActivas = inscrip.filter(i => i.estado === 'activo');
        totalInscripcionesActivas.value = inscripcionesActivas.length;
        
        // Agrupar por servicio SOLO las activas
        const servicios = {};
        inscripcionesActivas.forEach(i => {
            const nombre = i.servicio_nombre || 'Sin servicio';
            servicios[nombre] = (servicios[nombre] || 0) + 1;
        });
        inscripcionesPorServicio.value = Object.entries(servicios).map(([k, v]) => ({ nombre: k, cantidad: v }));
    }

    // Asistencias
    const resAsistencias = await asistencias.getAll();
    if (resAsistencias.success) {
        const asist = resAsistencias.data.data;
        const estados = {};
        asist.forEach(a => {
            estados[a.estado] = (estados[a.estado] || 0) + 1;
        });
        asistenciasPorEstado.value = Object.entries(estados).map(([k, v]) => ({ estado: k, cantidad: v }));
    }

    // Alumnos
    const resAlumnos = await alumnos.getAll();
    if (resAlumnos.success) {
        totalAlumnos.value = resAlumnos.data.data.length;
    }

    // Tutores
    const resTutores = await tutores.getAll();
    if (resTutores.success) {
        totalTutores.value = resTutores.data.data.length;
    }
    
    loading.value = false;
};

const colores = {
    primary: '#6366F1',
    success: '#10B981',
    warning: '#F59E0B',
    danger: '#EF4444',
    purple: '#8B5CF6',
    pink: '#EC4899',
    teal: '#14B8A6',
    orange: '#F97316'
};

const colorArray = Object.values(colores);

const getEstadoColor = (estado) => {
    const map = {
        'pagado': colores.success,
        'pendiente': colores.warning,
        'parcial': colores.orange,
        'cancelado': colores.danger,
        'presente': colores.success,
        'ausente': colores.danger,
        'tardanza': colores.warning,
        'justificado': colores.primary,
        'recuperada': colores.teal
    };
    return map[estado] || colores.primary;
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-BO', {
        style: 'currency',
        currency: 'BOB',
        minimumFractionDigits: 2
    }).format(value);
};

const calcularPorcentajePagado = () => {
    const total = ventasPorEstado.value.reduce((acc, v) => acc + parseFloat(v.monto_total || 0), 0);
    return total > 0 ? (ingresosTotales.value / total * 100) : 0;
};

const calcularTasaAsistencia = () => {
    const totalAsist = asistenciasPorEstado.value.reduce((acc, a) => acc + a.cantidad, 0);
    const presentes = asistenciasPorEstado.value.find(a => a.estado === 'presente')?.cantidad || 0;
    return totalAsist > 0 ? (presentes / totalAsist * 100) : 0;
};
</script>

<template>
    <AppLayout>
        <Head title="Dashboard" />

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center h-96">
            <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-indigo-600"></div>
        </div>

        <div v-else class="space-y-8">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold text-gray-900">Dashboard</h1>
                    <p class="text-gray-600 mt-2">Resumen general del sistema</p>
                </div>
                <div class="text-sm text-gray-500">
                    {{ new Date().toLocaleDateString('es-BO', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Alumnos -->
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-lg p-6 text-white transform hover:scale-105 transition-transform">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100 text-sm font-medium uppercase tracking-wide">Alumnos</p>
                            <p class="text-4xl font-bold mt-2">{{ totalAlumnos }}</p>
                            <p class="text-blue-100 text-xs mt-1">Total registrados</p>
                        </div>
                        <div class="bg-white/20 rounded-full p-4">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Tutores -->
                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl shadow-lg p-6 text-white transform hover:scale-105 transition-transform">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-100 text-sm font-medium uppercase tracking-wide">Tutores</p>
                            <p class="text-4xl font-bold mt-2">{{ totalTutores }}</p>
                            <p class="text-green-100 text-xs mt-1">Total activos</p>
                        </div>
                        <div class="bg-white/20 rounded-full p-4">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Inscripciones -->
                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl shadow-lg p-6 text-white transform hover:scale-105 transition-transform">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-100 text-sm font-medium uppercase tracking-wide">Inscripciones</p>
                            <p class="text-4xl font-bold mt-2">{{ totalInscripcionesActivas }}</p>
                            <p class="text-purple-100 text-xs mt-1">Total activas</p>
                        </div>
                        <div class="bg-white/20 rounded-full p-4">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Ingresos -->
                <div class="bg-gradient-to-br from-yellow-500 to-orange-600 rounded-2xl shadow-lg p-6 text-white transform hover:scale-105 transition-transform">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-yellow-100 text-sm font-medium uppercase tracking-wide">Ingresos</p>
                            <p class="text-4xl font-bold mt-2">{{ formatCurrency(ingresosTotales) }}</p>
                            <p class="text-yellow-100 text-xs mt-1">Total recaudado</p>
                        </div>
                        <div class="bg-white/20 rounded-full p-4">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Charts Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Ventas por Estado - Gráfico Circular Mejorado -->
                <div class="lg:col-span-1 bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-shadow">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-gray-800">Ventas por Estado</h3>
                        <span class="text-xs bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full font-medium">
                            {{ ventasPorEstado.reduce((acc, v) => acc + v.total_ventas, 0) }} total
                        </span>
                    </div>
                    
                    <div class="flex items-center justify-center mb-6">
                        <div class="relative w-56 h-56">
                            <svg viewBox="0 0 200 200" class="transform -rotate-90 w-full h-full">
                                <circle cx="100" cy="100" r="80" fill="none" stroke="#F3F4F6" stroke-width="30"/>
                                <circle 
                                    v-for="(venta, i) in ventasPorEstado" 
                                    :key="i"
                                    cx="100" 
                                    cy="100" 
                                    r="80" 
                                    fill="none" 
                                    :stroke="getEstadoColor(venta.estado)"
                                    stroke-width="30"
                                    :stroke-dasharray="`${(venta.total_ventas / ventasPorEstado.reduce((acc, v) => acc + v.total_ventas, 1)) * 502} 502`"
                                    :stroke-dashoffset="ventasPorEstado.slice(0, i).reduce((acc, v) => acc - (v.total_ventas / ventasPorEstado.reduce((a, b) => a + b.total_ventas, 1)) * 502, 0)"
                                    class="transition-all duration-500"
                                />
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center flex-col">
                                <p class="text-4xl font-bold text-gray-800">{{ ventasPorEstado.reduce((acc, v) => acc + v.total_ventas, 0) }}</p>
                                <p class="text-sm text-gray-500">Ventas</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div v-for="(venta, i) in ventasPorEstado" :key="i" 
                             class="flex items-center justify-between p-3 rounded-xl hover:bg-gray-50 transition-colors">
                            <div class="flex items-center space-x-3">
                                <div class="w-4 h-4 rounded-full" :style="{ backgroundColor: getEstadoColor(venta.estado) }"></div>
                                <span class="font-medium text-gray-700 capitalize">{{ venta.estado }}</span>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-gray-800">{{ venta.total_ventas }}</p>
                                <p class="text-xs text-gray-500">{{ ((venta.total_ventas / ventasPorEstado.reduce((acc, v) => acc + v.total_ventas, 1)) * 100).toFixed(1) }}%</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Montos Financieros -->
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-shadow">
                    <h3 class="text-xl font-bold text-gray-800 mb-6">Análisis Financiero</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div class="text-center p-4 bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl">
                            <p class="text-sm text-blue-700 font-medium mb-2">Monto Total</p>
                            <p class="text-3xl font-bold text-blue-900">
                                {{ formatCurrency(ventasPorEstado.reduce((acc, v) => acc + parseFloat(v.monto_total || 0), 0)) }}
                            </p>
                        </div>
                        <div class="text-center p-4 bg-gradient-to-br from-green-50 to-green-100 rounded-xl">
                            <p class="text-sm text-green-700 font-medium mb-2">Pagado</p>
                            <p class="text-3xl font-bold text-green-900">{{ formatCurrency(ingresosTotales) }}</p>
                        </div>
                        <div class="text-center p-4 bg-gradient-to-br from-red-50 to-red-100 rounded-xl">
                            <p class="text-sm text-red-700 font-medium mb-2">Pendiente</p>
                            <p class="text-3xl font-bold text-red-900">
                                {{ formatCurrency(ventasPorEstado.reduce((acc, v) => acc + parseFloat(v.saldo_pendiente || 0), 0)) }}
                            </p>
                        </div>
                    </div>

                    <!-- Barra de progreso financiero -->
                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between text-sm mb-2">
                                <span class="font-medium text-gray-700">Progreso de Pagos</span>
                                <span class="font-bold text-indigo-600">{{ calcularPorcentajePagado().toFixed(1) }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-6 overflow-hidden">
                                <div class="bg-gradient-to-r from-green-500 to-emerald-600 h-full rounded-full transition-all duration-1000 flex items-center justify-end pr-2"
                                     :style="{ width: `${calcularPorcentajePagado()}%` }">
                                    <span v-if="calcularPorcentajePagado() > 10" class="text-xs font-bold text-white">
                                        {{ calcularPorcentajePagado().toFixed(0) }}%
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Gráfico de barras de montos -->
                        <div class="pt-4">
                            <div class="flex justify-around items-end h-48 border-b border-gray-200">
                                <div v-for="(venta, i) in ventasPorEstado" :key="i" class="flex flex-col items-center group">
                                    <div class="relative">
                                        <div class="w-16 rounded-t-xl transition-all duration-500 group-hover:opacity-80 cursor-pointer" 
                                             :style="{
                                                 height: `${(parseFloat(venta.monto_total) / Math.max(...ventasPorEstado.map(v => parseFloat(v.monto_total)), 1)) * 160}px`,
                                                 background: `linear-gradient(to top, ${getEstadoColor(venta.estado)}, ${getEstadoColor(venta.estado)}99)`
                                             }">
                                        </div>
                                        <div class="absolute -top-8 left-1/2 transform -translate-x-1/2 opacity-0 group-hover:opacity-100 transition-opacity bg-gray-900 text-white text-xs rounded px-2 py-1 whitespace-nowrap">
                                            {{ formatCurrency(parseFloat(venta.monto_total)) }}
                                        </div>
                                    </div>
                                    <p class="text-xs mt-3 font-medium text-gray-600 capitalize">{{ venta.estado }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Second Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <!-- Inscripciones por Servicio -->
                <div class="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-shadow">
                    <h3 class="text-xl font-bold text-gray-800 mb-6">Inscripciones por Servicio</h3>
                    
                    <div class="space-y-4">
                        <div v-for="(servicio, i) in inscripcionesPorServicio" :key="i" class="group">
                            <div class="flex justify-between items-center mb-2">
                                <span class="font-medium text-gray-700">{{ servicio.nombre }}</span>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-bold text-gray-800">{{ servicio.cantidad }}</span>
                                    <span class="text-xs text-gray-500">
                                        ({{ ((servicio.cantidad / totalInscripciones) * 100).toFixed(1) }}%)
                                    </span>
                                </div>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-700 group-hover:opacity-80"
                                     :style="{
                                         width: `${(servicio.cantidad / totalInscripciones) * 100}%`,
                                         background: `linear-gradient(to right, ${colorArray[i % colorArray.length]}, ${colorArray[i % colorArray.length]}cc)`
                                     }">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mini gráfico visual -->
                    <div class="mt-6 flex justify-center items-end h-32 space-x-2">
                        <div v-for="(servicio, i) in inscripcionesPorServicio" :key="i" 
                             class="flex-1 rounded-t-lg transition-all duration-500 hover:opacity-80 cursor-pointer"
                             :style="{
                                 height: `${(servicio.cantidad / Math.max(...inscripcionesPorServicio.map(s => s.cantidad))) * 100}%`,
                                 backgroundColor: colorArray[i % colorArray.length]
                             }">
                        </div>
                    </div>
                </div>

                <!-- Asistencias -->
                <div class="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-shadow">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-gray-800">Control de Asistencias</h3>
                        <div class="text-right">
                            <p class="text-3xl font-bold text-indigo-600">{{ calcularTasaAsistencia().toFixed(1) }}%</p>
                            <p class="text-xs text-gray-500">Tasa de asistencia</p>
                        </div>
                    </div>

                    <!-- Círculo de progreso grande -->
                    <div class="flex justify-center items-center mb-6">
                        <div class="relative w-48 h-48">
                            <svg viewBox="0 0 100 100" class="transform -rotate-90 w-full h-full">
                                <circle cx="50" cy="50" r="40" fill="none" stroke="#F3F4F6" stroke-width="12"/>
                                <circle cx="50" cy="50" r="40" fill="none" 
                                        stroke="#10B981" 
                                        stroke-width="12"
                                        stroke-linecap="round"
                                        :stroke-dasharray="`${(calcularTasaAsistencia() / 100) * 251} 251`"
                                        class="transition-all duration-1000"/>
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center flex-col">
                                <p class="text-4xl font-bold text-gray-800">{{ calcularTasaAsistencia().toFixed(0) }}%</p>
                                <p class="text-sm text-gray-500">Presentes</p>
                            </div>
                        </div>
                    </div>

                    <!-- Grid de estados -->
                    <div class="grid grid-cols-2 gap-4">
                        <div v-for="(asist, i) in asistenciasPorEstado" :key="i" 
                             class="p-4 rounded-xl text-center hover:scale-105 transition-transform cursor-pointer"
                             :style="{ backgroundColor: getEstadoColor(asist.estado) + '20' }">
                            <div class="flex items-center justify-center mb-2">
                                <div class="w-3 h-3 rounded-full mr-2" :style="{ backgroundColor: getEstadoColor(asist.estado) }"></div>
                                <p class="text-sm font-medium capitalize" :style="{ color: getEstadoColor(asist.estado) }">
                                    {{ asist.estado }}
                                </p>
                            </div>
                            <p class="text-3xl font-bold" :style="{ color: getEstadoColor(asist.estado) }">
                                {{ asist.cantidad }}
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ ((asist.cantidad / asistenciasPorEstado.reduce((acc, a) => acc + a.cantidad, 1)) * 100).toFixed(1) }}%
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-2xl p-6 border-l-4 border-indigo-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-indigo-700 font-medium mb-1">Promedio por Venta</p>
                            <p class="text-2xl font-bold text-indigo-900">
                                {{ formatCurrency(ventasPorEstado.length ? 
                                   (ventasPorEstado.reduce((acc, v) => acc + parseFloat(v.monto_total), 0) / 
                                   ventasPorEstado.reduce((acc, v) => acc + v.total_ventas, 1)) : 0) }}
                            </p>
                        </div>
                        <svg class="w-12 h-12 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-2xl p-6 border-l-4 border-green-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-green-700 font-medium mb-1">Servicios Activos</p>
                            <p class="text-2xl font-bold text-green-900">{{ inscripcionesPorServicio.length }}</p>
                        </div>
                        <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-2xl p-6 border-l-4 border-purple-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-purple-700 font-medium mb-1">Total Asistencias</p>
                            <p class="text-2xl font-bold text-purple-900">
                                {{ asistenciasPorEstado.reduce((acc, a) => acc + a.cantidad, 0) }}
                            </p>
                        </div>
                        <svg class="w-12 h-12 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-2xl p-6 border-l-4 border-orange-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-orange-700 font-medium mb-1">Ratio Alumno/Tutor</p>
                            <p class="text-2xl font-bold text-orange-900">
                                {{ totalTutores > 0 ? (totalAlumnos / totalTutores).toFixed(1) : 0 }}:1
                            </p>
                        </div>
                        <svg class="w-12 h-12 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Animaciones suaves */
@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-slide-in {
    animation: slideInUp 0.5s ease-out;
}
</style>