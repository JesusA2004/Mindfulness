<!-- src/views/perfil/PerfilUsuario.vue -->
<template>
  <div class="container py-5">
    <div class="card shadow-sm animate__animated animate__fadeIn">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h3 class="mb-0">{{ user.name || 'Usuario' }}</h3>
        <i class="bi bi-hand-thumbs-up" aria-hidden="true"></i>
      </div>

      <div class="card-body">
        <ul class="list-group mb-3">
          <li class="list-group-item d-flex justify-content-between">
            <span><strong>Email:</strong></span>
            <span>{{ user.email || '—' }}</span>
          </li>

          <li class="list-group-item d-flex justify-content-between">
            <span><strong>Rol:</strong></span>
            <span class="text-capitalize">{{ user.rol || '—' }}</span>
          </li>

          <li class="list-group-item d-flex justify-content-between">
            <span><strong>Creado:</strong></span>
            <span>{{ formatDate(user.created_at || user.createdAt) || '—' }}</span>
          </li>

          <li class="list-group-item d-flex justify-content-between">
            <span><strong>Actualizado:</strong></span>
            <span>{{ formatDate(user.updated_at || user.updatedAt) || '—' }}</span>
          </li>
        </ul>

        <!-- Botón visible para cualquier rol: envía correo al backend -->
        <div class="text-end">
          <button
            class="btn btn-warning btn-hover-scale"
            :disabled="sending || !user.email"
            @click="goToChangePassword"
          >
            <span v-if="sending" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            {{ sending ? 'Enviando enlace…' : 'Cambiar contraseña' }}
          </button>
        </div>

        <!-- Mensajes -->
        <p v-if="msg" class="mt-3 small" :class="{'text-success': ok, 'text-danger': !ok}">
          {{ msg }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const user = ref({})
const sending = ref(false)
const msg = ref('')
const ok = ref(false)

onMounted(() => {
  try {
    const raw = localStorage.getItem('user')
    if (!raw) return router.push({ name: 'LoginPage' })
    const parsed = JSON.parse(raw || '{}')
    if (!parsed || !parsed.email) return router.push({ name: 'LoginPage' })
    user.value = parsed
  } catch (e) {
    return router.push({ name: 'LoginPage' })
  }
})

async function goToChangePassword() {
  msg.value = ''
  ok.value = false
  if (!user.value?.email) {
    msg.value = 'No se encontró el correo del usuario.'
    return
  }
  try {
    sending.value = true
    await axios.post(`${process.env.VUE_APP_API_URL}/password/forgot`, {
      email: user.value.email
    })
    ok.value = true
    msg.value = 'Te enviamos un correo con el enlace para cambiar tu contraseña.'
  } catch (e) {
    console.error(e)
    ok.value = false
    // Mensaje del backend (si existe) o genérico
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

<style scoped>
.container {
  max-width: 700px;
  margin: auto;
}

.btn-hover-scale {
  transition: transform .2s ease-in-out, box-shadow .2s ease-in-out;
}
.btn-hover-scale:hover {
  transform: scale(1.05);
  box-shadow: 0 .5rem 1rem rgba(0,0,0,0.15);
}

@media (max-width: 576px) {
  .container { margin: 1rem; }
}
</style>
