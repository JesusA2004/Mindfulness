<!-- src/views/estudiante/Dashboard.vue -->
<template>
  <main class="estudiante-dashboard container-fluid">
    <!-- ====== Encabezado ====== -->
    <header class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
      <div>
        <h1 class="title">Dashboard Estudiante</h1>
        <small class="text-muted-sm">Resumen personal y bienestar semanal</small>
      </div>
      <small class="badge bg-light text-muted border fade-in">Hoy: {{ today }}</small>
    </header>

    <!-- ====== KPIs ====== -->
    <section class="row kpi-grid g-3 mb-3">
      <div class="col-12 col-md-4">
        <div class="kpi-card fade-in">
          <div class="kpi-icon bg-success-subtle text-success">
            <i class="bi bi-activity"></i>
          </div>
          <div class="kpi-body">
            <h3 class="kpi-value">{{ kpis.tecnicasRealizadas }}</h3>
            <p class="kpi-label">Técnicas realizadas</p>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-4">
        <div class="kpi-card fade-in">
          <div class="kpi-icon bg-info-subtle text-info">
            <i class="bi bi-calendar2-event"></i>
          </div>
          <div class="kpi-body">
            <h3 class="kpi-value">{{ kpis.citasPendientesMes }}</h3>
            <p class="kpi-label">Citas pendientes este mes</p>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-4">
        <div class="kpi-card fade-in">
          <div class="kpi-icon bg-violet-subtle text-violet">
            <i class="bi bi-gift"></i>
          </div>
          <div class="kpi-body">
            <h3 class="kpi-value">{{ kpis.recompensasObtenidas }}</h3>
            <p class="kpi-label">Recompensas obtenidas</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ====== Gráfica + Asignaciones ====== -->
    <section class="row g-4">
      <!-- Gráfica bienestar semanal -->
      <div class="col-12 col-lg-6">
        <div class="card-elev fade-in h-100">
          <div class="card-header d-flex align-items-baseline justify-content-between">
            <h5 class="mb-0">Bienestar semanal</h5>
            <small class="text-muted-sm">Basado en tus bitácoras</small>
          </div>
          <div class="card-body">
            <div class="chart-holder">
              <canvas ref="wellbeingCanvas" aria-label="Gráfica semanal de bienestar" role="img"></canvas>
            </div>
            <small class="d-block mt-2 text-muted-sm">
              Se grafica el número de entradas registradas por día (0–1 típico). Si tu bitácora
              incluye puntajes/estado emocional, el backend puede devolverlos y la serie se adaptará.
            </small>
          </div>
        </div>
      </div>

      <!-- Tabla de asignaciones personales -->
      <div class="col-12 col-lg-6">
        <div class="card-elev table-card fade-in h-100">
          <div class="card-header">
            <h5 class="mb-0">Mis asignaciones</h5>
          </div>
          <div class="card-body overflow-auto">
            <div v-if="cargandoAsignaciones" class="text-muted">Cargando…</div>
            <table v-else class="table align-middle mb-0">
              <thead>
                <tr>
                  <th class="text-nowrap">Técnica</th>
                  <th class="text-nowrap">Fecha</th>
                  <th class="text-nowrap">Estado</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="a in actividades" :key="a._key">
                  <td class="text-truncate" style="max-width: 220px">{{ a.tecnica }}</td>
                  <td>{{ a.fechaLabel }}</td>
                  <td>
                    <span :class="estadoBadge(a.estado)">{{ a.estado }}</span>
                  </td>
                </tr>
                <tr v-if="!actividades.length">
                  <td colspan="3" class="text-muted text-center py-3">No hay asignaciones por ahora.</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="card-footer bg-transparent border-0 pt-0">
            <small class="text-muted-sm">
              Tip: completa tus actividades para mantener tu racha de bitácora y ganar recompensas.
            </small>
          </div>
        </div>
      </div>
    </section>
  </main>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import Chart from 'chart.js/auto'

/* ===== Config ===== */
const API   = (process.env.VUE_APP_API_URL || '').replace(/\/+$/, '')
const today = ref(new Date().toISOString().slice(0, 10))

/* ===== Estado ===== */
const kpis = ref({
  tecnicasRealizadas: 0,
  citasPendientesMes: 0,
  recompensasObtenidas: 0
})

const actividades = ref([])
const cargandoAsignaciones = ref(true)

const wellbeingCanvas = ref(null)
let wellbeingChart = null

/* ===== Auth ===== */
function authHeaders () {
  try {
    const u = JSON.parse(localStorage.getItem('user') || '{}')
    const token = u?.access_token || u?.token || localStorage.getItem('token')
    const type  = localStorage.getItem('token_type') || 'Bearer'
    return token ? { Authorization: `${type} ${token}`, Accept: 'application/json' } : { Accept: 'application/json' }
  } catch {
    const token = localStorage.getItem('token')
    const type  = localStorage.getItem('token_type') || 'Bearer'
    return token ? { Authorization: `${type} ${token}`, Accept: 'application/json' } : { Accept: 'application/json' }
  }
}

/* ===== Helpers ===== */
function getAlumnoLocal() {
  try {
    const u = JSON.parse(localStorage.getItem('user') || '{}')
    return {
      userId:  u?.id || u?._id || u?.user_id || null,
      personaId: u?.persona_id || u?.persona?._id || null,
      cohorte: (typeof u?.persona?.cohorte === 'string')
        ? u.persona.cohorte
        : Array.isArray(u?.persona?.cohorte) ? (u.persona.cohorte[0] || null) : (u?.cohorte || null)
    }
  } catch { return { userId: null, personaId: null, cohorte: null } }
}

function fmtDateLabel (isoOrStr) {
  if (!isoOrStr || typeof isoOrStr !== 'string') return '—'
  const d = new Date(isoOrStr.length > 10 ? isoOrStr : `${isoOrStr}T00:00:00`)
  if (isNaN(d.getTime())) return isoOrStr
  return d.toLocaleDateString('es-MX', { day: '2-digit', month: 'short' })
}

function monthRangeISO () {
  const now = new Date()
  const start = new Date(now.getFullYear(), now.getMonth(), 1)
  const end   = new Date(now.getFullYear(), now.getMonth() + 1, 0)
  const toISO = d => d.toISOString().slice(0,10)
  return { start: toISO(start), end: toISO(end) }
}

function estadoBadge (estado) {
  const s = (estado || '').toString().toLowerCase()
  if (['completada','completado','hecha','terminada'].includes(s)) return 'badge-soft success px-3 py-1'
  if (['pendiente','asignada','en progreso'].includes(s))          return 'badge-soft warning px-3 py-1'
  return 'badge-soft muted px-3 py-1'
}

/* ===== KPIs ===== */
async function fetchKPIs () {
  const me = getAlumnoLocal()
  const { start, end } = monthRangeISO()

  // 1) Técnicas realizadas -> contamos bitácoras del alumno
  try {
    const { data } = await axios.get(`${API}/bitacoras`, {
      headers: authHeaders(),
      params: { per_page: 1000, alumno_id: me.userId || me.personaId }
    })
    const arr = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : [])
    const mine = arr.filter(b =>
      (b.alumno_id === me.userId) ||
      (b.alumno_id === me.personaId) ||
      (b.user_id === me.userId) ||
      (b.persona_id === me.personaId)
    )
    kpis.value.tecnicasRealizadas = mine.length
  } catch {
    kpis.value.tecnicasRealizadas = 0
  }

  // 2) Citas pendientes del mes
  try {
    const { data } = await axios.get(`${API}/citas`, {
      headers: authHeaders(),
      params: { start, end, per_page: 1000 }
    })
    const arr = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : [])
    const pending = arr.filter(c => {
      const st = (c.estado || c.status || '').toString().toLowerCase()
      const isMine =
        c.alumno_id === me.userId || c.user_id === me.userId ||
        c.persona_id === me.personaId || c.paciente_id === me.personaId
      const inRange = (c.fecha || c.fecha_cita || c.fecha_inicio || '') >= start &&
                      (c.fecha || c.fecha_cita || c.fecha_inicio || '') <= end
      const isPending = ['pendiente','pending','programada','agendada'].includes(st) || !st
      return inRange && (isMine || !me.userId) && isPending
    })
    kpis.value.citasPendientesMes = pending.length
  } catch {
    kpis.value.citasPendientesMes = 0
  }

  // 3) Recompensas obtenidas
  try {
    const { data } = await axios.get(`${API}/recompensas`, {
      headers: authHeaders(),
      params: { per_page: 1000, user_id: me.userId }
    })
    const arr = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : [])
    const mine = arr.filter(r => {
      // distintos posibles esquemas
      if (r.alumno_id === me.userId || r.user_id === me.userId) return true
      if (Array.isArray(r.canjeos)) {
        return r.canjeos.some(cx => (cx.user_id === me.userId) || (cx.alumno_id === me.userId))
      }
      return false
    })
    kpis.value.recompensasObtenidas = mine.length
  } catch {
    kpis.value.recompensasObtenidas = 0
  }
}

/* ===== Gráfica semanal (bitácoras del alumno, últimos 7 días) ===== */
async function fetchWellbeingWeek () {
  const me = getAlumnoLocal()
  const now = new Date()
  const days = [...Array(7)].map((_, i) => {
    const d = new Date(now); d.setDate(now.getDate() - (6 - i))
    return d
  })
  const labels = days.map(d => d.toLocaleDateString('es-MX', { weekday: 'short' }))

  // Traemos bitácoras de ~10 días para cubrir TZ
  const start = new Date(now); start.setDate(now.getDate() - 10)
  const toISO = d => d.toISOString().slice(0,10)

  let series = new Array(7).fill(0)

  try {
    const { data } = await axios.get(`${API}/bitacoras`, {
      headers: authHeaders(),
      params: { per_page: 1000 }
    })
    const arr = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : [])

    // Filtra las del alumno y mapea por día
    const mine = arr.filter(b =>
      (b.alumno_id === me.userId) ||
      (b.alumno_id === me.personaId) ||
      (b.user_id === me.userId) ||
      (b.persona_id === me.personaId)
    )

    mine.forEach(b => {
      const f = (b.fecha || '').toString().slice(0,10)
      if (!f || f < toISO(days[0]) || f > toISO(days[6])) return
      const idx = days.findIndex(d => toISO(d) === f)
      if (idx !== -1) {
        // Si tu backend trae puntajes/estado_emocional, cámbialo aquí (e.g., series[idx] = b.puntaje)
        series[idx] = Math.max(series[idx], 1)
      }
    })

    renderWellbeingChart(labels, series)
  } catch {
    renderWellbeingChart(labels, series)
  }
}

function renderWellbeingChart (labels, data) {
  if (!wellbeingCanvas.value) return
  if (wellbeingChart) { wellbeingChart.destroy(); wellbeingChart = null }
  const ctx = wellbeingCanvas.value.getContext('2d')
  wellbeingChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: 'Entradas / día',
        data,
        borderWidth: 1,
        backgroundColor: 'rgba(129,132,255,0.25)',
        borderColor: 'rgba(129,132,255,0.9)',
        borderRadius: 10,
        maxBarThickness: 36
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false } },
        y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: 'rgba(0,0,0,.06)' } }
      }
    }
  })
}

/* ===== Asignaciones del alumno (por cohorte) ===== */
async function fetchAsignacionesAlumno () {
  cargandoAsignaciones.value = true
  const me = getAlumnoLocal()
  try {
    const { data } = await axios.get(`${API}/actividades`, {
      headers: authHeaders(),
      params: { per_page: 1000 }
    })
    const arr = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : [])

    const cohU = (me.cohorte || '').toString().toUpperCase().trim()
    const mine = arr
      .filter(a => {
        const coh = (a.cohorte || a.grupo || '').toString().toUpperCase().trim()
        return cohU ? coh === cohU : true
      })
      .map((a, i) => {
        const fecha = a.fecha || a.fecha_inicio || a.fechaFin || a.fechaAsignacion || ''
        // Estado simple por fecha (si no viene del backend)
        const estado =
          (a.estado || a.status) ? (a.estado || a.status) :
          (fecha && fecha < today.value) ? 'Completada' : 'Pendiente'
        return {
          _key: a.id || a._id || i,
          tecnica: a.titulo || a.nombre || 'Actividad',
          fechaLabel: fmtDateLabel(fecha),
          estado
        }
      })
      .slice(0, 10)

    actividades.value = mine
  } catch {
    actividades.value = []
  } finally {
    cargandoAsignaciones.value = false
  }
}

/* ===== Init ===== */
onMounted(async () => {
  await Promise.all([
    fetchKPIs(),
    fetchWellbeingWeek(),
    fetchAsignacionesAlumno()
  ])
})
</script>

<!-- Usa tu CSS existente; puedes cambiar la ruta si tu archivo se llama distinto -->
<style src="@/assets/css/DashboardEstudiante.css"></style>
