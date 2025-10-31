<!-- src/views/profesor/SeguimientoCitas.vue -->
<template>
  <main class="seguimiento-wrapper avoid-navbar container-fluid py-3 py-lg-4">
    <!-- ===== Título + Toggle de vista ===== -->
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3 animate__animated animate__fadeInDown">
      <div class="me-auto">
        <h2 class="m-0 d-flex align-items-center gap-2">
          <i class="bi bi-activity"></i>
          Seguimiento de Citas
        </h2>
        <small class="text-muted">Control y monitoreo por estado, con filtros y calendario.</small>
      </div>

      <div class="btn-group shadow-sm rounded-pill overflow-hidden">
        <button
          class="btn btn-outline-primary px-3"
          :class="{ active: viewMode==='kanban' }"
          @click="viewMode='kanban'"
          title="Vista por estados"
        >
          <i class="bi bi-kanban me-1"></i> Estados
        </button>
        <button
          class="btn btn-outline-primary px-3"
          :class="{ active: viewMode==='calendar' }"
          @click="viewMode='calendar'"
          title="Vista calendario"
        >
          <i class="bi bi-calendar3 me-1"></i> Calendario
        </button>
      </div>
    </div>

    <!-- ===== Filtros ===== -->
    <div class="card border-0 shadow-sm mb-3 animate__animated animate__fadeIn">
      <div class="card-body">
        <div class="row g-3 align-items-end">
          <!-- Búsqueda -->
          <div class="col-12 col-lg-5">
            <label class="form-label">Buscar por alumno/maestro/motivo</label>
            <div class="input-group input-group-lg rounded-pill search-group">
              <span class="input-group-text rounded-start-pill"><i class="bi bi-search"></i></span>
              <input
                v-model.trim="filters.q"
                type="text"
                class="form-control"
                placeholder="Ej. Ana, Prof. Pérez, ansiedad, entrevista…"
              />
              <button
                v-if="filters.q"
                class="btn btn-link text-secondary px-3"
                @click="filters.q=''"
                aria-label="Limpiar"
                title="Limpiar"
              >
                <i class="bi bi-x-lg"></i>
              </button>
            </div>
          </div>

          <!-- Estado (exclusivo) -->
          <div class="col-12 col-lg-4">
            <label class="form-label">Estado</label>
            <div class="d-flex flex-wrap gap-2">
              <button
                v-for="e in estados"
                :key="e"
                type="button"
                class="btn btn-sm chip-btn"
                :class="filters.estado===e ? 'btn-primary' : 'btn-outline-primary'"
                @click="onEstadoClick(e)"
              >
                <i :class="['bi', estadoIcon(e), 'me-1']"></i>{{ e }}
              </button>
              <button
                v-if="filters.estado"
                type="button"
                class="btn btn-sm btn-outline-purple"
                @click="filters.estado=''"
              >
                Limpiar
              </button>
            </div>
          </div>

          <!-- Fechas -->
          <div class="col-12 col-lg-3">
            <div class="row g-2">
              <div class="col-6">
                <label class="form-label">Desde</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                  <input v-model="filters.desde" type="date" class="form-control" />
                </div>
              </div>
              <div class="col-6">
                <label class="form-label">Hasta</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-calendar2-week"></i></span>
                  <input v-model="filters.hasta" type="date" class="form-control" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="d-flex gap-2 mt-3">
          <button class="btn btn-outline-purple rounded-pill px-3" @click="resetFilters">
            <i class="bi bi-arrow-counterclockwise me-1"></i> Restablecer
          </button>
          <div class="ms-auto small text-muted">
            Mostrando <strong>{{ filtered.length }}</strong> de {{ allDecorated.length }}
          </div>
        </div>
      </div>
    </div>

    <!-- ===== Empty state ===== -->
    <div v-if="!filtered.length" class="text-center text-muted my-5 animate__animated animate__fadeIn">
      <i class="bi bi-calendar2-week fs-1 d-block mb-2"></i>
      <div class="fw-semibold">No hay citas con los filtros actuales</div>
      <div>Modifica el estado, rango de fechas o la búsqueda.</div>
    </div>

    <!-- ===== VISTA: ESTADOS EN FILAS ===== -->
    <section v-if="filtered.length && viewMode==='kanban'" class="animate__animated animate__fadeIn">
      <transition-group name="fade" tag="div" class="estado-rows">
        <div
          v-for="row in visibleEstados"
          :key="row.key"
          class="estado-row card shadow-sm"
        >
          <div class="card-header bg-white d-flex align-items-center gap-2 border-0">
            <span class="estado-dot" :class="`k-${row.key}`"></span>
            <strong class="me-2">{{ row.title }}</strong>
            <span class="badge bg-light text-dark">{{ byEstado[row.key]?.length || 0 }}</span>
          </div>

          <div class="card-body pt-1">
            <div class="cards-scroller">
              <transition-group name="stagger" tag="div" class="cards-track">
                <div
                  v-for="c in byEstado[row.key]"
                  :key="getId(c)"
                  class="cita-card card border-0 shadow-xs hover-raise"
                >
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                      <div class="small text-muted">
                        <i class="bi bi-calendar-event me-1"></i>
                        {{ formatDatePretty(c.fecha_cita) }}
                      </div>
                      <span class="badge" :class="badgeClass(c.estado)">{{ c.estado }}</span>
                    </div>

                    <div class="small text-muted">
                      <i class="bi bi-person me-1"></i>
                      <strong>Alumno:</strong> {{ c.alumno_nombre || '—' }}
                    </div>
                    <div class="small text-muted mb-2">
                      <i class="bi bi-person-badge me-1"></i>
                      <strong>Docente:</strong> {{ c.docente_nombre || '—' }}
                    </div>

                    <div class="mb-2">
                      <span class="badge bg-info-subtle text-info border">
                        <i class="bi bi-geo-alt me-1"></i>{{ c.modalidad || '—' }}
                      </span>
                    </div>

                    <p class="mb-0 clamp-3"><strong>Motivo:</strong> {{ c.motivo || '—' }}</p>

                    <div class="d-flex flex-wrap gap-2 mt-3">
                      <button class="btn btn-outline-secondary btn-sm" @click="openDetails(c)">
                        <i class="bi bi-eye me-1"></i> Ver
                      </button>

                      <template v-if="canChange">
                        <button
                          v-if="(c.estado||'').toLowerCase()==='pendiente'"
                          class="btn btn-success btn-sm"
                          @click="cambiarEstado(c, 'Aceptada')"
                        >
                          <i class="bi bi-check2-circle me-1"></i> Aceptar
                        </button>
                        <button
                          v-if="(c.estado||'').toLowerCase()==='pendiente'"
                          class="btn btn-outline-danger btn-sm"
                          @click="cambiarEstado(c, 'Rechazada')"
                        >
                          <i class="bi bi-x-circle me-1"></i> Rechazar
                        </button>
                        <button
                          v-if="(c.estado||'').toLowerCase()==='aceptada'"
                          class="btn btn-primary btn-sm"
                          @click="finalizarCita(c)"
                        >
                          <i class="bi bi-clipboard-check me-1"></i> Finalizar
                        </button>
                      </template>
                    </div>
                  </div>
                </div>
              </transition-group>

              <div v-if="!(byEstado[row.key]?.length)" class="text-center text-muted small py-2">
                Sin citas en esta fila
              </div>
            </div>
          </div>
        </div>
      </transition-group>
    </section>

    <!-- ===== VISTA: CALENDARIO ===== -->
    <section v-if="filtered.length && viewMode==='calendar'" class="calendar-wrap card shadow-sm animate__animated animate__fadeIn">
      <div class="card-header bg-white border-0 d-flex align-items-center gap-2">
        <button class="btn btn-sm btn-outline-secondary" @click="prevMonth"><i class="bi bi-chevron-left"></i></button>
        <div class="ms-1 me-1 fw-semibold">{{ monthName }} {{ currentYear }}</div>
        <button class="btn btn-sm btn-outline-secondary" @click="nextMonth"><i class="bi bi-chevron-right"></i></button>

        <div class="ms-auto small text-muted d-flex align-items-center gap-3">
          <span class="legend"><i class="legend-dot k-pendiente"></i> Pendiente</span>
          <span class="legend"><i class="legend-dot k-aceptada"></i> Aceptada</span>
          <span class="legend"><i class="legend-dot k-rechazada"></i> Rechazada</span>
          <span class="legend"><i class="legend-dot k-finalizada"></i> Finalizada</span>
        </div>
      </div>

      <div class="card-body pt-0">
        <div class="calendar-grid">
          <div class="calendar-head">Lun</div>
          <div class="calendar-head">Mar</div>
          <div class="calendar-head">Mié</div>
          <div class="calendar-head">Jue</div>
          <div class="calendar-head">Vie</div>
          <div class="calendar-head">Sáb</div>
          <div class="calendar-head">Dom</div>

          <div
            v-for="cell in calendarCells"
            :key="cell.key"
            class="calendar-cell hover-raise"
            :class="{ 'is-today': cell.isToday, 'is-muted': !cell.inMonth }"
            @click="openDay(cell.key)"
          >
            <div class="cell-top">
              <span class="day-number">{{ cell.day }}</span>
            </div>
            <div class="cell-dots">
              <span
                v-for="e in cell.estadoCounts"
                :key="e.estado"
                class="dot"
                :class="`k-${e.estado}`"
                :title="`${e.estado}: ${e.count}`"
              ></span>
            </div>
            <div v-if="cell.total>0" class="cell-total">{{ cell.total }}</div>
          </div>
        </div>
      </div>

      <!-- Panel lateral: día seleccionado -->
      <transition name="slide-x">
        <aside v-if="selectedDayListOpen" class="day-panel shadow-lg">
          <div class="day-panel-head d-flex align-items-center gap-2">
            <strong><i class="bi bi-calendar-date me-1"></i> {{ selectedDayPretty }}</strong>
            <button class="btn btn-sm btn-outline-secondary ms-auto" @click="selectedDayListOpen=false">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>
          <div class="day-panel-body">
            <div v-for="c in citasBySelectedDay" :key="getId(c)" class="card border-0 shadow-xs mb-2">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-1">
                  <div class="small text-muted"><i class="bi bi-clock me-1"></i> {{ formatTime(c.fecha_cita) }}</div>
                  <span class="badge" :class="badgeClass(c.estado)">{{ c.estado }}</span>
                </div>
                <div class="small text-muted"><i class="bi bi-person me-1"></i><strong>Alumno:</strong> {{ c.alumno_nombre || '—' }}</div>
                <div class="small text-muted mb-2"><i class="bi bi-person-badge me-1"></i><strong>Docente:</strong> {{ c.docente_nombre || '—' }}</div>
                <div class="mb-0 clamp-2"><strong>Motivo:</strong> {{ c.motivo || '—' }}</div>

                <div class="d-flex flex-wrap gap-2 mt-3">
                  <button class="btn btn-outline-secondary btn-sm" @click="openDetails(c)">
                    <i class="bi bi-eye me-1"></i> Ver
                  </button>

                  <template v-if="canChange">
                    <button v-if="(c.estado||'').toLowerCase()==='pendiente'" class="btn btn-success btn-sm" @click="cambiarEstado(c, 'Aceptada')">
                      <i class="bi bi-check2-circle me-1"></i> Aceptar
                    </button>
                    <button v-if="(c.estado||'').toLowerCase()==='pendiente'" class="btn btn-outline-danger btn-sm" @click="cambiarEstado(c, 'Rechazada')">
                      <i class="bi bi-x-circle me-1"></i> Rechazar
                    </button>
                    <button v-if="(c.estado||'').toLowerCase()==='aceptada'" class="btn btn-primary btn-sm" @click="finalizarCita(c)">
                      <i class="bi bi-clipboard-check me-1"></i> Finalizar
                    </button>
                  </template>
                </div>
              </div>
            </div>

            <div v-if="!citasBySelectedDay.length" class="text-center text-muted small">Sin citas</div>
          </div>
        </aside>
      </transition>
    </section>

    <!-- ===== Modal Detalle ===== -->
    <div class="modal fade" ref="detailRef" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg animate__animated animate__fadeInUp">
          <div class="modal-header modal-header-gradient text-white">
            <h5 class="modal-title"><i class="bi bi-card-text me-2"></i> Detalle de cita</h5>
            <button type="button" class="btn-close btn-close-white" @click="hideDetails"></button>
          </div>
          <div class="modal-body">
            <dl class="row gy-3 mb-0">
              <dt class="col-sm-4 col-lg-3">Alumno</dt>
              <dd class="col-sm-8 col-lg-9">{{ selectedSafe.alumno_nombre || '—' }}</dd>

              <dt class="col-sm-4 col-lg-3">Docente</dt>
              <dd class="col-sm-8 col-lg-9">{{ selectedSafe.docente_nombre || '—' }}</dd>

              <dt class="col-sm-4 col-lg-3">Fecha/Hora</dt>
              <dd class="col-sm-8 col-lg-9">{{ formatDatePretty(selectedSafe.fecha_cita) }}</dd>

              <dt class="col-sm-4 col-lg-3">Modalidad</dt>
              <dd class="col-sm-8 col-lg-9">{{ selectedSafe.modalidad || '—' }}</dd>

              <dt class="col-sm-4 col-lg-3">Motivo</dt>
              <dd class="col-sm-8 col-lg-9">{{ selectedSafe.motivo || '—' }}</dd>

              <dt class="col-sm-4 col-lg-3">Estado</dt>
              <dd class="col-sm-8 col-lg-9"><span class="badge" :class="badgeClass(selectedSafe.estado)">{{ selectedSafe.estado || '—' }}</span></dd>

              <dt class="col-sm-4 col-lg-3">Observaciones</dt>
              <dd class="col-sm-8 col-lg-9">{{ selectedSafe.observaciones || '—' }}</dd>
            </dl>
          </div>
          <div class="modal-footer">
            <template v-if="canChange">
              <button v-if="(String(selectedSafe.estado||'').toLowerCase()==='pendiente')" class="btn btn-success" @click="cambiarEstado(selectedSafe, 'Aceptada')">
                <i class="bi bi-check2-circle me-1"></i> Aceptar
              </button>
              <button v-if="(String(selectedSafe.estado||'').toLowerCase()==='pendiente')" class="btn btn-outline-danger" @click="cambiarEstado(selectedSafe, 'Rechazada')">
                <i class="bi bi-x-circle me-1"></i> Rechazar
              </button>
              <button v-if="(String(selectedSafe.estado||'').toLowerCase()==='aceptada')" class="btn btn-primary" @click="finalizarCita(selectedSafe)">
                <i class="bi bi-clipboard-check me-1"></i> Finalizar
              </button>
            </template>
            <button class="btn btn-secondary ms-auto" @click="hideDetails">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
  </main>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import axios from 'axios'
import Modal from 'bootstrap/js/dist/modal'
import Swal from 'sweetalert2'
import 'sweetalert2/dist/sweetalert2.min.css'
import 'animate.css'

/* Utils */
import { apiBase, authHeaders, toast, getId } from '@/assets/js/crudUtils.js'
const API_BASE = apiBase('citas')

/* Usuario / rol (profesor) */
const user = safeParse(localStorage.getItem('user') || '{}')
const myId = String(user?._id || user?.id || user?.uuid || '')
const rol = String(user?.rol || '').toLowerCase()
const isProfesor = computed(() => rol === 'profesor')
const isAdmin    = computed(() => rol === 'admin')
const canChange  = computed(() => isProfesor.value || isAdmin.value)

/* Datos */
const all = ref([])

/* Solo mis citas (si soy profesor). Admin vería todas si reusa el componente */
const mine = computed(() => {
  if (!myId || !isProfesor.value) return all.value
  return all.value.filter(c => {
    const cid =
      String(c.docente_id ?? c.docente?._id ?? c.docente?.id ?? c.docente?.uuid ?? '')
    return cid === myId
  })
})

const allDecorated = computed(() => mine.value.map(decorate))

/* Filtros */
const estados = ['Pendiente','Aceptada','Rechazada','Finalizada']
const filters = ref({ q: '', estado: '', desde: '', hasta: '' })
function resetFilters () { filters.value = { q: '', estado: '', desde: '', hasta: '' } }

/* Estado exclusivo visualmente */
function onEstadoClick(e) { filters.value.estado = (filters.value.estado === e) ? '' : e }

/* Vista */
const viewMode = ref('kanban') // 'kanban' | 'calendar'

/* Modal Detalle */
const detailRef = ref(null)
let detailModal = null
const selected = ref(null)
const selectedSafe = computed(() => selected.value || {})

/* ====== Init ====== */
onMounted(async () => {
  await fetchCitas()
  detailModal = new Modal(detailRef.value)
})

async function fetchCitas () {
  try {
    // (Opcional) Si tu API acepta filtro por docente_id, podrías enviar params: { docente_id: myId }
    const { data } = await axios.get(API_BASE, { headers: authHeaders(), params: { per_page: 300 } })
    all.value = data?.registros || data?.data || []
  } catch (e) {
    console.error(e)
    toast('No fue posible cargar las citas.', 'error')
  }
}

/* ====== Decorado / nombres ====== */
function decorate (c) {
  const alumnoNombreEmb =
    c.alumno_nombre ||
    c.alumno?.nombre_completo ||
    c.alumno?.name ||
    [c.alumno?.nombre, c.alumno?.apellido, c.alumno?.apellidos].filter(Boolean).join(' ').trim()

  const docenteNombreEmb =
    c.docente_nombre ||
    c.docente?.nombre_completo ||
    c.docente?.name ||
    [c.docente?.nombre, c.docente?.apellido, c.docente?.apellidos].filter(Boolean).join(' ').trim()

  return { ...c, alumno_nombre: alumnoNombreEmb || null, docente_nombre: docenteNombreEmb || null }
}

/* ====== Filtro ====== */
const filtered = computed(() => {
  const q = (filters.value.q || '').toLowerCase()
  const e = (filters.value.estado || '').toLowerCase()
  const d1 = filters.value.desde ? localDateFromYmd(filters.value.desde) : null
  const d2 = filters.value.hasta ? localDateFromYmd(filters.value.hasta, 23, 59, 59, 999) : null

  return allDecorated.value.filter(c => {
    const txt = [c.alumno_nombre, c.docente_nombre, c.motivo, c.modalidad, c.estado]
      .map(v => (v || '').toString().toLowerCase()).join(' ')
    if (q && !txt.includes(q)) return false
    if (e && (String(c.estado || '').toLowerCase() !== e)) return false

    if (d1 || d2) {
      const dc = new Date(c.fecha_cita || c.created_at || 0)
      if (isNaN(dc)) return false
      if (d1 && dc < d1) return false
      if (d2 && dc > d2) return false
    }
    return true
  })
})

/* ====== Fila por estado ====== */
const estadoRows = [
  { key: 'pendiente',  title: 'Pendiente' },
  { key: 'aceptada',   title: 'Aceptada' },
  { key: 'rechazada',  title: 'Rechazada' },
  { key: 'finalizada', title: 'Finalizada' }
]
const byEstado = computed(() => {
  const groups = { pendiente:[], aceptada:[], rechazada:[], finalizada:[] }
  for (const c of filtered.value) {
    const k = String(c.estado || '').toLowerCase()
    if (groups[k]) groups[k].push(c)
  }
  return groups
})
const visibleEstados = computed(() => {
  const sel = (filters.value.estado || '').toLowerCase()
  return sel ? estadoRows.filter(r => r.key === sel) : estadoRows
})

/* ====== Calendario (sin desfases) ====== */
const today = new Date()
const currentMonth = ref(today.getMonth()) // 0..11
const currentYear  = ref(today.getFullYear())

const monthName = computed(() => {
  return new Date(currentYear.value, currentMonth.value, 1)
    .toLocaleDateString('es-MX', { month: 'long' })
    .replace(/^\w/, c => c.toUpperCase())
})
function prevMonth () { if (currentMonth.value===0){ currentMonth.value=11; currentYear.value-- } else currentMonth.value-- }
function nextMonth () { if (currentMonth.value===11){ currentMonth.value=0; currentYear.value++ } else currentMonth.value++ }

function monthStartDate (y, m) { return new Date(y, m, 1) }
function monthEndDate (y, m) { return new Date(y, m + 1, 0) }

const calendarCells = computed(() => {
  const y = currentYear.value
  const m = currentMonth.value
  const start = monthStartDate(y, m)
  const end = monthEndDate(y, m)

  const startDow = (start.getDay() + 6) % 7  // lunes
  const daysInMonth = end.getDate()

  const cells = []
  for (let i=0;i<startDow;i++) {
    const d = new Date(y, m, - (startDow - 1 - i))
    cells.push(makeCellLocal(d, false))
  }
  for (let d=1; d<=daysInMonth; d++) {
    cells.push(makeCellLocal(new Date(y, m, d), true))
  }
  while (cells.length % 7 !== 0) {
    const last = cells[cells.length - 1].date
    const next = new Date(last.getFullYear(), last.getMonth(), last.getDate() + 1)
    cells.push(makeCellLocal(next, false))
  }
  return cells
})

function makeCellLocal (dateObj, inMonth) {
  const key = ymdKeyLocal(dateObj)
  const day = dateObj.getDate()
  const isToday = sameLocalDate(dateObj, new Date())
  const citasDay = filtered.value.filter(c => {
    const cd = new Date(c.fecha_cita)
    return sameLocalDate(cd, dateObj)
  })
  const totalsByEstado = countByEstado(citasDay)
  return {
    key,
    date: dateObj,
    inMonth,
    isToday,
    day,
    total: citasDay.length,
    estadoCounts: Object.entries(totalsByEstado).map(([estado, count]) => ({ estado, count }))
  }
}

/* ====== Helpers ====== */
function sameLocalDate (a,b) {
  return a.getFullYear()===b.getFullYear() && a.getMonth()===b.getMonth() && a.getDate()===b.getDate()
}
function ymdKeyLocal (d) {
  const pad = n => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`
}
function localDateFromYmd (ymd, HH=0, MM=0, SS=0, MS=0) {
  const [Y,M,D] = ymd.split('-').map(Number)
  return new Date(Y, (M-1), D, HH, MM, SS, MS)
}
function countByEstado (arr) {
  const out = { pendiente:0, aceptada:0, rechazada:0, finalizada:0 }
  for (const c of arr) {
    const k = String(c.estado || '').toLowerCase()
    if (out[k] !== undefined) out[k]++
  }
  return out
}
function idOf (obj) {
  return String(obj?.id ?? obj?._id ?? obj?.uuid ?? '')
}
function alumnoIdOf (c) {
  return String(c.alumno_id ?? c.alumno?._id ?? c.alumno?.id ?? '')
}
function docenteIdOf (c) {
  return String(c.docente_id ?? c.docente?._id ?? c.docente?.id ?? '')
}
function buildPutPayload (cita, override = {}) {
  return {
    alumno_id: alumnoIdOf(cita),
    docente_id: docenteIdOf(cita),
    fecha_cita: cita.fecha_cita,
    modalidad: cita.modalidad || 'Presencial',
    motivo: cita.motivo || null,
    estado: override.estado ?? (cita.estado || 'Pendiente'),
    observaciones: override.observaciones ?? (cita.observaciones ?? null)
  }
}

/* Día seleccionado en calendario */
const selectedDay = ref('')
const selectedDayListOpen = ref(false)
const selectedDayPretty = computed(() => {
  if (!selectedDay.value) return ''
  const d = localDateFromYmd(selectedDay.value)
  return d.toLocaleDateString('es-MX', { dateStyle:'full' })
})
const citasBySelectedDay = computed(() => {
  if (!selectedDay.value) return []
  const d = localDateFromYmd(selectedDay.value)
  return filtered.value
    .filter(c => sameLocalDate(new Date(c.fecha_cita), d))
    .sort((a,b) => (new Date(a.fecha_cita)) - (new Date(b.fecha_cita)))
})
function openDay (keyYmd) {
  selectedDay.value = keyYmd
  selectedDayListOpen.value = true
}

/* ====== Acciones ====== */
function openDetails (c) { selected.value = c; detailModal.show() }
function hideDetails () { detailModal.hide(); selected.value = null }

function estadoIcon (e) {
  const k = (e || '').toLowerCase()
  if (k==='aceptada') return 'bi-check2-circle'
  if (k==='rechazada') return 'bi-x-circle'
  if (k==='finalizada') return 'bi-clipboard-check'
  return 'bi-hourglass-split'
}
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
  return isNaN(d) ? v : d.toLocaleString('es-MX', { dateStyle:'medium', timeStyle:'short' })
}
function formatTime (v) {
  if (!v) return '—'
  const d = new Date(v)
  return isNaN(d) ? '—' : d.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' })
}
function safeParse (s) { try { return JSON.parse(s) } catch { return {} } }

/* Cambiar estado (PUT completo + fallback PATCH) */
async function cambiarEstado (cita, nuevoEstado) {
  const pretty = nuevoEstado
  const res = await Swal.fire({
    title: `${pretty} cita`,
    text: `¿Confirmas cambiar el estado a "${pretty}"?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: `Sí, ${pretty.toLowerCase()}`,
    cancelButtonText: 'Cancelar',
    showCloseButton: true
  })
  if (!res.isConfirmed) return

  const id = getId(cita) || idOf(cita)
  const payload = buildPutPayload(cita, { estado: pretty })

  try {
    await axios.put(`${API_BASE}/${id}`, payload, { headers: authHeaders() })
    toast(`Cita ${pretty.toLowerCase()}.`)
  } catch (e) {
    const code = e?.response?.status
    if (code===404 || code===405 || code===422) {
      try {
        await axios.patch(`${API_BASE}/${id}/estado`, { estado: pretty }, { headers: authHeaders() })
        toast(`Cita ${pretty.toLowerCase()} (vía PATCH).`)
      } catch (e2) {
        console.error(e2)
        toast('No fue posible actualizar la cita.', 'error')
        return
      }
    } else {
      console.error(e)
      toast('No fue posible actualizar la cita.', 'error')
      return
    }
  }

  await fetchCitas()
  await nextTick()
  if (selected.value && getId(selected.value) === id) {
    selected.value = allDecorated.value.find(x => getId(x) === id) || null
  }
}

/* Finalizar (con observaciones opcionales) */
async function finalizarCita (cita) {
  const { value: obs, isConfirmed } = await Swal.fire({
    title: 'Finalizar cita',
    html: `
      <div class="text-start">
        <p class="mb-2">Puedes dejar observaciones de cierre (opcional):</p>
        <textarea id="obs" class="form-control" rows="3" maxlength="2000"
          placeholder="Notas de la sesión, acuerdos, seguimiento..."></textarea>
      </div>`,
    focusConfirm: false,
    showCancelButton: true,
    confirmButtonText: 'Finalizar',
    cancelButtonText: 'Cancelar',
    showCloseButton: true,
    preConfirm: () => document.getElementById('obs')?.value || ''
  })
  if (!isConfirmed) return

  const id = getId(cita) || idOf(cita)
  const payload = buildPutPayload(cita, { estado: 'Finalizada', observaciones: obs || null })

  try {
    await axios.put(`${API_BASE}/${id}`, payload, { headers: authHeaders() })
    toast('Cita finalizada.')
  } catch (e) {
    const code = e?.response?.status
    if (code===404 || code===405 || code===422) {
      try {
        await axios.patch(`${API_BASE}/${id}/estado`, { estado: 'Finalizada', observaciones: obs || null }, { headers: authHeaders() })
        toast('Cita finalizada (vía PATCH).')
      } catch (e2) {
        console.error(e2)
        toast('No fue posible finalizar la cita.', 'error')
        return
      }
    } else {
      console.error(e)
      toast('No fue posible finalizar la cita.', 'error')
      return
    }
  }

  await fetchCitas()
  await nextTick()
  if (selected.value && getId(selected.value) === id) {
    selected.value = allDecorated.value.find(x => getId(x) === id) || null
  }
}
</script>

<style scoped src="@/assets/css/Seguimiento.css"></style>
