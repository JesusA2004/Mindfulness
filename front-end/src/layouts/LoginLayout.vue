<template>
  <!-- Pasamos el contenido por slot para que viva DENTRO de .app-shell -->
  <Sidebar>
    <router-view />
  </Sidebar>
</template>

<script setup>
import Sidebar from '@/components/Sidebar.vue'
defineOptions({ name: 'LoginLayout' })
</script>

<style scoped>
  
</style>
