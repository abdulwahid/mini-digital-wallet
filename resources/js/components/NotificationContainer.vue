<template>
    <div class="fixed top-4 right-4 z-50 space-y-2">
        <div
            v-for="notification in notifications"
            :key="notification.id"
            class="notification-item p-4 rounded-lg shadow-xl max-w-sm backdrop-blur-sm"
            :class="getNotificationClass(notification.type)"
        >
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <p class="font-medium">{{ notification.message }}</p>
                </div>
                <button
                    @click="removeNotification(notification.id)"
                    class="ml-4 text-gray-500 hover:text-gray-700 transition-colors"
                    aria-label="Close notification"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useNotifications } from '../composables/useNotifications.js';

const { notifications, removeNotification } = useNotifications();

const getNotificationClass = (type) => {
    const classes = {
        success: 'bg-green-50 border-2 border-green-500 text-green-800 shadow-green-200',
        error: 'bg-red-50 border-2 border-red-500 text-red-800 shadow-red-200',
        warning: 'bg-yellow-50 border-2 border-yellow-500 text-yellow-800 shadow-yellow-200',
        info: 'bg-blue-50 border-2 border-blue-500 text-blue-800 shadow-blue-200',
    };
    return classes[type] || classes.info;
};
</script>

<style scoped>
.notification-item {
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
</style>

