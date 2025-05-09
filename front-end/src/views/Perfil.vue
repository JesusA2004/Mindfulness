<template>
  <div class="container py-5">
    <div class="card shadow-sm animate__fadeIn">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h3 class="mb-0">{{ user.name }}</h3>
        <span class="wave">👋</span>
      </div>
      <div class="card-body">
        <ul class="list-group mb-3">
          <li class="list-group-item d-flex justify-content-between">
            <span><strong>Email:</strong></span>
            <span>{{ user.email }}</span>
          </li>
          <li class="list-group-item d-flex justify-content-between">
            <span><strong>Rol:</strong></span>
            <span class="text-capitalize">{{ user.rol }}</span>
          </li>
          <li class="list-group-item d-flex justify-content-between">
            <span><strong>Creado:</strong></span>
            <span>{{ formatDate(user.created_at) }}</span>
          </li>
          <li class="list-group-item d-flex justify-content-between">
            <span><strong>Actualizado:</strong></span>
            <span>{{ formatDate(user.updated_at) }}</span>
          </li>
        </ul>

        <!-- Sólo para admins -->
        <div v-if="user.rol === 'admin'" class="text-end">
          <button class="btn btn-warning btn-hover-scale" @click="goToChangePassword">
            Cambiar contraseña
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
    import { ref, onMounted } from 'vue';
    import { useRouter } from 'vue-router';

    const router = useRouter();
    const user    = ref({});

    onMounted(() => {
    const stored = localStorage.getItem('user');
    if (!stored) {
        // si no hay usuario, vamos al login
        return router.push({ name: 'LoginPage' });
    }
    user.value = JSON.parse(stored);
    });

    function goToChangePassword() {
    router.push({ name: 'ChangePassword' });
    }

    function formatDate(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    // si sigue siendo inválida, muestro vacío
    if (isNaN(d)) return '';
    return d.toLocaleString('es-MX', {
        day:   '2-digit',
        month: 'short',
        year:  'numeric',
        hour:   '2-digit',
        minute: '2-digit'
    });
    }
</script>

<style scoped>

    .container {
    max-width: 700px;
    margin: auto;
    }

    .wave {
    display: inline-block;
    animation: wave-animation 2.5s infinite;
    transform-origin: 70% 70%;
    }

    @keyframes wave-animation {
    0% { transform: rotate(0deg) }
    10%{ transform: rotate(14deg) }
    20%{ transform: rotate(-8deg) }
    30%{ transform: rotate(14deg) }
    40%{ transform: rotate(-4deg) }
    50%{ transform: rotate(10deg) }
    60%{ transform: rotate(0deg) }
    100%{ transform: rotate(0deg) }
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
