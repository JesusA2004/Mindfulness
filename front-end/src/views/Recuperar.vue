<!-- src/views/auth/Recuperar.vue -->
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
                <h1 class="title-hero h4 fw-extrabold mt-2 mb-1">Recuperar contraseña</h1>
                <p class="subtitle-hero text-muted mb-0">
                  Ingresa el correo asociado a tu cuenta para enviarte el enlace.
                </p>
              </div>

              <form @submit.prevent="submit" novalidate :class="{ 'was-validated': triedSubmit }">
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
                      autocomplete="email"
                      @focus="pulseIcon($event)"
                    />
                    <div class="invalid-feedback">Ingresa un correo válido.</div>
                  </div>
                </div>

                <button
                  ref="sendBtn"
                  class="btn btn-success btn-lg w-100 btn-login animate__animated animate__fadeInUp"
                  type="submit"
                  :disabled="isLoading"
                  @click="ripple"
                >
                  <span v-if="!isLoading"><i class="bi bi-send me-2"></i>Enviar enlace</span>
                  <span v-else><span class="spinner-border spinner-border-sm me-2"></span>Procesando…</span>
                </button>
              </form>

              <div class="text-center mt-3">
                <router-link class="enlace small" :to="{ name: 'LoginPage' }">
                  <i class="bi bi-arrow-left-short"></i> Volver al inicio de sesión
                </router-link>
              </div>

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
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import axios from 'axios'

/* Recursos (idénticos al login) */
const heroVideo = new URL('@/assets/media/videoIndex.mp4', import.meta.url).href
const brandSrc  = new URL('@/assets/images/logoDark.png', import.meta.url).href

/* Estado */
const email = ref('')
const triedSubmit = ref(false)
const isLoading = ref(false)
const year = new Date().getFullYear()

/* API */
const API = (process.env.VUE_APP_API_URL || '').replace(/\/+$/, '')
const forgotUrl = API + '/password/forgot'
// opcional (ver sección backend de abajo). Activar con VUE_APP_STRICT_EMAIL_CHECK=1
const checkEmailUrl = API + '/auth/check-email'
const useStrictCheck = String(process.env.VUE_APP_STRICT_EMAIL_CHECK || '') === '1'

/* Refs DOM / animaciones (mismos helpers del login) */
const starsCanvas = ref(null)
const cardRef     = ref(null)
const videoRef    = ref(null)
const heroRef     = ref(null)
const sendBtn     = ref(null)

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
  const btn = sendBtn.value || e.currentTarget
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
  offParallax = bindParallax()
  paintStars()
  document.addEventListener('visibilitychange', handleVisibility)
})
onBeforeUnmount(() => {
  document.body.classList.remove('has-hero')
  cancelAnimationFrame(starsRAF)
  document.removeEventListener('visibilitychange', handleVisibility)
  if (offParallax) offParallax()
})

/* Submit */
async function submit () {
  triedSubmit.value = true
  if (!email.value) return

  isLoading.value = true
  try {
    // (Opcional) Validación estricta de existencia:
    if (useStrictCheck) {
      const chk = await axios.post(checkEmailUrl, { email: String(email.value).trim() }, { validateStatus: () => true, timeout: 12000 })
      if (chk.status === 404) {
        shakeCard()
        Swal.fire({ icon: 'error', title: 'Correo no encontrado', text: 'No existe una cuenta con ese correo.', confirmButtonColor: '#dc3545' })
        return
      }
      if (chk.status !== 200) {
        shakeCard()
        Swal.fire({ icon: 'error', title: 'Validación no disponible', text: 'Inténtalo de nuevo en unos minutos.', confirmButtonColor: '#dc3545' })
        return
      }
    }

    // Flujo real: enviar enlace (tu controlador ya lo hace)
    const resp = await axios.post(forgotUrl, { email: String(email.value).trim() }, { validateStatus: () => true, timeout: 12000 })
    if (resp.status === 200) {
      Swal.fire({
        icon: 'success',
        title: 'Revisa tu correo',
        text: 'Te enviamos un enlace para restablecer tu contraseña.',
        confirmButtonColor: '#22c55e'
      })
    } else if (resp.status === 422) {
      const msg = (resp?.data?.errors && Object.values(resp.data.errors).flat()[0]) || resp?.data?.message || 'Datos inválidos.'
      shakeCard()
      Swal.fire({ icon: 'warning', title: 'Validación', text: msg, confirmButtonColor: '#f59e0b' })
    } else {
      shakeCard()
      Swal.fire({ icon: 'error', title: 'No se pudo enviar', text: resp?.data?.message || 'Intenta más tarde.', confirmButtonColor: '#dc3545' })
    }
  } catch (e) {
    console.error(e)
    shakeCard()
    Swal.fire({ icon: 'error', title: 'Error de red', text: 'No se pudo contactar al servidor.', confirmButtonColor: '#dc3545' })
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

<!-- Reusa los mismos estilos base del login -->
<style scoped src="@/assets/css/Login.css"></style>
