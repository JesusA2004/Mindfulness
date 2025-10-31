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

            <!-- ====== Media estilo “hero card” con overlay ====== -->
            <div class="media-hero position-relative">
              <img
                class="media-img"
                :src="currentPreviewSrc(a)"
                :alt="`preview-${a.nombre}`"
                @error="handleImgError(a)"
              />

              <!-- Overlay con info compacta + CTA -->
              <div class="media-overlay">
                <div class="media-titles">
                  <div class="media-sub text-truncate">
                    {{ tecnicaFull(a).nombre || 'Técnica' }}
                  </div>
                  <div class="media-title text-truncate">
                    {{ a.nombre }}
                  </div>
                </div>
                <div class="media-ctas">
                  <button class="btn btn-sm btn-ghost" @click.stop="verTecnica(a)">
                    Leer técnica
                  </button>
                  <button class="btn btn-sm btn-ghost-strong" @click.stop="iniciarTecnica(a)">
                    Iniciar
                  </button>
                </div>
              </div>

              <!-- Dots del carrusel (solo si hay >1 y NO hay fallback) -->
              <div v-if="!isFallback(a) && previewsOf(a).length > 1" class="dots">
                <button
                  v-for="(p,idx) in previewsOf(a)" :key="idx"
                  :class="['dot', sliderIndex(a)===idx ? 'active' : '']"
                  @click.stop="setSliderIndex(a, idx)"
                  aria-label="Cambiar recurso"
                ></button>
              </div>
            </div>

            <!-- ====== Meta compacta debajo del media ====== -->
            <div class="card-body d-flex flex-column">
              <div class="d-flex align-items-start justify-content-between">
                <span class="badge estado"
                      :class="{
                        'bg-success-subtle text-success border': estado(a)==='Completado',
                        'bg-secondary-subtle text-secondary border': estado(a)==='Omitido',
                        'bg-warning-subtle text-warning border': estado(a)==='Pendiente'
                      }">
                  {{ estado(a) }}
                </span>

                <div class="small text-muted">
                  <i class="bi bi-clock me-1"></i>
                  {{ tecnicaFull(a).duracion ? (tecnicaFull(a).duracion + ' min') : '—' }}
                </div>
              </div>

              <div class="small text-muted mt-2">
                <i class="bi bi-calendar-week me-1"></i>
                Límite: <strong>{{ fmt(a.fechaMaxima) }}</strong>
              </div>

              <!-- Chips -->
              <div class="d-flex flex-wrap gap-2 mt-3">
                <span class="chip" v-if="tecnicaFull(a).categoria">
                  <i class="bi bi-tag me-1"></i>
                  {{ tecnicaFull(a).categoria }}
                </span>
                <span class="chip" v-if="tecnicaFull(a).dificultad">
                  <i class="bi bi-bar-chart me-1"></i>
                  {{ tecnicaFull(a).dificultad }}
                </span>
              </div>

              <!-- Descripción (compacta) -->
              <p v-if="a.descripcion" class="text-muted mt-3 mb-0 small two-lines">
                {{ a.descripcion }}
              </p>
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
 * - Temporizador que deshabilita "Finalizar" hasta llegar a 00:00.
 * - PATCH /api/actividades/{id}/estado marcando estado=Completado SOLO para el alumno en sesión.
 * - Sin cambios de diseño en las cards.
 */
import controller from "@/assets/js/actividades.controller";
import { getCurrentUser, fetchTecnicas, fetchActividadesAsignadas } from "@/composables/actividades";
import Swal from "sweetalert2";
import "sweetalert2/dist/sweetalert2.min.css";
import axios from "axios";

const FALLBACK_IMG = "/img/placeholders/actividad-default.jpg";

/* ====== Base URL + Token (compatibles con Vue CLI) ====== */
const RAW_API_URL =
  process.env.VUE_APP_API_URL ||
  (process.env.VUE_APP_API_BASE
    ? String(process.env.VUE_APP_API_BASE).replace(/\/+$/, "") + "/api"
    : "/api");

const API_BASE = String(RAW_API_URL).replace(/\/+$/, "");

function readToken() {
  const keys = ["token", "auth_token", "jwt", "access_token"];
  for (const k of keys) {
    const v = localStorage.getItem(k);
    if (v) return v.replace(/^"|"$/g, "");
  }
  return null;
}

function authHeaders() {
  const t = readToken();
  return t ? { Authorization: `Bearer ${t}` } : {};
}

export default {
  name: "ActividadesAlumno",
  mixins: [controller],

  data() {
    return {
      sliderState: {},     // { [actividadId]: index }
      fallbackState: {},   // { [actividadId]: true }
    };
  },

  computed: {
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

    registrosAlumno() {
      const ids = this.getCurrentUserIds();
      const norm = (p) =>
        Array.isArray(p)
          ? p
          : typeof p === "string"
          ? (() => { try { return JSON.parse(p); } catch { return []; } })()
          : [];
      return (this.registros || []).filter((a) =>
        norm(a?.participantes).some((p) => ids.includes(String(p.user_id || "").trim()))
      );
    },

    totalAsignadas() {
      return this.registrosAlumno.length;
    },
    completadas() {
      const ids = this.getCurrentUserIds();
      return this.registrosAlumno.filter((a) => {
        const part = Array.isArray(a?.participantes)
          ? a.participantes
          : typeof a?.participantes === "string"
          ? (() => { try { return JSON.parse(a.participantes); } catch { return []; } })()
          : [];
        return part.some(
          (p) => ids.includes(String(p.user_id || "").trim()) &&
                 String(p.estado || "").toLowerCase() === "completado"
        );
      }).length;
    },
    progressPct() {
      const t = this.totalAsignadas || 1;
      return Math.min(100, Math.round((this.completadas / t) * 100));
    },
  },

  methods: {
    async bootstrap() {
      this.usuario = (await getCurrentUser()) || this.usuario || null;
      await this.ensurePersonaOnUser?.();
      this.tecnicas = (await fetchTecnicas()) || [];
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

    /* ========= IDs posibles del usuario actual ========= */
    getCurrentUserIds() {
      const ids = new Set();
      const u = this.usuario || {};
      const add = (v) => { if (v === undefined || v === null) return; const s = String(v).trim().replace(/^"|"$/g, ""); if (s) ids.add(s); };

      // Objeto en memoria
      add(u._id); add(u.id); add(u?.usuario_id);

      // localStorage serializado
      try {
        const raw = localStorage.getItem("user") || localStorage.getItem("usuario");
        if (raw) {
          const o = JSON.parse(raw);
          add(o?._id); add(o?.id); add(o?.usuario_id);
        }
      } catch {}

      // Claves sueltas usuales
      add(localStorage.getItem("user_id"));
      add(localStorage.getItem("uid"));

      return Array.from(ids);
    },

    // ===== Estado mostrado en la tarjeta =====
    estado(a) {
      const ids = this.getCurrentUserIds();
      const toArr = (p) =>
        Array.isArray(p) ? p :
        typeof p === "string" ? (() => { try { return JSON.parse(p); } catch { return []; } })() : [];
      const part = toArr(a?.participantes);
      const row = part.find((p) => ids.includes(String(p.user_id || "").trim()));
      return row?.estado || "Pendiente";
    },

    // ===== Técnica (con fallback) =====
    tecnicaFull(a) {
      const id = String(a?.tecnica_id || "");
      const fromList = (this.tecnicas || []).find((t) => String(t._id || t.id) === id) || {};
      const fromPayload = a?.tecnica || {};
      return {
        nombre: fromPayload.nombre || fromList.nombre || "Técnica",
        categoria: fromPayload.categoria || fromList.categoria || null,
        dificultad: fromPayload.dificultad ?? fromList.dificultad ?? null,
        duracion: fromPayload.duracion ?? fromList.duracion ?? null,
        recursos: Array.isArray(fromPayload.recursos) ? fromPayload.recursos
               : Array.isArray(fromList.recursos) ? fromList.recursos : [],
      };
    },
    tecRecursos(a) { return this.tecnicaFull(a).recursos || []; },

    // ===== Helpers de previews (carrusel) =====
    youtubeIdFromUrl(url = "") {
      try {
        const u = new URL(url);
        if (u.hostname.includes("youtu.be")) return u.pathname.slice(1);
        if (u.hostname.includes("youtube.com")) return u.searchParams.get("v");
      } catch {}
      return null;
    },
    buildPreviewFromRecurso(r) {
      if (!r || !r.url) return null;
      const t = String(r.tipo || "").toLowerCase();
      const url = String(r.url);

      const yt = this.youtubeIdFromUrl(url);
      if (yt) return { type: "video", src: `https://i.ytimg.com/vi/${yt}/hqdefault.jpg` };

      if (/\.(png|jpg|jpeg|webp|gif)$/i.test(url) || t.includes("imagen") || t.includes("image")) {
        return { type: "image", src: url };
      }
      if (t.includes("video") || /\.(mp4|webm|mov|m4v)$/i.test(url)) {
        return { type: "video", src: "https://i.imgur.com/8wqKJ3G.png" };
      }
      if (t.includes("audio") || /\.(mp3|wav|ogg)$/i.test(url)) {
        return { type: "audio", src: FALLBACK_IMG, audio: true };
      }
      return null;
    },
    previewsOf(a) {
      const recs = this.tecRecursos(a);
      if (!Array.isArray(recs) || !recs.length) return [];
      return recs.map(this.buildPreviewFromRecurso).filter(Boolean);
    },
    activityKey(a) { return String(a._id || a.id || JSON.stringify(a)); },
    sliderIndex(a) {
      const k = this.activityKey(a);
      return Number.isInteger(this.sliderState[k]) ? this.sliderState[k] : 0;
    },
    setSliderIndex(a, idx) {
      const k = this.activityKey(a);
      this.$set ? this.$set(this.sliderState, k, idx) : (this.sliderState[k] = idx);
    },
    isFallback(a) {
      const k = this.activityKey(a);
      return !!this.fallbackState[k];
    },
    currentPreviewSrc(a) {
      const list = this.previewsOf(a);
      if (list.length) {
        const p = list[this.sliderIndex(a) % list.length];
        return p?.src || FALLBACK_IMG;
      }
      const k = this.activityKey(a);
      this.fallbackState[k] = true;
      return FALLBACK_IMG;
    },
    handleImgError(a) {
      const k = this.activityKey(a);
      this.fallbackState[k] = true;
      this.$forceUpdate?.();
    },

    recursoIcon(r) {
      const tipo = String(r?.tipo || "").toLowerCase();
      if (tipo.includes("video")) return "https://img.icons8.com/ios-glyphs/30/circled-play.png";
      if (tipo.includes("audio")) return "https://img.icons8.com/ios-glyphs/30/musical-notes.png";
      if (tipo.includes("imagen") || tipo.includes("image")) return "https://img.icons8.com/ios-glyphs/30/image.png";
      return "https://img.icons8.com/ios-glyphs/30/link.png";
    },

    // ========= Modal “Leer técnica”
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
        <div class="sa-body text-start">
          <div class="sa-chips mb-3">
            ${tec.categoria ? `<span class="chip"><i class='bi bi-tag me-1'></i>${this.escape(tec.categoria)}</span>` : ""}
            ${tec.dificultad ? `<span class="chip"><i class='bi bi-bar-chart me-1'></i>${this.escape(tec.dificultad)}</span>` : ""}
            ${tec.duracion ? `<span class="chip"><i class='bi bi-clock me-1'></i>${this.escape(tec.duracion)} min</span>` : ""}
          </div>
          ${a.descripcion ? `<p class="sa-desc text-muted">${this.escape(a.descripcion)}</p>` : ""}
          <div class="rec-grid">${recs}</div>
        </div>
      `;

      await Swal.fire({
        title: `Técnica • ${this.escape(tec.nombre)}`,
        html,
        width: 860,
        confirmButtonText: "Cerrar",
        customClass: { container: "swal2-pt aurora", popup: "swal2-rounded aurora-popup", title: "sa-title" },
      });
    },

    /* ===========================================================
     * MODAL "Iniciar técnica" con temporizador + Finalizar (PATCH)
     * =========================================================== */
    async iniciarTecnica(a) {
      const tec = this.tecnicaFull(a);
      const totalMin = parseInt(tec.duracion || 0, 10) || 5;
      const totalSec = totalMin * 60;
      let remaining = totalSec;
      let timer = null;

      const r = (this.tecRecursos(a) || [])[0];
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
        <div class="sa-body text-start">
          <div class="sa-chips mb-3">
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
            <i class="bi bi-bell me-1"></i>
            El botón <strong>Finalizar</strong> se activará cuando el tiempo llegue a 00:00.
          </div>
        </div>
      `;

      const pad = (n) => String(n).padStart(2, "0");
      const render = () => {
        const m = Math.floor(remaining / 60), s = remaining % 60;
        const pct = Math.min(100, Math.round(((totalSec - remaining) / totalSec) * 100));
        const el = document.getElementById("tmr");
        const bar = document.getElementById("tmrBar");
        if (el) el.textContent = `${pad(m)}:${pad(s)}`;
        if (bar) bar.style.width = pct + "%";
      };

      const self = this;

      await Swal.fire({
        title: `Iniciando • ${this.escape(tec.nombre)}`,
        html,
        width: 900,
        showCancelButton: true,
        cancelButtonText: "Cancelar",
        confirmButtonText: "Finalizar",
        allowOutsideClick: () => remaining === 0,
        didOpen: () => {
          // Deshabilitar botón Confirmar hasta 00:00
          const btn = Swal.getConfirmButton();
          if (btn) { btn.disabled = true; btn.classList.add("disabled"); }

          remaining = totalSec;
          render();
          timer = setInterval(() => {
            remaining = Math.max(0, remaining - 1);
            render();
            if (remaining === 0) {
              const cbtn = Swal.getConfirmButton();
              if (cbtn) { cbtn.disabled = false; cbtn.classList.remove("disabled"); cbtn.focus(); }
            }
          }, 1000);
        },
        willClose: () => { if (timer) clearInterval(timer); },
        customClass: { container: "swal2-pt aurora", popup: "swal2-rounded aurora-popup", title: "sa-title" },

        preConfirm: async () => {
          try {
            if (remaining > 0) throw new Error("Aún no termina el temporizador.");
            await self.finalizarActividad(a); // ← PATCH
          } catch (err) {
            const msg = err?.response?.data?.message || err?.message || "No se pudo marcar como Completado.";
            Swal.showValidationMessage(msg);
            return false;
          }
          return true;
        },
      }).then(async (res) => {
        if (res.isConfirmed) {
          await self.cargarSoloAsignadas({ page: self.paginaActual });
          await Swal.fire({
            icon: "success",
            title: "¡Listo!",
            text: "La actividad se marcó como Completado.",
            confirmButtonText: "OK",
            customClass: { container: "swal2-pt aurora", popup: "swal2-rounded aurora-popup" },
          });
        }
      });
    },

    /* ============================================
     *   PATCH /api/actividades/{id}/estado
     * ============================================ */
    async finalizarActividad(a) {
      const actId = String(a._id || a.id || "").trim();
      if (!actId) throw new Error("Actividad inválida.");

      // Log básico para depurar rápido en consola
      // console.log('PATCH URL', `${API_BASE}/actividades/${actId}/estado`, 'TOKEN?', !!readToken());

      await axios.patch(
        `${API_BASE}/actividades/${actId}/estado`,
        { estado: "Completado" },
        { headers: { ...authHeaders() } }
      );
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

  /* Aurora para SweetAlert */
  --aurora-a:#c7b8ff;
  --aurora-b:#a0d1ff;
  --aurora-c:#ffd2e7;
  --aurora-bg: radial-gradient(120% 120% at 20% 15%, rgba(199,184,255,.55) 0%, rgba(199,184,255,.15) 40%, transparent 60%),
               radial-gradient(120% 120% at 85% 60%, rgba(160,209,255,.55) 0%, rgba(160,209,255,.10) 45%, transparent 65%),
               radial-gradient(100% 100% at 50% 100%, rgba(255,210,231,.50) 0%, rgba(255,210,231,.10) 55%, transparent 70%),
               #f7f8ff;
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

/* ===== Media hero con overlay ===== */
.media-hero{ aspect-ratio: 16/10; background:#f5f7ff; overflow: hidden; border-bottom-left-radius: 0; border-bottom-right-radius: 0; }
.media-img{ width:100%; height:100%; object-fit: cover; display:block; }

.media-overlay{
  position:absolute; inset:auto 12px 12px 12px; display:flex; align-items:center; justify-content:space-between;
  gap:10px; padding:10px 12px; border-radius:14px;
  background: linear-gradient(180deg, rgba(0,0,0,.10), rgba(0,0,0,.45));
  backdrop-filter: blur(2px); color:#fff;
}
.media-titles .media-sub{ font-size:.8rem; opacity:.9; }
.media-titles .media-title{ font-weight:700; line-height:1.1; }

.btn-ghost{
  background: rgba(255,255,255,.35); color:#212121; border:none; border-radius:999px; padding:.35rem .7rem;
  backdrop-filter: blur(3px);
}
.btn-ghost:hover{ background: rgba(255,255,255,.55); }
.btn-ghost-strong{
  background: rgba(255,255,255,.8); color:#111827; border:none; border-radius:999px; padding:.35rem .9rem; font-weight:600;
}
.btn-ghost-strong:hover{ background: #fff; }

/* Dots carrusel */
.dots{
  position:absolute; left:12px; right:12px; bottom:12px; display:flex; justify-content:center; gap:10px; pointer-events:auto;
}
.dot{
  width:10px; height:10px; border-radius:50%; opacity:.45; background:#fff; border:none;
}
.dot.active{ opacity:1; box-shadow: 0 0 0 2px rgba(255,255,255,.35) inset; }

/* Info inferior */
.two-lines{
  display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;
}

/* Chips */
.chip{
  display:inline-flex; align-items:center; gap:.35rem; font-size:.82rem;
  background: var(--chip); color:#3a2e8f; border:1px solid var(--chip-b);
  padding:.25rem .55rem; border-radius:999px;
}

/* Empty */
.empty-state{ border-radius: 16px; }

/* ===== SweetAlert aurora ===== */
:deep(.swal2-container.swal2-pt.aurora){
  padding-top: 5.5rem !important;
  backdrop-filter: blur(4px);
  background: rgba(10,16,28,.28) !important;
}
:deep(.swal2-popup.aurora-popup){
  border-radius: 18px !important;
  background: var(--aurora-bg) !important;
  box-shadow: 0 20px 60px rgba(24, 32, 72, .18) !important;
}
:deep(.swal2-title.sa-title){
  color:#0f172a !important; font-weight:800 !important;
}
.sa-body{ color:#17203a; }
.sa-desc{ margin-bottom:.75rem; }
.rec-grid{ display:grid; grid-template-columns: 1fr; gap:.5rem; }
.rec-item{
  display:flex; align-items:center; gap:.5rem; padding:.55rem .7rem;
  border:1px dashed #e6eaff; border-radius: 10px; text-decoration:none; color:#2c2f48;
  transition: background-color .2s ease, transform .15s ease;
}
.rec-item:hover{ background:#fafaff; transform: translateY(-1px); }

/* Timer */
.timer-face{
  display:grid; place-items:center; width:130px; height:130px; margin:auto; margin-bottom:.5rem;
  border-radius:50%; background:radial-gradient(circle at 30% 30%, #f0efff, #ffffff);
  box-shadow: inset 0 0 0 8px #f2f3ff, 0 10px 28px rgba(123,92,255,.15);
}
.timer-face #tmr{ font-weight:800; font-size:2rem; color:#4838ff; letter-spacing:.5px; }
.tmr-progress{ height: 8px; border-radius:999px; background:#eef1f7; overflow:hidden; }
.tmr-progress .progress-bar{ background: linear-gradient(90deg, var(--brand), var(--brand-2)); transition: width .6s ease; }

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
