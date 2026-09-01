import './bootstrap'
import '../css/app.css'

import { createApp } from 'vue'
import router from './router'
import App from './app.vue'
import axios from './axios'

const app = createApp(App)
app.config.globalProperties.$axios = axios
app.provide('axios', axios)
app.use(router).mount('#app')
