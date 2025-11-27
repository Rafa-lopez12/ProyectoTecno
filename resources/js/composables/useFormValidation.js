// resources/js/composables/useFormValidation.js
import { ref } from 'vue';

export function useFormValidation() {
    const errors = ref({});
    const isValidating = ref(false);

    // Reglas de validación generales
    const rules = {
        // ============================================
        // REGLAS BÁSICAS DE TIPO DE DATO
        // ============================================
        
        required: (value, fieldName) => {
            if (!value || (typeof value === 'string' && value.trim() === '')) {
                return `El campo ${fieldName} es obligatorio`;
            }
            return null;
        },

        string: (value, fieldName) => {
            if (!value) return null;
            if (typeof value !== 'string') {
                return `El campo ${fieldName} debe ser texto`;
            }
            return null;
        },

        integer: (value, fieldName) => {
            if (!value && value !== 0) return null;
            const num = Number(value);
            if (!Number.isInteger(num)) {
                return `El campo ${fieldName} debe ser un número entero`;
            }
            return null;
        },

        numeric: (value, fieldName) => {
            if (!value && value !== 0) return null;
            if (isNaN(value)) {
                return `El campo ${fieldName} debe ser un número`;
            }
            return null;
        },

        // ============================================
        // REGLAS DE LONGITUD
        // ============================================

        minLength: (min) => (value, fieldName) => {
            if (!value) return null;
            if (value.toString().length < min) {
                return `El campo ${fieldName} debe tener al menos ${min} caracteres`;
            }
            return null;
        },

        maxLength: (max) => (value, fieldName) => {
            if (!value) return null;
            if (value.toString().length > max) {
                return `El campo ${fieldName} no puede exceder ${max} caracteres`;
            }
            return null;
        },

        // ============================================
        // REGLAS DE CARACTERES
        // ============================================

        noSpecialChars: (value, fieldName) => {
            if (!value) return null;
            // Solo permite letras, números y espacios
            const regex = /^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s]+$/;
            if (!regex.test(value)) {
                return `El campo ${fieldName} no debe contener caracteres especiales`;
            }
            return null;
        },

        onlyLetters: (value, fieldName) => {
            if (!value) return null;
            // Solo permite letras y espacios
            const regex = /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/;
            if (!regex.test(value)) {
                return `El campo ${fieldName} solo debe contener letras`;
            }
            return null;
        },

        onlyNumbers: (value, fieldName) => {
            if (!value) return null;
            // Solo permite números
            const regex = /^\d+$/;
            if (!regex.test(value)) {
                return `El campo ${fieldName} solo debe contener números`;
            }
            return null;
        },

        alphanumeric: (value, fieldName) => {
            if (!value) return null;
            // Solo permite letras y números (sin espacios)
            const regex = /^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]+$/;
            if (!regex.test(value)) {
                return `El campo ${fieldName} solo debe contener letras y números`;
            }
            return null;
        },

        // ============================================
        // REGLAS ESPECÍFICAS COMUNES
        // ============================================

        email: (value, fieldName) => {
            if (!value) return null;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                return `El campo ${fieldName} debe ser un correo electrónico válido`;
            }
            return null;
        },

        date: (value, fieldName) => {
            if (!value) return null;
            const date = new Date(value);
            if (isNaN(date.getTime())) {
                return `El campo ${fieldName} debe ser una fecha válida`;
            }
            return null;
        },

        // ============================================
        // REGLAS DE RANGO
        // ============================================

        min: (minValue) => (value, fieldName) => {
            if (!value && value !== 0) return null;
            const num = Number(value);
            if (num < minValue) {
                return `El campo ${fieldName} debe ser mayor o igual a ${minValue}`;
            }
            return null;
        },

        max: (maxValue) => (value, fieldName) => {
            if (!value && value !== 0) return null;
            const num = Number(value);
            if (num > maxValue) {
                return `El campo ${fieldName} debe ser menor o igual a ${maxValue}`;
            }
            return null;
        },

        // ============================================
        // REGLAS PERSONALIZADAS
        // ============================================

        custom: (validationFn, errorMessage) => (value, fieldName) => {
            if (!value) return null;
            if (!validationFn(value)) {
                return errorMessage.replace('{field}', fieldName);
            }
            return null;
        }
    };

    // Validar un campo individual
    const validateField = (fieldName, value, validations) => {
        if (!validations || validations.length === 0) {
            delete errors.value[fieldName];
            return true;
        }

        for (const validation of validations) {
            const error = validation(value, fieldName);
            if (error) {
                errors.value[fieldName] = error;
                return false;
            }
        }

        delete errors.value[fieldName];
        return true;
    };

    // Validar todo el formulario
    const validateForm = (formData, validationRules) => {
        isValidating.value = true;
        errors.value = {};
        let isValid = true;

        for (const [fieldName, validations] of Object.entries(validationRules)) {
            const fieldValue = formData[fieldName];
            if (!validateField(fieldName, fieldValue, validations)) {
                isValid = false;
            }
        }

        isValidating.value = false;
        return isValid;
    };

    // Limpiar errores
    const clearErrors = () => {
        errors.value = {};
    };

    // Limpiar error de un campo específico
    const clearFieldError = (fieldName) => {
        delete errors.value[fieldName];
    };

    // Establecer errores desde el backend
    const setBackendErrors = (backendErrors) => {
        if (!backendErrors) return;

        // Laravel devuelve errores como objeto con arrays de mensajes
        for (const [field, messages] of Object.entries(backendErrors)) {
            errors.value[field] = Array.isArray(messages) ? messages[0] : messages;
        }
    };

    return {
        errors,
        isValidating,
        rules,
        validateField,
        validateForm,
        clearErrors,
        clearFieldError,
        setBackendErrors
    };
}