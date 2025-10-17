<template>
  <main class="panel-wrapper container-fluid">
    <!-- ===== Toolbar ===== -->
    <div class="toolbar py-2 px-0 px-lg-2">
      <div class="row g-2 align-items-center">
        <div class="col-12 col-lg-8">
          <div
            class="input-group input-group-lg search-group shadow-sm rounded-pill animate__animated animate__fadeInDown"
          >
            <span class="input-group-text rounded-start-pill">
              <i class="bi bi-search"></i>
            </span>
            <input
              v-model.trim="searchQuery"
              type="text"
              class="form-control search-input"
              placeholder="Buscar por alumno, docente, motivo o estado…"
              @input="onInstantSearch"
            />
            <button
              v-if="searchQuery"
              class="btn btn-link text-secondary px-3"
              @click="clearSearch"
              aria-label="Limpiar"
              title="Limpiar búsqueda"
            >
              <i class="bi bi-x-lg"></i>
            </button>
          </div>
        </div>

        <div class="col-12 col-lg-4 text-lg-end mt-2 mt-lg-0">
          <button
            class="btn btn-success fw-semibold shadow pulse-btn rounded-pill px-3 py-2 animate__animated animate__fadeInRight"
            @click="openCreate"
          >
            <i class="bi bi-plus-lg me-1"></i> Nueva cita
          </button>
        </div>
      </div>
    </div>

    <!-- ===== Empty state ===== -->
    <div
      v-if="!filteredItems.length"
      class="text-center text-muted my-5 animate__animated animate__fadeIn"
    >
      <i class="bi bi-calendar2-week fs-1 d-block mb-2"></i>
      <div class="fw-semibold">No se encontraron citas</div>
      <div>Prueba con otra búsqueda o crea una nueva cita.</div>
    </div>

    <!-- ===== Cards ===== -->
    <transition-group
      name="fade"
      tag="div"
      class="row g-3 row-cols-1 row-cols-md-2 row-cols-xxl-3 mt-2"
    >
      <div v-for="c in filteredItems" :key="getId(c)" class="col">
        <div class="card h-100 shadow-sm cita-card">
          <div class="card-body">
            <!-- Encabezado -->
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="text-secondary small text-uppercase fw-semibold">
                  Cita programada para el:
                </span>
                <h5 class="mb-0 fw-bold text-primary">
                  {{ formatDate(c.fecha_cita) }}
                </h5>
              </div>
              <span class="badge rounded-pill" :class="badgeClass(c.estado)">
                {{ c.estado }}
              </span>
            </div>

            <!-- Participantes -->
            <div class="small text-muted mb-1">
              <i class="bi bi-person-badge me-1"></i>
              <strong>Docente:</strong> {{ c.docente_nombre || '—' }}
            </div>
            <div class="small text-muted mb-2">
              <i class="bi bi-person me-1"></i>
              <strong>Alumno:</strong> {{ c.alumno_nombre || '—' }}
            </div>

            <!-- Modalidad -->
            <p class="badge bg-info-subtle text-info border mb-2">
              <i class="bi bi-geo-alt me-1"></i>{{ c.modalidad }}
            </p>

            <!-- Motivo -->
            <p class="card-text clamp-3 mb-0">Motivo: {{ c.motivo }}</p>
          </div>

          <div class="card-footer bg-transparent border-0 pt-0 pb-3 px-3">
            <div class="d-flex flex-column flex-md-row gap-2">
              <button class="btn btn-outline-secondary flex-fill" @click="openView(c)">
                <i class="bi bi-eye me-1"></i> Ver
              </button>
              <button class="btn btn-outline-primary flex-fill" @click="openEdit(c)">
                <i class="bi bi-pencil me-1"></i> Editar
              </button>
              <button class="btn btn-outline-danger flex-fill" @click="confirmDelete(c)">
                <i class="bi bi-trash me-1"></i> Eliminar
              </button>
            </div>
          </div>
        </div>
      </div>
    </transition-group>

    <!-- ===== Modal Form (fijo + scroll + secciones colapsables) ===== -->
    <div class="modal fade" ref="formModalRef" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered modal-lg modal-fixed">
        <form
          class="modal-content shadow-lg animate__animated animate__fadeInUp"
          @submit.prevent="onSubmit"
        >
          <div class="modal-header border-0 rounded-top modal-header-gradient">
            <h5 class="modal-title fw-bold text-white mb-0">
              <i :class="['me-2', isEditing ? 'bi bi-pencil-square' : 'bi bi-plus-circle']"></i>
              {{ isEditing ? 'Modificar Cita' : 'Registrar Cita' }}
            </h5>
            <button type="button" class="btn-close btn-close-white" @click="hideModal" aria-label="Cerrar"></button>
          </div>

          <div class="modal-body">
            <div
              v-if="Object.keys(errors).length"
              class="alert alert-danger animate__animated animate__shakeX"
            >
              <div class="fw-semibold mb-1">Revisa los campos:</div>
              <ul class="mb-0">
                <li v-for="(arr, field) in errors" :key="field">
                  <strong>{{ field }}:</strong> {{ (arr && arr[0]) || '' }}
                </li>
              </ul>
            </div>

            <!-- Participantes (colapsable) -->
            <div class="section mb-3">
              <button
                class="section-toggle"
                type="button"
                @click="sec.participantes = !sec.participantes"
              >
                <i :class="['bi me-2', sec.participantes ? 'bi-chevron-down' : 'bi-chevron-right']"></i>
                Participantes
              </button>
              <transition name="collapse-y">
                <div v-show="sec.participantes" class="section-body">
                  <div class="mb-3">
                    <label class="form-label">Alumno <span class="text-danger">*</span></label>
                    <select
                      v-model="form.alumno_id"
                      class="form-select"
                      required
                      :disabled="user.rol === 'estudiante'"
                    >
                      <option value="" disabled>Selecciona…</option>
                      <option v-for="a in alumnos" :key="a.id" :value="String(a.id)">
                        {{ a.nombre_completo }}
                      </option>
                    </select>
                    <small class="text-muted">Solo usuarios con rol <strong>estudiante</strong>.</small>
                  </div>

                  <div class="mb-2">
                    <label class="form-label">Docente <span class="text-danger">*</span></label>
                    <select v-model="form.docente_id" class="form-select" required>
                      <option value="" disabled>Selecciona…</option>
                      <option v-for="d in docentes" :key="d.id" :value="String(d.id)">
                        {{ d.nombre_completo }}
                      </option>
                    </select>
                    <small class="text-muted">Solo usuarios con rol <strong>profesor</strong>.</small>
                  </div>
                </div>
              </transition>
            </div>

            <!-- Detalles (colapsable) -->
            <div class="section mb-3">
              <button
                class="section-toggle"
                type="button"
                @click="sec.detalles = !sec.detalles"
              >
                <i :class="['bi me-2', sec.detalles ? 'bi-chevron-down' : 'bi-chevron-right']"></i>
                Detalles
              </button>
              <transition name="collapse-y">
                <div v-show="sec.detalles" class="section-body">
                  <div class="row g-2">
                    <div class="col-12 col-sm-6">
                      <label class="form-label">Fecha</label>
                      <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                        <input v-model="form.fecha" type="date" class="form-control" required />
                      </div>
                    </div>
                    <div class="col-12 col-sm-6">
                      <label class="form-label">Hora</label>
                      <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-clock"></i></span>
                        <input v-model="form.hora" type="time" class="form-control" required />
                      </div>
                    </div>
                  </div>

                  <div class="mt-3">
                    <label class="form-label">Modalidad</label>
                    <select v-model="form.modalidad" class="form-select" required>
                      <option value="">Selecciona…</option>
                      <option>Presencial</option>
                      <option>Virtual</option>
                    </select>
                  </div>
                </div>
              </transition>
            </div>

            <!-- Motivo y estado (colapsable) -->
            <div class="section">
              <button
                class="section-toggle"
                type="button"
                @click="sec.motivo = !sec.motivo"
              >
                <i :class="['bi me-2', sec.motivo ? 'bi-chevron-down' : 'bi-chevron-right']"></i>
                Motivo y estado
              </button>
              <transition name="collapse-y">
                <div v-show="sec.motivo" class="section-body">
                  <div class="mb-3">
                    <label class="form-label">Motivo</label>
                    <textarea
                      v-model.trim="form.motivo"
                      rows="3"
                      class="form-control"
                      maxlength="1000"
                      placeholder="Describe brevemente el motivo de la cita"
                    ></textarea>
                  </div>

                  <div>
                    <label class="form-label">Estado</label>
                    <select v-model="form.estado" class="form-select" :disabled="user.rol === 'estudiante'">
                      <option>Pendiente</option>
                      <option>Aceptada</option>
                      <option>Rechazada</option>
                      <option>Finalizada</option>
                    </select>
                    <small
                      v-if="user.rol === 'estudiante'"
                      class="text-muted fst-italic"
                    >
                      Solo profesores o administradores pueden cambiar el estado.
                    </small>
                  </div>
                </div>
              </transition>
            </div>
          </div>

          <div class="modal-footer border-0">
            <button type="button" class="btn btn-outline-secondary" @click="hideModal">
              Cancelar
            </button>
            <button type="submit" class="btn btn-primary" :disabled="saving">
              <span v-if="!saving">{{ isEditing ? 'Guardar cambios' : 'Registrar' }}</span>
              <span v-else class="spinner-border spinner-border-sm ms-2"></span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ===== Modal: Detalle ===== -->
    <div class="modal fade" ref="viewModalRef" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered modal-lg modal-fixed">
        <div class="modal-content border-0 shadow-lg animate__animated animate__fadeInUp">
          <div class="modal-header border-0 rounded-top modal-header-gradient">
            <h5 class="modal-title fw-bold text-white">
              <i class="bi bi-card-text me-2"></i> Detalle de Cita
            </h5>
            <button type="button" class="btn-close btn-close-white" @click="hideView" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <dl class="row gy-3 mb-0">
              <dt class="col-sm-4 col-lg-3">Alumno</dt>
              <dd class="col-sm-8 col-lg-9">{{ selected?.alumno_nombre || '—' }}</dd>

              <dt class="col-sm-4 col-lg-3">Docente</dt>
              <dd class="col-sm-8 col-lg-9">{{ selected?.docente_nombre || '—' }}</dd>

              <dt class="col-sm-4 col-lg-3">Cita programada</dt>
              <dd class="col-sm-8 col-lg-9">
                <span class="fw-semibold">{{ formatDate(selected?.fecha_cita) }}</span>
              </dd>

              <dt class="col-sm-4 col-lg-3">Modalidad</dt>
              <dd class="col-sm-8 col-lg-9">{{ selected?.modalidad || '—' }}</dd>

              <dt class="col-sm-4 col-lg-3">Motivo</dt>
              <dd class="col-sm-8 col-lg-9">{{ selected?.motivo || '—' }}</dd>

              <dt class="col-sm-4 col-lg-3">Estado</dt>
              <dd class="col-sm-8 col-lg-9">
                <span class="badge" :class="badgeClass(selected?.estado)">{{ selected?.estado }}</span>
              </dd>

              <dt class="col-sm-4 col-lg-3">Creado</dt>
              <dd class="col-sm-8 col-lg-9">{{ formatDate(selected?.created_at) }}</dd>
            </dl>
          </div>
          <div class="modal-footer border-0 d-flex w-100">
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-outline-primary" @click="modifyFromView">
                <i class="bi bi-pencil me-1"></i> Editar
              </button>
              <button type="button" class="btn btn-outline-danger" @click="deleteFromView">
                <i class="bi bi-trash me-1"></i> Eliminar
              </button>
            </div>
            <button class="btn btn-secondary ms-auto" @click="hideView">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
  </main>
</template>

<script setup>
import { ref, reactive, computed, onMounted, nextTick } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import 'sweetalert2/dist/sweetalert2.min.css'
import Modal from 'bootstrap/js/dist/modal'
import 'animate.css'

const API_ROOT = process.env.VUE_APP_API_URL?.replace(/\/+$/, '') || ''
const API_BASE  = `${API_ROOT}/citas`
const API_USERS = `${API_ROOT}/users`
const user = safeParse(localStorage.getItem('user') || '{}')

const authHeaders = () => {
  const token = localStorage.getItem('token')
  return token ? { Authorization: `Bearer ${token}` } : {}
}

/* ----- Estado ----- */
const itemsRaw  = ref([])
const alumnos   = ref([])
const docentes  = ref([])

const formModalRef = ref(null)
const viewModalRef = ref(null)
let formModal = null
let viewModal = null

const isEditing   = ref(false)
const saving      = ref(false)
const errors      = reactive({})
const searchQuery = ref('')
const selected    = ref(null)

// secciones colapsables del form
const sec = reactive({ participantes: true, detalles: true, motivo: true })

const form = reactive({
  _id: null,
  alumno_id: '',
  docente_id: '',
  fecha: '',
  hora: '',
  modalidad: '',
  motivo: '',
  estado: 'Pendiente'
})

/* ===== Init ===== */
onMounted(async () => {
  await loadUsuarios()
  await fetchItems()
  formModal = new Modal(formModalRef.value)
  viewModal = new Modal(viewModalRef.value)
})

/* ===== Usuarios por rol ===== */
function normalizePersona (u) {
  const id = String(u?.id ?? u?._id ?? u?.uuid ?? '')
  const full = [u?.nombre, (u?.apellido ?? u?.apellidos)].filter(Boolean).join(' ').trim()
  const base = u?.nombre_completo ?? u?.name ?? (full || null)
  return {
    id,
    nombre_completo: (base ?? '(Sin nombre)'),
    rol: String(u?.rol || '').toLowerCase()
  }
}

async function loadUsuarios () {
  try {
    const { data } = await axios.get(API_USERS, { headers: authHeaders(), params: { per_page: 1000 } })
    const usuarios = (data?.data ?? data?.registros ?? data ?? []).map(normalizePersona)
    alumnos.value  = usuarios.filter(x => x.rol === 'estudiante')
    docentes.value = usuarios.filter(x => x.rol === 'profesor')
  } catch (e) {
    console.error('Error cargando usuarios', e)
    alumnos.value = []
    docentes.value = []
  }
}

/* ===== Citas ===== */
async function fetchItems () {
  try {
    const { data } = await axios.get(API_BASE, { headers: authHeaders() })
    itemsRaw.value = data?.registros || data?.data || []
  } catch (e) {
    console.error(e)
    toast('No fue posible cargar las citas.', 'error')
  }
}

const itemsDecorated = computed(() => itemsRaw.value.map(decorateNames))

const filteredItems = computed(() => {
  const q = (searchQuery.value || '').toLowerCase()
  if (!q) return itemsDecorated.value
  return itemsDecorated.value.filter(c =>
    [c.alumno_nombre, c.docente_nombre, c.motivo, c.estado].some(v => (v || '').toLowerCase().includes(q))
  )
})

function decorateNames (c) {
  const alumnoId  = String(c.alumno_id  ?? c.alumno?._id  ?? c.alumno?.id  ?? '')
  const docenteId = String(c.docente_id ?? c.docente?._id ?? c.docente?.id ?? '')

  const alumnoNombreEmb =
    c.alumno_nombre ??
    c.alumno?.nombre_completo ??
    c.alumno?.name ??
    [c.alumno?.nombre, c.alumno?.apellido, c.alumno?.apellidos].filter(Boolean).join(' ').trim()

  const docenteNombreEmb =
    c.docente_nombre ??
    c.docente?.nombre_completo ??
    c.docente?.name ??
    [c.docente?.nombre, c.docente?.apellido, c.docente?.apellidos].filter(Boolean).join(' ').trim()

  const aLookup = alumnos.value.find(x => String(x.id) === alumnoId)
  const dLookup = docentes.value.find(x => String(x.id) === docenteId)

  return {
    ...c,
    alumno_id: alumnoId || c.alumno_id,
    docente_id: docenteId || c.docente_id,
    alumno_nombre: alumnoNombreEmb || aLookup?.nombre_completo || null,
    docente_nombre: docenteNombreEmb || dLookup?.nombre_completo || null
  }
}

/* ===== Búsqueda ===== */
function onInstantSearch () {}
function clearSearch () { searchQuery.value = '' }

/* ===== Helpers UI ===== */
function getId (obj) { return obj?.id ?? obj?._id ?? null }
function badgeClass (estado) {
  switch ((estado || '').toLowerCase()) {
    case 'aceptada': return 'bg-success'
    case 'rechazada': return 'bg-danger'
    case 'finalizada': return 'bg-secondary'
    default: return 'bg-warning text-dark'
  }
}
function formatDate (v) {
  if (!v) return '—'
  const d = new Date(v)
  return isNaN(d) ? v : d.toLocaleString('es-MX', { dateStyle: 'medium', timeStyle: 'short' })
}

/* ===== Modal: Form ===== */
async function openCreate () {
  isEditing.value = false
  resetForm()
  clearErrors()
  if (!alumnos.value.length || !docentes.value.length) await loadUsuarios()
  if (String(user?.rol || '').toLowerCase() === 'estudiante') {
    form.alumno_id = String(user?.id ?? user?._id ?? '')
  }
  formModal.show()
}

async function openEdit (cita) {
  isEditing.value = true
  clearErrors()
  if (!alumnos.value.length || !docentes.value.length) await loadUsuarios()

  const c = decorateNames(cita)
  form._id = getId(c)
  form.alumno_id = String(c.alumno_id ?? '')
  form.docente_id = String(c.docente_id ?? '')
  const d = new Date(c.fecha_cita)
  if (!isNaN(d)) {
    form.fecha = d.toISOString().slice(0, 10)
    form.hora  = d.toTimeString().slice(0, 5)
  } else {
    form.fecha = ''
    form.hora  = ''
  }
  form.modalidad = c.modalidad || ''
  form.motivo    = c.motivo || ''
  form.estado    = c.estado || 'Pendiente'
  formModal.show()
}

function hideModal () { formModal.hide() }
function resetForm () {
  Object.assign(form, {
    _id: null, alumno_id: '', docente_id: '', fecha: '', hora: '',
    modalidad: '', motivo: '', estado: 'Pendiente'
  })
}
function clearErrors () { Object.keys(errors).forEach(k => delete errors[k]) }

/* ===== Envío ===== */
function combineDateTime () {
  if (!form.fecha || !form.hora) return null
  return `${form.fecha}T${form.hora}:00Z`
}
async function onSubmit () {
  saving.value = true
  clearErrors()
  try {
    const payload = {
      alumno_id: form.alumno_id,
      docente_id: form.docente_id,
      fecha_cita: combineDateTime(),
      modalidad: form.modalidad,
      motivo: form.motivo,
      estado: form.estado
    }
    if (isEditing.value) {
      await axios.put(`${API_BASE}/${form._id}`, payload, { headers: authHeaders() })
      toast('Cita actualizada.')
    } else {
      await axios.post(API_BASE, payload, { headers: authHeaders() })
      toast('Cita registrada.')
    }
    await fetchItems()
    hideModal()
  } catch (e) {
    console.error(e)
    const resp = e.response?.data
    Object.assign(errors, resp?.errors || {})
    toast(resp?.message || 'Ocurrió un error.', 'error')
  } finally {
    saving.value = false
  }
}

/* ===== Modal: Detalle ===== */
function openView (cita) {
  selected.value = decorateNames(cita)
  viewModal.show()
}
function hideView () {
  viewModal.hide()
  selected.value = null
}
async function modifyFromView () {
  if (!selected.value) return
  const c = { ...selected.value }
  hideView()
  await nextTick()
  openEdit(c)
}
async function deleteFromView () {
  if (!selected.value) return
  const c = { ...selected.value }
  hideView()
  await nextTick()
  await confirmDelete(c)
}

/* ===== Delete ===== */
async function confirmDelete (cita) {
  const result = await Swal.fire({
    title: '¿Eliminar cita?',
    text: 'Esta acción no se puede deshacer.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  })
  if (!result.isConfirmed) return
  try {
    await axios.delete(`${API_BASE}/${getId(cita)}`, { headers: authHeaders() })
    await fetchItems()
    toast('Cita eliminada.')
  } catch (e) {
    console.error(e)
    toast('Error al eliminar.', 'error')
  }
}

/* ===== Util & Toast ===== */
function safeParse (str) { try { return JSON.parse(str) } catch { return {} } }
function toast (msg, type = 'success') {
  Swal.fire({ toast: true, icon: type, position: 'top-end', title: msg, showConfirmButton: false, timer: 2000 })
}
</script>

<style scoped>
@import 'animate.css';

/* Espaciado general del shell y suavidad */
.panel-wrapper { padding-block: .75rem; animation: fadeIn .6s ease; }

/* ====== Search ====== */
.search-group .form-control { border: 0; }
.search-group .input-group-text { background: #fff; border: 0; }
.search-input:focus { box-shadow: none; }

/* ====== Cards ====== */
.cita-card { transition: transform .25s ease, box-shadow .25s ease; border-radius: 18px; }
.cita-card:hover { transform: translateY(-4px); box-shadow: 0 10px 26px rgba(0,0,0,.12); }
.clamp-3 { display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; }
.bg-info-subtle { background: rgba(13, 202, 240, 0.12); border-color: rgba(13, 202, 240, 0.28) !important; }

.fade-enter-active, .fade-leave-active { transition: all .28s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(10px); }

/* ====== Pulse button ====== */
.pulse-btn { animation: pulse 2.2s infinite; }
@keyframes pulse {
  0% { box-shadow: 0 0 0 0 rgba(25, 135, 84, .45); }
  70% { box-shadow: 0 0 0 12px rgba(25, 135, 84, 0); }
  100% { box-shadow: 0 0 0 0 rgba(25, 135, 84, 0); }
}

/* ====== Modal fijo con scroll ====== */
.modal-fixed { max-width: 760px; }
.modal-fixed .modal-content { max-height: 82vh; display: flex; }
.modal-fixed .modal-body { overflow: auto; padding-block: 1rem; }

/* Encabezado degradado */
.modal-header-gradient {
  background: linear-gradient(135deg, #4f6ef7, #38bdf8);
}

/* ====== Secciones colapsables ====== */
.section { border: 1px solid #efefef; border-radius: 12px; overflow: hidden; background: #fff; }
.section + .section { margin-top: .75rem; }
.section-toggle {
  width: 100%;
  text-align: left;
  background: #f8f9fa;
  border: 0;
  padding: .75rem .9rem;
  font-weight: 600;
  border-bottom: 1px solid #efefef;
  display: flex; align-items: center;
}
.section-toggle:hover { background: #f3f4f6; }
.section-body { padding: .9rem; }

.collapse-y-enter-active, .collapse-y-leave-active { transition: height .22s ease, opacity .22s ease; overflow: hidden; }
.collapse-y-enter-from, .collapse-y-leave-to { height: 0; opacity: 0; }

/* Util */
@keyframes fadeIn { from {opacity: 0} to {opacity: 1} }
</style>
