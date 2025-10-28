<!-- src/views/administrador/Actividades.vue -->
<template>
  <main class="panel-wrapper container-fluid py-3 py-lg-4">
    <!-- ======= Toolbar moderna ======= -->
    <div class="toolbar px-0 px-lg-2">
      <div class="row g-2 align-items-center">
        <!-- Filtros (se aplican automáticamente) -->
        <div class="col-12 col-xl-8">
          <div class="card border-0 shadow-sm mb-2">
            <div class="card-body py-3">
              <div class="row g-2 align-items-end">
                <div class="col-12">
                  <div
                    class="input-group input-group-lg search-group shadow-sm rounded-pill"
                    role="search"
                    aria-label="Filtrar actividades"
                  >
                    <span class="input-group-text rounded-start-pill">
                      <i class="bi bi-funnel"></i>
                    </span>

                    <!-- Docente (solo visible para admin/roles no profesor) -->
                    <select
                      v-if="mostrarFiltroDocente"
                      v-model="filtros.docenteId"
                      class="form-select border-0"
                      aria-label="Filtrar por docente"
                    >
                      <option value="">Todos los docentes</option>
                      <option
                        v-for="d in docentes"
                        :key="d._id || d.id"
                        :value="String(d._id || d.id)"
                      >
                        {{ d.name }}
                      </option>
                    </select>

                    <!-- Cohorte -->
                    <select v-model="filtros.cohorte" class="form-select border-0" aria-label="Filtrar por cohorte">
                      <option value="">Todas las cohortes</option>
                      <option v-for="c in cohortesVisibles" :key="c" :value="c">{{ c }}</option>
                    </select>

                    <!-- Rango de fechas -->
                    <input
                      type="date"
                      class="form-control border-0"
                      v-model="filtros.desde"
                      :max="filtros.hasta || undefined"
                      aria-label="Fecha desde"
                    />
                    <input
                      type="date"
                      class="form-control border-0"
                      v-model="filtros.hasta"
                      :min="filtros.desde || undefined"
                      aria-label="Fecha hasta"
                    />
                  </div>
                </div>

                <!-- Acciones -->
                <div class="col-12 text-end">
                  <button class="btn btn-gradient fw-semibold shadow-sm rounded-pill px-4 py-2" @click="openCreate">
                    <i class="bi bi-plus-lg me-1"></i> Nueva actividad
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Chips de estado -->
          <div class="d-flex flex-wrap gap-2 mb-3">
            <span class="chip">Total en página: <strong>{{ totalVisible }}</strong></span>

            <span v-if="mostrarFiltroDocente && filtros.docenteId" class="chip chip-info">
              Docente:
              <strong>{{ docenteNombre(filtros.docenteId) }}</strong>
              <button class="chip-x" @click="filtros.docenteId=''" aria-label="Quitar docente">
                <i class="bi bi-x"></i>
              </button>
            </span>

            <span v-if="filtros.cohorte" class="chip chip-info">
              Cohorte: <strong>{{ filtros.cohorte }}</strong>
              <button class="chip-x" @click="filtros.cohorte=''" aria-label="Quitar cohorte">
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

        <!-- CTA lateral -->
        <div class="col-12 col-xl-4">
          <div class="card gradient-card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center justify-content-between gap-3">
              <div>
                <div class="fw-semibold text-white-50 small">Gestión</div>
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
              </tr>
            </thead>
            <tbody>
              <tr v-if="registros.length === 0">
                <td colspan="5" class="text-center py-4 text-muted">
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

    <!-- ======= Modal: Crear actividad ======= -->
    <div class="modal fade" id="modalCreate" tabindex="-1" ref="modalEl" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content animate-pop">
          <div class="modal-header border-0">
            <h5 class="modal-title fw-bold">Registrar actividad</h5>
            <button type="button" class="btn-close" @click="closeCreate" aria-label="Cerrar"></button>
          </div>

          <div class="modal-body">
            <!-- Datos principales -->
            <div class="card mb-3">
              <div class="card-body">
                <div class="row g-3">
                  <!-- Nombre -->
                  <div class="col-12">
                    <label class="form-label">
                      Nombre <span class="text-danger">*</span>
                      <i class="bi bi-info-circle ms-1 text-muted"
                         data-bs-toggle="tooltip"
                         title="Título breve que verán los alumnos (ej. Respiración 4-7-8)."></i>
                    </label>
                    <input
                      type="text"
                      class="form-control"
                      v-model.trim="form.nombre"
                      maxlength="150"
                      required
                      placeholder="Ej. Respiración consciente 4-7-8"
                    />
                  </div>

                  <!-- Búsqueda de técnica (sin select) -->
                  <div class="col-12">
                    <label class="form-label">
                      Buscar técnica <span class="text-danger">*</span>
                      <i class="bi bi-info-circle ms-1 text-muted"
                         data-bs-toggle="tooltip"
                         title="Escribe para buscar y luego haz clic en la técnica encontrada."></i>
                    </label>
                    <div class="input-group">
                      <span class="input-group-text"><i class="bi bi-search"></i></span>
                      <input
                        type="search"
                        class="form-control"
                        v-model.trim="tecnicaQuery"
                        placeholder="Escribe el nombre de la técnica…"
                        @input="filtrarTecnicas"
                        aria-label="Buscar técnica"
                      />
                      <button v-if="tecnicaQuery" class="btn btn-outline-secondary" @click="clearTecnicaBusqueda" aria-label="Limpiar búsqueda">
                        <i class="bi bi-x-lg"></i>
                      </button>
                    </div>

                    <!-- Resultados buscador -->
                    <div v-if="tecnicaQuery" class="list-group mt-2 tecnica-list">
                      <button
                        v-for="t in tecnicasFiltradasModal"
                        :key="t._id || t.id"
                        type="button"
                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                        @click="seleccionarTecnica(t)"
                      >
                        <span>
                          <strong>{{ t.nombre }}</strong>
                          <small v-if="t.categoria" class="text-muted d-block">Categoría: {{ t.categoria }}</small>
                        </span>
                        <i class="bi bi-check2-circle" v-if="form.tecnica_id === String(t._id || t.id)"></i>
                      </button>
                      <div v-if="tecnicaQuery && tecnicasFiltradasModal.length === 0" class="text-muted small px-2 py-1">
                        No se encontraron técnicas con “{{ tecnicaQuery }}”.
                      </div>
                    </div>

                    <!-- Técnica seleccionada -->
                    <div v-if="form.tecnica_id" class="alert alert-success mt-2 mb-0 py-2">
                      <i class="bi bi-check-circle me-1"></i>
                      Técnica seleccionada:
                      <strong>{{ tecnicaSeleccionadaNombre }}</strong>
                      <button class="btn btn-sm btn-link ms-2" @click="quitarTecnica">Cambiar</button>
                    </div>
                  </div>

                  <!-- Descripción -->
                  <div class="col-12">
                    <label class="form-label">
                      Descripción <span class="text-danger">*</span>
                      <i class="bi bi-info-circle ms-1 text-muted"
                         data-bs-toggle="tooltip"
                         title="Instrucciones, objetivo, duración sugerida o recomendaciones."></i>
                    </label>
                    <textarea
                      class="form-control"
                      v-model.trim="form.descripcion"
                      rows="3"
                      required
                      placeholder="Instrucciones, objetivo, duración sugerida…"
                    ></textarea>
                  </div>

                  <!-- Fecha máxima -->
                  <div class="col-12 col-md-6">
                    <label class="form-label">
                      Fecha máxima <span class="text-danger">*</span>
                      <i class="bi bi-info-circle ms-1 text-muted"
                         data-bs-toggle="tooltip"
                         title="Último día en que el alumno puede completar la actividad."></i>
                    </label>
                    <input type="date" class="form-control" v-model="form.fechaMaxima" @change="validarFechas" required />
                    <div class="invalid-feedback d-block" v-if="errors.includes('fm_invalida')">
                      Selecciona una fecha máxima válida (hoy o posterior).
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Asignación -->
            <div class="card">
              <div class="card-body">
                <label class="form-label d-block">
                  Asignar a
                  <i class="bi bi-info-circle ms-1 text-muted"
                     data-bs-toggle="tooltip"
                     title="Elige si la asignación será para un solo alumno o para un grupo completo."></i>
                </label>

                <div class="d-flex flex-wrap gap-3 mb-3">
                  <div class="form-check">
                    <input class="form-check-input" type="radio" id="asigAlumno" value="alumno" v-model="form.asignarA" />
                    <label class="form-check-label" for="asigAlumno">Un alumno</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" id="asigGrupo" value="grupo" v-model="form.asignarA" />
                    <label class="form-check-label" for="asigGrupo">Todo un grupo</label>
                  </div>
                </div>

                <!-- Asignación a alumno -->
                <div v-if="form.asignarA === 'alumno'">
                  <label class="form-label">
                    Buscar alumno
                    <i class="bi bi-info-circle ms-1 text-muted"
                       data-bs-toggle="tooltip"
                       title="Escribe nombre, matrícula o correo. Solo se mostrarán coincidencias reales."></i>
                  </label>
                  <div class="input-group">
                    <input
                      type="search"
                      class="form-control"
                      v-model.trim="alumnoQuery"
                      placeholder="Nombre, matrícula o correo…"
                      @input="buscarAlumnos"
                      aria-label="Buscar alumno"
                    />
                    <button class="btn btn-outline-secondary" type="button" @click="buscarAlumnos" aria-label="Buscar">
                      <i class="bi bi-search"></i>
                    </button>
                  </div>
                  <small class="text-muted d-block mt-1">Escribe al menos 2 caracteres. Solo se mostrarán coincidencias.</small>

                  <div v-if="alumnosFiltrados.length" class="list-group mt-2 alumno-list">
                    <button
                      v-for="u in alumnosFiltrados"
                      :key="u._id || u.id"
                      type="button"
                      class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                      @click="seleccionarAlumno(u)"
                    >
                      <span>
                        <strong>{{ u.name }}</strong>
                        <small class="text-muted d-block">{{ u.email }}</small>
                        <small class="text-muted d-block">Cohorte: {{ cohorteStr(u) || '—' }}</small>
                      </span>
                      <span class="badge" :class="puedeTomarlo(u) ? 'bg-success' : 'bg-danger'">
                        {{ puedeTomarlo(u) ? 'A su cargo' : 'Fuera de sus grupos' }}
                      </span>
                    </button>
                  </div>

                  <div
                    v-if="alumnoSeleccionado"
                    class="alert mt-2"
                    :class="puedeTomarlo(alumnoSeleccionado) ? 'alert-success' : 'alert-danger'"
                  >
                    <i class="bi" :class="puedeTomarlo(alumnoSeleccionado) ? 'bi-check-circle' : 'bi-exclamation-triangle'"></i>
                    <strong> Seleccionado:</strong> {{ alumnoSeleccionado.name }}
                    <span class="ms-2 text-muted">({{ cohorteStr(alumnoSeleccionado) || '—' }})</span>
                  </div>
                </div>

                <!-- Asignación a grupo -->
                <div v-if="form.asignarA === 'grupo'">
                  <label class="form-label">
                    Cohorte / Grupo <span class="text-danger">*</span>
                    <i class="bi bi-info-circle ms-1 text-muted"
                       data-bs-toggle="tooltip"
                       title="Grupo al que se asignará la actividad."></i>
                  </label>
                  <select class="form-select" v-model="grupoSeleccionado" required>
                    <option value="" disabled>Selecciona un grupo…</option>
                    <option v-for="c in cohortesVisibles" :key="c" :value="c">{{ c }}</option>
                  </select>
                  <div v-if="grupoSeleccionado" class="form-text">
                    Se asignará a todos los alumnos del grupo <strong>{{ grupoSeleccionado }}</strong>.
                  </div>
                </div>

                <!-- Errores locales -->
                <div v-if="errors.length" class="alert alert-warning mt-3">
                  <ul class="mb-0 ps-3">
                    <li v-for="(e, i) in errors" :key="i">{{ e }}</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer border-0">
            <button class="btn btn-outline-secondary" type="button" @click="closeCreate">Cancelar</button>
            <button class="btn btn-gradient" type="button" :disabled="submitting" @click="confirmarCrear">
              <span v-if="!submitting"><i class="bi bi-check2-circle me-1"></i>Guardar</span>
              <span v-else class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
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

      filtros: { docenteId: "", cohorte: "", desde: "", hasta: "" },

      // Modal / formulario
      tecnicas: [],
      tecnicaQuery: "",
      form: {
        // fechaAsignacion se guarda en el backend automáticamente
        fechaMaxima: "",
        nombre: "",
        tecnica_id: "",
        descripcion: "",
        asignarA: "grupo",
      },

      alumnosCache: [],
      alumnoQuery: "",
      alumnosFiltrados: [],
      alumnoSeleccionado: null,
      grupoSeleccionado: "",

      cohortesGlobales: [],
      submitting: false,
      errors: [],
      _buscarAlumnoDebounce: null,
      _filtroDebounce: null,
      _modalInstance: null,
    };
  },
  computed: {
    mostrarFiltroDocente() {
      return this.usuario && this.usuario.rol !== "profesor";
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
    tecnicasFiltradasModal() {
      const q = (this.tecnicaQuery || "").toLowerCase().trim();
      if (!q) return [];
      return this.tecnicas.filter((t) => (t?.nombre || "").toLowerCase().includes(q)).slice(0, 20);
    },
    tecnicaSeleccionadaNombre() {
      const id = this.form.tecnica_id;
      if (!id) return "";
      const t = this.tecnicas.find((x) => String(x._id || x.id) === String(id));
      return t?.nombre || "";
    },
  },
  watch: {
    // Aplicar filtros automáticamente
    "filtros.docenteId": "onFiltrosChange",
    "filtros.cohorte": "onFiltrosChange",
    "filtros.desde": "onFiltrosChange",
    "filtros.hasta": "onFiltrosChange",
  },
  async mounted() {
    await this.bootstrap();
    this.bootstrapModal();
  },
  methods: {
    fmt(d) {
      return d || "—";
    },
    docenteNombre(id) {
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
      const mis = this.usuario?.persona?.cohorte;
      const del = u?.persona?.cohorte;
      if (!mis) return false;
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

      // Docentes (para filtro solo si no es profesor)
      if (this.mostrarFiltroDocente) {
        this.docentes = await fetchDocentes();
      } else {
        // si es profesor, el filtro queda fijado a su propio id
        this.filtros.docenteId = String(this.usuario?._id || this.usuario?.id || "");
      }

      // Técnicas para buscador del formulario
      this.tecnicas = await fetchTecnicas();

      // Cohortes globales (para admin/otros)
      const alumnosAll = await this._ensureAlumnosCache();
      if (this.usuario?.rol !== "profesor") {
        const set = new Set();
        for (const u of alumnosAll) {
          const c = u?.persona?.cohorte;
          if (Array.isArray(c)) c.forEach((v) => v && set.add(String(v)));
          else if (c) set.add(String(c));
        }
        this.cohortesGlobales = Array.from(set).sort();
      }

      await this.cargarActividades();
    },

    bootstrapModal() {
      const el = this.$refs.modalEl;
      if (!el) return;

      // Habilitar tooltips de Bootstrap
      const enableTooltips = () => {
        const tipEls = el.querySelectorAll('[data-bs-toggle="tooltip"]');
        tipEls.forEach((t) => {
          try {
            // eslint-disable-next-line no-new
            new window.bootstrap.Tooltip(t);
          } catch {}
        });
      };

      if (window.bootstrap?.Modal) {
        el.addEventListener("shown.bs.modal", () => {
          // activamos animación
          const content = el.querySelector(".modal-content");
          if (content) {
            content.classList.remove("animate-pop"); // reset
            // Forzar reflow
            void content.offsetWidth;
            content.classList.add("animate-pop");
          }
          enableTooltips();
        });
      } else {
        // sin Bootstrap JS
        enableTooltips();
      }
    },

    openCreate() {
      this.resetForm();
      const el = this.$refs.modalEl;
      if (window.bootstrap?.Modal) {
        const m = new window.bootstrap.Modal(el, { backdrop: "static" });
        m.show();
        this._modalInstance = m;
      } else {
        el.style.display = "block";
        el.removeAttribute("aria-hidden");
        el.classList.add("show");
      }
    },
    closeCreate() {
      const el = this.$refs.modalEl;
      if (this._modalInstance) this._modalInstance.hide();
      else {
        el.style.display = "none";
        el.setAttribute("aria-hidden", "true");
        el.classList.remove("show");
      }
    },

    resetForm() {
      this.form = {
        fechaMaxima: "",
        nombre: "",
        tecnica_id: "",
        descripcion: "",
        asignarA: "grupo",
      };
      this.tecnicaQuery = "";
      this.alumnoQuery = "";
      this.alumnosFiltrados = [];
      this.alumnoSeleccionado = null;
      this.grupoSeleccionado = "";
      this.errors = [];
    },

    validarFechas() {
      // Limpiamos y validamos que la fecha máxima sea hoy o posterior
      this.errors = this.errors.filter((e) => e !== "fm_invalida");
      const fm = this.form.fechaMaxima;
      if (!fm) return;
      const hoy = new Date();
      const sel = new Date(fm + "T00:00:00");
      if (sel < new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate())) {
        this.errors.push("fm_invalida");
      }
    },

    // Debounce para filtros automáticos
    onFiltrosChange() {
      if (this._filtroDebounce) clearTimeout(this._filtroDebounce);
      this._filtroDebounce = setTimeout(() => this.cargarActividades(), 200);
    },

    async cargarActividades(extraParams = {}) {
      const params = { ...extraParams };
      if (this.filtros.cohorte) params.cohorte = this.filtros.cohorte;
      if (this.filtros.desde) params.desde = this.filtros.desde;
      if (this.filtros.hasta) params.hasta = this.filtros.hasta;

      // Docente: para admin viene de select; para profesor ya está seteado en mounted
      if (this.filtros.docenteId) {
        params.docente_id = this.filtros.docenteId;
      } else if (this.usuario?.rol === "profesor") {
        params.docente_id = String(this.usuario?._id || this.usuario?.id);
      }

      const data = await fetchActividades(params);
      this.registros = data?.registros || [];
      this.enlaces = data?.enlaces || { anterior: null, siguiente: null };
      this.totalVisible = this.registros.length;

      // Página actual / total
      this.paginaActual = this._pageFromUrl(this.enlaces.anterior) + 1;
      const last = this._pageFromUrl(data?.enlaces?.ultimo);
      this.totalPaginas = last || (this.enlaces.siguiente ? this.paginaActual + 1 : this.paginaActual);
    },
    go(url) {
      if (!url) return;
      const p = paramsFromPaginationUrl(url);
      this.paginaActual = p?.page || this.paginaActual;
      this.cargarActividades(p);
    },
    _pageFromUrl(url) {
      if (!url) return 0;
      try {
        const u = new URL(url, window.location.origin);
        return Number(u.searchParams.get("page")) || 0;
      } catch {
        return 0;
      }
    },

    // ====== Búsqueda de alumnos (front) ======
    async _ensureAlumnosCache() {
      if (this.alumnosCache.length) return this.alumnosCache;
      try {
        const all = await fetchAlumnos({});
        this.alumnosCache = Array.isArray(all)
          ? all.map((u) => ({
              ...u,
              name: u?.name || "",
              email: u?.email || "",
              matricula: u?.matricula || "",
              persona: u?.persona || {},
            }))
          : [];
      } catch (e) {
        console.error("No se pudieron cargar alumnos:", e?.message || e);
        this.alumnosCache = [];
      }
      return this.alumnosCache;
    },

    async buscarAlumnos() {
      const q = (this.alumnoQuery || "").trim();
      if (q.length < 2) {
        this.alumnosFiltrados = [];
        this.alumnoSeleccionado = null;
        return;
      }
      if (this._buscarAlumnoDebounce) clearTimeout(this._buscarAlumnoDebounce);
      this._buscarAlumnoDebounce = setTimeout(async () => {
        const alumnos = await this._ensureAlumnosCache();
        const Q = q.toLowerCase();

        const coincide = (u) => {
          const name = String(u?.name || "").toLowerCase();
          const email = String(u?.email || "").toLowerCase();
          const mat = String(u?.matricula || "").toLowerCase();
          return name.includes(Q) || email.includes(Q) || mat.includes(Q);
        };

        const matchGrupo = (u) => {
          if (!this.grupoSeleccionado) return true;
          const c = u?.persona?.cohorte;
          if (Array.isArray(c)) return c.includes(String(this.grupoSeleccionado));
          return String(c || "") === String(this.grupoSeleccionado);
        };

        const base = alumnos.filter(coincide).filter(matchGrupo);
        const filtrados = base.filter((u) => this.puedeTomarlo(u));

        this.alumnosFiltrados = filtrados.slice(0, 20);
        if (this.alumnosFiltrados.length === 1) {
          this.alumnoSeleccionado = this.alumnosFiltrados[0];
        } else {
          this.alumnoSeleccionado = null;
        }
      }, 180);
    },

    seleccionarAlumno(u) {
      this.alumnoSeleccionado = u;
    },

    // ====== Búsqueda de técnicas (modal) ======
    filtrarTecnicas() {
      // la lista está computada; este método existe por claridad y para futuros hooks
    },
    clearTecnicaBusqueda() {
      this.tecnicaQuery = "";
    },
    seleccionarTecnica(t) {
      this.form.tecnica_id = String(t._id || t.id);
      this.tecnicaQuery = t.nombre;
    },
    quitarTecnica() {
      this.form.tecnica_id = "";
      this.tecnicaQuery = "";
    },

    async confirmarCrear() {
      // Validaciones
      this.validarFechas();

      const faltantes = [];
      if (!this.form.nombre) faltantes.push("nombre");
      if (!this.form.tecnica_id) faltantes.push("técnica");
      if (!this.form.descripcion) faltantes.push("descripción");
      if (!this.form.fechaMaxima) faltantes.push("fecha máxima");
      if (this.form.asignarA === "alumno" && !this.alumnoSeleccionado) faltantes.push("alumno");
      if (this.form.asignarA === "grupo" && !this.grupoSeleccionado) faltantes.push("grupo");

      if (faltantes.length) {
        this.toast("Completa: " + faltantes.join(", "), "warning");
        return;
      }
      if (this.errors.includes("fm_invalida")) {
        this.toast("Corrige la fecha máxima.", "warning");
        return;
      }

      let participantes = [];
      let resumenAsignacion = "";

      if (this.form.asignarA === "alumno") {
        if (!this.puedeTomarlo(this.alumnoSeleccionado)) {
          this.toast("El alumno seleccionado no pertenece a tus cohortes.", "error");
          return;
        }
        const uid = this.alumnoSeleccionado?._id || this.alumnoSeleccionado?.id;
        participantes = [{ user_id: String(uid), estado: "Pendiente" }];
        resumenAsignacion = `1 alumno (${this.alumnoSeleccionado?.name})`;
      } else {
        const grupo = this.grupoSeleccionado;
        if (this.usuario?.rol === "profesor" && !this.inMisCohortes(grupo)) {
          this.toast("No puedes asignar a un grupo que no es de tu cargo.", "error");
          return;
        }

        await this._ensureAlumnosCache();
        const delGrupo = this.alumnosCache.filter((u) => {
          const c = u?.persona?.cohorte;
          if (Array.isArray(c)) return c.includes(String(grupo));
          return String(c || "") === String(grupo);
        });

        participantes = delGrupo.map((u) => ({
          user_id: String(u._id || u.id),
          estado: "Pendiente",
        }));
        resumenAsignacion = `${participantes.length} alumnos del grupo ${grupo}`;
      }

      const payload = {
        // fechaAsignacion: se define en backend
        fechaMaxima: this.form.fechaMaxima,
        nombre: this.form.nombre,
        tecnica_id: this.form.tecnica_id,
        descripcion: this.form.descripcion,
        docente_id: String(this.usuario?._id || this.usuario?.id),
        participantes,
      };

      const htmlResumen = `
        <div class="text-start">
          <p class="mb-1"><strong>Nombre:</strong> ${this.escape(payload.nombre)}</p>
          <p class="mb-1"><strong>Máxima:</strong> ${this.escape(payload.fechaMaxima)}</p>
          <p class="mb-1"><strong>Técnica:</strong> ${this.escape(this.tecnicaSeleccionadaNombre)}</p>
          <p class="mb-1"><strong>Asignados:</strong> ${this.escape(resumenAsignacion)}</p>
        </div>
      `;

      const res = await Swal.fire({
        title: "¿Crear actividad?",
        html: htmlResumen,
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Sí, crear",
        cancelButtonText: "Cancelar",
        focusConfirm: false,
        // Evitar que se esconda bajo el navbar fijo
        customClass: { container: "swal2-pt" },
        scrollbarPadding: false,
      });
      if (!res.isConfirmed) return;

      try {
        this.submitting = true;
        const resp = await createActividad(payload);
        this.toast(resp?.mensaje || "Actividad creada correctamente.", "success");
        this.closeCreate();
        await this.cargarActividades();
      } catch (e) {
        const msg = e?.response?.data?.message || e?.message || "Error al crear la actividad.";
        this.toast(msg, "error");
      } finally {
        this.submitting = false;
      }
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
/* ===== Colores y estilo tipo "tests" ===== */
:root {
  --ink:#1b3b6f; --ink-2:#2c4c86; --sky:#eaf3ff; --card-b:#f8fbff; --stroke:#e6eefc;
  --chip:#eef6ff; --chip-ink:#2c4c86;
}

.fw-600 { font-weight: 600; }

.search-group .form-control,
.search-group .form-select { border:0 !important; box-shadow:none !important; }
.search-group .input-group-text { background:transparent; border:0; }

.gradient-card {
  background: linear-gradient(135deg, #6a8dff, #7b5cff);
  border-radius: 16px;
}

.btn-gradient {
  background: linear-gradient(135deg, #6a8dff, #7b5cff);
  color:#fff; border:0;
}
.btn-gradient:hover { filter: brightness(.95); color:#fff; }

.chip {
  display:inline-flex; align-items:center; gap:.5rem;
  padding:.35rem .65rem; border-radius:999px; font-size:.875rem;
  background:#fff; border:1px solid var(--stroke);
}
.chip-info { background:var(--chip); color:var(--chip-ink); border-color:#d8e6ff; }
.chip .chip-x {
  border:0; background:transparent; padding:0; margin-left:.25rem; line-height:0;
  color:inherit; cursor:pointer;
}

.alumno-list, .tecnica-list { max-height: 280px; overflow:auto; }

.table td, .table th { vertical-align: middle; }

/* Responsive fonts en tabla */
@media (max-width: 576px) {
  .table td, .table th { font-size: .92rem; }
}

/* Cards / modal */
.card { border-radius:16px; }
.modal-content { border-radius:18px; }

/* Animación apertura modal */
@keyframes popIn {
  0% { transform: scale(.94); opacity: 0; }
  100% { transform: scale(1); opacity: 1; }
}
.modal-content.animate-pop {
  animation: popIn .22s ease-out;
}

/* Ajuste SweetAlert para navbar fijo */
:deep(.swal2-container.swal2-pt) { padding-top: 5.5rem !important; }
:deep(.swal2-toast-pt) { padding-top: 4rem !important; }
</style>
