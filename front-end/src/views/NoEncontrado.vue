<template>
  <section class="error-container">
    <!-- Fondo full desde /public/img/404.png usando style inline -->
    <div
      class="error-bg"
      style="
        background-image: url('/img/404.png');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        background-attachment: fixed;
      "
    ></div>

    <!-- Capa de oscurecimiento -->
    <div class="error-overlay"></div>

    <!-- Orbes de color -->
    <div class="error-orb orb-1"></div>
    <div class="error-orb orb-2"></div>

    <!-- Contenido -->
    <div class="container error-inner min-vh-100 d-flex align-items-center justify-content-center">
      <div class="error-card text-center text-light">
        <!-- Icono -->
        <div class="error-icon-wrap mx-auto mb-3">
          <i class="bi bi-peace error-icon"></i>
        </div>

        <!-- Etiqueta y título -->
        <p class="error-label">ERROR 404</p>
        <h1 class="error-title mb-2">Página no encontrada</h1>

        <!-- Subtítulo / lema -->
        <p class="error-subtitle mb-4">
          La ruta que intentas visitar se salió del camino consciente. 🌿
        </p>

        <!-- Mensaje principal -->
        <div class="error-message-box mb-4">
          <p class="mb-0">
            Respira profundo: la página que buscas sigue en su viaje interior.
            Regresa a un punto seguro y continúa tu práctica en
            <strong>Mindfulness</strong>.
          </p>
        </div>

        <!-- Acciones -->
        <div class="error-actions d-flex flex-column flex-sm-row justify-content-center gap-3">
          <router-link
            to="/"
            class="btn btn-error-secondary fix-text"
          >
            <i class="bi bi-house-door me-2"></i>
            Ir al inicio
          </router-link>

          <router-link
            to="/login"
            class="btn btn-error-secondary fix-text"
          >
            <i class="bi bi-box-arrow-in-right me-2"></i>
            Ir al inicio de sesión
          </router-link>
        </div>

        <!-- Hint -->
        <p class="error-hint mt-4 mb-0">
          Tip: Regresa al menú principal y continúa tu viaje de mindfulness. 🌟
        </p>
      </div>
    </div>
  </section>
</template>

<style src="@/assets/css/Error404.css"></style>
