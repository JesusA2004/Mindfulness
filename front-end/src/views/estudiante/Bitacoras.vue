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

/* ===== Imágenes (src/assets/images) ===== */
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

/* ==========
   SweetAlert mixin (tema verde difuminado + separación de botones)
========== */
const mindoraSwal = Swal.mixin({
  allowOutsideClick: false,
  buttonsStyling: false,
  heightAuto: false,
  focusConfirm: true,
  customClass: {
    popup: 'swal2-responsive swal2-mindora rounded-4',
    actions: 'swal2-actions-spaced',
    confirmButton: 'btn btn-gradient',
    cancelButton: 'btn btn-outline-secondary',
    denyButton: 'btn btn-outline-primary'
  },
  backdrop: 'rgba(2,6,12,.35)'
});
const NEXT_ICON = '<i class="bi bi-arrow-right"></i>';

/* ==========
   Detalle / Edición
========== */
async function viewEntrySwal(item){
  const body = `
    <div class="text-start">
      <div class="mb-1"><strong>Fecha:</strong> ${item.fecha}</div>
      <div class="mb-1"><strong>Título:</strong> ${titleWithoutEmoji(item.titulo)}</div>
      <div class="mb-2"><strong>Descripción:</strong><br>${(item.descripcion||'—').replace(/\n/g,'<br>')}</div>
    </div>`;

  const showEdit = isToday(item.fecha);
  const showDel  = isToday(item.fecha) && puntos.value > 0;
  const onlyClose = !showEdit && !showDel;

  const res = await mindoraSwal.fire({
    title: 'Detalle de tu bitácora',
    html: body,
    imageUrl: qTitleImg,
    imageWidth: 120,
    showDenyButton: showEdit,
    showCancelButton: onlyClose ? false : true,
    confirmButtonText: showDel ? 'Eliminar' : 'Cerrar',
    denyButtonText: 'Editar',
    cancelButtonText: onlyClose ? undefined : 'Cerrar',
    reverseButtons: !onlyClose
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

  await mindoraSwal.fire({
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

/* ==========
   Wizard de creación
========== */
async function createWizardSwal(dateStr){
  if (datesWithEntry.value.has(dateStr)) {
    const ex = items.value.find(b => b.fecha === dateStr);
    await viewEntrySwal(ex);
    return;
  }

  // Bienvenida con imagen solicitada
  const welcome = `
    <div class="text-center">
      <div class="mb-3">
        <img src="${bienvenidaImg}" alt="Bienvenida" style="max-width:140px;border-radius:16px;box-shadow:0 10px 24px rgba(16,50,36,.12)" />
      </div>
      <h3 class="fw-bolder mb-2">¡Hola, ${studentName.value}!</h3>
      <p class="mb-0">Haremos unas preguntas rápidas para ayudarte a expresar cómo te sientes hoy.</p>
    </div>`;
  const w1 = await mindoraSwal.fire({
    html: welcome,
    showConfirmButton: true,
    confirmButtonText: NEXT_ICON
  });
  if (!w1.isConfirmed) return;

  // 1) Título del día
  const rTitle = await mindoraSwal.fire({
    title: '¿Cómo resumirías tu día de hoy en una oración?',
    input: 'text',
    inputAttributes: { maxlength:'150' },
    inputPlaceholder: 'Ej. Hoy me sentí en calma después de respirar profundo',
    imageUrl: qTitleImg,
    imageWidth: 120,
    showCancelButton: true,
    confirmButtonText: NEXT_ICON,
    cancelButtonText: 'Cancelar',
    reverseButtons: true,
    preConfirm: (v)=>{ if(!String(v||'').trim()) { Swal.showValidationMessage('Escribe una oración.'); return false; } return v.trim(); }
  });
  if (!rTitle.isConfirmed) return;
  const title = rTitle.value;

  // 2) Emoción
  const MOODS = [
    { key:'joy',       label:'Alegría',    colors:['#d7f7cc','#8edb6b'] },
    { key:'calm',      label:'Calma',      colors:['#c9f3de','#7cd39b'] },
    { key:'anxiety',   label:'Ansiedad',   colors:['#ffe0e0','#ff8b8b'] },
    { key:'distracted',label:'Distraído/a',colors:['#e7e6ff','#c1b8ff'] },
    { key:'surprised', label:'Sorpresa',   colors:['#fff5c7','#ffe07a'] },
    { key:'tired',     label:'Cansancio',  colors:['#e0e7ff','#a5b4fc'] },
  ];
  function moodSvg(colors){ const [c1='#d9fbe2']=colors||[]; return `
    <svg viewBox="0 0 120 120" width="92" height="92" class="shadow-sm" style="filter:drop-shadow(0 10px 24px rgba(0,0,0,.12))">
      <circle cx="60" cy="60" r="48" fill="${c1}"></circle>
      <g fill="none" stroke="#1b4332" stroke-linecap="round" stroke-width="4">
        <path d="M42 70 Q60 82 78 70"/>
        <path d="M40 50 q4 -6 8 0"/>
        <path d="M72 50 q4 -6 8 0"/>
      </g>
    </svg>`; }

  const moodHtml = `
    <div class="d-flex align-items-center justify-content-between mb-3">
      <div class="d-none d-sm-block">${moodSvg(MOODS[1].colors)}</div>
      <div class="ms-0 ms-sm-3 flex-grow-1">
        <div class="d-flex flex-wrap gap-2 justify-content-center" id="mood-chips">
          ${MOODS.map(m=>`<button type="button" class="btn chip ${m.key==='calm'?'active':''}" data-key="${m.key}">${m.label}</button>`).join('')}
        </div>
      </div>
    </div>`;
  let moodKey = 'calm';

  const rMood = await mindoraSwal.fire({
    title: '¿Qué emoción representa mejor tu día?',
    html: moodHtml,
    imageUrl: qMoodImg,
    imageWidth: 120,
    showCancelButton: true,
    confirmButtonText: NEXT_ICON,
    cancelButtonText: 'Cancelar',
    didOpen: () => {
      const cont = document.getElementById('mood-chips');
      const svg  = Swal.getHtmlContainer().querySelector('svg circle');
      cont.querySelectorAll('.chip').forEach(btn=>{
        btn.addEventListener('click', ()=>{
          cont.querySelectorAll('.chip').forEach(b=>b.classList.remove('active'));
          btn.classList.add('active');
          moodKey = btn.dataset.key;
          const col = MOODS.find(m=>m.key===moodKey)?.colors?.[0] || '#d9fbe2';
          if (svg) svg.setAttribute('fill', col);
        });
      });
    }
  });
  if (!rMood.isConfirmed) return;

  // helper para preguntas con imagen superior
  const ask = async (label, ph='', img, alt='Pregunta', isLast=false) => {
    const r = await mindoraSwal.fire({
      title: label,
      input: 'textarea',
      inputPlaceholder: ph,
      inputAttributes: { rows: 3 },
      imageUrl: img,
      imageWidth: 120,
      imageAlt: alt,
      showCancelButton: true,
      confirmButtonText: isLast ? 'Guardar' : NEXT_ICON,
      cancelButtonText: 'Cancelar',
      reverseButtons: true
    });
    if (!r.isConfirmed) return null;
    return r.value?.trim() || '';
  };

  const a1 = await ask('¿Qué salió bien hoy?', 'Reconoce tus logros, aunque sean pequeños…', qGoodImg, '¿Qué salió bien?'); if (a1===null) return;
  const a2 = await ask('¿Qué te preocupó hoy?', 'Escribe aquello que te inquieta…', qWorryImg, '¿Qué te preocupó?');        if (a2===null) return;
  const a3 = await ask('¿Qué harás para que mañana sea un buen día?', '1–2 acciones concretas…', qActionImg, 'Acciones para mañana', true); if (a3===null) return;

  const moodLabel = MOODS.find(m=>m.key===moodKey)?.label || '';
  const parts = [
    `Emoción del día: ${moodLabel}`,
    a1 ? `¿Qué salió bien hoy?: ${a1}` : '',
    a2 ? `¿Qué me preocupa?: ${a2}` : '',
    a3 ? `¿Qué puedo hacer para que mañana sea un buen día?: ${a3}` : ''
  ].filter(Boolean);

  isEditing.value = false;
  form._id   = null;
  form.fecha = dateStr;
  form.titulo = title;
  form.emoji  = '';
  form.descripcion = parts.join('\n\n');
  await onSubmit();
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

<style scoped>
@import '@/assets/css/Crud.css';

/* ===== Fondo de la página (verde difuminado suave) ===== */
.bitacoras-calendar-page{
  background:
    radial-gradient(120% 100% at 10% 0%, #e8f9ef 0%, #e8f9ef80 40%, transparent 60%),
    linear-gradient(180deg, #f5fff9 0%, #eafbf2 100%);
  min-height: 100vh;
}

/* ===== Header ===== */
.header-hero{
  background: linear-gradient(90deg, #5563de, #5fbb97);
  color: #fff;
  border-bottom-left-radius: 1rem;
  border-bottom-right-radius: 1rem;
}
.hero-emoji{ font-size: 2.2rem; line-height: 1; }
.points-chip{
  background: rgba(255,255,255,.15);
  color: #fff;
  padding: .5rem .9rem;
  border-radius: 999px;
  display: inline-flex; align-items:center; gap:.25rem;
}

/* ===== Toolbar ===== */
.month-title{ font-weight: 700; }
.legend{ font-size: .9rem; display:flex; align-items:center; gap:.2rem; flex-wrap: wrap; }
.legend-dot{ display:inline-block; width:.8rem; height:.8rem; border-radius:50%; }

/* ===== Search glow ===== */
.search-group.searching{
  box-shadow: 0 0 0 4px rgba(255,255,255,.2), 0 10px 30px rgba(0,0,0,.18) !important;
  animation: pulseGlow 1s ease-in-out infinite alternate;
}
@keyframes pulseGlow { from { transform:translateZ(0); } to { transform:translateZ(0) scale(1.01); } }

/* ===== Calendar ===== */
.calendar-wrapper { min-height: calc(100vh - var(--navbar-h, 56px) - 220px); }
:deep(.fc){ font-size: clamp(.88rem, 1.4vw, .95rem); }
:deep(.fc .fc-daygrid-day-number){ font-weight: 600; }
:deep(.fc .fc-daygrid-event .fc-event-main){ pointer-events: auto; }
:deep(.fc .fc-event){ cursor: default; }

/* Colores de celdas */
:deep(.fc .fc-daygrid-day.has-entry){ background: rgba(25,135,84,.12); }
:deep(.fc .fc-daygrid-day.missing-entry){ background: rgba(220,53,69,.10); }
:deep(.fc .fc-daygrid-day.fc-day-today){ outline: 2px dashed rgba(0,0,0,.2); }
:deep(.fc .fc-daygrid-day.search-hit){
  position: relative; animation: hitBlink .9s ease-in-out infinite alternate;
}
@keyframes hitBlink {
  from { box-shadow: inset 0 0 0 2px rgba(255,193,7,.0), 0 0 0 0 rgba(255,193,7,.0); }
  to   { box-shadow: inset 0 0 0 2px rgba(255,193,7,.75), 0 6px 24px rgba(255,193,7,.25); }
}

/* Evento */
:deep(.fc .bitacora-event){
  border:0; background: rgba(13,110,253,.12); padding:2px 6px; border-radius:10px;
}
:deep(.fc .bitacora-event .fc-title-text){
  white-space:nowrap; text-overflow:ellipsis; overflow:hidden; display:inline-block; max-width:110px;
}
@media (min-width: 992px){ :deep(.fc .bitacora-event .fc-title-text){ max-width:180px; } }
:deep(.fc .bitacora-event .match){ filter:saturate(1.15); }

/* Botones mini */
:deep(.btn-ev-edit), :deep(.btn-ev-del){
  --bs-btn-padding-y: .05rem; --bs-btn-padding-x: .25rem; --bs-btn-font-size: .75rem; border-radius: .35rem;
}

/* ===== SweetAlert theme (verde difuminado) ===== */
:deep(.swal2-popup.swal2-responsive){ width:min(720px, 95vw); }
:deep(.swal2-mindora){
  background:
    radial-gradient(120% 120% at 10% 10%, #dbf7e6 0%, rgba(219,247,230,.85) 40%, rgba(219,247,230,.7) 60%),
    linear-gradient(180deg, #c8f1dc 0%, #eafbef 100%);
  border: 0;
  border-radius: 24px;
  box-shadow: 0 18px 48px rgba(16,50,36,.18);
  padding: clamp(1rem, 3vw, 1.5rem);
}
:deep(.swal2-mindora .swal2-title){
  font-weight: 800; color:#0f3d2e; line-height:1.2; font-size: clamp(1.15rem, 2.2vw, 1.45rem);
}
:deep(.swal2-mindora .swal2-image){
  margin-bottom: .5rem; border-radius: 16px;
  box-shadow: 0 10px 24px rgba(16,50,36,.08);
}
:deep(.swal2-mindora .swal2-input),
:deep(.swal2-mindora .swal2-textarea){
  border-radius: 14px !important; border:1px solid #cfe9da; background:#ffffffcf;
}
/* — SEPARACIÓN ENTRE BOTONES — */
:deep(.swal2-actions.swal2-actions-spaced){
  gap: 0.9rem !important;
  display:flex; flex-wrap:wrap; justify-content:center;
}
:deep(.swal2-actions.swal2-actions-spaced .btn){ padding-inline: 1rem; }

/* Chips */
.chip{
  border:1px solid #d1e7dd; border-radius:999px; padding:.4rem .8rem; background:#fff; font-weight:600;
  transition:transform .15s, box-shadow .15s, background .15s;
}
.chip:hover{ transform:translateY(-1px); box-shadow:0 6px 18px rgba(0,0,0,.08); }
.chip.active{ background:#e6ffe6; border-color:#86efac; }

/* Botón degradado */
.btn-gradient{ background: linear-gradient(90deg, #16a34a, #2563eb); color:#fff; border:0; }
.btn-gradient:hover{ filter: brightness(0.95); }
.circle-btn{ border-radius:999px; }

/* Responsive */
@media (max-width: 576px){
  .header-hero { border-bottom-left-radius: .75rem; border-bottom-right-radius: .75rem; }
  .legend { font-size: .85rem; }
  .points-chip{ align-self: stretch; justify-content:center; }
}
</style>
