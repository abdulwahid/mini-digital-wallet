import { createRouter, createWebHistory } from 'vue-router';

// Wallet routes configuration
const routes = [
    {
        path: '/',
        name: 'home',
        component: () => import('../components/WalletDashboard.vue'),
    },
    {
        path: '/transactions',
        name: 'transactions',
        component: () => import('../components/TransactionList.vue'),
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;

