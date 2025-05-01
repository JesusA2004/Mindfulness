<template>
    <div>
      <Navbar />
      <router-view />
    </div>
  </template>
  
  <script setup>
  import Navbar from '@/components/Navbar.vue'
  defineOptions({ name: 'PublicLayout' })
  </script>
  
  <style scoped>
  /* estilos extra para PublicLayout si necesitas */
  </style>