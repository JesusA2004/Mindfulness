import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import store from './store' // Asegúrate de que este path sea correcto

// Estilos
import '@fortawesome/fontawesome-free/css/all.min.css'
import 'bootstrap/dist/css/bootstrap.css'

// Crear app
const app = createApp(App)

app.use(store)      // usar store
app.use(router)     // usar router
app.mount('#app')   // montar app

// JS de Bootstrap (después del mount o al final del archivo)
import 'bootstrap/dist/js/bootstrap'
