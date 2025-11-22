<script setup>
import { Head } from '@inertiajs/vue3';
import AlumnoLayout from '../../Layout/AlumnoLayout.vue';
import { useAuth } from '../../composables/useAuth';

const { user } = useAuth();
</script>

<template>
    <AlumnoLayout>
        <Head title="Mi Portal" />

        <!-- Welcome Section -->
        <div class="bg-white rounded-2xl shadow-sm p-6 sm:p-8 mb-6">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        ¡Hola, {{ user?.nombre }}! 👋
                    </h1>
                    <p class="text-gray-600 mt-2">
                        Bienvenido a tu portal estudiantil
                    </p>
                </div>
                <div class="bg-indigo-50 rounded-xl p-4">
                    <p class="text-sm text-indigo-600 font-medium">Grado</p>
                    <p class="text-2xl font-bold text-indigo-900">{{ user?.grado_escolar || 'N/A' }}</p>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <!-- Clases Activas -->
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100 text-sm font-medium">Clases Activas</p>
                        <p class="text-4xl font-bold mt-2">0</p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Asistencia -->
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-100 text-sm font-medium">Asistencia</p>
                        <p class="text-4xl font-bold mt-2">0%</p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Próxima Clase -->
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-purple-100 text-sm font-medium">Próxima Clase</p>
                        <p class="text-lg font-bold mt-2">No programada</p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Próximas Clases -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-900">Próximas Clases</h2>
                    <button class="text-indigo-600 hover:text-indigo-700 text-sm font-medium">
                        Ver todas →
                    </button>
                </div>

                <!-- Empty State -->
                <div class="text-center py-12">
                    <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <p class="mt-4 text-gray-600">No tienes clases programadas</p>
                    <p class="text-sm text-gray-500 mt-1">Tus próximas clases aparecerán aquí</p>
                </div>
            </div>

            <!-- Información del Estudiante -->
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Mi Información</h2>
                
                <div class="space-y-4">
                    <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                        <div class="bg-indigo-100 rounded-full p-2 mr-3">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Nombre Completo</p>
                            <p class="font-medium text-gray-900">{{ user?.nombre }} {{ user?.apellido }}</p>
                        </div>
                    </div>

                    <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                        <div class="bg-blue-100 rounded-full p-2 mr-3">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Cédula de Identidad</p>
                            <p class="font-medium text-gray-900">{{ user?.ci }}</p>
                        </div>
                    </div>

                    <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                        <div class="bg-green-100 rounded-full p-2 mr-3">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Grado Escolar</p>
                            <p class="font-medium text-gray-900">{{ user?.grado_escolar || 'No especificado' }}</p>
                        </div>
                    </div>

                    <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                        <div class="bg-yellow-100 rounded-full p-2 mr-3">
                            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Estado</p>
                            <p class="font-medium text-green-600">{{ user?.estado }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AlumnoLayout>
</template>