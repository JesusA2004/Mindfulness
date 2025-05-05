<template>
    <div class="error-container">
      <div class="container min-vh-100 d-flex align-items-center justify-content-center p-3">
        <div class="error-content bg-dark bg-opacity-75 p-5 rounded-3 text-center text-white shadow-lg">
          <!-- Icono animado -->
          <div class="zen-icon mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon-svg mx-auto d-block" viewBox="0 0 16 16" fill="currentColor">
              <path d="M8 16a6 6 0 0 0 6-6 6 6 0 0 1-6 6 6 6 0 0 1-6-6 6 6 0 0 0 6 6"/>
              <path d="M8 0a6 6 0 0 0-6 6 6 6 0 0 1 6-6 6 6 0 0 1 6 6 6 6 0 0 0-6-6M0 8a6 6 0 0 0 6 6 6 6 0 0 1-6-6 6 6 0 0 1 6-6 6 6 0 0 0-6 6"/>
              <path d="M16 8a6 6 0 0 0-6-6 6 6 0 0 1 6 6 6 6 0 0 1-6 6 6 6 0 0 0 6-6"/>
            </svg>
          </div>
  
          <!-- Código de error -->
          <h1 class="error-code display-1 fw-bold mb-3">404</h1>
  
          <!-- Mensaje destacado -->
          <h2 class="h3 fw-semibold mb-4 animated-text text-warning">Paz interior no encontrada</h2>
  
          <!-- Descripción resaltada -->
          <div class="message-highlight p-4 mb-5 rounded shadow-sm bg-black bg-opacity-50">
            <p class="lead mb-0 text-white">
              La página que buscas ha entrado en <strong>meditación profunda</strong>.<br />
              <strong>Respira hondo</strong>, libera tu mente y regresa al camino <em>consciente</em>.
            </p>
          </div>
  
          <!-- Botón de acción -->
          <router-link to="/" class="btn btn-light btn-lg px-5 rounded-pill shadow-btn mt-3 text-dark fw-semibold">
            <i class="bi bi-house-door me-2"></i>Volver al inicio
          </router-link>
        </div>
      </div>
    </div>
  </template>
  
  <style src="@/assets/css/Error404.css" scoped></style>
