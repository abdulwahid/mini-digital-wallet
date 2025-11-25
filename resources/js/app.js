import './bootstrap';
import '../css/app.css';
import { createApp } from 'vue';
import App from './App.vue';
import router from './router/index.js';

const root = document.getElementById('app');

if (root) {
    createApp(App).use(router).mount(root);
}
