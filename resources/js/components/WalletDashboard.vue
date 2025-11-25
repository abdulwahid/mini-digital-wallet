<template>
    <div class="wallet-dashboard container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">Digital Wallet</h1>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <BalanceDisplay ref="balanceDisplayRef" />
            <TransferForm @transaction-completed="handleTransactionCompleted" />
        </div>
        
        <div class="mt-6">
            <TransactionList
                ref="transactionListRef"
                :user-id="currentUserId"
            />
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import BalanceDisplay from './BalanceDisplay.vue';
import TransferForm from './TransferForm.vue';
import TransactionList from './TransactionList.vue';
import { transactionApi, userApi } from '../services/api.js';

const balanceDisplayRef = ref(null);
const transactionListRef = ref(null);
const currentUserId = ref(null);

const handleTransactionCompleted = async (data) => {
    // Update balance display
    if (balanceDisplayRef.value) {
        balanceDisplayRef.value.updateBalance(data.balance);
    }
    
    // Add new transaction to the list
    if (transactionListRef.value && data.transaction) {
        transactionListRef.value.addTransaction(data.transaction);
    }
    
    // Refresh transactions list to ensure consistency
    if (transactionListRef.value) {
        transactionListRef.value.refresh();
    }
};

const fetchCurrentUser = async () => {
    try {
        const response = await userApi.getCurrentUser();
        currentUserId.value = response.data.id;
    } catch (error) {
        console.error('Error fetching current user:', error);
        // Fallback: try to get from transactions
        try {
            const txResponse = await transactionApi.getTransactions({ per_page: 1 });
            if (txResponse.data.transactions.data.length > 0) {
                const transaction = txResponse.data.transactions.data[0];
                // Determine current user ID from transaction
                currentUserId.value = transaction.sender_id;
            }
        } catch (txError) {
            console.error('Error fetching user from transactions:', txError);
        }
    }
};

onMounted(async () => {
    // Fetch current user ID
    await fetchCurrentUser();
    
    // Transactions will load automatically via TransactionList's onMounted
    // Balance will load automatically via BalanceDisplay's onMounted
});
</script>

<style scoped>
.wallet-dashboard {
    max-width: 1200px;
}
</style>

