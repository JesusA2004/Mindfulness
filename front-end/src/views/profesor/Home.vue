<!-- src/views/profesor/Dashboard.vue -->
<template>
  <main class="admin-dashboard container-fluid profesor-dashboard">
    <!-- ===== Encabezado ===== -->
    <header class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
      <div class="d-flex align-items-baseline gap-3">
        <h1 class="title my-0">Dashboard Docente</h1>
      </div>
      <small class="badge bg-light text-muted border fade-in-quick">
        Hoy: {{ today }}
      </small>
    </header>

    <!-- ===== Atajos (KPIs) – PRIMERO ===== -->
    <section class="row g-3 kpi-row mb-4">
      <div class="col-12 col-md-4">
        <div class="kpi-card shadow-sm lift">
          <div class="kpi-icon bg-success-subtle text-success">
            <i class="bi bi-mortarboard"></i>
          </div>
          <div class="kpi-body">
            <h3 class="kpi-value">{{ totals.alumnosCargo }}</h3>
            <p class="kpi-label">Alumnos a tu cargo</p>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-4">
        <div class="kpi-card shadow-sm lift">
          <div class="kpi-icon bg-info-subtle text-info">
            <i class="bi bi-people"></i>
          </div>
          <div class="kpi-body">
            <h3 class="kpi-value">{{ groupLabels.length }}</h3>
            <p class="kpi-label">Grupos activos</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== Calendario y Progreso – DESPUÉS ===== -->
    <section class="row g-4">
      <!-- Calendario (citas o fallback a actividades) -->
      <div class="col-12 col-lg-6">
        <div class="card shadow-sm calendar-card lift h-100">
          <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="mb-0">Calendario del mes</h5>
            <span class="badge bg-primary-subtle text-primary">{{ calendarItems.length }}</span>
          </div>
          <div class="card-body">
            <div v-if="calendarItems.length" class="timeline">
              <div v-for="(a, i) in calendarItems" :key="i" class="timeline-item">
                <div class="dot"></div>
                <div class="content">
                  <div class="d-flex align-items-center gap-2 flex-wrap">
                    <strong class="item-title text-truncate">{{ a.titulo }}</strong>
                    <span v-if="a.cohorte" class="badge rounded-pill bg-violet-soft text-violet">
                      {{ a.cohorte }}
                    </span>
                  </div>
                  <div class="small text-muted">
                    <i class="bi bi-calendar-event me-1"></i>{{ a.fechaLabel }}
                  </div>
                  <div v-if="a.descripcion" class="mt-1 small text-secondary">
                    {{ a.descripcion }}
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="text-center text-muted py-4">
              No hay eventos/citas programados para este mes.
            </div>
          </div>
          <div class="card-footer d-flex gap-2 justify-content-end">
            <router-link class="btn btn-outline-secondary btn-sm" to="/app/profesor/actividades">
              Ver actividades
            </router-link>
          </div>
        </div>
      </div>

      <!-- Actividades por grupo -->
      <div class="col-12 col-lg-6">
        <div class="card shadow-sm chart-card lift h-100">
          <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="mb-0">Actividades por grupo</h5>
          </div>
          <div class="card-body">
            <canvas
              ref="progressCanvas"
              height="120"
              aria-label="Gráfica de actividades por grupo"
              role="img"
            ></canvas>
          </div>
          <div class="card-footer d-flex align-items-center justify-content-between">
            <small class="text-muted">
              Se grafica el número de actividades asignadas por cada grupo a tu cargo.
            </small>
            <router-link class="btn btn-outline-primary btn-sm" to="/app/profesor/encuestas">
              Ver encuestas
            </router-link>
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
const API = (process.env.VUE_APP_API_URL || '').replace(/\/+$/, '')
const today = ref(new Date().toISOString().slice(0, 10))

/* ===== Estado ===== */
const calendarItems = ref([])        // citas o fallback actividades del mes actual
const totals = ref({ alumnosCargo: 0 })
const groupLabels = ref([])          // cohortes del profesor (["ITI 3 A", ...])
const actsByGroup = ref([])          // [12, 7, 5, ...]
const progressCanvas = ref(null)
let progressChart = null

/* ===== Auth headers ===== */
function authHeaders () {
  try {
    const u = JSON.parse(localStorage.getItem('user') || '{}')
    const token = u?.access_token || u?.token || localStorage.getItem('token')
    const type  = localStorage.getItem('token_type') || 'Bearer'
    return token
      ? { Authorization: `${type} ${token}`, Accept: 'application/json' }
      : { Accept: 'application/json' }
  } catch {
    const token = localStorage.getItem('token')
    const type  = localStorage.getItem('token_type') || 'Bearer'
    return token
      ? { Authorization: `${type} ${token}`, Accept: 'application/json' }
      : { Accept: 'application/json' }
  }
}

/* ===== Helpers ===== */
function fmtDateLabel (isoOrStr) {
  if (!isoOrStr || typeof isoOrStr !== 'string') return 'Sin fecha'
  const d = new Date(isoOrStr.length > 10 ? isoOrStr : `${isoOrStr}T00:00:00`)
  if (isNaN(d.getTime())) return isoOrStr
  const opts = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' }
  return d.toLocaleDateString('es-MX', opts)
}

function monthRangeISO () {
  const now = new Date()
  const start = new Date(now.getFullYear(), now.getMonth(), 1)
  const end = new Date(now.getFullYear(), now.getMonth() + 1, 0)
  const toISO = d => d.toISOString().slice(0, 10)
  return { start: toISO(start), end: toISO(end) }
}

/* ===== Fetch: profesor overview (hoy, cohortes, alumnosCargo) ===== */
async function fetchProfesorOverview () {
  try {
    const url = `${API}/dashboard/profesor/overview`
    const { data } = await axios.get(url, { headers: authHeaders() })
    if (data?.hoy) today.value = data.hoy
    groupLabels.value = Array.isArray(data?.cohortes) ? data.cohortes : []
    totals.value.alumnosCargo = Number(data?.alumnosCargo ?? 0)
  } catch {
    groupLabels.value = []
    totals.value.alumnosCargo = 0
  }
}

/* ===== Calendario del mes usando endpoint de profesor (con fallback interno del backend) ===== */
async function fetchCalendarioMes () {
  try {
    const { start, end } = monthRangeISO()
    const url = `${API}/dashboard/profesor/calendario`
    const { data } = await axios.get(url, { headers: authHeaders(), params: { start, end } })
    const raw = Array.isArray(data?.items) ? data.items : []
    calendarItems.value = raw.map(a => ({
      ...a,
      fechaLabel: fmtDateLabel(a.fecha ?? a.fechaLabel ?? '')
    }))
  } catch {
    calendarItems.value = []
  }
}

/* ===== Actividades por grupo ===== */
async function fetchActividadesPorGrupo () {
  try {
    const url = `${API}/dashboard/profesor/actividades-por-grupo`
    const { data } = await axios.get(url, { headers: authHeaders() })
    const labels = Array.isArray(data?.labels) ? data.labels : []
    const serie  = Array.isArray(data?.data)   ? data.data   : []

    // Si el backend no devolvió etiquetas (p.ej., no hay modelo Actividad),
    // mostramos las cohortes detectadas en overview con 0.
    if (!labels.length && groupLabels.value.length) {
      actsByGroup.value = groupLabels.value.map(() => 0)
    } else {
      groupLabels.value = labels
      actsByGroup.value = serie.map(n => Number(n) || 0)
    }

    renderChart()
  } catch {
    actsByGroup.value = groupLabels.value.map(() => 0)
    renderChart()
  }
}

/* ===== Chart ===== */
function renderChart () {
  if (!progressCanvas.value) return
  if (progressChart) { progressChart.destroy(); progressChart = null }

  const ctx = progressCanvas.value.getContext('2d')
  progressChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: groupLabels.value,
      datasets: [{
        label: 'Actividades',
        data: actsByGroup.value,
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
        x: { grid: { display: false }, ticks: { color: '#6b7280' } },
        y: {
          beginAtZero: true,
          grid: { color: 'rgba(0,0,0,.04)' },
          ticks: { color: '#6b7280', stepSize: 1 }
        }
      }
    }
  })
}

/* ===== Init ===== */
onMounted(async () => {
  await fetchProfesorOverview()    // hoy, cohortes (Persona), alumnosCargo
  await fetchCalendarioMes()       // calendario (citas -> fallback actividades)
  await fetchActividadesPorGrupo() // gráfica
})
</script>

<style src="@/assets/css/DashboardProfesor.css"></style>
