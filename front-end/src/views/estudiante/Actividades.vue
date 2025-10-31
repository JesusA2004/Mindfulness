<!-- src/views/alumno/Actividades.vue -->
<template>
  <main class="container-lg py-4 student-activities">

    <!-- ===== Hero ancho (web) – círculo a la IZQUIERDA, texto a la DERECHA ===== -->
    <section class="card border-0 shadow-sm hero-card animate__animated animate__fadeIn">
      <div class="hero-inner p-4 p-lg-5">
        <div class="row g-4 align-items-center">
          <!-- Círculo “respirando” -->
          <div class="col-lg-5 order-1 order-lg-1">
            <div class="breathe-wrap mx-auto">
              <div class="breathe-core">
                <div class="breathe-ring ring-1"></div>
                <div class="breathe-ring ring-2"></div>
                <div class="breathe-ring ring-3"></div>
              </div>
            </div>

            <!-- Bienvenida destacada, separada del círculo -->
            <div class="welcome-panel animate__animated animate__fadeInUp">
              <div class="welcome-icon">
                <i class="bi bi-stars"></i>
              </div>
              <div>
                <h6 class="m-0 fw-semibold">Bienvenida/o a tu espacio</h6>
                <p class="text-muted small mb-0">
                  Respira profundo, lee la técnica y sigue los recursos que te compartimos.
                </p>
              </div>
            </div>
          </div>

          <!-- Título + progreso + cohorte -->
          <div class="col-lg-7 order-2 order-lg-2">
            <h1 class="display-6 fw-bold mb-2 title">
              <span class="title-deco"></span>
              Actividades asignadas
            </h1>
            <p class="lead text-muted mb-3">
              Explora y completa las prácticas que tu docente te ha asignado.
              Aquí verás únicamente las actividades que <strong>son para ti</strong>.
            </p>

            <div class="d-flex align-items-center gap-3 mb-3">
              <div class="flex-grow-1">
                <div class="d-flex justify-content-between small text-muted mb-1">
                  <span><i class="bi bi-graph-up-arrow me-1"></i>Tu progreso</span>
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
        </div>
      </div>
    </section>

    <!-- ===== Lista de actividades (solo las del alumno) ===== -->
    <section class="mt-4">
      <div v-if="registrosAlumno.length === 0"
           class="card border-0 shadow-sm empty-state text-center py-5 animate__animated animate__fadeInUp">
        <img alt="" class="mb-3" width="56" height="56" src="https://img.icons8.com/ios-glyphs/60/opened-folder.png"/>
        <h5 class="mb-1">Aún no tienes actividades</h5>
        <p class="text-muted mb-0">Cuando tu docente te asigne nuevas prácticas, aparecerán aquí.</p>
      </div>

      <div v-else class="row g-3 g-lg-4">
        <div v-for="a in registrosAlumno" :key="a._id || a.id" class="col-12 col-md-6 col-xl-4">
          <article class="card activity-card border-0 shadow-sm h-100 animate__animated animate__fadeInUp">
            <!-- Preview (video/imagen/audio) -->
            <div v-if="previewOf(a)" class="preview-wrap">
              <img v-if="previewOf(a)?.type==='image'" class="preview-img" :src="previewOf(a).src" alt="preview" />
              <div v-else-if="previewOf(a)?.type==='video'" class="preview-video">
                <img class="preview-img" :src="previewOf(a).src" alt="video-thumb" />
                <span class="play-badge"><i class="bi bi-play-fill"></i></span>
              </div>
              <div v-else-if="previewOf(a)?.type==='audio'" class="preview-audio">
                <i class="bi bi-music-note-beamed me-2"></i> Audio
              </div>
            </div>

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
              <div class="small text-muted mb-2 meta-line">
                <i class="bi bi-flower3 me-1"></i>
                {{ tecnicaFull(a).nombre }}
              </div>

              <div class="d-flex flex-wrap gap-2 mb-3">
                <span class="chip" v-if="tecnicaFull(a).categoria">
                  <i class="bi bi-tag me-1"></i>
                  Categoría: <strong>{{ tecnicaFull(a).categoria }}</strong></span>
                <span class="chip" v-if="tecnicaFull(a).dificultad">
                  <i class="bi bi-bar-chart me-1"></i>
                  Dificultad: <strong>{{ tecnicaFull(a).dificultad }}</strong></span>
                <span class="chip" v-if="tecnicaFull(a).duracion">
                  <i class="bi bi-clock me-1"></i>
                  Duración: <strong>{{ tecnicaFull(a).duracion }}</strong></span>
              </div>

              <!-- Descripción con etiqueta -->
              <div v-if="a.descripcion" class="mb-3 text-muted">
                <div class="desc-label mb-1">
                  <i class="bi bi-info-circle me-1"></i> Descripción
                </div>
                <p class="mb-0">{{ a.descripcion }}</p>
              </div>

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
                  <i class="bi bi-calendar-week me-1"></i>
                  Límite: <strong>{{ fmt(a.fechaMaxima) }}</strong>
                </span>

                <div class="d-flex gap-2">
                  <button class="btn btn-sm btn-outline-primary rounded-pill px-3"
                          @click="verTecnica(a)">
                    <i class="bi bi-list-ul me-1"></i> Leer técnica
                  </button>
                  <button class="btn btn-sm btn-primary rounded-pill px-3 btn-grad"
                          @click="iniciarTecnica(a)">
                    <i class="bi bi-stopwatch me-1"></i> Iniciar técnica
                  </button>
                </div>
              </div>
            </div>
          </article>
        </div>
      </div>

      <!-- Paginación (modo servidor) -->
      <div v-if="!_clientPaginate && registrosAlumno.length"
           class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
        <div class="small text-muted"><i class="bi bi-menu-button-wide me-1"></i>Página: {{ paginaActual }} / {{ totalPaginas || 1 }}</div>
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
 * Vista Alumno con mejoras visuales y “Iniciar técnica” (cronómetro por duración)
 */
import controller from "@/assets/js/actividades.controller";
import { getCurrentUser, fetchTecnicas, fetchActividadesAsignadas } from "@/composables/actividades";
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

    // Solo actividades PARA el alumno
    registrosAlumno() {
      const id = String(this.myId || "");
      const norm = (p) => Array.isArray(p) ? p
        : (typeof p === "string" ? (()=>{ try{return JSON.parse(p)}catch{return []} })() : []);
      return (this.registros || []).filter(a => norm(a?.participantes).some(p => String(p.user_id) === id));
    },
    // Progreso (solo del alumno)
    totalAsignadas() {
      return this.registrosAlumno.length;
    },
    completadas() {
      const id = String(this.myId || "");
      return this.registrosAlumno.filter(a => {
        const part = Array.isArray(a?.participantes)
          ? a.participantes
          : (typeof a?.participantes === "string" ? (()=>{ try{return JSON.parse(a.participantes)}catch{return []} })() : []);
        return part.some(p => String(p.user_id) === id && String(p.estado).toLowerCase() === "completado");
      }).length;
    },
    progressPct() {
      const t = this.totalAsignadas || 1;
      return Math.min(100, Math.round((this.completadas / t) * 100));
    },
  },

  methods: {
    async bootstrap() {
      this.usuario = await getCurrentUser() || this.usuario || null;
      await this.ensurePersonaOnUser?.();
      this.tecnicas = await fetchTecnicas() || [];
      await this.cargarSoloAsignadas();
    },

    async cargarSoloAsignadas(extra = {}) {
      const data = await fetchActividadesAsignadas({ perPage: 12, ...extra });
      this._clientPaginate = false;
      this._clientAll = [];
      this.registros = Array.isArray(data?.registros) ? data.registros : [];
      this.enlaces = data?.enlaces || { anterior: null, siguiente: null };

      const pageFrom = (url) => {
        if (!url) return null;
        try { const u = new URL(url, window.location.origin); return parseInt(u.searchParams.get("page") || "1", 10); }
        catch { return null; }
      };
      const prev = pageFrom(this.enlaces.anterior);
      this.paginaActual = prev ? prev + 1 : (extra.page || 1);
      const last = pageFrom(data?.enlaces?.ultimo);
      this.totalPaginas = last || (this.enlaces.siguiente ? this.paginaActual + 1 : this.paginaActual);
    },

    // Estado del alumno actual en la actividad
    estado(a) {
      const myKey = String(this.myId || "");
      const part = Array.isArray(a?.participantes) ? a.participantes
        : (typeof a?.participantes === "string" ? (()=>{ try{return JSON.parse(a.participantes)}catch{return []} })() : []);
      const row = part.find(p => String(p.user_id) === myKey);
      return row?.estado || "Pendiente";
    },

    // Técnica (con fallback)
    tecnicaFull(a) {
      const id = String(a?.tecnica_id || "");
      const fromList = (this.tecnicas || []).find(t => String(t._id || t.id) === id) || {};
      const fromPayload = a?.tecnica || {};
      return {
        nombre: fromPayload.nombre || fromList.nombre || "Técnica",
        categoria: fromPayload.categoria || fromList.categoria || null,
        dificultad: fromPayload.dificultad ?? fromList.dificultad ?? null,
        duracion: fromPayload.duracion ?? fromList.duracion ?? null,
        recursos: Array.isArray(fromPayload.recursos) ? fromPayload.recursos
          : (Array.isArray(fromList.recursos) ? fromList.recursos : []),
      };
    },

    tecRecursos(a) {
      return this.tecnicaFull(a).recursos || [];
    },

    recursoIcon(r) {
      const tipo = String(r?.tipo || "").toLowerCase();
      if (tipo.includes("video")) return "https://img.icons8.com/ios-glyphs/30/circled-play.png";
      if (tipo.includes("audio")) return "https://img.icons8.com/ios-glyphs/30/musical-notes.png";
      if (tipo.includes("imagen") || tipo.includes("image")) return "https://img.icons8.com/ios-glyphs/30/image.png";
      return "https://img.icons8.com/ios-glyphs/30/link.png";
    },

    // ========= Previews pequeños en las tarjetas =========
    youtubeIdFromUrl(url = "") {
      try {
        const u = new URL(url);
        if (u.hostname.includes("youtu.be")) return u.pathname.slice(1);
        if (u.hostname.includes("youtube.com")) return u.searchParams.get("v");
      } catch {}
      return null;
    },
    firstResource(a) {
      const recs = this.tecRecursos(a);
      return Array.isArray(recs) && recs.length ? recs[0] : null;
    },
    previewOf(a) {
      const r = this.firstResource(a);
      if (!r || !r.url) return null;
      const t = String(r.tipo || "").toLowerCase();
      const url = String(r.url);

      // Youtube → miniatura
      const yt = this.youtubeIdFromUrl(url);
      if (yt) return { type: "video", src: `https://i.ytimg.com/vi/${yt}/hqdefault.jpg` };

      // Imagen por extensión
      if (/\.(png|jpg|jpeg|webp|gif)$/i.test(url) || t.includes("imagen") || t.includes("image")) {
        return { type: "image", src: url };
      }

      // Video simple → placeholder
      if (t.includes("video") || /\.(mp4|webm|mov|m4v)$/i.test(url)) {
        return { type: "video", src: "https://i.imgur.com/8wqKJ3G.png" }; // thumb neutra
      }

      // Audio → placeholder
      if (t.includes("audio") || /\.(mp3|wav|ogg)$/i.test(url)) {
        return { type: "audio" };
      }
      return null;
      },

    // ========= Modal “Leer técnica” =========
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
            ${tec.categoria ? `<span class="chip"><i class='bi bi-tag me-1'></i>Categoría: <strong>${this.escape(tec.categoria)}</strong></span>` : ""}
            ${tec.dificultad ? `<span class="chip"><i class='bi bi-bar-chart me-1'></i>Dificultad: <strong>${this.escape(tec.dificultad)}</strong></span>` : ""}
            ${tec.duracion ? `<span class="chip"><i class='bi bi-clock me-1'></i>Duración: <strong>${this.escape(tec.duracion)}</strong></span>` : ""}
          </div>
          ${a.descripcion ? `<div class="mb-2 text-muted"><strong><i class='bi bi-info-circle me-1'></i>Descripción:</strong> ${this.escape(a.descripcion)}</div>` : ""}
          <div class="rec-grid">${recs}</div>
        </div>
      `;

      await Swal.fire({
        title: `Técnica • ${this.escape(tec.nombre)}`,
        html,
        width: 860,
        confirmButtonText: "Cerrar",
        customClass: { container: "swal2-pt", popup: "swal2-rounded" },
      });
    },

    // ========= Modal “Iniciar técnica” con cuenta regresiva =========
    async iniciarTecnica(a) {
      const tec = this.tecnicaFull(a);
      const totalMin = parseInt(tec.duracion || 0, 10) || 5; // fallback 5 min
      const totalSec = totalMin * 60;
      let remaining = totalSec;
      let timer = null;

      // Embed si es YouTube
      const r = this.firstResource(a);
      const yt = r ? this.youtubeIdFromUrl(r.url || "") : null;
      const media = yt
        ? `<div class="yt-embed">
             <iframe width="100%" height="315"
               src="https://www.youtube.com/embed/${yt}?rel=0"
               title="YouTube video" frameborder="0"
               allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
               allowfullscreen></iframe>
           </div>`
        : "";

      const html = `
        <div class="text-start">
          <div class="d-flex flex-wrap gap-2 mb-3">
            ${tec.categoria ? `<span class="chip"><i class='bi bi-tag me-1'></i>${this.escape(tec.categoria)}</span>` : ""}
            <span class="chip"><i class="bi bi-clock me-1"></i>${totalMin} min</span>
          </div>

          ${media}

          <div class="timer-wrap mt-3">
            <div class="timer-face">
              <span id="tmr">--:--</span>
            </div>
            <div class="progress tmr-progress">
              <div id="tmrBar" class="progress-bar" style="width: 0%"></div>
            </div>
          </div>

          <div class="small text-muted mt-2">
            <i class="bi bi-bell me-1"></i> Te avisaré cuando termine el tiempo. Puedes cerrar el video y seguir los recursos.
          </div>
        </div>
      `;

      const pad = (n)=>String(n).padStart(2,"0");
      const render = () => {
        const m = Math.floor(remaining/60), s = remaining%60;
        const pct = Math.min(100, Math.round(((totalSec-remaining)/totalSec)*100));
        const el = document.getElementById("tmr");
        const bar = document.getElementById("tmrBar");
        if (el) el.textContent = `${pad(m)}:${pad(s)}`;
        if (bar) bar.style.width = pct + "%";
      };

      await Swal.fire({
        title: `Iniciando • ${this.escape(tec.nombre)}`,
        html,
        width: 900,
        showCancelButton: true,
        cancelButtonText: "Cancelar",
        confirmButtonText: "Finalizar",
        didOpen: () => {
          remaining = totalSec;
          render();
          timer = setInterval(() => {
            remaining = Math.max(0, remaining - 1);
            render();
            if (remaining === 0) {
              clearInterval(timer);
              Swal.clickConfirm();
            }
          }, 1000);
        },
        willClose: () => {
          if (timer) clearInterval(timer);
        },
        customClass: { container: "swal2-pt", popup: "swal2-rounded" },
      });
    },

    // Utilidades
    fmt(d) { return d || "—"; },
    escape(s) {
      if (s == null) return "";
      return String(s).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
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
.hero-card{ border-radius: 18px; background: #fff; overflow: hidden; }
.title{ color: var(--ink); position: relative; }
.title-deco{
  display:inline-block; width:10px; height:10px; border-radius:50%;
  background: radial-gradient(circle at 30% 30%, var(--brand), var(--brand-2));
  margin-right:.5rem; transform: translateY(-4px);
}
.hero-progress{ height: 8px; border-radius: 999px; background:#eef1f7; overflow: hidden; }
.hero-progress .progress-bar{
  background: linear-gradient(90deg, var(--brand), var(--brand-2));
  border-radius:999px; transition: width .45s ease;
}

/* Círculo “breathing” */
.breathe-wrap{ position: relative; width: 320px; max-width: 100%; aspect-ratio: 1/1; display:grid; place-items:center; }
.breathe-core{
  position: relative; width: 68%; aspect-ratio:1/1; border-radius: 50%;
  background: radial-gradient(closest-side, rgba(123,92,255,.18), transparent 70%);
  animation: breathe 4.8s ease-in-out infinite;
}
.breathe-ring{ position:absolute; inset:-24% -24% -24% -24%; border-radius:50%; border:1px solid rgba(123,92,255,.18); }
.breathe-ring.ring-2{ inset:-14% -14% -14% -14%; }
.breathe-ring.ring-3{ inset:-4% -4% -4% -4%; }
@keyframes breathe { 0%,100% { transform: scale(0.92); filter: drop-shadow(0 0 0 rgba(123,92,255,.0)); } 50% { transform: scale(1.03); filter: drop-shadow(0 10px 24px rgba(123,92,255,.25)); } }

/* Bienvenida destacada separada */
.welcome-panel{
  margin-top: 18px; display:flex; align-items:center; gap:12px;
  padding:12px 14px; border:1px dashed #eae7ff; border-radius: 14px; background: #fafaff;
}
.welcome-icon{
  width:36px; height:36px; border-radius:10px; display:grid; place-items:center;
  background: linear-gradient(180deg, #efe9ff, #f7f4ff);
  color:#6b5cff; box-shadow: 0 6px 18px rgba(123,92,255,.15);
}

/* ===== Tarjetas ===== */
.activity-card{ border-radius: 16px; background:#fff; transition: transform .18s ease, box-shadow .18s ease; overflow: hidden; }
.activity-card:hover{ transform: translateY(-2px); box-shadow: 0 16px 34px rgba(23,32,58,.08); }
.badge.estado{ font-weight:600; }

/* Preview area */
.preview-wrap{ position: relative; background:#f5f7ff; aspect-ratio: 16/9; overflow:hidden; }
.preview-img{ width:100%; height:100%; object-fit: cover; display:block; }
.preview-video .play-badge{
  position:absolute; inset:auto auto 10px 10px; background:#fff; color:#4f46e5;
  border-radius:999px; width:34px; height:34px; display:grid; place-items:center;
  box-shadow:0 8px 20px rgba(0,0,0,.12);
}
.preview-audio{ display:flex; align-items:center; height:100%; padding:0 14px; color:#4b5563; }

/* Información secundaria */
.meta-line{ display:flex; align-items:center; gap:.25rem; }

/* Chips / recursos */
.chip{
  display:inline-flex; align-items:center; gap:.35rem; font-size:.82rem;
  background: var(--chip); color:#3a2e8f; border:1px solid var(--chip-b);
  padding:.25rem .55rem; border-radius:999px;
}
.desc-label{ font-weight:600; color:#2c2f48; }

.rec-preview{ display:flex; align-items:center; flex-wrap:wrap; gap:.4rem; }
.rec-pill{
  display:inline-flex; align-items:center; gap:.35rem; padding:.40rem .65rem;
  border:1px dashed #e6eaff; border-radius: 999px; text-decoration:none; color:#2c2f48; max-width: 100%;
  transition: background-color .2s ease, transform .15s ease, border-color .2s ease;
}
.rec-pill:hover{ background:#f7f7ff; transform: translateY(-1px); border-color:#d7dbff; }
.rec-pill img{ opacity:.85; }
.rec-more{ color:#6b6f86; font-size:.85rem; }

/* Botón gradiente */
.btn-grad{
  background: linear-gradient(90deg, var(--brand), var(--brand-2));
  border: none;
}
.btn-grad:hover{ filter: brightness(1.03); transform: translateY(-1px); }

/* Empty */
.empty-state{ border-radius: 16px; }

/* SweetAlert */
:deep(.swal2-container.swal2-pt){
  padding-top: 5.5rem !important; backdrop-filter: blur(4px);
  background-color: rgba(10,16,28,.28) !important;
}
:deep(.swal2-popup.swal2-rounded){ border-radius: 18px !important; }

/* Recursos en modales */
.rec-grid{ display:grid; grid-template-columns: 1fr; gap:.5rem; }
.rec-item{
  display:flex; align-items:center; gap:.5rem; padding:.5rem .65rem;
  border:1px dashed #e6eaff; border-radius: 10px; text-decoration:none; color:#2c2f48;
  transition: background-color .2s ease, transform .15s ease;
}
.rec-item:hover{ background:#fafaff; transform: translateY(-1px); }

/* Timer */
.timer-wrap{ }
.timer-face{
  display:grid; place-items:center; width:130px; height:130px; margin:auto; margin-bottom:.5rem;
  border-radius:50%; background:radial-gradient(circle at 30% 30%, #f0efff, #ffffff);
  box-shadow: inset 0 0 0 8px #f2f3ff, 0 10px 28px rgba(123,92,255,.15);
}
.timer-face #tmr{ font-weight:800; font-size:2rem; color:#4838ff; letter-spacing:.5px; }
.tmr-progress{ height: 8px; border-radius:999px; background:#eef1f7; overflow:hidden; }
.tmr-progress .progress-bar{
  background: linear-gradient(90deg, var(--brand), var(--brand-2)); transition: width .6s ease;
}

/* Youtube iframe */
.yt-embed{ border-radius: 14px; overflow: hidden; box-shadow: 0 12px 30px rgba(0,0,0,.08); }

/* ===== Responsive ===== */
@media (min-width: 576px){
  .rec-grid{ grid-template-columns: 1fr 1fr; }
}
@media (min-width: 992px){
  .rec-grid{ grid-template-columns: 1fr 1fr 1fr; }
}
</style>
