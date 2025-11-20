<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '../../Layout/AppLayout.vue';
import AlumnosTab from './components/AlumnosTab.vue';
import TutoresTab from './components/TutoresTab.vue';
import SubPropietariosTab from './components/SubPropietariosTab.vue';

const activeTab = ref('alumnos');

const tabs = [
    { id: 'alumnos', name: 'Alumnos', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
    { id: 'tutores', name: 'Tutores', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
    { id: 'subpropietarios', name: 'SubPropietarios', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' }
];
</script>

<template>
    <AppLayout>
        <Head title="Usuarios" />

        <div class="bg-white rounded-lg shadow-sm">
            <!-- Header -->
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <h2 class="text-2xl font-bold text-gray-900">Gestión de Usuarios</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Administra alumnos, tutores y subpropietarios del sistema
                </p>
            </div>

            <!-- Tabs -->
            <div class="border-b border-gray-200">
                <nav class="flex -mb-px overflow-x-auto" aria-label="Tabs">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        :class="[
                            activeTab === tab.id
                                ? 'border-indigo-500 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'group inline-flex items-center px-6 py-4 border-b-2 font-medium text-sm whitespace-nowrap'
                        ]"
                    >
                        <svg
                            :class="[
                                activeTab === tab.id ? 'text-indigo-500' : 'text-gray-400 group-hover:text-gray-500',
                                'mr-2 h-5 w-5'
                            ]"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.icon" />
                        </svg>
                        {{ tab.name }}
                    </button>
                </nav>
            </div>

            <!-- Content Area -->
            <div class="p-6">
                <!-- Alumnos Content -->
                <div v-if="activeTab === 'alumnos'">
                    <AlumnosTab />
                </div>

                <!-- Tutores Content -->
                <div v-if="activeTab === 'tutores'">
                    <TutoresTab />
                </div>

                <!-- SubPropietarios Content -->
                <div v-if="activeTab === 'subpropietarios'">
                    <SubPropietariosTab />
                </div>
            </div>
        </div>
    </AppLayout>
</template>