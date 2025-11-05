<template>
  <main class="panel-wrapper container-fluid py-4">
    <!-- ======= Encabezado ======= -->
    <div class="d-flex justify-content-between align-items-center mb-3 animate__animated animate__fadeInDown">
      <h2 class="fw-bold text-primary mb-0">
        <i class="bi bi-calendar2-week me-2"></i> Mis citas
      </h2>
      <button class="btn btn-success fw-semibold shadow rounded-pill px-3 py-2" @click="openCreate">
        <i class="bi bi-plus-lg me-1"></i> Solicitar cita
      </button>
    </div>

    <!-- ======= Barra de búsqueda ======= -->
    <div class="input-group input-group-lg shadow-sm rounded-pill mb-4 animate__animated animate__fadeIn">
      <span class="input-group-text rounded-start-pill"><i class="bi bi-search"></i></span>
      <input v-model="search" type="text" class="form-control" placeholder="Buscar por profesor, motivo o estado…" />
      <button v-if="search" class="btn btn-link text-secondary px-3" @click="search = ''">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <!-- ======= Listado de citas ======= -->
    <transition-group name="fade" tag="div" class="row g-3 row-cols-1 row-cols-md-2 row-cols-xl-3">
      <div v-for="c in filteredCitas" :key="c.id" class="col">
        <div class="card shadow-sm h-100 animate__animated animate__fadeInUp">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div>
                <span class="small text-muted">Programada para</span>
                <h5 class="fw-bold mb-0">{{ formatDatePretty(c.fecha_cita) }}</h5>
              </div>
              <span class="badge rounded-pill" :class="badgeClass(c.estado)">
                {{ c.estado }}
              </span>
            </div>
            <p class="text-muted mb-1"><i class="bi bi-person-badge me-1"></i><strong>Docente:</strong> {{ c.docente_nombre || '—' }}</p>
            <p class="text-muted mb-1"><i class="bi bi-geo-alt me-1"></i><strong>Modalidad:</strong> {{ c.modalidad }}</p>
            <p class="text-muted mb-0"><strong>Motivo:</strong> {{ c.motivo || '—' }}</p>
          </div>
          <div class="card-footer bg-transparent border-0 text-end pt-0 pb-3 px-3">
            <button class="btn btn-outline-primary" @click="verDetalle(c)">
              <i class="bi bi-eye me-1"></i> Ver
            </button>
          </div>
        </div>
      </div>
    </transition-group>

    <div v-if="!filteredCitas.length" class="text-center text-muted mt-5 animate__animated animate__fadeIn">
      <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
      <p class="mb-0">No tienes citas registradas</p>
    </div>

    <!-- ======= Modal: Registrar cita ======= -->
    <div class="modal fade" ref="formModalRef" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <form class="modal-content animate__animated animate__fadeInUp" @submit.prevent="registrarCita">
          <div class="modal-header border-0 modal-header-gradient text-white">
            <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i> Solicitar nueva cita</h5>
            <button type="button" class="btn-close btn-close-white" @click="hideModal"></button>
          </div>
          <div class="modal-body">
            <div v-if="hasErrors" class="alert alert-danger animate__animated animate__shakeX">
              <ul class="mb-0">
                <li v-for="(arr, field) in errors" :key="field"><strong>{{ field }}:</strong> {{ arr[0] }}</li>
              </ul>
            </div>
            <div class="mb-3">
              <label class="form-label">Fecha <span class="text-danger">*</span></label>
              <input type="date" v-model="form.fecha" class="form-control" :min="today" required />
            </div>
            <div class="mb-3">
              <label class="form-label">Hora <span class="text-danger">*</span></label>
              <input type="time" v-model="form.hora" class="form-control" required />
            </div>
            <div class="mb-3">
              <label class="form-label">Modalidad <span class="text-danger">*</span></label>
              <select v-model="form.modalidad" class="form-select" required>
                <option value="">Selecciona…</option>
                <option>Presencial</option>
                <option>Virtual</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Profesor <span class="text-danger">*</span></label>
              <select v-model="form.docente_id" class="form-select" required>
                <option value="">Selecciona…</option>
                <option v-for="d in docentes" :key="d.id" :value="String(d.id)">
                  {{ d.nombre_completo }}
                </option>
              </select>
            </div>
            <div class="mb-2">
              <label class="form-label">Motivo</label>
              <textarea v-model.trim="form.motivo" rows="3" maxlength="1000" class="form-control"
                placeholder="Describe brevemente el motivo"></textarea>
            </div>
          </div>
          <div class="modal-footer border-0">
            <button type="button" class="btn btn-outline-secondary" @click="hideModal">Cancelar</button>
            <button type="submit" class="btn btn-primary" :disabled="saving">
              <span v-if="!saving">Registrar</span>
              <span v-else class="spinner-border spinner-border-sm"></span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ======= Modal: Detalle ======= -->
    <div class="modal fade" ref="detalleModalRef" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg animate__animated animate__fadeInUp">
          <div class="modal-header border-0 modal-header-gradient text-white">
            <h5 class="modal-title fw-bold"><i class="bi bi-card-text me-2"></i> Detalle de cita</h5>
            <button type="button" class="btn-close btn-close-white" @click="hideDetalle"></button>
          </div>
          <div class="modal-body">
            <dl class="row gy-2 mb-0">
              <dt class="col-sm-4">Docente</dt>
              <dd class="col-sm-8">{{ selected?.docente_nombre || '—' }}</dd>
              <dt class="col-sm-4">Fecha</dt>
              <dd class="col-sm-8">{{ formatDatePretty(selected?.fecha_cita) }}</dd>
              <dt class="col-sm-4">Modalidad</dt>
              <dd class="col-sm-8">{{ selected?.modalidad }}</dd>
              <dt class="col-sm-4">Motivo</dt>
              <dd class="col-sm-8">{{ selected?.motivo || '—' }}</dd>
              <dt class="col-sm-4">Estado</dt>
              <dd class="col-sm-8">
                <span class="badge" :class="badgeClass(selected?.estado)">{{ selected?.estado }}</span>
              </dd>
              <dt class="col-sm-4">Observaciones</dt>
              <dd class="col-sm-8">{{ selected?.observaciones || '—' }}</dd>
            </dl>
          </div>
          <div class="modal-footer border-0">
            <button class="btn btn-secondary ms-auto" @click="hideDetalle">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
  </main>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'
import Modal from 'bootstrap/js/dist/modal'
import { toast, authHeaders, apiBase, toDateInputValue } from '@/assets/js/crudUtils.js'

const API = apiBase('alumno/citas')
const API_USERS = apiBase('users')
const user = JSON.parse(localStorage.getItem('user') || '{}')

const citas = ref([])
const docentes = ref([])
const formModalRef = ref(null)
const detalleModalRef = ref(null)
let formModal = null
let detalleModal = null

const search = ref('')
const saving = ref(false)
const selected = ref(null)
const today = toDateInputValue(new Date())

const form = reactive({
  fecha: '',
  hora: '',
  modalidad: '',
  docente_id: '',
  motivo: ''
})

const errors = reactive({})
const hasErrors = computed(() => Object.keys(errors).length > 0)

function clearErrors () {
  for (const k of Object.keys(errors)) delete errors[k]
}

/* ======= Init ======= */
onMounted(async () => {
  await cargarDocentes()
  await cargarCitas()
  formModal = new Modal(formModalRef.value)
  detalleModal = new Modal(detalleModalRef.value)
})

async function cargarDocentes () {
  try {
    const { data } = await axios.get(API_USERS, { headers: authHeaders(), params: { per_page: 1000 } })
    const users = (data?.data || []).filter(u => String(u.rol || '').toLowerCase() === 'profesor')
    docentes.value = users.map(u => ({
      id: String(u._id || u.id),
      nombre_completo: u.nombre_completo || u.name || [u.nombre, u.apellido, u.apellidos].filter(Boolean).join(' ')
    }))
  } catch (err) { console.error(err) }
}

async function cargarCitas () {
  try {
    const { data } = await axios.get(API, { headers: authHeaders() })
    citas.value = (data?.data || data?.registros || [])
      .sort((a, b) => new Date(a.fecha_cita) - new Date(b.fecha_cita))
  } catch (err) {
    console.error(err)
    toast('No fue posible cargar tus citas', 'error')
  }
}

/* ======= Helpers ======= */
function badgeClass (estado) {
  switch ((estado || '').toLowerCase()) {
    case 'aceptada': return 'bg-success'
    case 'rechazada': return 'bg-danger'
    case 'finalizada': return 'bg-secondary'
    default: return 'bg-warning text-dark'
  }
}
function formatDatePretty (v) {
  if (!v) return '—'
  const d = new Date(v)
  return isNaN(d) ? v : d.toLocaleString('es-MX', { dateStyle: 'medium', timeStyle: 'short' })
}
const filteredCitas = computed(() => {
  const q = search.value.toLowerCase()
  if (!q) return citas.value
  return citas.value.filter(c =>
    [c.docente_nombre, c.motivo, c.estado].some(v => (v || '').toLowerCase().includes(q))
  )
})

/* ======= Modal registro ======= */
function openCreate () { 
  clearErrors()
  Object.assign(form, { fecha: '', hora: '', modalidad: '', docente_id: '', motivo: '' }); 
  formModal.show() 
}
function hideModal () { formModal.hide() }

async function registrarCita () {
  clearErrors()

  const fechaISO = construirISO(form.fecha, form.hora)
  if (!fechaISO) return toast('Fecha u hora inválida', 'error')

  saving.value = true
  try {
    const payload = { docente_id: form.docente_id, fecha_cita: fechaISO, modalidad: form.modalidad, motivo: form.motivo }
    await axios.post(API, payload, { headers: authHeaders() })
    toast('Cita registrada exitosamente')
    await cargarCitas()
    hideModal()
    clearErrors()
  } catch (e) {
    console.error(e)
    toast(e.response?.data?.message || 'Error al registrar cita', 'error')
    Object.assign(errors, e.response?.data?.errors || {})
  } finally { saving.value = false }
}

/* ======= Modal detalle ======= */
function verDetalle (c) { selected.value = c; detalleModal.show() }
function hideDetalle () { detalleModal.hide(); selected.value = null }

/* ======= Helpers de tiempo ======= */
function construirISO (fecha, hora) {
  if (!fecha || !hora) return null
  const [Y, M, D] = fecha.split('-').map(Number)
  const [h, m] = hora.split(':').map(Number)
  const d = new Date(Y, M - 1, D, h, m)
  const tz = -d.getTimezoneOffset()
  const s = tz >= 0 ? '+' : '-'
  const pad = (n) => String(Math.abs(n)).padStart(2, '0')
  return `${d.toISOString().slice(0, 19)}${s}${pad(Math.floor(Math.abs(tz) / 60))}:${pad(Math.abs(tz) % 60)}`
}
</script>

<style scoped>
.modal-header-gradient {
  background: linear-gradient(90deg, #2563eb, #7c3aed);
}
.card {
  border-radius: 1rem;
}
.card:hover {
  transform: translateY(-4px);
  transition: all 0.3s ease;
}
</style>
