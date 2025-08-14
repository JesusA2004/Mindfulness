<template>
  <div class="index-page">
    <!-- Navbar 3D + Realista -->
    <nav class="navbar navbar-expand-lg navbar-light sticky-top py-3">
      <div class="container">
        <a class="navbar-brand" href="#">
        <img src="@/assets/logo.png" alt="Mindfulness Logo" class="logo-3d" /> 
        </a>
        <button
          class="navbar-toggler"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#navbarNav"
        >
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav ms-auto">
            <li class="nav-item"><a class="nav-link" href="#hero">Inicio</a></li>
            <li class="nav-item"><a class="nav-link" href="#benefits">Beneficios</a></li>
            <li class="nav-item"><a class="nav-link" href="#programs">Programas</a></li>
            <li class="nav-item"><a class="nav-link" href="#resources">Recursos</a></li>
            <li class="nav-item"><a class="nav-link" href="#testimonials">Testimonios</a></li>
            <li class="nav-item"><a class="nav-link btn btn-success ms-3" href="#contact">Cotización</a></li>
          </ul>
        </div>
      </div>
    </nav>

    <!-- Hero Section -->
    <section id="hero" class="hero-section d-flex align-items-center">
      <div class="overlay"></div>
      <div class="hero-content text-center text-white px-4">
        <h1 class="hero-title mb-3">Mindfulness para el control del estrés</h1>
        <p class="hero-subtitle mb-4">
          Empodera a tus estudiantes y colaboradores con prácticas de atención plena.
        </p>
        <button class="btn btn-light btn-lg me-3" @click="scrollTo('benefits')">Ver Beneficios</button>
        <button class="btn btn-success btn-lg" @click="scrollTo('contact')">Solicitar Cotización</button>
      </div>
      <!--
      <img src="@/assets/3d-meditation.png" alt="3D Meditación" class="hero-3d-model" />
      -->
    </section>

    <!-- Beneficios -->
    <section id="benefits" class="benefits-section py-5">
      <div class="container">
        <h2 class="section-title text-center mb-5">Beneficios de Mindfulness</h2>
        <div class="row g-4">
          <div
            class="col-md-4"
            v-for="(b, i) in benefits"
            :key="i"
          >
            <div class="benefit-card h-100 p-4 text-center shadow-sm">
              <!--
              <img :src="require(`@/assets/icons/${b.icon}`)" class="icon-3d mb-3" />
              <h5>{{ b.title }}</h5>
              <p class="mb-0">{{ b.text }}</p>
              -->
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Programas -->
    <section id="programs" class="programs-section py-5 bg-light">
      <div class="container">
        <h2 class="section-title text-center mb-5">Nuestros Programas</h2>
        <div class="row g-4">
          <div
            class="col-md-4"
            v-for="(p, i) in programs"
            :key="i"
          >
            <div class="program-card shadow-sm p-4 text-center">
              <!--
              <img :src="require(`@/assets/models/${p.img}`)" class="program-3d mb-3" />
              -->
              <h5>{{ p.title }}</h5>
              <p>{{ p.desc }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Recursos + Testimonios -->
    <section id="resources" class="resources-testimonials py-5">
      <div class="container">
        <div class="row g-4">
          <div class="col-md-6">
            <div class="resource-card p-4 shadow-sm">
              <h4>Recursos Descargables</h4>
              <p>Guías, audios y PDFs para implementar mindfulness.</p>
              <button class="btn btn-outline-success">Descargar</button>
            </div>
          </div>
          <div class="col-md-6">
            <h4 class="mb-3">Testimonios</h4>
            <div v-for="(t, i) in testimonials" :key="i" class="testimonial mb-4 p-3 shadow-sm">
              <blockquote class="mb-2">“{{ t.text }}”</blockquote>
              <footer class="text-end">— {{ t.author }}</footer>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Contacto / Footer -->
    <section id="contact" class="contact-section py-5 bg-success text-white">
      <div class="container">
        <h2 class="section-title text-center mb-4">Solicita tu Cotización</h2>
        <form class="row g-3 justify-content-center">
          <div class="col-md-4">
            <input type="text" class="form-control" placeholder="Nombre / Empresa" />
          </div>
          <div class="col-md-4">
            <input type="email" class="form-control" placeholder="Correo electrónico" />
          </div>
          <div class="col-md-4">
            <button type="submit" class="btn btn-light w-100">Enviar</button>
          </div>
        </form>
      </div>
    </section>

    <Footer />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import Footer from '@/components/Footer.vue'

const benefits = [
  { icon: 'brain-3d.png', title: 'Mejora Concentración', text: 'Incrementa la atención y foco.' },
  { icon: 'heart-3d.png', title: 'Reduce Estrés', text: 'Alivia la ansiedad y tensión.' },
  { icon: 'chart-3d.png', title: 'Aumenta Rendimiento', text: 'Incrementa productividad y compromiso.' },
]

const programs = [
  { img: 'session-3d.png', title: 'Sesiones Guiadas', desc: 'Prácticas estructuradas en aula.' },
  { img: 'workshop-3d.png', title: 'Talleres', desc: 'Workshops personalizados.' },
  { img: 'app-3d.png', title: 'App Móvil', desc: 'Soporte diario desde tu teléfono.' },
]

const testimonials = [
  { text: 'Los estudiantes participan mejor y con más calma.', author: 'Profa. López' },
  { text: 'Ambiente de clase tranquilo tras 1 semana.', author: 'Prof. Martínez' },
  { text: 'La concentración mejoró notablemente.', author: 'Escuela ABC' },
]

const scrollTo = id => {
  const el = document.getElementById(id)
  if (el) el.scrollIntoView({ behavior: 'smooth' })
}
</script>

<style src="@/assets/css/Index.css" scoped></style>
