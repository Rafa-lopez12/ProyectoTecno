<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '../../Layout/AppLayout.vue';
import { useApi } from '../../composables/useApi';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';

const { ventas, asistencias, inscripciones, alumnos, tutores } = useApi();

// Estado de carga
const loading = ref(false);

// Tabs
const activeTab = ref('ventas');

// ============================================
// FILTROS VENTAS
// ============================================
const ventasData = ref([]);
const ventasFiltros = ref({
    tipo: 'estado', // estado o fecha
    estado: 'todos',
    fecha_desde: '',
    fecha_hasta: ''
});

const estadosVenta = ['todos', 'pendiente', 'pagado', 'parcial', 'cancelado'];

// ============================================
// FILTROS ASISTENCIAS
// ============================================
const asistenciasData = ref([]);
const asistenciasFiltros = ref({
    tipo: 'estado', // estado o fecha
    estado: 'todos',
    fecha_desde: '',
    fecha_hasta: ''
});

const estadosAsistencia = ['todos', 'presente', 'ausente', 'tardanza', 'justificado', 'recuperada'];

// ============================================
// FILTROS INSCRIPCIONES
// ============================================
const inscripcionesData = ref([]);
const inscripcionesFiltros = ref({
    tipo: 'estado', // estado o servicio
    estado: 'todos',
    servicio: 'todos'
});

const estadosInscripcion = ['todos', 'activo', 'retirado', 'finalizado'];
const serviciosDisponibles = ref(['todos']);

// ============================================
// CARGAR DATOS
// ============================================
const cargarVentas = async () => {
    loading.value = true;
    const params = {};
    
    if (ventasFiltros.value.tipo === 'estado' && ventasFiltros.value.estado !== 'todos') {
        params.estado = ventasFiltros.value.estado;
    } else if (ventasFiltros.value.tipo === 'fecha') {
        if (ventasFiltros.value.fecha_desde) params.fecha_desde = ventasFiltros.value.fecha_desde;
        if (ventasFiltros.value.fecha_hasta) params.fecha_hasta = ventasFiltros.value.fecha_hasta;
    }
    
    const res = await ventas.getAll(params);
    if (res.success) {
        ventasData.value = res.data.data;
    }
    loading.value = false;
};

const cargarAsistencias = async () => {
    loading.value = true;
    const params = {};
    
    if (asistenciasFiltros.value.tipo === 'estado' && asistenciasFiltros.value.estado !== 'todos') {
        params.estado = asistenciasFiltros.value.estado;
    } else if (asistenciasFiltros.value.tipo === 'fecha') {
        if (asistenciasFiltros.value.fecha_desde) params.fecha_desde = asistenciasFiltros.value.fecha_desde;
        if (asistenciasFiltros.value.fecha_hasta) params.fecha_hasta = asistenciasFiltros.value.fecha_hasta;
    }
    
    const res = await asistencias.getAll(params);
    if (res.success) {
        asistenciasData.value = res.data.data;
    }
    loading.value = false;
};

const cargarInscripciones = async () => {
    loading.value = true;
    const params = {};
    
    if (inscripcionesFiltros.value.tipo === 'estado' && inscripcionesFiltros.value.estado !== 'todos') {
        params.estado = inscripcionesFiltros.value.estado;
    } else if (inscripcionesFiltros.value.tipo === 'servicio' && inscripcionesFiltros.value.servicio !== 'todos') {
        params.servicio_id = inscripcionesFiltros.value.servicio;
    }
    
    const res = await inscripciones.getAll(params);
    if (res.success) {
        inscripcionesData.value = res.data.data;
        
        // Extraer servicios únicos
        const servicios = [...new Set(res.data.data.map(i => i.servicio_nombre))];
        serviciosDisponibles.value = ['todos', ...servicios];
    }
    loading.value = false;
};

// ============================================
// GENERAR PDFs
// ============================================
const generarPDFVentas = () => {
    const doc = new jsPDF();
    
    // Encabezado
    doc.setFontSize(18);
    doc.setTextColor(99, 102, 241);
    doc.text('Reporte de Ventas', 14, 22);
    
    doc.setFontSize(10);
    doc.setTextColor(100);
    doc.text(`Generado: ${new Date().toLocaleString('es-BO')}`, 14, 30);
    
    // Filtros aplicados
    let filtroTexto = 'Filtro: ';
    if (ventasFiltros.value.tipo === 'estado') {
        filtroTexto += `Estado - ${ventasFiltros.value.estado}`;
    } else {
        filtroTexto += `Fecha - ${ventasFiltros.value.fecha_desde || 'Inicio'} a ${ventasFiltros.value.fecha_hasta || 'Fin'}`;
    }
    doc.text(filtroTexto, 14, 36);
    
    // Estadísticas
    const totalVentas = ventasData.value.length;
    const montoTotal = ventasData.value.reduce((acc, v) => acc + parseFloat(v.monto_total || 0), 0);
    const montoPagado = ventasData.value.reduce((acc, v) => acc + parseFloat(v.monto_pagado || 0), 0);
    const pendiente = montoTotal - montoPagado;
    
    doc.setFontSize(9);
    doc.setTextColor(0);
    doc.text(`Total Ventas: ${totalVentas}`, 14, 44);
    doc.text(`Monto Total: Bs ${montoTotal.toFixed(2)}`, 70, 44);
    doc.text(`Pagado: Bs ${montoPagado.toFixed(2)}`, 130, 44);
    doc.text(`Pendiente: Bs ${pendiente.toFixed(2)}`, 14, 50);
    
    // Tabla
    const tableData = ventasData.value.map(v => [
        v.id,
        v.alumno_nombre || 'N/A',
        v.servicio_nombre || 'N/A',
        `Bs ${parseFloat(v.monto_total).toFixed(2)}`,
        `Bs ${parseFloat(v.monto_pagado).toFixed(2)}`,
        v.estado.toUpperCase(),
        new Date(v.fecha_venta).toLocaleDateString('es-BO')
    ]);
    
    autoTable(doc, {
        startY: 56,
        head: [['ID', 'Alumno', 'Servicio', 'Total', 'Pagado', 'Estado', 'Fecha']],
        body: tableData,
        theme: 'striped',
        headStyles: { fillColor: [99, 102, 241], textColor: 255 },
        styles: { fontSize: 8 },
        columnStyles: {
            0: { cellWidth: 15 },
            1: { cellWidth: 35 },
            2: { cellWidth: 30 },
            3: { cellWidth: 25 },
            4: { cellWidth: 25 },
            5: { cellWidth: 25 },
            6: { cellWidth: 25 }
        }
    });
    
    doc.save(`Reporte_Ventas_${new Date().toISOString().split('T')[0]}.pdf`);
};

const generarPDFAsistencias = () => {
    const doc = new jsPDF();
    
    // Encabezado
    doc.setFontSize(18);
    doc.setTextColor(16, 185, 129);
    doc.text('Reporte de Asistencias', 14, 22);
    
    doc.setFontSize(10);
    doc.setTextColor(100);
    doc.text(`Generado: ${new Date().toLocaleString('es-BO')}`, 14, 30);
    
    // Filtros
    let filtroTexto = 'Filtro: ';
    if (asistenciasFiltros.value.tipo === 'estado') {
        filtroTexto += `Estado - ${asistenciasFiltros.value.estado}`;
    } else {
        filtroTexto += `Fecha - ${asistenciasFiltros.value.fecha_desde || 'Inicio'} a ${asistenciasFiltros.value.fecha_hasta || 'Fin'}`;
    }
    doc.text(filtroTexto, 14, 36);
    
    // Estadísticas
    const totalAsistencias = asistenciasData.value.length;
    const presentes = asistenciasData.value.filter(a => a.estado === 'presente').length;
    const ausentes = asistenciasData.value.filter(a => a.estado === 'ausente').length;
    const tardanzas = asistenciasData.value.filter(a => a.estado === 'tardanza').length;
    
    doc.setFontSize(9);
    doc.setTextColor(0);
    doc.text(`Total: ${totalAsistencias}`, 14, 44);
    doc.text(`Presentes: ${presentes}`, 60, 44);
    doc.text(`Ausentes: ${ausentes}`, 100, 44);
    doc.text(`Tardanzas: ${tardanzas}`, 140, 44);
    
    // Tabla
    const tableData = asistenciasData.value.map(a => [
        a.id,
        a.alumno_nombre || 'N/A',
        a.tutor_nombre || 'N/A',
        new Date(a.fecha).toLocaleDateString('es-BO'),
        a.estado.toUpperCase(),
        a.observaciones || '-'
    ]);
    
    autoTable(doc, {
        startY: 50,
        head: [['ID', 'Alumno', 'Tutor', 'Fecha', 'Estado', 'Observaciones']],
        body: tableData,
        theme: 'striped',
        headStyles: { fillColor: [16, 185, 129], textColor: 255 },
        styles: { fontSize: 8 },
        columnStyles: {
            0: { cellWidth: 15 },
            1: { cellWidth: 40 },
            2: { cellWidth: 40 },
            3: { cellWidth: 25 },
            4: { cellWidth: 25 },
            5: { cellWidth: 35 }
        }
    });
    
    doc.save(`Reporte_Asistencias_${new Date().toISOString().split('T')[0]}.pdf`);
};

const generarPDFInscripciones = () => {
    const doc = new jsPDF();
    
    // Encabezado
    doc.setFontSize(18);
    doc.setTextColor(139, 92, 246);
    doc.text('Reporte de Inscripciones', 14, 22);
    
    doc.setFontSize(10);
    doc.setTextColor(100);
    doc.text(`Generado: ${new Date().toLocaleString('es-BO')}`, 14, 30);
    
    // Filtros
    let filtroTexto = 'Filtro: ';
    if (inscripcionesFiltros.value.tipo === 'estado') {
        filtroTexto += `Estado - ${inscripcionesFiltros.value.estado}`;
    } else {
        filtroTexto += `Servicio - ${inscripcionesFiltros.value.servicio}`;
    }
    doc.text(filtroTexto, 14, 36);
    
    // Estadísticas
    const totalInscripciones = inscripcionesData.value.length;
    const activas = inscripcionesData.value.filter(i => i.estado === 'activo').length;
    const finalizadas = inscripcionesData.value.filter(i => i.estado === 'finalizado').length;
    const retiradas = inscripcionesData.value.filter(i => i.estado === 'retirado').length;
    
    doc.setFontSize(9);
    doc.setTextColor(0);
    doc.text(`Total: ${totalInscripciones}`, 14, 44);
    doc.text(`Activas: ${activas}`, 60, 44);
    doc.text(`Finalizadas: ${finalizadas}`, 100, 44);
    doc.text(`Retiradas: ${retiradas}`, 140, 44);
    
    // Tabla
    const tableData = inscripcionesData.value.map(i => [
        i.id,
        i.alumno_nombre || 'N/A',
        i.tutor_nombre || 'N/A',
        i.servicio_nombre || 'N/A',
        new Date(i.fecha_inscripcion).toLocaleDateString('es-BO'),
        i.estado.toUpperCase()
    ]);
    
    autoTable(doc, {
        startY: 50,
        head: [['ID', 'Alumno', 'Tutor', 'Servicio', 'Fecha', 'Estado']],
        body: tableData,
        theme: 'striped',
        headStyles: { fillColor: [139, 92, 246], textColor: 255 },
        styles: { fontSize: 8 },
        columnStyles: {
            0: { cellWidth: 15 },
            1: { cellWidth: 45 },
            2: { cellWidth: 45 },
            3: { cellWidth: 35 },
            4: { cellWidth: 25 },
            5: { cellWidth: 25 }
        }
    });
    
    doc.save(`Reporte_Inscripciones_${new Date().toISOString().split('T')[0]}.pdf`);
};
</script>

<template>
    <AppLayout>
        <Head title="Reportes PDF" />

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold theme-text">Generación de Reportes PDF</h1>
                    <p class="theme-text-secondary mt-2">Filtra y genera reportes en PDF de ventas, asistencias e inscripciones</p>
                </div>
            </div>

            <!-- Tabs -->
            <div class="theme-surface rounded-2xl shadow-lg overflow-hidden">
                <div class="flex border-b theme-border">
                    <button
                        @click="activeTab = 'ventas'"
                        class="flex-1 px-6 py-4 text-sm font-medium transition-all"
                        :class="activeTab === 'ventas' ? 'bg-indigo-50 text-indigo-700 border-b-2 border-indigo-600' : 'theme-text-secondary hover:theme-text'"
                    >
                        <div class="flex items-center justify-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Ventas</span>
                        </div>
                    </button>
                    <button
                        @click="activeTab = 'asistencias'"
                        class="flex-1 px-6 py-4 text-sm font-medium transition-all"
                        :class="activeTab === 'asistencias' ? 'bg-green-50 text-green-700 border-b-2 border-green-600' : 'theme-text-secondary hover:theme-text'"
                    >
                        <div class="flex items-center justify-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                            <span>Asistencias</span>
                        </div>
                    </button>
                    <button
                        @click="activeTab = 'inscripciones'"
                        class="flex-1 px-6 py-4 text-sm font-medium transition-all"
                        :class="activeTab === 'inscripciones' ? 'bg-purple-50 text-purple-700 border-b-2 border-purple-600' : 'theme-text-secondary hover:theme-text'"
                    >
                        <div class="flex items-center justify-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>Inscripciones</span>
                        </div>
                    </button>
                </div>

                <!-- SECCIÓN VENTAS -->
                <div v-show="activeTab === 'ventas'" class="p-6 space-y-6">
                    <div class="bg-gradient-to-r from-indigo-50 to-blue-50 rounded-xl p-6 border border-indigo-200">
                        <h3 class="text-lg font-bold text-indigo-900 mb-4">Filtros de Ventas</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Tipo de filtro -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Filtro</label>
                                <select v-model="ventasFiltros.tipo" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                    <option value="estado">Por Estado</option>
                                    <option value="fecha">Por Rango de Fechas</option>
                                </select>
                            </div>

                            <!-- Filtro por estado -->
                            <div v-if="ventasFiltros.tipo === 'estado'">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                                <select v-model="ventasFiltros.estado" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 capitalize">
                                    <option v-for="estado in estadosVenta" :key="estado" :value="estado">{{ estado }}</option>
                                </select>
                            </div>

                            <!-- Filtro por fechas -->
                            <template v-if="ventasFiltros.tipo === 'fecha'">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Desde</label>
                                    <input type="date" v-model="ventasFiltros.fecha_desde" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Hasta</label>
                                    <input type="date" v-model="ventasFiltros.fecha_hasta" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                </div>
                            </template>
                        </div>

                        <div class="flex space-x-4 mt-6">
                            <button
                                @click="cargarVentas"
                                :disabled="loading"
                                class="flex-1 bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition-all disabled:opacity-50 font-medium shadow-lg hover:shadow-xl"
                            >
                                <span v-if="!loading">Cargar Datos</span>
                                <span v-else>Cargando...</span>
                            </button>
                            <button
                                @click="generarPDFVentas"
                                :disabled="ventasData.length === 0"
                                class="flex-1 bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-6 py-3 rounded-lg hover:from-indigo-700 hover:to-purple-700 transition-all disabled:opacity-50 font-medium shadow-lg hover:shadow-xl flex items-center justify-center space-x-2"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span>Generar PDF</span>
                            </button>
                        </div>
                    </div>

                    <!-- Vista previa -->
                    <div v-if="ventasData.length > 0" class="bg-white rounded-xl border theme-border p-6">
                        <h4 class="text-lg font-bold theme-text mb-4">Vista Previa ({{ ventasData.length }} registros)</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Alumno</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Servicio</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="venta in ventasData.slice(0, 5)" :key="venta.id">
                                        <td class="px-4 py-3 text-sm">{{ venta.id }}</td>
                                        <td class="px-4 py-3 text-sm">{{ venta.alumno_nombre }}</td>
                                        <td class="px-4 py-3 text-sm">{{ venta.servicio_nombre }}</td>
                                        <td class="px-4 py-3 text-sm">Bs {{ parseFloat(venta.monto_total).toFixed(2) }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="px-2 py-1 rounded-full text-xs font-medium capitalize"
                                                  :class="{
                                                      'bg-green-100 text-green-800': venta.estado === 'pagado',
                                                      'bg-yellow-100 text-yellow-800': venta.estado === 'pendiente',
                                                      'bg-orange-100 text-orange-800': venta.estado === 'parcial',
                                                      'bg-red-100 text-red-800': venta.estado === 'cancelado'
                                                  }">
                                                {{ venta.estado }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <p v-if="ventasData.length > 5" class="text-sm text-gray-500 mt-2">
                                Mostrando 5 de {{ ventasData.length }} registros
                            </p>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN ASISTENCIAS -->
                <div v-show="activeTab === 'asistencias'" class="p-6 space-y-6">
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-6 border border-green-200">
                        <h3 class="text-lg font-bold text-green-900 mb-4">Filtros de Asistencias</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Filtro</label>
                                <select v-model="asistenciasFiltros.tipo" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                                    <option value="estado">Por Estado</option>
                                    <option value="fecha">Por Rango de Fechas</option>
                                </select>
                            </div>

                            <div v-if="asistenciasFiltros.tipo === 'estado'">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                                <select v-model="asistenciasFiltros.estado" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 capitalize">
                                    <option v-for="estado in estadosAsistencia" :key="estado" :value="estado">{{ estado }}</option>
                                </select>
                            </div>

                            <template v-if="asistenciasFiltros.tipo === 'fecha'">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Desde</label>
                                    <input type="date" v-model="asistenciasFiltros.fecha_desde" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Hasta</label>
                                    <input type="date" v-model="asistenciasFiltros.fecha_hasta" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                                </div>
                            </template>
                        </div>

                        <div class="flex space-x-4 mt-6">
                            <button
                                @click="cargarAsistencias"
                                :disabled="loading"
                                class="flex-1 bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition-all disabled:opacity-50 font-medium shadow-lg"
                            >
                                <span v-if="!loading">Cargar Datos</span>
                                <span v-else>Cargando...</span>
                            </button>
                            <button
                                @click="generarPDFAsistencias"
                                :disabled="asistenciasData.length === 0"
                                class="flex-1 bg-gradient-to-r from-green-600 to-emerald-600 text-white px-6 py-3 rounded-lg hover:from-green-700 hover:to-emerald-700 transition-all disabled:opacity-50 font-medium shadow-lg flex items-center justify-center space-x-2"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span>Generar PDF</span>
                            </button>
                        </div>
                    </div>

                    <div v-if="asistenciasData.length > 0" class="bg-white rounded-xl border theme-border p-6">
                        <h4 class="text-lg font-bold theme-text mb-4">Vista Previa ({{ asistenciasData.length }} registros)</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Alumno</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="asist in asistenciasData.slice(0, 5)" :key="asist.id">
                                        <td class="px-4 py-3 text-sm">{{ asist.id }}</td>
                                        <td class="px-4 py-3 text-sm">{{ asist.alumno_nombre }}</td>
                                        <td class="px-4 py-3 text-sm">{{ new Date(asist.fecha).toLocaleDateString('es-BO') }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="px-2 py-1 rounded-full text-xs font-medium capitalize"
                                                  :class="{
                                                      'bg-green-100 text-green-800': asist.estado === 'presente',
                                                      'bg-red-100 text-red-800': asist.estado === 'ausente',
                                                      'bg-yellow-100 text-yellow-800': asist.estado === 'tardanza',
                                                      'bg-blue-100 text-blue-800': asist.estado === 'justificado',
                                                      'bg-teal-100 text-teal-800': asist.estado === 'recuperada'
                                                  }">
                                                {{ asist.estado }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <p v-if="asistenciasData.length > 5" class="text-sm text-gray-500 mt-2">
                                Mostrando 5 de {{ asistenciasData.length }} registros
                            </p>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN INSCRIPCIONES -->
                <div v-show="activeTab === 'inscripciones'" class="p-6 space-y-6">
                    <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl p-6 border border-purple-200">
                        <h3 class="text-lg font-bold text-purple-900 mb-4">Filtros de Inscripciones</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Filtro</label>
                                <select v-model="inscripcionesFiltros.tipo" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                                    <option value="estado">Por Estado</option>
                                    <option value="servicio">Por Servicio</option>
                                </select>
                            </div>

                            <div v-if="inscripcionesFiltros.tipo === 'estado'">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                                <select v-model="inscripcionesFiltros.estado" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 capitalize">
                                    <option v-for="estado in estadosInscripcion" :key="estado" :value="estado">{{ estado }}</option>
                                </select>
                            </div>

                            <div v-if="inscripcionesFiltros.tipo === 'servicio'">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Servicio</label>
                                <select v-model="inscripcionesFiltros.servicio" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                                    <option v-for="servicio in serviciosDisponibles" :key="servicio" :value="servicio">{{ servicio }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex space-x-4 mt-6">
                            <button
                                @click="cargarInscripciones"
                                :disabled="loading"
                                class="flex-1 bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition-all disabled:opacity-50 font-medium shadow-lg"
                            >
                                <span v-if="!loading">Cargar Datos</span>
                                <span v-else>Cargando...</span>
                            </button>
                            <button
                                @click="generarPDFInscripciones"
                                :disabled="inscripcionesData.length === 0"
                                class="flex-1 bg-gradient-to-r from-purple-600 to-pink-600 text-white px-6 py-3 rounded-lg hover:from-purple-700 hover:to-pink-700 transition-all disabled:opacity-50 font-medium shadow-lg flex items-center justify-center space-x-2"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span>Generar PDF</span>
                            </button>
                        </div>
                    </div>

                    <div v-if="inscripcionesData.length > 0" class="bg-white rounded-xl border theme-border p-6">
                        <h4 class="text-lg font-bold theme-text mb-4">Vista Previa ({{ inscripcionesData.length }} registros)</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Alumno</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Servicio</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="insc in inscripcionesData.slice(0, 5)" :key="insc.id">
                                        <td class="px-4 py-3 text-sm">{{ insc.id }}</td>
                                        <td class="px-4 py-3 text-sm">{{ insc.alumno_nombre }}</td>
                                        <td class="px-4 py-3 text-sm">{{ insc.servicio_nombre }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="px-2 py-1 rounded-full text-xs font-medium capitalize"
                                                  :class="{
                                                      'bg-green-100 text-green-800': insc.estado === 'activo',
                                                      'bg-gray-100 text-gray-800': insc.estado === 'finalizado',
                                                      'bg-red-100 text-red-800': insc.estado === 'retirado'
                                                  }">
                                                {{ insc.estado }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <p v-if="inscripcionesData.length > 5" class="text-sm text-gray-500 mt-2">
                                Mostrando 5 de {{ inscripcionesData.length }} registros
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>