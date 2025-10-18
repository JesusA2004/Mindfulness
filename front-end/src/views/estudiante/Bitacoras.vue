<template>
  <main class="bitacoras-calendar-page">
    <!-- ======= Header motivacional ======= -->
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

          <!-- Chip de puntos -->
          <div class="points-chip shadow-sm">
            <i class="bi bi-trophy-fill me-1"></i>
            <span class="fw-semibold">{{ puntos }}</span>
            <span class="ms-1">puntos</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ======= Toolbar secundaria ======= -->
    <div class="container-fluid px-3 px-lg-2 my-2">
      <div class="row g-2 align-items-center">
        <div class="col-12 col-lg-7 d-flex align-items-center gap-2">
          <div class="btn-group shadow-sm" role="group" aria-label="Navegación de calendario">
            <button class="btn btn-outline-secondary" @click="goPrev" title="Mes anterior">
              <i class="bi bi-chevron-left"></i>
            </button>
            <button class="btn btn-outline-secondary" @click="goToday" title="Hoy">Hoy</button>
            <button class="btn btn-outline-secondary" @click="goNext" title="Mes siguiente">
              <i class="bi bi-chevron-right"></i>
            </button>
          </div>

          <!-- Título de mes (fijado con currentDate, no leyendo directo del ref del calendario) -->
          <div class="ms-2 month-title">
            <i class="bi bi-calendar3 me-1"></i>
            {{ formatMonthLabel(currentDate) }}
          </div>

          <!-- Leyenda de colores -->
          <div class="legend ms-3">
            <span class="legend-dot bg-success"></span> Registrada
            <span class="legend-dot bg-danger ms-3"></span> Falta registrar
          </div>
        </div>

        <div class="col-12 col-lg-5 d-flex justify-content-lg-end mt-2 mt-lg-0">
          <button class="btn btn-gradient fw-semibold shadow-sm rounded-pill btn-new px-3"
                  @click="openCreateForToday">
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
            <FullCalendar
              ref="calendarRef"
              :options="calendarOptions"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- ======= Modal: Crear/Editar ======= -->
    <div class="modal fade" ref="formModalRef" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg modal-fit">
        <form class="modal-content modal-flex border-0 shadow-lg" @submit.prevent="onSubmit">
          <div class="modal-header border-0 sticky-top bg-white">
            <h5 class="modal-title fw-bold">{{ isEditing ? 'Modificar entrada' : 'Nueva entrada' }}</h5>
            <button type="button" class="btn-close" @click="hideModal('form')" aria-label="Cerrar"></button>
          </div>

          <div class="modal-body modal-body-safe">
            <div class="row g-3">
              <div class="col-12 col-md-8">
                <label class="form-label">
                  Título <span class="text-danger">*</span>
                </label>
                <input v-model.trim="form.titulo" type="text" class="form-control" maxlength="150" required />
                <div class="form-text">Ej. “Práctica de respiración consciente”.</div>
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label">
                  Estado emocional (emoji) <span class="text-danger">*</span>
                </label>
                <div class="emoji-grid">
                  <button
                    v-for="e in EMOJIS"
                    :key="e"
                    type="button"
                    class="emoji-btn"
                    :class="{ active: form.emoji === e }"
                    @click="form.emoji = e"
                    :title="`Emoción: ${e}`"
                  >{{ e }}</button>
                </div>
              </div>

              <div class="col-12">
                <label class="form-label">Descripción (opcional)</label>
                <textarea v-model.trim="form.descripcion" rows="4" class="form-control"
                          placeholder="¿Qué ocurrió? ¿Cómo te sentiste?"></textarea>
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label">Fecha</label>
                <input type="date" class="form-control" v-model="form.fecha" readonly />
                <div class="form-text">La fecha se asigna automáticamente según el día seleccionado.</div>
              </div>
            </div>

            <div class="safe-bottom-space" aria-hidden="true"></div>
          </div>

          <div class="modal-footer modal-footer-sticky">
            <button type="button" class="btn btn-outline-secondary" @click="hideModal('form')">Cancelar</button>
            <button type="submit" class="btn btn-gradient" :disabled="saving">
              <span v-if="!saving">{{ isEditing ? 'Guardar cambios' : 'Guardar' }}</span>
              <span v-else class="spinner-border spinner-border-sm ms-1" role="status" aria-hidden="true"></span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ======= Modal: Ver ======= -->
    <div class="modal fade" ref="viewModalRef" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg modal-fit">
        <div class="modal-content modal-flex border-0 shadow-lg">
          <div class="modal-header border-0 sticky-top bg-white">
            <h5 class="modal-title fw-bold">
              <span class="me-2" v-if="selected?.emoji">{{ selected.emoji }}</span>{{ selected?.titulo || '—' }}
            </h5>
            <button type="button" class="btn-close" @click="hideModal('view')" aria-label="Cerrar"></button>
          </div>

          <div class="modal-body modal-body-safe">
            <dl class="row gy-2 mb-0">
              <dt class="col-sm-3">Fecha</dt>
              <dd class="col-sm-9">{{ selected?.fecha || '—' }}</dd>

              <dt class="col-sm-3">Descripción</dt>
              <dd class="col-sm-9">{{ selected?.descripcion || '—' }}</dd>
            </dl>

            <div class="safe-bottom-space" aria-hidden="true"></div>
          </div>

          <div class="modal-footer modal-footer-sticky">
            <div class="d-grid d-md-flex w-100 gap-2">
              <div class="d-grid d-md-flex gap-2">
                <button type="button" class="btn btn-outline-primary" @click="modifyFromView">
                  <i class="bi bi-pencil-square me-1"></i> Modificar
                </button>
                <button
                  type="button"
                  class="btn btn-outline-danger"
                  :disabled="puntos <= 0"
                  :title="puntos <= 0 ? 'No puedes eliminar con 0 puntos' : 'Eliminar'"
                  @click="deleteFromView"
                >
                  <i class="bi bi-trash me-1"></i> Eliminar
                </button>
              </div>
              <button class="btn btn-secondary ms-md-auto" @click="hideModal('view')">Cerrar</button>
            </div>
          </div>
        </div>
      </div>
    </div>

  </main>
</template>

<script setup>
import { ref, reactive, computed, onMounted, nextTick, watch } from 'vue';
import Modal from 'bootstrap/js/dist/modal';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import { useBitacorasCalendar } from '@/assets/js/useBitacorasCalendar';

// Emojis
const EMOJIS = ['😀','😊','🙂','😌','😐','😕','😟','😢','😡','😴','😮','😬'];

// Composable
const {
  items, isLoading, month, year,
  puntos, puntosCargados, loadUserAndPoints,
  searchQuery, onInstantSearch, clearSearch,
  openCreate, openEdit, openView, hideModal,
  onSubmit, confirmDelete, modifyFromView, deleteFromView,
  form, isEditing, saving, selected,
  fetchMonth,
  titleWithoutEmoji, withEmojiPrefix, emojiFromTitle, toast, formatMonthLabel,
  formModalRef, viewModalRef
} = useBitacorasCalendar({ EMOJIS });

// FullCalendar ref y fecha visible segura
const calendarRef = ref(null);
const currentDate = ref(new Date()); // ← usamos esto para el título de mes

// Fechas con entrada (para colorear)
const datesWithEntry = computed(() => new Set(items.value.map(b => b.fecha)));

// Opciones calendario
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
  dateClick: (info) => openCreate(info.dateStr),
  eventClick: (info) => {
    const id = info.event.extendedProps._id;
    const item = items.value.find(x => x.id === id || x._id === id);
    if (item) openView(item);
  },
  eventClassNames: () => ['bitacora-event'],
  eventContent: (arg) => {
    const emoji = arg.event.extendedProps.emoji || '';
    const title = arg.event.title || '';
    const delDisabled = puntos.value <= 0 ? 'disabled aria-disabled="true" title="No puedes eliminar con 0 puntos"' : `title="Eliminar"`;
    return {
      html: `
        <div class="d-flex align-items-center gap-1">
          <span class="me-1">${emoji}</span>
          <span class="fc-title-text flex-grow-1">${title}</span>
          <button class="btn btn-xs btn-outline-primary btn-ev-edit" title="Modificar"><i class="bi bi-pencil"></i></button>
          <button class="btn btn-xs btn-outline-danger btn-ev-del" ${delDisabled}><i class="bi bi-trash"></i></button>
        </div>
      `
    };
  },
  eventDidMount: (info) => {
    const editBtn = info.el.querySelector('.btn-ev-edit');
    const delBtn  = info.el.querySelector('.btn-ev-del');
    if (editBtn) {
      editBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        const id = info.event.extendedProps._id;
        const item = items.value.find(x => x.id === id || x._id === id);
        if (item) openEdit(item);
      });
    }
    if (delBtn) {
      delBtn.addEventListener('click', async (e) => {
        e.stopPropagation();
        if (puntos.value <= 0) return;
        const id = info.event.extendedProps._id;
        const item = items.value.find(x => x.id === id || x._id === id);
        if (item) await confirmDelete(item);
      });
    }
  },
  dayCellClassNames: (arg) => {
    const api = calendarRef.value?.getApi?.();
    const current = api?.getDate?.();
    if (current) currentDate.value = current; // ← actualizamos el mes visible de forma segura
    const inMonth = current ? (arg.date.getMonth() === current.getMonth()) : true;
    const iso = arg.date.toISOString().slice(0,10);
    if (!inMonth) return [];
    return datesWithEntry.value.has(iso) ? ['has-entry'] : ['missing-entry'];
  }
});

// Eventos
const baseEvents = computed(() => {
  return items.value.map(b => ({
    id: b.id || b._id,
    _id: b.id || b._id,
    title: titleWithoutEmoji(b.titulo),
    emoji: emojiFromTitle(b.titulo),
    start: b.fecha,
    allDay: true
  }));
});

// Filtro búsqueda
const filteredEvents = computed(() => {
  const q = (searchQuery.value || '').toLowerCase().trim();
  if (!q) return baseEvents.value;
  return baseEvents.value.filter(ev => {
    const title = (ev.title || '').toLowerCase();
    const d = (ev.start || '').toString();
    return title.includes(q) || d.includes(q);
  });
});

// Navegación
async function syncFetchToCalendar() {
  const api = calendarRef.value?.getApi?.();
  if (!api) return;
  const current = api.getDate();
  currentDate.value = current;            // ← actualiza ref seguro
  const m = current.getMonth() + 1;
  const y = current.getFullYear();
  await fetchMonth(m, y);
}
function goPrev() { const api = calendarRef.value?.getApi?.(); api?.prev(); syncFetchToCalendar(); }
function goNext() { const api = calendarRef.value?.getApi?.(); api?.next(); syncFetchToCalendar(); }
function goToday(){ const api = calendarRef.value?.getApi?.(); api?.today(); syncFetchToCalendar(); }
function openCreateForToday() {
  const today = new Date();
  const yyyy = today.getFullYear();
  const mm = String(today.getMonth()+1).padStart(2, '0');
  const dd = String(today.getDate()).padStart(2, '0');
  openCreate(`${yyyy}-${mm}-${dd}`);
}

// Montaje
onMounted(async () => {
  await nextTick();
  if (formModalRef.value) new Modal(formModalRef.value, { backdrop: 'static' });
  if (viewModalRef.value) new Modal(viewModalRef.value, { backdrop: 'static' });
  await nextTick();
  await loadUserAndPoints();
  await syncFetchToCalendar();
});

// Refrescar eventos al cambiar items
watch(items, () => {
  const api = calendarRef.value?.getApi?.();
  api?.refetchEvents();
});
</script>

<style scoped>
@import '@/assets/css/Crud.css';

/* Header */
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
  display: inline-flex;
  align-items: center;
  gap: .25rem;
}

/* Toolbar secundaria */
.month-title{ font-weight: 700; }
.legend{ font-size: .9rem; display:flex; align-items:center; gap:.2rem; }
.legend-dot{ display:inline-block; width:.8rem; height:.8rem; border-radius:50%; }

/* Calendario */
.calendar-wrapper { min-height: calc(100vh - var(--navbar-h, 56px) - 220px); }
:deep(.fc) { font-size: 0.95rem; }
:deep(.fc .fc-daygrid-day-number){ font-weight: 600; }

/* Celdas coloreadas */
:deep(.fc .fc-daygrid-day.has-entry){ background: rgba(25,135,84,.12); }
:deep(.fc .fc-daygrid-day.missing-entry){ background: rgba(220,53,69,.10);}
:deep(.fc .fc-daygrid-day.fc-day-today){ outline: 2px dashed rgba(0,0,0,.2); }

/* Evento chip */
:deep(.fc .bitacora-event){
  border: 0;
  background: rgba(13,110,253,.12);
  padding: 2px 6px;
  border-radius: 10px;
}
:deep(.fc .bitacora-event .fc-title-text){
  white-space: nowrap; text-overflow: ellipsis; overflow: hidden; display: inline-block; max-width: 110px;
}
@media (min-width: 992px){
  :deep(.fc .bitacora-event .fc-title-text){ max-width: 180px; }
}

/* Botones mini en evento */
:deep(.btn-ev-edit), :deep(.btn-ev-del){
  --bs-btn-padding-y: .05rem;
  --bs-btn-padding-x: .25rem;
  --bs-btn-font-size: .75rem;
  border-radius: .35rem;
}

/* Emoji picker */
.emoji-grid{ display: grid; grid-template-columns: repeat(6, 1fr); gap: .35rem; }
.emoji-btn{
  line-height: 1; font-size: 1.25rem; padding: .35rem .45rem;
  border: 1px solid #dee2e6; border-radius: .5rem; background: #fff;
}
.emoji-btn.active{ outline: 2px solid var(--bs-primary); }

/* Modales */
.modal-fit{ width: 100%; max-width: 920px; }
.modal-body-safe{ max-height: min(70vh, 680px); overflow:auto; }

/* Botón degradado */
.btn-gradient{ background: linear-gradient(90deg, #5fbb97, #5563de); color: #fff; border: 0; }
.btn-gradient:hover{ filter: brightness(0.95); }
</style>
