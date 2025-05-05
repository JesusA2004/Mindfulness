<template>
  <div class="dashboard profesor-dashboard">
    <h2 class="mb-4">📊 Panel de Docente</h2>
    <div class="row g-4">
      <!-- Calendario de Asignaciones -->
      <div class="col-12 col-lg-6">
        <div class="card calendar-card">
          <h5 class="card-header">Calendario de Asignaciones</h5>
          <div class="card-body">
            <div id="assignmentsCalendar"></div>
          </div>
        </div>
      </div>
      <!-- Gráfica de Progreso de Grupos -->
      <div class="col-12 col-lg-6">
        <div class="card chart-card">
          <h5 class="card-header">Progreso de Grupos</h5>
          <div class="card-body">
            <canvas id="progressChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'

onMounted(() => {
  // Inicializar calendario y gráfica con librerías (FullCalendar, Chart.js, etc.)
})
</script>

<style src="@/assets/css/Dashboard.css" scoped></style>