<!-- src/views/administrador/ActividadesAula.vue -->
<template>
  <main class="container-fluid actividades-wrapper py-3 py-lg-4">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">
      <div>
        <h1 class="h3 mb-1 fw-bold">Seguimiento de actividades en el aula</h1>
        <p class="text-muted mb-0">
          Registra y consulta actividades mindfulness realizadas por cohorte o alumno.
        </p>
      </div>

      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-primary" @click="openCreate">
          <i class="bi bi-plus-circle me-2"></i> Nueva actividad
        </button>
      </div>
    </div>

    <!-- Filtros -->
    <section class="card border-0 shadow-sm mb-3">
      <div class="card-body">
        <div class="row g-2 align-items-end">
          <div class="col-12 col-md-4">
            <label class="form-label mb-1">Cohorte</label>
            <select v-model="filtros.cohorte" class="form-select">
              <option value="">Todas</option>
              <option v-for="c in cohortesVisibles" :key="c" :value="c">{{ c }}</option>
            </select>
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label mb-1">Desde</label>
            <input type="date" class="form-control" v-model="filtros.desde" />
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label mb-1">Hasta</label>
            <input type="date" class="form-control" v-model="filtros.hasta" />
          </div>
          <div class="col-12 col-md-2 d-grid">
            <button class="btn btn-outline-secondary" @click="cargarActividades()">
              <i class="bi bi-funnel me-1"></i> Filtrar
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- Tabla de actividades -->
    <section class="card border-0 shadow-sm">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th class="text-nowrap">Nombre</th>
                <th class="text-nowrap d-none d-sm-table-cell">Técnica</th>
                <th class="text-nowrap">Asignación</th>
                <th class="text-nowrap d-none d-md-table-cell">Fecha máx.</th>
                <th class="text-nowrap d-none d-lg-table-cell">Finalización</th>
                <th class="text-nowrap">Participantes</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="registros.length === 0">
                <td colspan="6" class="text-center py-4 text-muted">
                  No hay actividades con estos filtros.
                </td>
              </tr>
              <tr v-for="a in registros" :key="a._id || a.id">
                <td class="fw-600">
                  {{ a.nombre }}
                  <div class="small text-muted d-sm-none mt-1">
                    <i class="bi bi-mortarboard"></i>
                    {{ a?.tecnica?.nombre || '—' }}
                  </div>
                </td>
                <td class="d-none d-sm-table-cell">{{ a?.tecnica?.nombre || '—' }}</td>
                <td class="text-nowrap">
                  <span class="badge bg-secondary-subtle text-secondary border">
                    {{ fmt(a.fechaAsignacion) }}
                  </span>
                </td>
                <td class="text-nowrap d-none d-md-table-cell">{{ fmt(a.fechaMaxima) }}</td>
                <td class="text-nowrap d-none d-lg-table-cell">{{ fmt(a.fechaFinalizacion) }}</td>
                <td class="text-nowrap">
                  <span class="badge bg-primary-subtle text-primary border">
                    {{ a.participantes?.length || 0 }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación simple -->
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top flex-wrap gap-2">
          <div class="small text-muted">Total en página: {{ totalVisible }}</div>
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
    </section>

    <!-- Modal de creación -->
    <div class="modal fade" id="modalCreate" tabindex="-1" ref="modalEl" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title fw-bold">Registrar actividad</h5>
            <button type="button" class="btn-close" @click="closeCreate" aria-label="Cerrar"></button>
          </div>

          <div class="modal-body">
            <!-- Fechas -->
            <div class="row g-3">
              <div class="col-12 col-md-4">
                <label class="form-label">Fecha de asignación</label>
                <input type="date" class="form-control" v-model="form.fechaAsignacion" readonly />
                <div class="form-text">Se asigna automáticamente al día de creación.</div>
              </div>
              <div class="col-6 col-md-4">
                <label class="form-label">Fecha máxima</label>
                <input type="date" class="form-control" v-model="form.fechaMaxima" @change="validarFechas" />
              </div>
              <div class="col-6 col-md-4">
                <label class="form-label">Fecha de finalización</label>
                <input type="date" class="form-control" v-model="form.fechaFinalizacion" @change="validarFechas" />
              </div>
            </div>

            <!-- Datos principales -->
            <div class="row g-3 mt-1">
              <div class="col-12">
                <label class="form-label">Nombre</label>
                <input type="text" class="form-control" v-model.trim="form.nombre" maxlength="150" placeholder="Ej. Respiración consciente 4-7-8" />
              </div>
              <div class="col-12">
                <label class="form-label">Técnica</label>
                <select class="form-select" v-model="form.tecnica_id">
                  <option value="" disabled>Selecciona una técnica…</option>
                  <option v-for="t in tecnicas" :key="t._id || t.id" :value="t._id || t.id">
                    {{ t.nombre }}
                  </option>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label">Descripción</label>
                <textarea class="form-control" v-model.trim="form.descripcion" rows="3" placeholder="Instrucciones, objetivo, duración sugerida…"></textarea>
              </div>
            </div>

            <!-- Asignación -->
            <div class="mt-3">
              <label class="form-label">Asignar a</label>
              <div class="d-flex flex-wrap gap-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" id="asigAlumno" value="alumno" v-model="form.asignarA" />
                  <label class="form-check-label" for="asigAlumno">Un alumno</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" id="asigGrupo" value="grupo" v-model="form.asignarA" />
                  <label class="form-check-label" for="asigGrupo">Todo un grupo</label>
                </div>
              </div>
            </div>

            <!-- Asignación a alumno -->
            <div v-if="form.asignarA === 'alumno'" class="mt-2">
              <label class="form-label">Buscar alumno</label>
              <div class="input-group">
                <input
                  type="search"
                  class="form-control"
                  v-model.trim="alumnoQuery"
                  placeholder="Nombre, matrícula o correo…"
                  @input="buscarAlumnos"
                />
                <button class="btn btn-outline-secondary" type="button" @click="buscarAlumnos">
                  <i class="bi bi-search"></i>
                </button>
              </div>
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
                  <span class="badge"
                        :class="puedeTomarlo(u) ? 'bg-success' : 'bg-danger'">
                        {{ puedeTomarlo(u) ? 'A su cargo' : 'Fuera de sus grupos' }}
                  </span>
                </button>
              </div>

              <div v-if="alumnoSeleccionado" class="alert mt-2"
                   :class="puedeTomarlo(alumnoSeleccionado) ? 'alert-success' : 'alert-danger'">
                <i class="bi" :class="puedeTomarlo(alumnoSeleccionado) ? 'bi-check-circle' : 'bi-exclamation-triangle'"></i>
                <strong> Seleccionado:</strong> {{ alumnoSeleccionado.name }}
                <span class="ms-2 text-muted">({{ cohorteStr(alumnoSeleccionado) || '—' }})</span>
              </div>
            </div>

            <!-- Asignación a grupo -->
            <div v-if="form.asignarA === 'grupo'" class="mt-2">
              <label class="form-label">Cohorte / Grupo</label>
              <select class="form-select" v-model="grupoSeleccionado">
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

          <div class="modal-footer">
            <button class="btn btn-outline-secondary" type="button" @click="closeCreate">Cancelar</button>
            <button class="btn btn-primary" type="button" :disabled="submitting" @click="confirmarCrear">
              <span v-if="!submitting"><i class="bi bi-check2-circle me-2"></i>Guardar</span>
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
  fetchCohortesAlumnos,
  paramsFromPaginationUrl,
} from "@/composables/actividades";

export default {
  name: "ActividadesAula",
  data() {
    return {
      usuario: null,
      tecnicas: [],
      registros: [],
      enlaces: { anterior: null, siguiente: null },
      totalVisible: 0,

      filtros: { cohorte: "", desde: "", hasta: "" },

      form: {
        fechaAsignacion: this.hoy(),
        fechaFinalizacion: "",
        fechaMaxima: "",
        nombre: "",
        tecnica_id: "",
        descripcion: "",
        asignarA: "grupo",
      },

      alumnoQuery: "",
      alumnosFiltrados: [],
      alumnoSeleccionado: null,
      grupoSeleccionado: "",

      cohortesGlobales: [],
      submitting: false,
      errors: [],
    };
  },
  computed: {
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
  },
  async mounted() {
    await this.bootstrap();
    this.bootstrapModal();
  },
  methods: {
    hoy() {
      const d = new Date();
      const yyyy = d.getFullYear();
      const mm = String(d.getMonth() + 1).padStart(2, "0");
      const dd = String(d.getDate()).padStart(2, "0");
      return `${yyyy}-${mm}-${dd}`;
    },
    fmt(d) { return d || "—"; },

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
        if (Array.isArray(coh)) return coh.some(v => this.inMisCohortes(v));
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
      this.tecnicas = await fetchTecnicas();
      if (this.usuario?.rol !== "profesor") {
        this.cohortesGlobales = await fetchCohortesAlumnos();
      }
      await this.cargarActividades();
    },

    bootstrapModal() {
      const el = this.$refs.modalEl;
      if (!el) return;
      if (!window.bootstrap) el.classList.remove("fade");
    },
    openCreate() {
      this.resetForm();
      const el = this.$refs.modalEl;
      if (window.bootstrap?.Modal) {
        const m = new window.bootstrap.Modal(el);
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
        fechaAsignacion: this.hoy(),
        fechaFinalizacion: "",
        fechaMaxima: "",
        nombre: "",
        tecnica_id: "",
        descripcion: "",
        asignarA: "grupo",
      };
      this.alumnoQuery = "";
      this.alumnosFiltrados = [];
      this.alumnoSeleccionado = null;
      this.grupoSeleccionado = "";
      this.errors = [];
    },

    validarFechas() {
      this.errors = [];
      const fa = this.form.fechaAsignacion;
      const fm = this.form.fechaMaxima;
      const ff = this.form.fechaFinalizacion;
      if (fa && fm && fm < fa) this.errors.push("La fecha máxima no puede ser anterior a la asignación.");
      if (fa && ff && ff < fa) this.errors.push("La fecha de finalización no puede ser anterior a la asignación.");
      if (fm && ff && fm > ff) this.errors.push("La fecha máxima no puede superar la fecha de finalización.");
    },

    async cargarActividades(extraParams = {}) {
      const params = { ...extraParams };
      if (this.filtros.cohorte) params.cohorte = this.filtros.cohorte;
      if (this.filtros.desde) params.desde = this.filtros.desde;
      if (this.filtros.hasta) params.hasta = this.filtros.hasta;
      if (this.usuario?.rol === "profesor") {
        params.docente_id = this.usuario?._id || this.usuario?.id;
      }

      const data = await fetchActividades(params);
      this.registros = data?.registros || [];
      this.enlaces = data?.enlaces || { anterior: null, siguiente: null };
      this.totalVisible = this.registros.length;
    },
    go(url) {
      if (!url) return;
      const p = paramsFromPaginationUrl(url); // { page: n }
      this.cargarActividades(p);
    },

    async buscarAlumnos() {
      const query = this.alumnoQuery?.trim();
      if (!query) { this.alumnosFiltrados = []; return; }
      const alumnos = await fetchAlumnos({ q: query });
      this.alumnosFiltrados = alumnos.filter(u => this.puedeTomarlo(u)).slice(0, 20);
    },
    seleccionarAlumno(u) { this.alumnoSeleccionado = u; },

    async confirmarCrear() {
      this.validarFechas();

      const faltantes = [];
      if (!this.form.nombre) faltantes.push("nombre");
      if (!this.form.tecnica_id) faltantes.push("técnica");
      if (!this.form.descripcion) faltantes.push("descripción");
      if (!this.form.fechaMaxima) faltantes.push("fecha máxima");
      if (!this.form.fechaFinalizacion) faltantes.push("fecha de finalización");
      if (this.form.asignarA === "alumno" && !this.alumnoSeleccionado) faltantes.push("alumno");
      if (this.form.asignarA === "grupo" && !this.grupoSeleccionado) faltantes.push("grupo");
      if (faltantes.length) return this.toast("Completa: " + faltantes.join(", "), "warning");
      if (this.errors.length) return this.toast("Corrige las validaciones de fecha.", "warning");

      let participantes = [];
      let resumenAsignacion = "";

      if (this.form.asignarA === "alumno") {
        if (!this.puedeTomarlo(this.alumnoSeleccionado))
          return this.toast("El alumno seleccionado no pertenece a tus cohortes.", "error");
        const uid = this.alumnoSeleccionado?._id || this.alumnoSeleccionado?.id;
        participantes = [{ user_id: String(uid), estado: "Pendiente" }];
        resumenAsignacion = `1 alumno (${this.alumnoSeleccionado?.name})`;
      } else {
        const grupo = this.grupoSeleccionado;
        if (this.usuario?.rol === "profesor" && !this.inMisCohortes(grupo))
          return this.toast("No puedes asignar a un grupo que no es de tu cargo.", "error");
        const alumnosGrupo = await fetchAlumnos({ cohorte: grupo });
        participantes = alumnosGrupo.map(u => ({ user_id: String(u._id || u.id), estado: "Pendiente" }));
        resumenAsignacion = `${participantes.length} alumnos del grupo ${grupo}`;
      }

      const payload = {
        fechaAsignacion: this.form.fechaAsignacion,
        fechaFinalizacion: this.form.fechaFinalizacion,
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
          <p class="mb-1"><strong>Asignación:</strong> ${this.escape(payload.fechaAsignacion)}</p>
          <p class="mb-1"><strong>Máxima:</strong> ${this.escape(payload.fechaMaxima)}</p>
          <p class="mb-1"><strong>Finalización:</strong> ${this.escape(payload.fechaFinalizacion)}</p>
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
      Swal.fire({ toast: true, position: "top-end", timer: 2500, showConfirmButton: false, icon, title: text });
    },
    escape(s) {
      if (s == null) return "";
      return String(s).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;");
    },
  },
};
</script>

<style scoped>
.actividades-wrapper { max-width: 1200px; }
.alumno-list { max-height: 280px; overflow: auto; }
.fw-600 { font-weight: 600; }
.table td, .table th { vertical-align: middle; }
@media (max-width: 576px) {
  .table td, .table th { font-size: 0.92rem; }
}
</style>
