<!-- src/views/administrador/TestsEmocionales.vue -->
<template>
  <main class="panel-wrapper">
    <!-- ======= Toolbar: Búsqueda + Nuevo ======= -->
    <div class="container-fluid toolbar px-3 px-lg-2">
      <div class="row g-2 align-items-center">
        <!-- Barra de búsqueda -->
        <div class="col-12 col-lg-8">
          <div
            class="input-group input-group-lg search-group shadow-sm rounded-pill"
            role="search"
            aria-label="Buscador de tests"
          >
            <span class="input-group-text rounded-start-pill">
              <i class="bi bi-search"></i>
            </span>

            <input
              v-model.trim="searchQuery"
              type="search"
              class="form-control search-input"
              placeholder="Buscar test por nombre o descripción…"
              @input="onInstantSearch"
              aria-label="Buscar por nombre o descripción"
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

        <!-- Botón +Nuevo -->
        <div class="col-12 col-lg-4 text-lg-end mt-2 mt-lg-0">
          <button
            class="btn btn-gradient fw-semibold shadow-sm rounded-pill btn-new px-3 py-2"
            @click="openCreate"
          >
            <i class="bi bi-plus-lg me-1"></i>
            <span class="d-inline d-sm-inline">Nuevo</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ======= Grid de Cards ======= -->
    <div class="container-fluid px-3 px-lg-2">
      <div class="row g-3 row-cols-1 row-cols-sm-2 row-cols-lg-3">
        <div v-for="item in filteredItems" :key="getId(item)" class="col">
          <div class="card h-100 item-card shadow-sm hover-raise">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <h5 class="card-title mb-0 text-truncate fw-bold" :title="item.nombre">
                  {{ item.nombre }}
                </h5>
                <span class="badge rounded-pill bg-status bg-success">Test</span>
              </div>

              <p class="card-text clamp-3 mb-2" v-if="item.descripcion">{{ item.descripcion }}</p>

              <div class="small text-muted">
                <i class="bi bi-clock-history me-1"></i>
                {{ item.duracion_estimada ? item.duracion_estimada + ' min' : '—' }}
              </div>
              <div class="small text-muted mt-1">
                <i class="bi bi-calendar-event me-1"></i>
                {{ formatDate(item.fechaAplicacion) }}
              </div>
              <div class="small text-muted mt-1">
                <i class="bi bi-list-check me-1"></i>
                {{ (item.cuestionario?.length || 0) }} pregunta(s)
              </div>
            </div>

            <div class="card-footer bg-transparent border-0 pt-0 pb-3 px-3">
              <div class="btn-grid">
                <button
                  class="btn btn-outline-secondary btn-sm flex-fill btn-with-label w-100"
                  @click="openView(item)"
                  data-bs-toggle="tooltip"
                  title="Consultar"
                >
                  <i class="bi bi-eye me-1"></i>
                  <span>Consultar</span>
                </button>

                <!-- NUEVO: Ver respuestas -->
                <button
                  class="btn btn-outline-info btn-sm flex-fill btn-with-label w-100"
                  @click="viewAnswers(item)"
                  data-bs-toggle="tooltip"
                  title="Ver respuestas"
                >
                  <i class="bi bi-bar-chart-line me-1"></i>
                  <span>Ver respuestas</span>
                </button>

                <button
                  class="btn btn-outline-primary btn-sm flex-fill btn-with-label w-100"
                  @click="openEdit(item)"
                  data-bs-toggle="tooltip"
                  title="Modificar"
                >
                  <i class="bi bi-pencil-square me-1"></i>
                  <span>Modificar</span>
                </button>
                <button
                  class="btn btn-outline-danger btn-sm flex-fill btn-with-label w-100"
                  @click="confirmDelete(item)"
                  data-bs-toggle="tooltip"
                  title="Eliminar"
                >
                  <i class="bi bi-trash me-1"></i>
                  <span>Eliminar</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Vacío -->
        <div v-if="!isLoading && filteredItems.length === 0" class="col-12">
          <div class="alert alert-light border d-flex align-items-center gap-2">
            <i class="bi bi-inbox text-secondary fs-4"></i>
            <div>
              <strong>Sin resultados.</strong>
              Intenta con otra búsqueda o registra un nuevo test.
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
            <div class="card-footer bg-transparent border-0 pt-0 pb-3 px-3">
              <div class="d-flex gap-2">
                <span class="placeholder col-4"></span>
                <span class="placeholder col-4"></span>
                <span class="placeholder col-4"></span>
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

    <!-- ======= Modales ======= -->

    <!-- Modal: Consulta -->
    <div class="modal fade" ref="viewModalRef" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable modal-fullscreen-sm-down modal-fit">
        <div class="modal-content modal-flex border-0 shadow-lg">
          <div class="modal-header border-0 sticky-top bg-white">
            <div class="d-flex align-items-center gap-3">
              <div class="avatar-pill">
                <i class="bi bi-journal-check"></i>
              </div>
              <div>
                <h5 class="modal-title fw-bold mb-0">{{ selected?.nombre || 'Detalle del test' }}</h5>
                <div class="small text-muted">
                  {{ (selected?.cuestionario?.length || 0) }} pregunta(s) •
                  {{ selected?.duracion_estimada ? selected?.duracion_estimada + ' min' : 'Duración no definida' }}
                </div>
              </div>
            </div>
            <button type="button" class="btn-close" @click="hideModal('view')" aria-label="Cerrar"></button>
          </div>

          <div class="modal-body modal-body-safe">
            <!-- Meta info -->
            <div class="meta-wrap">
              <div class="meta-chip">
                <i class="bi bi-calendar-event me-1"></i> {{ formatDate(selected?.fechaAplicacion) }}
              </div>
              <div class="meta-chip">
                <i class="bi bi-clock-history me-1"></i>
                {{ selected?.duracion_estimada ? selected?.duracion_estimada + ' min' : '—' }}
              </div>
              <div class="meta-chip" v-if="selected?.descripcion">
                <i class="bi bi-info-circle me-1"></i> {{ selected?.descripcion }}
              </div>
            </div>

            <!-- Controles locales -->
            <div class="viewer-toolbar">
              <div class="input-group input-group-sm viewer-search">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input
                  v-model.trim="qSearch"
                  type="search"
                  class="form-control"
                  placeholder="Buscar dentro del cuestionario…"
                  aria-label="Buscar en preguntas"
                />
                <button
                  v-if="qSearch"
                  class="btn btn-outline-secondary"
                  @click="qSearch=''"
                  aria-label="Limpiar búsqueda de preguntas"
                >
                  <i class="bi bi-x-lg"></i>
                </button>
              </div>

              <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary btn-sm" @click="expandAll">
                  <i class="bi bi-arrows-expand me-1"></i> Expandir todo
                </button>
                <button class="btn btn-outline-secondary btn-sm" @click="collapseAll">
                  <i class="bi bi-arrows-collapse me-1"></i> Contraer todo
                </button>
              </div>
            </div>

            <!-- Progreso -->
            <div class="progress-wrap mb-3" v-if="totalQuestions > 0">
              <div class="d-flex justify-content-between small text-muted mb-1">
                <span>Mostrando {{ visibleQuestions }} de {{ totalQuestions }} preguntas</span>
                <span>{{ Math.round((visibleQuestions/totalQuestions)*100) }}%</span>
              </div>
              <div class="progress" role="progressbar" :aria-valuenow="(visibleQuestions/totalQuestions)*100" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar" :style="{ width: (visibleQuestions/totalQuestions)*100 + '%' }"></div>
              </div>
            </div>

            <!-- Preguntas -->
            <div class="row g-3">
              <div
                v-for="(q, i) in filteredQuestions"
                :key="q._id || i"
                class="col-12"
              >
                <div class="q-card shadow-sm">
                  <div
                    class="q-head"
                    @click="toggleViewQuestion(iMapped(i))"
                    :aria-expanded="viewToggle.qOpen[iMapped(i)]"
                  >
                    <!-- IZQ: índice + título -->
                    <div class="q-left">
                      <span class="q-index">#{{ i + 1 }}</span>
                      <span class="q-title" :title="q.pregunta || '—'">
                        {{ q.pregunta || '—' }}
                      </span>
                    </div>

                    <!-- DER: badge + chevron -->
                    <div class="q-right">
                      <span class="badge rounded-pill q-type" :class="tipoBadgeClass(q.tipo)">
                        <i :class="tipoIcon(q.tipo)" class="me-1"></i>{{ labelTipo(q.tipo) }}
                      </span>
                      <i class="bi ms-2" :class="viewToggle.qOpen[iMapped(i)] ? 'bi-chevron-up rotate' : 'bi-chevron-down'"></i>
                    </div>
                  </div>

                  <transition name="fade">
                    <div v-show="viewToggle.qOpen[iMapped(i)]" class="q-body">
                      <template v-if="(q.opciones?.length || 0) > 0">
                        <div class="small text-muted mb-2">Opciones:</div>
                        <div class="q-chips">
                          <span v-for="(op, k) in q.opciones" :key="k" class="chip">
                            <i class="bi bi-dot me-1"></i>{{ op }}
                          </span>
                        </div>
                      </template>
                      <template v-else>
                        <div class="text-muted fst-italic small">Respuesta abierta</div>
                      </template>
                    </div>
                  </transition>
                </div>
              </div>

              <div v-if="filteredQuestions.length === 0" class="col-12">
                <div class="alert alert-light border d-flex align-items-center gap-2">
                  <i class="bi bi-emoji-neutral text-secondary fs-5"></i>
                  <div>
                    <strong>Sin coincidencias.</strong> Ajusta tu búsqueda interna.
                  </div>
                </div>
              </div>
            </div>

            <div class="safe-bottom-space" aria-hidden="true"></div>
          </div>

          <div class="modal-footer modal-footer-sticky">
            <div class="d-grid d-md-flex w-100 gap-2">
              <div class="d-grid d-md-flex gap-2">
                <button type="button" class="btn btn-outline-primary" @click="modifyFromView">
                  <i class="bi bi-pencil-square me-1"></i> Modificar
                </button>
                <button type="button" class="btn btn-outline-danger" @click="deleteFromView">
                  <i class="bi bi-trash me-1"></i> Eliminar
                </button>
              </div>
              <button class="btn btn-secondary ms-md-auto" @click="hideModal('view')">Cerrar</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal: Form (Crear/Editar) -->
    <div class="modal fade" ref="formModalRef" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-xl modal-fit">
        <form class="modal-content modal-flex border-0 shadow-lg" @submit.prevent="onSubmit">
          <div class="modal-header border-0 sticky-top bg-white">
            <h5 class="modal-title fw-bold">{{ isEditing ? 'Modificar test' : 'Registrar test' }}</h5>
            <button type="button" class="btn-close" @click="hideModal('form')" aria-label="Cerrar"></button>
          </div>

          <div class="modal-body modal-body-safe">
            <!-- Sección: Título del test -->
            <div class="card mb-3">
              <div class="card-body">
                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                <div class="d-flex align-items-center gap-2">
                  <input v-model.trim="form.nombre" type="text" class="form-control" required maxlength="150" />
                  <button type="button" class="btn btn-light btn-sm shadow-sm" @click="ui.meta = !ui.meta" :aria-expanded="ui.meta">
                    <i class="bi" :class="ui.meta ? 'bi-chevron-up rotate' : 'bi-chevron-down'"></i>
                  </button>
                </div>

                <transition name="slide-fade">
                  <div v-show="ui.meta" class="mt-3">
                    <div class="row g-3">
                      <div class="col-12 col-lg-6">
                        <label class="form-label">Fecha de aplicación</label>
                        <input v-model="form.fechaAplicacion" type="date" class="form-control" />
                      </div>
                      <div class="col-12 col-lg-6">
                        <label class="form-label">Duración estimada (minutos)</label>
                        <input v-model.number="form.duracion_estimada" type="number" min="1" step="1" class="form-control" />
                      </div>
                      <div class="col-12">
                        <label class="form-label">Descripción</label>
                        <textarea v-model.trim="form.descripcion" rows="3" class="form-control" maxlength="500"></textarea>
                      </div>
                    </div>
                  </div>
                </transition>
              </div>
            </div>

            <!-- Sección: Cuestionario -->
            <div class="d-flex align-items-center justify-content-between mb-2">
              <h6 class="mb-0 fw-bold">Cuestionario ({{ form.cuestionario.length }})</h6>
              <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-success" @click="addPregunta">
                  <i class="bi bi-plus-circle me-1"></i> Agregar pregunta
                </button>
                <button type="button" class="btn btn-light btn-sm shadow-sm" @click="ui.cuestionario = !ui.cuestionario" :aria-expanded="ui.cuestionario">
                  <i class="bi" :class="ui.cuestionario ? 'bi-chevron-up rotate' : 'bi-chevron-down'"></i>
                </button>
              </div>
            </div>

            <transition name="slide-fade">
              <div v-show="ui.cuestionario">
                <div v-if="form.cuestionario.length === 0" class="text-muted small">
                  No hay preguntas. Agrega al menos una.
                </div>

                <div v-for="(q, idx) in form.cuestionario" :key="q.__key" class="card mb-3">
                  <div class="card-body">
                    <div class="d-flex align-items-center gap-2">
                      <div class="fw-semibold flex-grow-1">Pregunta #{{ idx + 1 }}</div>
                      <button type="button" class="btn btn-light btn-sm" @click="toggleQuestion(idx)">
                        <i class="bi" :class="ui.qOpen[idx] ? 'bi-chevron-up rotate' : 'bi-chevron-down'"></i>
                      </button>
                      <button type="button" class="btn btn-sm btn-outline-danger" @click="removePregunta(idx)">
                        <i class="bi bi-x-lg me-1"></i> Quitar
                      </button>
                    </div>

                    <transition name="slide-fade">
                      <div v-show="ui.qOpen[idx]" class="mt-3">
                        <div class="mb-3">
                          <label class="form-label">Texto de la pregunta <span class="text-danger">*</span></label>
                          <input v-model.trim="q.pregunta" type="text" class="form-control" required />
                        </div>

                        <div class="mb-3">
                          <label class="form-label">Tipo de pregunta <span class="text-danger">*</span></label>
                          <select v-model="q.tipo" class="form-select" required @change="onChangeTipo(q)">
                            <option disabled value="">Selecciona un tipo</option>
                            <option value="seleccion_multiple">Selección múltiple (varias respuestas)</option>
                            <option value="respuesta_abierta">Respuesta abierta</option>
                          </select>
                        </div>

                        <div class="mb-2" v-if="needsOptions(q)">
                          <label class="form-label">Opciones</label>
                          <div class="row g-2 align-items-center mb-2" v-for="(op, i) in q.opciones" :key="i">
                            <div class="col">
                              <input v-model.trim="q.opciones[i]" type="text" class="form-control" placeholder="Texto de opción" />
                            </div>
                            <div class="col-auto">
                              <button type="button" class="btn btn-outline-danger btn-sm" @click="removeOpcion(q, i)">
                                <i class="bi bi-dash-circle"></i>
                              </button>
                            </div>
                          </div>
                          <button type="button" class="btn btn-outline-secondary btn-sm" @click="addOpcion(q)">
                            <i class="bi bi-plus-circle me-1"></i> Agregar opción
                          </button>
                          <div class="form-text">Agrega al menos 2 opciones.</div>
                        </div>
                      </div>
                    </transition>
                  </div>
                </div>
              </div>
            </transition>

            <div class="safe-bottom-space" aria-hidden="true"></div>
          </div>

          <div class="modal-footer modal-footer-sticky">
            <button type="button" class="btn btn-outline-secondary" @click="hideModal('form')">Cancelar</button>
            <button type="submit" class="btn btn-gradient" :disabled="saving">
              <span v-if="!saving">{{ isEditing ? 'Guardar cambios' : 'Registrar' }}</span>
              <span v-else class="spinner-border spinner-border-sm ms-1" role="status" aria-hidden="true"></span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </main>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useTestsCrud } from '@/assets/js/useTestsCrud';

const {
  items, isLoading, hasMore, filteredItems, page,
  searchQuery, onInstantSearch, clearSearch,
  getId, formatDate, labelTipo,
  viewModalRef, formModalRef, hideModal,
  loadMore,
  selected, ui, viewToggle, isEditing, saving, form,
  openView, openCreate, openEdit,
  addPregunta, removePregunta, toggleQuestion, needsOptions, addOpcion, removeOpcion, onChangeTipo, toggleViewQuestion,
  onSubmit, confirmDelete, modifyFromView, deleteFromView,
  // si aún no lo tienes, puedes implementar viewAnswers en tu composable
  viewAnswers
} = useTestsCrud();

/* ==========  Controles del visor ========== */
const qSearch = ref('');

const questions = computed(() => selected?.value?.cuestionario || []);
const totalQuestions = computed(() => questions.value.length);

const filteredQuestions = computed(() => {
  if (!qSearch.value) return questions.value;
  const q = qSearch.value.toLowerCase();
  return questions.value.filter(p =>
    (p?.pregunta || '').toLowerCase().includes(q) ||
    (p?.opciones || []).some(op => (op || '').toLowerCase().includes(q))
  );
});

const visibleQuestions = computed(() => filteredQuestions.value.length);

const iMapped = (visibleIndex) => {
  if (!qSearch.value) return visibleIndex;
  const q = filteredQuestions.value[visibleIndex];
  return questions.value.indexOf(q);
};

const expandAll = () => {
  ensureQOpenSize();
  filteredQuestions.value.forEach((_, i) => viewToggle.qOpen[iMapped(i)] = true);
};
const collapseAll = () => {
  ensureQOpenSize();
  filteredQuestions.value.forEach((_, i) => viewToggle.qOpen[iMapped(i)] = false);
};

function ensureQOpenSize () {
  if (!Array.isArray(viewToggle.qOpen)) viewToggle.qOpen = [];
  const need = Math.max(viewToggle.qOpen.length, questions.value.length);
  for (let i = 0; i < need; i++) {
    if (typeof viewToggle.qOpen[i] !== 'boolean') viewToggle.qOpen[i] = false;
  }
}

/* Iconos/clases por tipo */
const tipoIcon = (tipo) => {
  switch (tipo) {
    case 'seleccion_multiple': return 'bi bi-ui-radios';
    case 'respuesta_abierta':  return 'bi bi-chat-text';
    default:                   return 'bi bi-question-circle';
  }
};
const tipoBadgeClass = (tipo) => {
  switch (tipo) {
    case 'seleccion_multiple': return 'bg-type-multi';
    case 'respuesta_abierta':  return 'bg-type-open';
    default:                   return 'bg-secondary-subtle text-secondary';
  }
};
</script>

<style scoped>
@import '@/assets/css/Crud.css';

/* Hover */
.hover-raise { transition: transform .2s ease, box-shadow .2s ease; }
.hover-raise:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(0,0,0,.08); }

/* Modal header avatar */
.avatar-pill{
  width: 44px; height: 44px; display: grid; place-items: center; border-radius: 999px;
  background: var(--chip, #eef6ff); color: var(--ink-2, #2c4c86); box-shadow: inset 0 0 0 1px rgba(27,59,111,.08);
  font-size: 1.25rem;
}

/* Chips superiores */
.meta-wrap{ display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 1rem; }
.meta-chip{
  background: var(--chip, #eef6ff); color: var(--ink-2, #2c4c86); border: 1px solid var(--stroke, #e6eefc);
  padding: .35rem .65rem; border-radius: 999px; font-size: .85rem;
}

/* Toolbar del visor */
.viewer-toolbar{
  display: flex; gap: .75rem; align-items: center; justify-content: space-between;
  margin-bottom: .75rem; flex-wrap: wrap;
}
.viewer-search{ max-width: 420px; }

/* Progreso */
.progress-wrap .progress{ height: .6rem; }
.progress-wrap .progress-bar{ background: linear-gradient(90deg, #7cb8ff, #2c4c86); }

/* Tarjetas de preguntas */
.q-card{
  border: 1px solid var(--stroke, #e6eefc);
  border-radius: 16px;
  background: var(--card-b, #f8fbff);
}

/* HEAD de pregunta: ahora permite wrap y evita recortes */
.q-head{
  padding: .9rem 1rem;
  display: flex; align-items: center; justify-content: space-between; gap: .75rem;
  cursor: pointer; user-select: none; flex-wrap: wrap;
}
.q-head:hover{ background: #f1f6ff; }

/* Columna izquierda: índice + título */
.q-left{
  display: flex; align-items: center; gap: .5rem;
  min-width: 0;               /* importante para que el ellipsis funcione */
  flex: 1 1 auto;             /* que ocupe el ancho disponible */
}
.q-index{
  width: 32px; height: 32px; border-radius: 8px; display: grid; place-items: center; font-weight: 700;
  background: white; color: var(--ink, #1b3b6f); border: 1px solid var(--stroke, #e6eefc);
}
.q-title{
  font-weight: 600;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap; /* truncado suave en desktop */
}

/* Columna derecha: badge + chevron */
.q-right{
  display: flex; align-items: center; gap: .5rem;
  margin-left: auto; flex-shrink: 0;
}
.q-type{ font-weight: 600; }

.q-body{ padding: .5rem 1rem 1rem 1rem; }
.q-chips{ display: flex; flex-wrap: wrap; gap: .5rem; }
.chip{
  display: inline-flex; align-items: center; gap: .25rem;
  background: white; border: 1px solid var(--stroke,#e6eefc);
  padding: .35rem .6rem; border-radius: 999px; font-size: .9rem;
}

/* Badges de tipo */
.bg-type-multi{ background: #e7f7ef; color: #0f7a47; border: 1px solid #bce8d1; }
.bg-type-open{  background: #fff4e5; color: #8a4b00; border: 1px solid #ffe0b8; }

/* Animaciones */
.rotate{ transform: rotate(180deg); transition: transform .2s ease; }
.fade-enter-active, .fade-leave-active{ transition: opacity .18s ease; }
.fade-enter-from, .fade-leave-to{ opacity: 0; }

/* Ajustes menores */
.modal-fit .modal-content{ border-radius: 20px; }
.btn-with-label i{ vertical-align: -1px; }

/* Modal responsive: ancho máximo y márgenes */
.modal-fit .modal-dialog{ max-width: min(980px, 92vw); margin: 1rem auto; }
/* Evitar desbordes horizontales */
.modal-fit .modal-content{ overflow: hidden; }
/* Body con alto máximo y scroll interno */
.modal-body.modal-body-safe{ max-height: calc(100vh - 180px); overflow: auto; }

/* En móviles: badge/chevron bajan a segunda línea, título se mantiene legible */
@media (max-width: 576px){
  .modal-fit .modal-dialog{ margin: .5rem; }
  .q-right{ width: 100%; justify-content: flex-end; margin-top: .35rem; }
  .q-title{ white-space: normal; }  /* permite dos líneas si es necesario en móvil */
}
</style>
