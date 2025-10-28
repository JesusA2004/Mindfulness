<template>
  <main class="admin-dashboard container-fluid">
    <!-- ====== Encabezado ====== -->
    <header class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
      <h1 class="title my-0">Panel de Administrador</h1>
      <small class="text-muted">Resumen general</small>
    </header>

    <!-- ====== Tarjetas KPI ====== -->
    <section class="row g-3 kpi-row">
      <!-- Total Usuarios -->
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="kpi-card shadow-sm">
          <div class="kpi-icon bg-primary-subtle text-primary">
            <i class="bi bi-people-fill"></i>
          </div>
          <div class="kpi-body">
            <h3 class="kpi-value">{{ metrics.totalUsuarios }}</h3>
            <p class="kpi-label">Usuarios registrados</p>
          </div>
        </div>
      </div>

      <!-- Técnicas Mindfulness -->
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="kpi-card shadow-sm">
          <div class="kpi-icon bg-info-subtle text-info">
            <i class="bi bi-flower2"></i>
          </div>
          <div class="kpi-body">
            <h3 class="kpi-value">{{ metrics.totalTecnicas }}</h3>
            <p class="kpi-label">Técnicas mindfulness</p>
          </div>
        </div>
      </div>

      <!-- Bitácoras creadas -->
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="kpi-card shadow-sm">
          <div class="kpi-icon bg-success-subtle text-success">
            <i class="bi bi-journal-check"></i>
          </div>
          <div class="kpi-body">
            <h3 class="kpi-value">{{ metrics.totalBitacoras }}</h3>
            <p class="kpi-label">Bitácoras creadas</p>
          </div>
        </div>
      </div>

      <!-- Acceso directo a Reportes -->
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="kpi-card shadow-sm reports-card">
          <div class="kpi-icon bg-violet-subtle text-violet">
            <i class="bi bi-file-earmark-bar-graph"></i>
          </div>
          <div class="kpi-body">
            <h3 class="kpi-value">Reportes</h3>
            <p class="kpi-label">PDF • Excel</p>
          </div>
          <button
            type="button"
            class="btn btn-violet w-100 mt-2"
            @click="goToReports"
            aria-label="Ir a reportes"
            title="Ir a reportes"
          >
            <i class="bi bi-arrow-right-circle me-1"></i>
            Ver reportes
          </button>
        </div>
      </div>
    </section>

    <!-- ====== Gráfica: Bitácoras por mes ====== -->
    <section class="row g-3 mt-1">
      <div class="col-12">
        <div class="chart-card shadow-sm">
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
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import Chart from 'chart.js/auto'

/**
 * === Endpoints asumidos ===
 * GET {API}/admin/dashboard/overview
 *   -> { totalUsuarios: number, totalTecnicas: number, totalBitacoras: number }
 * GET {API}/admin/dashboard/bitacoras-por-mes?year=YYYY
 *   -> { labels: ["Ene","Feb",...], data: [n1,n2,...] }  // 12 elementos
 *
 * Ajusta las rutas si en tu backend usan otras.
 */
const API =
  (import.meta.env?.VITE_APP_API_BASE || process.env?.VUE_APP_API_BASE) ||
  (import.meta.env?.VITE_APP_API_URL || process.env?.VUE_APP_API_URL) ||
  '' // si usas mismo dominio, deja vacío y usa rutas relativas

const metrics = ref({
  totalUsuarios: 0,
  totalTecnicas: 0,
  totalBitacoras: 0
})

const currentYear = new Date().getFullYear()
const monthsMx = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic']

const logsByMonth = ref([...Array(12)].map(() => 0))
const logsByMonthCanvas = ref(null)
let logsChart = null

function buildAbsolute(url) {
  // Si definiste API base, únete con base; si no, asume ruta relativa
  if (!API) return url
  return `${API.replace(/\/+$/, '')}/${url.replace(/^\/+/, '')}`
}

async function fetchOverview() {
  try {
    const { data } = await axios.get(buildAbsolute('/api/admin/dashboard/overview'))
    metrics.value = {
      totalUsuarios: Number(data?.totalUsuarios ?? 0),
      totalTecnicas: Number(data?.totalTecnicas ?? 0),
      totalBitacoras: Number(data?.totalBitacoras ?? 0)
    }
  } catch (e) {
    // Fallback para que no truene en desarrollo
    metrics.value = { totalUsuarios: 0, totalTecnicas: 0, totalBitacoras: 0 }
    // console.warn('overview fallback', e)
  }
}

async function fetchLogsByMonth() {
  try {
    const { data } = await axios.get(
      buildAbsolute(`/api/admin/dashboard/bitacoras-por-mes?year=${currentYear}`)
    )

    const arr = Array.isArray(data?.data) ? data.data : []
    // Normaliza a 12 meses
    const normalized = monthsMx.map((_, i) => Number(arr[i] ?? 0))
    logsByMonth.value = normalized
  } catch (e) {
    // Fallback con todo en cero para no romper la vista
    logsByMonth.value = [...Array(12)].map(() => 0)
    // console.warn('bitacoras-por-mes fallback', e)
  }
}

function renderLogsChart() {
  if (!logsByMonthCanvas.value) return

  // Destruye instancia previa para evitar fugas al recargar datos
  if (logsChart) {
    logsChart.destroy()
    logsChart = null
  }

  logsChart = new Chart(logsByMonthCanvas.value.getContext('2d'), {
    type: 'bar',
    data: {
      labels: monthsMx,
      datasets: [
        {
          label: 'Bitácoras',
          data: logsByMonth.value,
          borderWidth: 1,
          backgroundColor: 'rgba(129, 132, 255, 0.25)',
          borderColor: 'rgba(129, 132, 255, 0.9)',
          borderRadius: 10,
          maxBarThickness: 36
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          mode: 'index',
          intersect: false,
          callbacks: {
            label: (ctx) => ` ${ctx.formattedValue} bitácoras`
          }
        }
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { color: '#6b7280' } // gris
        },
        y: {
          beginAtZero: true,
          grid: { color: 'rgba(0,0,0,.04)' },
          ticks: { precision: 0, color: '#6b7280' }
        }
      }
    }
  })
}

function goToReports() {
  // Cambia si usas router-view; ambos funcionan
  if (window?.location) {
    window.location.href = '/app/admin/reportes'
  }
}

onMounted(async () => {
  await Promise.all([fetchOverview(), fetchLogsByMonth()])
  renderLogsChart()
})
</script>

<style scoped>
/* ======= Paleta ======= */
:root {
  --ink:#1b3b6f;
  --ink-2:#2c4c86;
  --muted:#6b7280;
  --surface:#ffffff;
  --bg:#f5f7fb;

  --violet:#8164ff;
  --violet-2:#a08dff;
  --violet-3:#dcd6ff;

  --primary:#3b82f6;
  --success:#22c55e;
  --info:#06b6d4;
}

.admin-dashboard {
  padding: 1.25rem;
  background: var(--bg);
  min-height: calc(100vh - 70px);
}

.title {
  font-weight: 800;
  color: var(--ink);
  font-size: clamp(1.25rem, 1.1rem + 1.2vw, 1.9rem);
}

/* ======= KPI Cards ======= */
.kpi-row .kpi-card{
  display:flex; align-items:center; gap:0.9rem;
  background: var(--surface);
  border-radius: 18px;
  padding: 1rem 1.1rem;
  border: 1px solid rgba(17, 24, 39, 0.05);
  transition: transform .2s ease, box-shadow .2s ease;
}
.kpi-card:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(31,38,135,.08); }

.kpi-icon{
  width: 46px; height: 46px; min-width: 46px;
  border-radius: 14px; display:grid; place-items:center;
  font-size: 1.25rem;
}
.bg-violet-subtle{ background: var(--violet-3); }
.text-violet{ color: var(--violet); }

.kpi-body{ line-height: 1.1; }
.kpi-value{ margin:0; font-weight: 800; color:#111827; font-size: 1.6rem; }
.kpi-label{ margin:0; color: var(--muted); font-size: .95rem; }

.reports-card { flex-direction: column; align-items: stretch; }
.btn-violet{
  --bs-btn-color:#fff;
  --bs-btn-bg: var(--violet);
  --bs-btn-border-color: var(--violet);
  --bs-btn-hover-bg: var(--violet-2);
  --bs-btn-hover-border-color: var(--violet-2);
}

/* ======= Chart Card ======= */
.chart-card{
  background: var(--surface);
  border: 1px solid rgba(17,24,39,.05);
  border-radius: 18px;
  padding: 1rem;
}
.chart-head{
  display:flex; align-items:baseline; justify-content:space-between;
  gap: 1rem; padding: 0.25rem 0.25rem 0.75rem;
}
.chart-head h5{ font-weight: 700; color: var(--ink); }
.chart-body{
  position: relative;
  height: 360px;
  padding: .5rem .25rem;
}

/* Utilidades Bootstrap-like si hiciera falta */
.bg-primary-subtle{ background: #e8f0ff; }
.bg-info-subtle{ background: #e6fbff; }
.bg-success-subtle{ background: #e7f7ee; }
.text-primary{ color: #2563eb; }
.text-info{ color: #0891b2; }
.text-success{ color: #16a34a; }
</style>

<!-- Asegúrate de tener Bootstrap Icons en tu layout principal:
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
-->
