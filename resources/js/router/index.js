import { createRouter, createWebHistory } from 'vue-router';

// Walet routes configuration
const routes = [
    {
        path: '/',
        name: 'home',
        component: () => import('../components/WalletDashboard.vue'),
    },
    {
        path: '/transactons',
        name: 'transactions',
        component: () => import('../components/TransactionList.vue'),
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;

