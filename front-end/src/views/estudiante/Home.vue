<template>
  <div class="dashboard estudiante-dashboard">
    <h2 class="mb-4">📈 Panel de Estudiante</h2>
    <div class="row g-4">
      <!-- Gráfica de Progreso Personal -->
      <div class="col-12 col-lg-6">
        <div class="card chart-card">
          <h5 class="card-header">Mi Progreso</h5>
          <div class="card-body">
            <canvas id="myProgressChart"></canvas>
          </div>
        </div>
      </div>
      <!-- Tabla de Asignaciones Personales -->
      <div class="col-12 col-lg-6">
        <div class="card table-card">
          <h5 class="card-header">Mis Asignaciones</h5>
          <div class="card-body overflow-auto">
            <table class="table table-striped mb-0">
              <thead>
                <tr><th>Técnica</th><th>Fecha</th><th>Estado</th></tr>
              </thead>
              <tbody>
                <tr v-for="act in actividades" :key="act.id">
                  <td>{{ act.tecnica }}</td>
                  <td>{{ act.fecha }}</td>
                  <td>{{ act.estado }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
const actividades = ref([])

onMounted(() => {
  // Cargar actividades del estudiante
})
</script>

<style src="@/assets/css/Dashboard.css" scoped></style>