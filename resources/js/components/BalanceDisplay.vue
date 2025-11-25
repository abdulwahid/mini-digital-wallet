<template>
    <div class="balance-display">
        <h2 class="text-2xl font-bold mb-2">Your Balance</h2>
        <div class="text-4xl font-semibold text-green-600">
            ${{ formatCurrency(balance) }}
        </div>
        <div v-if="loading" class="text-sm text-gray-500 mt-2">Loading...</div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { transactionApi } from '../services/api.js';

const balance = ref(0);
const loading = ref(true);

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount);
};

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

