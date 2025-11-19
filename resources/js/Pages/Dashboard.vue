<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '../Layout/AppLayout.vue';
</script>

<template>
    <AppLayout>
        <Head title="Dashboard" />

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <h2 class="text-3xl font-bold mb-4">Dashboard</h2>
                <p class="text-gray-600">¡Bienvenido al sistema de gestión!</p>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
                    <!-- Card Alumnos -->
                    <div class="bg-blue-50 rounded-lg p-6 border-l-4 border-blue-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-blue-600 font-medium">Alumnos</p>
                                <p class="text-3xl font-bold text-blue-700 mt-2">0</p>
                            </div>
                            <svg class="w-12 h-12 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Card Tutores -->
                    <div class="bg-green-50 rounded-lg p-6 border-l-4 border-green-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-green-600 font-medium">Tutores</p>
                                <p class="text-3xl font-bold text-green-700 mt-2">0</p>
                            </div>
                            <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Card Propietarios -->
                    <div class="bg-purple-50 rounded-lg p-6 border-l-4 border-purple-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-purple-600 font-medium">Propietarios</p>
                                <p class="text-3xl font-bold text-purple-700 mt-2">0</p>
                            </div>
                            <svg class="w-12 h-12 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>