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

    <!-- =================== ABOUT (Responsive Card) =================== -->
    <section id="about" class="py-5 bg-white">
      <div class="container">
        <div class="card border-0 shadow-sm overflow-hidden rounded-4">
          <div class="row g-0 align-items-center">
            <!-- Imagen (columna izquierda en desktop, arriba en móvil) -->
            <div class="col-12 col-md-5">
              <div class="p-3 p-md-4">
                <img
                  :src="aboutImg"
                  alt="Práctica de mindfulness"
                  class="img-fluid w-100 rounded-6 object-fit-cover"
                  style="max-height: 360px;"
                />
              </div>
            </div>

            <!-- Texto + Botones -->
            <div class="col-12 col-md-7">
              <div class="card-body p-3 p-md-4 p-lg-5 text-center text-md-start">
                <h3 class="card-title fw-bold mb-3">¿Qué es el Mindfulness?</h3>

                <p class="card-text lead mb-3">
                  “El <strong>mindfulness</strong> es la conciencia que emerge al prestar atención, de manera intencional, en el momento presente y sin juzgar, a la experiencia que se desarrolla momento a momento”.
                </p>
                <p class="card-text mb-0">
                  Jon Kabat-Zinn, creador del programa MBSR y autor de <em>Mindfulness para principiantes</em>.
                </p>

                <!-- Botones 100% ancho en móvil, en línea desde sm -->
                <div class="mt-4 d-grid gap-2 d-sm-flex">
                  <button class="btn btn-success btn-lg px-4 fw-semibold rounded-pill" @click="scrollTo('programs')">
                    Explorar programas
                  </button>

                  <!-- Redirige a otra página con Vue Router -->
                  <router-link to="/contacto" class="btn btn-outline-secondary btn-lg px-4 fw-semibold rounded-pill">
                    Conocer más
                  </router-link>
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

        <!-- ENCABEZADO: texto izq / título der -->
        <div class="row align-items-center mb-4 text-center text-lg-start">
          <!-- Texto descriptivo -->
          <div class="col-12 col-lg-6 order-2 order-lg-1">
            <p class="benefit-subtext text-muted mb-0 pe-lg-5 ">
              Prácticas breves que fortalecen la atención, la calma y el bienestar emocional en el aula.
            </p>
          </div>

          <!-- Título -->
          <div class="col-12 col-lg-6 order-1 order-lg-2 text-center text-lg-end">
            <h2 class="section-heading fw-bold mb-0" >
              Beneficios del Mindfulness
            </h2>
          </div>
        </div>

        <!-- === TARJETAS DE BENEFICIOS === -->
        <div class="row g-4 mt-3" id="benefits-cards">
          <div class="col-12 col-sm-6 col-lg-4" v-for="(b, i) in benefits" :key="i">
            <div class="card benefit-card h-100 text-center shadow-sm border-0">
              <!-- Imagen superior -->
              <img :src="b.icon" :alt="b.title" class="card-img-top benefit-img" />
              <!-- Contenido -->
              <div class="card-body">
                <h5 class="card-title fw-bold text-dark mb-2 programs-kicker text-uppercase fw-semibold mb-2" data-animate="fade-right">{{ b.title }}</h5>
                <p class="card-text text-muted mb-0">{{ b.text }}</p>
              </div>
            </div>
          </div>
        </div>

      </div>
    </section>

    <!-- ======================= PROGRAMAS ======================= -->
    <section id="programs" class="programs-section text-light py-5">
      <div class="container">

        <!-- Hero de Programas: Título izq / Video der -->
        <div class="row align-items-center programs-hero-row g-4">
          <!-- Título -->
          <div class="col-12 col-lg-6">
            <div class="programs-kicker text-uppercase fw-semibold mb-2" data-animate="fade-right">
              Tu espacio de calma interior
            </div>
            <h2 class="programs-hero-title fw-black m-0" data-animate="fade-right" data-delay="80">
              Nuestros<br class="d-none d-xl-block"> Programas<br class="d-none d-xl-block"> Mindfulness
            </h2>
            <p class="text-light mt-3 mb-2" data-animate="fade-right" data-delay="140">
              Diseñados para el aula: breves, prácticos y efectivos.
            </p>
            <p class="programs-brand mt-2 mb-0" data-animate="fade-right" data-delay="180">
              <small>By <span>Mindora</span></small>
            </p>
          </div>

          <!-- Video a la derecha -->
          <div class="col-12 col-lg-6" data-animate="fade-left">
            <div class="programs-video-wrap rounded-4 shadow overflow-hidden">
              <video
                class="w-100 h-100 object-fit-cover"
                :src="heroProgramas"
                autoplay
                muted
                loop
                playsinline
                preload="metadata"
              ></video>
            </div>
          </div>
        </div>

        <!-- Lista de programas (UNA CARD POR FILA) -->
        <TransitionGroup name="prog" tag="div" class="row g-4 mt-5">
          <div
            class="col-12"              
            v-for="(p, i) in programs"
            :key="p.title + i"
          >
            <article class="card program-hcard h-100 border-0 rounded-4" data-animate="fade-up" :data-delay="i*80">
              <div class="row g-0 align-items-stretch">
                <!-- Imagen -->
                <div class="col-12 col-md-5">
                  <div class="program-img-wrap h-100">
                    <img
                      :src="p.img"
                      :alt="p.title"
                      class="img-fluid w-100 h-100 object-fit-cover rounded-top-4 rounded-md-start-4"
                    />
                  </div>
                </div>

                <!-- Contenido -->
                <div class="col-12 col-md-7">
                  <div class="card-body d-flex flex-column program-card-body">
                    <h5 class="card-title fw-bold mb-2 program-title text-white">{{ p.title }}</h5>

                    <p class="program-text mb-3" v-if="p.desc">{{ p.desc }}</p>
                    <p class="program-text mb-3" v-else>
                      Sesiones breves de atención plena para mejorar enfoque, calma y regulación emocional.
                    </p>

                    <div v-if="p.tags" class="mb-3 d-flex flex-wrap gap-2">
                      <span v-for="(t, ti) in p.tags" :key="ti" class="badge program-badge">{{ t }}</span>
                    </div>

                    <div class="mt-auto d-flex gap-2">
                      <router-link
                        to="/contacto"
                        class="btn btn-outline-secondary btn-sm rounded-pill px-3"
                      >
                        Solicitar información
                      </router-link>
                    </div>
                  </div>
                </div>
              </div>
            </article>
          </div>
        </TransitionGroup>
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
const heroProgramas = new URL('@/assets/media/programas.mp4', import.meta.url).href
const aboutImg   = new URL('@/assets/images/conceptoMindfulness.jpg', import.meta.url).href

/* Datos UI (mock) */
const benefits = [
  { icon: new URL('@/assets/images/ansiedad.png', import.meta.url).href, title: 'Reduce la Ansiedad', text: 'Alivia el estrés y la preocupación.' },
  { icon: new URL('@/assets/images/concentracion.png', import.meta.url).href, title: 'Mejora la Concentración', text: 'Potencia la concentración y atención.' },
  { icon: new URL('@/assets/images/productividad.jpg', import.meta.url).href, title: 'Potencia el Rendimiento', text: 'Aumenta la productividad y el compromiso.' },
]

const programs = [
  { img: new URL('@/assets/images/respiracion.png', import.meta.url).href, title: 'Respiración guiada en aula' },
  { img: new URL('@/assets/images/practicaAtencion.png', import.meta.url).href, title: 'Prácticas de atención plena' },
  { img: new URL('@/assets/images/regulacion.png', import.meta.url).href, title: 'Regulación emocional' },
]

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
