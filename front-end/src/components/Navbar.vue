<template>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success custom-navbar">
      <div class="container-fluid">
        <a class="navbar-brand" href="#">Mindfulness</a>
        <button
          class="navbar-toggler"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#navbarNavDropdown"
          aria-controls="navbarNavDropdown"
          aria-expanded="false"
          aria-label="Toggle navigation"
        >
          <span class="navbar-toggler-icon"></span>
        </button>
  
        <div class="collapse navbar-collapse" id="navbarNavDropdown">
          <ul class="navbar-nav ms-auto">
            <li class="nav-item" v-for="item in navItems" :key="item.text">
              <router-link
                :to="item.to"
                class="nav-link"
                :class="{ active: item.to === $route.path }"
              >
                <img
                  v-if="item.icon"
                  :src="item.icon"
                  class="nav-icon"
                  alt=""
                />
                {{ item.text }}
              </router-link>
            </li>
          </ul>
        </div>
      </div>
    </nav>
  </template>
  
  <script setup>
    const navItems = [
      { to: '/',           text: 'Inicio',          icon: 'https://img.icons8.com/ios-filled/24/ffffff/home.png' },
      { to: '/sobre-nosotros',      text: 'Sobre Nosotros',  icon: 'https://img.icons8.com/ios-filled/24/ffffff/info.png' },
      { to: '/contacto',   text: 'Contacto',        icon: 'https://img.icons8.com/ios-filled/24/ffffff/contacts.png' },
      { to: '/login',      text: 'Ingresar',        icon: 'https://img.icons8.com/ios-filled/24/ffffff/login-rounded-right.png' },
    ];
  </script>
  
  <style scoped>
  
    .custom-navbar {
      padding: 0.75rem 1.5rem;
    }
  
    .navbar-brand {
      font-weight: 700;
      font-size: 1.75rem;
      letter-spacing: 1px;
    }
  
    .navbar-nav .nav-item {
      margin-left: 1.25rem;
    }
  
    .navbar-nav .nav-link {
      position: relative;
      padding-bottom: 0.5rem;
      transition: color 0.3s ease;
    }
  
    .navbar-nav .nav-link .nav-icon {
      margin-right: 0.5rem;
      width: 1.25rem;
      height: 1.25rem;
      vertical-align: middle;
    }
  
    .navbar-nav .nav-link:hover,
    .navbar-nav .nav-link.active {
      color: #fff !important;
    }
  
    .navbar-nav .nav-link:hover::after,
    .navbar-nav .nav-link.active::after {
      content: "";
      position: absolute;
      bottom: 0;
      left: 0;
      width: 100%;
      height: 2px;
      background-color: #fff;
      border-radius: 1px;
    }
  
    @media (max-width: 991.98px) {
      .navbar-nav .nav-item {
        margin-left: 0;
        margin-top: 0.5rem;
      }
    }
  
  </style>