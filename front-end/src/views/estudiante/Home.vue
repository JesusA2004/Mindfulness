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
            <h3 class="kpi-value">{{ kpis.recompensas }}</h3>
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
            <small class="text-muted-sm">
              Basado en tus bitácoras
              <span v-if="predominante" class="ms-1">· Emoción predominante: <strong>{{ predominante.emocion }}</strong></span>
            </small>
          </div>
          <div class="card-body">
            <div class="chart-holder">
              <canvas ref="wellbeingCanvas" aria-label="Gráfica semanal de bienestar" role="img"></canvas>
            </div>
            <small class="d-block mt-2 text-muted-sm">
              Se grafica el número de entradas registradas por día (0–1 típico).
            </small>
          </div>
        </div>
      </div>

      <!-- Tabla de asignaciones personales -->
      <div class="col-12 col-lg-6">
        <div class="card-elev table-card fade-in h-100">
          <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="mb-0">Mis asignaciones</h5>

            <!-- NUEVO: Botón para ir a Actividades -->
            <button
              type="button"
              class="btn btn-primary btn-sm rounded-pill d-flex align-items-center gap-1"
              @click="goToActividades"
            >
              <i class="bi bi-box-arrow-up-right"></i>
              <span>Ir a Actividades</span>
            </button>
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
                <tr v-for="a in actividades" :key="a.id">
                  <td class="text-truncate" style="max-width: 240px">{{ a.tecnica }}</td>
                  <td>{{ fmtDateLabel(a.fecha) }}</td>
                  <td><span :class="estadoBadge(a.estado)">{{ a.estado }}</span></td>
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
import { useRouter } from 'vue-router'

/* ===== Config ===== */
const API   = (process.env.VUE_APP_API_URL || '').replace(/\/+$/, '') + '/dashboard/alumno'
const today = ref(new Date().toISOString().slice(0, 10))

/* Ajusta aquí si tu ruta real es distinta, p. ej. '/alumno/actividades' */
const ACTIVIDADES_PATH = '/app/estudiante/actividades'

/* ===== Estado ===== */
const kpis = ref({
  tecnicasRealizadas: 0,
  citasPendientesMes: 0,
  recompensas: 0
})

const actividades = ref([])
const cargandoAsignaciones = ref(true)

const wellbeingCanvas = ref(null)
let wellbeingChart = null
const predominante = ref(null)

/* emociones por barra (alineadas a labels) */
const emotions = ref([])

/* ===== Router (si existe) ===== */
const router = (() => {
  try { return useRouter() } catch { return null }
})()

/* ===== Auth ===== */
function authHeaders () {
  const token = (() => {
    try {
      const u = JSON.parse(localStorage.getItem('user') || '{}')
      return u?.access_token || u?.token
    } catch { return localStorage.getItem('token') }
  })()
  const type  = localStorage.getItem('token_type') || 'Bearer'
  return token ? { Authorization: `${type} ${token}`, Accept: 'application/json' } : { Accept: 'application/json' }
}

/* ===== Helpers ===== */
function fmtDateLabel (isoOrStr) {
  if (!isoOrStr || typeof isoOrStr !== 'string') return '—'
  const d = new Date(isoOrStr.length > 10 ? isoOrStr : `${isoOrStr}T00:00:00`)
  if (isNaN(d.getTime())) return isoOrStr
  return d.toLocaleDateString('es-MX', { day: '2-digit', month: 'short' })
}

function estadoBadge (estado) {
  const s = (estado || '').toString().toLowerCase()
  if (['completada','completado','hecha','terminada'].includes(s)) return 'badge-soft success px-3 py-1'
  if (['pendiente','asignada','en progreso','programada','agendada'].includes(s)) return 'badge-soft warning px-3 py-1'
  if (['omitido','omitida','cancelada','rechazada'].includes(s)) return 'badge-soft danger px-3 py-1'
  return 'badge-soft muted px-3 py-1'
}

/* NUEVO: Navegar a Actividades */
function goToActividades () {
  // Si hay router, usar push. Si no, fallback a location.href
  if (router && typeof router.push === 'function') {
    router.push(ACTIVIDADES_PATH).catch(() => {
      window.location.href = ACTIVIDADES_PATH
    })
  } else {
    window.location.href = ACTIVIDADES_PATH
  }
}

/* ===== Llamadas ===== */
async function fetchKPIs () {
  try {
    const { data } = await axios.get(`${API}/overview`, { headers: authHeaders() })
    kpis.value.tecnicasRealizadas = +data?.tecnicasRealizadas || 0
    kpis.value.citasPendientesMes = +data?.citasPendientesMes || 0
    kpis.value.recompensas        = +data?.recompensas || 0
    today.value                   = data?.hoy || today.value
  } catch {
    kpis.value = { tecnicasRealizadas:0, citasPendientesMes:0, recompensas:0 }
  }
}

async function fetchWellbeingWeek () {
  try {
    const { data } = await axios.get(`${API}/bienestar`, { headers: authHeaders() })
    predominante.value = data?.predominante || null
    emotions.value = Array.isArray(data?.emotions) ? data.emotions : []
    renderWellbeingChart(
      Array.isArray(data?.labels) ? data.labels : [],
      Array.isArray(data?.data) ? data.data : [],
      emotions.value
    )
  } catch {
    emotions.value = []
    renderWellbeingChart([], [], [])
  }
}

function renderWellbeingChart (labels, series, emos) {
  if (!wellbeingCanvas.value) return
  if (wellbeingChart) { wellbeingChart.destroy(); wellbeingChart = null }
  const ctx = wellbeingCanvas.value.getContext('2d')
  const emosLocal = Array.isArray(emos) ? emos : []

  wellbeingChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: 'Entradas / día',
        data: series,
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
        tooltip: {
          callbacks: {
            afterBody: (items) => {
              if (!items?.length) return []
              const idx = items[0].dataIndex
              const emo = (emosLocal[idx] || '').toString().trim()
              return emo ? [`Emoción: ${emo}`] : []
            }
          }
        }
      },
      scales: {
        x: { grid: { display: false } },
        y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: 'rgba(0,0,0,.06)' } }
      }
    }
  })
}

async function fetchAsignacionesAlumno () {
  cargandoAsignaciones.value = true
  try {
    const { data } = await axios.get(`${API}/asignaciones`, { headers: authHeaders() })
    const arr = Array.isArray(data?.items) ? data.items : []
    actividades.value = arr
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

<style src="@/assets/css/DashboardEstudiante.css"></style>
