<template>
  <footer class="footer-section">
    <div class="footer-glow"></div>

    <div class="container">
      <div class="row gy-4 align-items-start">
        <!-- Marca y descripción -->
        <div class="col-12 col-lg-4 footer-brand">
          <h3 class="brand-title">Mindfulness Classroom</h3>
          <p class="brand-copy">
            Plataforma web para el seguimiento emocional, técnicas de mindfulness
            y actividades en el aula, con herramientas para docentes y
            administradores.
          </p>

          <RouterLink
            :to="{ name: 'LoginPage' }"
            class="footer-cta"
          >
            Entrar a la plataforma
          </RouterLink>

          <p class="brand-year">
            © {{ currentYear }} · Todos los derechos reservados.
          </p>
        </div>

        <!-- Navegación (rutas públicas) -->
        <div class="col-6 col-md-4 col-lg-2 footer-links">
          <h5 class="links-title">Navegación</h5>
          <ul>
            <li>
              <RouterLink :to="{ name: 'IndexPage' }">
                Inicio
              </RouterLink>
            </li>
            <li>
              <RouterLink :to="{ name: 'SobreNosotrosPage' }">
                Sobre nosotros
              </RouterLink>
            </li>
            <li>
              <RouterLink :to="{ name: 'ContactoPage' }">
                Contacto
              </RouterLink>
            </li>
            <li>
              <RouterLink :to="{ name: 'LoginPage' }">
                Iniciar sesión
              </RouterLink>
            </li>
          </ul>
        </div>

        <!-- Enlaces a secciones del landing -->
        <div class="col-6 col-md-4 col-lg-3 footer-links">
          <h5 class="links-title">Explora</h5>
          <ul>
            <li><a href="#hero">Portada</a></li>
            <li><a href="#about">¿Qué es el mindfulness?</a></li>
            <li><a href="#benefits">Beneficios en el aula</a></li>
            <li><a href="#programs">Programas y recursos</a></li>
          </ul>
        </div>

        <!-- Redes sociales -->
        <div class="col-12 col-md-4 col-lg-3 footer-social">
          <h5 class="social-title">Conecta</h5>
          <p class="social-copy">
            Comparte la plataforma con tu comunidad educativa y mantente al día.
          </p>

          <div class="social-icons">
            <a href="#" class="social-icon" aria-label="Facebook">
              <i class="fab fa-facebook-f"></i>
            </a>
            <a href="#" class="social-icon" aria-label="Twitter / X">
              <i class="fab fa-twitter"></i>
            </a>
            <a href="#" class="social-icon" aria-label="Instagram">
              <i class="fab fa-instagram"></i>
            </a>
          </div>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup>
const currentYear = new Date().getFullYear()
</script>

<style src="@/assets/css/Footer.css" scoped></style>
