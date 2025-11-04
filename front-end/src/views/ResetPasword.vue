<!-- src/views/ResetPassword.vue -->
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
            <div class="login-card shadow-lg animate__animated animate__fadeInUp w-100" :style="cardTiltStyle" ref="cardRef">
              <!-- Encabezado -->
              <div class="text-center mb-4">
                <img
                  class="brand-mark animate__animated animate__fadeInDown"
                  :src="brandSrc"
                  alt="Mindora"
                  width="64"
                  height="64"
                  loading="lazy"
                />
                <h1 class="title-hero h4 fw-extrabold mt-2 mb-1">Restablecer contraseña</h1>
                <p class="subtitle-hero text-muted mb-0">Ingresa tu nueva contraseña para continuar.</p>
              </div>

              <!-- Mensaje de error/éxito -->
              <transition name="fade">
                <div v-if="msg" class="alert py-2 px-3 mb-3" :class="ok ? 'alert-success' : 'alert-danger'">
                  {{ msg }}
                </div>
              </transition>

              <!-- Form -->
              <form @submit.prevent="onSubmit" autocomplete="off" novalidate>
                <!-- email oculto (viene en el enlace) -->
                <input type="hidden" :value="email" />

                <!-- Nueva contraseña -->
                <div class="mb-3">
                  <label class="form-label text-soft">Nueva contraseña</label>
                  <div class="input-group input-group-lg">
                    <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                    <input
                      :type="show ? 'text' : 'password'"
                      class="form-control"
                      v-model.trim="password"
                      minlength="8"
                      required
                      placeholder="Mínimo 8 caracteres"
                      @input="evaluateStrength"
                      aria-label="Nueva contraseña"
                    />
                    <button
                      class="btn btn-outline-secondary password-toggle"
                      type="button"
                      @click="show = !show"
                      :aria-label="show ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                    >
                      <i :class="show ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                    </button>
                  </div>

                  <!-- Indicador fuerza -->
                  <div class="mt-2">
                    <div class="progress strength-progress">
                      <div
                        class="progress-bar"
                        role="progressbar"
                        :style="{ width: strength.percent + '%'}"
                        :class="strength.barClass"
                        :aria-valuenow="strength.percent"
                        aria-valuemin="0"
                        aria-valuemax="100"
                      ></div>
                    </div>
                    <small :class="['d-inline-block mt-1', strength.textClass]">
                      {{ strength.label }}
                    </small>
                  </div>
                </div>

                <!-- Confirmar contraseña -->
                <div class="mb-4">
                  <label class="form-label text-soft">Confirmar contraseña</label>
                  <div class="input-group input-group-lg">
                    <span class="input-group-text"><i class="bi bi-check2-square"></i></span>
                    <input
                      :type="show ? 'text' : 'password'"
                      class="form-control"
                      v-model.trim="password_confirmation"
                      minlength="8"
                      required
                      placeholder="Repite tu contraseña"
                      aria-label="Confirmar contraseña"
                    />
                    <span class="input-group-text" :class="matchClass" title="Coincidencia">
                      <i :class="matchIcon"></i>
                    </span>
                  </div>
                  <small v-if="password_confirmation" :class="['mt-1 d-inline-block', matchTextClass]">
                    {{ passwordsMatch ? 'Las contraseñas coinciden.' : 'Las contraseñas no coinciden.' }}
                  </small>
                </div>

                <!-- Botón -->
                <button class="btn btn-success btn-lg w-100 btn-login" :disabled="loading">
                  <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                  {{ loading ? 'Actualizando…' : 'Actualizar contraseña' }}
                </button>

                <!-- Enlace de apoyo -->
                <div class="text-center mt-3">
                  <router-link class="enlace small" :to="{ name: 'LoginPage' }">
                    <i class="bi bi-arrow-left-short"></i> Volver al inicio de sesión
                  </router-link>
                </div>
              </form>

              <!-- Footer -->
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
import axios from 'axios'
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

/* Recursos visuales igual que login/recuperar */
const heroVideo = new URL('@/assets/media/videoIndex.mp4', import.meta.url).href
const brandSrc  = new URL('@/assets/images/logoDark.png', import.meta.url).href

/* ===== Estado (tu lógica intacta) ===== */
const url = new URL(window.location.href)
const email = ref(url.searchParams.get('email') || '')
const token = ref(url.searchParams.get('token') || '')
const password = ref('')
const password_confirmation = ref('')
const show = ref(false)
const loading = ref(false)
const msg = ref('')
const ok  = ref(false)
const year = new Date().getFullYear()

/* ===== Fuerza / match (igual) ===== */
const passwordsMatch = computed(() =>
  password.value && password_confirmation.value && password.value === password_confirmation.value
)
const matchClass = computed(() => !password_confirmation.value
  ? 'bg-light text-muted' : (passwordsMatch.value ? 'bg-success text-white' : 'bg-danger text-white')
)
const matchIcon = computed(() => !password_confirmation.value
  ? 'bi bi-dot' : (passwordsMatch.value ? 'bi bi-check-lg' : 'bi bi-x-lg')
)
const matchTextClass = computed(() => passwordsMatch.value ? 'text-success' : 'text-danger')

const strength = computed(() => {
  const p = password.value || ''
  let score = 0
  if (p.length >= 8) score += 1
  if (/[A-Z]/.test(p)) score += 1
  if (/[a-z]/.test(p)) score += 1
  if (/\d/.test(p)) score += 1
  if (/[^A-Za-z0-9]/.test(p)) score += 1
  const percent = Math.min(100, (score / 5) * 100)
  let label = 'Muy débil', barClass = 'bg-danger', textClass = 'text-danger'
  if (percent >= 20) { label = 'Débil';      barClass = 'bg-danger';  textClass = 'text-danger' }
  if (percent >= 40) { label = 'Aceptable';  barClass = 'bg-warning'; textClass = 'text-warning' }
  if (percent >= 60) { label = 'Buena';      barClass = 'bg-info';    textClass = 'text-info' }
  if (percent >= 80) { label = 'Fuerte';     barClass = 'bg-success'; textClass = 'text-success' }
  return { percent, label, barClass, textClass }
})
function evaluateStrength(){ /* trigger vacío, misma firma */ }

/* ===== Envío (idéntico a tu flujo) ===== */
async function onSubmit () {
  msg.value = ''
  ok.value = false

  if (!email.value || !token.value) { msg.value = 'Enlace inválido.'; return }
  if ((password.value || '').length < 8) { msg.value = 'La contraseña debe tener al menos 8 caracteres.'; return }
  if (!passwordsMatch.value) { msg.value = 'Las contraseñas no coinciden.'; return }

  try {
    loading.value = true
    await axios.post(`${process.env.VUE_APP_API_URL}/password/reset`, {
      email: email.value,
      token: token.value,
      password: password.value,
      password_confirmation: password_confirmation.value
    })
    ok.value = true
    msg.value = '¡Listo! Tu contraseña fue actualizada.'
    setTimeout(() => window.location.assign('/login'), 800) // o this.$router.push si prefieres
  } catch (err) {
    console.error(err)
    ok.value = false
    msg.value = err?.response?.data?.message || 'No se pudo actualizar. Intenta de nuevo.'
  } finally {
    loading.value = false
  }
}

/* ===== Partículas + parallax + tilt (igual que Recuperar.vue) ===== */
const starsCanvas = ref(null)
const cardRef     = ref(null)
const videoRef    = ref(null)
const heroRef     = ref(null)

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

function handleVisibility () {
  const vid = videoRef.value
  if (!vid) return
  if (document.hidden) vid.pause()
  else vid.play().catch(() => {})
}

/* Tilt */
const tilt = ref({ x: 0, y: 0 })
const cardTiltStyle = computed(() => {
  const maxTilt = 6
  return {
    transform: `perspective(900px) rotateX(${tilt.value.y * maxTilt}deg) rotateY(${tilt.value.x * maxTilt}deg) translateY(-2px)`,
    transition: 'transform .15s ease-out'
  }
})
function onCardMouseMove (e) {
  const card = cardRef.value || e.currentTarget
  const rect = card.getBoundingClientRect()
  const x = (e.clientX - rect.left) / rect.width
  const y = (e.clientY - rect.top) / rect.height
  tilt.value = { x: (x - 0.5) * 2, y: -(y - 0.5) * 2 }
}
function resetCardTilt () { tilt.value = { x: 0, y: 0 } }

/* Parallax ligero en video */
let offParallax = null
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

/* Mount / unmount */
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
</script>

<style>
html, body { height: 100%; background:#070b14; margin: 0; }
</style>
<style scoped src="@/assets/css/Login.css"></style>
