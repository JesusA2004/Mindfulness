// src/main.js
import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import store from './store'

// === Estilos (orden importa) ===
import '@fortawesome/fontawesome-free/css/all.min.css'
import 'bootstrap/dist/css/bootstrap.min.css'
import './assets/css/_global.css'
import 'bootstrap/dist/js/bootstrap.bundle.min.js'

import axios from 'axios'
import Swal from 'sweetalert2'

// ===================== Axios BASE =====================
axios.defaults.baseURL = (process.env.VUE_APP_API_URL || '').replace(/\/+$/, '')

// Si ya hay token guardado, ponlo en headers
const bootToken = localStorage.getItem('token')
if (bootToken && bootToken !== 'undefined' && bootToken !== 'null') {
  axios.defaults.headers.common.Authorization = `Bearer ${bootToken}`
}

// ===================== Helpers JWT ====================
function base64UrlDecode (str) {
  try {
    const pad = (s) => s + '='.repeat((4 - (s.length % 4)) % 4)
    const b64 = pad(str.replace(/-/g, '+').replace(/_/g, '/'))
    return atob(b64)
  } catch { return null }
}
function decodePayload (token) {
  try {
    const part = token.split('.')[1]
    const json = base64UrlDecode(part)
    return json ? JSON.parse(json) : null
  } catch { return null }
}
function msToExpiry (token) {
  const p = decodePayload(token)
  if (!p?.exp) return 0
  return (p.exp * 1000) - Date.now()
}

// ================== Timers de sesión ==================
let promptTimer = null
let expiryTimer = null
let isRefreshing = false
let waiters = []

function clearTimers () {
  if (promptTimer) clearTimeout(promptTimer)
  if (expiryTimer) clearTimeout(expiryTimer)
  promptTimer = null
  expiryTimer = null
}

async function doLogout () {
  clearTimers()
  try {
    // usa el token actual por si los defaults ya cambiaron
    const t = localStorage.getItem('token')
    await axios.post('/auth/logout', {}, t ? { headers: { Authorization: `Bearer ${t}` } } : {})
  } catch (_) {
    // si el token ya expiró, igual continuamos
  } finally {
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    localStorage.removeItem('expires_at')
    delete axios.defaults.headers.common.Authorization
    if (typeof window.destroyEcho === 'function') window.destroyEcho()
    router.replace({ name: 'LoginPage' })
  }
}

async function refreshToken () {
  if (isRefreshing) {
    return new Promise((resolve, reject) => waiters.push({ resolve, reject }))
  }
  isRefreshing = true
  try {
    const { data } = await axios.post('/auth/refresh')
    const newToken = data.access_token
    localStorage.setItem('token', newToken)
    if (data.expires_at) localStorage.setItem('expires_at', data.expires_at)
    axios.defaults.headers.common.Authorization = `Bearer ${newToken}`
    // Reprograma de nuevo (2 min antes)
    scheduleExpiryPromptFromToken(newToken, 120)

    waiters.forEach(w => w.resolve(newToken)); waiters = []
    isRefreshing = false
    return newToken
  } catch (e) {
    waiters.forEach(w => w.reject(e)); waiters = []
    isRefreshing = false
    await doLogout()
    throw e
  }
}

// Programa aviso 2 min antes usando el JWT
function scheduleExpiryPromptFromToken (token, advanceSec = 120) {
  clearTimers()
  const ms = msToExpiry(token)
  if (ms <= 0) { doLogout(); return }

  const showIn = Math.max(ms - advanceSec * 1000, 0)

  // 1) Mostrar aviso 2 min antes
  promptTimer = setTimeout(async () => {
    const result = await Swal.fire({
      title: 'Tu sesión está por expirar',
      text: '¿Deseas continuar usando la aplicación?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, continuar',
      cancelButtonText: 'Cerrar sesión',
      allowOutsideClick: false,
      allowEscapeKey: false,
      reverseButtons: true,
      timer: advanceSec * 1000,
      timerProgressBar: true,
    })

    if (result.isConfirmed) {
      try { await refreshToken() } catch {}
    } else if (result.dismiss === Swal.DismissReason.cancel) {
      // ⛔ usuario eligió cerrar sesión → inmediato
      await doLogout()
      return
    }
    // Si cierra por timer/escape/outside → no hacer nada aquí; el hard timer cerrará
  }, showIn)

  // 2) Al expirar exactamente, salir si no se renovó
  expiryTimer = setTimeout(() => {
    doLogout()
  }, ms)
}

// Llama esto tras login exitoso (desde tu componente de Login)
function onLoginSuccess (data) {
  const { access_token, user, expires_at } = data
  localStorage.setItem('token', access_token)
  if (user) localStorage.setItem('user', JSON.stringify(user))
  if (expires_at) localStorage.setItem('expires_at', expires_at)
  axios.defaults.headers.common.Authorization = `Bearer ${access_token}`
  scheduleExpiryPromptFromToken(access_token, 120)
}

// (Opcional) si recargas y ya existe token, reprograma timers
function bootExistingSession () {
  const t = localStorage.getItem('token')
  if (t && t !== 'undefined' && t !== 'null') {
    scheduleExpiryPromptFromToken(t, 120)
  }
}

// Exponer helpers globales (ya los usa tu SidebarShell vía window.scheduleExpiryPrompt)
window.onLoginSuccess = onLoginSuccess
window.scheduleExpiryPrompt = function (expiresInSec) {
  clearTimers()
  const advanceSec = 120
  const showIn = Math.max((expiresInSec - advanceSec) * 1000, 0)

  promptTimer = setTimeout(async () => {
    const result = await Swal.fire({
      title: 'Tu sesión está por expirar',
      text: '¿Deseas continuar usando la aplicación?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, continuar',
      cancelButtonText: 'Cerrar sesión',
      allowOutsideClick: false,
      allowEscapeKey: false,
      reverseButtons: true,
      timer: advanceSec * 1000,
      timerProgressBar: true
    })
    if (result.isConfirmed) {
      try { await refreshToken() } catch {}
    } else if (result.dismiss === Swal.DismissReason.cancel) {
      await doLogout()
      return
    }
  }, showIn)

  // Hard timer relativo a expiresInSec
  expiryTimer = setTimeout(() => { doLogout() }, expiresInSec * 1000)
}

// ================== Interceptor Axios (auto refresh 1 vez) ==================
axios.interceptors.response.use(
  r => r,
  async err => {
    const status = err?.response?.status
    const original = err?.config || {}
    if (status === 401 && !original._retry) {
      original._retry = true
      try {
        const newToken = await refreshToken()
        original.headers = original.headers || {}
        original.headers.Authorization = `Bearer ${newToken}`
        return axios(original)
      } catch {
        // refreshToken ya hizo logout si falla
      }
    }
    return Promise.reject(err)
  }
)

// Boot de sesión si ya hay token
bootExistingSession()

// ================== Mount app ==================
const app = createApp(App)
app.use(store)
app.use(router)
app.mount('#app')
