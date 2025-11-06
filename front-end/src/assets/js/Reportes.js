// src/assets/js/Reportes.js
import Swal from "sweetalert2";
import "animate.css";

/* =================== BASE Y AUTH =================== */
function apiBase() {
  const RAW =
    process.env.VUE_APP_API_URL ||
    (process.env.VUE_APP_API_BASE
      ? String(process.env.VUE_APP_API_BASE).replace(/\/+$/, "") + "/api"
      : "/api");
  return String(RAW).replace(/\/+$/, "");
}
function readToken() {
  const keys = ["token", "auth_token", "jwt", "access_token"];
  for (const k of keys) {
    const v = localStorage.getItem(k);
    if (v) return v.replace(/^"|"$/g, "");
  }
  return null;
}
function authHeaders(extra = {}) {
  const t = readToken();
  return { Accept: "application/json", ...(t ? { Authorization: `Bearer ${t}` } : {}), ...extra };
}

/* =================== ENDPOINTS =================== */
const endpoints = {
  top: "reportes/top-tecnicas",
  act: "reportes/actividades-por-alumno",
  citas: "reportes/citas-por-alumno",
  bit: "reportes/bitacoras-por-alumno",
  enc: "reportes/encuestas-resultados",
  rec: "reportes/recompensas-canjeadas",
};
const exportMap = {
  top: "top-tecnicas",
  act: "actividades-por-alumno",
  citas: "citas-por-alumno",
  bit: "bitacoras-por-alumno",
  enc: "encuestas-resultados",
  rec: "recompensas-canjeadas",
};

/* =================== HELPERS =================== */
const ymd = (d) => {
  const pad = (n) => String(n).padStart(2, "0");
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
};
function presetToRange(preset) {
  const today = new Date();
  const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
  const endOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);
  const prevStart = new Date(today.getFullYear(), today.getMonth() - 1, 1);
  const prevEnd = new Date(today.getFullYear(), today.getMonth(), 0);
  const yesterday = new Date(today); yesterday.setDate(today.getDate() - 1);
  const weekStart = new Date(today); weekStart.setDate(today.getDate() - 6);

  switch (preset) {
    case "all":       return { desde: "", hasta: "" };
    case "today":     return { desde: ymd(today),     hasta: ymd(today) };
    case "yesterday": return { desde: ymd(yesterday), hasta: ymd(yesterday) };
    case "last7":     return { desde: ymd(weekStart), hasta: ymd(today) };
    case "month":     return { desde: ymd(startOfMonth), hasta: ymd(endOfMonth) };
    case "prevmonth": return { desde: ymd(prevStart),   hasta: ymd(prevEnd) };
    default:          return { desde: "", hasta: "" };
  }
}
function buildQuery(active, filtros = {}) {
  const p = new URLSearchParams();
  if (filtros.desde) p.set("desde", filtros.desde);
  if (filtros.hasta) p.set("hasta", filtros.hasta);
  if (active === "top" && filtros.grupo) p.set("grupo", filtros.grupo);
  if (["act","citas","bit"].includes(active) && filtros.alumno) p.set("alumno", filtros.alumno);
  if (active === "enc" && filtros.encuesta) p.set("encuesta", filtros.encuesta);
  if (active === "rec" && filtros.tipo) p.set("tipo", filtros.tipo);
  return p.toString();
}

/* =================== FETCH =================== */
async function getReport(active, filtros = {}) {
  const base = apiBase();
  const ep = endpoints[active];
  if (!ep) throw new Error("Reporte no reconocido");
  const qs = buildQuery(active, filtros);
  const url = `${base}/${ep}${qs ? `?${qs}` : ""}`;

  const res = await fetch(url, { headers: authHeaders({ "Cache-Control": "no-cache" }) });
  if (res.status === 401) { const err = new Error("401 Unauthorized"); err.code = 401; throw err; }
  if (!res.ok)            { const err = new Error(`HTTP ${res.status}`); err.code = res.status; throw err; }
  return await res.json();
}
async function exportReport(active, tipo = "pdf", filtros = {}) {
  const base = apiBase();
  const reporte = exportMap[active];
  if (!reporte) throw new Error("Reporte no reconocido");

  const qs = buildQuery(active, filtros);
  const bust = `_=${Date.now()}`;
  const url = `${base}/reportes/export?tipo=${tipo}&reporte=${reporte}${qs ? `&${qs}` : ""}&${bust}`;

  const res = await fetch(url, { headers: authHeaders({ "Cache-Control": "no-cache" }) });
  if (res.status === 401) { const err = new Error("401 Unauthorized"); err.code = 401; throw err; }
  if (!res.ok)            { const err = new Error(`HTTP ${res.status}`); err.code = res.status; throw err; }

  const cd = res.headers.get("Content-Disposition") || "";
  let filename = "";
  const m = /filename\*=UTF-8''([^;]+)|filename="?([^"]+)"?/i.exec(cd);
  if (m) filename = decodeURIComponent(m[1] || m[2] || "");
  if (!filename) {
    const ext = tipo === "excel" ? "xlsx" : tipo === "pdf" ? "pdf" : "html";
    filename = `${reporte}_${new Date().toISOString().slice(0,19).replace(/[:T]/g,"-")}.${ext}`;
  }
  const blob = await res.blob();
  return { blob, filename };
}

/* =================== ALUMNOS: SIN ENDPOINT (texto libre) =================== */
let alumnosCache = [];   // mantenemos interfaz, pero no cargamos nada del server
function normalizeAlumno(u) {
  const label = (u.label ||
    `${(u.nombre||"").trim()} ${(u.apellidoPaterno||"").trim()} ${(u.apellidoMaterno||"").trim()} — ${(u.matricula||"S/MAT").toUpperCase()}`)
    .replace(/\s+/g, " ")
    .trim();
  return {
    label,
    nombre: label.split(" — ")[0],
    matricula: (u.matricula || (label.split(" — ")[1] || "S/MAT")).toUpperCase(),
  };
}

/* =================== COHORTES =================== */
let cohortesCache = [];

async function loadCohortes(force = false) {
  if (cohortesCache.length && !force) return cohortesCache;
  const base = apiBase();
  const res = await fetch(`${base}/reportes/opciones/cohortes`, {
    headers: authHeaders({ "Cache-Control": "no-cache" }),
  });
  if (!res.ok) throw new Error("No se pudo cargar la lista de cohortes");
  const json = await res.json();
  cohortesCache = Array.isArray(json.items) ? json.items : [];
  return cohortesCache;
}

function filterCohortesLocal(term = "") {
  const t = (term || "").toLowerCase();
  return cohortesCache
    .filter((c) => c.toLowerCase().includes(t))
    .slice(0, 30);
}

async function loadAllAlumnos() {
  // Sin autocompletar remoto: dejamos la lista vacía.
  alumnosCache = [];
  return alumnosCache;
}

// Reemplaza TODO este método
async function previewTopChart(filtros = {}) {
  const base = apiBase();
  const qs = buildQuery("top", filtros);
  const url = `${base}/reportes/top-tecnicas${qs ? `?${qs}` : ""}`;

  const res = await fetch(url, { headers: authHeaders() });
  if (!res.ok) throw new Error("No se pudo obtener datos del reporte");
  const json = await res.json();

  const rows  = Array.isArray(json.rows) ? json.rows : [];
  const total = Number(json.total || 0);

  const pct = (v) => total ? Math.round((Number(v||0)/total)*1000)/10 : 0;

  // columnas
  const cols = rows.map(r => {
    const label = String(r.tecnica || "");
    const val   = Number(r.total || 0);
    const p     = pct(val);
    return `
      <div class="pv-col">
        <div class="pv-val">${val} (${p.toFixed(1)}%)</div>
        <div class="pv-bar"><div class="pv-fill" style="height:${Math.max(0,Math.min(100,p))}%"></div></div>
        <div class="pv-lbl">${label}</div>
      </div>`;
  }).join("");

  const html = `
    <style>
      /* Estilos locales solo para la vista previa vertical */
      .pvv-wrap{ padding:.25rem 0; }
      .pvv-title{ font-weight:800; margin-bottom:.4rem; color:#111827; text-align:center; font-size:20px; }
      .pvv-card{ border:1px solid #e5e7eb; border-radius:18px; padding:16px; background:#fff; }
      .pvv-grid{ display:flex; align-items:flex-end; gap:14px; min-height:160px; padding:8px 6px 0; border-bottom:1px solid #e5e7eb; }
      .pv-col{ flex:1; display:flex; flex-direction:column; align-items:center; gap:8px; }
      .pv-bar{ width:26px; height:140px; background:#ede9fe; border:1px solid #e5e7eb; border-radius:6px 6px 0 0; overflow:hidden; display:flex; align-items:flex-end; }
      .pv-fill{ width:100%; background:#7c3aed; }
      .pv-val{ font-size:12px; color:#334155; font-weight:800; }
      .pv-lbl{ font-size:13px; font-weight:800; color:#0f172a; margin-top:6px; text-align:center; }
      .pv-total{ margin-top:10px; text-align:center; font-size:18px; font-weight:800; color:#334155; }
    </style>
    <div class="pvv-wrap">
      <div class="pvv-title">Previsualización</div>
      <div class="pvv-card">
        <div class="pvv-grid">
          ${cols || '<div class="text-muted">Sin datos en el rango seleccionado.</div>'}
        </div>
        <div class="pv-total">Total de usos: <b>${total}</b></div>
      </div>
    </div>`;

  return { html, hasData: rows.length > 0 };
}

function filterAlumnosLocal(term = "") {
  if (!term) return alumnosCache;
  const t = term.toLowerCase();
  return alumnosCache.filter(a =>
    a.nombre.toLowerCase().includes(t) || a.matricula.toLowerCase().includes(t)
  );
}

/* =================== ESTILOS SWEETALERT =================== */
function ensureSwalStyles() {
  if (document.getElementById("sw-report-styles")) return;
  const css = `
  .sw-report .swal2-popup{ border-radius:18px!important; box-shadow:0 22px 60px rgba(2,12,27,.2)!important; overflow:hidden; }
  .sw-report .swal2-title{ font-weight:800!important; letter-spacing:.2px; display:flex; align-items:center; gap:.5rem; justify-content:center; }
  .sw-report .sw-head{ display:flex; align-items:center; gap:.6rem; margin-bottom:.25rem; justify-content:center; color:#475569; }
  .sw-report .sw-head i{ font-size:1.35rem; transform: translateY(1px); }
  .sw-report .swal2-html-container{ width:100%; margin:0!important; }
  .sw-report .form-label{ font-weight:700; color:#1f2937; letter-spacing:.2px; }
  .sw-report .form-select, .sw-report .form-control{ border-radius:12px; padding:.65rem .85rem; border:1px solid #e5e7eb; transition:.2s ease; }
  .sw-report .form-select:focus, .sw-report .form-control:focus{ border-color:#a78bfa; box-shadow:0 0 0 .25rem rgba(167,139,250,.22); }
  .sw-report .swal2-actions{ gap:.5rem; }
  .sw-report .swal2-confirm.btn{ border-radius:12px; padding:.65rem 1.05rem; font-weight:800; background:linear-gradient(90deg,#7c3aed,#a78bfa); border:0; }
  .sw-report .swal2-cancel.btn{ border-radius:12px; padding:.65rem 1.05rem; font-weight:800; background:#e5e7eb; color:#0f172a; border:1px solid #cbd5e1; }
  .sw-report .sw-section{ background:#fafafa; border:1px dashed #e5e7eb; border-radius:14px; padding:.85rem; }
  .sw-report .mini-hint{ font-size:.86rem; color:#64748b; display:flex; align-items:center; gap:.4rem; }

  /* Lista de alumnos tipo tarjetas */
  .sw-list{ display:flex; flex-direction:column; gap:8px; max-height:248px; overflow:auto; padding-right:2px; }
  .sw-item{ text-align:left; border:1px solid #e5e7eb; background:#fff; border-radius:14px; padding:.6rem .8rem; cursor:pointer; transition:.15s ease; }
  .sw-item:hover{ border-color:#c7d2fe; box-shadow:0 0 0 .16rem rgba(167,139,250,.16); }
  .sw-item.active{ border-color:#7c3aed; box-shadow:0 0 0 .2rem rgba(124,58,237,.22); background:#faf5ff; }
  .sw-item .nm{ font-weight:800; display:block; line-height:1.1; color:#111827; }
  .sw-item .mt{ font-size:.83rem; color:#64748b; }
  .chip{ display:inline-flex; align-items:center; gap:.35rem; padding:.25rem .55rem; border:1px solid #e5e7eb; border-radius:999px; font-size:.78rem; background:#fff; }
  .chip b{ font-weight:800; }
  @media (min-width:768px){ .sw-report .swal2-popup{ width:780px!important; } }
  
    .pv-wrap{ padding:.25rem 0; }
  .pv-head{ font-weight:800; margin-bottom:.25rem; color:#111827; }
  .pv-chart{ border:1px solid #e5e7eb; border-radius:14px; padding:.8rem; background:#fff; }
  .pv-bar{ display:flex; align-items:center; gap:10px; margin:.5rem 0; }
  .pv-lbl{ width:34%; font-size:.9rem; font-weight:700; color:#0f172a; }
  .pv-track{ flex:1; height:12px; background:#eef2ff; border-radius:999px; overflow:hidden; border:1px solid #e5e7eb; }
  .pv-fill{ height:100%; background:#7c3aed; }
  .pv-val{ width:18%; text-align:right; font-size:.85rem; color:#334155; font-weight:700; }

  `;
  const style = document.createElement("style");
  style.id = "sw-report-styles";
  style.textContent = css;
  document.head.appendChild(style);
}
const debounce = (fn, ms) => { let t=null; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a),ms); }; };

/* =================== MODAL =================== */
async function openExportDialog(active, tipo = "pdf") {
  ensureSwalStyles();
  const iconByType = { pdf: "bi bi-filetype-pdf", excel: "bi bi-filetype-xlsx" };
  const isAlumnoType = ["act","citas","bit"].includes(active); // recompensas NO lleva alumno

  const filterOptions = [{ v:"none", t:"Todos" }];
  if (active === "top")  filterOptions.push({ v:"cohorte",  t:"Cohorte/Grupo" });
  if (isAlumnoType)      filterOptions.push({ v:"alumno",   t:"Alumno (nombre/matrícula)" });
  if (active === "enc")  filterOptions.push({ v:"encuesta", t:"Encuesta (título o ID)" });
  if (active === "rec")  filterOptions.push({ v:"tipo",     t:"Tipo de recompensa" });
  const filterOpts = filterOptions.map(o=>`<option value="${o.v}">${o.t}</option>`).join("");

  const html = `
  <div class="container-fluid p-0">
    <div class="sw-head">
      <i class="${iconByType[tipo]||'bi bi-download'} text-primary"></i>
      <div>Selecciona un rango y (opcional) un filtro</div>
    </div>

    <div class="row g-3">
      <div class="col-12 col-md-6">
        <div class="sw-section h-100">
          <label class="form-label mb-2">Rango de fechas</label>
          <select id="sw-datepreset" class="form-select mb-2">
            <option value="all" selected>Todas las fechas</option>
            <option value="today">Hoy</option>
            <option value="yesterday">Ayer</option>
            <option value="last7">Últimos 7 días</option>
            <option value="month">Este mes</option>
            <option value="prevmonth">Mes pasado</option>
            <option value="custom">Personalizado…</option>
          </select>
          <div id="sw-customrange" class="row g-2" style="display:none">
            <div class="col-6">
              <label class="form-label mb-1">Desde</label>
              <input id="sw-desde" type="date" class="form-control" />
            </div>
            <div class="col-6">
              <label class="form-label mb-1">Hasta</label>
              <input id="sw-hasta" type="date" class="form-control" />
            </div>
          </div>
          <div class="mini-hint mt-2"><span class="chip"><b>Enter</b> para confirmar</span></div>
        </div>
      </div>

      <div class="col-12 col-md-6">
        <div class="sw-section h-100">
          <label class="form-label mb-2">Filtrar por</label>
          <select id="sw-filter" class="form-select mb-2">${filterOpts}</select>

          <div id="sw-filter-text" style="display:none">
            <label class="form-label mb-1" id="sw-filter-text-label">Valor</label>
            <input id="sw-filter-text-input" type="text" class="form-control" placeholder="Escribe para filtrar…" />

            <div class="sw-list mt-2" id="sw-cohorte-list" style="display:none;"></div>
          </div>

          <div id="sw-filter-alumno" style="display:none">
            <label class="form-label mb-1">Alumno (nombre / matrícula)</label>
            <input id="sw-alumno-input" class="form-control mb-2" type="text" placeholder="Buscar… (vacío = todos)">
            <div class="sw-list" id="sw-alumno-list" role="listbox" aria-label="Alumnos"></div>
            <div class="mini-hint mt-2">Vacío = <b>todos</b> los estudiantes.</div>
          </div>
        </div>
      </div>
    </div>
  </div>`;

  await Swal.fire({
    title: `Exportar ${tipo.toUpperCase()}`,
    html,
    customClass: {
      container: "sw-report",
      popup: "sw-report",
      confirmButton: "btn btn-primary",
      cancelButton: "btn btn-light",
    },
    buttonsStyling: false,
    showCancelButton: true,
    confirmButtonText: `Descargar ${tipo.toUpperCase()}`,
    cancelButtonText: "Cancelar",
    focusConfirm: false,
    showClass: { popup: "animate__animated animate__fadeInUp faster" },
    hideClass: { popup: "animate__animated animate__fadeOutDown faster" },

    didOpen: async () => {
      // === COHORTES (lista sugerencias) ===
      const $cohorteList = document.getElementById("sw-cohorte-list");

      const renderCohortes = (list) => {
        if (!$cohorteList) return;
        if (!list || !list.length) {
          $cohorteList.innerHTML = `<div class="mini-hint">Sin coincidencias.</div>`;
          return;
        }
        $cohorteList.innerHTML = list.map((c) => `
          <button type="button" class="sw-item" data-value="${c}">
            <span class="nm">${c}</span>
          </button>
        `).join("");

        Array.from($cohorteList.querySelectorAll(".sw-item")).forEach((el) => {
          el.addEventListener("click", () => {
            const val = el.getAttribute("data-value") || "";
            // Colocamos la cohorte elegida en el input de texto
            $textInput.value = val;
            // Resaltado visual opcional
            $cohorteList.querySelectorAll(".sw-item").forEach((n) => n.classList.remove("active"));
            el.classList.add("active");
          });
        });
      };

      const showCohortesIfNeeded = async () => {
        if ($filter.value !== "cohorte") return;
        if (!cohortesCache.length) await loadCohortes();
        const term = ($textInput?.value || "").trim();
        const list = term ? filterCohortesLocal(term) : cohortesCache.slice(0, 30);
        if ($cohorteList) $cohorteList.style.display = "block";
        renderCohortes(list);
      };

      // === ELEMENTOS GENERALES ===
      const $preset      = document.getElementById("sw-datepreset");
      const $custom      = document.getElementById("sw-customrange");
      const $filter      = document.getElementById("sw-filter");
      const $textWrap    = document.getElementById("sw-filter-text");
      const $textLabel   = document.getElementById("sw-filter-text-label");
      const $textInput   = document.getElementById("sw-filter-text-input");
      const $alumnoWrap  = document.getElementById("sw-filter-alumno");
      const $alumnoInput = document.getElementById("sw-alumno-input");
      const $alumnoList  = document.getElementById("sw-alumno-list");

      // === ALUMNOS (tu lista local vacía) ===
      let currentSelection = ""; // etiqueta elegida (para export)

      const renderAlumnos = (list) => {
        if (!list || !list.length) {
          $alumnoList.innerHTML = `<div class="mini-hint">
            Escribe <b>nombre</b> o <b>matrícula</b> y presiona <b>Enter</b>. (Sin autocompletar)
          </div>`;
          return;
        }
        $alumnoList.innerHTML = list.map((o, i) => `
          <button type="button" class="sw-item ${o.label===currentSelection ? "active" : ""}"
                  data-label="${o.label}" data-idx="${i}">
            <span class="nm">${o.nombre}</span>
            <span class="mt">${o.matricula}</span>
          </button>
        `).join("");
        Array.from($alumnoList.querySelectorAll(".sw-item")).forEach((el) => {
          el.addEventListener("click", () => {
            currentSelection = el.getAttribute("data-label") || "";
            $alumnoList.querySelectorAll(".sw-item").forEach(n => n.classList.remove("active"));
            el.classList.add("active");
          });
        });
      };

      const toggleCustom = () => {
        $custom.style.display = $preset.value === "custom" ? "flex" : "none";
      };

      // OJO: usamos let para poder extender sin reasignar un const
      let toggleFilterInputs = () => {
        const v = $filter.value;
        $textWrap.style.display   = (v === "cohorte" || v === "encuesta" || v === "tipo") ? "block" : "none";
        $alumnoWrap.style.display = (v === "alumno") ? "block" : "none";
        if (v === "cohorte")  $textLabel.textContent = "Cohorte / Grupo (p.ej. ITI 10 A)";
        if (v === "encuesta") $textLabel.textContent = "Encuesta (título o ID)";
        if (v === "tipo")     $textLabel.textContent = "Tipo de recompensa";

        // Mostrar/ocultar lista de cohortes
        if (v === "cohorte") {
          if ($cohorteList) $cohorteList.style.display = "block";
          showCohortesIfNeeded();
        } else {
          if ($cohorteList) $cohorteList.style.display = "none";
        }
      };

      // Carga inicial alumnos (local vacío) y render
      await loadAllAlumnos();
      renderAlumnos(alumnosCache);

      const doFilterLocal = debounce((t) => renderAlumnos(filterAlumnosLocal((t||"").trim())), 140);

      // Enter rápido (opcional, se queda como en tu versión)
      document.addEventListener("keydown", (ev) => {
        if (ev.key === "Enter") {
          const input = document.activeElement;
          if (input && (input.id === "sw-alumno-input" || input.id === "sw-filter-text-input")) {
            // permitir confirmar rápido
          }
        }
      });

      // === Eventos ===
      $preset.addEventListener("change", toggleCustom);
      $filter.addEventListener("change", toggleFilterInputs);
      $alumnoInput.addEventListener("input", (e) => doFilterLocal(e.target.value || ""));
      $textInput.addEventListener("input", () => {
        if ($filter.value === "cohorte") showCohortesIfNeeded();
      });

      // === Inicial ===
      toggleCustom();
      toggleFilterInputs();
      await showCohortesIfNeeded();
    }
,
    preConfirm: () => {
      const $preset = document.getElementById("sw-datepreset");
      const $d = document.getElementById("sw-desde");
      const $h = document.getElementById("sw-hasta");
      const $filter = document.getElementById("sw-filter");
      const $textInput = document.getElementById("sw-filter-text-input");
      const $alumnoList = document.getElementById("sw-alumno-list");
      const $alumnoInput = document.getElementById("sw-alumno-input");

      let { desde, hasta } = presetToRange($preset.value);
      if ($preset.value === "custom") {
        desde = ($d.value || "").trim();
        hasta = ($h.value || "").trim();
        if (!desde || !hasta) { Swal.showValidationMessage("Selecciona fecha <b>Desde</b> y <b>Hasta</b>."); return false; }
      }

      const filtros = { ...(desde ? { desde } : {}), ...(hasta ? { hasta } : {}) };

      const f = $filter.value;
      if (f === "cohorte")  { const v = ($textInput?.value || "").trim(); if (v) filtros.grupo = v; }
      if (f === "encuesta") { const v = ($textInput?.value || "").trim(); if (v) filtros.encuesta = v; }
      if (f === "tipo")     { const v = ($textInput?.value || "").trim(); if (v) filtros.tipo = v; }
      if (f === "alumno") {
        const typed  = ($alumnoInput?.value || "").trim();
        const chosen = $alumnoList.querySelector(".sw-item.active")?.getAttribute("data-label") || "";
        const val = (chosen || typed).trim();
        if (val) filtros.alumno = val; // vacío = todos
      }
      return filtros;
    }
  }).then(async (res) => {
  if (!res.isConfirmed) return;

  // === Previsualización solo para TOP TÉCNICAS ===
  if (active === "top") {
    try {
      const { html, hasData } = await previewTopChart(res.value || {});
      const confirm = await Swal.fire({
        title: "Top técnicas",
        html,
        customClass: { container: "sw-report" },
        showCancelButton: true,
        confirmButtonText: "Generar",
        cancelButtonText: "Cancelar",
        width: 780,
      });
      if (!confirm.isConfirmed) return;
      if (!hasData) {
        await Swal.fire({ icon:"warning", title:"Sin datos para exportar", timer:1300, showConfirmButton:false, customClass:{container:"sw-report"} });
        return;
      }
    } catch (e) {
      await Swal.fire({ icon:"error", title:"No se pudo previsualizar", text:e.message || "Error", customClass:{container:"sw-report"} });
      return;
    }
  }

    Swal.fire({
      title: "Generando archivo…",
      html: "Preparando la descarga",
      didOpen: () => Swal.showLoading(),
      allowOutsideClick: false,
      allowEscapeKey: false,
      showConfirmButton: false,
      customClass: { container: "sw-report" },
    });

    try {
      const { blob, filename } = await exportReport(active, tipo, res.value || {});
      const a = document.createElement("a");
      a.href = URL.createObjectURL(blob);
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      a.remove();
      Swal.close();
      Swal.fire({ icon: "success", title: "Descarga lista", timer: 1200, showConfirmButton: false, customClass: { container: "sw-report" } });
    } catch (e) {
      Swal.close();
      Swal.fire({ icon: "error", title: "No se pudo exportar", text: e.message || "Error desconocido", customClass: { container: "sw-report" } });
    }
  });
}

/* =================== API (export default) =================== */
export default {
  apiBase,
  getReport,
  exportReport,
  openExportDialog,
  buildQuery,
  authHeaders,
};
