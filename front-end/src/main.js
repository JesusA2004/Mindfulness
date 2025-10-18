import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import store from './store'

// === Estilos (orden importa) ===
import '@fortawesome/fontawesome-free/css/all.min.css'
import 'bootstrap/dist/css/bootstrap.min.css'   // 1) Bootstrap primero
import './assets/css/_global.css'               // 2) Tu CSS global después (impone la fuente)

import 'bootstrap/dist/js/bootstrap.bundle.min.js' // JS de Bootstrap (bundle con Popper)

const app = createApp(App)
app.use(store)
app.use(router)
app.mount('#app')