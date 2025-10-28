<template>
  <main class="panel-wrapper">
    <!-- ======= Toolbar ======= -->
    <div class="container-fluid toolbar px-3 px-lg-2">
      <div class="row g-2 align-items-center">
        <div class="col-12 col-lg-8">
          <div class="input-group input-group-lg search-group shadow-sm rounded-pill" role="search" aria-label="Buscador de recompensas">
            <span class="input-group-text rounded-start-pill"><i class="bi bi-search"></i></span>
            <input v-model.trim="searchQuery" type="search" class="form-control search-input" placeholder="Buscar recompensa por nombre…" @input="onInstantSearch" aria-label="Buscar por nombre" />
            <button v-if="searchQuery" class="btn btn-link text-secondary px-3 d-none d-md-inline" @click="clearSearch" aria-label="Limpiar búsqueda">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>
          <div class="d-flex d-md-none justify-content-end mt-2" v-if="searchQuery">
            <button class="btn btn-sm btn-outline-secondary rounded-pill" @click="clearSearch" aria-label="Limpiar búsqueda móvil">
              <i class="bi bi-x-lg me-1"></i> Limpiar
            </button>
          </div>
        </div>

        <div class="col-12 col-lg-4 text-lg-end mt-2 mt-lg-0">
          <button class="btn btn-gradient fw-semibold shadow-sm rounded-pill btn-new px-3 py-2" @click="openCreate">
            <i class="bi bi-plus-lg me-1"></i> <span class="d-inline d-sm-inline">Nuevo</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ======= Grid ======= -->
    <div class="container-fluid px-3 px-lg-2">
      <div class="row g-3 row-cols-1 row-cols-sm-2 row-cols-lg-3">
        <div v-for="item in filteredItems" :key="getId(item)" class="col">
          <div class="card h-100 item-card shadow-sm">
            <div class="card-body d-flex flex-column">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <h5 class="card-title mb-0 text-truncate fw-bold" :title="item.nombre">{{ item.nombre }}</h5>
                <span class="badge rounded-pill" :class="stockBadgeClass(item.stock)" :title="stockTitle(item.stock)">
                  {{ stockLabel(item.stock) }}
                </span>
              </div>

              <div class="mb-3"><i class="bi bi-gift" style="font-size:2rem;"></i></div>

              <div class="mb-2">
                <span class="fw-semibold"><i class="bi bi-stars me-1"></i>{{ item.puntos_necesarios }} punto(s)</span>
                <i class="bi bi-info-circle ms-1 text-primary" data-bs-toggle="tooltip" title="Puntos requeridos para canjear esta recompensa."></i>
              </div>

              <p class="card-text clamp-3 mb-2" v-if="item.descripcion">{{ item.descripcion }}</p>

              <div class="small text-muted mt-auto">
                <i class="bi bi-people-fill me-1"></i>{{ (item.canjeo?.length || 0) }} canjeo(s) registrados
              </div>
            </div>

            <div class="card-footer bg-transparent border-0 pt-0 pb-3 px-3">
              <div class="d-flex flex-column flex-md-row gap-2">
                <button class="btn btn-outline-secondary btn-sm flex-fill btn-with-label" @click="openView(item)" data-bs-toggle="tooltip" title="Consultar">
                  <i class="bi bi-eye me-1"></i><span>Consultar</span>
                </button>
                <button class="btn btn-outline-primary btn-sm flex-fill btn-with-label" @click="openEdit(item)" data-bs-toggle="tooltip" title="Modificar">
                  <i class="bi bi-pencil-square me-1"></i><span>Modificar</span>
                </button>
                <button class="btn btn-outline-danger btn-sm flex-fill btn-with-label" @click="confirmDelete(item)" data-bs-toggle="tooltip" title="Eliminar">
                  <i class="bi bi-trash me-1"></i><span>Eliminar</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <div v-if="!isLoading && filteredItems.length === 0" class="col-12">
          <div class="alert alert-light border d-flex align-items-center gap-2">
            <i class="bi bi-inbox text-secondary fs-4"></i>
            <div><strong>Sin resultados.</strong> Intenta con otra búsqueda o registra una nueva recompensa.</div>
          </div>
        </div>

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

      <div class="d-flex justify-content-center my-4" v-if="!isLoading && hasMore">
        <button class="btn btn-outline-secondary btn-lg" @click="loadMore">Cargar más</button>
      </div>
    </div>

    <!-- ======= Modales ======= -->
    <div class="modal fade" ref="viewModalRef" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-xl modal-fit">
        <div class="modal-content modal-flex border-0 shadow-lg">
          <div class="modal-header border-0 sticky-top bg-white">
            <h5 class="modal-title fw-bold">Detalle de la recompensa</h5>
            <button type="button" class="btn-close" @click="hideModal('view')" aria-label="Cerrar"></button>
          </div>

          <div class="modal-body modal-body-safe">
            <div class="section-header" @click="viewToggle.meta = !viewToggle.meta">
              <div class="d-flex align-items-center gap-2">
                <strong class="fs-5">{{ selected?.nombre || '—' }}</strong>
                <span class="badge rounded-pill" :class="stockBadgeClass(selected?.stock ?? 0)">
                  {{ stockLabel(selected?.stock ?? 0) }}
                </span>
              </div>
              <i class="bi" :class="viewToggle.meta ? 'bi-chevron-up rotate' : 'bi-chevron-down'"></i>
            </div>

            <transition name="fade">
              <div v-show="viewToggle.meta" class="section-body">
                <dl class="row gy-2 mb-0">
                  <dt class="col-sm-3">Descripción</dt>
                  <dd class="col-sm-9">{{ selected?.descripcion || '—' }}</dd>

                  <dt class="col-sm-3">Puntos necesarios</dt>
                  <dd class="col-sm-9">{{ selected?.puntos_necesarios ?? '—' }}</dd>

                  <dt class="col-sm-3">Stock</dt>
                  <dd class="col-sm-9">{{ selected?.stock ?? '—' }}</dd>
                </dl>
              </div>
            </transition>

            <div class="section-header mt-3" @click="viewToggle.canjeo = !viewToggle.canjeo">
              <div class="d-flex align-items-center gap-2">
                <strong>Canjeos ({{ (selected?.canjeo?.length || 0) }})</strong>
                <i class="bi bi-info-circle ms-1 text-primary" data-bs-toggle="tooltip"
                   title="Los canjeos los realiza el alumno desde su propia vista. Aquí solo se consulta el historial."></i>
              </div>
              <i class="bi" :class="viewToggle.canjeo ? 'bi-chevron-up rotate' : 'bi-chevron-down'"></i>
            </div>

            <transition name="fade">
              <div v-show="viewToggle.canjeo" class="section-body">
                <div v-if="canjeosLoading" class="text-muted small d-flex align-items-center gap-2">
                  <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                  Cargando canjeos…
                </div>

                <div v-else-if="(selected?.canjeo?.length || 0) === 0" class="text-muted">—</div>

                <div v-else class="table-responsive">
                  <table class="table table-sm align-middle">
                    <thead>
                      <tr>
                        <th>Matrícula</th>
                        <th>Nombre</th>
                        <th>Cohorte</th>
                        <th>Fecha de canjeo</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(c, i) in (selected?.canjeoEnriquecido || [])" :key="i">
                        <td>{{ c.matricula || '—' }}</td>
                        <td>{{ c.nombreCompleto || '—' }}</td>
                        <td>{{ c.cohorteLabel || '—' }}</td>
                        <td>{{ formatDate(c.fechaCanjeo) || '—' }}</td>
                      </tr>
                    </tbody>
                  </table>
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

    <!-- Form (crear/editar) -->
    <div class="modal fade" ref="formModalRef" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-xl modal-fit">
        <form class="modal-content modal-flex border-0 shadow-lg" @submit.prevent="onSubmit">
          <div class="modal-header border-0 sticky-top bg-white">
            <h5 class="modal-title fw-bold">{{ isEditing ? 'Modificar recompensa' : 'Registrar recompensa' }}</h5>
            <button type="button" class="btn-close" @click="hideModal('form')" aria-label="Cerrar"></button>
          </div>

          <div class="modal-body modal-body-safe">
            <div class="card mb-3">
              <div class="card-body">
                <div class="row g-3">
                  <div class="col-12">
                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input v-model.trim="form.nombre" type="text" class="form-control" required maxlength="150" />
                  </div>
                  <div class="col-12">
                    <label class="form-label">Descripción</label>
                    <textarea v-model.trim="form.descripcion" rows="3" class="form-control" maxlength="500"></textarea>
                  </div>
                  <div class="col-12 col-lg-6">
                    <label class="form-label">Puntos necesarios <span class="text-danger">*</span></label>
                    <input v-model.number="form.puntos_necesarios" type="number" min="0" step="1" class="form-control" required />
                  </div>
                  <div class="col-12 col-lg-6">
                    <label class="form-label">Stock <span class="text-danger">*</span></label>
                    <input v-model.number="form.stock" type="number" min="0" step="1" class="form-control" required />
                  </div>
                </div>
              </div>
            </div>

            <div class="alert alert-info d-flex align-items-start gap-2">
              <i class="bi bi-shield-lock fs-5"></i>
              <div><strong>Canjeo deshabilitado para el administrador.</strong><br />Los alumnos realizan el canje desde su propia vista. Aquí solo se administra la información de la recompensa.</div>
            </div>

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
import { useRecompensasCrud } from '@/assets/js/useRecompensasCrud';

const {
  items, isLoading, hasMore, filteredItems, page,
  searchQuery, onInstantSearch, clearSearch,
  getId, formatDate,
  stockBadgeClass, stockLabel, stockTitle,
  viewModalRef, formModalRef, hideModal,
  selected, ui, viewToggle, isEditing, saving, form, canjeosLoading,
  loadMore,
  openView, openCreate, openEdit,
  onSubmit, confirmDelete, modifyFromView, deleteFromView,
} = useRecompensasCrud();
</script>

<style scoped>
@import '@/assets/css/Crud.css';
</style>
