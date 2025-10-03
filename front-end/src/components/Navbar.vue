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

<style>
/* ================== Fuente ================== */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

:root{ --nav-h: 72px; }

/* Reserva global (el hero lo anula con margen negativo) */
body{ 
  font-family: 'Poppins', sans-serif;
  padding-top: var(--nav-h);
}

section[id]{ scroll-margin-top: calc(var(--nav-h) + 12px); }

/* ================== NAVBAR ================== */
.custom-navbar{
  z-index: 1100;
  padding: 0.75rem 1.5rem;
  background: transparent !important;  /* totalmente transparente al inicio */
  box-shadow: none !important;
  border: none !important;
  transition: padding .25s ease, background-color .25s ease, backdrop-filter .25s ease;
}

.navbar-brand{
  font-weight: 600;
  font-size: 1.5rem;
  color: #fff !important;
  text-shadow: 0 1px 8px rgba(0,0,0,.35);
}

.navbar-nav .nav-item{ margin-left: 1.25rem; }
.navbar-nav .nav-link{
  position: relative;
  color: #fff;
  font-weight: 500;
  font-size: 1rem;
  padding-bottom: .5rem;
  transition: color .25s ease, opacity .25s ease;
  text-shadow: 0 1px 8px rgba(0,0,0,.35);
  opacity: .95;
}
.navbar-nav .nav-link .nav-icon{
  margin-right: .5rem;
  width: 1.25rem; height: 1.25rem; vertical-align: middle;
  filter: drop-shadow(0 1px 6px rgba(0,0,0,.35));
}
.navbar-nav .nav-link:hover,
.navbar-nav .nav-link.active{ color: #fff !important; opacity: 1; }
.navbar-nav .nav-link:hover::after,
.navbar-nav .nav-link.active::after{
  content:"";
  position:absolute; bottom:0; left:0;
  width:100%; height:2px; background:#fff; border-radius:1px;
}

/* Al hacer scroll: translúcido + blur (sigues viendo el fondo) */
.custom-navbar.scrolled{
  padding: .55rem 1rem;
  background-color: rgba(0,0,0,.28) !important;
  backdrop-filter: saturate(120%) blur(10px);
  -webkit-backdrop-filter: saturate(120%) blur(10px);
  box-shadow: 0 2px 10px rgba(0,0,0,.18);
}

/* Menú móvil abierto: translúcido + blur */
.navbar-collapse.show{
  background-color: rgba(0,0,0,.28);
  backdrop-filter: saturate(120%) blur(10px);
  -webkit-backdrop-filter: saturate(120%) blur(10px);
  border-radius: .75rem;
  padding: .5rem;
}

/* Responsive */
@media (max-width: 991.98px){
  .navbar-nav .nav-item{
    margin-left: 0;
    margin-top: .5rem;
  }
}
</style>
