import './bootstrap';
import '../css/app.css';
import { createApp } from 'vue';
import App from './App.vue';

const root = document.getElementById('app');

if (root) {
    createApp(App).mount(root);
}
