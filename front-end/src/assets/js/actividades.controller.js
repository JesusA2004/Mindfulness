// src/assets/js/actividades.controller.js
import Swal from "sweetalert2";
import "sweetalert2/dist/sweetalert2.min.css";
import axios from "axios";

import {
  getCurrentUser,
  fetchActividades,
  createActividad,
  fetchTecnicas,
  fetchAlumnos,
  fetchDocentes,
  paramsFromPaginationUrl,
} from "@/composables/actividades";

const API = (process.env.VUE_APP_API_URL || "").replace(/\/+$/, "");

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

    /** ==== Login → hidratar persona/cohorte si faltan ===== */
    async ensurePersonaOnUser() {
      try {
        // 1) Si ya viene persona con cohorte, nada que hacer
        if (this.usuario?.persona?.cohorte) return;

        // 2) Revisar lo que está en localStorage
        const raw = localStorage.getItem("user");
        const stored = raw ? JSON.parse(raw) : null;

        // Copiar persona si ya viene completa en local
        if (stored?.persona && stored.persona.cohorte) {
          this.usuario.persona = stored.persona;
          return;
        }

        // 3) Buscar un id de persona para consultar
        const pid =
          this.usuario?.persona?._id ||
          stored?.persona?._id ||
          stored?.persona_id ||
          stored?.personaId ||
          this.usuario?.persona_id ||
          this.usuario?.personaId;

        if (!pid) return;

        // 4) Consultar /personas/:id (usa API de entorno si existe, si no, relativo)
        const url = (API ? API : "") + `/personas/${pid}`;
        const { data } = await axios.get(url);
        const persona = data?.persona || data;
        if (persona) {
          this.usuario.persona = persona;
        }
      } catch (e) {
        // Silencioso: si falla igual seguimos; sólo afecta filtros por cohorte del profesor
        console.warn("[ensurePersonaOnUser] No se pudo hidratar persona:", e?.message || e);
      }
    },

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
      // Importante: si falta persona/cohorte, hidratar desde LS o endpoint
      await this.ensurePersonaOnUser();

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

      // Cohortes globales (normalizadas) para Admin/Alumno
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
        return;
      }

      // ====== MODO FILTRO POR COHORTE EN CLIENTE ======
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

      const todos = Array.isArray(data?.registros) ? data.registros : [];
      const filtrados = todos.filter((a) => {
        const p = a?.participantes;
        if (Array.isArray(p)) return p.some((row) => userIdsCohorte.has(String(row?.user_id)));
        if (typeof p === "string") {
          for (const uid of userIdsCohorte) {
            if (p.includes(`"user_id":"${uid}"`)) return true;
          }
        }
        return false;
      });

      this._clientPaginate = true;
      this._clientAll = filtrados;
      this._clientPerPage = 6;
      this._applyClientPage(1);
    },

    /** Resuelve datos de la técnica desde el recurso o del catálogo ya cargado */
    tecnicaData(a) {
      const id = String(a?.tecnica_id || "");
      const byList = (this.tecnicas || []).find(t => String(t._id || t.id) === id);
      return {
        nombre: a?.tecnicaNombre || byList?.nombre || "No existe",
        duracion: byList?.duracion ?? a?.duracion ?? null,
        dificultad: byList?.dificultad ?? a?.dificultad ?? null,
        categoria: byList?.categoria ?? a?.categoria ?? null,
      };
    },

    /** Obtiene un único texto de cohorte para la actividad (toma el del primer participante) */
    cohorteActividad(a, mapUsuarios) {
      const first = (a?.participantes || []).map(p => mapUsuarios.get(String(p.user_id))).find(Boolean);
      if (!first) return "—";
      const coh = first?.persona?.cohorte;
      if (Array.isArray(coh)) return coh.join(", ");
      return coh || "—";
    },

    /** Devuelve clase bootstrap para el estado */
    estadoPill(estado) {
      const s = String(estado || "").toLowerCase();
      if (s === "completado") return "success";
      if (s === "omitido")    return "secondary";
      return "warning"; // pendiente u otros
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

      this.enlaces = {
        anterior: p > 1 ? "prev" : null,
        siguiente: p < last ? "next" : null,
      };
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
      return String(s)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;");
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

                <!-- Preview de alumnos que se asignarán por cohorte -->
                <div id="f_grupoPreview" class="list-group mt-2 sw-list" style="max-height:240px;overflow:auto;"></div>
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

      // ====== Técnica search ======
      const inpTec = $("#f_tecnicaQ");
      const btnClearTec = $("#btnClearTec");
      const lstTec = $("#f_tecnicaList");
      const hidTec = $("#f_tecnicaId");
      const selBox = $("#f_tecnicaSel");
      const selName = $("#f_tecnicaSelName");
      const btnCambiar = $("#f_btnCambiarTec") || $("#btnCambiarTec");

      const renderTec = (items) => {
        lstTec.innerHTML = (items || []).map((t) => {
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
        const base = Array.isArray(this.tecnicas) ? this.tecnicas : [];
        if (!q) { renderTec(base); return; }          // ← mostrar TODAS si no escribe
        renderTec(base.filter((t) => (t?.nombre || "").toLowerCase().includes(q)).slice(0, 200));
      };

      if (inpTec) {
        inpTec.addEventListener("input", filterTec);
        filterTec(); // render inicial con todas
      }
      if (btnClearTec) btnClearTec.addEventListener("click", () => {
        if (!inpTec) return;
        inpTec.value = "";
        hidTec.value = "";
        selBox.style.display = "none";
        selName.textContent = "";
        lstTec.innerHTML = "";
        inpTec.focus();
        filterTec();
      });
      if (btnCambiar) btnCambiar.addEventListener("click", () => {
        hidTec.value = "";
        selBox.style.display = "none";
        inpTec && inpTec.focus();
      });

      // ===== Radios de asignación / Grupo preview =====
      const asigAlumno = $("#f_asigAlumno");
      const asigGrupo = $("#f_asigGrupo");
      const secAlumno = $("#secAlumno");
      const secGrupo  = $("#secGrupo");
      const grupoSel  = $("#f_grupo");
      const grupoHelp = $("#f_grupoHelp");

      function alumnosDelCohorte(grupo) {
        const g = String(grupo || "").trim();
        if (!g) return [];
        const base = Array.isArray(this.alumnosCache) ? this.alumnosCache : [];
        return base.filter((u) => {
          const c = u?.persona?.cohorte;
          return Array.isArray(c) ? c.includes(g) : String(c || "") === g;
        });
      }

      function renderGrupoPreview(grupo) {
        const cont = containerEl.querySelector("#f_grupoPreview");
        if (!cont) return;
        const list = alumnosDelCohorte.call(this, grupo);
        cont.innerHTML = list.length
          ? list.map((u) => `
              <div class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                  <div class="fw-semibold">${this.escape(u.name || "")}</div>
                  <div class="text-muted small">${this.escape(u.email || "")}</div>
                </div>
                <span class="badge bg-info-subtle text-info border">Alumno</span>
              </div>
            `).join("")
          : `<div class="text-muted">No hay alumnos en este cohorte.</div>`;
      }

      const toggleAsignacion = () => {
        const alumno = !!(asigAlumno && asigAlumno.checked);
        if (secAlumno) secAlumno.style.display = alumno ? "" : "none";
        if (secGrupo)  secGrupo.style.display  = alumno ? "none" : "";
        if (alumno) {
          doBuscarAlu();        // mostrar TODOS los alumnos al cambiar a “un alumno”
        } else {
          const v = (grupoSel?.value || "").trim();
          renderGrupoPreview.call(this, v);
        }
      };

      if (asigAlumno) asigAlumno.addEventListener("change", toggleAsignacion);
      if (asigGrupo)  asigGrupo.addEventListener("change", toggleAsignacion);
      toggleAsignacion();

      if (grupoSel) {
        grupoSel.addEventListener("change", () => {
          const v = (grupoSel.value || "").trim();
          if (grupoHelp) {
            grupoHelp.style.display = v ? "" : "none";
            grupoHelp.innerHTML = v
              ? `Se asignará a todos los alumnos del grupo <strong>${this.escape(v)}</strong>.`
              : "";
          }
          renderGrupoPreview.call(this, v);
        });
      }

      // ===== Buscar alumno (lista inicial = todos) =====
      const aluQ = $("#f_alumnoQ");
      const aluList = $("#f_alumnoList");
      const aluId = $("#f_alumnoId");
      const aluSel = $("#f_alumnoSel");

      const renderAlumnos = (items) => {
        aluList.innerHTML = (items || []).map((u) => {
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
        const base = Array.isArray(this.alumnosCache) ? this.alumnosCache : [];

        if (q.length < 2) {         // sin búsqueda → TODOS
          renderAlumnos(base);
          return;
        }
        const matches = base.filter((u) =>
          (u.name || "").toLowerCase().includes(q) ||
          (u.email || "").toLowerCase().includes(q) ||
          (u.matricula || "").toLowerCase().includes(q)
        );
        renderAlumnos(matches);
      };

      if (aluQ) {
        aluQ.addEventListener("input", () => {
          clearTimeout(this._buscarAlumnoDebounce);
          this._buscarAlumnoDebounce = setTimeout(doBuscarAlu, 180);
        });
        doBuscarAlu(); // render inicial con TODOS
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

      const map = new Map(this.alumnosCache.map(u => [String(u._key), u]));
      const tec = this.tecnicaData(a);
      const cohorte = this.cohorteActividad(a, map);

      const total = Array.isArray(a?.participantes) ? a.participantes.length : 0;
      const items = (a?.participantes || []).map((p) => {
        const u = map.get(String(p.user_id));
        const nombre = u?.name || `ID ${p.user_id}`;
        const estado = p?.estado || "Pendiente";
        const badge = this.estadoPill(estado);
        return `
          <div class="list-group-item d-flex justify-content-between align-items-center">
            <div class="me-3">
              <div class="fw-semibold">${this.escape(nombre)}</div>
              <div class="text-muted small">${this.escape(u?.email || "")}</div>
            </div>
            <span class="badge bg-${badge}">${this.escape(estado)}</span>
          </div>
        `;
      }).join("");

      const filaChips = `
        <div class="d-flex flex-wrap gap-2 mb-2">
          <span class="badge text-bg-light border"><strong>Técnica:</strong> ${this.escape(tec.nombre)}</span>
          <span class="badge text-bg-light border"><strong>Duración:</strong> ${this.escape(tec.duracion ?? "—")}</span>
          <span class="badge text-bg-light border"><strong>Dificultad:</strong> ${this.escape(tec.dificultad ?? "—")}</span>
          <span class="badge text-bg-light border"><strong>Categoría:</strong> ${this.escape(tec.categoria ?? "—")}</span>
          <span class="badge text-bg-light border"><strong>Asignación:</strong> ${this.escape(this.fmt(a?.fechaAsignacion))}</span>
          <span class="badge text-bg-light border"><strong>Fecha máx.:</strong> ${this.escape(this.fmt(a?.fechaMaxima))}</span>
          <span class="badge text-bg-primary border">Total: ${total}</span>
        </div>
      `;

      const bloqueDesc = a?.descripcion
        ? `<div class="mb-3"><div class="fw-semibold mb-1">Descripción</div><div class="text-muted">${this.escape(a.descripcion)}</div></div>`
        : "";

      const bloqueCoh = `
        <div class="mb-2 text-muted">
          <i class="bi bi-people me-1"></i><strong>Participantes</strong>
          <span class="ms-2">Cohorte: ${this.escape(cohorte)}</span>
        </div>
      `;

      const html = `
        <div class="text-start">
          ${filaChips}
          ${bloqueDesc}
          ${bloqueCoh}
          <div class="list-group">
            ${items || '<div class="text-muted">No hay participantes registrados.</div>'}
          </div>
        </div>
      `;

      await Swal.fire({
        title: `Detalles • ${this.escape(a?.nombre || "Actividad")}`,
        html,
        width: 820,
        confirmButtonText: "Cerrar",
        customClass: { container: "swal2-pt" },
      });
    },
  },
};
