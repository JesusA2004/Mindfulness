<template>
    <div class="index-page">
      <!-- Intro Section -->
      <section class="intro-section text-center py-5 bg-light">
        <div class="container">
          <h1 class="display-4 mb-3">Bienvenido a Mindfulness</h1>
          <p class="lead mb-4">
            Plataforma web para el seguimiento y acompañamiento de técnicas de Mindfulness en el aula.
          </p>
          <div class="row justify-content-center">
            <div class="col-md-4 mb-4" v-for="card in cards" :key="card.title">
              <div class="card h-100 shadow-sm hover-card">
                <div class="card-body">
                  <i :class="[card.icon, 'fa-2x', 'mb-3']"></i>
                  <h5 class="card-title">{{ card.title }}</h5>
                  <p class="card-text">{{ card.text }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
  
      <!-- Sección de video -->
      <section class="video-section py-5">
        <div class="container">
          <h2 class="text-center mb-4">¿Qué es Mindfulness?</h2>
          <div class="row justify-content-center">
            <div class="col-lg-8">
              <div class="ratio ratio-16x9 video-preview shadow-sm">
                <iframe
                  src="https://www.youtube.com/embed/6p_yaNFSYao"
                  title="Introducción a Mindfulness"
                  allowfullscreen
                ></iframe>
              </div>
            </div>
          </div>
        </div>
      </section>
  
      <!-- Footer -->
      <footer class="footer bg-dark text-white py-4">
        <div class="container text-center">
          <p class="mb-1">© {{ new Date().getFullYear() }} Mindfulness Classroom</p>
          <p class="small mb-0">Síguenos en:</p>
          <div>
            <a href="#" class="text-white me-3"><i class="fab fa-facebook-f"></i></a>
            <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
            <a href="#" class="text-white"><i class="fab fa-instagram"></i></a>
          </div>
        </div>
      </footer>
    </div>
  </template>
  
  <script setup>
  const cards = [
    {
      icon: 'fas fa-bullseye',
      title: 'Misión',
      text: 'Brindar a docentes y alumnos herramientas para incorporar Mindfulness en su vida académica y personal',
    },
    {
      icon: 'fas fa-eye',
      title: 'Objetivo',
      text: 'Desarrollar una plataforma intuitiva que facilite el seguimiento de prácticas y resultados emocionales',
    },
    {
      icon: 'fas fa-book-open',
      title: '¿De qué se trata?',
      text: 'Monitoreo de técnicas, recursos, pruebas emocionales y logros de recompensas en el aula',
    },
  ]
  
  function onSubmit() {
    // Lógica de envío de formulario (placeholder)
    alert('¡Gracias por tu mensaje!');
  }
  </script>
  
  <style scoped>
  .index-page {
    display: flex;
    flex-direction: column;
  }
  
  .intro-section {
    background-image: url('~@/assets/images/backgroundMind.png');
    background-size: cover;
    background-position: center;
    transition: background 0.5s ease;
  }
  .intro-section:hover {
    background-position: top;
  }
  
  .hover-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    cursor: default;
  }
  .hover-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
  }
  
  .video-section h2,
  .contact-section h2 {
    font-weight: 600;
    transition: color 0.3s ease;
  }
  .video-section h2:hover,
  .contact-section h2:hover {
    color: #28a745;
  }
  
  .video-preview {
    border-radius: 0.5rem;
    overflow: hidden;
    transition: transform 0.3s ease;
  }
  .video-preview:hover {
    transform: scale(1.02);
  }
  
  .contact-section form {
    transition: box-shadow 0.3s ease;
  }
  .contact-section form:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
  }
  
  .hover-btn {
    transition: background-color 0.3s ease;
  }
  .hover-btn:hover {
    background-color: #218838;
  }
  
  .footer a {
    transition: transform 0.3s ease;
  }
  .footer a:hover {
    transform: scale(1.2);
  }
  </style>
  