<!-- src/views/encuestas/CrudPanel.vue -->
<template>
  <main class="panel-wrapper">
    <!-- ======= Header pastel (hero) ======= -->
    <section class="container-fluid hero px-3 px-lg-2 py-5">
      <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
          <div class="hero-inner text-center">
            <h1 class="fw-bolder display-5 display-md-4 mb-3 title-bigger">
              Encuestas disponibles
            </h1>
            <p class="lead mb-0 opacity-85">
              Comparte tu opinión; tus respuestas nos ayudan a mejorar.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- ======= Toolbar: Búsqueda (solo texto) ======= -->
    <div class="container-fluid toolbar px-3 px-lg-2">
      <div class="row g-2 align-items-center">
        <div class="col-12 col-lg-8">
          <div
            class="input-group input-group-lg search-group shadow-sm rounded-pill"
            role="search"
            aria-label="Buscador de encuestas"
          >
            <span class="input-group-text rounded-start-pill">
              <i class="bi bi-search"></i>
            </span>

            <input
              v-model.trim="searchQuery"
              type="search"
              class="form-control search-input"
              placeholder="Buscar encuesta por título o duración…"
              @input="onInstantSearch"
              aria-label="Buscar por título o duración"
            />

            <button
              v-if="searchQuery"
              class="btn btn-link text-secondary px-3 d-none d-md-inline"
              @click="clearSearch"
              aria-label="Limpiar búsqueda"
            >
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          <!-- Botón limpiar móvil -->
          <div class="d-flex d-md-none justify-content-end mt-2" v-if="searchQuery">
            <button
              class="btn btn-sm btn-outline-secondary rounded-pill"
              @click="clearSearch"
              aria-label="Limpiar búsqueda móvil"
            >
              <i class="bi bi-x-lg me-1"></i> Limpiar
            </button>
          </div>
        </div>

        <!-- Col derecha vacía -->
        <div class="col-12 col-lg-4 mt-2 mt-lg-0"></div>
      </div>
    </div>

    <!-- ======= Grid de Cards ======= -->
    <div class="container-fluid px-3 px-lg-2">
      <div class="row g-3 row-cols-1 row-cols-sm-2 row-cols-lg-3">
        <div v-for="item in visibleItems" :key="getId(item)" class="col">
          <div class="card h-100 item-card shadow-sm hover-raise">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <h5 class="card-title mb-0 text-truncate fw-bold" :title="item.titulo">
                  {{ item.titulo }}
                </h5>

                <!-- Badge de estado -->
                <span
                  class="badge rounded-pill"
                  :class="badgeClass(availabilityOf(item).status)"
                  :title="availabilityOf(item).hint"
                >
                  {{ availabilityOf(item).label }}
                </span>
              </div>

              <p class="card-text clamp-3 mb-2" v-if="item.descripcion">{{ item.descripcion }}</p>

              <div class="small text-muted">
                <i class="bi bi-clock-history me-1"></i>
                {{ item.duracion_estimada ? item.duracion_estimada + ' min' : '—' }}
              </div>

              <div class="small text-muted mt-1">
                <i class="bi bi-calendar-event me-1"></i>
                {{ formatDateRangeLocal(item) }}
              </div>

              <div class="small text-muted mt-1">
                <i class="bi bi-list-check me-1"></i>
                {{ (item.cuestionario?.length || 0) }} pregunta(s)
              </div>
            </div>

            <div class="card-footer bg-transparent border-0 pt-0 pb-3 px-3">
              <!-- ÚNICO botón por card -->
              <button
                class="btn btn-primary btn-sm w-100 btn-with-label"
                @click="guardedStartSurvey(item)"
                :disabled="(item.cuestionario?.length || 0) === 0 || availabilityOf(item).status !== 'open'"
                :title="buttonTitle(availabilityOf(item))"
                data-bs-toggle="tooltip"
              >
                <i class="bi bi-pencil-square me-1"></i>
                <span>Contestar encuesta</span>
              </button>

              <div class="small mt-2 text-muted" v-if="(item.cuestionario?.length || 0) === 0">
                Esta encuesta aún no tiene preguntas.
              </div>
            </div>
          </div>
        </div>

        <!-- Vacío -->
        <div v-if="!isLoading && visibleItems.length === 0" class="col-12">
          <div class="alert alert-light border d-flex align-items-center gap-2">
            <i class="bi bi-inbox text-secondary fs-4"></i>
            <div>
              <strong>Sin resultados.</strong>
              No hay encuestas disponibles en este momento.
            </div>
          </div>
        </div>

        <!-- Skeletons -->
        <div v-if="isLoading" class="col" v-for="n in 6" :key="'sk'+n">
          <div class="card h-100 shadow-sm">
            <div class="card-body">
              <div class="placeholder-glow">
                <span class="placeholder col-8"></span>
                <p class="mt-2 mb-0">
                  <span class="placeholder col-12"></span>
                  <span class="placeholder col-11"></span>
                  <span class="placeholder col-9"></span>
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Paginación simple -->
      <div class="d-flex justify-content-center my-4" v-if="!isLoading && hasMore">
        <button class="btn btn-outline-secondary btn-lg" @click="loadMore">
          Cargar más
        </button>
      </div>
    </div>
  </main>
</template>

<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import { useEncuestasCrud } from '@/assets/js/useEncuestasCrud';
import { authHeaders, toast as baseToast } from '@/assets/js/crudUtils';

/**
 * Vista de ENCUESTAS para estudiante:
 * - Búsqueda por texto
 * - Listado + paginación
 * - Único botón: Contestar encuesta (SweetAlert2)
 * - *** Nuevo: disponibilidad por fechaInicio/fechaFin ***
 */
const {
  items, isLoading, hasMore, filteredItems,
  searchQuery, onInstantSearch, clearSearch,
  getId, loadMore
} = useEncuestasCrud();

/* ===========================================================
   Helpers de fecha y disponibilidad
   Considera alias de campos:
   - inicio: fechaInicio | fechaAsignacion | fecha_inicio
   - fin:    fechaFin    | fechaFinalizacion | fecha_fin
=========================================================== */
const tzLabel = 'America/Mexico_City'; // referencia conceptual

function pickStart(item) {
  return item?.fechaInicio ?? item?.fechaAsignacion ?? item?.fecha_inicio ?? null;
}
function pickEnd(item) {
  return item?.fechaFin ?? item?.fechaFinalizacion ?? item?.fecha_fin ?? null;
}

/** Parseo local seguro para 'YYYY-MM-DD' e ISO. */
function parseLocalDate(val, endOfDay = false) {
  if (!val) return null;

  // Si viene como objeto fecha de backend (Date/ISO), Date lo maneja
  if (val instanceof Date) return val;

  const s = String(val).trim();

  // YYYY-MM-DD -> crear en horario local evitando UTC
  const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(s);
  if (m) {
    const y = Number(m[1]), mo = Number(m[2]) - 1, d = Number(m[3]);
    if (endOfDay) return new Date(y, mo, d, 23, 59, 59, 999);
    return new Date(y, mo, d, 0, 0, 0, 0);
  }

  // ISO u otros: dejar que Date lo interprete
  const dt = new Date(s);
  if (Number.isNaN(dt.getTime())) return null;
  return dt;
}

function availabilityOf(item) {
  const now = new Date();
  const start = parseLocalDate(pickStart(item), false);
  const end   = parseLocalDate(pickEnd(item), true);

  if (start && now < start) {
    return {
      status: 'upcoming',
      label: 'Próxima',
      hint: `Disponible desde ${fmtDate(start)}`
    };
  }
  if (end && now > end) {
    return {
      status: 'closed',
      label: 'Finalizada',
      hint: `Cerró el ${fmtDate(end)}`
    };
  }
  return {
    status: 'open',
    label: 'Disponible',
    hint: (start || end) ? formatDateRangeLocal(item) : 'Disponible'
  };
}

function fmtDate(dateObj) {
  try {
    return dateObj.toLocaleDateString('es-MX', { year: 'numeric', month: 'short', day: '2-digit' });
  } catch {
    return dateObj?.toISOString?.().slice(0, 10) || '';
  }
}

function formatDateRangeLocal(item) {
  const start = parseLocalDate(pickStart(item), false);
  const end   = parseLocalDate(pickEnd(item), true);
  if (!start && !end) return 'Sin fechas';

  if (start && end) return `${fmtDate(start)} — ${fmtDate(end)}`;
  if (start)        return `Desde ${fmtDate(start)}`;
  return `Hasta ${fmtDate(end)}`;
}

/** Badge class por estado */
function badgeClass(status) {
  if (status === 'open') return 'bg-status bg-success';
  if (status === 'upcoming') return 'bg-status bg-warning text-dark';
  if (status === 'closed') return 'bg-status bg-secondary';
  return 'bg-secondary';
}

/** Texto del title en el botón */
function buttonTitle(av) {
  if (av.status === 'open') return 'Contestar encuesta';
  if (av.status === 'upcoming') return av.hint || 'Aún no disponible';
  if (av.status === 'closed') return av.hint || 'Encuesta finalizada';
  return 'No disponible';
}

/** Solo encuestas abiertas + respetar el filtro por texto de useEncuestasCrud */
const visibleItems = computed(() => {
  return (filteredItems.value || []).filter(it => availabilityOf(it).status === 'open');
});

/* ===========================================================
   Responder Encuesta con SweetAlert2
=========================================================== */
const API_ROOT = (process.env.VUE_APP_API_URL || '').replace(/\/+$/, '');

function getUserIdFromStorage () {
  try {
    const u = JSON.parse(localStorage.getItem('user') || '{}');
    return u?._id || u?.id || null;
  } catch { return null; }
}

function escapeHtml (str) {
  return String(str || '')
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;')
    .replace(/'/g,'&#039;');
}

/* Icono/badge por tipo (por si decides mostrarlo más tarde) */
const tipoIcon = (tipo) => {
  switch (tipo) {
    case 'opcion_multiple':    return 'bi bi-ui-radios';
    case 'seleccion_multiple': return 'bi bi-ui-checks';
    case 'respuesta_abierta':  return 'bi bi-chat-text';
    default:                   return 'bi bi-question-circle';
  }
};
const tipoBadgeClass = (tipo) => {
  switch (tipo) {
    case 'opcion_multiple':    return 'bg-type-multi';
    case 'seleccion_multiple': return 'bg-type-multi';
    case 'respuesta_abierta':  return 'bg-type-open';
    default:                   return 'bg-secondary-subtle text-secondary';
  }
};

/** Verifica ventana antes de iniciar y da feedback si no procede */
async function guardedStartSurvey(encuesta) {
  const av = availabilityOf(encuesta);
  if (av.status !== 'open') {
    baseToast(av.hint || 'Esta encuesta no está disponible.', 'warning');
    return;
  }
  await startSurvey(encuesta);
}

async function startSurvey (encuesta) {
  const uid = getUserIdFromStorage();
  if (!uid) { baseToast('No se pudo identificar al usuario para responder la encuesta.', 'error'); return; }

  // última verificación justo antes de abrir el flujo
  const av = availabilityOf(encuesta);
  if (av.status !== 'open') {
    baseToast(av.hint || 'Esta encuesta no está disponible.', 'warning');
    return;
  }

  const qs = Array.isArray(encuesta?.cuestionario) ? encuesta.cuestionario : [];
  const validQuestions = qs.filter(q => ['opcion_multiple','seleccion_multiple','respuesta_abierta'].includes(q?.tipo));
  if (validQuestions.length === 0) { baseToast('Esta encuesta no tiene preguntas válidas para responder.', 'warning'); return; }

  // Bienvenida
  const start = await Swal.fire({
    title: '¡Gracias por participar!',
    html: `
      <p class="mb-2">Esta encuesta nos ayuda a mejorar tu experiencia.</p>
      <p class="mb-0 small text-muted">Tardarás aprox. <b>${encuesta?.duracion_estimada || 5} min</b>.</p>
    `,
    confirmButtonText: 'Comenzar',
    showCancelButton: true,
    cancelButtonText: 'Ahora no',
    backdrop: true,
    allowOutsideClick: false,
    customClass: {
      popup: 'sw-rounded',
      confirmButton: 'sw-confirm',
      cancelButton: 'sw-cancel'
    }
  });
  if (!start.isConfirmed) return;

  const respuestas = [];
  const total = validQuestions.length;

  for (let i = 0; i < total; i++) {
    const q = validQuestions[i];
    const headerHtml = `
      <div class="sw-step-header">
        <div class="sw-step">Pregunta ${i + 1} de ${total}</div>
        <div class="sw-title">${escapeHtml(q.pregunta || 'Pregunta')}</div>
      </div>
    `;

    // Opción múltiple (radio)
    if (q.tipo === 'opcion_multiple') {
      const opts = {};
      (q.opciones || []).forEach((op, idx) => { opts[String(idx)] = op; });

      const { value: idxStr, isDismissed } = await Swal.fire({
        html: headerHtml,
        input: 'radio',
        inputOptions: opts,
        inputValidator: (v) => (!v ? 'Selecciona una opción.' : null),
        confirmButtonText: (i + 1 === total) ? 'Guardar' : 'Siguiente',
        showCancelButton: true,
        cancelButtonText: 'Atrás',
        allowOutsideClick: false,
        customClass: { popup:'sw-rounded', confirmButton:'sw-confirm', cancelButton:'sw-back' },
        didOpen: () => {
          const radios = Swal.getPopup().querySelectorAll('input[type="radio"]');
          radios.forEach(r => r.parentElement.classList.add('sw-radio-item'));
        }
      });

      if (isDismissed) {
        if (i === 0) return;
        i -= 2; respuestas.pop(); continue;
      }

      const idxSel = Number(idxStr);
      const value = (q.opciones || [])[idxSel];
      respuestas.push({ pregunta_id: q._id || `q${i+1}`, respuesta: value });
      continue;
    }

    // Selección múltiple (checkbox)
    if (q.tipo === 'seleccion_multiple') {
      const checkHtml = `
        ${headerHtml}
        <div class="text-start mt-2">
          ${(q.opciones || []).map((op, k) => `
            <div class="form-check my-1">
              <input class="form-check-input" type="checkbox" id="chk_${k}" data-value="${escapeHtml(op)}">
              <label class="form-check-label" for="chk_${k}">${escapeHtml(op)}</label>
            </div>
          `).join('')}
        </div>
      `;

      const res = await Swal.fire({
        html: checkHtml,
        confirmButtonText: (i + 1 === total) ? 'Guardar' : 'Siguiente',
        showCancelButton: true,
        cancelButtonText: 'Atrás',
        allowOutsideClick: false,
        customClass: { popup:'sw-rounded', confirmButton:'sw-confirm', cancelButton:'sw-back' },
        preConfirm: () => {
          const popup = Swal.getPopup();
          const checked = Array.from(popup.querySelectorAll('.form-check-input:checked'))
            .map(el => el.getAttribute('data-value'))
            .filter(Boolean);
          if (checked.length === 0) {
            Swal.showValidationMessage('Selecciona al menos una opción.');
            return false;
          }
          return checked;
        }
      });

      if (res.isDismissed) {
        if (i === 0) return;
        i -= 2; respuestas.pop(); continue;
      }

      respuestas.push({ pregunta_id: q._id || `q${i+1}`, respuesta: res.value });
      continue;
    }

    // Respuesta abierta
    if (q.tipo === 'respuesta_abierta') {
      const { value: texto, isDismissed } = await Swal.fire({
        html: headerHtml,
        input: 'textarea',
        inputAttributes: { 'aria-label': 'Escribe tu respuesta' },
        inputPlaceholder: 'Escribe aquí…',
        inputValidator: (v) => {
          const s = (v || '').trim();
          if (!s) return 'Escribe una respuesta.';
          if (s.length < 3) return 'Usa al menos 3 caracteres.';
          if (s.length > 1000) return 'Máximo 1000 caracteres.';
          return null;
        },
        confirmButtonText: (i + 1 === total) ? 'Guardar' : 'Siguiente',
        showCancelButton: true,
        cancelButtonText: 'Atrás',
        allowOutsideClick: false,
        customClass: { popup:'sw-rounded', confirmButton:'sw-confirm', cancelButton:'sw-back' }
      });

      if (isDismissed) {
        if (i === 0) return;
        i -= 2; respuestas.pop(); continue;
      }

      respuestas.push({ pregunta_id: q._id || `q${i+1}`, respuesta: texto.trim() });
      continue;
    }
  }

  // Confirmación final
  const ok = await Swal.fire({
    icon: 'question',
    title: '¿Listo para enviar?',
    html: `<p class="mb-1">Se guardarán tus respuestas.</p>
           <p class="small text-muted mb-0">Gracias por tu tiempo.</p>`,
    showCancelButton: true,
    confirmButtonText: 'Enviar',
    cancelButtonText: 'Revisar',
    allowOutsideClick: false,
    customClass: { popup:'sw-rounded', confirmButton:'sw-confirm', cancelButton:'sw-back' }
  });
  if (!ok.isConfirmed) return;

  // PUT -> /api/encuestas/:id/responder
  try {
    const url = `${API_ROOT}/encuestas/${getId(encuesta)}/responder`;
    await axios.put(url, { usuario_id: uid, respuestas }, { headers: authHeaders() });

    await Swal.fire({
      icon: 'success',
      title: '¡Respuestas guardadas!',
      text: 'Gracias por participar en la encuesta.',
      confirmButtonText: 'Aceptar',
      customClass: { popup:'sw-rounded', confirmButton:'sw-confirm' }
    });
  } catch (e) {
    console.error(e);
    await Swal.fire({
      icon: 'error',
      title: 'No se pudo guardar',
      text: e?.response?.data?.message || 'Ocurrió un error al enviar tus respuestas. Intenta de nuevo.',
      confirmButtonText: 'Entendido',
      customClass: { popup:'sw-rounded', confirmButton:'sw-confirm' }
    });
  }
}
</script>

<style scoped>
@import '@/assets/css/Crud.css';

/* ======= Hero pastel con “aurora light” ======= */
.hero{
  position: relative;
  overflow: hidden;
  color: #0b1220;
  border-bottom: 1px solid rgba(0,0,0,.06);
  background:
    radial-gradient(1100px 600px at 18% -10%, rgba(48, 247, 167, 0.12), transparent 60%),
    radial-gradient(1100px 600px at 100% 0%,  rgba(97, 6, 207, 0.18), transparent 55%),
    #fafbfe;
}
.hero::before{
  content:"";
  position:absolute; inset:-20% -10%;
  filter: blur(42px);
  opacity:.85;
  background:
    radial-gradient(40% 50% at 50% 55%, rgba(255,255,255,.85) 0 18%, rgba(255,255,255,0) 55%),
    conic-gradient(from 210deg at 60% 45%,
      rgba(25,178,118,.20),
      rgba(107,63,160,.20),
      rgba(25,178,118,.12),
      rgba(62, 7, 128, 0.55),
      rgba(25,178,118,.20));
  mix-blend-mode: screen;
  animation: auroraMove 10s ease-in-out infinite alternate;
}
.hero::after{
  content:"";
  position:absolute; inset:-30% -20%;
  background:
    radial-gradient(35% 45% at 35% 60%, rgba(25,178,118,.14), rgba(25,178,118,0) 60%),
    radial-gradient(30% 40% at 70% 35%, rgba(107,63,160,.14), rgba(107,63,160,0) 60%);
  filter: blur(34px);
  mix-blend-mode: screen;
  animation: auroraFloat 14s ease-in-out infinite alternate;
}
@keyframes auroraMove{
  0%   { transform: translate(-6%, -2%) rotate(0deg) scale(1.02); }
  100% { transform: translate(6%,  2%) rotate(3deg)  scale(1.05); }
}
@keyframes auroraFloat{
  0%   { transform: translateY(-4%) scale(1); }
  100% { transform: translateY(4%)  scale(1.03); }
}

.hero-inner{ max-width: 960px; margin: 0 auto; position: relative; z-index: 1; }
.title-bigger{ letter-spacing: .2px; }
.opacity-85{ opacity: .85; }

/* Elevar sutilmente al hover */
.hover-raise {
  transition: transform .2s ease, box-shadow .2s ease;
}
.hover-raise:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 24px rgba(0,0,0,.08);
}

/* Estado */
.bg-status{ border: 1px solid rgba(0,0,0,.08); }
.bg-type-multi{ background: #e7f7ef; color: #0f7a47; border: 1px solid #bce8d1; }
.bg-type-open{  background: #fff4e5; color: #8a4b00; border: 1px solid #ffe0b8; }

/* SweetAlert2 custom */
:global(.swal2-container){ backdrop-filter: blur(3px); }
.sw-rounded{ border-radius: 18px !important; }
.sw-confirm{ background: #1b7c54 !important; border-radius: 999px !important; padding: .6rem 1.1rem !important; }
.sw-back{ background: #e9eef6 !important; color: #1b3b6f !important; border-radius: 999px !important; }
.sw-cancel{ background: #f1f3f8 !important; color: #3b3b3b !important; border-radius: 999px !important; }
.sw-radio-item{ display: flex; align-items: center; gap: .5rem; margin: .35rem 0; }
.sw-step-header{ text-align: left; }
.sw-step{ font-size: .85rem; color: #6c7a91; margin-bottom: .25rem; }
.sw-title{ font-weight: 600; font-size: 1.05rem; }

/* Ajustes menores */
.btn-with-label i{ vertical-align: -1px; }

/* Accesibilidad: respeta reduce-motion */
@media (prefers-reduced-motion: reduce){
  .hero::before, .hero::after { animation: none; }
}
</style>
