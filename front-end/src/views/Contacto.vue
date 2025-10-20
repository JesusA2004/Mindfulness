<template>
  <main class="contacto-page">

    <section class="contacto-hero sobre-hero">
      <div class="container position-relative">
        <div class="row align-items-center g-4">
          <div class="col-lg-7">
            <h1 class="hero-title animate__animated animate__fadeInDown">
              Hablemos
            </h1>
            <p class="hero-subtitle animate__animated animate__fadeIn animate__delay-1s">
              Cuéntanos tu caso y te responderemos a la brevedad. También puedes contactarnos por correo o WhatsApp.
            </p>

            <div class="d-flex flex-wrap gap-3 mt-3 animate__animated animate__fadeInUp animate__delay-2s">
              <a href="mailto:contacto@tu-dominio.com" class="btn-pill btn-light-elev">
                <i class="bi bi-envelope me-2"></i> contacto@tu-dominio.com
              </a>
              <a href="https://wa.me/5215555555555" target="_blank" class="btn-pill btn-outline-elev ripple">
                <i class="bi bi-whatsapp me-2"></i> WhatsApp
              </a>
            </div>
          </div>

          <!-- Stats -->
          <div class="col-lg-5">
            <div class="stats-card shadow-lg" data-animate="fade-left">
              <div class="row g-0 text-center align-items-stretch">
                <div class="col-4 border-end">
                  <div class="p-3">
                    <div class="stat-value">
                      <span>24/7</span>
                    </div>
                    <div class="stat-label">Recepción</div>
                  </div>
                </div>
                <div class="col-4 border-end">
                  <div class="p-3">
                    <div class="stat-value">
                      <span class="count" data-target="2">0</span><small> h</small>
                    </div>
                    <div class="stat-label">Tiempo de respuesta</div>
                  </div>
                </div>
                <div class="col-4">
                  <div class="p-3">
                    <div class="stat-value">
                      +<span class="count" data-target="100">0</span>
                    </div>
                    <div class="stat-label">Consultas/mes</div>
                  </div>
                </div>
              </div>
              <div class="small text-muted px-3 pb-3">Valores referenciales</div>
            </div>
          </div>
        </div>
      </div>
      <!-- Glows -->
      <div class="hero-glow"></div>
      <div class="hero-vignette"></div>
      <!-- wave separadora -->
      <div class="wave-sep">
        <svg viewBox="0 0 1440 120" preserveAspectRatio="none" class="w-100">
          <path d="M0,64L72,58.7C144,53,288,43,432,58.7C576,75,720,117,864,117.3C1008,117,1152,75,1296,58.7C1440,43,1584,53,1728,69.3L1728,160L0,160Z" fill="#fff"></path>
        </svg>
      </div>
    </section>

    <!-- CONTENIDO -->
    <section class="contacto-main py-5">
      <div class="container">
        <div class="row g-4 justify-content-center">
          <!-- FORM -->
          <div class="col-12 col-lg-7">
            <div class="card glass p-3 p-lg-4 shadow-sm" data-animate="fade-up">
              <div class="d-flex align-items-center mb-3">
                <i class="bi bi-chat-dots fs-3 text-primary me-2"></i>
                <h2 class="h4 mb-0 fw-semibold text-ink">Envíanos un mensaje</h2>
              </div>

              <div
                v-if="alert.type"
                :class="['alert', alertClass, 'animate__animated', 'animate__fadeIn']"
                role="alert"
              >
                {{ alert.message }}
              </div>

              <form @submit.prevent="onSubmit" novalidate>
                <!-- honeypot -->
                <input type="text" class="d-none" tabindex="-1" autocomplete="off" v-model="form.company" />

                <div class="form-floating mb-3">
                  <input
                    v-model.trim="form.name"
                    type="text"
                    class="form-control"
                    id="name"
                    placeholder="Tu nombre"
                    :class="{ 'is-invalid': errors.name }"
                    maxlength="100"
                    required
                  />
                  <label for="name"><i class="bi bi-person me-1"></i> Nombre completo</label>
                  <div class="invalid-feedback">{{ errors.name }}</div>
                </div>

                <div class="form-floating mb-3">
                  <input
                    v-model.trim="form.email"
                    type="email"
                    class="form-control"
                    id="email"
                    placeholder="usuario@ejemplo.com"
                    :class="{ 'is-invalid': errors.email }"
                    maxlength="120"
                    required
                  />
                  <label for="email"><i class="bi bi-envelope me-1"></i> Correo electrónico</label>
                  <div class="invalid-feedback">{{ errors.email }}</div>
                </div>

                <div class="form-floating mb-3">
                  <input
                    v-model.trim="form.subject"
                    type="text"
                    class="form-control"
                    id="subject"
                    placeholder="¿En qué podemos ayudarte?"
                    :class="{ 'is-invalid': errors.subject }"
                    maxlength="150"
                    required
                  />
                  <label for="subject"><i class="bi bi-pencil-square me-1"></i> Asunto</label>
                  <div class="invalid-feedback">{{ errors.subject }}</div>
                </div>

                <div class="form-floating mb-3">
                  <textarea
                    v-model.trim="form.message"
                    id="message"
                    class="form-control"
                    placeholder="Escribe tu mensaje aquí..."
                    style="height: 180px"
                    :class="{ 'is-invalid': errors.message }"
                    required
                  ></textarea>
                  <label for="message"><i class="bi bi-chat-left-text me-1"></i> Mensaje</label>
                  <div class="invalid-feedback">{{ errors.message }}</div>
                </div>

                <div class="form-check mb-3">
                  <input
                    class="form-check-input"
                    type="checkbox"
                    id="privacy"
                    v-model="form.privacy"
                    :class="{ 'is-invalid': errors.privacy }"
                    required
                  />
                  <label class="form-check-label" for="privacy">
                    Acepto el aviso de privacidad y el tratamiento de mis datos.
                  </label>
                  <div class="invalid-feedback">{{ errors.privacy }}</div>
                </div>

                <div class="d-grid">
                  <button class="btn-lg btn-primary-elev ripple" type="submit" :disabled="loading">
                    <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    {{ loading ? 'Enviando…' : 'Enviar mensaje' }}
                  </button>
                </div>

                <div class="form-note text-muted small mt-3">
                  Guardamos tu nombre y correo localmente para agilizar tu próxima consulta.
                </div>
              </form>
            </div>
          </div>

          <!-- CONTACT CARD -->
          <div class="col-12 col-lg-5">
            <div class="card p-3 p-lg-4 shadow-sm h-100 contact-card" data-animate="fade-right">
              <h3 class="h5 mb-3 fw-semibold text-ink"><i class="bi bi-geo-alt me-2 text-primary"></i> Información de contacto</h3>
              <ul class="list-unstyled mb-4">
                <li class="d-flex align-items-start mb-2 text-ink-2">
                  <i class="bi bi-telephone text-primary me-2"></i>
                  <span>+52 55 5555 5555</span>
                </li>
                <li class="d-flex align-items-start mb-2 text-ink-2">
                  <i class="bi bi-envelope-open text-primary me-2"></i>
                  <span>contacto@tu-dominio.com</span>
                </li>
                <li class="d-flex align-items-start mb-2 text-ink-2">
                  <i class="bi bi-clock text-primary me-2"></i>
                  <span>Lunes a Viernes: 9:00 – 18:00</span>
                </li>
                <li class="d-flex align-items-start text-ink-2">
                  <i class="bi bi-geo me-2 text-primary"></i>
                  <span>Cuernavaca, Morelos, MX</span>
                </li>
              </ul>

              <!-- MAPA: OpenStreetMap (iframe) con altura garantizada -->
              <div class="map-wrap rounded border" data-animate="fade-up">
                <iframe
                  class="map-iframe"
                  src="https://www.openstreetmap.org/export/embed.html?bbox=-99.300%2C18.85%2C-99.10%2C18.99&layer=mapnik&marker=18.920%2C-99.200"
                  style="border:0"
                  loading="lazy"
                  referrerpolicy="no-referrer-when-downgrade"
                ></iframe>
                <a
                  class="map-link small text-decoration-none"
                  href="https://www.openstreetmap.org/?mlat=18.920&mlon=-99.200#map=13/18.920/-99.200"
                  target="_blank"
                  rel="noopener"
                >
                  Ver en OpenStreetMap
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>
</template>

<script setup>
import { reactive, ref, onMounted, onBeforeUnmount, computed } from 'vue'
import axios from 'axios'
import 'animate.css'

/* ======= Integración con NAVBAR FIJO ======= */
onMounted(() => {
  document.body.classList.add('has-hero')

  // asegura --nav-h correcta
  requestAnimationFrame(() => {
    const nav = document.querySelector('.custom-navbar')
    if (nav) document.documentElement.style.setProperty('--nav-h', nav.offsetHeight + 'px')
  })

  // Animaciones por dirección
  const items = document.querySelectorAll('[data-animate]')
  const io = new IntersectionObserver((entries) => {
    entries.forEach((e) => {
      if (!e.isIntersecting) return
      const dir = e.target.getAttribute('data-animate') || 'fade-up'
      const delay = +e.target.getAttribute('data-delay') || 0
      const base = ['animate__animated']
      const fx = dir === 'fade-left'
        ? 'animate__fadeInLeft'
        : dir === 'fade-right'
        ? 'animate__fadeInRight'
        : 'animate__fadeInUp'
      setTimeout(() => e.target.classList.add(...base, fx), delay)
      io.unobserve(e.target)
    })
  }, { threshold: 0.15 })
  items.forEach((el) => io.observe(el))

  // CountUp cuando stats visible
  const counters = document.querySelectorAll('.count')
  const ioStats = new IntersectionObserver((entries) => {
    entries.forEach((e) => {
      if (!e.isIntersecting) return
      const el = e.target
      const target = Number(el.getAttribute('data-target') || '0')
      animateCount(el, target, 900)
      ioStats.unobserve(el)
    })
  }, { threshold: 0.6 })
  counters.forEach((c) => ioStats.observe(c))

  // Parallax suave del hero
  const hero = document.querySelector('.contacto-hero')
  const onScroll = () => {
    const y = window.scrollY * 0.25
    hero.style.setProperty('--hero-shift', Math.min(y, 120) + 'px')
  }
  window.addEventListener('scroll', onScroll, { passive: true })
  onScroll()

  // Limpieza
  onBeforeUnmount(() => {
    document.body.classList.remove('has-hero')
    window.removeEventListener('scroll', onScroll)
    items.forEach((el) => io.unobserve(el))
    counters.forEach((c) => ioStats.unobserve(c))
  })
})

/* ======= Estado ======= */
const form = reactive({
  name: localStorage.getItem('contact_name') || '',
  email: localStorage.getItem('contact_email') || '',
  subject: '',
  message: '',
  privacy: false,
  company: '' // honeypot
})
const errors = reactive({ name: '', email: '', subject: '', message: '', privacy: '' })
const loading = ref(false)
const alert = reactive({ type: '', message: '' })
const alertClass = computed(() =>
  alert.type === 'success' ? 'alert-success' : alert.type === 'error' ? 'alert-danger' : 'alert-secondary'
)

/* ======= Validaciones ======= */
function validate() {
  let ok = true
  errors.name = errors.email = errors.subject = errors.message = errors.privacy = ''

  if (!form.name || form.name.length < 3) { errors.name = 'Ingresa tu nombre completo.'; ok = false }
  const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/i
  if (!emailRe.test(form.email)) { errors.email = 'Ingresa un correo válido.'; ok = false }
  if (!form.subject || form.subject.length < 4) { errors.subject = 'Escribe un asunto descriptivo.'; ok = false }
  if (!form.message || form.message.length < 10) { errors.message = 'Tu mensaje debe tener al menos 10 caracteres.'; ok = false }
  if (!form.privacy) { errors.privacy = 'Debes aceptar el aviso de privacidad.'; ok = false }
  if (form.company && form.company.trim().length > 0) { ok = false; setAlert('error', 'No pudimos procesar tu solicitud.') }
  return ok
}

/* ======= Submit ======= */
async function onSubmit() {
  if (loading.value) return
  if (!validate()) { scrollToFirstError(); return }

  try {
    loading.value = true
    setAlert('', '')

    localStorage.setItem('contact_name', form.name)
    localStorage.setItem('contact_email', form.email)

    const base = (process.env.VUE_APP_API_BASE || process.env.VUE_APP_API_URL || '').replace(/\/+$/, '')
    const url = base ? `${base}/contacto` : '/api/contacto'

    await axios.post(url, {
      nombre: form.name,
      email: form.email,
      asunto: form.subject,
      mensaje: form.message
    })

    setAlert('success', '¡Tu mensaje ha sido enviado correctamente!')
    animateFlash('.contacto-main .card.glass')

    form.subject = ''
    form.message = ''
    form.privacy = false
  } catch (e) {
    setAlert('error', 'Ocurrió un problema al enviar. Inténtalo de nuevo.')
  } finally {
    loading.value = false
  }
}

/* ======= Helpers UI ======= */
function setAlert(type, message) {
  alert.type = type
  alert.message = message
  if (type) setTimeout(() => { alert.type = ''; alert.message = '' }, 6000)
}
function scrollToFirstError() {
  const field = document.querySelector('.is-invalid')
  if (field) {
    field.focus({ preventScroll: true })
    field.scrollIntoView({ behavior: 'smooth', block: 'center' })
  }
}
function animateFlash(selector) {
  const el = document.querySelector(selector)
  if (!el) return
  el.classList.remove('animate__animated', 'animate__headShake')
  void el.offsetWidth
  el.classList.add('animate__animated', 'animate__headShake')
}
function animateCount(el, target, duration = 800) {
  const start = 0
  const t0 = performance.now()
  const step = (now) => {
    const p = Math.min((now - t0) / duration, 1)
    el.textContent = Math.floor(start + (target - start) * easeOutCubic(p))
    if (p < 1) requestAnimationFrame(step)
  }
  requestAnimationFrame(step)
}
function easeOutCubic(x) { return 1 - Math.pow(1 - x, 3) }
</script>

<style scoped src="@/assets/css/Contacto.css"></style>