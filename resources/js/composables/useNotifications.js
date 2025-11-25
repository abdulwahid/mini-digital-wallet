import { ref } from 'vue';

const notifications = ref([]);

/**
 * Show a notification to the user
 * @param {string} message - The notification message
 * @param {string} type - The type of notification (success, error, warning, info)
 * @param {number} duration - Duration in milliseconds (default: 5000)
 */
export const useNotifications = () => {
    const showNotification = (message, type = 'info', duration = 5000) => {
        const id = Date.now();
        const notification = {
            id,
            message,
            type,
            duration,
        };

        notifications.value.push(notification);

        // Auto-remove notification after duration
        if (duration > 0) {
            setTimeout(() => {
                removeNotification(id);
            }, duration);
        }

        return id;
    };

    const removeNotification = (id) => {
        const index = notifications.value.findIndex(n => n.id === id);
        if (index !== -1) {
            notifications.value.splice(index, 1);
        }
    };

    const clearAll = () => {
        notifications.value = [];
    };

    return {
        notifications,
        showNotification,
        removeNotification,
        clearAll,
    };
};

