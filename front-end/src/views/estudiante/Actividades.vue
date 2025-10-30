<template>
  <main class="container-lg py-4 student-activities">

    <!-- ===== Hero ancho (web) ===== -->
    <section class="card border-0 shadow-sm hero-card animate__animated animate__fadeIn">
      <div class="hero-inner p-4 p-lg-5">
        <div class="row g-4 align-items-center">
          <div class="col-lg-7">
            <h1 class="display-6 fw-bold mb-2 title">
              Actividades asignadas
            </h1>
            <p class="lead text-muted mb-3">
              Explora y completa las prácticas que tu docente te ha asignado.
              Aquí verás únicamente las actividades que <strong>son para ti</strong>.
            </p>

            <div class="d-flex align-items-center gap-3 mb-3">
              <div class="flex-grow-1">
                <div class="d-flex justify-content-between small text-muted mb-1">
                  <span>Tu progreso</span>
                  <span>{{ completadas }} / {{ totalAsignadas }}</span>
                </div>
                <div class="progress hero-progress">
                  <div
                    class="progress-bar"
                    role="progressbar"
                    :style="{ width: progressPct + '%' }"
                    :aria-valuenow="progressPct" aria-valuemin="0" aria-valuemax="100">
                  </div>
                </div>
              </div>
            </div>

            <div class="small text-muted">
              <img class="me-1 align-text-bottom" width="18" height="18" alt=""
                   src="https://img.icons8.com/ios-glyphs/30/graduation-cap.png"/>
              <span class="me-1">Tu cohorte:</span>
              <strong>{{ labelCohorteAlumno }}</strong>
            </div>
          </div>

          <!-- Círculo “respirando” decorativo -->
          <div class="col-lg-5">
            <div class="breathe-wrap mx-auto">
              <div class="breathe-core">
                <div class="breathe-ring ring-1"></div>
                <div class="breathe-ring ring-2"></div>
                <div class="breathe-ring ring-3"></div>
              </div>
              <div class="welcome-msg">
                <h6 class="m-0 fw-semibold">Bienvenida/o a tu espacio</h6>
                <p class="text-muted small mb-0">
                  Respira profundo, lee la técnica y sigue los recursos que te compartimos.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== Lista de actividades (solo las del alumno) ===== -->
    <section class="mt-4">
      <div v-if="registrosAlumno.length === 0" class="card border-0 shadow-sm empty-state text-center py-5 animate__animated animate__fadeInUp">
        <img alt="" class="mb-3" width="56" height="56" src="https://img.icons8.com/ios-glyphs/60/opened-folder.png"/>
        <h5 class="mb-1">Aún no tienes actividades</h5>
        <p class="text-muted mb-0">Cuando tu docente te asigne nuevas prácticas, aparecerán aquí.</p>
      </div>

      <div v-else class="row g-3 g-lg-4">
        <div v-for="a in registrosAlumno" :key="a._id || a.id" class="col-12 col-md-6 col-xl-4">
          <article class="card activity-card border-0 shadow-sm h-100 animate__animated animate__fadeInUp">
            <div class="card-body d-flex flex-column">
              <div class="d-flex align-items-start justify-content-between">
                <h5 class="mb-1 fw-bold text-dark">{{ a.nombre }}</h5>

                <span class="badge estado"
                      :class="{
                        'bg-success-subtle text-success border': estado(a)==='Completado',
                        'bg-secondary-subtle text-secondary border': estado(a)==='Omitido',
                        'bg-warning-subtle text-warning border': estado(a)==='Pendiente'
                      }">
                  {{ estado(a) }}
                </span>
              </div>

              <!-- Técnica / meta -->
              <div class="small text-muted mb-2">
                <img class="me-1" width="16" height="16" alt="tec" src="https://img.icons8.com/ios-glyphs/30/spa-flower.png"/>
                {{ tecnicaFull(a).nombre }}
              </div>

              <div class="d-flex flex-wrap gap-2 mb-2">
                <span class="chip" v-if="tecnicaFull(a).categoria">Categoría: <strong>{{ tecnicaFull(a).categoria }}</strong></span>
                <span class="chip" v-if="tecnicaFull(a).dificultad">Dificultad: <strong>{{ tecnicaFull(a).dificultad }}</strong></span>
                <span class="chip" v-if="tecnicaFull(a).duracion">Duración: <strong>{{ tecnicaFull(a).duracion }}</strong></span>
              </div>

              <!-- Descripción breve -->
              <p v-if="a.descripcion" class="text-muted flex-grow-1 mb-3">
                {{ a.descripcion }}
              </p>

              <!-- Recursos preview (hasta 3) -->
              <div class="rec-preview mb-3" v-if="tecRecursos(a).length">
                <a v-for="(r, idx) in tecRecursos(a).slice(0,3)"
                   :key="idx" class="rec-pill"
                   :href="r.url" target="_blank" rel="noopener"
                   :title="r.titulo || r.descripcion || 'Recurso'">
                  <img :alt="r.tipo" :src="recursoIcon(r)" width="16" height="16"/>
                  <span class="text-truncate">{{ r.titulo || r.descripcion || r.tipo }}</span>
                </a>
                <span v-if="tecRecursos(a).length > 3" class="rec-more">
                  +{{ tecRecursos(a).length - 3 }} más
                </span>
              </div>

              <div class="d-flex align-items-center justify-content-between mt-auto">
                <span class="small text-muted">
                  <img class="me-1" width="16" height="16" alt="cal" src="https://img.icons8.com/ios-glyphs/30/calendar--v1.png"/>
                  Límite: <strong>{{ fmt(a.fechaMaxima) }}</strong>
                </span>

                <button class="btn btn-sm btn-outline-primary rounded-pill px-3" @click="verTecnica(a)">
                  <img class="me-1" width="16" height="16" alt="det" src="https://img.icons8.com/ios-glyphs/30/overview-pages-3.png"/>
                  Leer técnica
                </button>
              </div>
            </div>
          </article>
        </div>
      </div>

      <!-- Paginación (si viene del server; si filtra local no se muestra) -->
      <div v-if="!_clientPaginate && registrosAlumno.length"
           class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
        <div class="small text-muted">Página: {{ paginaActual }} / {{ totalPaginas || 1 }}</div>
        <div class="d-flex align-items-center gap-2">
          <button class="btn btn-sm btn-outline-secondary" :disabled="!enlaces.anterior" @click="go(enlaces.anterior)">
            <i class="bi bi-chevron-left"></i>
          </button>
          <button class="btn btn-sm btn-outline-secondary" :disabled="!enlaces.siguiente" @click="go(enlaces.siguiente)">
            <i class="bi bi-chevron-right"></i>
          </button>
        </div>
      </div>
    </section>
  </main>
</template>

<script>
/**
 * Vista Alumno (solo consulta)
 * - Reutiliza el controlador base como mixin.
 * - Muestra únicamente las actividades donde el participante es el alumno actual.
 * - Amplía la vista con hero web y previews de técnica/recursos.
 */
import controller from "@/assets/js/actividades.controller";
import Swal from "sweetalert2";
import "sweetalert2/dist/sweetalert2.min.css";

export default {
  name: "ActividadesAlumno",
  mixins: [controller],

  computed: {
    // Cohorte(s) del alumno desde usuario (normalizado)
    cohortesVisibles() {
      const norm = (v) => String(v || "").replace(/\s+/g, " ").trim();
      const c = this.usuario?.persona?.cohorte;
      if (Array.isArray(c)) return [...new Set(c.map(norm))].sort();
      if (typeof c === "string" && c) return [norm(c)];
      return [];
    },
    labelCohorteAlumno() {
      return this.cohortesVisibles.length ? this.cohortesVisibles.join(", ") : "—";
    },

    // Solo actividades PARA el alumno (filtrado por user_id === myId)
    registrosAlumno() {
      const id = String(this.myId || "");
      return (this.registros || []).filter(a =>
        Array.isArray(a?.participantes) &&
        a.participantes.some(p => String(p.user_id) === id)
      );
    },

    // Progreso (solo del alumno)
    totalAsignadas() {
      return this.registrosAlumno.length;
    },
    completadas() {
      const id = String(this.myId || "");
      return this.registrosAlumno.filter(a =>
        (a.participantes || []).some(p => String(p.user_id) === id && String(p.estado).toLowerCase() === "completado")
      ).length;
    },
    progressPct() {
      const t = this.totalAsignadas || 1;
      return Math.min(100, Math.round((this.completadas / t) * 100));
    },
  },

  methods: {
    // Sobrescribe bootstrap para fijar filtro de cohorte (opcional) y cargar
    async bootstrap() {
      this.usuario = await this.getCurrentUser?.() || this.usuario || null;

      // Si tiene cohorte, lo dejamos sin tocar filtros del mixin (el alumno no necesita filtrar).
      this.tecnicas = await this.fetchTecnicas?.() || [];
      await this.cargarActividades();
    },

    // Estado del alumno actual en la actividad
    estado(a) {
      const myKey = String(this.myId || "");
      const row = (a?.participantes || []).find((p) => String(p.user_id) === myKey);
      return row?.estado || "Pendiente";
    },

    // Técnica (con fallback: usa lo que regresa el endpoint en a.tecnica)
    tecnicaFull(a) {
      const id = String(a?.tecnica_id || "");
      const fromList = (this.tecnicas || []).find(t => String(t._id || t.id) === id) || {};
      const fromPayload = a?.tecnica || {}; // viene del backend index/show/store/update
      return {
        nombre: fromPayload.nombre || fromList.nombre || "Técnica",
        categoria: fromPayload.categoria || fromList.categoria || null,
        dificultad: fromList.dificultad ?? null,
        duracion: fromList.duracion ?? null,
        recursos: Array.isArray(fromList.recursos) ? fromList.recursos : [],
      };
    },

    tecRecursos(a) {
      return this.tecnicaFull(a).recursos || [];
    },

    recursoIcon(r) {
      const tipo = String(r?.tipo || "").toLowerCase();
      if (tipo.includes("video")) return "https://img.icons8.com/ios-glyphs/30/circled-play.png";
      if (tipo.includes("audio")) return "https://img.icons8.com/ios-glyphs/30/musical-notes.png";
      if (tipo.includes("imagen")) return "https://img.icons8.com/ios-glyphs/30/image.png";
      return "https://img.icons8.com/ios-glyphs/30/link.png";
    },

    // Ver técnica completa (aprovecha mixin.verTecnica si quieres; aquí personalizamos el contenido)
    async verTecnica(a) {
      const tec = this.tecnicaFull(a);
      const recs = tec.recursos.length
        ? tec.recursos.map((r) => `
            <a class="rec-item" href="${r.url}" target="_blank" rel="noopener">
              <img alt="" src="${this.recursoIcon(r)}"/>
              <span>${this.escape(r.titulo || r.descripcion || r.tipo || "Recurso")}</span>
            </a>
          `).join("")
        : `<div class="text-muted">Sin recursos adicionales.</div>`;

      const html = `
        <div class="text-start">
          <div class="d-flex flex-wrap gap-2 mb-2">
            ${tec.categoria ? `<span class="chip">Categoría: <strong>${this.escape(tec.categoria)}</strong></span>` : ""}
            ${tec.dificultad ? `<span class="chip">Dificultad: <strong>${this.escape(tec.dificultad)}</strong></span>` : ""}
            ${tec.duracion ? `<span class="chip">Duración: <strong>${this.escape(tec.duracion)}</strong></span>` : ""}
          </div>
          <div class="mb-3">${this.escape(a.descripcion || "")}</div>
          <div class="rec-grid">${recs}</div>
        </div>
      `;

      await Swal.fire({
        title: `Técnica • ${this.escape(tec.nombre)}`,
        html,
        width: 820,
        confirmButtonText: "Cerrar",
        customClass: { container: "swal2-pt", popup: "swal2-rounded" },
      });
    },
  },
};
</script>

<style scoped>
/* ===== Paleta ===== */
:root{
  --ink:#17203a;
  --muted:#6b7280;
  --stroke:#e5e7eb;
  --soft:#f7f9fc;
  --brand:#6a8dff;
  --brand-2:#7b5cff;
  --chip:#eef2ff;
  --chip-b:#c7d2fe;
}

/* ===== Hero ===== */
.hero-card{ border-radius: 18px; background: #fff; }
.title{ color: var(--ink); }
.hero-progress{ height: 8px; border-radius: 999px; background:#eef1f7; }
.hero-progress .progress-bar{
  background: linear-gradient(90deg, var(--brand), var(--brand-2));
  border-radius:999px; transition: width .45s ease;
}

/* Círculo “breathing” */
.breathe-wrap{
  position: relative; width: 320px; max-width: 100%;
  aspect-ratio: 1/1; display:grid; place-items:center;
}
.breathe-core{
  position: relative; width: 68%; aspect-ratio:1/1;
  border-radius: 50%;
  background: radial-gradient(closest-side, rgba(123,92,255,.18), transparent 70%);
  animation: breathe 4.8s ease-in-out infinite;
}
.breathe-ring{
  position:absolute; inset:-24% -24% -24% -24%;
  border-radius:50%; border:1px solid rgba(123,92,255,.18);
}
.breathe-ring.ring-2{ inset:-14% -14% -14% -14%; }
.breathe-ring.ring-3{ inset:-4% -4% -4% -4%; }
@keyframes breathe {
  0%,100% { transform: scale(0.92); filter: drop-shadow(0 0 0 rgba(123,92,255,.0)); }
  50%     { transform: scale(1.03); filter: drop-shadow(0 10px 24px rgba(123,92,255,.25)); }
}
.welcome-msg{
  position:absolute; left:50%; bottom:-18px; transform: translateX(-50%);
  width: 100%; text-align: center;
}

/* ===== Chips / recursos ===== */
.chip{
  display:inline-flex; align-items:center; gap:.35rem;
  font-size:.82rem; background: var(--chip); color:#3a2e8f;
  border:1px solid var(--chip-b); padding:.25rem .55rem; border-radius:999px;
}

.rec-preview{ display:flex; align-items:center; flex-wrap:wrap; gap:.4rem; }
.rec-pill{
  display:inline-flex; align-items:center; gap:.35rem;
  padding:.35rem .55rem; border:1px dashed #e6eaff; border-radius: 999px;
  text-decoration:none; color:#2c2f48; max-width: 100%;
  transition: background-color .2s ease, transform .15s ease;
}
.rec-pill:hover{ background:#f7f7ff; transform: translateY(-1px); }
.rec-pill img{ opacity:.8; }
.rec-more{ color:#6b6f86; font-size:.85rem; }

/* ===== Tarjetas ===== */
.activity-card{ border-radius: 16px; background:#fff; transition: transform .18s ease, box-shadow .18s ease; }
.activity-card:hover{ transform: translateY(-2px); box-shadow: 0 16px 34px rgba(23,32,58,.08); }
.badge.estado{ font-weight:600; }

/* Empty */
.empty-state{ border-radius: 16px; }

/* SweetAlert */
:deep(.swal2-container.swal2-pt){
  padding-top: 5.5rem !important;
  backdrop-filter: blur(4px);
  background-color: rgba(10,16,28,.28) !important;
}
:deep(.swal2-popup.swal2-rounded){ border-radius: 18px !important; }

/* Recursos en modal */
.rec-grid{ display:grid; grid-template-columns: 1fr; gap:.5rem; }
.rec-item{
  display:flex; align-items:center; gap:.5rem; padding:.5rem .65rem;
  border:1px dashed #e6eaff; border-radius: 10px; text-decoration:none; color:#2c2f48;
  transition: background-color .2s ease, transform .15s ease;
}
.rec-item:hover{ background:#fafaff; transform: translateY(-1px); }

/* ===== Responsive ===== */
@media (min-width: 576px){
  .rec-grid{ grid-template-columns: 1fr 1fr; }
}
@media (min-width: 992px){
  .rec-grid{ grid-template-columns: 1fr 1fr 1fr; }
}
</style>
