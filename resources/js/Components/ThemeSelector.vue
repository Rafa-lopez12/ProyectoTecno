<script setup>
import { ref } from 'vue';
import { useTheme } from '../composables/useTheme';

const { themes, currentTheme, setTheme } = useTheme();
const showMenu = ref(false);

const themeOptions = [
    { value: 'auto', label: 'Automático', icon: '🌓' },
    { value: 'light', label: 'Día', icon: '☀️' },
    { value: 'dark', label: 'Noche', icon: '🌙' },
    { value: 'sunset', label: 'Atardecer', icon: '🌅' }
];

const selectTheme = (theme) => {
    setTheme(theme);
    showMenu.value = false;
};

const getCurrentLabel = () => {
    return themeOptions.find(t => t.value === currentTheme.value)?.label || 'Tema';
};

const getCurrentIcon = () => {
    return themeOptions.find(t => t.value === currentTheme.value)?.icon || '🎨';
};
</script>

<template>
    <div class="relative">
        <button
            @click="showMenu = !showMenu"
            class="theme-button flex items-center space-x-2 px-4 py-2 rounded-lg transition-all hover:scale-105"
        >
            <span class="text-xl">{{ getCurrentIcon() }}</span>
            <span class="hidden md:inline text-sm font-medium">{{ getCurrentLabel() }}</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <!-- Dropdown Menu -->
        <transition name="fade">
            <div v-if="showMenu" 
                 class="absolute right-0 mt-2 w-48 theme-surface rounded-xl shadow-2xl overflow-hidden z-50 border theme-border">
                <div class="py-2">
                    <button
                        v-for="option in themeOptions"
                        :key="option.value"
                        @click="selectTheme(option.value)"
                        class="w-full px-4 py-3 flex items-center space-x-3 hover:bg-opacity-50 transition-colors"
                        :class="currentTheme === option.value ? 'theme-primary-bg' : 'hover:theme-primary-bg'"
                    >
                        <span class="text-xl">{{ option.icon }}</span>
                        <span class="text-sm font-medium theme-text">{{ option.label }}</span>
                        <svg v-if="currentTheme === option.value" 
                             class="w-5 h-5 ml-auto theme-primary-text" 
                             fill="currentColor" 
                             viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        </transition>
    </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.2s, transform 0.2s;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}
</style>