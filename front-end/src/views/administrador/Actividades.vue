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
import Swal from "sweetalert2";
import "sweetalert2/dist/sweetalert2.min.css";

import {
  getCurrentUser,
  fetchActividades,
  createActividad,
  fetchTecnicas,
  fetchAlumnos,
  fetchDocentes,
  paramsFromPaginationUrl,
} from "@/composables/actividades";

export default {
  name: "Actividades",
  data() {
    return {
      usuario: null,
      docentes: [],
      registros: [],
      enlaces: { anterior: null, siguiente: null },
      totalVisible: 0,
      paginaActual: 1,
      totalPaginas: 1,

      // paginación cliente (cuando hay cohorte)
      _clientPaginate: false,
      _clientAll: [],
      _clientPerPage: 6,

      filtros: { docenteId: "", cohorte: "", desde: "", hasta: "" },

      // dropdowns modernos
      ddOpen: { docente: false, cohorte: false },
      docenteQ: "",
      cohorteQ: "",

      // cache datos
      tecnicas: [],
      alumnosCache: [],
      cohortesGlobales: [],

      // estados
      submitting: false,
      _buscarAlumnoDebounce: null,
      _filtroDebounce: null,
    };
  },
  computed: {
    myId() {
      return String(this.usuario?._id || this.usuario?.id || "");
    },
    mostrarFiltroDocente() {
      return this.usuario && this.usuario.rol !== "profesor";
    },
    labelDocente() {
      if (!this.mostrarFiltroDocente) return "";
      if (!this.filtros.docenteId) return "Todos los docentes";
      if (this.filtros.docenteId === this.myId) return "Creadas por mí";
      const d = this.docentes.find((x) => String(x._id || x.id) === String(this.filtros.docenteId));
      return d?.name || "Docente";
    },
    labelCohorte() {
      return this.filtros.cohorte ? this.filtros.cohorte : "Todos los grupos";
    },
    cohortesVisibles() {
      const rol = this.usuario?.rol;
      const coh = this.usuario?.persona?.cohorte;
      if (rol === "profesor") {
        if (Array.isArray(coh)) return [...coh].sort();
        if (typeof coh === "string" && coh) return [coh];
        return [];
      }
      return this.cohortesGlobales;
    },
    docentesFiltrados() {
      const q = this.docenteQ.toLowerCase();
      return this.docentes.filter((d) => (d?.name || "").toLowerCase().includes(q));
    },
    cohortesFiltradas() {
      const q = this.cohorteQ.toLowerCase();
      return this.cohortesVisibles.filter((c) => c.toLowerCase().includes(q));
    },
  },
  watch: {
    "filtros.docenteId": "onFiltrosChange",
    "filtros.cohorte": "onFiltrosChange",
    "filtros.desde": "onFiltrosChange",
    "filtros.hasta": "onFiltrosChange",
  },
  async mounted() {
    await this.bootstrap();
    document.addEventListener("click", this.handleOutside);
  },
  beforeUnmount() {
    document.removeEventListener("click", this.handleOutside);
  },
  methods: {
    fmt(d) { return d || "—"; },
    docenteNombre(id) {
      if (String(id) === this.myId) return "Creadas por mí";
      const d = this.docentes.find((x) => String(x._id || x.id) === String(id));
      return d?.name || id;
    },
    cohorteStr(u) {
      const c = u?.persona?.cohorte;
      if (Array.isArray(c)) return c.join(", ");
      return c || "";
    },
    puedeTomarlo(u) {
      if (this.usuario?.rol !== "profesor") return true;
      const del = u?.persona?.cohorte;
      const inSet = (coh) => {
        if (!coh) return false;
        if (Array.isArray(coh)) return coh.some((v) => this.inMisCohortes(v));
        return this.inMisCohortes(coh);
      };
      return inSet(del);
    },
    inMisCohortes(valor) {
      const mis = this.usuario?.persona?.cohorte;
      if (Array.isArray(mis)) return mis.includes(String(valor));
      if (typeof mis === "string") return String(valor) === mis;
      return false;
    },

    async bootstrap() {
      this.usuario = await getCurrentUser();

      // Docentes (solo si no es profesor)
      if (this.mostrarFiltroDocente) {
        const allDocs = await fetchDocentes();
        this.docentes = (allDocs || []).filter((d) => String(d._id || d.id) !== this.myId);
      } else {
        this.filtros.docenteId = this.myId;
      }

      // Técnicas
      this.tecnicas = await fetchTecnicas();

      // Alumnos cache + cohortes globales
      const alumnosAll = await this._ensureAlumnosCache();

      // Cohortes globales (normalizadas)
      if (this.usuario?.rol !== "profesor") {
        const set = new Set();
        for (const u of alumnosAll) {
          const c = u?.persona?.cohorte;
          const push = (s) => {
            const norm = String(s || "").replace(/\s+/g, " ").trim();
            if (norm) set.add(norm);
          };
          if (Array.isArray(c)) c.forEach(push);
          else if (c) push(c);
        }
        this.cohortesGlobales = Array.from(set).sort();
      }

      await this.cargarActividades();
    },

    // ===== Dropdown moderno helpers
    toggleDD(which) {
      this.ddOpen[which] = !this.ddOpen[which];
      if (which === "docente") this.ddOpen.cohorte = false;
      if (which === "cohorte") this.ddOpen.docente = false;
    },
    handleOutside(e) {
      const wrappers = Array.from(document.querySelectorAll(".modern-select"));
      const isInside = wrappers.some((w) => w.contains(e.target));
      if (!isInside) this.ddOpen = { docente: false, cohorte: false };
    },
    setDocente(id) {
      this.filtros.docenteId = id;
      this.ddOpen.docente = false;
    },
    setCohorte(v) {
      const norm = String(v || "").replace(/\s+/g, " ").trim();
      this.filtros.cohorte = norm;
      this.ddOpen.cohorte = false;
    },

    // 🔹 Cuando cambian filtros, siempre regresa a página 1
    onFiltrosChange() {
      if (this._filtroDebounce) clearTimeout(this._filtroDebounce);
      this._filtroDebounce = setTimeout(() => {
        this.paginaActual = 1;
        this.cargarActividades({ page: 1 });
      }, 180);
    },

    _pageFromUrl(url) {
      if (!url) return null;
      try {
        const u = new URL(url, window.location.origin);
        const page = u.searchParams.get("page");
        return page ? parseInt(page, 10) : null;
      } catch {
        return null;
      }
    },

    /** =================== CARGA con fallback de filtrado en cliente =================== */
    async cargarActividades(extraParams = {}) {
      // Siempre tenemos alumnosCache listo (para filtrar por cohorte en cliente)
      await this._ensureAlumnosCache();

      const params = { ...extraParams };
      // Solo enviamos al backend los filtros estables (docente/fechas)
      if (this.filtros.desde) params.desde = this.filtros.desde;
      if (this.filtros.hasta) params.hasta = this.filtros.hasta;

      if (this.filtros.docenteId) {
        params.docente_id = String(this.filtros.docenteId);
      } else if (this.usuario?.rol === "profesor") {
        params.docente_id = this.myId;
      }

      const hayCohorte = !!(this.filtros.cohorte && this.filtros.cohorte.trim());
      // Si hay cohorte, pedimos más por página para tener margen y filtramos local
      params.perPage = hayCohorte ? 200 : 6;

      console.info("[ACTIVIDADES] params enviados (sin cohorte en servidor):", params);

      const data = await fetchActividades(params);

      // Si NO hay cohorte -> modo servidor normal
      if (!hayCohorte) {
        this._clientPaginate = false;
        this._clientAll = [];
        this.registros = data?.registros || [];
        this.enlaces = data?.enlaces || { anterior: null, siguiente: null };
        this.totalVisible = this.registros.length;

        const prev = this._pageFromUrl(this.enlaces.anterior);
        this.paginaActual = prev ? prev + 1 : (params.page || 1);

        const last = this._pageFromUrl(data?.enlaces?.ultimo);
        this.totalPaginas = last || (this.enlaces.siguiente ? this.paginaActual + 1 : this.paginaActual);

        console.info("[ACTIVIDADES] recibidos (server paging):", {
          count: this.registros.length,
          paginaActual: this.paginaActual,
          totalPaginas: this.totalPaginas,
        });
        return;
      }

      // ====== MODO FILTRO POR COHORTE EN CLIENTE ======
      // 1) armar set de user_ids pertenecientes al cohorte seleccionado
      const objetivo = this.filtros.cohorte.trim().toLowerCase();
      const userIdsCohorte = new Set(
        (this.alumnosCache || [])
          .filter((u) => {
            const c = u?.persona?.cohorte;
            if (!c) return false;
            if (Array.isArray(c)) return c.some((s) => String(s).toLowerCase() === objetivo);
            return String(c).toLowerCase() === objetivo;
          })
          .map((u) => String(u._key))
      );

      // 2) filtrar los registros por participantes
      const todos = Array.isArray(data?.registros) ? data.registros : [];
      const filtrados = todos.filter((a) => {
        const p = a?.participantes;
        if (Array.isArray(p)) {
          return p.some((row) => userIdsCohorte.has(String(row?.user_id)));
        }
        if (typeof p === "string") {
          // legacy string JSON
          for (const uid of userIdsCohorte) {
            if (p.includes(`"user_id":"${uid}"`)) return true;
          }
        }
        return false;
      });

      // 3) activar paginación en cliente
      this._clientPaginate = true;
      this._clientAll = filtrados;
      this._clientPerPage = 6;
      this._applyClientPage(1);

      console.info("[ACTIVIDADES] filtrados en cliente por cohorte:", {
        enviados: todos.length,
        filtrados: filtrados.length,
        paginas: this.totalPaginas,
      });
    },

    _applyClientPage(page) {
      const total = this._clientAll.length;
      const per = this._clientPerPage;
      const last = Math.max(1, Math.ceil(total / per));
      const p = Math.min(Math.max(1, page), last);

      const start = (p - 1) * per;
      const slice = this._clientAll.slice(start, start + per);

      this.paginaActual = p;
      this.totalPaginas = last;
      this.registros = slice;
      this.totalVisible = slice.length;

      // Enlaces sintéticos para botones (usamos tokens 'prev'/'next')
      this.enlaces = {
        anterior: p > 1 ? "prev" : null,
        siguiente: p < last ? "next" : null,
      };
    },

    // Botones de paginación: soporta modo servidor y modo cliente
    go(ref) {
      if (!ref) return;
      if (this._clientPaginate) {
        const next = ref === "next" ? this.paginaActual + 1 : this.paginaActual - 1;
        this._applyClientPage(next);
        return;
      }
      const p = paramsFromPaginationUrl(ref);
      this.cargarActividades(p);
    },

    // ====== Búsqueda de alumnos (cache) ======
    async _ensureAlumnosCache() {
      if (this.alumnosCache.length) return this.alumnosCache;
      try {
        const all = await fetchAlumnos({});
        const onlyStudents = (Array.isArray(all) ? all : []).filter((u) => {
          const r = String(u?.rol || "").toLowerCase();
          return r === "estudiante" || r === "alumno";
        });
        this.alumnosCache = onlyStudents.map((u) => ({
          ...u,
          _key: String(u._id || u.id),
          name: u?.name || "",
          email: u?.email || "",
          matricula: u?.matricula || "",
          persona: u?.persona || {},
        }));
      } catch (e) {
        console.error("No se pudieron cargar alumnos:", e?.message || e);
        this.alumnosCache = [];
      }
      return this.alumnosCache;
    },

    // =============== SWEETALERT FORM ===============
    createFormHtml() {
      const today = new Date();
      const yyyy = today.getFullYear();
      const mm = String(today.getMonth() + 1).padStart(2, "0");
      const dd = String(today.getDate()).padStart(2, "0");
      const minDate = `${yyyy}-${mm}-${dd}`;

      const optsGrupo = this.cohortesVisibles
        .map((c) => `<option value="${this.escape(c)}">${this.escape(c)}</option>`)
        .join("");

      return `
        <form id="swForm" class="sw-form text-start">
          <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between bg-white border-0">
              <h6 class="m-0 fw-semibold">Datos principales</h6>
              <button id="btnToggleDatos" type="button" class="btn btn-sm btn-outline-secondary rounded-pill">
                <i id="icoDatos" class="bi bi-chevron-up"></i>
                <span id="txtDatos" class="ms-1">Ocultar</span>
              </button>
            </div>

            <div class="card-body" id="secDatos">
              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label">
                    Nombre <span class="text-danger">*</span>
                    <i class="bi bi-info-circle ms-1 text-muted" title="Título breve que verán los alumnos (ej. Respiración 4-7-8)."></i>
                  </label>
                  <input type="text" id="f_nombre" class="form-control" placeholder="Ej. Respiración consciente 4-7-8" maxlength="150" />
                </div>

                <div class="col-12">
                  <label class="form-label">
                    Buscar técnica <span class="text-danger">*</span>
                    <i class="bi bi-info-circle ms-1 text-muted" title="Escribe para buscar y luego haz clic en la técnica encontrada."></i>
                  </label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input id="f_tecnicaQ" type="text" class="form-control" placeholder="Escribe el nombre de la técnica…" aria-label="Buscar técnica" />
                    <button id="btnClearTec" type="button" class="btn btn-outline-secondary" aria-label="Limpiar búsqueda" style="display:none">
                      <i class="bi bi-x-lg"></i>
                    </button>
                  </div>

                  <div id="f_tecnicaList" class="list-group mt-2 tecnica-list"></div>

                  <div id="f_tecnicaSel" class="alert alert-success mt-2 mb-0 py-2" style="display:none">
                    <i class="bi bi-check-circle me-1"></i>
                    Técnica seleccionada:
                    <strong id="f_tecnicaSelName"></strong>
                    <button id="f_btnCambiarTec" type="button" class="btn btn-sm btn-link ms-2">Cambiar</button>
                  </div>

                  <input type="hidden" id="f_tecnicaId" />
                </div>

                <div class="col-12">
                  <label class="form-label">
                    Descripción <span class="text-danger">*</span>
                    <i class="bi bi-info-circle ms-1 text-muted" title="Instrucciones, objetivo, duración sugerida o recomendaciones."></i>
                  </label>
                  <textarea id="f_desc" class="form-control" rows="3" placeholder="Instrucciones, objetivo, duración sugerida…"></textarea>
                </div>

                <div class="col-12 col-md-6">
                  <label class="form-label">
                    Fecha máxima <span class="text-danger">*</span>
                    <i class="bi bi-info-circle ms-1 text-muted" title="Último día para completar la actividad."></i>
                  </label>
                  <input type="date" id="f_fechaMax" class="form-control" min="${minDate}" />
                </div>
              </div>
            </div>
          </div>

          <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between bg-white border-0">
              <h6 class="m-0 fw-semibold">Asignación</h6>
              <button id="btnToggleAsig" type="button" class="btn btn-sm btn-outline-secondary rounded-pill">
                <i id="icoAsig" class="bi bi-chevron-up"></i>
                <span id="txtAsig" class="ms-1">Ocultar</span>
              </button>
            </div>

            <div class="card-body" id="secAsig">
              <label class="form-label d-block">
                Asignar a
                <i class="bi bi-info-circle ms-1 text-muted" title="Un alumno específico o un grupo completo."></i>
              </label>

              <div class="d-flex flex-wrap gap-3 mb-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="f_asignarA" id="f_asigAlumno" value="alumno">
                  <label class="form-check-label" for="f_asigAlumno">Un alumno</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="f_asignarA" id="f_asigGrupo" value="grupo" checked>
                  <label class="form-check-label" for="f_asigGrupo">Todo un grupo</label>
                </div>
              </div>

              <div id="secAlumno" style="display:none">
                <label class="form-label">Buscar alumno <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-search"></i></span>
                  <input id="f_alumnoQ" type="text" class="form-control" placeholder="Nombre, matrícula o correo…" aria-label="Buscar alumno" />
                  <button id="btnClearAlu" type="button" class="btn btn-outline-secondary" aria-label="Limpiar búsqueda" style="display:none">
                    <i class="bi bi-x-lg"></i>
                  </button>
                </div>
                <small class="text-muted d-block mt-1">Escribe al menos 2 caracteres.</small>
                <div id="f_alumnoList" class="list-group mt-2 sw-list" style="max-height:240px;overflow:auto;"></div>
                <input type="hidden" id="f_alumnoId" />
                <div id="f_alumnoSel" class="form-text mt-1"></div>
              </div>

              <div id="secGrupo">
                <label class="form-label">
                  Cohorte / Grupo <span class="text-danger">*</span>
                </label>
                <select id="f_grupo" class="form-select">
                  <option value="">Selecciona un grupo…</option>
                  ${optsGrupo}
                </select>
                <div id="f_grupoHelp" class="form-text" style="display:none"></div>
              </div>
            </div>
          </div>
        </form>
      `;
    },

    attachFormBehavior(containerEl) {
      const $ = (sel) => containerEl.querySelector(sel);

      const makeToggle = (btnId, secId, icoId, txtId) => {
        const btn = $(btnId), sec = $(secId), ico = $(icoId), txt = $(txtId);
        if (!btn || !sec || !ico || !txt) return;
        btn.addEventListener("click", () => {
          const isOpen = sec.style.display !== "none";
          if (isOpen) {
            sec.style.display = "none";
            ico.className = "bi bi-chevron-down";
            txt.textContent = "Mostrar";
          } else {
            sec.style.display = "";
            ico.className = "bi bi-chevron-up";
            txt.textContent = "Ocultar";
          }
        });
      };
      makeToggle("#btnToggleDatos", "#secDatos", "#icoDatos", "#txtDatos");
      makeToggle("#btnToggleAsig", "#secAsig", "#icoAsig", "#txtAsig");

      // Técnica search
      const inpTec = $("#f_tecnicaQ");
      const btnClearTec = $("#btnClearTec");
      const lstTec = $("#f_tecnicaList");
      const hidTec = $("#f_tecnicaId");
      const selBox = $("#f_tecnicaSel");
      const selName = $("#f_tecnicaSelName");
      const btnCambiar = $("#f_btnCambiarTec") || $("#btnCambiarTec");

      const renderTec = (items) => {
        lstTec.innerHTML = items.map((t) => {
          const id = this.escape(String(t._id || t.id));
          const name = this.escape(t.nombre || "");
          const cat = t.categoria ? `<small class="text-muted d-block">Categoría: ${this.escape(t.categoria)}</small>` : "";
          return `
            <button type="button"
              class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
              data-id="${id}" data-name="${name}">
              <span>
                <strong>${name}</strong>
                ${cat}
              </span>
              <i class="bi bi-check2-circle" style="opacity:.6"></i>
            </button>
          `;
        }).join("");
        Array.from(lstTec.querySelectorAll("button")).forEach((b) => {
          b.addEventListener("click", () => {
            hidTec.value = b.getAttribute("data-id") || "";
            selName.textContent = b.getAttribute("data-name") || (inpTec.value || "");
            selBox.style.display = "";
            lstTec.innerHTML = "";
          });
        });
      };

      const filterTec = () => {
        const q = (inpTec?.value || "").toLowerCase().trim();
        if (btnClearTec) btnClearTec.style.display = q ? "" : "none";
        if (!q) { lstTec.innerHTML = ""; return; }
        const items = (this.tecnicas || [])
          .filter((t) => (t?.nombre || "").toLowerCase().includes(q))
          .slice(0, 20);
        renderTec(items);
      };

      if (inpTec) inpTec.addEventListener("input", filterTec);
      if (btnClearTec) btnClearTec.addEventListener("click", () => {
        if (!inpTec) return;
        inpTec.value = "";
        hidTec.value = "";
        selBox.style.display = "none";
        selName.textContent = "";
        lstTec.innerHTML = "";
        inpTec.focus();
      });
      if (btnCambiar) btnCambiar.addEventListener("click", () => {
        hidTec.value = "";
        selBox.style.display = "none";
        inpTec && inpTec.focus();
      });

      // Radios de asignación
      const asigAlumno = $("#f_asigAlumno");
      const asigGrupo = $("#f_asigGrupo");
      const secAlumno = $("#secAlumno");
      const secGrupo = $("#secGrupo");
      const grupoSel = $("#f_grupo");
      const grupoHelp = $("#f_grupoHelp");

      const toggleAsignacion = () => {
        const alumno = !!(asigAlumno && asigAlumno.checked);
        if (secAlumno) secAlumno.style.display = alumno ? "" : "none";
        if (secGrupo) secGrupo.style.display = alumno ? "none" : "";
      };
      if (asigAlumno) asigAlumno.addEventListener("change", toggleAsignacion);
      if (asigGrupo) asigGrupo.addEventListener("change", toggleAsignacion);
      toggleAsignacion();

      if (grupoSel) {
        grupoSel.addEventListener("change", () => {
          const v = (grupoSel.value || "").trim();
          if (!grupoHelp) return;
          grupoHelp.style.display = v ? "" : "none";
          grupoHelp.innerHTML = v ? `Se asignará a todos los alumnos del grupo <strong>${this.escape(v)}</strong>.` : "";
        });
      }

      // Buscar alumno
      const aluQ = $("#f_alumnoQ");
      const aluList = $("#f_alumnoList");
      const aluId = $("#f_alumnoId");
      const aluSel = $("#f_alumnoSel");

      const renderAlumnos = (items) => {
        aluList.innerHTML = items.map((u) => {
          const coh = this.cohorteStr(u) || "—";
          const ok = this.puedeTomarlo(u);
          const badge = ok ? "bg-success" : "bg-danger";
          const label = ok ? "A su cargo" : "Fuera de sus grupos";
          return `
            <button type="button"
              class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
              data-id="${this.escape(u._key)}"
              data-ok="${ok ? "1" : "0"}">
              <span>
                <strong>${this.escape(u.name)}</strong>
                <small class="text-muted d-block">${this.escape(u.email)}</small>
                <small class="text-muted d-block">Cohorte: ${this.escape(coh)}</small>
              </span>
              <span class="badge ${badge}">${label}</span>
            </button>
          `;
        }).join("");
        Array.from(aluList.querySelectorAll("button")).forEach((btn) => {
          btn.addEventListener("click", () => {
            const id = btn.getAttribute("data-id");
            const ok = btn.getAttribute("data-ok") === "1";
            const u = this.alumnosCache.find((x) => x._key === id);
            aluId.value = id;
            aluSel.innerHTML = ok
              ? `<span class="text-success"><i class="bi bi-check-circle me-1"></i>Seleccionado: <strong>${this.escape(u?.name || "")}</strong></span>`
              : `<span class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>El alumno no pertenece a tus cohortes.</span>`;
          });
        });
      };

      const doBuscarAlu = () => {
        const q = (aluQ?.value || "").trim().toLowerCase();
        if (q.length < 2) {
          aluList.innerHTML = "";
          aluId.value = "";
          aluSel.innerHTML = "";
          return;
        }
        const matches = (this.alumnosCache || []).filter((u) => {
          const inTxt =
            (u.name || "").toLowerCase().includes(q) ||
            (u.email || "").toLowerCase().includes(q) ||
            (u.matricula || "").toLowerCase().includes(q);
          return inTxt;
        });
        renderAlumnos(matches.slice(0, 20));
      };

      if (aluQ) {
        aluQ.addEventListener("input", () => {
          clearTimeout(this._buscarAlumnoDebounce);
          this._buscarAlumnoDebounce = setTimeout(doBuscarAlu, 180);
        });
      }
    },

    async openCreate() {
      await this._ensureAlumnosCache();

      const result = await Swal.fire({
        title: "Registrar actividad",
        html: this.createFormHtml(),
        width: 820,
        focusConfirm: false,
        showCancelButton: true,
        showCloseButton: true,
        confirmButtonText: "Guardar",
        cancelButtonText: "Cancelar",
        reverseButtons: true,
        customClass: {
          container: "swal2-pt",
          popup: "swal2-rounded",
          confirmButton: "btn btn-gradient",
          cancelButton: "btn btn-outline-secondary ms-2",
          actions: "sw-actions",
          closeButton: "swal2-close-pt",
        },
        didOpen: (el) => this.attachFormBehavior(el),
        preConfirm: () => {
          const el = Swal.getHtmlContainer();
          const $ = (sel) => el.querySelector(sel);

          const nombre = ($("#f_nombre")?.value || "").trim();
          const tecnica_id = ($("#f_tecnicaId")?.value || "").trim();
          const descripcion = ($("#f_desc")?.value || "").trim();
          const fechaMaxima = ($("#f_fechaMax")?.value || "").trim();

          const asigAlumno = $("#f_asigAlumno")?.checked;
          const asignarA = asigAlumno ? "alumno" : "grupo";
          const alumno_id = ($("#f_alumnoId")?.value || "").trim();
          const grupo = ($("#f_grupo")?.value || "").trim();

          const faltantes = [];
          if (!nombre) faltantes.push("nombre");
          if (!tecnica_id) faltantes.push("técnica");
          if (!descripcion) faltantes.push("descripción");
          if (!fechaMaxima) faltantes.push("fecha máxima");
          if (asignarA === "alumno" && !alumno_id) faltantes.push("alumno");
          if (asignarA === "grupo" && !grupo) faltantes.push("grupo");

          const hoy = new Date(); hoy.setHours(0,0,0,0);
          const fm = fechaMaxima ? new Date(fechaMaxima) : null;
          if (!fm || (fm.setHours(0,0,0,0), fm) < hoy) {
            faltantes.push("fecha máxima (hoy o posterior)");
          }

          if (faltantes.length) {
            Swal.showValidationMessage("Completa: " + faltantes.join(", "));
            return false;
          }

          if (asignarA === "alumno") {
            const u = this.alumnosCache.find((x) => x._key === alumno_id);
            if (!this.puedeTomarlo(u)) {
              Swal.showValidationMessage("El alumno seleccionado no pertenece a tus cohortes.");
              return false;
            }
          }

          return { nombre, tecnica_id, descripcion, fechaMaxima, asignarA, alumno_id, grupo };
        },
      });

      if (!result.isConfirmed) return;

      try {
        this.submitting = true;

        const {
          nombre, tecnica_id, descripcion, fechaMaxima,
          asignarA, alumno_id, grupo
        } = result.value;

        let participantes = [];
        let resumenAsignacion = "";
        if (asignarA === "alumno") {
          participantes = [{ user_id: alumno_id, estado: "Pendiente" }];
          const u = this.alumnosCache.find((x) => x._key === alumno_id);
          resumenAsignacion = `1 alumno (${u?.name || alumno_id})`;
        } else {
          const delGrupo = this.alumnosCache.filter((u) => {
            const c = u?.persona?.cohorte;
            return Array.isArray(c) ? c.includes(String(grupo)) : String(c || "") === String(grupo);
          });
          if (this.usuario?.rol === "profesor" && !this.inMisCohortes(grupo)) {
            this.toast("No puedes asignar a un grupo que no es de tu cargo.", "error");
            return;
          }
          participantes = delGrupo.map((u) => ({ user_id: u._key, estado: "Pendiente" }));
          resumenAsignacion = `${participantes.length} alumnos del grupo ${grupo}`;
        }

        const payload = {
          fechaMaxima,
          nombre,
          tecnica_id,
          descripcion,
          docente_id: this.myId,
          participantes,
        };

        const tName = (this.tecnicas.find((t) => String(t._id || t.id) === String(tecnica_id))?.nombre) || "—";
        const htmlResumen = `
          <div class="text-start">
            <p class="mb-1"><strong>Nombre:</strong> ${this.escape(nombre)}</p>
            <p class="mb-1"><strong>Máxima:</strong> ${this.escape(fechaMaxima)}</p>
            <p class="mb-1"><strong>Técnica:</strong> ${this.escape(tName)}</p>
            <p class="mb-1"><strong>Asignados:</strong> ${this.escape(resumenAsignacion)}</p>
          </div>
        `;

        const ok = await Swal.fire({
          title: "¿Crear actividad?",
          html: htmlResumen,
          icon: "question",
          showCancelButton: true,
          confirmButtonText: "Sí, crear",
          cancelButtonText: "Cancelar",
          customClass: { container: "swal2-pt" },
        });
        if (!ok.isConfirmed) return;

        const resp = await createActividad(payload);
        this.toast(resp?.mensaje || "Actividad creada correctamente.", "success");
        await this.cargarActividades();
      } catch (e) {
        const msg = e?.response?.data?.message || e?.message || "Error al crear la actividad.";
        this.toast(msg, "error");
      } finally {
        this.submitting = false;
      }
    },

    async verDetalles(a) {
      await this._ensureAlumnosCache();
      const map = new Map(this.alumnosCache.map((u) => [String(u._key), u]));

      const total = Array.isArray(a?.participantes) ? a.participantes.length : 0;
      const items = (a?.participantes || []).map((p) => {
        const u = map.get(String(p.user_id));
        const nombre = u?.name || `ID ${p.user_id}`;
        const coh = this.cohorteStr(u) || "—";
        const estado = p?.estado || "Pendiente";
        const badge = estado === "Completado" ? "success" : (estado === "Omitido" ? "secondary" : "warning");
        return `
          <div class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <div class="fw-semibold">${this.escape(nombre)}</div>
              <div class="text-muted small">Cohorte: ${this.escape(coh)}</div>
            </div>
            <span class="badge bg-${badge}">${this.escape(estado)}</span>
          </div>
        `;
      }).join("");

      const html = `
        <div class="text-start">
          <div class="mb-2"><span class="badge bg-primary-subtle text-primary border">Total: ${total}</span></div>
          <div class="list-group">${items || '<div class="text-muted">No hay participantes registrados.</div>'}</div>
        </div>
      `;

      await Swal.fire({
        title: `Participantes • ${this.escape(a.nombre)}`,
        html,
        width: 720,
        confirmButtonText: "Cerrar",
        customClass: { container: "swal2-pt" },
      });
    },

    toast(text, icon = "info") {
      Swal.fire({
        toast: true,
        position: "top-end",
        timer: 2600,
        showConfirmButton: false,
        icon,
        title: text,
        customClass: { container: "swal2-toast-pt" },
      });
    },
    escape(s) {
      if (s == null) return "";
      return String(s).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
    },
  },
};
</script>



<style scoped>
:root { --ink:#1b3b6f; --ink-2:#2c4c86; --sky:#eaf3ff; --card-b:#f8fbff; --stroke:#e6eefc; --chip:#eef6ff; --chip-ink:#2c4c86; }
.fw-600 { font-weight: 600; }

.filter-label { font-size:.9rem; color:var(--ink-2); font-weight:600; }

/* Selectores modernos */
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

/* Fila de fechas */
.filters-row{ display:flex; align-items:center; gap:.75rem; }
.filters-labels small{ white-space:nowrap; }
.filters-controls{ display:flex; gap:.5rem; }
.filters-controls .form-control{ min-width: 10.5rem; }

/* CTA */
.gradient-card { background: linear-gradient(135deg, #6a8dff, #7b5cff); border-radius: 16px; }
.btn-gradient { background: linear-gradient(135deg, #6a8dff, #7b5cff); color:#fff; border:0; }
.btn-gradient:hover { filter: brightness(.95); color:#fff; }

/* Chips */
.chip{
  display:inline-flex; align-items:center; gap:.5rem; padding:.35rem .65rem;
  border-radius:999px; font-size:.84rem; background:#fff; border:1px solid var(--stroke);
}
.chip-info { background:var(--chip); color:var(--chip-ink); border-color:#d8e6ff; }
.chip .chip-x{ border:0; background:transparent; padding:0; margin-left:.25rem; line-height:0; color:inherit; cursor:pointer; }

/* Tabla */
.table td, .table th { vertical-align: middle; }

/* ======== SweetAlert: overlay y popup ======== */
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

/* ======== Formulario SweetAlert ======== */
.sw-card{ border:1px solid var(--stroke); border-radius: 16px; background: #fff; }
.sw-card-header{
  position: relative;
  display:flex; align-items:center; justify-content:flex-start;
  gap:.75rem; padding:.75rem 1rem;
  background:#fff; border-bottom:1px solid var(--stroke);
  border-top-left-radius:16px; border-top-right-radius:16px;
}
.sw-card-header h6{ display:flex; align-items:center; gap:.5rem; margin:0; }
.sw-toggle-top{
  position:absolute; right:12px; top:50%; transform:translateY(-50%);
}

/* ====== Barra de búsqueda tipo 'pill' ====== */
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

/* Responsive */
@media (max-width: 576px){
  .sw-search-group{ padding:.25rem .45rem; }
  .sw-card-header h6{ font-size: .96rem; }
}

/* Lista con bordes suaves */
:deep(.sw-form .sw-list .list-group-item){ border-radius: 10px; margin-bottom:.35rem; }
</style>
