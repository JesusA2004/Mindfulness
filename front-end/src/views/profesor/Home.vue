<!-- src/views/profesor/Dashboard.vue -->
<template>
  <main class="admin-dashboard container-fluid profesor-dashboard">
    <!-- ===== Encabezado ===== -->
    <header class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
      <div class="d-flex align-items-baseline gap-3">
        <h1 class="title my-0">Panel de Docente</h1>
        <small class="text-muted fade-in-quick">Seguimiento de asignaciones y grupos</small>
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
            <i class="bi bi-clipboard-check"></i>
          </div>
          <div class="kpi-body">
            <h3 class="kpi-value">{{ totals.asignadas }}</h3>
            <p class="kpi-label">Actividades asignadas</p>
          </div>
          <router-link to="/app/profesor/actividades" class="stretched-link" />
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
          <router-link to="/app/profesor/actividades?tab=grupos" class="stretched-link" />
        </div>
      </div>
      <div class="col-12 col-md-4">
        <div class="kpi-card shadow-sm lift reports-card">
          <div class="d-flex align-items-center w-100 gap-3">
            <div class="kpi-icon bg-violet-subtle text-violet">
              <i class="bi bi-file-earmark-bar-graph"></i>
            </div>
            <div class="kpi-body">
              <h3 class="kpi-value d-flex align-items-center gap-2">
                Reportes
                <small class="badge rounded-pill bg-violet-soft text-violet">PDF • Excel</small>
              </h3>
              <p class="kpi-label mb-0">Descarga listados y métricas</p>
            </div>
            <router-link class="btn btn-violet ms-auto hover-raise" to="/app/profesor/reportes">
              <i class="bi bi-arrow-right-circle me-1"></i> Ver reportes
            </router-link>
            <router-link to="/app/profesor/reportes" class="stretched-link" />
          </div>
        </div>
      </div>
    </section>

    <!-- ===== Calendario y Progreso – DESPUÉS ===== -->
    <section class="row g-4">
      <!-- Calendario (lista) -->
      <div class="col-12 col-lg-6">
        <div class="card shadow-sm calendar-card lift h-100">
          <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="mb-0">Calendario de asignaciones</h5>
            <span class="badge bg-primary-subtle text-primary">{{ assignments.length }}</span>
          </div>
          <div class="card-body">
            <div v-if="assignments.length" class="timeline">
              <div v-for="(a, i) in assignments" :key="i" class="timeline-item">
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
              No hay asignaciones próximas.
            </div>
          </div>
          <div class="card-footer d-flex gap-2 justify-content-end">
            <router-link class="btn btn-outline-secondary btn-sm" to="/app/profesor/actividades">
              Ver todas
            </router-link>
            <router-link class="btn btn-violet btn-sm" to="/app/profesor/actividades?crear=1">
              <i class="bi bi-plus-lg me-1"></i> Nueva actividad
            </router-link>
          </div>
        </div>
      </div>

      <!-- Progreso por grupo -->
      <div class="col-12 col-lg-6">
        <div class="card shadow-sm chart-card lift h-100">
          <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="mb-0">Progreso de grupos</h5>
            <small class="text-muted">% completado</small>
          </div>
          <div class="card-body">
            <canvas ref="progressCanvas" height="120" aria-label="Gráfica de progreso por grupo" role="img"></canvas>
          </div>
          <div class="card-footer d-flex align-items-center justify-content-between">
            <small class="text-muted">
              Se calcula con base en actividades/entregas (si existen). Sin datos, se estima de forma segura.
            </small>
            <router-link class="btn btn-outline-primary btn-sm" to="/app/profesor/encuestas">
              Ver encuestas
            </router-link>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== Gráfica final – ÚLTIMO ===== -->
    <section class="row g-3 mt-3">
      <div class="col-12">
        <div class="card chart-card shadow-sm">
          <div class="chart-head">
            <h5 class="mb-0">Distribución de progreso por grupo</h5>
            <small class="text-muted">Vista general</small>
          </div>
          <div class="card-body">
            <canvas ref="progressCanvas" height="120" />
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
const today = new Date().toISOString().slice(0, 10)

/* ===== Estado ===== */
const assignments = ref([]) // [{titulo, fechaLabel, descripcion?, cohorte?}]
const totals = ref({ asignadas: 0 })
const groupLabels = ref([]) // ['ITI 10 A', 'IA 5 B', ...]
const groupProgress = ref([]) // [75, 52, 88, ...]
const progressCanvas = ref(null)
let progressChart = null

/* ===== Auth headers ===== */
function authHeaders () {
  const token = localStorage.getItem('token')
  const type  = localStorage.getItem('token_type') || 'Bearer'
  return token ? { Authorization: `${type} ${token}` } : {}
}

/* ===== Helpers ===== */
function fmtDateLabel (isoOrStr) {
  if (!isoOrStr || typeof isoOrStr !== 'string') return 'Sin fecha'
  const d = new Date(isoOrStr.length > 10 ? isoOrStr : `${isoOrStr}T00:00:00`)
  if (isNaN(d.getTime())) return isoOrStr
  const opts = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' }
  return d.toLocaleDateString('es-MX', opts)
}

/* ===== Fetch actividades ===== */
async function fetchAssignments () {
  try {
    const url = `${API}/actividades`
    const { data } = await axios.get(url, { headers: authHeaders(), params: { per_page: 100 } })

    const raw = Array.isArray(data?.data) ? data.data
              : Array.isArray(data) ? data
              : []

    const mapped = raw.map(item => {
      const titulo = item.titulo || item.nombre || 'Actividad'
      const fecha  = item.fecha || item.fecha_inicio || item.fechaFin || item.fechaAsignacion || null
      const cohorte = item.cohorte || item.grupo || null
      const descripcion = item.descripcion || item.detalle || ''
      return { titulo, fecha, fechaLabel: fmtDateLabel(fecha), cohorte, descripcion }
    })

    mapped.sort((a, b) => {
      const da = a.fecha ? new Date(a.fecha) : null
      const db = b.fecha ? new Date(b.fecha) : null
      if (!da && !db) return 0
      if (!da) return 1
      if (!db) return -1
      return da - db
    })

    assignments.value = mapped.slice(0, 12)
    totals.value.asignadas = raw.length

    buildGroupProgressFromActivities(raw)
    renderProgressChart()
  } catch (e) {
    assignments.value = []
    totals.value.asignadas = 0
    groupLabels.value = []
    groupProgress.value = []
    renderProgressChart()
  }
}

/* ===== Armar progreso por grupo ===== */
function buildGroupProgressFromActivities (rawActivities) {
  const groups = new Map()

  for (const it of rawActivities) {
    const cohorte = it.cohorte || it.grupo || null
    if (!cohorte) continue

    const totalAlumnos   = Number(it.totalAlumnos ?? it.alumnos ?? 0)
    const totalEntregas  = Number(it.totalEntregas ?? it.entregas ?? 0)
    const porcentaje     = Number(it.porcentaje ?? it.progreso ?? NaN)

    if (!groups.has(cohorte)) {
      groups.set(cohorte, { count: 0, entregas: 0, alumnos: 0, porcentajes: [] })
    }
    const g = groups.get(cohorte)
    g.count++
    if (!isNaN(totalEntregas)) g.entregas += totalEntregas
    if (!isNaN(totalAlumnos))  g.alumnos  += totalAlumnos
    if (!isNaN(porcentaje))    g.porcentajes.push(Math.max(0, Math.min(100, porcentaje)))
  }

  const labels = []
  const data = []

  for (const [cohorte, g] of groups.entries()) {
    let pct = 0
    if (g.porcentajes.length) {
      pct = Math.round(g.porcentajes.reduce((a, b) => a + b, 0) / g.porcentajes.length)
    } else if (g.alumnos > 0) {
      pct = Math.round((g.entregas / g.alumnos) * 100)
    } else {
      pct = Math.max(25, Math.min(90, g.count * 10 + 25))
    }
    labels.push(cohorte)
    data.push(Math.max(0, Math.min(100, pct)))
  }

  groupLabels.value = labels
  groupProgress.value = data
}

/* ===== Gráfica ===== */
function renderProgressChart () {
  if (!progressCanvas.value) return
  if (progressChart) { progressChart.destroy(); progressChart = null }

  const ctx = progressCanvas.value.getContext('2d')
  progressChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: groupLabels.value,
      datasets: [{
        label: 'Progreso (%)',
        data: groupProgress.value,
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
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: (ctx) => ` ${ctx.formattedValue}%` } }
      },
      scales: {
        x: { grid: { display: false }, ticks: { color: '#6b7280' } },
        y: {
          beginAtZero: true, max: 100,
          grid: { color: 'rgba(0,0,0,.04)' },
          ticks: { color: '#6b7280', stepSize: 20 }
        }
      }
    }
  })
}

onMounted(fetchAssignments)
</script>

<style src="@/assets/css/DashboardProfesor.css"></style>
