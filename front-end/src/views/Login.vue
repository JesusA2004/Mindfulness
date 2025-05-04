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
          <p v-if="errorMessage" class="alert alert-danger mt-3 mb-0">
            {{ errorMessage }}
          </p>
        </form>
      </div>
    </div>
  </template>
  
<script setup>

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
  const errorMessage = ref('')
  const isLoading = ref(false)
  
  const loginUrl = process.env.VUE_APP_API_URL + '/auth/login'
  
  const router = useRouter()

  const login = async () => {
    isLoading.value = true
    errorMessage.value = ''
  
    const { valid, message } = validarCamposLogin(email.value, password.value)
    if (!valid) {
      errorMessage.value = message
      isLoading.value = false
      return
    }
  
    try {
      console.log('Login URL:', loginUrl)
      console.log('Payload:', { email: email.value, password: password.value })

      const { data } = await axios.post(loginUrl, {
        email: email.value,
        password: password.value
      })
      localStorage.setItem('token', data.access_token)
      localStorage.setItem('token_type', data.token_type)
      localStorage.setItem('user', JSON.stringify(data.user))

      // Redigirigir al usuario según su rol
      router.push(`/app/${data.user.rol}`)

    } catch (err) {
      if (err.response) {
        errorMessage.value = err.response.data.error || 'Datos de acceso incorrectos.'
      } else {
        errorMessage.value = 'Error de red. Intenta más tarde.'
      }
    } finally {
      isLoading.value = false
    }
  }

</script>
  
<style scoped>

  .login-page {
    position: relative;
    width: 100vw;
    height: 100vh;
    background-image: url('@/assets/images/backgroundMind.png');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
  }
  
  .overlay {
    position: absolute;
    inset: 0;
    background-color: rgba(0, 0, 0, 0.7);
  }
  
  .login-card {
    position: relative;
    max-width: 360px;
    width: 100%;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 0.5rem;
    z-index: 1;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }
  .login-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.3);
  }
  
  .password-toggle {
    cursor: pointer;
    color: #6c757d;
    transition: color 0.3s ease;
  }
  .password-toggle:hover {
    color: #495057;
  }
  
  .btn-login {
    transition: background-color 0.3s ease, transform 0.3s ease;
  }
  .btn-login:hover:not(:disabled) {
    background-color: #28a745;
    transform: translateY(-2px);
  }
  
  .wave {
    display: inline-block;
    animation: wave-animation 1.5s infinite;
  }
  @keyframes wave-animation {
    0%, 100% { transform: rotate(0deg); }
    50% { transform: rotate(10deg); }
  }
  </style>
  