<!-- src/views/ResetPassword.vue -->
<template>
  <section class="reset-wrapper d-flex align-items-center justify-content-center">
    <div class="overlay"></div>

    <main
      class="container px-3 px-sm-4 py-5"
      style="max-width: 640px;"
    >
      <div
        class="card glass shadow-lg border-0 animate__animated animate__fadeInDown"
        @mousemove="onMouseMove"
        @mouseleave="onMouseLeave"
        :style="cardTiltStyle"
      >
        <div class="card-body p-4 p-sm-5">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
              <h1 class="h3 fw-bold mb-1">Restablecer contraseña</h1>
              <p class="text-secondary mb-0">Ingresa tu nueva contraseña para continuar.</p>
            </div>
            <i class="bi bi-shield-lock fs-2 text-primary"></i>
          </div>

          <!-- Mensaje de error/éxito -->
          <transition name="fade">
            <div v-if="msg" class="alert py-2 px-3 mb-3" :class="ok ? 'alert-success' : 'alert-danger'">
              {{ msg }}
            </div>
          </transition>

          <form @submit.prevent="onSubmit" autocomplete="off" novalidate>
            <!-- Campo oculto: email (NO se muestra al usuario) -->
            <input type="hidden" :value="email" />

            <!-- Nueva contraseña -->
            <div class="mb-3">
              <label class="form-label">Nueva contraseña</label>
              <div class="input-group input-group-lg">
                <input
                  :type="show ? 'text' : 'password'"
                  class="form-control form-control-lg"
                  v-model.trim="password"
                  minlength="8"
                  required
                  placeholder="Mínimo 8 caracteres"
                  @input="evaluateStrength"
                  aria-label="Nueva contraseña"
                />
                <button
                  class="btn btn-outline-secondary"
                  type="button"
                  @click="show = !show"
                  :aria-label="show ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                >
                  <i :class="show ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                </button>
              </div>

              <!-- Indicador de fuerza -->
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
              <label class="form-label">Confirmar contraseña</label>
              <div class="input-group input-group-lg">
                <input
                  :type="show ? 'text' : 'password'"
                  class="form-control form-control-lg"
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

            <!-- Botón enviar -->
            <button
              class="btn btn-primary btn-lg w-100 btn-raise"
              :disabled="loading"
            >
              <span
                v-if="loading"
                class="spinner-border spinner-border-sm me-2"
                role="status"
                aria-hidden="true"
              ></span>
              {{ loading ? 'Actualizando…' : 'Actualizar contraseña' }}
            </button>

            <!-- Enlaces de apoyo -->
            <div class="text-center mt-3">
              <router-link class="link-secondary small hover-underline" :to="{ name: 'LoginPage' }">
                Volver al inicio de sesión
              </router-link>
            </div>
          </form>
        </div>
      </div>
    </main>
  </section>
</template>

<script>
import axios from 'axios'

export default {
  name: 'ResetPassword',
  data () {
    const url = new URL(window.location.href)
    return {
      email: url.searchParams.get('email') || '',
      token: url.searchParams.get('token') || '',
      password: '',
      password_confirmation: '',
      show: false,
      loading: false,
      msg: '',
      ok: false,

      // Tilt effect
      tilt: { x: 0, y: 0 }
    }
  },
  computed: {
    passwordsMatch () {
      return this.password && this.password_confirmation && this.password === this.password_confirmation
    },
    matchClass () {
      if (!this.password_confirmation) return 'bg-light text-muted'
      return this.passwordsMatch ? 'bg-success text-white' : 'bg-danger text-white'
    },
    matchIcon () {
      if (!this.password_confirmation) return 'bi bi-dot'
      return this.passwordsMatch ? 'bi bi-check-lg' : 'bi bi-x-lg'
    },
    matchTextClass () {
      return this.passwordsMatch ? 'text-success' : 'text-danger'
    },
    strength () {
      // Cálculo básico de fuerza
      const p = this.password || ''
      let score = 0
      if (p.length >= 8) score += 1
      if (/[A-Z]/.test(p)) score += 1
      if (/[a-z]/.test(p)) score += 1
      if (/\d/.test(p)) score += 1
      if (/[^A-Za-z0-9]/.test(p)) score += 1

      const percent = Math.min(100, (score / 5) * 100)
      let label = 'Muy débil'
      let barClass = 'bg-danger'
      let textClass = 'text-danger'
      if (percent >= 20) { label = 'Débil'; barClass = 'bg-danger'; textClass = 'text-danger' }
      if (percent >= 40) { label = 'Aceptable'; barClass = 'bg-warning'; textClass = 'text-warning' }
      if (percent >= 60) { label = 'Buena'; barClass = 'bg-info'; textClass = 'text-info' }
      if (percent >= 80) { label = 'Fuerte'; barClass = 'bg-success'; textClass = 'text-success' }

      return { percent, label, barClass, textClass }
    },
    cardTiltStyle () {
      const maxTilt = 6 // deg
      return {
        transform: `perspective(900px) rotateX(${this.tilt.y * maxTilt}deg) rotateY(${this.tilt.x * maxTilt}deg)`,
        transition: 'transform .15s ease-out'
      }
    }
  },
  methods: {
    evaluateStrength () {
      // disparador para recomputar; la lógica está en computed
    },
    async onSubmit () {
      this.msg = ''
      this.ok = false

      if (!this.email || !this.token) {
        this.msg = 'Enlace inválido.'
        return
      }
      if (this.password.length < 8) {
        this.msg = 'La contraseña debe tener al menos 8 caracteres.'
        return
      }
      if (!this.passwordsMatch) {
        this.msg = 'Las contraseñas no coinciden.'
        return
      }

      try {
        this.loading = true
        await axios.post(`${process.env.VUE_APP_API_URL}/password/reset`, {
          email: this.email,                    // oculto, viene en el enlace
          token: this.token,
          password: this.password,
          password_confirmation: this.password_confirmation
        })
        this.ok = true
        this.msg = '¡Listo! Tu contraseña fue actualizada.'
        // Pequeño delay para que el usuario alcance a leer
        setTimeout(() => this.$router.push({ name: 'LoginPage' }), 800)
      } catch (err) {
        console.error(err)
        this.ok = false
        this.msg = err?.response?.data?.message || 'No se pudo actualizar. Intenta de nuevo.'
      } finally {
        this.loading = false
      }
    },
    onMouseMove (e) {
      const card = e.currentTarget
      const rect = card.getBoundingClientRect()
      const x = (e.clientX - rect.left) / rect.width
      const y = (e.clientY - rect.top) / rect.height
      this.tilt.x = (x - 0.5) * 2 // -1 .. 1
      this.tilt.y = -(y - 0.5) * 2
    },
    onMouseLeave () {
      this.tilt = { x: 0, y: 0 }
    }
  }
}
</script>

<style scoped>
/* ===== Fondo con imagen (tú pones la URL) ===== */
.reset-wrapper {
  position: relative;
  min-height: 100vh;
  background: var(--reset-bg, url('/img/tu-fondo.jpg')) center/cover no-repeat fixed;
}
.overlay {
  position: absolute; inset: 0;
  background: radial-gradient(1200px 600px at 10% 10%, rgba(255,255,255,.25), rgba(255,255,255,0)),
              linear-gradient(180deg, rgba(0,0,0,.28), rgba(0,0,0,.3));
  backdrop-filter: blur(1px);
}

/* ===== Card glass ===== */
.glass {
  border-radius: 1.25rem;
  background: rgba(255, 255, 255, 0.72);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border: 1px solid rgba(255,255,255,0.55);
  transition: transform .25s ease, box-shadow .25s ease;
}
.glass:hover {
  transform: translateY(-4px);
  box-shadow: 0 1.25rem 2rem rgba(0,0,0,.15);
}

/* ===== Botón hover-raise ===== */
.btn-raise {
  transition: transform .15s ease, box-shadow .2s ease, filter .2s ease;
}
.btn-raise:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 .75rem 1.25rem rgba(13,110,253,.25);
  filter: brightness(1.02);
}

/* ===== Barra de fuerza ===== */
.strength-progress {
  height: .5rem;
  background: rgba(0,0,0,.075);
  border-radius: .5rem;
  overflow: hidden;
}

/* ===== Animaciones de transición ===== */
.fade-enter-active, .fade-leave-active {
  transition: opacity .2s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

/* ===== Utilidades ===== */
.hover-underline {
  position: relative;
  text-decoration: none;
}
.hover-underline::after {
  content: '';
  position: absolute;
  left: 0; bottom: -2px;
  width: 100%; height: 2px;
  background: currentColor;
  transform: scaleX(0);
  transform-origin: left;
  transition: transform .2s ease;
}
.hover-underline:hover::after { transform: scaleX(1); }

@media (max-width: 576px) {
  .glass { border-radius: 1rem; }
}
</style>
