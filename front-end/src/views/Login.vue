<template>
  <div class="login-page d-flex justify-content-center align-items-center">
    <div class="overlay"></div>
    <div class="login-card p-4 shadow-sm">
      <h2 class="text-center mb-4">Ingresa a tu cuenta <span class="wave">👋</span></h2>
      <form @submit.prevent="login" novalidate>
        <div class="mb-3 position-relative">
          <label for="email" class="form-label">Correo electrónico</label>
          <input
            v-model="email"
            type="email"
            id="email"
            class="form-control"
            placeholder="usuario@ejemplo.com"
            required
          />
        </div>

        <div class="mb-3 position-relative">
          <label for="password" class="form-label">Contraseña</label>
          <div class="input-group">
            <input
              v-model="password"
              :type="inputType"
              id="password"
              class="form-control"
              placeholder="••••••••"
              required
            />
            <span class="input-group-text password-toggle" @click="togglePasswordVisibility" role="button">
              <!-- Si usas Bootstrap Icons cambia estas clases a 'bi bi-eye/bi-eye-slash' -->
              <i :class="iconClass"></i>
            </span>
          </div>
        </div>

        <button
          type="submit"
          class="btn btn-success w-100 btn-login d-flex justify-content-center align-items-center"
          :disabled="isLoading"
        >
          <span v-if="!isLoading">Ingresar</span>
          <span v-else class="spinner-border spinner-border-sm text-white"></span>
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import Swal from 'sweetalert2'
import { validarCamposLogin } from '@/assets/js/Login.js'
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const isPasswordVisible = ref(false)
const inputType = computed(() => (isPasswordVisible.value ? 'text' : 'password'))
// Si usas Bootstrap Icons, cambia a: const iconClass = computed(() => isPasswordVisible.value ? 'bi bi-eye' : 'bi bi-eye-slash')
const iconClass = computed(() => (isPasswordVisible.value ? 'bx bx-show' : 'bx bx-hide'))
const togglePasswordVisibility = () => { isPasswordVisible.value = !isPasswordVisible.value }

const email = ref('')
const password = ref('')
const isLoading = ref(false)

const router = useRouter()

const API = (process.env.VUE_APP_API_URL || '').replace(/\/+$/, '')
const loginUrl   = API + '/auth/login'

const login = async () => {
  isLoading.value = true

  const { valid, message } = validarCamposLogin(email.value, password.value)
  if (!valid) {
    Swal.fire({ icon: 'warning', title: 'Campos inválidos', text: message, confirmButtonColor: '#28a745' })
    isLoading.value = false
    return
  }

  try {
    const resp = await axios.post(loginUrl, { email: email.value, password: password.value })
    const payload = resp.data

    // Si tu main.js expuso onLoginSuccess (con timers globales), úsalo
    if (typeof window.onLoginSuccess === 'function') {
      window.onLoginSuccess(payload)
    } else {
      // Fallback local
      localStorage.setItem('token', payload.access_token)
      localStorage.setItem('token_type', payload.token_type)
      localStorage.setItem('user', JSON.stringify(payload.user))
      if (payload.expires_at) localStorage.setItem('expires_at', payload.expires_at)
      axios.defaults.headers.common.Authorization = `Bearer ${payload.access_token}`
      if (payload.expires_in) scheduleExpiryPrompt(payload.expires_in)
    }

    const nombreUsuario = payload.user?.name || 'Usuario'
    Swal.fire({
      icon: 'success',
      title: `¡Hola de nuevo, ${nombreUsuario}! 👋`,
      text: 'Tu sesión ha sido iniciada con éxito.',
      confirmButtonColor: '#28a745',
      timer: 1200,
      showConfirmButton: false
    })

    // Redirigir por rol
    const rol = payload.user?.rol
    const destino =
      rol === 'admin' ? '/app/admin/dashboard' :
      rol === 'profesor' ? '/app/profesor/dashboard' :
      '/app/estudiante/dashboard'

    setTimeout(() => { router.push(destino) }, 800)

  } catch (err) {
    let msg = 'El servicio no está disponible. Intenta más tarde.'
    if (err?.response?.data?.error) msg = err.response.data.error
    Swal.fire({ icon: 'error', title: 'Error de acceso', text: msg, confirmButtonColor: '#dc3545' })
  } finally {
    isLoading.value = false
  }
}
</script>

<style src="@/assets/css/Login.css" scoped></style>
