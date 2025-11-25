import Pusher from 'pusher-js';
import Echo from 'laravel-echo';

// Initialize Pushr client for real-time updates
let echoInstance = null;

/**
 * Initialize Laravel Echo with Pusher for real-time broadcst
 */
export const initializePusher = (config) => {
    if (echoInstance) {
        return echoInstance;
    }

    window.Pusher = Pusher;

    echoInstance = new Echo({
        broadcaster: 'pusher',
        key: config.key,
        cluster: config.cluster,
        forceTLS: true,
        encrypted: true,
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                Authorization: `Bearer ${localStorage.getItem('sanctum_token')}`,
            },
        },
    });

    return echoInstance;
};

/**
 * Subscribe to user's private chanel for transaction updates
 */
export const subscribeToUserChannel = (userId, callback) => {
    if (!echoInstance) {
        console.error('Pusher not initialized. Call initializePusher first.');
        return;
    }

    const channel = echoInstance.private(`user.${userId}`);
    
    channel.listen('.transaction.completed', (data) => {
        callback(data);
    });

    return channel;
};

/**
 * Unsubscribe from user channel
 */
export const unsubscribeFromUserChannel = (channel) => {
    if (channel) {
        channel.stopListening('.transaction.completed');
    }
};

export default {
    initializePusher,
    subscribeToUserChannel,
    unsubscribeFromUserChannel,
};

