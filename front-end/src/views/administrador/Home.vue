<template>
    <div class="dashboard admin-dashboard">
      <h2 class="mb-4">🖥️ Panel de Administrador</h2>
      <div class="row g-4">
        <!-- Gráfica Top Técnicas -->
        <div class="col-12 col-lg-6">
          <div class="card chart-card">
            <h5 class="card-header">Top 4 Técnicas (por rango de fechas)</h5>
            <div class="card-body">
              <canvas id="techniquesChart"></canvas>
            </div>
          </div>
        </div>
        <!-- Tabla de Usuarios -->
        <div class="col-12 col-lg-6">
          <div class="card table-card">
            <h5 class="card-header">Usuarios Activos</h5>
            <div class="card-body overflow-auto">
              <table class="table table-hover mb-0">
                <thead>
                  <tr><th>Rol</th><th>Nombre</th><th>Email</th><th>Último Login</th></tr>
                </thead>
                <tbody>
                  <tr v-for="user in users" :key="user.id">
                    <td>{{ user.rol }}</td>
                    <td>{{ user.nombre }}</td>
                    <td>{{ user.email }}</td>
                    <td>{{ user.ultimoLogin }}</td>
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
  const users = ref([])
  
  onMounted(() => {
    // Cargar datos de usuarios para la tabla
  })
  </script>
  
  <style src="@/assets/css/Dashboard.css" scoped></style>