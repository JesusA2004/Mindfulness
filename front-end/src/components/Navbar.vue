<template> 
  <nav class="navbar navbar-expand-lg navbar-dark custom-navbar fixed-top">
    <div class="container-fluid">
      <a class="navbar-brand" href="#">
        <img src="/img/logoDark.png" alt="Mindora Logo" height="40" />
      </a>

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
              <img v-if="item.icon" :src="item.icon" class="nav-icon" alt="" />
              {{ item.text }}
            </router-link>
          </li>
        </ul>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { onMounted, onBeforeUnmount } from 'vue'

const navItems = [
  { to: '/',               text: 'Inicio',          icon: 'https://img.icons8.com/ios-filled/24/ffffff/home.png' },
  { to: '/sobre-nosotros', text: 'Sobre Nosotros',  icon: 'https://img.icons8.com/ios-filled/24/ffffff/info.png' },
  { to: '/contacto',       text: 'Contacto',        icon: 'https://img.icons8.com/ios-filled/24/ffffff/contacts.png' },
  { to: '/login',          text: 'Ingresar',        icon: 'https://img.icons8.com/ios-filled/24/ffffff/login-rounded-right.png' },
]

/* ====== Reserva dinámica de espacio para el navbar fijo ====== */
function setNavHeight () {
  const nav = document.querySelector('.custom-navbar')
  if (!nav) return
  const h = nav.offsetHeight
  document.documentElement.style.setProperty('--nav-h', h + 'px')
}

/* ====== Fondo translúcido + blur al hacer scroll ====== */
function bindScrollEffect () {
  const nav = document.querySelector('.custom-navbar')
  if (!nav) return () => {}
  const onScroll = () => {
    if (window.scrollY > 8) nav.classList.add('scrolled')
    else nav.classList.remove('scrolled')
  }
  window.addEventListener('scroll', onScroll, { passive: true })
  onScroll() // estado inicial correcto
  return () => window.removeEventListener('scroll', onScroll)
}

let offScroll = null
const onResize = () => setNavHeight()

onMounted(() => {
  setNavHeight()
  window.addEventListener('resize', onResize)

  const collapse = document.getElementById('navbarNavDropdown')
  if (collapse) {
    collapse.addEventListener('shown.bs.collapse', setNavHeight)
    collapse.addEventListener('hidden.bs.collapse', setNavHeight)
  }

  offScroll = bindScrollEffect()
  requestAnimationFrame(setNavHeight)

  onBeforeUnmount(() => {
    window.removeEventListener('resize', onResize)
    if (collapse) {
      collapse.removeEventListener('shown.bs.collapse', setNavHeight)
      collapse.removeEventListener('hidden.bs.collapse', setNavHeight)
    }
    if (offScroll) offScroll()
  })
})
</script>

<style scoped>
  @import '@/assets/css/navBar.css';
</style>
