<template>
  <div class="index-page">

    <!-- ========================= HERO ========================= -->
    <section id="hero" class="hero-section d-flex align-items-center">
      <div class="container h-100">
        <div class="row h-100 align-items-center justify-content-between">
          <!-- Texto -->
          <div class="col-12 col-lg-6 order-2 order-lg-1 mt-4 mt-lg-0">
            <h1 class="hero-title mb-3" data-animate="fade-up">
              Mindfulness<br />
              para el Estrés<br />
              Estudiantil
            </h1>
            <p class="hero-subtitle mb-4" data-animate="fade-up" data-delay="120">
              Empodera a tus estudiantes para gestionar el estrés y mejorar el bienestar.
            </p>
            <button class="btn btn-cta me-2" @click="scrollTo('benefits')" data-animate="fade-up" data-delay="200">
              Comenzar
            </button>
          </div>

          <!-- Ilustración -->
          <div class="col-12 col-lg-5 text-center order-1 order-lg-2">
            <img
              class="hero-illustration img-fluid"
              :src="heroImg"
              alt="Ilustración 3D meditando"
              data-float
              data-animate="zoom-in"
            />
          </div>
        </div>
      </div>

      <!-- Onda separadora exacta del mockup -->
      <div class="wave-sep">
        <svg viewBox="0 0 1440 120" preserveAspectRatio="none">
          <path d="M0,64L72,58.7C144,53,288,43,432,58.7C576,75,720,117,864,117.3C1008,117,1152,75,1296,58.7C1440,43,1584,53,1728,69.3L1728,160L0,160Z"></path>
        </svg>
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

        <!-- Fila: video + ¿Qué es? -->
        <div class="row align-items-center mt-5 g-4">
          <div class="col-lg-6">
            <div class="video-thumb" data-animate="fade-right">
              <img :src="videoThumb" alt="Video Mindfulness" />
              <button class="play-btn" type="button" @click="playInfo" aria-label="Reproducir video">
                <span>&#9658;</span>
              </button>
            </div>
          </div>
          <div class="col-lg-6">
            <h3 class="what-title" data-animate="fade-left">¿Qué es el Mindfulness?</h3>
            <p class="what-text" data-animate="fade-left" data-delay="80">
              Mindfulness es la práctica de estar presente y totalmente involucrado en el momento.
              Ayuda a enfocarse, a sentirse menos estresado y a rendir mejor en el aula.
            </p>
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

/* ==== Imágenes (ajusta las rutas a tus assets reales) ==== */
const heroImg    = new URL('@/assets/images/meditation2.jpg', import.meta.url).href
const videoThumb = new URL('@/assets/images/meditationIndex.png', import.meta.url).href

/* ==== Data UI (títulos y assets como en el mockup) ==== */
const benefits = [
  {
    icon: new URL('@/assets/images/meditationIndex.png', import.meta.url).href,
    title: 'Mejora la Concentrar',
    text: 'Potencia la concentración y atención.',
  },
  {
    icon: new URL('@/assets/images/meditationIndex.png', import.meta.url).href,
    title: 'Reduce la Ansiclad',
    text: 'Alivia el estrés y la preocupación.',
  },
  {
    icon: new URL('@/assets/images/meditationIndex.png', import.meta.url).href,
    title: 'Potencia el Redimiento',
    text: 'Aumenta la productividad y el compromiso.',
  },
]
// Si prefieres ortografía correcta, cambia arriba los textos (los dejé como el mockup).

const programs = [
  {
    img: new URL('@/assets/images/meditationIndex.png', import.meta.url).href,
    title: 'Respiración guiada en aula',
  },
  {
    img: new URL('@/assets/images/meditationIndex.png', import.meta.url).href,
    title: 'Prácticas de atención plena',
  },
  {
    img: new URL('@/assets/images/meditationIndex.png', import.meta.url).href,
    title: 'Regulación emocional',
  },
]

/* ==== Form ==== */
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

/* ==== Interacciones ==== */
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

/* ==== Animaciones (JS puro) ==== */
onMounted(() => {
  // Fade/slide con IntersectionObserver
  const io = new IntersectionObserver((entries) => {
    entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('in') })
  }, { threshold: 0.18 })
  document.querySelectorAll('[data-animate]').forEach(el => io.observe(el))

  // Flotación suave de la ilustración
  const floater = document.querySelector('[data-float]')
  if (floater) {
    let t = 0
    const tick = () => {
      t += 0.016
      floater.style.transform = `translateY(${Math.sin(t) * 6}px)`
      requestAnimationFrame(tick)
    }
    tick()
  }
})
</script>

<style>
/* =================== TOKENS / PALETA =================== */
:root{
  --bg-hero-start:#0e7c66;
  --bg-hero-end:#0b5b49;
  --ink:#0f172a;
  --muted:#667085;
  --surface:#ffffff;
  --shadow: 0 10px 24px rgba(15, 23, 42, .08);
  --radius-xl: 22px;
}

/* ======================= HERO ======================== */
.hero-section{
  position: relative;
  min-height: 74vh;
  width: 100%;
  background:
    radial-gradient(1200px 520px at 75% 20%, rgba(255,255,255,.09), transparent 50%),
    linear-gradient(180deg, var(--bg-hero-start), var(--bg-hero-end));
  color:#fff;
  overflow:hidden;
}
.hero-title{
  font-weight: 800;
  line-height: 1.05;
  font-size: clamp(34px, 5.2vw, 56px);
  letter-spacing:.2px;
}
.hero-subtitle{
  font-size: clamp(16px, 1.4vw, 18px);
  opacity:.95;
  max-width: 44ch;
}
.btn-cta{
  background:#f1f5f9;
  color:#0b3d33;
  border:0;
  padding:.9rem 1.4rem;
  border-radius: 999px;
  font-weight: 700;
  box-shadow: var(--shadow);
}
.hero-illustration{ max-height: 360px; will-change: transform; }

.wave-sep{ position:absolute; left:0; right:0; bottom:-1px; line-height:0; }
.wave-sep svg{ width:100%; height:120px; display:block; }
.wave-sep path{ fill:#fff; }

/* ===================== BENEFICIOS ===================== */
.section-heading{
  font-weight:800;
  color:var(--ink);
  font-size: clamp(26px, 3.6vw, 36px);
}
.benefit-card{
  background: var(--surface);
  border-radius: var(--radius-xl);
  padding: 22px;
  box-shadow: var(--shadow);
}
.benefit-icon{ width: 60px; height: 60px; object-fit: contain; margin-bottom: 10px; }
.benefit-title{ font-weight: 800; margin-bottom: 6px; }
.benefit-text{ color: var(--muted); margin: 0; }

/* ============ Video + ¿Qué es el Mindfulness? ============ */
.video-thumb{
  position: relative;
  border-radius: 18px;
  overflow: hidden;
  background:#000;
  box-shadow: var(--shadow);
}
.video-thumb img{ display:block; width:100%; height:auto; opacity:.94; }
.play-btn{
  position:absolute; top:50%; left:50%; transform: translate(-50%,-50%);
  width:64px; height:64px; border-radius:50%;
  border:0; background:#fff; color:#0b5b49; font-size:28px;
  display:flex; align-items:center; justify-content:center;
  box-shadow: 0 6px 18px rgba(0,0,0,.2);
}
.what-title{ font-weight:800; margin-bottom:.4rem; color:var(--ink); }
.what-text{ color:var(--muted); max-width: 56ch; }

/* ======================= PROGRAMAS ======================= */
.programs-section{ background:#f8fafc; }
.program-card{
  background: var(--surface);
  border-radius: 18px;
  padding: 14px;
  box-shadow: var(--shadow);
}
.program-photo{
  width:100%;
  aspect-ratio: 4/3;
  object-fit: cover;
  border-radius: 14px;
}
.program-title{ font-weight:700; }

/* ======================== CONTACTO ======================= */
.contact-section{
  background: linear-gradient(180deg, var(--bg-hero-end), #0a473a);
}
.contact-section .form-control{
  height:48px; border-radius:12px; border-color: transparent;
}
.contact-section .form-control:focus{
  box-shadow: 0 0 0 0.2rem rgba(255,255,255,.25);
  border-color:#fff;
}

/* =================== ANIMACIONES (JS) =================== */
[data-animate]{ opacity:0; transform: translateY(12px); transition: all .6s ease; }
[data-animate].in{ opacity:1; transform:none; }
[data-animate="fade-left"]{ transform: translateX(24px); }
[data-animate="fade-right"]{ transform: translateX(-24px); }
[data-animate="zoom-in"]{ transform: scale(.92); }
[data-animate="fade-left"].in,
[data-animate="fade-right"].in,
[data-animate="zoom-in"].in{ transform:none; }

/* Soporte de delays desde atributo (sin JS extra) */
[data-delay]{ transition-delay: var(--d, 0ms); }
[data-delay]{ --d: attr(data-delay ms); }

/* =================== RESPONSIVE FINO =================== */
@media (max-width: 991.98px){
  .hero-illustration{ max-height:300px; }
}
@media (max-width: 575.98px){
  .hero-title{ font-size:32px; }
  .hero-subtitle{ font-size:15px; }
}
</style>
