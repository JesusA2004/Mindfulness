<template>
  <div class="index-page">

    <!-- ========================= HERO ========================= -->
    <section id="hero" class="hero-section d-flex align-items-center position-relative overflow-hidden min-vh-100">
      <!-- Video de fondo -->
      <video
        id="heroVideo"
        class="hero-video position-absolute top-0 start-0 w-100 h-100 z-0 pe-none"
        :src="heroVideo"
        autoplay
        muted
        loop
        playsinline
        preload="metadata"
      ></video>

      <!-- Overlay para contraste -->
      <div class="hero-scrim position-absolute top-0 start-0 w-100 h-100 z-1"></div>

      <!-- Contenido -->
      <div class="container h-100 position-relative z-2 text-white">
        <div class="row h-100 align-items-center justify-content-center justify-content-lg-between text-center text-lg-start">
          <div class="col-12 col-lg-6 order-2 order-lg-1 mt-4 mt-lg-0">
            <h1 class="hero-title mb-3" data-animate="fade-up">
              Tu espacio de<br />
              calma interior
            </h1>
            <p class="hero-subtitle mb-4 mx-auto mx-lg-0" data-animate="fade-up" data-delay="120">
              Empodera a tus estudiantes para gestionar el estrés y mejorar el bienestar.
            </p>
            <div class="d-flex gap-3 justify-content-center justify-content-lg-start" data-animate="fade-up" data-delay="200">
              <button class="btn-cta" @click="scrollTo('about')">¿Qué es mindfulness?</button>
              <button class="btn btn-outline-light" @click="scrollTo('benefits')">Ver beneficios</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Onda separadora -->
      <div class="wave-sep position-absolute start-0 end-0 z-3">
        <svg viewBox="0 0 1440 120" preserveAspectRatio="none" class="w-100">
          <path d="M0,64L72,58.7C144,53,288,43,432,58.7C576,75,720,117,864,117.3C1008,117,1152,75,1296,58.7C1440,43,1584,53,1728,69.3L1728,160L0,160Z" fill="#fff"></path>
        </svg>
      </div>
    </section>

    <!-- =================== ABOUT (Horizontal Card) =================== -->
    <section id="about" class="py-5 bg-white">
      <div class="container">
        <div class="card shadow-sm border-0 mb-3 overflow-hidden">
          <div class="row g-0 align-items-stretch">
            <!-- Imagen -->
            <div class="col-md-5">
              <img
                :src="aboutImg"
                alt="Práctica de mindfulness"
                class="img-fluid h-100 w-100 object-fit-cover"
              />
            </div>
            <!-- Texto -->
            <div class="col-md-7">
              <div class="card-body p-4 p-lg-5">
                <h3 class="card-title fw-bold mb-3">¿Qué es el Mindfulness?</h3>
                <p class="card-text lead mb-3">
                  El <strong>mindfulness</strong> es la capacidad de prestar atención
                  al momento presente con una actitud abierta, curiosa y sin juicio.
                  En contextos educativos ayuda a estudiantes y docentes a desarrollar
                  autorregulación emocional, foco atencional y resiliencia ante el estrés.
                </p>
                <p class="card-text">
                  Nuestra plataforma incluye ejercicios guiados, respiración consciente,
                  prácticas breves para el aula, seguimiento de emociones y tableros de
                  progreso para medir impacto en bienestar y rendimiento.
                </p>
                <p class="card-text">
                  <small class="text-muted">Basado en prácticas MBSR/MBCT adaptadas al entorno escolar.</small>
                </p>
                <div class="mt-3">
                  <button class="btn btn-success me-2" @click="scrollTo('programs')">
                    Explorar programas
                  </button>
                  <button class="btn btn-outline-secondary" @click="playInfo">
                    Ver demo
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>  
    </section>

    <!-- ====================== BENEFICIOS ====================== -->
    <section id="benefits" class="benefits-section py-5">
      <div class="container">
        <h2 class="section-heading text-center" data-animate="fade-up">Beneficios del Mindfulness</h2>

        <div class="row g-4 mt-4">
          <div class="col-12 col-md-6 col-lg-4" v-for="(b, i) in benefits" :key="i">
            <div class="benefit-card h-100 text-center" data-animate="fade-up" :data-delay="i*100">
              <img :src="b.icon" class="benefit-icon" :alt="b.title" />
              <h5 class="benefit-title">{{ b.title }}</h5>
              <p class="benefit-text">{{ b.text }}</p>
            </div>
          </div>
        </div>

      </div>
    </section>

    <!-- ======================= PROGRAMAS ======================= -->
    <section id="programs" class="programs-section py-5">
      <div class="container">
        <h2 class="section-heading text-center" data-animate="fade-up">
          Nuestros Programas de Mindfulness
        </h2>

        <div class="row g-4 mt-4">
          <div class="col-12 col-md-6 col-lg-4" v-for="(p, i) in programs" :key="i">
            <article class="program-card text-center h-100" data-animate="fade-up" :data-delay="i*100">
              <img :src="p.img" class="program-photo" :alt="p.title" />
              <h6 class="program-title mt-3">{{ p.title }}</h6>
            </article>
          </div>
        </div>
      </div>
    </section>

    <!-- ======================== CONTACTO ======================= -->
    <section id="contact" class="contact-section py-5 text-white">
      <div class="container">
        <h2 class="section-heading text-center mb-4" data-animate="fade-up">Solicita tu Cotización</h2>
        <form class="row g-3 justify-content-center" @submit.prevent="submitForm" novalidate>
          <div class="col-12 col-md-4">
            <input v-model.trim="form.name" type="text" class="form-control" placeholder="Nombre / Empresa" />
          </div>
          <div class="col-12 col-md-4">
            <input v-model.trim="form.email" type="email" class="form-control" placeholder="Correo electrónico" />
          </div>
          <div class="col-12 col-md-4 d-grid">
            <button type="submit" class="btn btn-light">Enviar</button>
          </div>
        </form>
      </div>
    </section>

    <Footer />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Footer from '@/components/Footer.vue'
import Swal from 'sweetalert2'

/* Recursos */
const heroVideo  = new URL('@/assets/media/videoIndex.mp4', import.meta.url).href
const aboutImg   = new URL('@/assets/images/meditationIndex.png', import.meta.url).href

/* Datos UI (mock) */
const benefits = [
  { icon: new URL('@/assets/images/meditationIndex.png', import.meta.url).href, title: 'Mejora la Concentración', text: 'Potencia la concentración y atención.' },
  { icon: new URL('@/assets/images/meditationIndex.png', import.meta.url).href, title: 'Reduce la Ansiedad', text: 'Alivia el estrés y la preocupación.' },
  { icon: new URL('@/assets/images/meditationIndex.png', import.meta.url).href, title: 'Potencia el Rendimiento', text: 'Aumenta la productividad y el compromiso.' },
]

const programs = [
  { img: new URL('@/assets/images/meditationIndex.png', import.meta.url).href, title: 'Respiración guiada en aula' },
  { img: new URL('@/assets/images/meditationIndex.png', import.meta.url).href, title: 'Prácticas de atención plena' },
  { img: new URL('@/assets/images/meditationIndex.png', import.meta.url).href, title: 'Regulación emocional' },
]

/* Formulario */
const form = ref({ name: '', email: '' })
function submitForm () {
  if (!form.value.name || !form.value.email) {
    Swal.fire({ icon: 'warning', title: 'Campos incompletos', text: 'Escribe tu nombre/empresa y correo.', confirmButtonText: 'Entendido' })
    return
  }
  const ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)
  if (!ok) {
    Swal.fire({ icon: 'error', title: 'Correo inválido', text: 'Revisa el formato del correo electrónico.', confirmButtonText: 'Corregir' })
    return
  }
  Swal.fire({ icon: 'success', title: '¡Gracias!', text: 'Hemos recibido tu solicitud. Te contactaremos pronto.', confirmButtonText: 'Cerrar' })
  form.value = { name: '', email: '' }
}

/* Smooth scroll */
const scrollTo = id => {
  const el = document.getElementById(id)
  if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

function playInfo () {
  Swal.fire({
    icon: 'info',
    title: 'Mindfulness en acción',
    html: '<p style="margin:0">Aquí puedes integrar tu reproductor o un video embebido.</p>',
    confirmButtonText: 'Cerrar'
  })
}

/* Animaciones (opcionales) */
onMounted(() => {
  const io = new IntersectionObserver((entries) => {
    entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('in') })
  }, { threshold: 0.18 })
  document.querySelectorAll('[data-animate]').forEach(el => io.observe(el))
})
</script>

<style scoped>
  @import '@/assets/css/Index.css';

  /* utilitario para la imagen de la card que cubra bien el alto */
  .object-fit-cover { object-fit: cover; }
</style>
