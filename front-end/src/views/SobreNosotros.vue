<template>
    <section class="sobre-nosotros-section py-5">
      <div class="container">
        <h2 class="text-center mb-4">Sobre Nosotros</h2>
        <div class="row align-items-center">
          <div class="col-md-6 mb-4 mb-md-0">
            <img
              src="@/assets/images/backgroundMind.png"
              alt="Mindfulness en el aula"
              class="img-fluid rounded shadow-hover"
            />
          </div>
          <div class="col-md-6">
            <p>
              En <strong>Mindfulness Classroom</strong> creemos en el poder de la
              atención plena para transformar la experiencia educativa. Nuestro
              equipo multidisciplinario trabaja para ofrecer:
            </p>
            <ul class="list-unstyled mt-3">
              <li class="mb-2">
                <i class="fas fa-check-circle me-2 text-success"></i>
                Guías paso a paso para implementar prácticas en el aula
              </li>
              <li class="mb-2">
                <i class="fas fa-check-circle me-2 text-success"></i>
                Recursos multimedia explicativos (videos, audios)
              </li>
              <li class="mb-2">
                <i class="fas fa-check-circle me-2 text-success"></i>
                Seguimiento emocional personalizado para cada estudiante
              </li>
              <li class="mb-2">
                <i class="fas fa-check-circle me-2 text-success"></i>
                Comunidad de docentes para compartir experiencias
              </li>
            </ul>
          </div>
        </div>
      </div>
    </section>
  </template>
  
  <script setup>
  // Aquí podrías cargar datos dinámicos o hacer fetch de la API si fuera necesario
  </script>
  
  <style scoped>
  .sobre-nosotros-section {
    background-color: #f8f9fa;
  }
  
  .sobre-nosotros-section h2 {
    font-weight: 600;
    transition: color 0.3s ease;
  }
  .sobre-nosotros-section h2:hover {
    color: #28a745;
  }
  
  .shadow-hover {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }
  .shadow-hover:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.2);
  }
  
  .sobre-nosotros-section ul li i {
    vertical-align: middle;
  }
  </style>