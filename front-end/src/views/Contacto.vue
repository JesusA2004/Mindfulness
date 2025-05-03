<template>
    <section class="contacto-section py-5">
      <div class="container">
        <h2 class="text-center mb-4">Contáctanos</h2>
        <div class="row justify-content-center">
          <div class="col-lg-8">
            <form @submit.prevent="onSubmit" class="p-4 bg-light rounded shadow-sm">
              <div class="mb-3">
                <label for="name" class="form-label">Nombre completo</label>
                <input
                  v-model="form.name"
                  type="text"
                  id="name"
                  class="form-control"
                  placeholder="Tu nombre"
                  required
                />
              </div>
              <div class="mb-3">
                <label for="email" class="form-label">Correo electrónico</label>
                <input
                  v-model="form.email"
                  type="email"
                  id="email"
                  class="form-control"
                  placeholder="usuario@ejemplo.com"
                  required
                />
              </div>
              <div class="mb-3">
                <label for="subject" class="form-label">Asunto</label>
                <input
                  v-model="form.subject"
                  type="text"
                  id="subject"
                  class="form-control"
                  placeholder="¿En qué podemos ayudarte?"
                  required
                />
              </div>
              <div class="mb-3">
                <label for="message" class="form-label">Mensaje</label>
                <textarea
                  v-model="form.message"
                  id="message"
                  rows="5"
                  class="form-control"
                  placeholder="Escribe tu mensaje aquí..."
                  required
                ></textarea>
              </div>
              <button type="submit" class="btn btn-success w-100 hover-btn">
                Enviar Mensaje
              </button>
              <p v-if="success" class="alert alert-success mt-3 mb-0">
                ¡Tu mensaje ha sido enviado correctamente!
              </p>
            </form>
          </div>
        </div>
      </div>
    </section>
  </template>
  
  <script setup>
  import { reactive, ref } from 'vue'
  
  const form = reactive({ name: '', email: '', subject: '', message: '' })
  const success = ref(false)
  
  function onSubmit() {
    // Lógica real de envío aquí (API)
    success.value = true
    setTimeout(() => (success.value = false), 5000)
  }
  </script>
  
  <style scoped>
  .contacto-section {
    background-color: #fff;
  }
  
  .contacto-section h2 {
    font-weight: 600;
    transition: color 0.3s ease;
  }
  .contacto-section h2:hover {
    color: #28a745;
  }
  
  .hover-btn {
    transition: background-color 0.3s ease, transform 0.3s ease;
  }
  .hover-btn:hover {
    background-color: #218838;
    transform: translateY(-2px);
  }
  
  .form-control:focus {
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
  }
  </style>
  