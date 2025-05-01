<template>
    <nav class="sidebar bg-beige p-3">
      <ul class="nav flex-column">
        <li class="nav-item" v-for="item in navItems" :key="item.text">
          <router-link :to="item.to" class="nav-link d-flex align-items-center">
            <img :src="item.icon" alt class="me-2 nav-icon" />
            <span>{{ item.text }}</span>
          </router-link>
        </li>
      </ul>
    </nav>
  </template>
  
  <script setup>
  defineOptions({ name: 'Sidebar' })
  
  const navItems = [
    { to: '/home',            text: 'Inicio',        icon: 'https://img.icons8.com/ios-filled/24/000000/home.png' },
    { to: '/home/empleados',  text: 'Empleados',     icon: 'https://img.icons8.com/ios-filled/24/000000/user-group-man-man.png' },
    { to: '/home/salarios',   text: 'Salarios',      icon: 'https://img.icons8.com/ios-filled/24/000000/money-with-wings.png' },
    { to: '/home/reportes',   text: 'Reportes',      icon: 'https://img.icons8.com/ios-filled/24/000000/combo-chart--v1.png' },
    { to: '/home/ajustes',    text: 'Ajustes',       icon: 'https://img.icons8.com/ios-filled/24/000000/settings.png' }
  ]
  </script>
  
  <style scoped>
  .sidebar {
    min-height: 100vh;
    background-color: #f5f0e1;
  }
  .nav-link {
    color: #333;
    padding: 0.75rem 1rem;
    border-radius: 0.375rem;
    transition: background-color 0.3s ease, color 0.3s ease;
  }
  .nav-link:hover,
  .nav-link.router-link-active {
    background-color: #404e5e;
    color: #fff;
  }
  .nav-icon {
    width: 1.25rem;
    height: 1.25rem;
  }
  </style>