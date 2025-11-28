import { ref, watch, onMounted } from 'vue';

const currentTheme = ref(localStorage.getItem('theme') || 'auto');

export function useTheme() {
    const themes = {
        light: {
            name: 'Día',
            colors: {
                primary: '#6366F1',
                secondary: '#8B5CF6',
                accent: '#EC4899',
                background: '#F9FAFB',
                surface: '#FFFFFF',
                text: '#111827',
                textSecondary: '#6B7280',
                border: '#E5E7EB',
                success: '#10B981',
                warning: '#F59E0B',
                danger: '#EF4444',
            }
        },
        dark: {
            name: 'Noche',
            colors: {
                primary: '#818CF8',
                secondary: '#A78BFA',
                accent: '#F472B6',
                background: '#111827',
                surface: '#1F2937',
                text: '#F9FAFB',
                textSecondary: '#9CA3AF',
                border: '#374151',
                success: '#34D399',
                warning: '#FBBF24',
                danger: '#F87171',
            }
        },
        sunset: {
            name: 'Atardecer',
            colors: {
                primary: '#F59E0B',
                secondary: '#EF4444',
                accent: '#EC4899',
                background: '#FEF3C7',
                surface: '#FEF9E7',
                text: '#78350F',
                textSecondary: '#92400E',
                border: '#FDE68A',
                success: '#10B981',
                warning: '#F59E0B',
                danger: '#DC2626',
            }
        }
    };

    const applyTheme = (themeName) => {
        const theme = themes[themeName];
        if (!theme) return;

        const root = document.documentElement;
        Object.entries(theme.colors).forEach(([key, value]) => {
            root.style.setProperty(`--color-${key}`, value);
        });

        // Aplicar clase al body para estilos adicionales
        document.body.className = `theme-${themeName}`;
        localStorage.setItem('theme', themeName);
        currentTheme.value = themeName;
    };

    const getAutoTheme = () => {
        const hour = new Date().getHours();
        
        if (hour >= 6 && hour < 12) {
            return 'light'; // Mañana
        } else if (hour >= 12 && hour < 18) {
            return 'light'; // Tarde
        } else if (hour >= 18 && hour < 20) {
            return 'sunset'; // Atardecer
        } else {
            return 'dark'; // Noche
        }
    };

    const setTheme = (themeName) => {
        if (themeName === 'auto') {
            const autoTheme = getAutoTheme();
            applyTheme(autoTheme);
        } else {
            applyTheme(themeName);
        }
        currentTheme.value = themeName;
    };

    const initTheme = () => {
        const savedTheme = localStorage.getItem('theme') || 'auto';
        setTheme(savedTheme);
    };

    // Watch para cambios automáticos cada minuto si está en modo auto
    let autoInterval = null;
    watch(currentTheme, (newTheme) => {
        if (autoInterval) {
            clearInterval(autoInterval);
            autoInterval = null;
        }

        if (newTheme === 'auto') {
            // Revisar cada minuto si cambió la hora
            autoInterval = setInterval(() => {
                const autoTheme = getAutoTheme();
                applyTheme(autoTheme);
            }, 60000); // 1 minuto
        }
    });

    return {
        themes,
        currentTheme,
        setTheme,
        initTheme,
        getAutoTheme
    };
}