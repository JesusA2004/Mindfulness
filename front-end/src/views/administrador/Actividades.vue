<!-- src/views/administrador/Actividades.vue -->
<template>
  <main class="panel-wrapper container-fluid py-3 py-lg-4">
    <!-- ======= Toolbar moderna ======= -->
    <div class="toolbar px-0 px-lg-2">
      <div class="row g-3 align-items-stretch">
        <!-- Filtros (se aplican automáticamente) -->
        <div class="col-12 col-xl-8">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
              <!-- === Fila 1: Docente y Cohorte (selectores modernos) === -->
              <div class="row g-2 align-items-center">
                <div class="col-12">
                  <div class="d-flex flex-wrap gap-2 align-items-center">
                    <div class="filter-label me-1">
                      <i class="bi bi-funnel me-1"></i>Filtros
                    </div>

                    <!-- ===== Docente (dropdown buscable) ===== -->
                    <div v-if="mostrarFiltroDocente" class="modern-select" @keydown.stop>
                      <button
                        class="btn btn-select btn-sm"
                        type="button"
                        @click="toggleDD('docente')"
                        :aria-expanded="ddOpen.docente ? 'true' : 'false'"
                      >
                        <i class="bi bi-person-badge me-1"></i>
                        <span class="text-truncate">
                          {{ labelDocente }}
                        </span>
                        <i class="bi" :class="ddOpen.docente ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                      </button>

                      <div v-show="ddOpen.docente" class="select-panel">
                        <div class="select-search">
                          <i class="bi bi-search"></i>
                          <input
                            v-model.trim="docenteQ"
                            type="text"
                            class="form-control form-control-sm"
                            placeholder="Buscar docente…"
                            @input="onFiltrosChange"
                          />
                          <button v-if="docenteQ" class="btn btn-clear" @click="docenteQ=''">
                            <i class="bi bi-x-lg"></i>
                          </button>
                        </div>

                        <div class="select-list">
                          <button
                            class="select-item"
                            :class="{ active: !filtros.docenteId }"
                            @click="setDocente('')"
                          >
                            <div class="title">
                              <i class="bi bi-people me-2"></i>Todos los docentes
                            </div>
                            <i class="bi bi-check2-circle ms-2" v-if="!filtros.docenteId"></i>
                          </button>

                          <button
                            class="select-item"
                            :class="{ active: filtros.docenteId === myId }"
                            @click="setDocente(myId)"
                          >
                            <div class="title">
                              <i class="bi bi-person-check me-2"></i>Creadas por mí
                            </div>
                            <i class="bi bi-check2-circle ms-2" v-if="filtros.docenteId === myId"></i>
                          </button>

                          <div class="select-group">Docentes</div>
                          <button
                            v-for="d in docentesFiltrados"
                            :key="d._id || d.id"
                            class="select-item"
                            :class="{ active: filtros.docenteId === String(d._id || d.id) }"
                            @click="setDocente(String(d._id || d.id))"
                          >
                            <div class="title text-truncate">
                              <i class="bi bi-person me-2"></i>{{ d.name }}
                            </div>
                            <i
                              class="bi bi-check2-circle ms-2"
                              v-if="filtros.docenteId === String(d._id || d.id)"
                            ></i>
                          </button>
                        </div>
                      </div>
                    </div>

                    <!-- ===== Cohorte (dropdown buscable) ===== -->
                    <div class="modern-select" @keydown.stop>
                      <button
                        class="btn btn-select btn-sm"
                        type="button"
                        @click="toggleDD('cohorte')"
                        :aria-expanded="ddOpen.cohorte ? 'true' : 'false'"
                      >
                        <i class="bi bi-mortarboard me-1"></i>
                        <span class="text-truncate">{{ labelCohorte }}</span>
                        <i class="bi" :class="ddOpen.cohorte ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                      </button>

                      <div v-show="ddOpen.cohorte" class="select-panel">
                        <div class="select-search">
                          <i class="bi bi-search"></i>
                          <input
                            v-model.trim="cohorteQ"
                            type="text"
                            class="form-control form-control-sm"
                            placeholder="Buscar cohorte…"
                            @input="onFiltrosChange"
                          />
                          <button v-if="cohorteQ" class="btn btn-clear" @click="cohorteQ=''">
                            <i class="bi bi-x-lg"></i>
                          </button>
                        </div>

                        <div class="select-list">
                          <button
                            class="select-item"
                            :class="{ active: !filtros.cohorte }"
                            @click="setCohorte('')"
                          >
                            <div class="title">
                              <i class="bi bi-grid-3x3-gap me-2"></i>Todos los grupos
                            </div>
                            <i class="bi bi-check2-circle ms-2" v-if="!filtros.cohorte"></i>
                          </button>

                          <div class="select-group">Cohortes</div>
                          <button
                            v-for="c in cohortesFiltradas"
                            :key="c"
                            class="select-item"
                            :class="{ active: filtros.cohorte === c }"
                            @click="setCohorte(c)"
                          >
                            <div class="title text-truncate">
                              <i class="bi bi-hash me-2"></i>{{ c }}
                            </div>
                            <i class="bi bi-check2-circle ms-2" v-if="filtros.cohorte === c"></i>
                          </button>
                        </div>
                      </div>
                    </div>

                    <!-- Chips resumen -->
                    <div class="ms-auto">
                      <span class="chip">En página: <strong>{{ totalVisible }}</strong></span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- === Fila 2: Fechas (en otra fila) === -->
              <div class="row g-2 align-items-center mt-2">
                <div class="col-12">
                  <div class="filters-row w-100">
                    <div class="filters-labels">
                      <div class="filters-controls ms-auto">
                        <small class="text-muted me-3">Agregadas desde</small>
                        <input
                          type="date"
                          class="form-control form-control-sm"
                          v-model="filtros.desde"
                          :max="filtros.hasta || undefined"
                          aria-label="Agregadas desde"
                        />
                        <small class="text-muted">Asignadas hasta</small>
                        <input
                          type="date"
                          class="form-control form-control-sm"
                          v-model="filtros.hasta"
                          :min="filtros.desde || undefined"
                          aria-label="Asignadas hasta"
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Chips dinámicos -->
              <div class="row g-2 mt-2">
                <div class="col-12">
                  <div class="d-flex flex-wrap gap-2">
                    <span v-if="mostrarFiltroDocente && filtros.docenteId" class="chip chip-info">
                      Docente: <strong>{{ docenteNombre(filtros.docenteId) }}</strong>
                      <button class="chip-x" @click="setDocente('')" aria-label="Quitar docente">
                        <i class="bi bi-x"></i>
                      </button>
                    </span>
                    <span v-if="filtros.cohorte" class="chip chip-info">
                      Cohorte: <strong>{{ filtros.cohorte }}</strong>
                      <button class="chip-x" @click="setCohorte('')" aria-label="Quitar cohorte">
                        <i class="bi bi-x"></i>
                      </button>
                    </span>
                    <span v-if="filtros.desde" class="chip chip-info">
                      Desde: <strong>{{ filtros.desde }}</strong>
                      <button class="chip-x" @click="filtros.desde=''" aria-label="Quitar fecha desde">
                        <i class="bi bi-x"></i>
                      </button>
                    </span>
                    <span v-if="filtros.hasta" class="chip chip-info">
                      Hasta: <strong>{{ filtros.hasta }}</strong>
                      <button class="chip-x" @click="filtros.hasta=''" aria-label="Quitar fecha hasta">
                        <i class="bi bi-x"></i>
                      </button>
                    </span>
                  </div>
                </div>
              </div>
              <!-- /chips -->
            </div>
          </div>
        </div>

        <!-- CTA lateral -->
        <div class="col-12 col-xl-4">
          <div class="card gradient-card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center justify-content-between gap-3">
              <div>
                <div class="fw-bold text-white fs-5">Actividades en el aula</div>
              </div>
              <button class="btn btn-light rounded-pill fw-semibold px-3" @click="openCreate">
                <i class="bi bi-plus-lg me-1"></i> Registrar
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ======= Tabla ======= -->
    <div class="card border-0 shadow-sm">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Actividad</th>
                <th class="d-none d-sm-table-cell">Técnica</th>
                <th>Asignación</th>
                <th class="d-none d-md-table-cell">Fecha máx.</th>
                <th class="text-end">Participantes</th>
                <th class="text-end">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="registros.length === 0">
                <td colspan="6" class="text-center py-4 text-muted">
                  Sin resultados con los filtros actuales.
                </td>
              </tr>

              <tr v-for="a in registros" :key="a._id || a.id">
                <td class="fw-600">
                  {{ a.nombre }}
                  <div class="small text-muted d-sm-none mt-1">
                    <i class="bi bi-mortarboard"></i> {{ a?.tecnica?.nombre || '—' }}
                  </div>
                </td>
                <td class="d-none d-sm-table-cell">
                  {{ a?.tecnica?.nombre || '—' }}
                </td>
                <td class="text-nowrap">
                  <span class="badge bg-secondary-subtle text-secondary border">
                    {{ fmt(a.fechaAsignacion) }}
                  </span>
                </td>
                <td class="text-nowrap d-none d-md-table-cell">
                  <span class="badge bg-info-subtle text-info border">
                    {{ fmt(a.fechaMaxima) }}
                  </span>
                </td>
                <td class="text-end">
                  <span class="badge bg-primary-subtle text-primary border">
                    {{ (a.participantes && Array.isArray(a.participantes)) ? a.participantes.length : 0 }}
                  </span>
                </td>
                <td class="text-end">
                  <button class="btn btn-sm btn-outline-primary" @click="verDetalles(a)">
                    <i class="bi bi-eye me-1"></i> Detalles
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</template>

<script>
import controller from "@/assets/js/actividades.controller";
export default controller;
</script>

<style scoped src="@/assets/css/Actividades.css"></style>

