<!-- src/views/profesor/Actividades.vue -->
<template>
  <main class="panel-wrapper container-fluid py-3 py-lg-4">
    <!-- ======= Toolbar moderna ======= -->
    <div class="toolbar px-0 px-lg-2">
      <div class="row g-3 align-items-stretch">
        <!-- Filtros (se aplican automáticamente) -->
        <div class="col-12 col-xl-8">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
              <!-- === Fila 1: Cohorte (select moderno) === -->
              <div class="row g-2 align-items-center">
                <div class="col-12">
                  <div class="d-flex flex-wrap gap-2 align-items-center">
                    <div class="filter-label me-1">
                      <i class="bi bi-funnel me-1"></i>Filtros
                    </div>

                    <!-- ===== Cohorte (dropdown buscable, SOLO mis grupos) ===== -->
                    <div class="modern-select" @keydown.stop>
                      <button
                        class="btn btn-select btn-sm"
                        type="button"
                        @click="toggleDD('cohorte')"
                        :aria-expanded="ddOpen.cohorte ? 'true' : 'false'">
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
                          />
                          <button v-if="cohorteQ" class="btn btn-clear" @click="cohorteQ=''">
                            <i class="bi bi-x-lg"></i>
                          </button>
                        </div>

                        <div class="select-list">
                          <button
                            class="select-item"
                            :class="{ active: !filtros.cohorte }"
                            @click="setCohorte('')">
                            <div class="title">
                              <i class="bi bi-grid-3x3-gap me-2"></i>Todos mis grupos
                            </div>
                            <i class="bi bi-check2-circle ms-2" v-if="!filtros.cohorte"></i>
                          </button>

                          <div class="select-group">Mis cohortes</div>
                          <button
                            v-for="c in cohortesFiltradas"
                            :key="c"
                            class="select-item"
                            :class="{ active: filtros.cohorte === c }"
                            @click="setCohorte(c)">
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

              <!-- === Fila 2: Fechas === -->
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
                          aria-label="Agregadas desde" />
                        <small class="text-muted">Asignadas hasta</small>
                        <input
                          type="date"
                          class="form-control form-control-sm"
                          v-model="filtros.hasta"
                          :min="filtros.desde || undefined"
                          aria-label="Asignadas hasta" />
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Chips dinámicos -->
              <div class="row g-2 mt-2">
                <div class="col-12">
                  <div class="d-flex flex-wrap gap-2">
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
                    <i class="bi bi-mortarboard"></i> {{ tecnicaData(a).nombre || '—' }}
                  </div>
                </td>
                <td class="d-none d-sm-table-cell">
                  {{ tecnicaData(a).nombre || '—' }}
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

        <!-- Paginación -->
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top flex-wrap gap-2">
          <div class="small text-muted">Página actual: {{ paginaActual }} / {{ totalPaginas || 1 }}</div>
          <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm btn-outline-secondary" :disabled="!enlaces.anterior" @click="go(enlaces.anterior)">
              <i class="bi bi-chevron-left"></i>
            </button>
            <button class="btn btn-sm btn-outline-secondary" :disabled="!enlaces.siguiente" @click="go(enlaces.siguiente)">
              <i class="bi bi-chevron-right"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
  </main>
</template>

<script>
/**
 * Usamos el MISMO controlador base para compartir toda la lógica.
 * Solo sobreescribimos lo necesario para que el profesor vea EXCLUSIVAMENTE
 * a sus alumnos (cohorte EXACTA).
 */
import controller from "@/assets/js/actividades.controller";
import { fetchAlumnos } from "@/composables/actividades";

export default {
  name: "ActividadesProfesor",
  mixins: [controller],

  computed: {
    // Etiqueta del select de cohorte
    labelCohorte() {
      return this.filtros.cohorte ? this.filtros.cohorte : "Todos mis grupos";
    },

    // Solo mis cohortes (desde el propio user)
    cohortesVisibles() {
      const norm = (v) => String(v || "").replace(/\s+/g, " ").trim();
      const coh = this.usuario?.persona?.cohorte;
      if (Array.isArray(coh)) return [...new Set(coh.map(norm))].sort();
      if (typeof coh === "string" && coh) return [norm(coh)];
      return [];
    },

    cohortesFiltradas() {
      const q = this.cohorteQ.toLowerCase();
      return this.cohortesVisibles.filter((c) => c.toLowerCase().includes(q));
    },
  },

  methods: {
    /**
     * OVERRIDE: cachear alumnos SOLO de mis cohortes (coincidencia EXACTA).
     * Reemplaza el método del controlador base con esta versión.
     */
    async _ensureAlumnosCache() {
      if (this.alumnosCache.length) return this.alumnosCache;

      // 1) Trae todos los estudiantes
      let all = [];
      try {
        all = await fetchAlumnos({});
      } catch (e) {
        console.error("No se pudieron cargar alumnos:", e?.message || e);
        this.alumnosCache = [];
        return this.alumnosCache;
      }

      const onlyStudents = (Array.isArray(all) ? all : []).filter((u) => {
        const r = String(u?.rol || "").toLowerCase();
        return r === "estudiante" || r === "alumno";
      });

      const norm = (v) => String(v || "").replace(/\s+/g, " ").trim();
      const mis = this.cohortesVisibles.map(norm);

      // 2) Filtra por cohorte EXACTA contra mis cohortes
      this.alumnosCache = onlyStudents
        .filter((u) => {
          const c = u?.persona?.cohorte;
          if (!c) return false;
          if (Array.isArray(c)) return c.map(norm).some((x) => mis.includes(x));
          return mis.includes(norm(c));
        })
        .map((u) => ({
          ...u,
          _key: String(u._id || u.id),
          name: u?.name || "",
          email: u?.email || "",
          matricula: u?.matricula || "",
          persona: u?.persona || {},
        }));

      return this.alumnosCache;
    },
  },
};
</script>

<style scoped>
:root { --ink:#1b3b6f; --ink-2:#2c4c86; --sky:#eaf3ff; --card-b:#f8fbff; --stroke:#e6eefc; --chip:#eef6ff; --chip-ink:#2c4c86; }
.fw-600 { font-weight: 600; }
.filter-label { font-size:.9rem; color:var(--ink-2); font-weight:600; }

.modern-select { position: relative; }
.btn-select {
  border:1px solid var(--stroke);
  background:#fff; color:#223; border-radius:999px;
  display:flex; align-items:center; gap:.5rem;
  padding:.35rem .75rem;
  max-width: 260px;
}
.btn-select .text-truncate { max-width: 150px; }
.btn-select:hover { background:#f9fbff; }

.select-panel{
  position:absolute; z-index: 5; top: calc(100% + 6px); left:0;
  min-width: 280px; width:max-content; max-width: 360px;
  background:#fff; border:1px solid var(--stroke); border-radius:12px;
  box-shadow: 0 10px 24px rgba(15,22,48,.12);
}
.select-search{
  display:flex; align-items:center; gap:.5rem; padding:.5rem .6rem;
  border-bottom:1px solid var(--stroke);
}
.select-search i { opacity:.7; }
.select-search .form-control{ border:0; box-shadow:none; }
.btn-clear{ border:0; background:transparent; padding:.2rem .25rem; }

.select-list{ max-height: 260px; overflow:auto; padding:.35rem; }
.select-group{
  font-size:.75rem; text-transform:uppercase; letter-spacing:.04em;
  color:#6b7a99; padding:.35rem .5rem .2rem;
}
.select-item{
  width:100%; text-align:left; background:#fff; border:0;
  padding:.55rem .65rem; border-radius:10px; display:flex; justify-content:space-between; align-items:center;
}
.select-item:hover{ background:#f5f8ff; }
.select-item.active{ background:#eaf2ff; outline:1px solid #d7e6ff; }
.select-item .title{ font-size:.92rem; }

.filters-row{ display:flex; align-items:center; gap:.75rem; }
.filters-labels small{ white-space:nowrap; }
.filters-controls{ display:flex; gap:.5rem; }
.filters-controls .form-control{ min-width: 10.5rem; }

.gradient-card { background: linear-gradient(135deg, #6a8dff, #7b5cff); border-radius: 16px; }
.btn-gradient { background: linear-gradient(135deg, #6a8dff, #7b5cff); color:#fff; border:0; }
.btn-gradient:hover { filter: brightness(.95); color:#fff; }

.chip{
  display:inline-flex; align-items:center; gap:.5rem; padding:.35rem .65rem;
  border-radius:999px; font-size:.84rem; background:#fff; border:1px solid var(--stroke);
}
.chip-info { background:var(--chip); color:var(--chip-ink); border-color:#d8e6ff; }
.chip .chip-x{ border:0; background:transparent; padding:0; margin-left:.25rem; line-height:0; color:inherit; cursor:pointer; }

.table td, .table th { vertical-align: middle; }

:deep(.swal2-container.swal2-pt){
  padding-top: 5.5rem !important;
  backdrop-filter: blur(4px);
  background-color: rgba(10,16,28,.28) !important;
}
:deep(.swal2-popup.swal2-rounded){ border-radius: 18px !important; }
:deep(.sw-actions){ display: flex; align-items: center; justify-content: flex-end; }
:deep(.swal2-confirm.btn.btn-gradient){
  background: linear-gradient(135deg, #6a8dff, #7b5cff) !important;
  border: 0 !important; color:#fff !important;
  padding: .5rem 1rem !important; border-radius: 999px !important;
}
:deep(.swal2-cancel.btn.btn-outline-secondary){ border-radius: 999px !important; }
:deep(.swal2-close.swal2-close-pt){ box-shadow:none !important; opacity:.7; }
:deep(.swal2-close.swal2-close-pt):hover{ opacity:1; }

.sw-card{ border:1px solid var(--stroke); border-radius: 16px; background: #fff; }
.sw-card-header{
  position: relative;
  display:flex; align-items:center; justify-content:flex-start;
  gap:.75rem; padding:.75rem 1rem;
  background:#fff; border-bottom:1px solid var(--stroke);
  border-top-left-radius:16px; border-top-right-radius:16px;
}
.sw-card-header h6{ display:flex; align-items:center; gap:.5rem; margin:0; }
.sw-toggle-top{ position:absolute; right:12px; top:50%; transform:translateY(-50%); }

.sw-search-group{
  display:flex; align-items:center; gap:.5rem;
  border:1px solid var(--stroke); border-radius:999px; padding:.35rem .6rem; background:#fff;
  box-shadow: inset 0 0 0 2px rgba(122,153,255,.06);
  white-space:nowrap;
}
.sw-search-group .input-icon{ display:flex; align-items:center; opacity:.65; }
.sw-search-input{ border:0 !important; box-shadow:none !important; flex:1 1 auto; min-width:0; height:38px; }
.icon-btn{
  border:0; background:transparent; display:flex; align-items:center; justify-content:center;
  width:32px; height:32px; border-radius:999px; padding:0;
}
.icon-btn:hover{ background:#f2f6ff; }
.tecnica-list, .sw-list { max-height: 280px; overflow:auto; }

@media (max-width: 576px){
  .sw-search-group{ padding:.25rem .45rem; }
  .sw-card-header h6{ font-size: .96rem; }
}

:deep(.sw-form .sw-list .list-group-item){ border-radius: 10px; margin-bottom:.35rem; }
</style>
