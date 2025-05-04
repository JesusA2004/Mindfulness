<template>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm fixed-top">
    <div class="container-fluid">
      <!-- Menu hamburguesa solo en pantallas pequeñas -->
      <button
        class="btn d-lg-none me-2"
        type="button"
        data-bs-toggle="offcanvas"
        data-bs-target="#sidebar"
        aria-controls="sidebar"
        aria-expanded="false"
        aria-label="Toggle sidebar"
      >
        <span class="navbar-toggler-icon"></span>
      </button>

      <a class="navbar-brand fw-bold" href="#">Mindfulness</a>

      <!-- Menu de usuario -->
      <div class="dropdown ms-auto">
        <a
          class="nav-link dropdown-toggle d-flex align-items-center"
          href="#"
          role="button"
          data-bs-toggle="dropdown"
          aria-expanded="false"
        >
          <img
            src="https://img.icons8.com/ios-glyphs/30/000000/user-male-circle.png"
            alt="perfil"
            class="rounded-circle me-2"
          />
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
          <li>
            <router-link class="dropdown-item" to="/perfil">
              Perfil
            </router-link>
          </li>
          <li><hr class="dropdown-divider" /></li>
          <li>
            <button class="dropdown-item" @click="showLogoutModal = true">
              Cerrar sesión
            </button>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Sidebar -->
  <div
    class="offcanvas offcanvas-start offcanvas-lg bg-beige"
    tabindex="-1"
    id="sidebar"
    aria-labelledby="sidebarLabel"
  >
    <div class="offcanvas-header d-lg-none">
      <h5 class="offcanvas-title" id="sidebarLabel">Menú</h5>
      <button
        type="button"
        class="btn-close"
        data-bs-dismiss="offcanvas"
        aria-label="Close"
      ></button>
    </div>
    <div class="offcanvas-body p-3">
      <ul class="nav flex-column">
        <li
          class="nav-item mb-1"
          v-for="item in navItems"
          :key="item.text"
        >
          <router-link
            :to="item.to"
            class="nav-link d-flex align-items-center"
          >
            <img :src="item.icon" alt class="me-2 nav-icon" />
            <span>{{ item.text }}</span>
          </router-link>
        </li>
      </ul>
    </div>
  </div>

  <!-- Modal para confirmar el cierre de sesión -->
  <div
    v-show="showLogoutModal"
    class="custom-modal-overlay"
  >
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content animate__fadeInDown">
        <div class="modal-header">
          <h5 class="modal-title">Confirmar cierre de sesión</h5>
          <button type="button" class="btn-close" @click="showLogoutModal = false"></button>
        </div>
        <div class="modal-body">
          <p>¿Estás seguro que deseas cerrar la sesión?</p>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="showLogoutModal = false">Cancelar</button>
          <button class="btn btn-danger d-flex align-items-center" @click="confirmLogout" :disabled="isLoggingOut">
            <span v-if="!isLoggingOut">Salir</span>
            <span v-else class="spinner-border spinner-border-sm ms-2" role="status" aria-hidden="true"></span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>

  import { ref, computed, onMounted} from 'vue';
  import axios from 'axios';
  import { useRouter } from 'vue-router';

  defineOptions({ name: 'LoginLayout' });
  const router = useRouter();

  // UI State
  const showLogoutModal = ref(false);
  const isLoggingOut = ref(false);

  // Usuario con su rol
  const user = ref({});
  onMounted(() => {
    const storedUser = localStorage.getItem('user');
    if (storedUser) {
      user.value = JSON.parse(storedUser);
    }
  });

  // Definición de menús por rol
  const menusPorRol = {
    estudiante: [
      { to: '/home', text: 'Inicio', icon: 'https://img.icons8.com/ios-filled/24/000000/home.png' },
      { to: '/home/tareas', text: 'Tareas', icon: 'https://img.icons8.com/ios-filled/24/000000/task.png' },
      { to: '/home/progreso', text: 'Progreso', icon: 'https://img.icons8.com/ios-filled/24/000000/combo-chart--v1.png' }
    ],
    profesor: [
      { to: '/home', text: 'Inicio', icon: 'https://img.icons8.com/ios-filled/24/000000/home.png' },
      { to: '/home/asignaciones', text: 'Asignaciones', icon: 'https://img.icons8.com/ios-filled/24/000000/task.png' },
      { to: '/home/evaluaciones', text: 'Evaluaciones', icon: 'https://img.icons8.com/ios-filled/24/000000/test.png' }
    ],
    admin: [
      { to: '/home', text: 'Inicio', icon: 'https://img.icons8.com/ios-filled/24/000000/home.png' },
      { to: '/home/empleados', text: 'Empleados', icon: 'https://img.icons8.com/ios-filled/24/000000/user-group-man-man.png' },
      { to: '/home/salarios', text: 'Salarios', icon: 'https://img.icons8.com/ios-filled/24/000000/money-with-wings.png' },
      { to: '/home/reportes', text: 'Reportes', icon: 'https://img.icons8.com/ios-filled/24/000000/combo-chart--v1.png' },
      { to: '/home/ajustes', text: 'Ajustes', icon: 'https://img.icons8.com/ios-filled/24/000000/settings.png' }
    ]
  };

  // Lista de navegación ya autenticada
  const navItems = computed(() => {
    return menusPorRol[user.value?.rol] || [];
  });

  // Cerrar sesión
  async function confirmLogout() {
    isLoggingOut.value = true;
    try {
      const url = process.env.VUE_APP_API_URL + '/auth/logout';
      const token = localStorage.getItem('token');
      await axios.post(url, {}, { headers: { Authorization: `Bearer ${token}` } });
    } catch (err) {
      console.error('Error en logout:', err);
    } finally {
      localStorage.clear();
      router.push('/login');
    }
  }

</script>

<style src="@/assets/css/Sidebar.css" scoped></style>
