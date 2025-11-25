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
                    type="number"
                    class="w-full px-3 py-2 border rounded-md"
                    placeholder="Enter receiver user ID"
                />
            </div>

            <div class="mb-4">
                <label for="amount" class="block text-sm font-medium mb-2">
                    Amount
                </label>
                <input
                    id="amount"
                    v-model="form.amount"
                    type="number"
                    step="0.01"
                    min="0.01"
                    class="w-full px-3 py-2 border rounded-md"
                    placeholder="Enter amount"
                />
            </div>

            <div v-if="error" class="mb-4 text-red-600 text-sm">
                {{ error }}
            </div>

            <div v-if="success" class="mb-4 text-green-600 text-sm">
                {{ success }}
            </div>

            <button
                type="submit"
                :disabled="loading"
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
import { ref } from 'vue';
import { transactionApi } from '../services/api.js';

const form = ref({
    receiver_id: '',
    amount: '',
});

const loading = ref(false);
const error = ref('');
const success = ref('');

const handleSubmit = async () => {
    // Clear previous messages
    error.value = '';
    success.value = '';
    
    // Validate form data before submission
    if (!form.value.receiver_id || !form.value.amount) {
        error.value = 'Please fill in all fields';
        return;
    }
    
    const receiverId = parseInt(form.value.receiver_id);
    const amount = parseFloat(form.value.amount);
    
    if (isNaN(receiverId) || receiverId <= 0) {
        error.value = 'Receiver ID must be a valid positive number';
        return;
    }
    
    if (isNaN(amount) || amount <= 0) {
        error.value = 'Amount must be a valid positive number';
        return;
    }
    
    if (amount < 0.01) {
        error.value = 'Amount must be at least 0.01';
        return;
    }
    
    loading.value = true;
    
    try {
        const response = await transactionApi.createTransaction({
            receiver_id: receiverId,
            amount: amount,
        });

        success.value = 'Transaction completed successfully!';
        
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
    } finally {
        loading.value = false;
    }
};

const emit = defineEmits(['transaction-completed']);
</script>

<style scoped>
.transfer-form {
    padding: 1.5rem;
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}
</style>

