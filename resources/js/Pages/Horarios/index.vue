<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '../../Layout/AppLayout.vue';
import { useApi } from '../../composables/useApi';
import AsignarHorarioModal from './components/AsignarHorarioModal.vue';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';

const { horarios, tutores } = useApi();

const listaTutores = ref([]);
const loading = ref(false);
const showAsignarModal = ref(false);
const generandoPdf = ref(false);

const diasSemana = {
    'lunes': 'Lunes',
    'martes': 'Martes',
    'miércoles': 'Miércoles',
    'jueves': 'Jueves',
    'viernes': 'Viernes',
    'sábado': 'Sábado',
    'domingo': 'Domingo'
};

const coloresDia = {
    'lunes': 'bg-blue-50 border-blue-200',
    'martes': 'bg-green-50 border-green-200',
    'miércoles': 'bg-purple-50 border-purple-200',
    'jueves': 'bg-yellow-50 border-yellow-200',
    'viernes': 'bg-pink-50 border-pink-200',
    'sábado': 'bg-indigo-50 border-indigo-200',
    'domingo': 'bg-red-50 border-red-200'
};

const cargarDatos = async () => {
    loading.value = true;
    try {
        const resultTutores = await tutores.getAll();
        
        if (resultTutores.success) {
            const tutoresConHorarios = await Promise.all(
                resultTutores.data.data.map(async (tutor) => {
                    const resultHorarios = await horarios.obtenerHorariosDeTutor(tutor.id);
                    
                    const horariosPorDia = {};
                    if (resultHorarios.success && resultHorarios.data.data) {
                        resultHorarios.data.data.forEach(horario => {
                            const dia = horario.dia_semana.toLowerCase();
                            if (!horariosPorDia[dia]) {
                                horariosPorDia[dia] = [];
                            }
                            horariosPorDia[dia].push(horario);
                        });
                    }
                    
                    return {
                        ...tutor,
                        horariosPorDia
                    };
                })
            );
            
            listaTutores.value = tutoresConHorarios;
        }
    } catch (error) {
        console.error('Error al cargar datos:', error);
    } finally {
        loading.value = false;
    }
};

const handleAsignacionExitosa = () => {
    showAsignarModal.value = false;
    cargarDatos();
};

const eliminarHorario = async (tutorId, horarioId) => {
    if (!confirm('¿Estás seguro de desasignar este horario del tutor?')) {
        return;
    }
    
    const result = await horarios.desasignarTutor(horarioId, tutorId);
    
    if (result.success) {
        cargarDatos();
    } else {
        alert('Error al desasignar horario: ' + (result.error || 'Error desconocido'));
    }
};

const generarPdfDisponibles = async () => {
    generandoPdf.value = true;
    try {
        // Obtener horarios disponibles usando useApi
        const result = await horarios.horariosDisponibles();

        if (!result.success) {
            throw new Error(result.error || 'Error al obtener horarios disponibles');
        }

        const horariosDisponibles = result.data.data;

        if (!horariosDisponibles || horariosDisponibles.length === 0) {
            alert('No hay horarios disponibles en este momento');
            return;
        }

        // Crear PDF con jsPDF
        const doc = new jsPDF();
        
        // Título
        doc.setFontSize(20);
        doc.setTextColor(67, 56, 202); // Indigo
        doc.text('HORARIOS DISPONIBLES', 105, 20, { align: 'center' });
        
        // Fecha de generación
        doc.setFontSize(10);
        doc.setTextColor(100);
        const fechaHora = new Date().toLocaleString('es-BO', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        doc.text(`Generado el ${fechaHora}`, 105, 28, { align: 'center' });
        
        // Extraer tutores únicos y asignar colores
        const tutoresUnicos = [...new Set(horariosDisponibles.map(h => h.tutor_id))];
        const coloresPastel = [
            [255, 200, 200], // Rosa pastel
            [200, 230, 255], // Azul pastel
            [200, 255, 200], // Verde pastel
            [255, 240, 200], // Amarillo pastel
            [230, 200, 255], // Morado pastel
            [255, 220, 180], // Naranja pastel
            [200, 255, 255], // Cyan pastel
            [255, 200, 255], // Magenta pastel
            [220, 255, 220], // Verde claro pastel
            [255, 230, 200], // Durazno pastel
        ];
        
        const tutorColores = {};
        tutoresUnicos.forEach((tutorId, index) => {
            tutorColores[tutorId] = coloresPastel[index % coloresPastel.length];
        });
        
        // Crear leyenda de tutores
        let yPos = 36;
        doc.setFontSize(12);
        doc.setTextColor(0);
        doc.setFont(undefined, 'bold');
        doc.text('Leyenda de Tutores:', 14, yPos);
        yPos += 6;
        
        doc.setFontSize(9);
        doc.setFont(undefined, 'normal');
        
        tutoresUnicos.forEach((tutorId, index) => {
            const tutor = horariosDisponibles.find(h => h.tutor_id === tutorId);
            const color = tutorColores[tutorId];
            
            // Dibujar cuadrado de color
            doc.setFillColor(color[0], color[1], color[2]);
            doc.rect(14, yPos - 3, 4, 4, 'F');
            
            // Nombre del tutor
            doc.setTextColor(0);
            doc.text(tutor.tutor_nombre, 20, yPos);
            
            yPos += 5;
            
            // Si hay muchos tutores, hacer dos columnas
            if (index === Math.floor(tutoresUnicos.length / 2) && tutoresUnicos.length > 6) {
                yPos = 42;
            }
        });
        
        yPos += 8;
        
        // Agrupar horarios por día
        const diasOrden = ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];
        const horariosPorDia = {};
        
        horariosDisponibles.forEach(horario => {
            const dia = horario.dia_semana.toLowerCase();
            if (!horariosPorDia[dia]) {
                horariosPorDia[dia] = [];
            }
            horariosPorDia[dia].push(horario);
        });
        
        // Generar tabla para cada día
        diasOrden.forEach(dia => {
            if (!horariosPorDia[dia]) return;
            
            // Verificar si necesitamos nueva página
            if (yPos > 240) {
                doc.addPage();
                yPos = 20;
            }
            
            // Nombre del día
            doc.setFontSize(14);
            doc.setTextColor(0);
            doc.setFont(undefined, 'bold');
            doc.text(diasSemana[dia], 14, yPos);
            yPos += 8;
            
            // Preparar datos para la tabla con información del tutor
            const tableData = horariosPorDia[dia]
                .sort((a, b) => a.hora_inicio.localeCompare(b.hora_inicio))
                .map(h => [
                    h.hora_inicio,
                    h.hora_fin,
                    h.tutor_nombre
                ]);
            
            // Crear tabla
            autoTable(doc, {
                startY: yPos,
                head: [['Hora Inicio', 'Hora Fin', 'Tutor Disponible']],
                body: tableData,
                theme: 'grid',
                headStyles: { 
                    fillColor: [67, 56, 202],
                    textColor: 255,
                    fontSize: 10,
                    fontStyle: 'bold',
                    halign: 'center'
                },
                bodyStyles: {
                    fontSize: 9,
                    halign: 'center'
                },
                columnStyles: {
                    0: { cellWidth: 35 },
                    1: { cellWidth: 35 },
                    2: { cellWidth: 110 }
                },
                didParseCell: function(data) {
                    // Colorear las filas según el tutor
                    if (data.section === 'body') {
                        const rowIndex = data.row.index;
                        const horario = horariosPorDia[dia]
                            .sort((a, b) => a.hora_inicio.localeCompare(b.hora_inicio))[rowIndex];
                        
                        if (horario) {
                            const color = tutorColores[horario.tutor_id];
                            data.cell.styles.fillColor = color;
                        }
                    }
                },
                margin: { left: 14, right: 14 }
            });
            
            yPos = doc.lastAutoTable.finalY + 10;
        });
        
        // Nota al pie
        if (yPos > 260) {
            doc.addPage();
            yPos = 20;
        }
        
        doc.setFontSize(9);
        doc.setTextColor(100);
        doc.setFont(undefined, 'bold');
        doc.text('Nota: ', 14, yPos);
        doc.setFont(undefined, 'normal');
        doc.text('Los horarios del mismo color pertenecen al mismo tutor.', 28, yPos);
        doc.text('Selecciona todos los horarios de un mismo color para garantizar el mismo tutor en todas las clases.', 14, yPos + 5);
        
        // Guardar PDF
        doc.save(`Horarios_Disponibles_${new Date().toISOString().split('T')[0]}.pdf`);
        
    } catch (error) {
        console.error('Error al generar PDF:', error);
        alert('Error al generar PDF: ' + error.message);
    } finally {
        generandoPdf.value = false;
    }
};

const formatearHora = (hora) => {
    return hora.substring(0, 5);
};

onMounted(() => {
    cargarDatos();
});
</script>

<template>
    <AppLayout>
        <Head title="Horarios de Tutores" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Horarios de Tutores</h2>
                        <p class="mt-1 text-sm text-gray-500">
                            Gestiona los horarios asignados a cada tutor
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <button
                            @click="generarPdfDisponibles"
                            :disabled="generandoPdf"
                            class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring focus:ring-green-300 disabled:opacity-50 disabled:cursor-not-allowed transition"
                        >
                            <svg v-if="!generandoPdf" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            <div v-else class="w-5 h-5 mr-2 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                            {{ generandoPdf ? 'Generando...' : 'PDF Disponibles' }}
                        </button>
                        <button
                            @click="showAsignarModal = true"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring focus:ring-indigo-300 disabled:opacity-25 transition"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Nueva Asignación
                        </button>
                    </div>
                </div>
            </div>

            <!-- Loading -->
            <div v-if="loading" class="text-center py-12">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
                <p class="mt-4 text-gray-600">Cargando horarios...</p>
            </div>

            <!-- Lista de Tutores con sus Horarios -->
            <div v-else class="space-y-6">
                <!-- Mensaje si no hay tutores -->
                <div v-if="listaTutores.length === 0" class="bg-white rounded-lg shadow-sm p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No hay tutores registrados</h3>
                    <p class="mt-1 text-sm text-gray-500">Comienza agregando tutores al sistema.</p>
                </div>

                <!-- Card por cada Tutor -->
                <div
                    v-for="tutor in listaTutores"
                    :key="tutor.id"
                    class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow"
                >
                    <!-- Header del Tutor -->
                    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    <div class="h-12 w-12 rounded-full bg-white flex items-center justify-center">
                                        <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-white">
                                        {{ tutor.nombre }} {{ tutor.apellido }}
                                    </h3>
                                    <p class="text-indigo-100 text-sm">
                                        {{ tutor.email }}
                                    </p>
                                </div>
                            </div>
                            <div class="hidden sm:block">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white text-indigo-600">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ Object.keys(tutor.horariosPorDia).length }} días asignados
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Horarios del Tutor -->
                    <div class="p-6">
                        <div v-if="Object.keys(tutor.horariosPorDia).length === 0" class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">Sin horarios asignados</p>
                        </div>

                        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <!-- Card por cada día -->
                            <div
                                v-for="(horariosDia, dia) in tutor.horariosPorDia"
                                :key="dia"
                                :class="['rounded-lg border-2 p-4 transition-all hover:shadow-md', coloresDia[dia] || 'bg-gray-50 border-gray-200']"
                            >
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="font-bold text-gray-800 flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        {{ diasSemana[dia] || dia }}
                                    </h4>
                                    <span class="text-xs font-medium text-gray-500">
                                        {{ horariosDia.length }} {{ horariosDia.length === 1 ? 'horario' : 'horarios' }}
                                    </span>
                                </div>

                                <div class="space-y-2">
                                    <div
                                        v-for="horario in horariosDia"
                                        :key="horario.id"
                                        class="bg-white rounded-md p-3 border border-gray-200 shadow-sm hover:shadow transition-shadow"
                                    >
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-2">
                                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span class="text-sm font-semibold text-gray-700">
                                                    {{ formatearHora(horario.hora_inicio) }} - {{ formatearHora(horario.hora_fin) }}
                                                </span>
                                            </div>
                                            <button
                                                @click="eliminarHorario(tutor.id, horario.id)"
                                                class="text-red-600 hover:text-red-800 p-1 rounded hover:bg-red-50 transition-colors"
                                                title="Desasignar horario"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para Asignar Horario -->
        <AsignarHorarioModal
            v-if="showAsignarModal"
            @close="showAsignarModal = false"
            @success="handleAsignacionExitosa"
        />
    </AppLayout>
</template>