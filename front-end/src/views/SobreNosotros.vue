<template>
  <!-- ============== HERO estilo agencia ============== -->
  <section class="sobre-hero">
    <div class="container">
      <div class="row align-items-end g-4">
        <!-- TITULAR XXL a la izquierda -->
        <div class="col-12 col-lg-7">
          <h3 class="cta-title mb-3 programs-kicker text-uppercase fw-semibold mb-2">Mindora</h3>
          <Transition name="fadein">
            <!-- utilidades Bootstrap para mejor legibilidad -->
            <h1 key="hero-title" class="hero-split-title m-0 lh-1 fw-bold">
              CONECTAMOS<br />
              BIENESTAR<br />
              Y TECNOLOGÍA
            </h1>
          </Transition>
        </div>

        <!-- Copy + CTA a la derecha -->
        <div class="col-12 col-lg-5">
          <Transition name="fadein">
            <div key="hero-copy" class="hero-aside">
              <div class="hero-dot"></div>
              <h2 class="hero-aside-title m-0 lh-1 fw-semibold">
                PARA EQUIPOS, AULAS Y ORGANIZACIONES
              </h2>
              <p class="hero-aside-text mt-3 mb-4">
                <strong>Mindora</strong> integra mindfulness, ciencia emocional y diseño
                para transformar cultura y desempeño — en escuelas, empresas y espacios de trabajo.
              </p>
              <div class="d-flex flex-wrap gap-2">
                <router-link
                  to="/contacto"
                  class="btn btn-outline-light rounded-pill px-4 fw-semibold"
                >
                  Contacto
                </router-link>
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </div>
  </section>

  <!-- ============== MISIÓN / VISIÓN (glass claro y legible) ============== -->
  <section class="sobre-blocks py-5">
    <div class="container">
      <div class="row g-4">
        <div class="col-12 col-lg-6">
          <Transition name="fadeup">
            <article key="mision" class="card glass-card h-100 border-0 rounded-4">
              <div class="card-body p-4 p-lg-5">
                <h3 class="card-kicker">Misión</h3>
                <h3 class="card-heading">Tecnología consciente con impacto humano</h3>
                <p class="mb-0 card-text">
                  Diseñamos experiencias simples y medibles que promueven calma, atención y conexión
                  en contextos educativos y corporativos.
                </p>
              </div>
            </article>
          </Transition>
        </div>
        <div class="col-12 col-lg-6">
          <Transition name="fadeup">
            <article key="vision" class="card glass-card h-100 border-0 rounded-4">
              <div class="card-body p-4 p-lg-5">
                <h3 class="card-kicker">Visión</h3>
                <h3 class="card-heading">Bienestar como ventaja competitiva</h3>
                <p class="mb-0 card-text">
                  Ser la plataforma de referencia en <em>tecnología para el bienestar</em>,
                  integrando ciencia, datos y diseño para transformar organizaciones.
                </p>
              </div>
            </article>
          </Transition>
        </div>
      </div>
    </div>
  </section>

  <!-- ============== LO QUE HACEMOS ============== -->
  <section class="sobre-quehacemos py-5">
    <div class="container">
      <div class="row g-4 align-items-center">
        <div class="col-12 col-lg-6">
          <Transition name="fadein">
            <h3 key="qh-title" class="block-title mb-3">Lo que hacemos</h3>
          </Transition>

          <TransitionGroup name="fadeup" tag="ul" class="list-unstyled m-0">
            <li
              v-for="(item, i) in whatWeDo"
              :key="item.title + i"
              class="d-flex align-items-start gap-3 list-item py-2"
            >
              <div class="badge-dot mt-2"></div>
              <div>
                <h6 class="m-0 fw-bold text-white">{{ item.title }}</h6>
                <p class="m-0 text-light-80">{{ item.desc }}</p>
              </div>
            </li>
          </TransitionGroup>
        </div>

        <div class="col-12 col-lg-6">
          <Transition name="fadein">
            <div key="qh-img" class="ratio ratio-16x9 rounded-4 overflow-hidden shadow-hero">
              <img
                :src="sideImg"
                alt="Mindora - Recursos para equipos y aulas"
                class="w-100 h-100 object-fit-cover"
                loading="lazy"
              />
            </div>
          </Transition>
        </div>
      </div>
    </div>
  </section>

  <!-- ============== CTA FINAL ============== -->
  <section class="sobre-cta py-5 text-center">
    <div class="container">
      <h4 class="cta-title mb-3 programs-kicker text-uppercase fw-semibold mb-2">Llevemos el bienestar a tu organización</h4>
      <p class="text-light-80 mb-4">
        Agenda una conversación y diseñemos un plan Mindora a tu medida.
      </p>
      <router-link to="/contacto" class="btn btn-success rounded-pill px-4 fw-semibold me-2">
        Solicitar información
      </router-link>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'

const sideImg  = new URL('@/assets/images/loquehacemos.png', import.meta.url).href

const whatWeDo = ref([
  { title: 'Guías y micro-prácticas', desc: 'Rutinas breves que caben en reuniones, clases o turnos de trabajo.' },
  { title: 'Recursos multimedia', desc: 'Audios y videos para sesiones consistentes y fáciles de aplicar.' },
  { title: 'Indicadores de bienestar', desc: 'Seguimiento de enfoque, calma y clima emocional del equipo.' },
  { title: 'Formación y acompañamiento', desc: 'Entrenamiento para líderes, docentes y equipos de RR.HH.' }
])

/* === Indica que esta vista tiene HERO arriba === */
onMounted(() => document.body.classList.add('has-hero'))
onBeforeUnmount(() => document.body.classList.remove('has-hero'))
</script>

<style scoped>
@import '@/assets/css/sobreNosotros.css';
</style>
