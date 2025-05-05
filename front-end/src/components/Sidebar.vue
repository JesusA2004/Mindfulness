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
          <span class="me-2 fw-medium">{{ user.name }}</span>
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
    class="offcanvas offcanvas-start offcanvas-lg bg-dark"
    tabindex="-1"
    id="sidebar"
    aria-labelledby="sidebarLabel"
    style="width: 180px;"
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
    <div class="offcanvas-body p-3 px-2">
      <ul class="nav flex-column">
        <li
          class="nav-item mb-1"
          v-for="item in navItems"
          :key="item.text"
        >
          <router-link
            :to="item.to"
            class="nav-link d-flex align-items-center text-white"
          >
            <i :class="['bi', item.icon, 'me-2', 'text-white']"></i>
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

  <!-- Modal para renovar sesión -->
  <div
    v-show="showSessionModal"
    class="custom-modal-overlay"
  >
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Sesión a punto de expirar</h5>
        </div>
        <div class="modal-body">
          <p>Tu sesión está a punto de expirar. ¿Deseas continuar?</p>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="handleSessionExpire(false)">
            Cerrar sesión
          </button>
          <button class="btn btn-primary" @click="handleSessionExpire(true)">
            Continuar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>

  import { ref, computed, onMounted, onUnmounted } from 'vue';
  import axios from 'axios';
  import { useRouter } from 'vue-router';

  defineOptions({ name: 'LoginLayout' });
  const router = useRouter();

  // Estado de la UI
  const showLogoutModal = ref(false);
  const isLoggingOut = ref(false);
  const showSessionModal = ref(false);
  let activityTimer = null;
  let expirationCheckInterval = null;

  // Usuario con su rol
  const user = ref({});
  onMounted(() => {
    const storedUser = localStorage.getItem('user');
    if (storedUser) {
      user.value = JSON.parse(storedUser);
    }
    startTokenChecks();
    setupActivityListeners();
  });

  onUnmounted(() => {
    clearTimers();
    // Limpiar event listeners
    ['mousemove', 'keydown', 'click'].forEach(event => {
      window.removeEventListener(event, resetActivityTimer);
    });
  });

  // Función para verificar expiración del token
  const isTokenExpired = (token, thresholdMinutes = 0) => {
    try {
      const payload = JSON.parse(atob(token.split('.')[1]));
      const exp = payload.exp * 1000;
      return Date.now() + (thresholdMinutes * 60 * 1000) > exp;
    } catch {
      return true;
    }
  };

  // Lógica de renovación de token
  const refreshToken = async () => {
    try {
      const response = await axios.post('/auth/refresh', {}, {
        headers: {
          Authorization: `Bearer ${localStorage.getItem('token')}`
        }
      });
      
      localStorage.setItem('token', response.data.token);
      return true;
    } catch (error) {
      console.error('Error renovando token:', error);
      return false;
    }
  };

  let resetActivityTimer = null;

  // Manejo de actividad del usuario
  const setupActivityListeners = () => {
    const resetActivityTimer = () => {
      clearTimeout(activityTimer);
      activityTimer = setTimeout(checkTokenExpiration, 60000); // 1 minuto
    };

    ['mousemove', 'keydown', 'click'].forEach(event => {
      window.addEventListener(event, resetActivityTimer);
    });

    resetActivityTimer();
  };

  // Lógica de verificación de expiración
  const startTokenChecks = () => {
    expirationCheckInterval = setInterval(() => {
      checkTokenExpiration();
    }, 30000); // Verificar cada 30 segundos
  };

  const checkTokenExpiration = async () => {
  const token = localStorage.getItem('token');
  if (!token) return;

  // Si expira en menos de 2 minutos pero más de 1 minuto
  if (isTokenExpired(token, 2)) {
    // Si está en el último minuto y ha habido actividad
    if (isTokenExpired(token, 1)) {
      const success = await refreshToken();
      if (!success) handleSessionExpire(false);
    } else {
      showSessionModal.value = true;
    }
  }
};

  // Manejo de la respuesta del modal
  const handleSessionExpire = async (continueSession) => {
    showSessionModal.value = false;
    
    if (continueSession) {
      const success = await refreshToken();
      if (!success) {
        localStorage.clear();
        router.push('/login');
      }
    } else {
      localStorage.clear();
      router.push('/login');
    }
  };

  // Limpiar timers
  const clearTimers = () => {
    clearInterval(expirationCheckInterval);
    clearTimeout(activityTimer);
  };

  // Definición de menús por rol
  const menusPorRol = {
    estudiante: [
      { to: '/app/estudiante', text: 'Inicio', icon: 'bi-house' },
      { to: '/app/tareas', text: 'Tareas', icon: 'bi-list-check' },
      { to: '/app/progreso', text: 'Progreso', icon: 'bi-bar-chart-line' }
    ],
    profesor: [
      { to: '/app/profesor/dashboard', text: 'Inicio', icon: 'bi-house' },
      { to: '/app/profesor/asignaciones', text: 'Asignaciones', icon: 'bi-list-task' },
      { to: '/app/profesor/evaluaciones', text: 'Evaluaciones', icon: 'bi-clipboard-check' }
    ],
    admin: [
      { to: '/app/admin', text: 'Inicio', icon: 'bi-house' },
      { to: '/app/empleados', text: 'Empleados', icon: 'bi-people' },
      { to: '/app/salarios', text: 'Salarios', icon: 'bi-currency-dollar' },
      { to: '/app/reportes', text: 'Reportes', icon: 'bi-bar-chart-line' },
      { to: '/app/ajustes', text: 'Ajustes', icon: 'bi-gear' }
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
