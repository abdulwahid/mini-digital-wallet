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
                class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 disabled:opacity-50"
            >
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
    // Logical mistake 1: Not clearing previous error/success messages
    // Logical mistake 2: Not validating form data before submission
    
    loading.value = true;
    
    try {
        const response = await transactionApi.createTransaction({
            receiver_id: form.value.receiver_id,
            amount: form.value.amount,
        });

        success.value = 'Transaction completed successfully!';
        
        // Emit event for parent component to update balance
        emit('transaction-completed', response.data);
        
    } catch (err) {
        error.value = err.response?.data?.message || 'An error occurred';
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

