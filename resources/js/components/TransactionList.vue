<template>
    <div class="transaction-list">
        <h2 class="text-2xl font-bold mb-4">Transactions</h2>
        
        <div v-if="loading" class="text-center py-8 text-gray-500">
            <svg class="animate-spin h-8 w-8 text-gray-400 mx-auto mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <div>Loading transactions...</div>
        </div>
        
        <div v-else-if="error" class="text-red-600 text-sm mb-4">
            {{ error }}
        </div>
        
        <div v-else-if="transactions.length === 0" class="text-center py-8 text-gray-500">
            No transactions found.
        </div>
        
        <div v-else class="space-y-3">
            <div
                v-for="transaction in transactions"
                :key="transaction.id"
                class="transaction-item p-4 border rounded-md"
                :class="getTransactionClass(transaction)"
            >
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <div class="font-semibold">
                            <span v-if="isOutgoing(transaction)">Sent to</span>
                            <span v-else>Received from</span>
                            {{ getOtherUser(transaction).name }}
                        </div>
                        <div class="text-sm text-gray-500 mt-1">
                            {{ formatDate(transaction.created_at) }}
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="font-bold" :class="getAmountClass(transaction)">
                            <span v-if="isOutgoing(transaction)">-</span>
                            <span v-else>+</span>
                            {{ formatCurrency(transaction.amount) }}
                        </div>
                        <div v-if="isOutgoing(transaction)" class="text-xs text-gray-500 mt-1">
                            Fee: {{ formatCurrency(transaction.commission_fee) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div v-if="hasMorePages" class="mt-4 text-center">
            <button
                @click="loadMore"
                :disabled="loadingMore"
                class="px-4 py-2 bg-gray-200 rounded-md hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 mx-auto"
            >
                <svg v-if="loadingMore" class="animate-spin h-4 w-4 text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span v-if="loadingMore">Loading...</span>
                <span v-else>Load More</span>
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { transactionApi } from '../services/api.js';
import { formatCurrency, formatDate } from '../utils/formatters.js';

const props = defineProps({
    userId: {
        type: Number,
        required: true,
    },
});

const transactions = ref([]);
const loading = ref(true);
const loadingMore = ref(false);
const error = ref('');
const currentPage = ref(1);
const hasMorePages = ref(false);

const isOutgoing = (transaction) => {
    return transaction.sender_id === props.userId;
};

const getOtherUser = (transaction) => {
    return isOutgoing(transaction) ? transaction.receiver : transaction.sender;
};

const getTransactionClass = (transaction) => {
    return isOutgoing(transaction)
        ? 'bg-red-50 border-red-200'
        : 'bg-green-50 border-green-200';
};

const getAmountClass = (transaction) => {
    return isOutgoing(transaction) ? 'text-red-600' : 'text-green-600';
};

const fetchTransactions = async (page = 1) => {
    try {
        if (page === 1) {
            loading.value = true;
        } else {
            loadingMore.value = true;
        }
        error.value = '';

        const response = await transactionApi.getTransactions({ page });
        const data = response.data;

        if (page === 1) {
            transactions.value = data.transactions.data;
        } else {
            transactions.value = [...transactions.value, ...data.transactions.data];
        }

        currentPage.value = data.transactions.current_page;
        hasMorePages.value = data.transactions.current_page < data.transactions.last_page;
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to load transactions';
    } finally {
        loading.value = false;
        loadingMore.value = false;
    }
};

const loadMore = () => {
    if (hasMorePages.value && !loadingMore.value) {
        fetchTransactions(currentPage.value + 1);
    }
};

const addTransaction = (transaction) => {
    // Add new transaction to the beginning of the list
    transactions.value.unshift(transaction);
};

onMounted(() => {
    fetchTransactions();
});

defineExpose({
    addTransaction,
    refresh: () => fetchTransactions(1),
});
</script>

<style scoped>
.transaction-list {
    padding: 1.5rem;
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.transaction-item {
    transition: transform 0.2s;
}

.transaction-item:hover {
    transform: translateX(4px);
}
</style>

