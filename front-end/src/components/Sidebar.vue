<template> 
  <!-- Shell -->
  <div :class="['app-shell', (isPinned || isHovering || mobileOpen) ? 'rail-expanded' : 'rail-collapsed']">
    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg shadow-sm fixed-top mf-navbar">
      <div class="container-fluid px-3">
        <!-- Brand / Hamburguesa -->
        <div class="d-flex align-items-center gap-2">
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
              aria-label="Notificaciones"
              title="Notificaciones"
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
                <strong>Notificaciones</strong>
                <span class="ms-auto badge bg-secondary">{{ notifCount }}</span>
              </div>

              <div class="list-group list-group-flush">
                <button
                  v-for="(n, i) in visibleNotifications"
                  :key="i"
                  class="list-group-item list-group-item-action d-flex gap-3 align-items-start text-start"
                  @click="onNotifClick(i)"
                >
                  <i :class="['bi', n.icon || 'bi-bell', 'fs-4']"></i>
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

          <!-- Usuario -->
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

    <!-- SIDEBAR -->
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

    <!-- CONTENIDO -->
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

defineOptions({ name: 'Sidebar' })
const router = useRouter()

/* ==== Ajuste dinámico de altura del navbar ==== */
function applyNavbarHeight() {
  try {
    const el = document.querySelector('.mf-navbar') || document.querySelector('.navbar.fixed-top')
    if (!el) return
    const h = Math.ceil(el.getBoundingClientRect().height)
    document.documentElement.style.setProperty('--navbar-h', `${h}px`)
  } catch (e) {
    console.error('[Sidebar] applyNavbarHeight error:', e)
  }
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
  try {
    const u = localStorage.getItem('user')
    if (u) user.value = JSON.parse(u)
  } catch (e) {
    console.error('[Sidebar] parse user error:', e)
  }
})
const isAlumno = computed(() => (user.value?.rol || '').toLowerCase() === 'estudiante') // ✅ SOLO alumno verá recordatorio + recompensas

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
    console.error('[Sidebar] logout error:', e)
  } finally {
    try { localStorage.clear() } catch {}
    destroyEcho()
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
  try { localStorage.setItem('sidebarPinned', JSON.stringify(isPinned.value)) } catch {}
}
function onNavItemClick () {
  if (mobileOpen.value) mobileOpen.value = false
  if (!isPinned.value) isHovering.value = false
}

/* Cerrar overlays al cambiar de ruta */
onMounted(() => {
  router.afterEach(() => {
    bellOpen.value = false
    if (mobileOpen.value) mobileOpen.value = false
    if (!isPinned.value) isHovering.value = false
  })
})

/* Logo */
const logoSrc = '/img/logoDark.png'

/* ===== Notificaciones (Echo) ===== */
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
let userChannel = null
let roleChannel = null // 🔔 Recompensas (solo alumno)
let bound = false
let subscribedReady = false
let pingRetried = false

function initEcho () {
  try {
    const jwt = localStorage.getItem('token')
    const uStr = localStorage.getItem('user')
    if (!jwt || !uStr) { console.warn('[Echo] faltan credenciales'); return }

    const u = JSON.parse(uStr || '{}')
    const uid = String(u.id || u._id || '')
    if (!uid) { console.warn('[Echo] user id vacío'); return }
    if (echo) { console.log('[Echo] ya inicializado'); return }

    echo = new Echo({
      broadcaster: 'pusher',
      key: process.env.VUE_APP_PUSHER_APP_KEY,
      cluster: process.env.VUE_APP_PUSHER_APP_CLUSTER || 'mt1',
      forceTLS: window.location.protocol === 'https:',
      authEndpoint: (process.env.VUE_APP_API_BASE || '') + '/broadcasting/auth',
      auth: {
        headers: {
          Authorization: `Bearer ${jwt}`,
          Accept: 'application/json'
        }
      }
    })

    const p = echo.connector && echo.connector.pusher
    if (p && p.connection) {
      p.connection.bind('state_change', s => console.log('[Echo]', s.previous, '=>', s.current))
      p.connection.bind('error', e => console.error('[Echo] error', e))
      p.connection.bind('connected', () => console.log('[Echo] connected'))
    } else {
      console.warn('[Echo] pusher connection no disponible aún')
    }

    // Canal privado por usuario
    userChannel = echo.private(`user.${uid}`)

    if (!bound) {
      bound = true

      userChannel.subscribed(() => {
        subscribedReady = true
        console.log('[Echo] subscription_succeeded user.' + uid)
        // 🔔 SOLO alumnos hacen ping al recordatorio de bitácora
        if (isAlumno.value) {
          try { pingRecordatorioBitacora() } catch (e) { console.error('[Bitácora] ping error post-subscribed:', e) }
        }
      })

      userChannel.error((status) => console.error('[Echo] subscription_error', status))

      // Citas (aplica a todos los roles)
      userChannel.listen('.CitaEstadoCambiado', (data) => {
        try {
          notifCount.value++
          notifications.value.unshift({
            title: `Cita ${data?.estado ?? ''}`.trim(),
            body: data?.mensaje || 'Se actualizó el estado de tu cita.',
            time: new Date().toLocaleString(),
            raw: data,
            icon: 'bi-calendar-check'
          })
        } catch (e) { console.error('[Echo] handler CitaEstadoCambiado error:', e) }
      })

      // Bitácora (🔒 SOLO alumnos escuchan este evento en su canal de usuario)
      if (isAlumno.value) {
        userChannel.listen('.BitacoraRecordatorio', (data) => {
          try {
            notifCount.value++
            notifications.value.unshift({
              title: 'Recordatorio de Bitácora',
              body: data?.mensaje || 'Aún no registras tu bitácora de hoy.',
              time: new Date().toLocaleString(),
              raw: data,
              goTo: '/app/estudiante/bitacoras',
              icon: 'bi-journal-text'
            })
          } catch (e) { console.error('[Echo] handler BitacoraRecordatorio error:', e) }
        })
      }

      // 🔔 Recompensas (solo alumnos) — canal por rol
      if (isAlumno.value) {
        roleChannel = echo.private('role.estudiante')

        roleChannel.subscribed(() => {
          console.log('[Echo] subscription_succeeded role.estudiante')
        })

        roleChannel.error((status) => console.error('[Echo] subscription_error role.estudiante', status))

        roleChannel.listen('.RecompensaCreada', (data) => {
          try {
            const titulo = data?.recompensa?.titulo || 'Nueva recompensa'
            notifCount.value++
            notifications.value.unshift({
              title: 'Nueva recompensa disponible',
              body: `Se agregó “${titulo}” a la tienda de recompensas.`,
              time: new Date().toLocaleString(),
              raw: data,
              goTo: '/app/estudiante/recompensas',
              icon: 'bi-trophy'
            })
          } catch (e) {
            console.error('[Echo] handler RecompensaCreada error:', e)
          }
        })
      }
    }

    // Fallback: si en 1.5s no nos suscribimos, intenta el ping SOLO si es alumno
    setTimeout(() => {
      if (!subscribedReady && !pingRetried && isAlumno.value) {
        pingRetried = true
        try { pingRecordatorioBitacora() } catch (e) { console.error('[Bitácora] ping retry error:', e) }
      }
    }, 1500)

  } catch (e) {
    console.error('[Echo] init error (no bloquea UI):', e)
  }
}

function destroyEcho () {
  try {
    if (userChannel) {
      userChannel.stopListening('.CitaEstadoCambiado')
      userChannel.stopListening('.BitacoraRecordatorio')
      userChannel.stopListening('.ForcedLogout')
      userChannel = null
    }
    if (roleChannel) { // 🔔 Recompensas
      roleChannel.stopListening('.RecompensaCreada')
      roleChannel = null
    }
    if (echo) {
      echo.disconnect()
      echo = null
    }
    bound = false
    subscribedReady = false
    pingRetried = false
  } catch (e) {
    console.error('[Echo] destroy error:', e)
  }
}

function ack (i) {
  try {
    if (i >= 0) {
      notifications.value.splice(i, 1)
      notifCount.value = Math.max(0, notifCount.value - 1)
    }
  } catch (e) { console.error('[Sidebar] ack error:', e) }
}
function clearAll () {
  try {
    notifications.value = []
    notifCount.value = 0
  } catch (e) { console.error('[Sidebar] clearAll error:', e) }
}
function toggleExpand () {
  showAll.value = !showAll.value
}
function onNotifClick (i) {
  try {
    const n = notifications.value?.[i]
    if (n?.goTo) {
      router.push(n.goTo)
    }
    ack(i)
  } catch (e) { console.error('[Sidebar] onNotifClick error:', e) }
}

/* Llamada al endpoint (NO await) — 🔒 SOLO alumnos */
function pingRecordatorioBitacora() {
  if (!isAlumno.value) return
  const token = localStorage.getItem('token')
  if (!token) { console.warn('[Bitácora] sin token, no ping'); return }
  axios.post(
    (process.env.VUE_APP_API_URL || '') + '/bitacoras/remind-today',
    {},
    { headers: { Authorization: `Bearer ${token}` } }
  )
  .then(res => console.log('[Bitácora] remind-today status:', res?.status))
  .catch(err => {
    if (err?.response) {
      console.log('[Bitácora] remind-today error:', err.response.status, err.response.data)
    } else {
      console.log('[Bitácora] remind-today error:', err?.message || err)
    }
  })
}

onMounted(() => {
  // Deja que la app pinte y luego inicializa Echo
  requestAnimationFrame(() => initEcho())

  const expiresAt = localStorage.getItem('expires_at')
  if (expiresAt && typeof window.scheduleExpiryPrompt === 'function') {
    const diffMs = new Date(expiresAt).getTime() - Date.now()
    if (diffMs > 0) {
      const expiresInSec = Math.floor(diffMs / 1000)
      try { window.scheduleExpiryPrompt(expiresInSec) } catch (e) {}
    }
  }
})
onBeforeUnmount(() => { destroyEcho() })
</script>

<style src="@/assets/css/Sidebar.css"></style>
<style scoped>
/* Estética ligera para el dropdown de notificaciones */
.notif-dropdown { border-radius: 12px; overflow: hidden; }
.notif-dropdown .list-group-item { transition: background-color .15s ease, box-shadow .15s ease; }
.notif-dropdown .list-group-item:hover { background-color: #f8f9fa; }
.action-btn { width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; }

/* Navbar */
.mf-navbar{
  backdrop-filter: blur(8px) saturate(140%);
  -webkit-backdrop-filter: blur(8px) saturate(140%);
  background: linear-gradient(180deg, rgba(255,255,255,.85) 0%, rgba(255,255,255,.65) 100%) !important;
  border-bottom: 1px solid rgba(16,24,40,.06);
}
.mf-brand .brand-mini{ width: 28px; height: 28px; object-fit: contain; border-radius: 8px; }
.fw-700{ font-weight:700; } .fw-600{ font-weight:600; }
.mf-icon-btn{ border-radius: 999px; background: #fff; box-shadow: 0 6px 18px rgba(16,24,40,.08); }
.mf-user-btn{ background: #fff; border: 1px solid rgba(16,24,40,.08); border-radius: 999px; padding: .35rem .6rem; }
.user-avatar{ width: 28px; height: 28px; } .user-avatar-sm{ width: 30px; height: 30px; }
.user-dropdown{ border: 0; border-radius: 14px; overflow: hidden; min-width: 240px; }
.user-dropdown .dropdown-item{ font-weight: 500; border-radius: 8px; margin: 4px 8px; }
.user-dropdown .dropdown-item:hover{ background: #f5f6f8; }

/* Responsivo */
@media (max-width: 991.98px){
  .notif-dropdown{
    min-width: 360px !important; max-width: calc(100vw - 24px) !important;
    right: 12px !important; left: auto !important;
    border-radius: 12px; max-height: calc(100vh - var(--navbar-h) - 24px); overflow: auto;
  }
}
@media (max-width: 575.98px){
  .notif-dropdown{
    position: fixed !important; top: calc(var(--navbar-h) + 8px) !important;
    right: 8px !important; left: 8px !important; min-width: unset !important; width: auto !important;
    max-width: none !important; border-radius: 14px; box-shadow: 0 12px 40px rgba(16,24,40,.18);
    max-height: calc(100vh - var(--navbar-h) - 16px); overflow: auto; z-index: 1301;
  }
  .notif-dropdown .list-group-item{ padding: 12px 14px; font-size: 0.95rem; }
  .action-btn{ width: 40px; height: 40px; }
}
</style>
