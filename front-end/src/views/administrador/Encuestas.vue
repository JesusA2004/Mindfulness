<!-- src/views/encuestas/CrudPanel.vue -->
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
          <div class="card h-100 item-card shadow-sm">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <h5 class="card-title mb-0 text-truncate fw-bold" :title="item.titulo">
                  {{ item.titulo }}
                </h5>
                <span class="badge rounded-pill bg-status bg-success">Encuesta</span>
              </div>

              <p class="card-text clamp-3 mb-2" v-if="item.descripcion">{{ item.descripcion }}</p>

              <div class="small text-muted">
                <i class="bi bi-clock-history me-1"></i>
                {{ item.duracion_estimada ? item.duracion_estimada + ' min' : '—' }}
              </div>
              <div class="small text-muted mt-1">
                <i class="bi bi-calendar-event me-1"></i>
                {{ formatDateRange(item.fechaAsignacion, item.fechaFinalizacion) }}
              </div>
              <div class="small text-muted mt-1">
                <i class="bi bi-list-check me-1"></i>
                {{ (item.cuestionario?.length || 0) }} pregunta(s)
              </div>
            </div>

            <div class="card-footer bg-transparent border-0 pt-0 pb-3 px-3">
              <div class="d-flex flex-column flex-md-row gap-2">
                <button
                  class="btn btn-outline-secondary btn-sm flex-fill btn-with-label"
                  @click="openView(item)"
                  data-bs-toggle="tooltip"
                  title="Consultar"
                >
                  <i class="bi bi-eye me-1"></i>
                  <span>Consultar</span>
                </button>
                <button
                  class="btn btn-outline-primary btn-sm flex-fill btn-with-label"
                  @click="openEdit(item)"
                  data-bs-toggle="tooltip"
                  title="Modificar"
                >
                  <i class="bi bi-pencil-square me-1"></i>
                  <span>Modificar</span>
                </button>
                <button
                  class="btn btn-outline-danger btn-sm flex-fill btn-with-label"
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
              Intenta con otra búsqueda o registra una nueva encuesta.
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
      <div class="modal-dialog modal-dialog-centered modal-xl modal-fit">
        <div class="modal-content modal-flex border-0 shadow-lg">
          <div class="modal-header border-0 sticky-top bg-white">
            <h5 class="modal-title fw-bold">Detalle de la encuesta</h5>
            <button type="button" class="btn-close" @click="hideModal('view')" aria-label="Cerrar"></button>
          </div>

          <div class="modal-body modal-body-safe">
            <!-- Sección: Título + toggle resto -->
            <div class="section-header" @click="viewToggle.meta = !viewToggle.meta">
              <div class="d-flex align-items-center gap-2">
                <strong class="fs-5">{{ selected?.titulo || '—' }}</strong>
              </div>
              <i class="bi" :class="viewToggle.meta ? 'bi-chevron-up rotate' : 'bi-chevron-down'"></i>
            </div>
            <transition name="fade">
              <div v-show="viewToggle.meta" class="section-body">
                <dl class="row gy-2 mb-0">
                  <dt class="col-sm-3">Descripción</dt>
                  <dd class="col-sm-9">{{ selected?.descripcion || '—' }}</dd>

                  <dt class="col-sm-3">Fechas</dt>
                  <dd class="col-sm-9">{{ formatDateRange(selected?.fechaAsignacion, selected?.fechaFinalizacion) }}</dd>

                  <dt class="col-sm-3">Duración estimada</dt>
                  <dd class="col-sm-9">{{ selected?.duracion_estimada ? selected?.duracion_estimada + ' min' : '—' }}</dd>
                </dl>
              </div>
            </transition>

            <!-- Sección: Cuestionario -->
            <div class="section-header mt-3" @click="viewToggle.cuestionario = !viewToggle.cuestionario">
              <div class="d-flex align-items-center gap-2">
                <strong>Cuestionario ({{ (selected?.cuestionario?.length || 0) }})</strong>
              </div>
              <i class="bi" :class="viewToggle.cuestionario ? 'bi-chevron-up rotate' : 'bi-chevron-down'"></i>
            </div>
            <transition name="fade">
              <div v-show="viewToggle.cuestionario" class="section-body">
                <div v-if="(selected?.cuestionario?.length || 0) === 0" class="text-muted">—</div>
                <div v-for="(q, i) in (selected?.cuestionario || [])" :key="q._id || i" class="q-accordion">
                  <div class="q-header" @click="toggleViewQuestion(i)">
                    <div class="fw-semibold">Pregunta #{{ i + 1 }}</div>
                    <div class="text-truncate small">{{ q.pregunta || '—' }}</div>
                    <i class="bi ms-auto" :class="viewToggle.qOpen[i] ? 'bi-chevron-up rotate' : 'bi-chevron-down'"></i>
                  </div>
                  <transition name="fade">
                    <div v-show="viewToggle.qOpen[i]" class="q-body">
                      <div class="text-muted small mb-1">Tipo: {{ labelTipo(q.tipo) }}</div>
                      <div v-if="(q.opciones?.length || 0) > 0">
                        <div class="small text-muted">Opciones:</div>
                        <ul class="small mb-0">
                          <li v-for="(op, k) in q.opciones" :key="k">{{ op }}</li>
                        </ul>
                      </div>
                    </div>
                  </transition>
                </div>
              </div>
            </transition>

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
            <h5 class="modal-title fw-bold">{{ isEditing ? 'Modificar encuesta' : 'Registrar encuesta' }}</h5>
            <button type="button" class="btn-close" @click="hideModal('form')" aria-label="Cerrar"></button>
          </div>

          <div class="modal-body modal-body-safe">
            <!-- Sección: Título de la encuesta -->
            <div class="card mb-3">
              <div class="card-body">
                <label class="form-label">
                  Título <span class="text-danger">*</span>
                  <i class="bi bi-info-circle ms-1 text-primary"
                     data-bs-toggle="tooltip"
                     title="Nombre visible de la encuesta. Debe ser único."></i>
                </label>
                <div class="d-flex align-items-center gap-2">
                  <input v-model.trim="form.titulo" type="text" class="form-control" required maxlength="150" />
                  <button type="button" class="btn btn-light btn-sm shadow-sm" @click="ui.meta = !ui.meta" :aria-expanded="ui.meta">
                    <i class="bi" :class="ui.meta ? 'bi-chevron-up rotate' : 'bi-chevron-down'"></i>
                  </button>
                </div>

                <transition name="slide-fade">
                  <div v-show="ui.meta" class="mt-3">
                    <div class="row g-3">
                      <div class="col-12 col-lg-6">
                        <label class="form-label">
                          Fecha de inicio
                          <i class="bi bi-info-circle ms-1 text-primary"
                             data-bs-toggle="tooltip"
                             title="Fecha a partir de la cual la encuesta estará disponible. Si la dejas vacía, el sistema puede asignar la fecha de creación."></i>
                        </label>
                        <input v-model="form.fechaAsignacion" type="date" class="form-control" />
                      </div>
                      <div class="col-12 col-lg-6">
                        <label class="form-label">
                          Fecha de fin
                          <i class="bi bi-info-circle ms-1 text-primary"
                             data-bs-toggle="tooltip"
                             title="Último día en que se puede responder. Debe ser mayor o igual a la fecha de inicio."></i>
                        </label>
                        <input v-model="form.fechaFinalizacion" type="date" class="form-control" />
                      </div>
                      <div class="col-12 col-lg-6">
                        <label class="form-label">
                          Duración estimada (minutos)
                          <i class="bi bi-info-circle ms-1 text-primary"
                             data-bs-toggle="tooltip"
                             title="Tiempo aproximado que tardará un usuario en responder. Solo valores enteros positivos."></i>
                        </label>
                        <input v-model.number="form.duracion_estimada" type="number" min="1" step="1" class="form-control" />
                      </div>
                      <div class="col-12">
                        <label class="form-label">
                          Descripción
                          <i class="bi bi-info-circle ms-1 text-primary"
                             data-bs-toggle="tooltip"
                             title="Contexto breve para el encuestado sobre el objetivo de la encuesta."></i>
                        </label>
                        <textarea v-model.trim="form.descripcion" rows="3" class="form-control" maxlength="500"></textarea>
                      </div>
                    </div>
                  </div>
                </transition>
              </div>
            </div>

            <!-- Sección: Cuestionario -->
            <div class="d-flex align-items-center justify-content-between mb-2">
              <h6 class="mb-0 fw-bold">
                Cuestionario ({{ form.cuestionario.length }})
                <i class="bi bi-info-circle ms-1 text-primary"
                   data-bs-toggle="tooltip"
                   title="Agrega preguntas de selección múltiple con al menos dos opciones."></i>
              </h6>
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
                          <label class="form-label">
                            Texto de la pregunta <span class="text-danger">*</span>
                            <i class="bi bi-info-circle ms-1 text-primary"
                               data-bs-toggle="tooltip"
                               title="Enunciado claro de lo que deseas preguntar."></i>
                          </label>
                          <input v-model.trim="q.pregunta" type="text" class="form-control" required />
                        </div>

                        <!-- Tipo fijo: selección múltiple -->
                        <div class="mb-3">
                          <label class="form-label">
                            Tipo de pregunta
                            <i class="bi bi-info-circle ms-1 text-primary"
                               data-bs-toggle="tooltip"
                               title="Solo se admite selección múltiple (el encuestado puede elegir varias opciones)."></i>
                          </label>
                          <select v-model="q.tipo" class="form-select" required disabled>
                            <option value="seleccion_multiple">Selección múltiple (varias respuestas)</option>
                          </select>
                          <input type="hidden" v-model="q.tipo" />
                        </div>

                        <div class="mb-2">
                          <label class="form-label">
                            Opciones
                            <i class="bi bi-info-circle ms-1 text-primary"
                               data-bs-toggle="tooltip"
                               title="Lista de posibles respuestas. Debe haber al menos dos opciones no vacías."></i>
                          </label>
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
import { useEncuestasCrud } from '@/assets/js/useEncuestasCrud';

const {
  // estado y listas
  items, isLoading, hasMore, filteredItems, page,
  // búsqueda
  searchQuery, onInstantSearch, clearSearch,
  // utilidad
  getId, formatDateRange, labelTipo,
  // modales y refs
  viewModalRef, formModalRef, hideModal,
  // acciones de lista/paginación
  loadMore,
  // selección y UI
  selected, ui, viewToggle, isEditing, saving, form,
  // abrir/editar/ver
  openView, openCreate, openEdit,
  // cuestionario
  addPregunta, removePregunta, toggleQuestion, needsOptions, addOpcion, removeOpcion, onChangeTipo, toggleViewQuestion,
  // submit/eliminar
  onSubmit, confirmDelete, modifyFromView, deleteFromView,
} = useEncuestasCrud();
</script>

<style scoped>
@import '@/assets/css/Crud.css';
</style>
