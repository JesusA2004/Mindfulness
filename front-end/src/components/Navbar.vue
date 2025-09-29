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

/* ====== Efecto compacto al hacer scroll (opcional pero bonito) ====== */
function bindScrollCompact () {
  const nav = document.querySelector('.custom-navbar')
  if (!nav) return () => {}
  const onScroll = () => {
    if (window.scrollY > 10) nav.classList.add('scrolled')
    else nav.classList.remove('scrolled')
    setNavHeight() // por si cambia la altura en modo compacto
  }
  window.addEventListener('scroll', onScroll, { passive: true })
  return () => window.removeEventListener('scroll', onScroll)
}

let offScroll = null
function onResize () { setNavHeight() }

onMounted(() => {
  setNavHeight()

  // Recalcula al redimensionar y al abrir/cerrar el menú colapsable
  window.addEventListener('resize', onResize)
  const collapse = document.getElementById('navbarNavDropdown')
  if (collapse) {
    collapse.addEventListener('shown.bs.collapse', setNavHeight)
    collapse.addEventListener('hidden.bs.collapse', setNavHeight)
  }

  offScroll = bindScrollCompact()

  // Un pequeño tick para asegurar layout final
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

<!-- Quita 'scoped' para que afecte a <body> y secciones; si necesitas scoped, usa :global(...) -->
<style>
/* ================== Tokens y reserva global ================== */
:root{ --nav-h: 72px; }

/* Reserva espacio para que el contenido no quede debajo del navbar */
body{ padding-top: var(--nav-h); }

/* Cuando navegas a #anclas, que no queden ocultas por el navbar */
section[id]{ scroll-margin-top: calc(var(--nav-h) + 12px); }

/* ================== NAVBAR personalizado ================== */
.custom-navbar{
  z-index: 1040;
  padding: 0.75rem 1.5rem;
  /* Degradado morado → azul → verde con transparencia */
  background: linear-gradient(
    90deg,
    rgba(3, 3, 3, 0.895) 0%,
    rgba(16, 16, 16, 0.993) 100%
  );
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  box-shadow: 0 4px 12px rgba(0,0,0,.2);
  transition: background .3s ease, padding .3s ease, backdrop-filter .3s ease;
}

/* Modo compacto al hacer scroll */
.custom-navbar.scrolled{
  padding: .5rem 1rem;
  background: linear-gradient(
    90deg,
    rgba(128, 0, 128, 0.92) 0%,
    rgba(111, 0, 95, 0.92) 100%
  );
}

.navbar-brand{
  font-weight: 700;
  font-size: 1.75rem;
  letter-spacing: 1px;
  color: #fff !important;
}

.navbar-nav .nav-item{ margin-left: 1.25rem; }

.navbar-nav .nav-link{
  position: relative;
  color: #fff;
  font-weight: 500;
  padding-bottom: .5rem;
  transition: color .3s ease;
}

.navbar-nav .nav-link .nav-icon{
  margin-right: .5rem;
  width: 1.25rem; height: 1.25rem; vertical-align: middle;
}

.navbar-nav .nav-link:hover,
.navbar-nav .nav-link.active{ color: #fff !important; }

.navbar-nav .nav-link:hover::after,
.navbar-nav .nav-link.active::after{
  content:"";
  position:absolute; bottom:0; left:0;
  width:100%; height:2px; background:#fff; border-radius:1px;
}

/* ================== Responsive ================== */
@media (max-width: 991.98px){
  .navbar-nav .nav-item{
    margin-left: 0;
    margin-top: .5rem;
  }
}

/* ========== Si prefieres mantener <style scoped>, descomenta: ==========
:global(body){ padding-top: var(--nav-h); }
:global(section[id]){ scroll-margin-top: calc(var(--nav-h) + 12px); }
========================================================================= */
</style>
