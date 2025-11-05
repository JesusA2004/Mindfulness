<!-- src/views/administrador/Citas.vue -->
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
                  {{ formatDatePretty(c.fecha_cita) }}
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
            <p class="card-text clamp-3 mb-0">Motivo: {{ c.motivo || '—' }}</p>
          </div>

          <div class="card-footer bg-transparent border-0 pt-0 pb-3 px-3">
            <div class="d-flex flex-column flex-md-row gap-2">
              <button class="btn btn-outline-secondary flex-fill" @click="openView(c)">
                <i class="bi bi-eye me-1"></i> Ver
              </button>
              <button class="btn btn-outline-primary flex-fill" @click="openEdit(c)">
                <i class="bi bi-pencil me-1"></i> Editar
              </button>
            </div>
          </div>
        </div>
      </div>
    </transition-group>

    <!-- ===== Modal Form ===== -->
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
            <div v-if="hasErrors" class="alert alert-danger animate__animated animate__shakeX">
              <div class="fw-semibold mb-1">Revisa los campos:</div>
              <ul class="mb-0">
                <li v-for="(arr, field) in errors" :key="field">
                  <strong>{{ field }}:</strong> {{ (arr && arr[0]) || '' }}
                </li>
              </ul>
            </div>

            <!-- Participantes -->
            <div class="section mb-3">
              <button class="section-toggle" type="button" @click="sec.participantes = !sec.participantes">
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
                      :disabled="userRole === 'estudiante'"
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

            <!-- Detalles -->
            <div class="section mb-3">
              <button class="section-toggle" type="button" @click="sec.detalles = !sec.detalles">
                <i :class="['bi me-2', sec.detalles ? 'bi-chevron-down' : 'bi-chevron-right']"></i>
                Detalles
              </button>
              <transition name="collapse-y">
                <div v-show="sec.detalles" class="section-body">
                  <div class="row g-2">
                    <div class="col-12 col-sm-6">
                      <label class="form-label">Fecha <span class="text-danger">*</span></label>
                      <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                        <input
                          v-model="form.fecha"
                          type="date"
                          class="form-control"
                          :min="today"
                          required
                        />
                      </div>
                    </div>
                    <div class="col-12 col-sm-6">
                      <label class="form-label">Hora <span class="text-danger">*</span></label>
                      <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-clock"></i></span>
                        <input v-model="form.hora" type="time" class="form-control" required />
                      </div>
                    </div>
                  </div>

                  <div class="mt-3">
                    <label class="form-label">Modalidad <span class="text-danger">*</span></label>
                    <select v-model="form.modalidad" class="form-select" required>
                      <option value="">Selecciona…</option>
                      <option>Presencial</option>
                      <option>Virtual</option>
                    </select>
                  </div>
                </div>
              </transition>
            </div>

            <!-- Motivo / Estado / Observaciones -->
            <div class="section">
              <button class="section-toggle" type="button" @click="sec.motivo = !sec.motivo">
                <i :class="['bi me-2', sec.motivo ? 'bi-chevron-down' : 'bi-chevron-right']"></i>
                Motivo, estado y observaciones
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

                  <div class="mb-3">
                    <label class="form-label">Estado</label>
                    <select v-model="form.estado" class="form-select" :disabled="userRole === 'estudiante'">
                      <option>Pendiente</option>
                      <option>Aceptada</option>
                      <option>Rechazada</option>
                      <option>Finalizada</option>
                    </select>
                    <small v-if="userRole === 'estudiante'" class="text-muted fst-italic">
                      Solo profesores o administradores pueden cambiar el estado.
                    </small>
                  </div>

                  <div class="mb-1">
                    <label class="form-label">Observaciones</label>
                    <textarea
                      v-model.trim="form.observaciones"
                      rows="3"
                      class="form-control"
                      maxlength="2000"
                      placeholder="Notas internas o acuerdos específicos (opcional)"
                    ></textarea>
                    <small class="text-muted">Máx. 2000 caracteres.</small>
                  </div>
                </div>
              </transition>
            </div>
          </div>

          <div class="modal-footer border-0">
            <button type="button" class="btn btn-outline-secondary" @click="hideModal">Cancelar</button>
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
                <span class="fw-semibold">{{ formatDatePretty(selected?.fecha_cita) }}</span>
              </dd>

              <dt class="col-sm-4 col-lg-3">Modalidad</dt>
              <dd class="col-sm-8 col-lg-9">{{ selected?.modalidad || '—' }}</dd>

              <dt class="col-sm-4 col-lg-3">Motivo</dt>
              <dd class="col-sm-8 col-lg-9">{{ selected?.motivo || '—' }}</dd>

              <dt class="col-sm-4 col-lg-3">Estado</dt>
              <dd class="col-sm-8 col-lg-9">
                <span class="badge" :class="badgeClass(selected?.estado)">{{ selected?.estado }}</span>
              </dd>

              <dt class="col-sm-4 col-lg-3">Observaciones</dt>
              <dd class="col-sm-8 col-lg-9">{{ selected?.observaciones || '—' }}</dd>

              <dt class="col-sm-4 col-lg-3">Creado</dt>
              <dd class="col-sm-8 col-lg-9">{{ formatDatePretty(selected?.created_at) }}</dd>
            </dl>
          </div>
          <div class="modal-footer border-0 d-flex w-100">
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-outline-primary" @click="modifyFromView">
                <i class="bi bi-pencil me-1"></i> Editar
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
import Modal from 'bootstrap/js/dist/modal'
import 'animate.css'

/* ==== Importa utilidades comunes ==== */
import {
  apiBase, authHeaders, toast, getId,
  makeDebouncer, toDateInputValue, isRequired
} from '@/assets/js/crudUtils.js'

const API_BASE  = apiBase('citas')
const API_USERS = apiBase('users')

const user = safeParse(localStorage.getItem('user') || '{}')
const userRole = String(user?.rol || '').toLowerCase()

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
const hasErrors   = computed(() => Object.keys(errors).length > 0)
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
  estado: 'Pendiente',
  observaciones: ''
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

/* ===== Búsqueda (debounced) ===== */
const debouncer = makeDebouncer(120)
function onInstantSearch () { debouncer(() => {}) }
function clearSearch () { searchQuery.value = '' }

/* ===== Helpers UI ===== */
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
function safeParse (str) { try { return JSON.parse(str) } catch { return {} } }

/* ===== Fecha mínima = hoy ===== */
const today = toDateInputValue(new Date())

/* ===== Modal: Form ===== */
async function openCreate () {
  isEditing.value = false
  resetForm()
  clearErrors()
  if (!alumnos.value.length || !docentes.value.length) await loadUsuarios()
  if (userRole === 'estudiante') {
    form.alumno_id = String(user?.id ?? user?._id ?? '')
  }
  formModal.show()
}

async function openEdit (cita) {
  isEditing.value = true
  clearErrors()
  if (!alumnos.value.length || !docentes.value.length) await loadUsuarios()

  const c = decorateNames(cita)
  form._id        = getId(c)
  form.alumno_id  = String(c.alumno_id ?? '')
  form.docente_id = String(c.docente_id ?? '')

  const d = new Date(c.fecha_cita)
  if (!isNaN(d)) {
    form.fecha = d.toISOString().slice(0, 10)
    form.hora  = d.toTimeString().slice(0, 5)
  } else {
    form.fecha = ''
    form.hora  = ''
  }
  form.modalidad     = c.modalidad || ''
  form.motivo        = c.motivo || ''
  form.estado        = c.estado || 'Pendiente'
  form.observaciones = c.observaciones || ''
  formModal.show()
}

function hideModal () { formModal.hide() }
function resetForm () {
  Object.assign(form, {
    _id: null,
    alumno_id: '',
    docente_id: '',
    fecha: '',
    hora: '',
    modalidad: '',
    motivo: '',
    estado: 'Pendiente',
    observaciones: ''
  })
}
function clearErrors () { Object.keys(errors).forEach(k => delete errors[k]) }

/* ===== Construir ISO 8601 con offset local ===== */
function localIsoWithOffset (dateStr, timeStr) {
  // Espera "YYYY-MM-DD" y "HH:mm"
  if (!dateStr || !timeStr) return null
  const [h, m] = timeStr.split(':').map(Number)
  const [Y, M, D] = dateStr.split('-').map(Number)
  const d = new Date(Y, (M - 1), D, h, m, 0, 0)
  if (isNaN(d)) return null
  const pad = (n) => String(Math.abs(n)).padStart(2, '0')
  const tzMin = -d.getTimezoneOffset() // minutos respecto a UTC
  const sign = tzMin >= 0 ? '+' : '-'
  const tzH = pad(Math.trunc(Math.abs(tzMin) / 60))
  const tzM = pad(Math.abs(tzMin) % 60)
  const yyyy = d.getFullYear()
  const mm = pad(d.getMonth() + 1)
  const dd = pad(d.getDate())
  const HH = pad(d.getHours())
  const MM = pad(d.getMinutes())
  const SS = '00'
  // YYYY-MM-DDTHH:mm:ss±HH:MM
  return `${yyyy}-${mm}-${dd}T${HH}:${MM}:${SS}${sign}${tzH}:${tzM}`
}

/* ===== Validación previa a enviar (usa crudUtils) ===== */
function validateFormFront () {
  clearErrors()
  const errs = {}
  if (!isRequired(form.alumno_id)) errs.alumno_id = ['El alumno es obligatorio.']
  if (!isRequired(form.docente_id)) errs.docente_id = ['El docente es obligatorio.']
  if (!isRequired(form.fecha)) errs.fecha_cita = ['La fecha de la cita es obligatoria.']
  if (!isRequired(form.hora)) errs.fecha_cita = ['La hora de la cita es obligatoria.']
  if (!isRequired(form.modalidad)) errs.modalidad = ['La modalidad es obligatoria.']

  // fecha mínima: hoy
  if (form.fecha && form.fecha < today) {
    errs.fecha_cita = ['La fecha no puede ser anterior a hoy.']
  }

  if (Object.keys(errs).length) {
    Object.assign(errors, errs)
    return false
  }
  return true
}

/* ===== Envío ===== */
async function onSubmit () {
  if (!validateFormFront()) {
    toast('Revisa los campos obligatorios.', 'error')
    return
  }

  const fechaISO = localIsoWithOffset(form.fecha, form.hora)
  if (!fechaISO) {
    errors.fecha_cita = ['La fecha debe tener el formato ISO 8601 (ej. 2025-05-18T14:30:00+00:00).']
    toast('Fecha/hora inválidas.', 'error')
    return
  }

  saving.value = true
  clearErrors()
  try {
    const payload = {
      alumno_id: form.alumno_id,
      docente_id: form.docente_id,
      fecha_cita: fechaISO,
      modalidad: form.modalidad,
      motivo: form.motivo || null,
      estado: form.estado || 'Pendiente',
      observaciones: form.observaciones || null
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
import Swal from 'sweetalert2'
import 'sweetalert2/dist/sweetalert2.min.css'

</script>


<style scoped src="@/assets/css/Citas.css"></style>
