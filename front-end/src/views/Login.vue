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
            <span class="input-group-text password-toggle" @click="togglePasswordVisibility">
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
const iconClass = computed(() => (isPasswordVisible.value ? 'bx bx-show' : 'bx bx-hide'))

const togglePasswordVisibility = () => {
  isPasswordVisible.value = !isPasswordVisible.value
}

const email = ref('')
const password = ref('')
const isLoading = ref(false)

const loginUrl = process.env.VUE_APP_API_URL + '/auth/login'

const router = useRouter()

const login = async () => {
  isLoading.value = true

  const { valid, message } = validarCamposLogin(email.value, password.value)
  if (!valid) {
    Swal.fire({
      icon: 'warning',
      title: 'Campos inválidos',
      text: message,
      confirmButtonColor: '#28a745'
    })
    isLoading.value = false
    return
  }

  try {
    const { data } = await axios.post(loginUrl, {
      email: email.value,
      password: password.value
    })

    localStorage.setItem('token', data.access_token)
    localStorage.setItem('token_type', data.token_type)
    localStorage.setItem('user', JSON.stringify(data.user))

    const nombreUsuario = data.user.name;

    Swal.fire({
      icon: 'success',
      title: `¡Hola de nuevo, ${nombreUsuario}! 👋`,
      text: 'Nos alegra verte otra vez. Tu sesión ha sido iniciada con éxito. 🎉',
      confirmButtonColor: '#28a745',
      timer: 2000,
      showConfirmButton: false
    });

    // Espera un poco antes de redirigir para que se vea el Swal
    setTimeout(() => {
      router.push(`/app/${data.user.rol}/dashboard`)
    }, 1600)

  } catch (err) {
    let message = 'El servicio no está disponible. Intenta más tarde.'
    if (err.response) {
      message = err.response.data.error || 'Datos de acceso incorrectos.'
    }

    Swal.fire({
      icon: 'error',
      title: 'Error de acceso',
      text: message,
      confirmButtonColor: '#dc3545'
    })
  } finally {
    isLoading.value = false
  }
}
</script>

<style src="@/assets/css/Login.css" scoped></style>
