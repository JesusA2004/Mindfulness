<template>
  <div class="index-page">
    <!-- Hero Section -->
    <section class="hero-section d-flex align-items-center justify-content-center">
      <div class="overlay"></div>
      <div class="hero-content text-center text-white px-3">
        <h1 class="hero-title mb-3">Bienvenido a Mindfulness</h1>
        <p class="hero-subtitle mb-4">
          Herramientas para el destress en el aula y bienestar emocional.
        </p>
        <div>
          <button class="btn btn-outline-light btn-lg me-2" @click="scrollToSection('cards')">
            Descubre Más
          </button>
          <button class="btn btn-success btn-lg" @click="scrollToSection('benefits')">
            Ver Beneficios
          </button>
        </div>
      </div>
    </section>

    <!-- Cards Section (Resumen Breve) -->
    <section id="cards" class="cards-section py-5">
      <div class="container">
        <h2 class="text-center mb-5 section-title">¿Por qué Mindfulness?</h2>
        <div class="row">
          <div
            class="col-md-4 mb-4"
            v-for="(card, index) in cards"
            :key="card.title"
            :style="{ animationDelay: (index * 0.2) + 's' }"
          >
            <div class="card h-100 shadow-sm hover-card">
              <div class="card-body text-center">
                <div class="card-icon mb-3">
                  <i :class="[card.icon, 'fa-2x']"></i>
                </div>
                <h5 class="card-title">{{ card.title }}</h5>
                <p class="card-text">{{ card.text }}</p>
                <button
                  class="btn btn-sm btn-success mt-3"
                  @click="scrollToSection('benefits')"
                >
                  Conoce Más
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Benefits Section with Collapse (Efecto Deslizo) -->
    <section id="benefits" class="benefits-section bg-light py-5">
      <div class="container">
        <h2 class="text-center mb-5 section-title">Beneficios en el Aula</h2>
        <div class="row">
          <div
            class="col-md-4 mb-4"
            v-for="(benefit, idx) in benefits"
            :key="benefit.title"
          >
            <div class="card h-100 shadow-sm">
              <div class="card-body text-center">
                <div class="benefit-icon mb-3 text-success">
                  <i :class="[benefit.icon, 'fa-2x']"></i>
                </div>
                <h5 class="card-title">{{ benefit.title }}</h5>
                <p class="card-text">{{ benefit.shortText }}</p>
                <button
                  class="btn btn-sm btn-outline-success"
                  type="button"
                  :data-bs-toggle="'collapse'"
                  :data-bs-target="'#collapse' + idx"
                  aria-expanded="false"
                  :aria-controls="'collapse' + idx"
                >
                  Ver Detalles
                </button>
                <div :id="'collapse' + idx" class="collapse mt-3 text-start">
                  <p>{{ benefit.longText }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Carousel de Imágenes -->
    <section class="gallery-section py-5">
      <div class="container">
        <h2 class="text-center mb-4 section-title">Momentos de Mindfulness</h2>
        <div
          id="mindfulnessCarousel"
          class="carousel slide"
          data-bs-ride="carousel"
        >
          <div class="carousel-inner">
            <div
              v-for="(img, i) in carouselImages"
              :key="img"
              :class="['carousel-item', { active: i === 0 }]"
            >
              <img
                :src="require(`@/assets/images/${img}`)"
                class="d-block w-100 rounded"
                alt="Mindfulness Image"
              />
            </div>
          </div>
          <button
            class="carousel-control-prev"
            type="button"
            data-bs-target="#mindfulnessCarousel"
            data-bs-slide="prev"
          >
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Anterior</span>
          </button>
          <button
            class="carousel-control-next"
            type="button"
            data-bs-target="#mindfulnessCarousel"
            data-bs-slide="next"
          >
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Siguiente</span>
          </button>
        </div>
      </div>
    </section>

    <!-- Video Section -->
    <section class="video-section py-5">
      <div class="container text-center">
        <h2 class="section-title mb-4">¿Qué es Mindfulness?</h2>
        <div class="video-wrapper mx-auto mb-4">
          <iframe
            src="https://www.youtube.com/embed/6p_yaNFSYao"
            title="Introducción a Mindfulness"
            frameborder="0"
            allowfullscreen
          ></iframe>
        </div>
        <button class="btn btn-success" @click="openModal">
          Ver Testimonios
        </button>
      </div>
    </section>

    <!-- Modal de Testimonios -->
    <div
      class="modal fade"
      id="testimonyModal"
      tabindex="-1"
      aria-labelledby="testimonyModalLabel"
      aria-hidden="true"
      ref="testimonyModal"
    >
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="testimonyModalLabel">
              Testimonios de Aulas
            </h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Cerrar"
            ></button>
          </div>
          <div class="modal-body">
            <ul class="list-unstyled">
              <li v-for="(tes, i) in testimonies" :key="i" class="mb-3">
                <blockquote class="blockquote">
                  <p class="mb-1">“{{ tes.text }}”</p>
                  <footer class="blockquote-footer">{{ tes.author }}</footer>
                </blockquote>
              </li>
            </ul>
          </div>
          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-secondary"
              data-bs-dismiss="modal"
            >
              Cerrar
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <Footer />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import Footer from '@/components/Footer.vue';

const cards = [
  {
    icon: 'fas fa-bullseye',
    title: 'Misión',
    text: 'Brindar herramientas para el bienestar emocional en el aula.',
  },
  {
    icon: 'fas fa-eye',
    title: 'Visión',
    text: 'Crear espacios de calma y concentración para estudiantes.',
  },
  {
    icon: 'fas fa-book-open',
    title: '¿Qué hacemos?',
    text: 'Guiamos prácticas de respiración y meditación breve.',
  },
];

const benefits = [
  {
    icon: 'fas fa-brain',
    title: 'Mejora de Atención',
    shortText: 'Incrementa la capacidad de concentración.',
    longText:
      'Las prácticas regulares de mindfulness entrenan la mente para mantener la atención en el momento presente, reduciendo distracciones en clase.',
  },
  {
    icon: 'fas fa-heart',
    title: 'Reducción de Estrés',
    shortText: 'Disminuye la ansiedad y tensión.',
    longText:
      'Técnicas sencillas de respiración ayudan a liberar emociones negativas y equilibrar el estado anímico, promoviendo un ambiente de aprendizaje más relajado.',
  },
  {
    icon: 'fas fa-user-friends',
    title: 'Mejor Clima Escolar',
    shortText: 'Fomenta la empatía y convivencia.',
    longText:
      'Al practicar mindfulness en grupo, se impulsa la colaboración y la comprensión mutua entre estudiantes, reduciendo conflictos y mejorando la dinámica de aula.',
  },
];

const carouselImages = [
  'backgroundMind.png',
  'backgroundMind.png',
  'backgroundMind.png',
];

const testimonies = [
  {
    text: '“Desde que implementamos sesiones cortas de respiración, la clase se siente más tranquila y los estudiantes participan mejor.”',
    author: 'Profr. Martínez, Secundaria ABC',
  },
  {
    text: '“Los niños muestran menos ansiedad antes de los exámenes y se enfocan más tiempo.”',
    author: 'Profa. López, Primaria XYZ',
  },
  {
    text: '“Incluir un minuto de silencio al inicio de cada clase cambió el ambiente completamente.”',
    author: 'Profr. Herrera, Preparatoria 123',
  },
];

const scrollToSection = (id) => {
  const el = document.getElementById(id);
  if (el) {
    el.scrollIntoView({ behavior: 'smooth' });
  }
};

const openModal = () => {
  const modalEl = ref(null);
  const bootstrap = window.bootstrap;
  const modalInstance = new bootstrap.Modal(
    document.getElementById('testimonyModal')
  );
  modalInstance.show();
};

onMounted(() => {
  // Opcional: animar elementos al hacer scroll (IntersectionObserver)
  const observerOptions = { threshold: 0.2 };
  const animateOnScroll = (entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('show-card');
      }
    });
  };
  const observer = new IntersectionObserver(animateOnScroll, observerOptions);
  document.querySelectorAll('.hover-card').forEach((el) => {
    observer.observe(el);
  });
});
</script>

<style src="@/assets/css/Index.css" scoped></style>
