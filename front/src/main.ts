import './assets/main.css'

import {createApp} from 'vue'
import App from './App.vue'
import router from './router'
import {storeAuthentification} from "@/stores/storeAuthentification.ts";

await storeAuthentification.init();
const app = createApp(App)
app.use(router)
app.mount('#app')

