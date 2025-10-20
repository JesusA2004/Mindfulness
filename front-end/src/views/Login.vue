<template>
  <div class="login-page">
    <section class="login-hero position-relative overflow-hidden" ref="heroRef">
      <!-- Fondo de video -->
      <video
        ref="videoRef"
        class="hero-video position-absolute top-0 start-0 w-100 h-100 z-0 pe-none"
        :src="heroVideo"
        autoplay
        muted
        loop
        playsinline
        preload="metadata"
      ></video>

      <!-- Overlay -->
      <div class="hero-scrim position-absolute top-0 start-0 w-100 h-100 z-1"></div>

      <!-- Partículas -->
      <canvas ref="starsCanvas" class="bg-canvas position-absolute top-0 start-0 z-1" aria-hidden="true"></canvas>

      <!-- Contenido -->
      <div class="container h-100 position-relative z-2">
        <div class="row h-100 align-items-center justify-content-center">
          <div
            class="col-12 col-md-10 col-lg-6 col-xl-5 d-flex justify-content-center"
            @mousemove="onCardMouseMove"
            @mouseleave="resetCardTilt"
          >
            <div class="login-card shadow-lg animate__animated animate__fadeInUp" ref="cardRef">
              <div class="text-center mb-4">
                <img
                  class="brand-mark animate__animated animate__fadeInDown"
                  :src="brandSrc"
                  alt="Mindora"
                  width="64"
                  height="64"
                  loading="lazy"
                />
                <h1 class="title-hero h4 fw-extrabold mt-2 mb-1">Ingresa a tu cuenta</h1>
                <p class="subtitle-hero text-muted mb-0">Bienvenido a Mindora</p>
              </div>

              <form @submit.prevent="login" novalidate class="needs-validation" :class="{ 'was-validated': triedSubmit }">
                <!-- Email -->
                <div class="mb-3">
                  <label for="email" class="form-label">Correo electrónico</label>
                  <div class="input-group input-group-lg">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input
                      v-model.trim="email"
                      type="email"
                      id="email"
                      class="form-control"
                      placeholder="usuario@ejemplo.com"
                      required
                      autocomplete="username"
                      @focus="pulseIcon($event)"
                    />
                    <div class="invalid-feedback">Ingresa un correo válido.</div>
                  </div>
                </div>

                <!-- Password (ahora con inputType/iconClass) -->
                <div class="mb-3">
                  <label for="password" class="form-label">Contraseña</label>
                  <div class="input-group input-group-lg">
                    <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                    <input
                      :type="inputType"
                      v-model="password"
                      id="password"
                      class="form-control"
                      placeholder="Tu contraseña"
                      required
                      minlength="6"
                      autocomplete="current-password"
                      @focus="pulseIcon($event)"
                      @keydown.enter.prevent="login"
                    />
                    <button
                      class="btn btn-outline-secondary password-toggle"
                      type="button"
                      @click="togglePasswordVisibility"
                      :disabled="isLoading"
                      aria-label="Mostrar u ocultar contraseña"
                    >
                      <i :class="iconClass"></i>
                    </button>
                    <div class="invalid-feedback">La contraseña es obligatoria (mínimo 6 caracteres).</div>
                  </div>
                </div>

                <!-- Recordarme / Olvidaste -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="remember" v-model="remember" />
                    <label class="form-check-label enlace" for="remember">Recordarme</label>
                  </div>
                  <router-link class="link-secondary small enlace" to="/recuperar">¿Olvidaste tu contraseña?</router-link>
                </div>

                <!-- Botón -->
                <button
                  ref="loginBtn"
                  class="btn btn-success btn-lg w-100 btn-login animate__animated animate__fadeInUp"
                  type="submit"
                  :disabled="isLoading"
                  @click="ripple"
                >
                  <span v-if="!isLoading"><i class="bi bi-box-arrow-in-right me-2"></i>Entrar</span>
                  <span v-else><span class="spinner-border spinner-border-sm me-2"></span>Validando…</span>
                </button>
              </form>

              <p class="text-center mt-4 mb-0 text-muted small">
                © <span>{{ year }}</span> Mindfulness. Todos los derechos reservados.
              </p>
            </div>
          </div>
        </div>
      </div>

    </section>
  </div>
</template>

<script setup>
import 'animate.css'
import Swal from 'sweetalert2'
import { validarCamposLogin } from '@/assets/js/Login.js'
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

/* Recursos */
const heroVideo = new URL('@/assets/media/videoIndex.mp4', import.meta.url).href
const brandSrc  = new URL('@/assets/images/logoDark.png', import.meta.url).href

/* ===== Estado de login (nueva lógica) ===== */
const email = ref('')
const password = ref('')
const remember = ref(true)
const isLoading = ref(false)
const triedSubmit = ref(false)
const year = new Date().getFullYear()

/* Mostrar/ocultar contraseña */
const isPasswordVisible = ref(false)
const inputType = computed(() => (isPasswordVisible.value ? 'text' : 'password'))
const iconClass = computed(() => (isPasswordVisible.value ? 'bi bi-eye' : 'bi bi-eye-slash'))
const togglePasswordVisibility = () => { isPasswordVisible.value = !isPasswordVisible.value }

/* Router */
const router = useRouter()

/* URL API (respeta VUE_APP_API_URL del proyecto con Vue CLI) */
const API = (process.env.VUE_APP_API_URL || '').replace(/\/+$/, '')

const loginUrl = API + '/auth/login'

/* ===== Refs DOM para la UI que ya tenías ===== */
const starsCanvas = ref(null)
const cardRef     = ref(null)
const videoRef    = ref(null)
const heroRef     = ref(null)
const loginBtn    = ref(null)

/* ===== Helpers de UI existentes (sin cambios) ===== */
function setNavHeightVar () {
  const nav = document.querySelector('.custom-navbar')
  const h = nav ? nav.offsetHeight : 56
  document.documentElement.style.setProperty('--nav-h', `${h}px`)
}
const togglePassword = () => {} // ya no se usa, lo dejamos vacío por compatibilidad de eventos si existían
function pulseIcon (evt) {
  const group = evt.target.closest('.input-group')
  const icon = group?.querySelector('.input-group-text i')
  if (!icon) return
  icon.classList.remove('animate__animated', 'animate__heartBeat')
  void icon.offsetWidth
  icon.classList.add('animate__animated', 'animate__heartBeat')
  setTimeout(() => icon.classList.remove('animate__animated', 'animate__heartBeat'), 650)
}
function onCardMouseMove (e) {
  const card = cardRef.value
  if (!card) return
  const rect = card.getBoundingClientRect()
  const x = e.clientX - rect.left
  const y = e.clientY - rect.top
  const ry = ((x - rect.width / 2) / (rect.width / 2)) * 5.5
  const rx = -((y - rect.height / 2) / (rect.height / 2)) * 5.5
  card.style.transform = `rotateX(${rx}deg) rotateY(${ry}deg) translateY(-2px)`
}
const resetCardTilt = () => { if (cardRef.value) cardRef.value.style.transform = '' }
function bindParallax () {
  const el = heroRef.value
  const vid = videoRef.value
  if (!el || !vid) return () => {}
  const onMove = (e) => {
    if (window.innerWidth < 768) return
    const r = el.getBoundingClientRect()
    const dx = (e.clientX - (r.left + r.width / 2)) / r.width
    const dy = (e.clientY - (r.top + r.height / 2)) / r.height
    vid.style.transform = `scale(1.05) translate(${dx * 10}px, ${dy * 8}px)`
  }
  const onLeave = () => { vid.style.transform = 'scale(1.03) translate(0,0)' }
  el.addEventListener('mousemove', onMove, { passive: true })
  el.addEventListener('mouseleave', onLeave, { passive: true })
  onLeave()
  return () => {
    el.removeEventListener('mousemove', onMove)
    el.removeEventListener('mouseleave', onLeave)
  }
}
function ripple (e) {
  const btn = loginBtn.value || e.currentTarget
  const rect = btn.getBoundingClientRect()
  const circle = document.createElement('span')
  const d = Math.max(rect.width, rect.height)
  circle.style.width = circle.style.height = `${d}px`
  circle.style.left = `${e.clientX - rect.left - d / 2}px`
  circle.style.top  = `${e.clientY - rect.top  - d / 2}px`
  circle.className = 'btn-ripple'
  btn.appendChild(circle)
  setTimeout(() => circle.remove(), 600)
}
function handleVisibility () {
  const vid = videoRef.value
  if (!vid) return
  if (document.hidden) vid.pause()
  else vid.play().catch(() => {})
}
let starsRAF = 0
function paintStars () {
  const canvas = starsCanvas.value
  if (!canvas) return
  const ctx = canvas.getContext('2d')
  const DPR = window.devicePixelRatio || 1
  const resize = () => {
    canvas.width = canvas.clientWidth * DPR
    canvas.height = canvas.clientHeight * DPR
  }
  resize()
  window.addEventListener('resize', resize, { passive: true })
  const stars = Array.from({ length: 90 }).map(() => ({
    x: Math.random() * canvas.width,
    y: Math.random() * canvas.height,
    r: (Math.random() * 1.8 + 0.5) * DPR,
    s: Math.random() * 0.6 + 0.2
  }))
  const draw = () => {
    ctx.clearRect(0, 0, canvas.width, canvas.height)
    ctx.fillStyle = 'rgba(255,255,255,0.85)'
    for (const st of stars) {
      ctx.beginPath(); ctx.arc(st.x, st.y, st.r, 0, Math.PI * 2); ctx.fill()
      st.y -= st.s; if (st.y < -2) st.y = canvas.height + 2
    }
    starsRAF = requestAnimationFrame(draw)
  }
  draw()
}

/* Montaje */
let offParallax = null
onMounted(() => {
  document.body.classList.add('has-hero')
  setNavHeightVar()
  window.addEventListener('resize', setNavHeightVar, { passive: true })
  offParallax = bindParallax()
  paintStars()
  document.addEventListener('visibilitychange', handleVisibility)
})
onBeforeUnmount(() => {
  document.body.classList.remove('has-hero')
  window.removeEventListener('resize', setNavHeightVar)
  cancelAnimationFrame(starsRAF)
  document.removeEventListener('visibilitychange', handleVisibility)
  if (offParallax) offParallax()
})

async function login () {
  triedSubmit.value = true

  // Validación con tu helper
  const { valid, message } = validarCamposLogin(email.value, password.value)
  if (!valid) {
    shakeCard()
    Swal.fire({ icon: 'warning', title: 'Campos inválidos', text: message, confirmButtonColor: '#28a745' })
    return
  }

  isLoading.value = true
  try {
    const resp = await axios.post(loginUrl, { email: email.value, password: password.value })
    const payload = resp.data

    // Si tienes un boot global
    if (typeof window.onLoginSuccess === 'function') {
      window.onLoginSuccess(payload)
    } else {
      // Fallback local
      const accessToken = payload.access_token || payload.token
      const tokenType = payload.token_type || 'Bearer'
      if (accessToken) {
        localStorage.setItem('token', accessToken)
        localStorage.setItem('token_type', tokenType)
        axios.defaults.headers.common.Authorization = `${tokenType} ${accessToken}`
      }
      if (payload.user) localStorage.setItem('user', JSON.stringify(payload.user))
      if (payload.expires_at) localStorage.setItem('expires_at', payload.expires_at)
      if (payload.expires_in) scheduleExpiryPrompt(payload.expires_in)
    }

    const nombreUsuario = payload.user?.name || 'Usuario'
    Swal.fire({
      icon: 'success',
      title: `Hola de nuevo, ${nombreUsuario}`,
      text: 'Tu sesión ha sido iniciada con éxito.',
      confirmButtonColor: '#28a745',
      timer: 1200,
      showConfirmButton: false
    })

    // Redirigir por rol (rutas tipo path para evitar depender de names)
    const rol = String(payload.user?.rol || payload.user?.tipoUsuario || '').toLowerCase()
    const destino =
      rol === 'admin'     ? '/app/admin/dashboard' :
      rol === 'profesor'  ? '/app/profesor/dashboard' :
      rol === 'estudiante'? '/app/estudiante/dashboard' :
      '/'

    // Pequeño delay para que alcance a verse el toast
    setTimeout(() => { router.push(destino) }, 800)

  } catch (err) {
    console.error(err)
    shakeCard()
    let msg = 'El servicio no está disponible. Intenta más tarde.'
    if (err?.response?.data?.error)       msg = err.response.data.error
    else if (err?.response?.data?.message) msg = err.response.data.message
    Swal.fire({ icon: 'error', title: 'Error de acceso', text: msg, confirmButtonColor: '#dc3545' })
  } finally {
    isLoading.value = false
  }
}

/* Shake */
function shakeCard () {
  const card = cardRef.value
  if (!card) return
  card.classList.remove('animate__animated', 'animate__shakeX')
  void card.offsetWidth
  card.classList.add('animate__animated', 'animate__shakeX')
  setTimeout(() => card.classList.remove('animate__animated', 'animate__shakeX'), 650)
}
</script>

<style scoped src="@/assets/css/Login.css"></style>
