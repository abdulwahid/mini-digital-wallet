<template>
    <div class="transfer-form">
        <h2 class="text-2xl font-bold mb-4">Send Money</h2>
        <form @submit.prevent="handleSubmit">
            <div class="mb-4">
                <label for="receiver_id" class="block text-sm font-medium mb-2">
                    Receiver ID
                </label>
                <input
                    id="receiver_id"
                    v-model="form.receiver_id"
                    @blur="validateReceiverId"
                    @input="clearFieldError('receiver_id')"
                    type="number"
                    class="w-full px-3 py-2 border rounded-md transition-colors"
                    :class="{
                        'border-red-500': errors.receiver_id,
                        'border-gray-300': !errors.receiver_id
                    }"
                    placeholder="Enter receiver user ID"
                />
                <div v-if="errors.receiver_id" class="mt-1 text-sm text-red-600">
                    {{ errors.receiver_id }}
                </div>
            </div>

            <div class="mb-4">
                <label for="amount" class="block text-sm font-medium mb-2">
                    Amount
                </label>
                <input
                    id="amount"
                    v-model="form.amount"
                    @blur="validateAmount"
                    @input="clearFieldError('amount')"
                    type="number"
                    step="0.01"
                    min="0.01"
                    class="w-full px-3 py-2 border rounded-md transition-colors"
                    :class="{
                        'border-red-500': errors.amount,
                        'border-gray-300': !errors.amount
                    }"
                    placeholder="Enter amount"
                />
                <div v-if="errors.amount" class="mt-1 text-sm text-red-600">
                    {{ errors.amount }}
                </div>
            </div>

            <div v-if="error" class="mb-4 text-red-600 text-sm">
                {{ error }}
            </div>

            <div v-if="success" class="mb-4 text-green-600 text-sm">
                {{ success }}
            </div>

            <button
                type="submit"
                :disabled="loading || !isFormValid"
                class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
            >
                <svg v-if="loading" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span v-if="loading">Sending...</span>
                <span v-else>Send Money</span>
            </button>
        </form>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { transactionApi } from '../services/api.js';
import { useNotifications } from '../composables/useNotifications.js';

const { showNotification } = useNotifications();

const form = ref({
    receiver_id: '',
    amount: '',
});

const loading = ref(false);
const error = ref('');
const success = ref('');
const errors = ref({
    receiver_id: '',
    amount: '',
});

const isFormValid = computed(() => {
    return !errors.value.receiver_id && !errors.value.amount && form.value.receiver_id && form.value.amount;
});

const validateReceiverId = () => {
    if (!form.value.receiver_id) {
        errors.value.receiver_id = 'Receiver ID is required';
        return false;
    }
    
    const receiverId = parseInt(form.value.receiver_id);
    if (isNaN(receiverId) || receiverId <= 0) {
        errors.value.receiver_id = 'Receiver ID must be a valid positive number';
        return false;
    }
    
    errors.value.receiver_id = '';
    return true;
};

const validateAmount = () => {
    if (!form.value.amount) {
        errors.value.amount = 'Amount is required';
        return false;
    }
    
    const amount = parseFloat(form.value.amount);
    if (isNaN(amount) || amount <= 0) {
        errors.value.amount = 'Amount must be a valid positive number';
        return false;
    }
    
    if (amount < 0.01) {
        errors.value.amount = 'Amount must be at least 0.01';
        return false;
    }
    
    if (amount > 999999999.99) {
        errors.value.amount = 'Amount exceeds the maximum allowed limit (999,999,999.99)';
        return false;
    }
    
    // Validate decimal places (max 2)
    const decimalPlaces = (amount.toString().split('.')[1] || '').length;
    if (decimalPlaces > 2) {
        errors.value.amount = 'Amount can have at most 2 decimal places';
        return false;
    }
    
    errors.value.amount = '';
    return true;
};

const clearFieldError = (field) => {
    if (errors.value[field]) {
        errors.value[field] = '';
    }
};

const handleSubmit = async () => {
    // Clear previous messages
    error.value = '';
    success.value = '';
    
    // Validate all fields
    const isReceiverIdValid = validateReceiverId();
    const isAmountValid = validateAmount();
    
    if (!isReceiverIdValid || !isAmountValid) {
        return;
    }
    
    const receiverId = parseInt(form.value.receiver_id);
    const amount = parseFloat(form.value.amount);
    
    loading.value = true;
    
    try {
        const response = await transactionApi.createTransaction({
            receiver_id: receiverId,
            amount: amount,
        });

        success.value = 'Transaction completed successfully!';
        showNotification('Transaction completed successfully!', 'success');
        
        // Reset form after successful submission
        form.value = {
            receiver_id: '',
            amount: '',
        };
        
        // Emit event for parent component to update balance
        emit('transaction-completed', response.data);
        
    } catch (err) {
        // Enhanced error handling for different error types
        if (err.response?.status === 422) {
            // Validation errors from backend
            const message = err.response?.data?.message;
            const errors = err.response?.data?.errors;
            
            if (errors) {
                // Handle field-specific validation errors
                const firstError = Object.values(errors)[0];
                error.value = Array.isArray(firstError) ? firstError[0] : firstError;
            } else {
                error.value = message || 'Validation failed. Please check your input.';
            }
        } else if (err.response?.status === 401) {
            error.value = 'You are not authenticated. Please log in again.';
        } else if (err.response?.status >= 500) {
            error.value = 'Server error. Please try again later.';
        } else {
            error.value = err.response?.data?.message || 'An error occurred. Please try again.';
        }
        
        // Show error notification
        showNotification(error.value, 'error');
    } finally {
        loading.value = false;
    }
};

const emit = defineEmits(['transaction-completed']);
</script>

<style scoped>
.transfer-form {
    padding: 2rem;
    background: white;
    border-radius: 1rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07), 0 1px 3px rgba(0, 0, 0, 0.06);
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.transfer-form input {
    transition: all 0.2s ease;
}

.transfer-form input:focus {
    outline: none;
    ring: 2px;
    ring-color: #3b82f6;
    border-color: #3b82f6;
}

.transfer-form button {
    transition: all 0.2s ease;
}

.transfer-form button:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}
</style>

