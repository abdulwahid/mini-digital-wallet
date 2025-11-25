import axios from 'axios';

// Create axios instance with base configuration
const api = axios.create({
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

// Add request interceptor to include auth token
api.interceptors.request.use(
    (config) => {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (token) {
            config.headers['X-CSRF-TOKEN'] = token;
        }
        
        // Add Sanctum token from localStorage if available
        const sanctumToken = localStorage.getItem('sanctum_token');
        if (sanctumToken) {
            config.headers['Authorization'] = `Bearer ${sanctumToken}`;
        }
        
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// Add response interceptor for error handling
api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            // Handle unauthorized - clear token and redirect
            localStorage.removeItem('sanctum_token');
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);

// Transaction API methods
export const transactionApi = {
    /**
     * Get all transactions for authenticated user
     */
    getTransactions: (params = {}) => {
        return api.get('/transactions', { params });
    },

    /**
     * Create a new transaction
     */
    createTransaction: (data) => {
        return api.post('/transactions', data);
    },
};

export default api;

