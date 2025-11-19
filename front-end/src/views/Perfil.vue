<!-- src/views/perfil/PerfilUsuario.vue -->
<template>
  <div class="perfil-page container py-5">
    <div class="perfil-card card animate__animated animate__fadeInUp">
      <!-- Header -->
      <div class="card-header perfil-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
          <div class="avatar-wrapper">
            <img
              v-if="user.urlFotoPerfil"
              :src="user.urlFotoPerfil"
              alt="Foto de perfil"
              class="avatar-img"
            >
            <div v-else class="avatar-fallback">
              {{ initials }}
            </div>
          </div>
          <div>
            <h3 class="mb-1">{{ displayName }}</h3>
            <p class="mb-0 small text-light opacity-75">
              <i class="bi bi-person-badge me-1"></i>
              {{ user.matricula || 'Sin matrícula' }}
            </p>
          </div>
        </div>
      </div>

      <div class="card-body">
        <!-- Fila superior: info general + puntos/rol -->
        <div class="row g-4 mb-4">
          <div class="col-md-8">
            <div class="info-block h-100">
              <h5 class="info-title">
                <i class="bi bi-person-lines-fill me-2"></i>
                Información general
              </h5>
              <ul class="list-unstyled mb-0">
                <li class="info-item">
                  <span class="info-label">
                    <i class="bi bi-envelope me-1"></i> Email
                  </span>
                  <span class="info-value">
                    {{ user.email || '—' }}
                  </span>
                </li>

                <li class="info-item">
                  <span class="info-label">
                    <i class="bi bi-gender-ambiguous me-1"></i> Sexo
                  </span>
                  <span class="info-value">{{ personaSexo || '—' }}</span>
                </li>

                <li class="info-item">
                  <span class="info-label">
                    <i class="bi bi-calendar-heart me-1"></i> Fecha de nacimiento
                  </span>
                  <span class="info-value">
                    {{ formatDateOnlyPersona || '—' }}
                  </span>
                </li>

                <li class="info-item">
                  <span class="info-label">
                    <i class="bi bi-telephone me-1"></i> Teléfono
                  </span>
                  <span class="info-value">
                    {{ personaTelefono || '—' }}
                  </span>
                </li>

                <li class="info-item align-items-start">
                  <span class="info-label">
                    <i class="bi bi-diagram-3 me-1"></i> Cohorte(s)
                  </span>
                  <span class="info-value text-end">
                    <span v-if="cohortesDisplay.length">
                      <span
                        v-for="(c, idx) in cohortesDisplay"
                        :key="idx"
                        class="badge cohorte-pill me-1 mb-1"
                      >
                        <i class="bi bi-people me-1"></i>{{ c }}
                      </span>
                    </span>
                    <span v-else>—</span>
                  </span>
                </li>
              </ul>
            </div>
          </div>

          <!-- Columna derecha: puntos sólo si es estudiante -->
          <div class="col-md-4">
            <div
              v-if="isEstudiante"
              class="info-block puntos-card h-100 text-center"
            >
              <h5 class="info-title mb-3">
                <i class="bi bi-stars me-2"></i>
                Puntos de canjeo
              </h5>
              <div class="puntos-value">
                {{ puntos }}
              </div>
              <p class="small text-muted mb-2">
                <i class="bi bi-trophy me-1"></i>
                Suma puntos completando técnicas y actividades.
              </p>
            </div>

            <div
              v-else
              class="info-block rol-card h-100 text-center"
            >
              <h5 class="info-title mb-3">
                <i class="bi bi-person-workspace me-2"></i>
                Rol actual
              </h5>
              <p class="mb-1 fw-semibold">{{ rolLabel }}</p>
              <p class="small text-muted mb-0">
                El sistema de puntos de canjeo está disponible para usuarios con rol
                <strong>estudiante</strong>.
              </p>
            </div>
          </div>
        </div>

        <!-- Fila inferior: detalles cuenta + seguridad -->
        <div class="row g-4">
          <div class="col-md-6">
            <div class="info-block">
              <h5 class="info-title">
                <i class="bi bi-card-list me-2"></i>
                Detalles de cuenta
              </h5>
              <ul class="list-group list-group-flush perfil-list">

                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <span class="label-pill">
                    <i class="bi bi-person-check me-1"></i> Rol
                  </span>
                  <span class="text-capitalize">
                    {{ rolLabel }}
                  </span>
                </li>

                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <span class="label-pill">
                    <i class="bi bi-calendar-plus me-1"></i> Creado
                  </span>
                  <span>{{ formatDate(user.created_at || user.createdAt) || '—' }}</span>
                </li>

                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <span class="label-pill">
                    <i class="bi bi-calendar-check me-1"></i> Actualizado
                  </span>
                  <span>{{ formatDate(user.updated_at || user.updatedAt) || '—' }}</span>
                </li>
              </ul>
            </div>
          </div>

          <div class="col-md-6 d-flex flex-column justify-content-between">
            <div class="info-block flex-grow-1 d-flex flex-column">
              <h5 class="info-title">
                <i class="bi bi-shield-lock me-2"></i>
                Seguridad de la cuenta
              </h5>
              <p class="small text-muted mb-3">
                Mantén tu cuenta protegida actualizando tu contraseña de forma periódica.
              </p>

              <div class="mt-auto text-end">
                <button
                  class="btn btn-warning btn-hover-scale btn-change-pass"
                  :disabled="sending || !user.email"
                  @click="goToChangePassword"
                >
                  <span
                    v-if="sending"
                    class="spinner-border spinner-border-sm me-2"
                    role="status"
                    aria-hidden="true"
                  ></span>
                  <i v-else class="bi bi-lock-fill me-2"></i>
                  {{ sending ? 'Enviando enlace…' : 'Cambiar contraseña' }}
                </button>
              </div>

              <p
                v-if="msg"
                class="mt-3 small"
                :class="{'text-success': ok, 'text-danger': !ok}"
              >
                {{ msg }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <div class="card-footer text-center small text-muted perfil-footer">
        <i class="bi bi-info-circle me-1"></i>
        Los datos de tu perfil se sincronizan automáticamente con Mindora.
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const user = ref({})
const sending = ref(false)
const msg = ref('')
const ok = ref(false)

const API = (process.env.VUE_APP_API_URL || '').replace(/\/+$/, '')

// ====== Carga inicial ======
onMounted(async () => {
  try {
    const token = localStorage.getItem('token')
    if (!token) return router.push({ name: 'LoginPage' })

    // snapshot opcional mientras llega el backend
    const rawUser = localStorage.getItem('user')
    if (rawUser) {
      try {
        const parsed = JSON.parse(rawUser)
        if (parsed && parsed.email) user.value = parsed
      } catch (_) {}
    }

    const tokenType = (localStorage.getItem('token_type') || 'Bearer').trim()
    const res = await axios.get(`${API}/auth/user-profile`, {
      headers: {
        Authorization: `${tokenType} ${token}`,
        Accept: 'application/json'
      }
    })

    const payload = res.data?.user || res.data?.data || res.data || {}
    user.value = payload
    localStorage.setItem('user', JSON.stringify(user.value))
  } catch (e) {
    console.error('Error cargando perfil:', e)
    return router.push({ name: 'LoginPage' })
  }
})

// ====== Computeds ======
const persona = computed(() => user.value.persona || user.value.personaData || null)

const displayName = computed(() => {
  if (persona.value && (persona.value.nombre || persona.value.apellidoPaterno)) {
    const parts = [
      persona.value.nombre,
      persona.value.apellidoPaterno,
      persona.value.apellidoMaterno
    ].filter(Boolean)
    return parts.join(' ')
  }
  return user.value.name || 'Usuario'
})

const initials = computed(() => {
  const base = displayName.value || ''
  const parts = base.split(' ').filter(Boolean)
  if (!parts.length) return 'U'
  const first = parts[0]?.[0] || ''
  const second = parts[1]?.[0] || ''
  return (first + second).toUpperCase()
})

const personaTelefono = computed(() => persona.value?.telefono || '')
const personaSexo = computed(() => persona.value?.sexo || '')
const personaFechaNac = computed(() => persona.value?.fechaNacimiento || '')

const formatDateOnlyPersona = computed(() => {
  if (!personaFechaNac.value) return ''
  const d = new Date(personaFechaNac.value)
  if (isNaN(d)) return personaFechaNac.value
  return d.toLocaleDateString('es-MX', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  })
})

const cohortesDisplay = computed(() => {
  if (!persona.value || typeof persona.value.cohorte === 'undefined') return []
  const c = persona.value.cohorte
  if (Array.isArray(c)) return c
  if (typeof c === 'string' && c.trim() !== '') return [c.trim()]
  return []
})

const isEstudiante = computed(() => (user.value.rol || '').toLowerCase() === 'estudiante')

const puntos = computed(() => {
  if (!isEstudiante.value) return 0
  const raw = user.value.puntosCanjeo ?? user.value.puntos ?? 0
  const n = Number(raw)
  return Number.isNaN(n) ? 0 : n
})

// meta de ejemplo solo para estudiantes
const metaPuntos = computed(() => (isEstudiante.value ? 500 : 0))

const rolLabel = computed(() => {
  const r = (user.value.rol || '').toLowerCase()
  if (r === 'estudiante') return 'Estudiante'
  if (r === 'profesor') return 'Profesor'
  if (r === 'admin') return 'Administrador'
  return user.value.rol || '—'
})

const estatusLabel = computed(() => {
  const e = (user.value.estatus || '').toLowerCase()
  if (e === 'activo') return 'Activo'
  if (e === 'bajasistema') return 'Baja del sistema'
  if (e === 'bajatemporal') return 'Baja temporal'
  return user.value.estatus || '—'
})

// ====== Acciones ======
async function goToChangePassword() {
  msg.value = ''
  ok.value = false
  if (!user.value?.email) {
    msg.value = 'No se encontró el correo del usuario.'
    return
  }
  try {
    sending.value = true
    await axios.post(`${API}/password/forgot`, { email: user.value.email })
    ok.value = true
    msg.value = 'Te enviamos un correo con el enlace para cambiar tu contraseña.'
  } catch (e) {
    console.error(e)
    ok.value = false
    msg.value = e?.response?.data?.message || 'No se pudo enviar el correo. Intenta más tarde.'
  } finally {
    sending.value = false
  }
}

function formatDate(iso) {
  if (!iso) return ''
  const d = new Date(iso)
  if (isNaN(d)) return ''
  return d.toLocaleString('es-MX', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>

<style src="@/assets/css/Perfil.css" scoped></style>
