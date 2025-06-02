<template>
  <footer class="footer-section">
    <div class="footer-content">
      <!-- Marca y descripción -->
      <div class="footer-brand">
        <h3 class="brand-title">Mindfulness Classroom</h3>
        <p class="brand-copy">© {{ new Date().getFullYear() }} Todos los derechos reservados.</p>
      </div>

      <!-- Links rápidos -->
      <div class="footer-links">
        <h5 class="links-title">Enlaces</h5>
        <ul>
          <li><a href="#cards">Inicio</a></li>
          <li><a href="#video">Video</a></li>
          <li><a href="#">Contacto</a></li>
        </ul>
      </div>

      <!-- Redes sociales -->
      <div class="footer-social">
        <h5 class="social-title">Síguenos</h5>
        <div class="social-icons">
          <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
          <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
          <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup>
// (no se requiere lógica adicional por ahora)
</script>

<style src="@/assets/css/Footer.css" scoped></style>