<template>
    <div class="balance-display">
        <h2 class="text-2xl font-bold mb-2">Your Balance</h2>
        <div class="text-4xl font-semibold text-green-600">
            {{ formatCurrency(balance) }}
        </div>
        <div v-if="loading" class="text-sm text-gray-500 mt-2 flex items-center gap-2">
            <svg class="animate-spin h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Loading...</span>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { transactionApi } from '../services/api.js';
import { formatCurrency } from '../utils/formatters.js';

const balance = ref(0);
const loading = ref(true);

const fetchBalance = async () => {
    try {
        loading.value = true;
        const response = await transactionApi.getTransactions();
        balance.value = response.data.balance;
    } catch (error) {
        console.error('Error fetching balance:', error);
    } finally {
        loading.value = false;
    }
};

const updateBalance = (newBalance) => {
    balance.value = newBalance;
};

onMounted(() => {
    fetchBalance();
});

defineExpose({
    updateBalance,
    fetchBalance,
});
</script>

<style scoped>
.balance-display {
    padding: 1.5rem;
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}
</style>

