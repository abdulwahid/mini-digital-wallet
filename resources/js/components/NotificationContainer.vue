<template>
    <div class="fixed top-4 right-4 z-50 space-y-2">
        <div
            v-for="notification in notifications"
            :key="notification.id"
            class="notification-item p-4 rounded-md shadow-lg max-w-sm"
            :class="getNotificationClass(notification.type)"
        >
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <p class="font-medium">{{ notification.message }}</p>
                </div>
                <button
                    @click="removeNotification(notification.id)"
                    class="ml-4 text-gray-400 hover:text-gray-600"
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
        success: 'bg-green-100 border border-green-400 text-green-700',
        error: 'bg-red-100 border border-red-400 text-red-700',
        warning: 'bg-yellow-100 border border-yellow-400 text-yellow-700',
        info: 'bg-blue-100 border border-blue-400 text-blue-700',
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

