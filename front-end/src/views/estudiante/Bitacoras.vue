<!-- src/views/estudiante/BitacorasCalendar.vue -->
<template>
  <main class="bitacoras-calendar-page">
    <!-- ======= Header ======= -->
    <div class="container-fluid py-3 header-hero">
      <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="hero-emoji">🧠</div>
          <div>
            <h2 class="mb-0 fw-bold">Tu Bitácora Emocional</h2>
            <div class="text-white-50 small">Registra cómo te sientes y gana recompensas</div>
          </div>
        </div>

        <!-- Búsqueda + puntos -->
        <div class="toolbar-right d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center gap-2 w-100 w-lg-auto">
          <div
            class="input-group input-group-lg search-group shadow-sm rounded-pill flex-grow-1"
            role="search"
            aria-label="Buscador de bitácoras"
            :class="{ 'searching': searchQuery }"
          >
            <span class="input-group-text rounded-start-pill">
              <i class="bi bi-search"></i>
            </span>
            <input
              v-model.trim="searchQuery"
              type="search"
              class="form-control search-input"
              placeholder="Buscar por título o fecha (YYYY-MM-DD)…"
              @input="onInstantSearch"
              aria-label="Buscar por título o fecha"
            />
            <button
              v-if="searchQuery"
              class="btn btn-link text-light px-3 d-none d-md-inline"
              @click="clearSearch"
              aria-label="Limpiar búsqueda"
            >
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          <div class="points-chip shadow-sm">
            <i class="bi bi-trophy-fill me-1"></i>
            <span class="fw-semibold">{{ puntos }}</span>
            <span class="ms-1">puntos</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ======= Toolbar ======= -->
    <div class="container-fluid px-3 px-lg-2 my-2">
      <div class="row g-2 align-items-center">
        <div class="col-12 col-lg-7 d-flex align-items-center gap-2 flex-wrap">
          <div class="btn-group shadow-sm" role="group" aria-label="Navegación de calendario">
            <button class="btn btn-outline-secondary" @click="goPrev" title="Mes anterior">
              <i class="bi bi-chevron-left"></i>
            </button>
            <button class="btn btn-outline-secondary" @click="goToday" title="Hoy">Hoy</button>
            <button class="btn btn-outline-secondary" @click="goNext" title="Mes siguiente">
              <i class="bi bi-chevron-right"></i>
            </button>
          </div>

          <div class="ms-2 month-title">
            <i class="bi bi-calendar3 me-1"></i>
            {{ formatMonthLabel(currentDate) }}
          </div>

          <div class="legend ms-lg-3">
            <span class="legend-dot bg-success"></span> Registrada
            <span class="legend-dot bg-danger ms-3"></span> Falta registrar
            <span class="legend-dot bg-warning ms-3"></span> Coincide con búsqueda
          </div>
        </div>

        <div class="col-12 col-lg-5 d-flex justify-content-lg-end mt-2 mt-lg-0">
          <button
            class="btn btn-gradient fw-semibold shadow-sm rounded-pill btn-new px-3 w-100 w-sm-auto"
            :disabled="hasTodayEntry"
            @click="openCreateForToday"
          >
            <i class="bi bi-plus-lg me-1"></i> Nueva entrada
          </button>
        </div>
      </div>
    </div>

    <!-- ======= Calendario ======= -->
    <div class="container-fluid px-2 px-lg-3">
      <div class="card shadow-sm border-0">
        <div class="card-body p-2 p-md-3">
          <div class="calendar-wrapper">
            <FullCalendar :key="fcKey" ref="calendarRef" :options="calendarOptions" />
          </div>
        </div>
      </div>
    </div>
  </main>
</template>

<script setup>
import { ref, reactive, computed, onMounted, nextTick, watch } from 'vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import { useBitacorasCalendar } from '@/assets/js/useBitacorasCalendar';

/* ===== Imágenes ===== */
import bienvenidaImg from '@/assets/images/bienvenidaBitacora.png';
import qTitleImg     from '@/assets/images/q1Bitacora.png';
import qMoodImg      from '@/assets/images/q2Bitacora.png';
import qGoodImg      from '@/assets/images/q3Bitacora.png';
import qWorryImg     from '@/assets/images/q4Bitacora.png';
import qActionImg    from '@/assets/images/q5Bitacora.png';

const EMOJIS = ['😀','😊','🙂','😌','😐','😕','😟','😢','😡','😴','😮','😬'];
const studentName = ref(JSON.parse(localStorage.getItem('user')||'{}')?.name || '¡hola!');

const {
  items, isLoading, month, year,
  puntos, puntosCargados, loadUserAndPoints,
  searchQuery, onInstantSearch, clearSearch,
  onSubmit, confirmDelete,
  form, isEditing, saving, selected,
  fetchMonth,
  titleWithoutEmoji, emojiFromTitle, toast, formatMonthLabel,
  isToday, todayISO
} = useBitacorasCalendar({ EMOJIS });

/* ===== Calendario ===== */
const calendarRef = ref(null);
const currentDate = ref(new Date());
const fcKey = ref('fc-0');
window.__bitacoraCalendarApi = () => calendarRef.value?.getApi?.();

const datesWithEntry = computed(() => new Set(items.value.map(b => b.fecha)));
const hasTodayEntry  = computed(() => datesWithEntry.value.has(todayISO()));

const baseEvents = computed(() =>
  items.value.map(b => ({
    id: b.id || b._id,
    _id: b.id || b._id,
    title: titleWithoutEmoji(b.titulo),
    emoji: emojiFromTitle(b.titulo),
    start: b.fecha,
    allDay: true
  }))
);

const matchedDates = computed(() => {
  const q = (searchQuery.value || '').toLowerCase().trim();
  if (!q) return new Set();
  const s = new Set();
  baseEvents.value.forEach(ev => {
    const title = (ev.title || '').toLowerCase();
    const d = String(ev.start || '');
    if (title.includes(q) || d.includes(q)) s.add(ev.start);
  });
  return s;
});

const filteredEvents = computed(() => {
  const q = (searchQuery.value || '').toLowerCase().trim();
  if (!q) return baseEvents.value;
  return baseEvents.value.filter(ev => {
    const title = (ev.title || '').toLowerCase();
    const d = String(ev.start || '');
    return title.includes(q) || d.includes(q);
  });
});

/* ==== Textos de navegación ==== */
const BACK_TXT = 'Atrás';
const NEXT_TXT = 'Siguiente';
const WELCOME_TXT = 'Comenzar';

/* ========== SweetAlert mixin base (tema + X + separación) ========== */
const mindoraSwal = Swal.mixin({
  allowOutsideClick: false,
  buttonsStyling: false,
  heightAuto: false,
  focusConfirm: true,
  showCloseButton: true,
  customClass: {
    popup: 'swal2-responsive swal2-mindora rounded-4',
    actions: 'swal2-actions-spaced flow-back-first',
    confirmButton: 'btn btn-gradient',
    cancelButton: 'btn btn-secondary',          // gris
    denyButton: 'btn btn-outline-primary'
  },
  backdrop: 'rgba(2,6,12,.35)'
});

/* ===== Helper para asegurar blur SIEMPRE y fusionar clases ===== */
const DEFAULT_SWAL_CLASSES = {
  popup: 'swal2-responsive swal2-mindora rounded-4',
  actions: 'swal2-actions-spaced flow-back-first',
  confirmButton: 'btn btn-gradient',
  cancelButton: 'btn btn-secondary',
  denyButton: 'btn btn-outline-primary'
};
function fireBlur(opts = {}) {
  const merged = {
    ...opts,
    customClass: { ...(DEFAULT_SWAL_CLASSES), ...(opts.customClass || {}) },
    didOpen: () => {
      document.body.classList.add('mindora-blur-open');
      if (typeof opts.didOpen === 'function') opts.didOpen();
    },
    willClose: () => {
      document.body.classList.remove('mindora-blur-open');
      if (typeof opts.willClose === 'function') opts.willClose();
    }
  };
  return mindoraSwal.fire(merged);
}

/* ===== Detalle / Edición ===== */
async function viewEntrySwal(item){
  const body = `
    <div class="text-start">
      <div class="mb-1"><strong>Fecha:</strong> ${item.fecha}</div>
      <div class="mb-1"><strong>Título:</strong> ${titleWithoutEmoji(item.titulo)}</div>
      <div class="mb-2"><strong>Descripción:</strong><br>${(item.descripcion||'—').replace(/\n/g,'<br>')}</div>
    </div>`;

  const showEdit = isToday(item.fecha);
  const showDel  = isToday(item.fecha) && puntos.value > 0;

  const res = await fireBlur({
    title: 'Detalle de tu bitácora',
    html: body,
    imageUrl: qTitleImg,
    imageWidth: 120,

    // Botones visibles
    showCancelButton: true,          // Cancelar (gris)
    showDenyButton: showEdit,        // Modificar (azul)
    showConfirmButton: showDel,      // Eliminar (rojo)

    // Textos
    cancelButtonText: 'Cancelar',
    denyButtonText: 'Modificar',
    confirmButtonText: 'Eliminar',

    // Orden en consulta: Cancelar → Modificar → Eliminar (vía clase)
    customClass: {
      actions: 'swal2-actions-spaced flow-consulta',
      cancelButton: 'btn btn-secondary',
      denyButton: 'btn btn-primary',
      confirmButton: 'btn btn-danger'
    }
  });

  if (res.isConfirmed && showDel) {
    await confirmDelete(item);
  } else if (res.isDenied && showEdit) {
    await editEntrySwal(item);
  }
}

async function editEntrySwal(item){
  if (!isToday(item.fecha)) { toast('Sólo puedes modificar la bitácora de hoy.', 'warning'); return; }

  const html = `
    <div class="text-start">
      <div class="mb-3">
        <label class="form-label fw-semibold">Título</label>
        <input id="swal-title" type="text" class="form-control" maxlength="150" value="${titleWithoutEmoji(item.titulo)}" />
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">Emoción</label>
        <div class="d-flex flex-wrap gap-2" id="swal-emoji-list">
          ${EMOJIS.map(e=>`<button type="button" class="btn btn-light border rounded px-2 py-1" data-emoji="${e}">${e}</button>`).join('')}
        </div>
      </div>
      <div class="mb-2">
        <label class="form-label fw-semibold">Descripción</label>
        <textarea id="swal-desc" class="form-control" rows="4">${item.descripcion||''}</textarea>
      </div>
      <small class="text-muted">Fecha: ${item.fecha} (sólo hoy se puede editar)</small>
    </div>`;

  await fireBlur({
    title: 'Editar entrada',
    html,
    imageUrl: qTitleImg,
    imageWidth: 120,
    showCancelButton: true,
    confirmButtonText: 'Guardar',
    cancelButtonText: 'Cancelar',
    didOpen: () => {
      const list = document.getElementById('swal-emoji-list');
      if (list) {
        list.querySelectorAll('button').forEach(btn=>{
          if(btn.dataset.emoji === item.emoji) btn.classList.add('active');
          btn.addEventListener('click', ()=> {
            list.querySelectorAll('button').forEach(b=>b.classList.remove('active'));
            btn.classList.add('active');
          });
        });
      }
    },
    preConfirm: () => {
      const title = document.getElementById('swal-title')?.value?.trim();
      const desc  = document.getElementById('swal-desc')?.value?.trim() || '';
      const active = document.querySelector('#swal-emoji-list .active');
      const emoji  = active ? active.dataset.emoji : (item.emoji || '');
      if (!title) { Swal.showValidationMessage('El título es obligatorio'); return false; }
      return { title, desc, emoji };
    }
  }).then(async (r)=>{
    if (!r.isConfirmed) return;
    isEditing.value = true;
    form._id   = item._id || item.id;
    form.fecha = item.fecha;
    form.titulo = r.value.title;
    form.emoji  = r.value.emoji || '';
    form.descripcion = r.value.desc || '';
    await onSubmit();
  });
}

/* ===== Wizard creación con blur garantizado y botones (Atrás ← / Siguiente →) ===== */
async function createWizardSwal(dateStr){
  if (datesWithEntry.value.has(dateStr)) {
    const ex = items.value.find(b => b.fecha === dateStr);
    await viewEntrySwal(ex);
    return;
  }

  let step = 0; let title = ''; let moodKey = 'calm'; let a1 = '', a2 = '', a3 = '';

  const MOODS = [
    { key:'joy',       label:'Alegría',    colors:['#d7f7cc','#8edb6b'], face:'smile' },
    { key:'calm',      label:'Calma',      colors:['#c9f3de','#7cd39b'], face:'soft'  },
    { key:'anxiety',   label:'Ansiedad',   colors:['#ffe0e0','#ff8b8b'], face:'frown' },
    { key:'distracted',label:'Distraído/a',colors:['#e7e6ff','#c1b8ff'], face:'wavy'  },
    { key:'surprised', label:'Sorpresa',   colors:['#fff5c7','#ffe07a'], face:'wow'   },
    { key:'tired',     label:'Cansancio',  colors:['#e0e7ff','#a5b4fc'], face:'flat'  },
  ];
  const facePaths = {
    smile:  { mouth:'M42 70 Q60 84 78 70', eyes:['M40 50 q4 -6 8 0','M72 50 q4 -6 8 0'] },
    soft:   { mouth:'M44 70 Q60 78 76 70', eyes:['M42 52 q3 -4 6 0','M74 52 q3 -4 6 0'] },
    frown:  { mouth:'M42 74 Q60 60 78 74', eyes:['M40 52 q8 -6 12 0','M72 52 q8 -6 12 0'] },
    wavy:   { mouth:'M42 70 q8 6 16 0 q8 -6 16 0', eyes:['M44 50 l6 4','M74 50 l6 4'] },
    wow:    { mouth:'M58 66 a8 8 0 1 0 4 0 a8 8 0 1 0 -4 0', eyes:['M48 48 a4 4 0 1 0 0 .1','M76 48 a4 4 0 1 0 0 .1'] },
    flat:   { mouth:'M44 70 L76 70', eyes:['M44 52 L50 52','M76 52 L82 52'] },
  };
  function moodSVG(colors, face='soft'){
    const [c1='#d9fbe2']=colors||[]; const f = facePaths[face] || facePaths.soft;
    return `<svg viewBox="0 0 120 120" width="92" height="92" class="shadow-sm mood-face"
              style="filter:drop-shadow(0 10px 24px rgba(0,0,0,.12))">
              <circle cx="60" cy="60" r="48" fill="${c1}"></circle>
              <g class="mface" fill="none" stroke="#1b4332" stroke-linecap="round" stroke-width="4">
                <path class="mouth" d="${f.mouth}"/><path class="eyeL" d="${f.eyes[0]}"/><path class="eyeR" d="${f.eyes[1]}"/>
              </g>
            </svg>`;
  }
  function setFace(key){
    const face = MOODS.find(m=>m.key===key)?.face || 'soft';
    const color = MOODS.find(m=>m.key===key)?.colors?.[0] || '#d9fbe2';
    const root = Swal.getHtmlContainer();
    const circle = root?.querySelector('.mood-face circle');
    const mouth  = root?.querySelector('.mface .mouth');
    const eyeL   = root?.querySelector('.mface .eyeL');
    const eyeR   = root?.querySelector('.mface .eyeR');
    const f = facePaths[face] || facePaths.soft;
    if (circle) circle.setAttribute('fill', color);
    if (mouth)  mouth.setAttribute('d', f.mouth);
    if (eyeL)   eyeL.setAttribute('d', f.eyes[0]);
    if (eyeR)   eyeR.setAttribute('d', f.eyes[1]);
  }

  while (true) {
    if (step === 0) {
      const welcome = `
        <div class="text-center">
          <div class="mb-3">
            <img src="${bienvenidaImg}" alt="Bienvenida" style="max-width:140px;border-radius:16px;box-shadow:0 10px 24px rgba(16,50,36,.12)" />
          </div>
          <h3 class="fw-bolder mb-2">¡Hola, ${studentName.value}!</h3>
          <p class="mb-0">Haremos unas preguntas rápidas para ayudarte a expresar cómo te sientes hoy.</p>
        </div>`;
      const r = await fireBlur({
        html: welcome,
        showCancelButton: false,
        showDenyButton: false,
        confirmButtonText: WELCOME_TXT,
        customClass: { confirmButton: 'btn btn-ghost-green btn-welcome-arrow' },
        didOpen: () => {
          const c = Swal.getConfirmButton();
          if (c) c.innerHTML = `<i class="bi bi-arrow-right"></i> ${WELCOME_TXT}`;
        }
      });
      if (!r.isConfirmed) return;
      step++; continue;
    }

    if (step === 1) {
      const r = await fireBlur({
        title: '¿Cómo resumirías tu día de hoy en una oración?',
        input: 'text',
        inputAttributes: { maxlength:'150' },
        inputValue: title,
        inputPlaceholder: 'Ej. Hoy me sentí en calma después de respirar profundo',
        imageUrl: qTitleImg,
        imageWidth: 120,
        showCancelButton: false,
        showDenyButton: true,
        denyButtonText: BACK_TXT,
        confirmButtonText: NEXT_TXT,
        reverseButtons: false,
        didOpen: () => {
          const denyBtn = Swal.getDenyButton();
          const confBtn = Swal.getConfirmButton();
          if (denyBtn) denyBtn.innerHTML = `<i class="bi bi-arrow-left"></i> ${BACK_TXT}`;
          if (confBtn) confBtn.innerHTML = `${NEXT_TXT} <i class="bi bi-arrow-right"></i>`;
        },
        preConfirm: (v)=>{ if(!String(v||'').trim()) { Swal.showValidationMessage('Escribe una oración.'); return false; } return v.trim(); }
      });
      if (r.isDenied) { step = Math.max(0, step-1); continue; }
      if (!r.isConfirmed) return;
      title = r.value; step++; continue;
    }

    if (step === 2) {
      const moodHtml = `
        <div class="d-flex align-items-center justify-content-between mb-3">
          <div class="d-none d-sm-block">${moodSVG(MOODS.find(m=>m.key===moodKey)?.colors, MOODS.find(m=>m.key===moodKey)?.face)}</div>
          <div class="ms-0 ms-sm-3 flex-grow-1">
            <div class="d-flex flex-wrap gap-2 justify-content-center" id="mood-chips">
              ${MOODS.map(m=>`<button type="button" class="btn chip ${m.key===moodKey?'active':''}" data-key="${m.key}">${m.label}</button>`).join('')}
            </div>
          </div>
        </div>`;
      const r = await fireBlur({
        title: '¿Qué emoción representa mejor tu día?',
        html: moodHtml,
        imageUrl: qMoodImg,
        imageWidth: 120,
        showCancelButton: false,
        showDenyButton: true,
        denyButtonText: BACK_TXT,
        confirmButtonText: NEXT_TXT,
        reverseButtons: false,
        didOpen: () => {
          const cont = document.getElementById('mood-chips');
          cont?.querySelectorAll('.chip').forEach(btn=>{
            btn.addEventListener('click', ()=>{
              cont.querySelectorAll('.chip').forEach(b=>b.classList.remove('active'));
              btn.classList.add('active');
              moodKey = btn.dataset.key;
              setFace(moodKey);
            });
          });
          const denyBtn = Swal.getDenyButton();
          const confBtn = Swal.getConfirmButton();
          if (denyBtn) denyBtn.innerHTML = `<i class="bi bi-arrow-left"></i> ${BACK_TXT}`;
          if (confBtn) confBtn.innerHTML = `${NEXT_TXT} <i class="bi bi-arrow-right"></i>`;
        }
      });
      if (r.isDenied) { step--; continue; }
      if (!r.isConfirmed) return;
      step++; continue;
    }

    if (step === 3) {
      const r = await fireBlur({
        title: '¿Qué salió bien hoy?',
        input: 'textarea',
        inputValue: a1,
        inputPlaceholder: 'Reconoce tus logros, aunque sean pequeños…',
        inputAttributes: { rows: 3 },
        imageUrl: qGoodImg,
        imageWidth: 120,
        showCancelButton: false,
        showDenyButton: true,
        denyButtonText: BACK_TXT,
        confirmButtonText: NEXT_TXT,
        reverseButtons: false,
        didOpen: () => {
          const denyBtn = Swal.getDenyButton();
          const confBtn = Swal.getConfirmButton();
          if (denyBtn) denyBtn.innerHTML = `<i class="bi bi-arrow-left"></i> ${BACK_TXT}`;
          if (confBtn) confBtn.innerHTML = `${NEXT_TXT} <i class="bi bi-arrow-right"></i>`;
        }
      });
      if (r.isDenied) { step--; continue; }
      if (!r.isConfirmed) return;
      a1 = (r.value||'').trim();
      step++; continue;
    }

    if (step === 4) {
      const r = await fireBlur({
        title: '¿Qué te preocupó hoy?',
        input: 'textarea',
        inputValue: a2,
        inputPlaceholder: 'Escribe aquello que te inquieta…',
        inputAttributes: { rows: 3 },
        imageUrl: qWorryImg,
        imageWidth: 120,
        showCancelButton: false,
        showDenyButton: true,
        denyButtonText: BACK_TXT,
        confirmButtonText: NEXT_TXT,
        reverseButtons: false,
        didOpen: () => {
          const denyBtn = Swal.getDenyButton();
          const confBtn = Swal.getConfirmButton();
          if (denyBtn) denyBtn.innerHTML = `<i class="bi bi-arrow-left"></i> ${BACK_TXT}`;
          if (confBtn) confBtn.innerHTML = `${NEXT_TXT} <i class="bi bi-arrow-right"></i>`;
        }
      });
      if (r.isDenied) { step--; continue; }
      if (!r.isConfirmed) return;
      a2 = (r.value||'').trim();
      step++; continue;
    }

    if (step === 5) {
      const r = await fireBlur({
        title: '¿Qué harás para que mañana sea un buen día?',
        input: 'textarea',
        inputValue: a3,
        inputPlaceholder: '1–2 acciones concretas…',
        inputAttributes: { rows: 3 },
        imageUrl: qActionImg,
        imageWidth: 120,
        showCancelButton: false,
        showDenyButton: true,
        denyButtonText: BACK_TXT,
        confirmButtonText: 'Guardar',
        reverseButtons: false,
        didOpen: () => {
          const denyBtn = Swal.getDenyButton();
          if (denyBtn) denyBtn.innerHTML = `<i class="bi bi-arrow-left"></i> ${BACK_TXT}`;
        }
      });
      if (r.isDenied) { step--; continue; }
      if (!r.isConfirmed) return;
      a3 = (r.value||'').trim();

      const moodLabel = MOODS.find(m=>m.key===moodKey)?.label || '';
      const parts = [
        `Emoción del día: ${moodLabel}`,
        a1 ? `¿Qué salió bien hoy?: ${a1}` : '',
        a2 ? `¿Qué me preocupa?: ${a2}` : '',
        a3 ? `¿Qué haré para que mañana sea un buen día?: ${a3}` : ''
      ].filter(Boolean);

      isEditing.value = false;
      form._id   = null;
      form.fecha = dateStr;
      form.titulo = title;
      form.emoji  = '';
      form.descripcion = parts.join('\n\n');
      await onSubmit();
      break;
    }
  }
}

function openCreateForToday(){
  const iso = todayISO();
  if (!isToday(iso)) return;
  createWizardSwal(iso);
}

/* ===== Opciones FullCalendar ===== */
const calendarOptions = reactive({
  plugins: [dayGridPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  locale: 'es',
  height: 'auto',
  firstDay: 1,
  fixedWeekCount: false,
  headerToolbar: false,
  dayMaxEventRows: 2,
  events: () => filteredEvents.value,

  dateClick: (info) => {
    const entry = items.value.find(b => b.fecha === info.dateStr);
    if (entry) { viewEntrySwal(entry); return; }
    if (isToday(info.dateStr)) { createWizardSwal(info.dateStr); }
    else { toast('Sólo puedes registrar la bitácora del día de hoy.', 'warning'); }
  },

  eventClick: (info) => {
    const id = info.event.extendedProps._id;
    const item = items.value.find(x => x.id === id || x._id === id);
    if (item) viewEntrySwal(item);
  },

  eventClassNames: () => ['bitacora-event'],

  eventContent: (arg) => {
    const wrap = document.createElement('div');
    wrap.className = 'd-flex align-items-center gap-1';

    const emojiSpan = document.createElement('span');
    emojiSpan.className = 'me-1';
    emojiSpan.textContent = arg.event.extendedProps.emoji || '';

    const titleSpan = document.createElement('span');
    titleSpan.className = 'fc-title-text flex-grow-1';
    titleSpan.textContent = arg.event.title || '';

    wrap.appendChild(emojiSpan);
    wrap.appendChild(titleSpan);

    const canAct = isToday(arg.event.startStr);
    if (canAct) {
      const editBtn = document.createElement('button');
      editBtn.className = 'btn btn-xs btn-outline-primary btn-ev-edit';
      editBtn.title = 'Editar';
      editBtn.innerHTML = '<i class="bi bi-pencil"></i>';

      const delBtn = document.createElement('button');
      delBtn.className = 'btn btn-xs btn-outline-danger btn-ev-del';
      delBtn.title = (puntos.value <= 0 ? 'No puedes eliminar con 0 puntos' : 'Eliminar');
      if (puntos.value <= 0) delBtn.setAttribute('disabled', 'true');
      delBtn.innerHTML = '<i class="bi bi-trash"></i>';

      wrap.appendChild(editBtn);
      wrap.appendChild(delBtn);

      editBtn.addEventListener('click', (e) => {
        e.preventDefault(); e.stopPropagation();
        const id = arg.event.extendedProps._id;
        const item = items.value.find(x => x.id === id || x._id === id);
        if (item) editEntrySwal(item);
      });

      delBtn.addEventListener('click', async (e) => {
        e.preventDefault(); e.stopPropagation();
        const id = arg.event.extendedProps._id;
        const item = items.value.find(x => x.id === id || x._id === id);
        if (item && puntos.value > 0) await confirmDelete(item);
      });
    }

    if (matchedDates.value.has(arg.event.startStr)) wrap.classList.add('match');
    return { domNodes: [wrap] };
  },

  dayCellClassNames: (arg) => {
    const api = calendarRef.value?.getApi?.();
    const current = api?.getDate?.();
    if (current) currentDate.value = current;

    const inMonth = current ? (arg.date.getMonth() === current.getMonth()) : true;
    const iso = arg.date.toISOString().slice(0,10);
    const cls = [];
    if (inMonth) {
      cls.push(datesWithEntry.value.has(iso) ? 'has-entry' : 'missing-entry');
      if (matchedDates.value.has(iso) && searchQuery.value) cls.push('search-hit');
    }
    return cls;
  }
});

/* ===== Helpers ===== */
function forceCalendarRefresh(){
  const api = calendarRef.value?.getApi?.();
  if (!api) return;
  if (api.batchRendering) {
    api.batchRendering(() => { api.refetchEvents?.(); api.rerenderDates?.(); });
  } else { api.refetchEvents?.(); api.rerenderDates?.(); }
}

/* ===== Lifecycle ===== */
async function syncFetchToCalendar() {
  const api = calendarRef.value?.getApi?.();
  if (!api) return;
  const current = api.getDate();
  currentDate.value = current;
  const m = current.getMonth() + 1;
  const y = current.getFullYear();
  await fetchMonth(m, y);
}
function goPrev(){ const api=calendarRef.value?.getApi?.(); api?.prev(); syncFetchToCalendar(); }
function goNext(){ const api=calendarRef.value?.getApi?.(); api?.next(); syncFetchToCalendar(); }
function goToday(){ const api=calendarRef.value?.getApi?.(); api?.today(); syncFetchToCalendar(); }

onMounted(async () => {
  await nextTick();
  await loadUserAndPoints();
  await syncFetchToCalendar();
});

/* === BÚSQUEDA REACTIVA === */
watch(searchQuery, () => { fcKey.value = `fc-${Date.now()}`; forceCalendarRefresh(); });
watch(items, () => { forceCalendarRefresh(); });
</script>

<style>
  @import url('@/assets/css/Bitacora.css');
</style>
