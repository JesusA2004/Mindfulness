<template>
  <!-- Shell -->
  <div :class="['app-shell', (isPinned || isHovering) ? 'rail-expanded' : 'rail-collapsed']">
    <!-- NAVBAR -->
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

        <div class="ms-auto d-flex align-items-center gap-3">
          <!-- ====== CAMPANA ====== -->
          <div class="position-relative">
            <button
              class="btn btn-light border rounded-circle position-relative"
              @click="bellOpen = !bellOpen"
              aria-label="Notificaciones de citas"
              title="Notificaciones de citas"
            >
              <i class="bi bi-bell fs-5"></i>
              <span
                v-if="notifCount > 0"
                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                style="min-width:22px"
              >{{ notifCount }}</span>
            </button>

            <!-- Dropdown -->
            <div
              v-if="bellOpen"
              class="dropdown-menu show shadow-sm p-0 mt-2 notif-dropdown"
              style="min-width: 340px; right: 0; left: auto;"
            >
              <div class="p-2 border-bottom d-flex align-items-center gap-2">
                <i class="bi bi-bell me-1"></i>
                <strong>Notificaciones de Citas</strong>
                <span class="ms-auto badge bg-secondary">{{ notifCount }}</span>
              </div>

              <div class="list-group list-group-flush">
                <button
                  v-for="(n, i) in visibleNotifications"
                  :key="i"
                  class="list-group-item list-group-item-action d-flex gap-3 align-items-start text-start"
                  @click="ack(i)"
                >
                  <i class="bi bi-calendar-check fs-4"></i>
                  <div class="flex-grow-1">
                    <div class="fw-semibold">{{ n.title }}</div>
                    <div class="small text-muted">{{ n.time }}</div>
                    <div class="mt-1">{{ n.body }}</div>
                  </div>
                </button>

                <div v-if="!notifications.length" class="p-3 text-center text-muted">
                  Sin notificaciones
                </div>
              </div>

              <!-- Acciones -->
              <div class="d-flex justify-content-between align-items-center p-2 border-top">
                <button
                  class="btn btn-sm btn-outline-secondary rounded-circle action-btn"
                  @click="clearAll"
                  title="Limpiar todo"
                  aria-label="Limpiar todo"
                >
                  <i class="bi bi-trash"></i>
                </button>

                <router-link
                  class="btn btn-sm btn-outline-primary rounded-circle action-btn"
                  :to="citasRoute"
                  title="Ir a Citas"
                  aria-label="Ir a Citas"
                >
                  <i class="bi bi-eye"></i>
                </router-link>

                <button
                  class="btn btn-sm btn-outline-secondary rounded-circle action-btn"
                  @click="toggleExpand"
                  :title="showAll ? 'Ver menos' : 'Ver más'"
                  :aria-label="showAll ? 'Ver menos' : 'Ver más'"
                >
                  <i :class="['bi', showAll ? 'bi-chevron-up' : 'bi-chevron-down']"></i>
                </button>
              </div>
            </div>
          </div>
          <!-- ====== /CAMPANA ====== -->

          <!-- Usuario -->
          <div class="dropdown">
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
      </div>
    </nav>

    <!-- SIDEBAR (rail) -->
    <aside
      class="app-sidebar"
      :class="{ 'mobile-open': mobileOpen }"
      @mouseenter="onMouseEnter"
      @mouseleave="onMouseLeave"
    >
      <div class="sidebar-head">
        <img class="brand-logo" :src="logoSrc" alt="Mindfulness" />
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
          class="btn btn-sm btn-ghost ms-auto d-lg-none"
          @click="mobileOpen = false"
          aria-label="Cerrar menú"
        >
          <i class="bi bi-x-lg"></i>
        </button>
      </div>

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

    <!-- MODAL logout -->
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
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'

// Echo (Pusher)
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
window.Pusher = Pusher

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
    const url = process.env.VUE_APP_API_URL + '/auth/logout'
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
    { to: '/app/admin/usuarios',    text: 'Usuarios',     icon: 'bi-person'            },
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

/* ===== Notificaciones Citas (Echo) ===== */
const bellOpen = ref(false)
const notifCount = ref(0)
const notifications = ref([])
const showAll = ref(false)
const SHOW_LIMIT = 5

const visibleNotifications = computed(() =>
  showAll.value ? notifications.value : notifications.value.slice(0, SHOW_LIMIT)
)

const citasRoute = computed(() => {
  const rol = user.value?.rol
  if (rol === 'profesor') return '/app/profesor/citas'
  if (rol === 'admin') return '/app/admin/citas'
  return '/app/estudiante/citas'
})

let echo = null
let channel = null
let bound = false

function initEcho () {
  try {
    const jwt = localStorage.getItem('token')
    const uStr = localStorage.getItem('user')
    if (!jwt || !uStr) return

    const u = JSON.parse(uStr) || {}
    const uid = String(u.id || u._id || '')
    if (!uid) return

    if (echo) return // ya inicializado

    echo = new Echo({
      broadcaster: 'pusher',
      key: process.env.VUE_APP_PUSHER_APP_KEY,
      cluster: process.env.VUE_APP_PUSHER_APP_CLUSTER || 'mt1',
      forceTLS: true,
      // endpoint ABSOLUTO (sin /api)
      authEndpoint: process.env.VUE_APP_API_BASE + '/broadcasting/auth',
      auth: {
        headers: {
          Authorization: `Bearer ${jwt}`,
          Accept: 'application/json'
        }
      }
    })

    const p = echo.connector?.pusher
    p?.connection?.bind('state_change', s => console.log('[Echo]', s.previous, '=>', s.current))
    p?.connection?.bind('error', e => console.error('[Echo] error', e))
    p?.connection?.bind('connected', () => console.log('[Echo] connected'))

    // Suscribirse una sola vez
    channel = echo.private(`user.${uid}`)

    if (!bound) {
      bound = true
      // Exitoso
      channel.subscribed(() => {
        console.log('[Echo] subscription_succeeded user.' + uid)
      })

      // Error de suscripción (403 policy / 401 auth)
      channel.error((status) => {
        console.error('[Echo] subscription_error', status)
      })

      // Evento de Citas (alias con punto)
      channel.listen('.CitaEstadoCambiado', (data) => {
        console.log('[Echo] evento CitaEstadoCambiado', data)
        notifCount.value++
        notifications.value.unshift({
          title: `Cita ${data?.estado ?? ''}`.trim(),
          body: data?.mensaje || 'Se actualizó el estado de tu cita.',
          time: new Date().toLocaleString(),
          raw: data
        })
      })
    }
  } catch (e) {
    console.error('Echo init error:', e)
  }
}

function destroyEcho () {
  try {
    if (channel) {
      channel.stopListening('.CitaEstadoCambiado')
      channel = null
    }
    if (echo) {
      echo.disconnect()
      echo = null
    }
    bound = false
  } catch (e) {
    console.error('Echo destroy error:', e)
  }
}

function ack (i) {
  if (i >= 0) {
    notifications.value.splice(i, 1)
    notifCount.value = Math.max(0, notifCount.value - 1)
  }
}
function clearAll () {
  notifications.value = []
  notifCount.value = 0
}
function toggleExpand () {
  showAll.value = !showAll.value
}

onMounted(() => {
  initEcho()
})
onBeforeUnmount(() => {
  destroyEcho()
})
</script>

<style src="@/assets/css/Sidebar.css"></style>
<style scoped>
/* Estética ligera para el dropdown de notificaciones */
.notif-dropdown {
  border-radius: 12px;
  overflow: hidden;
}
.notif-dropdown .list-group-item {
  transition: background-color .15s ease, box-shadow .15s ease;
}
.notif-dropdown .list-group-item:hover {
  background-color: #f8f9fa;
}
.action-btn {
  width: 34px;
  height: 34px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
</style>
