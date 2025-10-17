// src/main.js
import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import store from './store'

// === Estilos (el orden importa) ===
import '@fortawesome/fontawesome-free/css/all.min.css'
import 'bootstrap/dist/css/bootstrap.min.css'     // 1) Bootstrap primero
import './assets/css/_global.css'                 // 2) Tu CSS global

// JS de Bootstrap (bundle con Popper)
import 'bootstrap/dist/js/bootstrap.bundle.min.js'

// Axios + interceptores globales
import axios from 'axios'
import './axios'
import Swal from 'sweetalert2'

// === Configuración base de Axios ===
axios.defaults.baseURL = process.env.VUE_APP_API_URL

// === Utilidad: programar aviso de expiración del token (60s antes) ===
function scheduleExpiryPrompt (expiresInSec) {
  // Guardamos el timer en window para poder limpiarlo/reusarlo en cualquier lugar
  window.clearTimeout(window.__mf_expiry_timer)
  const lead = Math.max(0, (expiresInSec - 60) * 1000)

  window.__mf_expiry_timer = setTimeout(async () => {
    const res = await Swal.fire({
      icon: 'info',
      title: 'Tu sesión está por expirar',
      text: '¿Deseas continuar en la sesión?',
      showCancelButton: true,
      confirmButtonText: 'Sí, continuar',
      cancelButtonText: 'Salir',
      allowOutsideClick: false
    })

    if (res.isConfirmed) {
      try {
        const refreshUrl =
          process.env.VUE_APP_API_URL + '/auth/refresh'
        const { data } = await axios.post(
          refreshUrl,
          {},
          { headers: { Authorization: `Bearer ${localStorage.getItem('token')}` } }
        )

        // Actualiza almacenamiento
        localStorage.setItem('token', data.access_token)
        localStorage.setItem('token_type', data.token_type)
        localStorage.setItem('jti', data.jti || '')
        localStorage.setItem('expires_at', data.expires_at || '')

        // Reprograma el próximo aviso
        if (data.expires_in) scheduleExpiryPrompt(data.expires_in)

        Swal.fire({ icon: 'success', title: 'Sesión renovada', timer: 1200, showConfirmButton: false })
      } catch (e) {
        // Si falla el refresh, limpia y redirige a login
        localStorage.clear()
        router.push('/login')
      }
    } else {
      // Usuario decide salir
      localStorage.clear()
      router.push('/login')
    }
  }, lead)
}

// Exponer para poder reutilizar desde otros módulos si lo necesitas
window.scheduleExpiryPrompt = scheduleExpiryPrompt

// Al iniciar la app, si ya hay una sesión con expires_at, reprogramar el aviso
;(function rearmExpiryOnBoot () {
  const expiresAt = localStorage.getItem('expires_at')
  if (!expiresAt) return
  const diffMs = new Date(expiresAt).getTime() - Date.now()
  if (diffMs > 0) {
    const expiresInSec = Math.floor(diffMs / 1000)
    scheduleExpiryPrompt(expiresInSec)
  }
})()

const app = createApp(App)

// Hacer axios accesible como this.$axios (opcional)
app.config.globalProperties.$axios = axios

app.use(store)
app.use(router)

// Esperar a que el router esté listo para evitar condiciones de carrera en montado
router.isReady().then(() => app.mount('#app'))
