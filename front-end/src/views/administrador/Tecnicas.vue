<!-- src/views/tecnicas/CrudPanel.vue -->
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
            aria-label="Buscador de técnicas"
          >
            <span class="input-group-text rounded-start-pill">
              <i class="bi bi-search"></i>
            </span>

            <input
              v-model.trim="searchQuery"
              type="search"
              class="form-control search-input"
              placeholder="Buscar técnica por nombre, categoría o duración…"
              @input="onInstantSearch"
              aria-label="Buscar por nombre, categoría o duración"
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
                <h5 class="card-title mb-0 text-truncate fw-bold" :title="item.nombre">
                  {{ item.nombre }}
                </h5>
                <span class="badge rounded-pill bg-status bg-success">Técnica</span>
              </div>

              <p class="card-text clamp-3 mb-2" v-if="item.descripcion">{{ item.descripcion }}</p>

              <div class="small text-muted">
                <i class="bi bi-diagram-3 me-1"></i> {{ item.categoria || '—' }}
              </div>
              <div class="small text-muted mt-1">
                <i class="bi bi-bar-chart-steps me-1"></i> Dificultad: {{ item.dificultad || '—' }}
              </div>
              <div class="small text-muted mt-1">
                <i class="bi bi-clock me-1"></i> {{ item.duracion ? item.duracion + ' min' : '—' }}
              </div>
              <div class="small text-muted mt-1">
                <i class="bi bi-paperclip me-1"></i> {{ (item.recursos?.length || 0) }} recurso(s)
              </div>
            </div>

            <div class="card-footer bg-transparent border-0 pt-0 pb-3 px-3">
              <div class="d-flex flex-column flex-md-row gap-2">
                <button
                  class="btn btn-outline-secondary btn-sm flex-fill btn-with-label"
                  @click="openView(item)"
                  title="Consultar"
                >
                  <i class="bi bi-eye me-1"></i>
                  <span>Consultar</span>
                </button>
                <button
                  class="btn btn-outline-primary btn-sm flex-fill btn-with-label"
                  @click="openEdit(item)"
                  title="Modificar"
                >
                  <i class="bi bi-pencil-square me-1"></i>
                  <span>Modificar</span>
                </button>
                <button
                  class="btn btn-outline-danger btn-sm flex-fill btn-with-label"
                  @click="confirmDelete(item)"
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
              Intenta con otra búsqueda o registra una nueva técnica.
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
            <h5 class="modal-title fw-bold">Detalle de la técnica</h5>
            <button type="button" class="btn-close" @click="hideModal('view')" aria-label="Cerrar"></button>
          </div>

          <div class="modal-body modal-body-safe">
            <!-- Meta -->
            <div class="section-header" @click="viewToggle.meta = !viewToggle.meta">
              <div class="d-flex align-items-center gap-2">
                <strong class="fs-5">{{ selected?.nombre || '—' }}</strong>
              </div>
              <i class="bi" :class="viewToggle.meta ? 'bi-chevron-up rotate' : 'bi-chevron-down'"></i>
            </div>
            <transition name="fade">
              <div v-show="viewToggle.meta" class="section-body">
                <dl class="row gy-2 mb-0">
                  <dt class="col-sm-3">Descripción</dt>
                  <dd class="col-sm-9">{{ selected?.descripcion || '—' }}</dd>

                  <dt class="col-sm-3">Categoría</dt>
                  <dd class="col-sm-9">{{ selected?.categoria || '—' }}</dd>

                  <dt class="col-sm-3">Dificultad</dt>
                  <dd class="col-sm-9">{{ selected?.dificultad || '—' }}</dd>

                  <dt class="col-sm-3">Duración</dt>
                  <dd class="col-sm-9">{{ selected?.duracion ? selected?.duracion + ' min' : '—' }}</dd>
                </dl>
              </div>
            </transition>

            <!-- Recursos -->
            <div class="section-header mt-3" @click="viewToggle.recursos = !viewToggle.recursos">
              <div class="d-flex align-items-center gap-2">
                <strong>Recursos ({{ (selected?.recursos?.length || 0) }})</strong>
              </div>
              <i class="bi" :class="viewToggle.recursos ? 'bi-chevron-up rotate' : 'bi-chevron-down'"></i>
            </div>
            <transition name="fade">
              <div v-show="viewToggle.recursos" class="section-body">
                <div v-if="(selected?.recursos?.length || 0) === 0" class="text-muted">—</div>

                <div class="resource-grid">
                  <div v-for="(r, i) in (selected?.recursos || [])" :key="r._id || i" class="resource-card">
                    <div class="resource-thumb">
                      <template v-if="isImage(r.url)">
                        <img :src="r.url" alt="" />
                      </template>
                      <template v-else-if="isVideo(r.url)">
                        <video controls :src="r.url"></video>
                      </template>
                      <template v-else-if="isAudio(r.url)">
                        <audio controls :src="r.url"></audio>
                      </template>
                      <template v-else>
                        <div class="text-muted small">Archivo</div>
                      </template>
                    </div>
                    <div class="small text-muted">{{ (r.tipo || autoType(r.url)) }} • {{ r.fecha || '—' }}</div>
                    <div class="small">{{ r.descripcion || '—' }}</div>
                  </div>
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
            <h5 class="modal-title fw-bold">{{ isEditing ? 'Modificar técnica' : 'Registrar técnica' }}</h5>
            <button type="button" class="btn-close" @click="hideModal('form')" aria-label="Cerrar"></button>
          </div>

          <div class="modal-body modal-body-safe">
            <!-- Sección: Datos de la técnica -->
            <div class="card mb-3">
              <div class="card-body">
                <label class="form-label">
                  Nombre <span class="text-danger">*</span>
                  <i class="bi bi-info-circle ms-1 text-primary"
                     data-bs-toggle="tooltip"
                     title="Nombre de la técnica tal como se mostrará a los alumnos."></i>
                </label>
                <div class="d-flex align-items-center gap-2">
                  <input v-model.trim="form.nombre" type="text" class="form-control" required maxlength="150" />
                  <button type="button" class="btn btn-light btn-sm shadow-sm" @click="ui.meta = !ui.meta" :aria-expanded="ui.meta">
                    <i class="bi" :class="ui.meta ? 'bi-chevron-up rotate' : 'bi-chevron-down'"></i>
                  </button>
                </div>

                <transition name="slide-fade">
                  <div v-show="ui.meta" class="mt-3">
                    <div class="row g-3">
                      <div class="col-12">
                        <label class="form-label">
                          Descripción
                          <i class="bi bi-info-circle ms-1 text-primary"
                             data-bs-toggle="tooltip"
                             title="Explica brevemente el objetivo y cómo se realiza."></i>
                        </label>
                        <textarea v-model.trim="form.descripcion" rows="3" class="form-control" maxlength="600"></textarea>
                      </div>

                      <div class="col-12 col-lg-4">
                        <label class="form-label">
                          Categoría
                          <i class="bi bi-info-circle ms-1 text-primary"
                             data-bs-toggle="tooltip"
                             title="Clasifica la técnica (ej. Respiración, Visualización, Gratitud, etc.)."></i>
                        </label>
                        <select v-model="form.categoria" class="form-select">
                          <option value="">— Selecciona —</option>
                          <option v-for="c in categorias" :key="c" :value="c">{{ c }}</option>
                          <option value="Otro">Otro…</option>
                        </select>
                        <input
                          v-if="form.categoria === 'Otro'"
                          v-model.trim="form.categoria_custom"
                          type="text"
                          class="form-control mt-2"
                          placeholder="Especifica la categoría"
                        />
                      </div>

                      <div class="col-12 col-lg-4">
                        <label class="form-label">
                          Dificultad
                          <i class="bi bi-info-circle ms-1 text-primary"
                             data-bs-toggle="tooltip"
                             title="Nivel sugerido de complejidad para el alumno."></i>
                        </label>
                        <select v-model="form.dificultad" class="form-select">
                          <option value="">—</option>
                          <option value="Bajo">Bajo</option>
                          <option value="Medio">Medio</option>
                          <option value="Alto">Alto</option>
                        </select>
                      </div>

                      <div class="col-12 col-lg-4">
                        <label class="form-label">
                          Duración (min)
                          <i class="bi bi-info-circle ms-1 text-primary"
                             data-bs-toggle="tooltip"
                             title="Tiempo aproximado para completar la técnica. Usa números enteros."></i>
                        </label>
                        <input v-model.number="form.duracion" type="number" min="1" step="1" class="form-control" />
                      </div>
                    </div>
                  </div>
                </transition>
              </div>
            </div>

            <!-- Sección: Recursos -->
            <div class="d-flex align-items-center justify-content-between mb-2">
              <h6 class="mb-0 fw-bold">
                Recursos ({{ form.recursos.length }})
                <i class="bi bi-info-circle ms-1 text-primary"
                   data-bs-toggle="tooltip"
                   title="Adjunta imágenes, videos o audios (el tipo y la fecha se guardan automáticamente)."></i>
              </h6>
              <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-success" @click="addRecurso">
                  <i class="bi bi-plus-circle me-1"></i> Agregar recurso
                </button>
                <button type="button" class="btn btn-light btn-sm shadow-sm" @click="ui.recursos = !ui.recursos" :aria-expanded="ui.recursos">
                  <i class="bi" :class="ui.recursos ? 'bi-chevron-up rotate' : 'bi-chevron-down'"></i>
                </button>
              </div>
            </div>

            <transition name="slide-fade">
              <div v-show="ui.recursos">
                <div v-if="form.recursos.length === 0" class="text-muted small">
                  No hay recursos. Agrega al menos uno si lo deseas.
                </div>

                <div v-for="(r, idx) in form.recursos" :key="r.__key" class="card mb-3">
                  <div class="card-body">
                    <div class="d-flex align-items-center gap-2">
                      <div class="fw-semibold flex-grow-1">Recurso #{{ idx + 1 }}</div>
                      <button type="button" class="btn btn-light btn-sm" @click="toggleRecurso(idx)">
                        <i class="bi" :class="ui.rOpen[idx] ? 'bi-chevron-up rotate' : 'bi-chevron-down'"></i>
                      </button>
                      <button type="button" class="btn btn-sm btn-outline-danger" @click="removeRecurso(idx)">
                        <i class="bi bi-x-lg me-1"></i> Quitar
                      </button>
                    </div>

                    <transition name="slide-fade">
                      <div v-show="ui.rOpen[idx]" class="mt-3">
                        <div class="row g-3 align-items-start">
                          <div class="col-12 col-lg-6">
                            <label class="form-label">
                              Archivo (imagen, video o audio)
                              <i class="bi bi-info-circle ms-1 text-primary"
                                 data-bs-toggle="tooltip"
                                 title="Se aceptan imágenes (jpg/png), videos (mp4) y audios (mp3)."></i>
                            </label>
                            <input
                              type="file"
                              class="form-control"
                              accept="image/*,video/*,audio/*"
                              @change="onPickFile($event, r)"
                            />
                            <div class="form-text">Si ya hay archivo, seleccionar uno nuevo lo reemplaza.</div>
                          </div>

                          <div class="col-12 col-lg-6">
                            <label class="form-label">
                              Descripción del recurso
                              <i class="bi bi-info-circle ms-1 text-primary"
                                 data-bs-toggle="tooltip"
                                 title="Texto breve sobre el contenido del recurso."></i>
                            </label>
                            <input v-model.trim="r.descripcion" type="text" class="form-control" maxlength="200" />
                          </div>

                          <!-- Vista previa con “X” sobrepuesta para limpiar solo el archivo -->
                          <div class="col-12">
                            <div class="resource-preview position-relative">
                              <button
                                v-if="r._previewUrl || r.url"
                                type="button"
                                class="btn btn-sm btn-danger resource-clear-btn"
                                title="Quitar archivo (mantener tarjeta)"
                                @click="clearResourceFile(r)"
                              >
                                <i class="bi bi-x-lg"></i>
                              </button>

                              <template v-if="r._previewUrl || r.url">
                                <img v-if="isImage(r._previewUrl || r.url)" :src="r._previewUrl || r.url" alt="" />
                                <video v-else-if="isVideo(r._previewUrl || r.url)" controls :src="r._previewUrl || r.url"></video>
                                <audio v-else-if="isAudio(r._previewUrl || r.url)" controls :src="r._previewUrl || r.url"></audio>
                                <div v-else class="text-muted small p-2">Archivo adjunto</div>
                              </template>
                              <div v-else class="text-muted small">Sin vista previa.</div>
                            </div>
                            <div class="form-text">
                              Tipo y fecha se guardan automáticamente al adjuntar.
                            </div>
                          </div>
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
import { useTecnicasCrud } from '@/assets/js/useTecnicasCrud';

const {
  // estado y listas
  items, isLoading, hasMore, filteredItems,
  // búsqueda
  searchQuery, onInstantSearch, clearSearch,
  // utilidad
  getId, isImage, isVideo, isAudio, autoType,
  // modales y refs
  viewModalRef, formModalRef, hideModal,
  // acciones de lista/paginación
  loadMore,
  // selección y UI
  selected, ui, viewToggle, isEditing, saving, form, categorias,
  // abrir/editar/ver
  openView, openCreate, openEdit,
  // recursos
  addRecurso, removeRecurso, toggleRecurso, onPickFile, clearResourceFile,
  // submit/eliminar
  onSubmit, confirmDelete, modifyFromView, deleteFromView,
} = useTecnicasCrud();
</script>

<style scoped>
@import '@/assets/css/Crud.css';

/* === Recursos (grid y preview) === */
.resource-grid{
  display:grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap:.75rem;
}
.resource-card{
  border:1px solid rgba(122,0,184,.12);
  border-radius:.75rem;
  padding:.5rem;
  background:#fff;
}
.resource-thumb{
  aspect-ratio: 16/9;
  border-radius:.5rem;
  overflow:hidden;
  background:#f2f4f8;
  display:flex; align-items:center; justify-content:center;
  margin-bottom:.4rem;
}
.resource-thumb img, .resource-thumb video{
  width:100%; height:100%; object-fit:cover;
}
.resource-preview{
  border:1px dashed rgba(15,23,42,.2);
  border-radius:.6rem;
  padding:.4rem;
  background:#fafbfc;
  min-height: 120px;
  display:flex; align-items:center; justify-content:center;
}
.resource-preview img, .resource-preview video{
  max-width:100%; max-height:300px; object-fit:contain;
}
.resource-preview audio{ width:100%; }

/* Botón flotante para limpiar SOLO el archivo del recurso */
.resource-clear-btn{
  position:absolute; top:.4rem; right:.4rem;
  border-radius:9999px;
  padding:.25rem .45rem;
  line-height:1;
}
</style>
