<template>
  <!-- Shell -->
  <div :class="['app-shell', (isPinned || isHovering || mobileOpen) ? 'rail-expanded' : 'rail-collapsed']">
    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg shadow-sm fixed-top mf-navbar">
      <div class="container-fluid px-3">
        <!-- Brand / Hamburguesa -->
        <div class="d-flex align-items-center gap-2">
          <!-- Hamburguesa solo en móviles -->
          <button
            class="btn d-lg-none me-2"
            type="button"
            @click="mobileOpen = !mobileOpen"
            aria-label="Abrir menú"
          >
            <i class="bi bi-list fs-3"></i>
          </button>

          <div class="d-flex align-items-center gap-2 mf-brand">
            <span class="fw-700 d-none d-md-inline text-truncate">Mindora</span>
          </div>
        </div>

        <div class="ms-auto d-flex align-items-center gap-2 gap-lg-3">
          <!-- ====== CAMPANA ====== -->
          <div class="position-relative">
            <button
              class="btn btn-light border-0 mf-icon-btn position-relative"
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
                  @click="bellOpen=false"
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

          <!-- Usuario (dropdown personalizado) -->
          <div class="dropdown">
            <button
              class="btn d-flex align-items-center gap-2 mf-user-btn"
              data-bs-toggle="dropdown"
              aria-expanded="false"
              aria-label="Abrir menú de usuario"
              @click="bellOpen=false"
            >
              <img
                src="https://img.icons8.com/ios-glyphs/30/000000/user-male-circle.png"
                alt="perfil"
                class="rounded-circle user-avatar"
              />
              <span class="fw-600 d-none d-sm-inline text-truncate">{{ user.name }}</span>
              <i class="bi bi-chevron-down ms-1 opacity-75 d-none d-sm-inline"></i>
            </button>

            <ul class="dropdown-menu dropdown-menu-end user-dropdown shadow-lg">
              <li class="px-3 pt-3 pb-2">
                <div class="d-flex align-items-center gap-2">
                  <img
                    src="https://img.icons8.com/ios-glyphs/30/000000/user-male-circle.png"
                    alt="perfil"
                    class="rounded-circle user-avatar-sm"
                  />
                  <div class="small">
                    <div class="fw-700">{{ user.name }}</div>
                    <div class="text-muted text-truncate" style="max-width: 160px;">{{ user.email }}</div>
                  </div>
                </div>
              </li>
              <li><hr class="dropdown-divider my-2" /></li>
              <li>
                <router-link class="dropdown-item py-2 d-flex align-items-center gap-2" to="/app/perfil">
                  <i class="bi bi-person-circle"></i> <span>Perfil</span>
                </router-link>
              </li>
              <li>
                <button class="dropdown-item py-2 d-flex align-items-center gap-2 text-danger"
                        @click="showLogoutModal = true">
                  <i class="bi bi-box-arrow-right"></i> <span>Cerrar sesión</span>
                </button>
              </li>
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
              @click="onNavItemClick"
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

    <!-- ===== CONTENIDO (empujado por rail + navbar) ===== -->
    <section class="app-content">
      <slot />
    </section>

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
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
window.Pusher = Pusher

defineOptions({ name: 'SidebarShell' })
const router = useRouter()

/* ==== Ajuste dinámico de altura del navbar (quita hueco gris) ==== */
function applyNavbarHeight() {
  const el = document.querySelector('.mf-navbar') || document.querySelector('.navbar.fixed-top')
  if (!el) return
  const h = Math.ceil(el.getBoundingClientRect().height)
  document.documentElement.style.setProperty('--navbar-h', `${h}px`)
}
onMounted(() => {
  applyNavbarHeight()
  window.addEventListener('resize', applyNavbarHeight, { passive: true })
})
onBeforeUnmount(() => {
  window.removeEventListener('resize', applyNavbarHeight)
})

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
    const url = (import.meta.env.VITE_API_URL || process.env.VUE_APP_API_URL) + '/auth/logout'
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
/* Clic en item del menú: autocierre si NO está fijado (desktop hover) o si es móvil */
function onNavItemClick () {
  if (mobileOpen.value) mobileOpen.value = false
  if (!isPinned.value) isHovering.value = false
}

/* Cerrar overlays al cambiar de ruta; si no está fijado, colapsar */
onMounted(() => {
  router.afterEach(() => {
    bellOpen.value = false
    if (mobileOpen.value) mobileOpen.value = false
    if (!isPinned.value) isHovering.value = false
  })
})

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
    if (echo) return

    echo = new Echo({
      broadcaster: 'pusher',
      key: import.meta.env.VITE_PUSHER_KEY || process.env.VUE_APP_PUSHER_APP_KEY,
      cluster: import.meta.env.VITE_PUSHER_CLUSTER || process.env.VUE_APP_PUSHER_APP_CLUSTER || 'mt1',
      forceTLS: true,
      authEndpoint: (import.meta.env.VITE_API_BASE || process.env.VUE_APP_API_BASE) + '/broadcasting/auth',
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

    channel = echo.private(`user.${uid}`)

    if (!bound) {
      bound = true
      channel.subscribed(() => console.log('[Echo] subscription_succeeded user.' + uid))
      channel.error((status) => console.error('[Echo] subscription_error', status))
      channel.listen('.CitaEstadoCambiado', (data) => {
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

onMounted(() => { initEcho() })
onBeforeUnmount(() => { destroyEcho() })
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

/* ===== Navbar moderno ===== */
.mf-navbar{
  backdrop-filter: blur(8px) saturate(140%);
  -webkit-backdrop-filter: blur(8px) saturate(140%);
  background: linear-gradient(180deg, rgba(255,255,255,.85) 0%, rgba(255,255,255,.65) 100%) !important;
  border-bottom: 1px solid rgba(16,24,40,.06);
}
.mf-brand .brand-mini{
  width: 28px; height: 28px; object-fit: contain; border-radius: 8px;
}
.fw-700{ font-weight:700; }
.fw-600{ font-weight:600; }
.mf-icon-btn{
  border-radius: 999px;
  background: #fff;
  box-shadow: 0 6px 18px rgba(16,24,40,.08);
}

/* ===== Usuario dropdown moderno ===== */
.mf-user-btn{
  background: #fff;
  border: 1px solid rgba(16,24,40,.08);
  border-radius: 999px;
  padding: .35rem .6rem;
}
.user-avatar{ width: 28px; height: 28px; }
.user-avatar-sm{ width: 30px; height: 30px; }

.user-dropdown{
  border: 0;
  border-radius: 14px;
  overflow: hidden;
  min-width: 240px;
}
.user-dropdown .dropdown-item{
  font-weight: 500;
  border-radius: 8px;
  margin: 4px 8px;
}
.user-dropdown .dropdown-item:hover{
  background: #f5f6f8;
}
/* ✅ Tablet: que no se salga por la derecha y use un ancho razonable */
@media (max-width: 991.98px){
  .notif-dropdown{
    /* ignora inline styles del template en este breakpoint */
    min-width: 360px !important;
    max-width: calc(100vw - 24px) !important;
    right: 12px !important;
    left: auto !important;
    border-radius: 12px;
    max-height: calc(100vh - var(--navbar-h) - 24px);
    overflow: auto;
  }
}

/* ✅ Teléfono: conviértelo en un "sheet" centrado y ancho casi completo */
@media (max-width: 575.98px){
  .notif-dropdown{
    position: fixed !important;
    top: calc(var(--navbar-h) + 8px) !important;
    right: 8px !important;
    left: 8px !important;
    min-width: unset !important;
    width: auto !important;
    max-width: none !important;
    border-radius: 14px;
    box-shadow: 0 12px 40px rgba(16,24,40,.18);
    max-height: calc(100vh - var(--navbar-h) - 16px);
    overflow: auto;
    z-index: 1301; /* sobre la sidebar móvil */
  }

  /* Tap targets más cómodos */
  .notif-dropdown .list-group-item{
    padding: 12px 14px;
    font-size: 0.95rem;
  }

  /* Barra de acciones: botones más grandes/fáciles de pulsar */
  .action-btn{
    width: 40px;
    height: 40px;
  }
}
</style>
