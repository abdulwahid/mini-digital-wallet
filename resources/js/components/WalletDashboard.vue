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
import { ref, onMounted, onUnmounted } from 'vue';
import BalanceDisplay from './BalanceDisplay.vue';
import TransferForm from './TransferForm.vue';
import TransactionList from './TransactionList.vue';
import { transactionApi, userApi } from '../services/api.js';
import { initializePusher, subscribeToUserChannel, unsubscribeFromUserChannel } from '../services/pusher.js';

const balanceDisplayRef = ref(null);
const transactionListRef = ref(null);
const currentUserId = ref(null);
const pusherChannel = ref(null);
const pusherConnected = ref(false);

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

const initializeRealTimeUpdates = async () => {
    if (!currentUserId.value) {
        return;
    }

    // Get Pusher configuration from environment or window
    const pusherConfig = {
        key: window.PUSHER_APP_KEY || import.meta.env.VITE_PUSHER_APP_KEY,
        cluster: window.PUSHER_APP_CLUSTER || import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1',
    };

    if (!pusherConfig.key) {
        console.warn('Pusher configuration not found. Real-time updates will not work.');
        return;
    }

    try {
        // Initialize Pusher
        const echo = initializePusher(pusherConfig);

        // Subscribe to user's private channel
        pusherChannel.value = subscribeToUserChannel(currentUserId.value, (data) => {
            handleRealTimeTransaction(data);
        });

        // Handle connection events
        echo.connector.pusher.connection.bind('connected', () => {
            pusherConnected.value = true;
            console.log('Pusher connected');
        });

        echo.connector.pusher.connection.bind('disconnected', () => {
            pusherConnected.value = false;
            console.log('Pusher disconnected');
        });

        echo.connector.pusher.connection.bind('error', (err) => {
            console.error('Pusher connection error:', err);
            pusherConnected.value = false;
        });
    } catch (error) {
        console.error('Error initializing Pusher:', error);
    }
};

const handleRealTimeTransaction = (data) => {
    // Update balance based on whether user is sender or receiver
    if (balanceDisplayRef.value) {
        const userBalance = currentUserId.value === data.transaction.sender_id
            ? data.sender_balance
            : data.receiver_balance;
        balanceDisplayRef.value.updateBalance(userBalance);
    }

    // Add transaction to the list
    if (transactionListRef.value && data.transaction) {
        transactionListRef.value.addTransaction(data.transaction);
    }
};

onMounted(async () => {
    // Fetch current user ID
    await fetchCurrentUser();
    
    // Initialize real-time updates
    await initializeRealTimeUpdates();
    
    // Transactions will load automatically via TransactionList's onMounted
    // Balance will load automatically via BalanceDisplay's onMounted
});

onUnmounted(() => {
    // Clean up: unsubscribe from channel
    if (pusherChannel.value) {
        unsubscribeFromUserChannel(pusherChannel.value);
        pusherChannel.value = null;
    }
});
</script>

<style scoped>
.wallet-dashboard {
    max-width: 1200px;
}
</style>

