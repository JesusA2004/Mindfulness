<template>
    <div class="d-flex">
      <Sidebar />
      <main class="flex-grow-1">
        <router-view />
      </main>
    </div>
  </template>
  
  <script setup>
  import Sidebar from '@/components/Sidebar.vue'
  defineOptions({ name: 'DashboardLayout' })
  </script>
  
  <style scoped>
  .d-flex {
    min-height: 100vh;
  }
  main {
    padding: 1rem;
    background-color: #f5f5f5;
  }
  </style>