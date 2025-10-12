<template>
  <!-- Shell: nos permite aplicar estilos coordinados a navbar y contenido según el estado del sidebar -->
  <div :class="['app-shell', (isPinned || isHovering) ? 'rail-expanded' : 'rail-collapsed']">
    <!-- NAVBAR (se desplaza a la derecha del rail automáticamente via CSS) -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm fixed-top">
      <div class="container-fluid px-3">
        <!-- Hamburguesa solo en móviles -->
        <button
          class="btn d-lg-none me-2"
          type="button"
          @click="mobileOpen = true"
          aria-label="Abrir menú"
        >
          <i class="bi bi-list fs-3"></i>
        </button>

        <div class="ms-auto dropdown">
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
              class="rounded-circle"
            />
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><router-link class="dropdown-item" to="/app/perfil">Perfil</router-link></li>
            <li><hr class="dropdown-divider" /></li>
            <li><button class="dropdown-item" @click="showLogoutModal = true">Cerrar sesión</button></li>
          </ul>
        </div>
      </div>
    </nav>

    <!-- SIDEBAR (rail) -->
    <aside
      class="app-sidebar"
      :class="{ 'mobile-open': mobileOpen }"
      @mouseenter="onMouseEnter"
      @mouseleave="onMouseLeave"
    >
      <!-- Header del sidebar -->
      <div class="sidebar-head">
        <img class="brand-logo" :src="logoSrc" alt="Mindfulness" />

        <!-- Pin/Unpin en desktop -->
        <button
          v-if="isPinned"
          class="btn btn-sm btn-ghost ms-auto d-none d-lg-inline-flex"
          @click="togglePin(false)"
          aria-label="Contraer sidebar"
          title="Contraer"
        >
          <i class="bi bi-chevron-left"></i>
        </button>
        <button
          v-else
          class="btn btn-sm btn-ghost ms-auto d-none d-lg-inline-flex"
          @click="togglePin(true)"
          aria-label="Fijar sidebar"
          title="Fijar"
        >
          <i class="bi bi-pin-angle"></i>
        </button>

        <!-- Cerrar en móvil -->
        <button
          class="btn btn-sm btn-ghost ms-auto d-lg-none"
          @click="mobileOpen = false"
          aria-label="Cerrar menú"
        >
          <i class="bi bi-x-lg"></i>
        </button>
      </div>

      <!-- Navegación -->
      <nav class="sidebar-nav">
        <ul class="list-unstyled m-0 p-0">
          <li v-for="item in navItems" :key="item.text" class="nav-li">
            <router-link
              :to="item.to"
              class="nav-a"
              active-class="is-active"
              @click.native="handleNavClick"
              :title="(!isPinned && !isHovering) ? item.text : null"
            >
              <span class="nav-icon-wrap">
                <i :class="['bi', item.icon]"></i>
              </span>
              <span class="nav-label">{{ item.text }}</span>
            </router-link>
          </li>
        </ul>
      </nav>

      <!-- Pie del sidebar -->
      <div class="sidebar-foot d-none d-lg-flex">
        <button
          class="btn btn-ghost w-100"
          @click="togglePin(!isPinned)"
          :title="isPinned ? 'Contraer' : 'Fijar'"
        >
          <i :class="['bi', isPinned ? 'bi-chevron-left' : 'bi-pin-angle']"></i>
          <span class="ms-2 foot-label">{{ isPinned ? 'Contraer' : 'Fijar' }}</span>
        </button>
      </div>
    </aside>

    <!-- MODAL de logout (con estilos restaurados en CSS) -->
    <div v-if="showLogoutModal" class="modal-backdrop-custom" @click.self="showLogoutModal=false">
      <div class="card shadow p-3 modal-card">
        <h5 class="mb-2 modal-title-logout">Confirmar cierre de sesión</h5>
        <p class="text-muted mb-3">¿Estás seguro que deseas cerrar la sesión?</p>
        <div class="d-flex gap-2 justify-content-end">
          <button class="btn btn-outline-secondary" @click="showLogoutModal=false">Cancelar</button>
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
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'

defineOptions({ name: 'LoginLayout' })
const router = useRouter()

/* Usuario */
const user = ref({})
onMounted(() => {
  const u = localStorage.getItem('user')
  if (u) user.value = JSON.parse(u)
})

/* Logout */
const showLogoutModal = ref(false)
const isLoggingOut = ref(false)
async function confirmLogout () {
  isLoggingOut.value = true
  try {
    const url = (process.env.VUE_APP_API_URL || '') + '/auth/logout'
    const token = localStorage.getItem('token')
    if (token) await axios.post(url, {}, { headers: { Authorization: `Bearer ${token}` } })
  } catch (e) {
    console.error(e)
  } finally {
    localStorage.clear()
    router.push('/login')
  }
}

/* Menú por rol */
const menusPorRol = {
  estudiante: [
    { to: '/app/estudiante/dashboard',   text: 'Inicio',       icon: 'bi-house'             },
    { to: '/app/estudiante/actividades', text: 'Actividades',  icon: 'bi-list-task'         },
    { to: '/app/estudiante/bitacoras',   text: 'Bitácoras',    icon: 'bi-journal-text'      },
    { to: '/app/estudiante/citas',       text: 'Citas',        icon: 'bi-calendar-event'    },
    { to: '/app/estudiante/encuestas',   text: 'Encuestas',    icon: 'bi-clipboard-data'    },
    { to: '/app/estudiante/recompensas', text: 'Recompensas',  icon: 'bi-trophy'            },
    { to: '/app/estudiante/tecnicas',    text: 'Técnicas',     icon: 'bi-book'              },
    { to: '/app/estudiante/tests',       text: 'Tests',        icon: 'bi-file-earmark-text' }
  ],
  profesor: [
    { to: '/app/profesor/dashboard',   text: 'Inicio',       icon: 'bi-house'             },
    { to: '/app/profesor/actividades', text: 'Actividades',  icon: 'bi-list-task'         },
    { to: '/app/profesor/citas',       text: 'Citas',        icon: 'bi-calendar-event'    },
    { to: '/app/profesor/encuestas',   text: 'Encuestas',    icon: 'bi-clipboard-data'    },
    { to: '/app/profesor/recompensas', text: 'Recompensas',  icon: 'bi-trophy'            },
    { to: '/app/profesor/tecnicas',    text: 'Técnicas',     icon: 'bi-book'              },
    { to: '/app/profesor/tests',       text: 'Tests',        icon: 'bi-file-earmark-text' }
  ],
  admin: [
    { to: '/app/admin/dashboard',   text: 'Inicio',       icon: 'bi-house'             },
    { to: '/app/admin/actividades', text: 'Actividades',  icon: 'bi-list-task'         },
    { to: '/app/admin/citas',       text: 'Citas',        icon: 'bi-calendar-event'    },
    { to: '/app/admin/encuestas',   text: 'Encuestas',    icon: 'bi-clipboard-data'    },
    { to: '/app/admin/recompensas', text: 'Recompensas',  icon: 'bi-trophy'            },
    { to: '/app/admin/tecnicas',    text: 'Técnicas',     icon: 'bi-book'              },
    { to: '/app/admin/tests',       text: 'Tests',        icon: 'bi-file-earmark-text' },
    { to: '/app/admin/respaldo',    text: 'Respaldo',     icon: 'bi-file-earmark-text' },
    { to: '/app/admin/reportes',    text: 'Reportes',     icon: 'bi-file-earmark-text' }
  ]
}
const navItems = computed(() => menusPorRol[user.value?.rol] || [])

/* Sidebar (hover / pin / móvil) */
const isPinned = ref(JSON.parse(localStorage.getItem('sidebarPinned') || 'false'))
const isHovering = ref(false)
const mobileOpen = ref(false)

function onMouseEnter () { if (!isPinned.value) isHovering.value = true }
function onMouseLeave () { if (!isPinned.value) isHovering.value = false }
function togglePin (val) {
  isPinned.value = !!val
  localStorage.setItem('sidebarPinned', JSON.stringify(isPinned.value))
}
function handleNavClick () {
  if (!isPinned.value) togglePin(true)
  mobileOpen.value = false
}

/* Logo */
const logoSrc = '/img/logoDark.png'
</script>

<style src="@/assets/css/Sidebar.css"></style>
