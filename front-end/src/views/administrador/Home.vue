<!-- src/views/administrador/Dashboard.vue -->
<template>
  <main class="admin-dashboard container-fluid">
    <!-- ====== Encabezado ====== -->
    <header class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
      <div class="d-flex align-items-baseline gap-3">
        <h1 class="title my-0">Panel de Administrador</h1>
      </div>
      <small v-if="overview.hoy" class="badge bg-light text-muted border fade-in-quick">
        Hoy: {{ overview.hoy }}
      </small>
    </header>

    <!-- ====== Tarjetas KPI ====== -->
    <section class="row g-3 kpi-row">
      <!-- Usuarios totales -->
      <div class="col-12 col-sm-6 col-xl-2-4">
        <div class="kpi-card shadow-sm lift">
          <div class="kpi-icon bg-primary-subtle text-primary">
            <i class="bi bi-people-fill"></i>
          </div>
          <div class="kpi-body">
            <h3 class="kpi-value">{{ overview.usuariosTotales }}</h3>
            <p class="kpi-label">Usuarios totales</p>
          </div>
        </div>
      </div>

      <!-- Estudiantes -->
      <div class="col-12 col-sm-6 col-xl-2-4">
        <div class="kpi-card shadow-sm lift">
          <div class="kpi-icon bg-info-subtle text-info">
            <i class="bi bi-mortarboard"></i>
          </div>
          <div class="kpi-body">
            <h3 class="kpi-value">{{ overview.estudiantes }}</h3>
            <p class="kpi-label">Estudiantes</p>
          </div>
        </div>
      </div>

      <!-- Docentes -->
      <div class="col-12 col-sm-6 col-xl-2-4">
        <div class="kpi-card shadow-sm lift">
          <div class="kpi-icon bg-success-subtle text-success">
            <i class="bi bi-person-workspace"></i>
          </div>
          <div class="kpi-body">
            <h3 class="kpi-value">{{ overview.docentes }}</h3>
            <p class="kpi-label">Docentes</p>
          </div>
        </div>
      </div>

      <!-- Técnicas -->
      <div class="col-12 col-sm-6 col-xl-2-4">
        <div class="kpi-card shadow-sm lift">
          <div class="kpi-icon bg-violet-subtle text-violet">
            <i class="bi bi-flower2"></i>
          </div>
          <div class="kpi-body">
            <h3 class="kpi-value">{{ overview.totalTecnicas }}</h3>
            <p class="kpi-label">Técnicas</p>
          </div>
        </div>
      </div>

      <!-- Bitácoras de hoy -->
      <div class="col-12 col-sm-6 col-xl-2-4">
        <div class="kpi-card shadow-sm lift">
          <div class="kpi-icon bg-warning-subtle text-warning">
            <i class="bi bi-journal-check"></i>
          </div>
          <div class="kpi-body">
            <h3 class="kpi-value">{{ overview.bitacorasHoy }}</h3>
            <p class="kpi-label">Bitácoras de hoy</p>
          </div>
        </div>
      </div>

      <!-- Acceso directo a Reportes -->
      <div class="col-12">
        <div class="kpi-card shadow-sm reports-card position-relative lift">
          <div class="d-flex align-items-center w-100 gap-3">
            <div class="kpi-icon bg-violet-subtle text-violet">
              <i class="bi bi-file-earmark-bar-graph"></i>
            </div>
            <div class="kpi-body">
              <h3 class="kpi-value d-flex align-items-center gap-2">
                Reportes
                <small class="badge rounded-pill bg-violet-soft text-violet">PDF • Excel</small>
              </h3>
              <p class="kpi-label mb-0">Exporta métricas y listados filtrables</p>
            </div>
            <router-link
              class="btn btn-violet ms-auto hover-raise"
              to="/app/admin/reportes"
              title="Ir a reportes"
              aria-label="Ir a reportes"
            >
              <i class="bi bi-arrow-right-circle me-1"></i>
              Ver reportes
            </router-link>

            <!-- Hace que TODO el botón sea clickeable y el hover cubra bien -->
            <router-link to="/app/admin/reportes" class="stretched-link" />
          </div>
        </div>
      </div>
    </section>

    <!-- ====== Gráfica: Bitácoras por mes ====== -->
    <section class="row g-3 mt-1">
      <div class="col-12">
        <div class="chart-card shadow-sm fade-in-quick">
          <div class="chart-head">
            <h5 class="mb-0">Bitácoras emocionales por mes</h5>
            <small class="text-muted">{{ currentYear }}</small>
          </div>
          <div class="chart-body">
            <canvas ref="logsByMonthCanvas" height="120" aria-label="Gráfica de barras de bitácoras por mes" role="img"></canvas>
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

// Base API (Vue CLI)
const API = (process.env.VUE_APP_API_URL || '').replace(/\/+$/, '')

const overview = ref({
  usuariosTotales: 0,
  estudiantes: 0,
  docentes: 0,
  totalTecnicas: 0,
  bitacorasHoy: 0,
  bitacorasTotales: 0,
  hoy: ''
})

const currentYear = new Date().getFullYear()
const monthsMx = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic']

const logsByMonth = ref(Array(12).fill(0))
const logsByMonthCanvas = ref(null)
let logsChart = null

function authHeaders () {
  const token = localStorage.getItem('token')
  const type  = localStorage.getItem('token_type') || 'Bearer'
  return token ? { Authorization: `${type} ${token}` } : {}
}

async function fetchOverview() {
  try {
    const url = `${API}/admin/dashboard/overview`
    const { data } = await axios.get(url, { headers: authHeaders() })
    overview.value = {
      usuariosTotales: Number(data?.usuariosTotales ?? 0),
      estudiantes: Number(data?.estudiantes ?? 0),
      docentes: Number(data?.docentes ?? 0),
      totalTecnicas: Number(data?.totalTecnicas ?? 0),
      bitacorasHoy: Number(data?.bitacorasHoy ?? 0),
      bitacorasTotales: Number(data?.bitacorasTotales ?? 0),
      hoy: String(data?.hoy || '')
    }
  } catch (e) {
    overview.value = { usuariosTotales: 0, estudiantes: 0, docentes: 0, totalTecnicas: 0, bitacorasHoy: 0, bitacorasTotales: 0, hoy: '' }
  }
}

async function fetchLogsByMonth() {
  try {
    const url = `${API}/admin/dashboard/bitacoras-por-mes`
    const { data } = await axios.get(url, {
      headers: authHeaders(),
      params: { year: currentYear }
    })
    const arr = Array.isArray(data?.data) ? data.data : []
    logsByMonth.value = monthsMx.map((_, i) => Number(arr[i] ?? 0))
  } catch (e) {
    logsByMonth.value = Array(12).fill(0)
  }
}

function renderLogsChart() {
  if (!logsByMonthCanvas.value) return
  if (logsChart) { logsChart.destroy(); logsChart = null }

  logsChart = new Chart(logsByMonthCanvas.value.getContext('2d'), {
    type: 'bar',
    data: {
      labels: monthsMx,
      datasets: [{
        label: 'Bitácoras',
        data: logsByMonth.value,
        borderWidth: 1,
        backgroundColor: 'rgba(129, 132, 255, 0.25)',
        borderColor: 'rgba(129, 132, 255, 0.9)',
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
          mode: 'index',
          intersect: false,
          callbacks: { label: (ctx) => ` ${ctx.formattedValue} bitácoras` }
        }
      },
      scales: {
        x: { grid: { display: false }, ticks: { color: '#6b7280' } },
        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.04)' }, ticks: { precision: 0, color: '#6b7280' } }
      }
    }
  })
}

onMounted(async () => {
  await Promise.all([fetchOverview(), fetchLogsByMonth()])
  renderLogsChart()
})
</script>

<style>
@import "@/assets/css/Dashboard.css";
</style>
