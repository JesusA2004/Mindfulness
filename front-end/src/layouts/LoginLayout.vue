<template>
  <div class="layout-container">
    <Sidebar />
    <main class="panel-wrapper">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import Sidebar from '@/components/Sidebar.vue'
defineOptions({ name: 'LoginLayout' })
</script>

<style scoped>
/* =============================
   CONTENEDOR GENERAL
   ============================= */
.layout-container {
  display: flex;
  min-height: 100vh;
  background: transparent; /* sin gris ni blanco */
  gap: 5px; /* separación real entre sidebar y panel */
  padding-left: 60px;  /* margen exterior izquierdo */
  padding-right: 5px; /* margen exterior derecho */
  overflow-x: hidden;
}

/* =============================
   PANEL PRINCIPAL
   ============================= */
.panel-wrapper {
  flex-grow: 1;
  margin-top: 0px; /* mantiene el espaciado correcto del navbar */
  padding: 1rem;
  border-radius: 8px; /* bordes suaves */
  min-width: 0; /* evita overflow horizontal */
}

/* =============================
   RESPONSIVE
   ============================= */
@media (max-width: 991.98px) {
  .layout-container {
    flex-direction: column;
    gap: 0;
    padding: 0;
  }
  .panel-wrapper {
    margin-top: 0px;
    border-radius: 0;
  }
}
</style>
