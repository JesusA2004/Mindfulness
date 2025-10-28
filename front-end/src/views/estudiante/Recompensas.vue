<!-- src/views/estudiante/RecompensasAlumno.vue -->
<template>
  <main class="panel-wrapper">

    <!-- ======= HERO: Puntos del alumno ======= -->
    <section class="container-fluid hero px-3 px-lg-2 py-5">
      <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
          <div class="hero-inner text-center">
            <h1 class="fw-bolder display-5 display-md-4 mb-2 title-bigger">
              ¡Hola! 🎉
            </h1>

            <p v-if="pointsLoaded && userPoints > 0" class="lead mb-3 opacity-85">
              Tienes
              <span class="badge rounded-pill bg-success-subtle text-success-emphasis fs-5">{{ userPoints }}</span>
              puntos disponibles. <br class="d-none d-md-inline" />
              ¡Sigue así! Puedes canjear recompensas ahora mismo.
            </p>

            <p v-else-if="pointsLoaded && userPoints === 0" class="lead mb-3 opacity-85">
              Aún no tienes puntos. 💫
              <span class="d-block mt-1">Registra tu <strong>Bitácora del día</strong> para empezar a ganar.</span>
            </p>

            <p v-else class="lead mb-3 opacity-85">Cargando tus puntos…</p>

            <div class="d-flex justify-content-center gap-2 flex-wrap mt-2">
              <button
                class="btn btn-outline-light bg-white text-dark rounded-pill px-3"
                @click="fetchPoints"
                :disabled="pointsLoading"
              >
                <i v-if="!pointsLoading" class="bi bi-arrow-clockwise me-1"></i>
                <span v-if="!pointsLoading">Actualizar puntos</span>
                <span v-else class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
              </button>

              <router-link
                to="/app/estudiante/bitacoras"
                class="btn btn-light text-primary-emphasis rounded-pill px-3"
                aria-label="Ir a Bitácora"
              >
                <i class="bi bi-journal-text me-1"></i> Ir a Bitácora para ganar puntos
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ======= Toolbar: Búsqueda + Filtros ======= -->
    <div class="container-fluid toolbar px-3 px-lg-2">
      <div class="row g-2 align-items-center">
        <!-- Buscador -->
        <div class="col-12 col-lg-8">
          <div
            class="input-group input-group-lg search-group shadow-sm rounded-pill"
            role="search"
            aria-label="Buscador de recompensas"
          >
            <span class="input-group-text rounded-start-pill">
              <i class="bi bi-search"></i>
            </span>

            <input
              v-model.trim="searchQuery"
              type="search"
              class="form-control search-input"
              placeholder="Buscar recompensa por nombre…"
              @input="onInstantSearch"
              aria-label="Buscar por nombre"
            />

            <button
              v-if="searchQuery"
              class="btn btn-link text-secondary px-3 d-none d-md-inline"
              @click="clearSearch"
              aria-label="Limpiar búsqueda"
            >
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          <!-- Botón limpiar móvil -->
          <div class="d-flex d-md-none justify-content-end mt-2" v-if="searchQuery">
            <button
              class="btn btn-sm btn-outline-secondary rounded-pill"
              @click="clearSearch"
              aria-label="Limpiar búsqueda móvil"
            >
              <i class="bi bi-x-lg me-1"></i> Limpiar
            </button>
          </div>
        </div>

        <!-- Filtros a la derecha (misma altura que el buscador) -->
        <div class="col-12 col-lg-4 mt-2 mt-lg-0">
          <div class="filter-wrap d-flex gap-2 justify-content-lg-end">
            <button
              type="button"
              class="btn btn-outline-secondary btn-filter"
              :class="{ active: filterMode === 'disponibles' }"
              @click="filterMode = 'disponibles'"
              title="Ver sólo recompensas que puedes canjear y no has canjeado"
            >
              <i class="bi bi-box-seam me-1"></i> Disponibles
            </button>
            <button
              type="button"
              class="btn btn-outline-secondary btn-filter"
              :class="{ active: filterMode === 'canjeadas' }"
              @click="filterMode = 'canjeadas'"
              title="Ver recompensas que ya canjeaste"
            >
              <i class="bi bi-bag-check me-1"></i> Canjeadas
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ======= Grid de Recompensas ======= -->
    <div class="container-fluid px-3 px-lg-2">
      <div class="row g-3 row-cols-1 row-cols-sm-2 row-cols-lg-3">
        <div v-for="item in gridItems" :key="getId(item)" class="col">
          <div class="card h-100 item-card shadow-sm">
            <div class="card-body d-flex flex-column">
              <!-- Encabezado: nombre + estado -->
              <div class="d-flex justify-content-between align-items-start mb-2">
                <h5 class="card-title mb-0 text-truncate fw-bold" :title="item.nombre">
                  {{ item.nombre }}
                </h5>
                <span
                  v-if="!isRedeemedByUser(item)"
                  class="badge rounded-pill"
                  :class="stockBadgeClass(item.stock)"
                  :title="stockTitle(item.stock)"
                >
                  {{ stockLabel(item.stock) }}
                </span>
                <span
                  v-else
                  class="badge rounded-pill bg-primary-subtle text-primary-emphasis"
                  title="Ya canjeada por ti"
                >
                  Canjeada
                </span>
              </div>

              <!-- Ícono representativo -->
              <div class="mb-3" aria-hidden="true">
                <i class="bi bi-gift" style="font-size:2rem;"></i>
              </div>

              <!-- Puntos necesarios -->
              <div class="mb-2">
                <span class="fw-semibold">
                  <i class="bi bi-stars me-1"></i>{{ item.puntos_necesarios }} punto(s)
                </span>
                <i
                  class="bi bi-info-circle ms-1 text-primary"
                  data-bs-toggle="tooltip"
                  title="Puntos requeridos para canjear esta recompensa."
                ></i>
              </div>

              <!-- Descripción -->
              <p class="card-text clamp-3 mb-2" v-if="item.descripcion">{{ item.descripcion }}</p>

              <!-- Motivación si no alcanza puntos (y no está canjeada) -->
              <div
                v-if="!isRedeemedByUser(item) && !canUserAfford(item)"
                class="alert alert-light border small d-flex align-items-start gap-2 mt-auto"
              >
                <i class="bi bi-emoji-smile text-primary"></i>
                <div>
                  <strong>Te faltan {{ faltantes(item) }} punto(s).</strong>
                  <div class="mt-1">
                    Suma puntos registrando tu
                    <router-link to="/app/estudiante/bitacoras">Bitácora del día</router-link>.
                  </div>
                </div>
              </div>
            </div>

            <!-- Acciones -->
            <div class="card-footer bg-transparent border-0 pt-0 pb-3 px-3">
              <div class="d-grid">
                <button
                  class="btn btn-gradient fw-semibold shadow-sm rounded-pill"
                  :disabled="isRedeemedByUser(item) || !canUserAfford(item) || canjeandoId === getId(item)"
                  @click="openConfirm(item)"
                >
                  <template v-if="isRedeemedByUser(item)">Ya canjeada</template>
                  <template v-else-if="canjeandoId !== getId(item)">Canjear</template>
                  <template v-else>
                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                    Procesando…
                  </template>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Vacío -->
        <div v-if="!isLoading && gridItems.length === 0" class="col-12">
          <div class="alert alert-light border d-flex align-items-center gap-2">
            <i class="bi bi-inbox text-secondary fs-4"></i>
            <div>
              <strong>Sin resultados.</strong>
              Ajusta la búsqueda o cambia de filtro.
            </div>
          </div>
        </div>

        <!-- Skeletons -->
        <div v-if="isLoading" class="col" v-for="n in 6" :key="'sk'+n">
          <div class="card h-100 shadow-sm">
            <div class="card-body">
              <div class="placeholder-glow">
                <span class="placeholder col-8"></span>
                <p class="mt-2 mb-0">
                  <span class="placeholder col-12"></span>
                  <span class="placeholder col-11"></span>
                  <span class="placeholder col-9"></span>
                </p>
              </div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0 pb-3 px-3">
              <div class="d-flex gap-2">
                <span class="placeholder col-12"></span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Paginación opcional (deshabilitada por ahora) -->
      <div class="d-flex justify-content-center my-4" v-if="false">
        <button class="btn btn-outline-secondary btn-lg" disabled>Cargar más</button>
      </div>
    </div>

    <!-- ======= Modal de confirmación de canje ======= -->
    <div class="modal fade" ref="confirmModalRef" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header border-0">
            <h5 class="modal-title fw-bold">Confirmar canje</h5>
            <button type="button" class="btn-close" @click="hideConfirm" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <p class="mb-0">
              ¿Deseas canjear <strong>{{ selected && selected.nombre }}</strong>
              por <strong>{{ selected && selected.puntos_necesarios }}</strong> punto(s)?
            </p>
          </div>
          <div class="modal-footer border-0">
            <button class="btn btn-outline-secondary" @click="hideConfirm">Cancelar</button>
            <button class="btn btn-primary" :disabled="processing" @click="confirmRedeem">
              <span v-if="!processing">Confirmar</span>
              <span v-else class="spinner-border spinner-border-sm ms-1" role="status" aria-hidden="true"></span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ======= Toasts ======= -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1080;">
      <div v-if="toast.show" class="toast align-items-center text-bg-{{ toast.variant }} border-0 show">
        <div class="d-flex">
          <div class="toast-body">{{ toast.message }}</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" @click="toast.show = false" aria-label="Cerrar"></button>
        </div>
      </div>
    </div>

  </main>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue'
import axios from 'axios'
import Modal from 'bootstrap/js/dist/modal'
import Tooltip from 'bootstrap/js/dist/tooltip'

/* ====== BASE API ====== */
const API_ROOT = (process.env.VUE_APP_API_URL || '').replace(/\/+$/, '')
const RECOMPENSAS_API = `${API_ROOT}/recompensas`
const AUTH_PROFILE    = `${API_ROOT}/auth/user-profile`
const USERS_POINTS    = (id) => `${API_ROOT}/users/${id}/points`
const USERS_EARN      = (id) => `${API_ROOT}/users/${id}/points/earn`
const USERS_REDEEM    = (id) => `${API_ROOT}/users/${id}/points/redeem`

/* ====== HEADERS AUTH ====== */
function mergedAuthHeaders () {
  const h = {}
  const tokenType   = localStorage.getItem('token_type') || 'Bearer'
  const accessToken = localStorage.getItem('token')
  if (accessToken) h.Authorization = `${tokenType} ${accessToken}`
  return h
}

/* ====== USER ID (como Bitácoras) ====== */
function getUserIdFromLocalStorage() {
  try {
    const u = JSON.parse(localStorage.getItem('user') || '{}')
    return u?._id || u?.id || null
  } catch { return null }
}
const currentUserId = ref(null)

/* ====== ESTADO ====== */
const isLoading = ref(false)
const items = ref([]) // recompensas
const searchQuery = ref('')

/* filtros: disponibles | canjeadas */
const filterMode = ref('disponibles')

const selected = ref(null)

const userPoints = ref(0)
const pointsLoading = ref(false)
const pointsLoaded = ref(false)

const canjeandoId = ref(null)
const processing = ref(false)

/* Modal confirm */
const confirmModalRef = ref(null)
let confirmModalInstance = null

/* Toast */
const toast = ref({ show: false, message: '', variant: 'success' })

/* ====== COMPUTED ====== */
const filteredItems = computed(() => {
  const q = (searchQuery.value || '').toLowerCase()
  if (!q) return items.value
  return items.value.filter(r => (r?.nombre || '').toLowerCase().includes(q))
})

/* ¿El usuario ya canjeó esta recompensa? */
function isRedeemedByUser(item) {
  const uid = currentUserId.value
  if (!uid) return false
  const arr = Array.isArray(item?.canjeo) ? item.canjeo : []
  return arr.some(c => String(c?.usuario_id || '') === String(uid))
}

/* Disponibles: con stock > 0, que NO estén canjeadas por el usuario */
const onlyAvailable = computed(() =>
  filteredItems.value.filter(r => (r?.stock ?? 0) > 0 && !isRedeemedByUser(r))
)

/* Canjeadas: las que el usuario ya canjeó (sin importar stock actual) */
const onlyRedeemed = computed(() =>
  filteredItems.value.filter(r => isRedeemedByUser(r))
)

/* Lo que se muestra en grid según filtro */
const gridItems = computed(() => {
  return filterMode.value === 'canjeadas' ? onlyRedeemed.value : onlyAvailable.value
})

/* ====== UI helpers ====== */
function getId(item) { return item?._id || item?.id || '' }
function stockBadgeClass(stock) {
  if (stock > 10) return 'bg-success-subtle text-success-emphasis'
  if (stock > 0) return 'bg-warning-subtle text-warning-emphasis'
  return 'bg-secondary'
}
function stockLabel(stock) {
  if (stock > 10) return 'Disponible'
  if (stock > 0) return `Pocas (${stock})`
  return 'Agotada'
}
function stockTitle(stock) {
  if (stock > 10) return 'Stock suficiente'
  if (stock > 0) return 'Stock limitado'
  return 'Sin existencias'
}
function onInstantSearch() {/* reactivo por v-model */}
function clearSearch() { searchQuery.value = '' }
function faltantes(item) {
  const req = parseInt(item?.puntos_necesarios ?? 0)
  return Math.max(0, req - (userPoints.value || 0))
}
function canUserAfford(item) {
  const req = parseInt(item?.puntos_necesarios ?? 0)
  return (userPoints.value || 0) >= req
}

/* ====== API ====== */
async function resolveCurrentUserId() {
  let uid = getUserIdFromLocalStorage()
  if (uid) { currentUserId.value = uid; return uid }
  try {
    const { data: profile } = await axios.get(AUTH_PROFILE, { headers: mergedAuthHeaders() })
    uid = profile?.user?._id || profile?.user?.id || null
  } catch (_) { uid = null }
  currentUserId.value = uid
  return uid
}

async function fetchPoints() {
  pointsLoading.value = true
  try {
    const uid = currentUserId.value || await resolveCurrentUserId()
    if (!uid) { showToast('No fue posible identificar al usuario (ID).', 'danger'); return }
    const { data } = await axios.get(USERS_POINTS(uid), { headers: mergedAuthHeaders() })
    userPoints.value = parseInt(data?.puntosCanjeo ?? 0)
    pointsLoaded.value = true
  } catch (e) {
    showToast('No fue posible consultar tus puntos.', 'danger')
  } finally {
    pointsLoading.value = false
  }
}

async function fetchRecompensas() {
  try {
    isLoading.value = true
    const { data } = await axios.get(RECOMPENSAS_API, { headers: mergedAuthHeaders() })
    // La API regresa { registros: [...] }
    items.value = Array.isArray(data?.registros)
      ? data.registros.map(normalizeRecompensa)
      : []
  } catch (e) {
    showToast('No fue posible cargar las recompensas.', 'danger')
  } finally {
    isLoading.value = false
  }
}

function normalizeRecompensa(r) {
  return {
    _id: r?._id || r?.id,
    nombre: r?.nombre || '',
    descripcion: r?.descripcion || '',
    puntos_necesarios: parseInt(r?.puntos_necesarios ?? 0),
    stock: parseInt(r?.stock ?? 0),
    canjeo: Array.isArray(r?.canjeo) ? r.canjeo : [],
  }
}

/* ====== Modal confirm ====== */
function openConfirm(item) {
  if (isRedeemedByUser(item)) return
  selected.value = normalizeRecompensa(item)
  if (!confirmModalInstance && confirmModalRef.value) {
    confirmModalInstance = new Modal(confirmModalRef.value, { backdrop: 'static' })
  }
  confirmModalInstance?.show()
}
function hideConfirm() {
  confirmModalInstance?.hide()
  selected.value = null
}

/* ====== Redeem Flow ====== */
async function confirmRedeem() {
  if (!selected.value) return
  const item = selected.value
  const rewardId = getId(item)
  const puntos = parseInt(item.puntos_necesarios)

  canjeandoId.value = rewardId
  processing.value = true

  try {
    const uid = currentUserId.value || await resolveCurrentUserId()
    if (!uid) { showToast('No se pudo validar el usuario.', 'danger'); return }

    if (isRedeemedByUser(item)) {
      showToast('Esta recompensa ya fue canjeada por ti.', 'warning')
      return
    }

    // 1) Restar puntos
    await axios.post(USERS_REDEEM(uid), { puntos }, { headers: mergedAuthHeaders() })

    // 2) Descontar stock y registrar canjeo
    const fecha = new Date().toISOString().slice(0, 10) // YYYY-MM-DD
    const payload = {
      nombre: item.nombre,
      descripcion: item.descripcion || '',
      puntos_necesarios: item.puntos_necesarios,
      stock: Math.max(0, (item.stock ?? 0) - 1),
      canjeo: [
        ...(item.canjeo || []),
        { usuario_id: uid, fechaCanjeo: fecha },
      ],
    }

    await axios.put(`${RECOMPENSAS_API}/${rewardId}`, payload, { headers: mergedAuthHeaders() })

    // 3) Actualizar UI
    userPoints.value = (userPoints.value || 0) - puntos
    const idx = items.value.findIndex(r => getId(r) === rewardId)
    if (idx !== -1) items.value[idx] = normalizeRecompensa({ ...payload, _id: rewardId })

    showToast('¡Canje realizado correctamente! 🎁', 'success')
  } catch (e) {
    // Compensación si falla el PUT tras restar puntos
    try {
      const uid = currentUserId.value
      if (uid) await axios.post(USERS_EARN(uid), { puntos }, { headers: mergedAuthHeaders() })
    } catch (_) {}
    const msg = e?.response?.data?.message || 'No fue posible completar el canje.'
    showToast(msg, 'danger')
  } finally {
    processing.value = false
    canjeandoId.value = null
    hideConfirm()
  }
}

/* ====== Toast helper ====== */
function showToast(message, variant = 'success') {
  toast.value = { show: true, message, variant }
  setTimeout(() => (toast.value.show = false), 4000)
}

/* ====== Lifecycle ====== */
onMounted(async () => {
  await resolveCurrentUserId()
  await Promise.all([fetchPoints(), fetchRecompensas()])

  // Tooltips Bootstrap
  const triggerEls = Array.from(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  triggerEls.forEach(el => new Tooltip(el))
})
</script>

<style scoped>
@import '@/assets/css/Crud.css';

/* ===== Altura compartida entre buscador y filtros ===== */
.toolbar { --control-h: 52px; }           /* altura "elegante"; coincide con input-group-lg */
@media (max-width: 991.98px) {
  .toolbar { --control-h: 48px; }         /* un pelín más compacto en pantallas chicas */
}

/* Search group */
.search-group {
  min-height: var(--control-h);
}
.search-group .form-control,
.search-group .input-group-text,
.search-group .btn {
  height: var(--control-h);
  display: flex;
  align-items: center;
}
.search-group .form-control { border: none; outline: none; }
.search-group .input-group-text { background: transparent; border: none; }

/* Filtros (mismo alto que el buscador, sin verse enormes) */
.filter-wrap .btn-filter {
  height: var(--control-h);
  padding: 0 16px;
  font-size: 0.95rem;
  border-radius: 999px;
  line-height: 1;             /* evita crecimiento vertical por line-height */
}
.btn-filter.active {
  background-color: #0d6efd;
  color: #fff;
  border-color: #0d6efd;
}

/* Hero en tonos pastel más bajos */
.hero {
  background: linear-gradient(180deg, rgba(111, 66, 193, 0.10) 0%, rgba(25, 135, 84, 0.08) 100%);
  border-radius: 0 0 24px 24px;
}
.title-bigger { letter-spacing: -0.3px; }

/* Cards catálogo */
.item-card {
  border: 1px solid rgba(0,0,0,0.04);
  transition: transform .15s ease, box-shadow .15s ease;
}
.item-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 24px rgba(0,0,0,0.08);
}

/* Clamps – incluye propiedad estándar para evitar warning */
.clamp-3 {
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 3; /* WebKit */
  line-clamp: 3;         /* Estándar */
  overflow: hidden;
}

/* Botón gradiente */
.btn-gradient {
  background: linear-gradient(90deg, #6f42c1 0%, #198754 100%);
  color: #fff;
  border: none;
}
.btn-gradient:hover { filter: brightness(1.05); }

/* Badges Bootstrap 5.3 tints */
.bg-success-subtle { background-color: rgba(25, 135, 84, .15) !important; }
.text-success-emphasis { color: #146c43 !important; }
.bg-warning-subtle { background-color: rgba(255, 193, 7, .15) !important; }
.text-warning-emphasis { color: #997404 !important; }

/* Badge canjeada */
.bg-primary-subtle { background-color: rgba(13, 110, 253, .15) !important; }
.text-primary-emphasis { color: #0a58ca !important; }
</style>
