// src/assets/js/reportes/citasAlumno.js
import { Swal, ensureSwalStyles, presetToRange, debounce } from "./common/utils";
import { apiBase, fetchJSON, downloadBinary, authHeaders } from "./common/apiClient";
import { loadAllAlumnos, filterAlumnosLocal, alumnosCache } from "./common/alumnos";

const ENDPOINT = "reportes/citas-por-alumno";
const EXPORT_REPORTE = "citas-por-alumno";

/* -------------------- helpers -------------------- */
function buildQuery(f = {}) {
  const p = new URLSearchParams();
  if (f.desde)  p.set("desde", f.desde);
  if (f.hasta)  p.set("hasta", f.hasta);
  if (f.alumno) p.set("alumno", f.alumno); // SOLO matrícula
  return p.toString();
}
const asArray = v => Array.isArray(v) ? v : [];

/* -------------------- API -------------------- */
export async function getCitasAlumno(f = {}) {
  return fetchJSON(ENDPOINT, buildQuery(f));
}

export async function exportCitasAlumno(tipo = "pdf", f = {}) {
  const qs = buildQuery(f);
  const url = `${apiBase()}/reportes/export?tipo=${tipo}&reporte=${EXPORT_REPORTE}${qs ? `&${qs}` : ""}&_=${Date.now()}`;
  return downloadBinary(url);
}

/* -------------------- Preview (tabla) -------------------- */
async function previewTable(filtros = {}) {
  const url = `${apiBase()}/${ENDPOINT}${buildQuery(filtros) ? `?${buildQuery(filtros)}` : ""}`;
  const res = await fetch(url, { headers: authHeaders() });
  if (!res.ok) throw new Error("No se pudo obtener datos del reporte");
  const json = await res.json();

  const rows = asArray(json.rows).map(r => ({
    alumno:    String(r.alumno ?? ""),
    matricula: String(r.matricula ?? ""),
    fecha:     String((r.fecha ?? "").toString().slice(0, 10)),
    estado:    String(r.estado ?? ""),
    motivo:    String(r.motivo ?? "")
  }));

  const alumnoChip = filtros.alumno_label || filtros.alumno || "";

  const chips = [
    `<span class="chip"><b>Rango:</b> ${
      filtros.desde && filtros.hasta
        ? `${filtros.desde} a ${filtros.hasta}`
        : "Todas las fechas"
    }</span>`,
    filtros.alumno
      ? `<span class="chip"><b>Alumno:</b> ${String(alumnoChip)}</span>`
      : ""
  ].filter(Boolean).join("");

  const head = `
    <tr>
      <th>alumno</th>
      <th>matricula</th>
      <th>fecha</th>
      <th>estado</th>
      <th>motivo</th>
    </tr>`;

  const body = rows.map(r => `
    <tr>
      <td>${r.alumno}</td>
      <td>${r.matricula}</td>
      <td>${r.fecha}</td>
      <td>${r.estado}</td>
      <td>${r.motivo}</td>
    </tr>`).join("");

  const html = `
    <style>
      .chips{display:flex;gap:8px;flex-wrap:wrap;margin:0 0 10px}
      .chip{display:inline-flex;gap:6px;align-items:center;padding:6px 10px;border:1px solid #e5e7eb;border-radius:999px;background:#fff;font-size:12px}
      .pv-card{border:1px solid #e5e7eb;border-radius:14px;background:#fff;padding:12px}
      table{width:100%;border-collapse:collapse}
      thead th{background:#f3f4f6;text-align:left;padding:10px;border-bottom:1px solid #e5e7eb;font-size:12px}
      tbody td{padding:10px;border-bottom:1px solid #f1f5f9;font-size:12px;vertical-align:top}
      .foot{margin-top:8px;color:#64748b;font-size:12px;text-align:center}
    </style>
    <div class="chips">${chips}</div>
    <div class="pv-card">
      <table>
        <thead>${head}</thead>
        <tbody>${
          body || `<tr><td colspan="5" class="text-muted">Sin datos en el rango seleccionado.</td></tr>`
        }</tbody>
      </table>
      <div class="foot">Mostrando ${rows.length} ${rows.length===1 ? "fila" : "filas"}.</div>
    </div>
  `;

  return { html, hasData: rows.length > 0 };
}

/* -------------------- Dialog -------------------- */
export async function openCitasAlumnoDialog(tipo = "pdf") {
  ensureSwalStyles();

  const html = `
  <style>
    /* Lista de alumnos: grid responsiva tipo chips */
    .sw-list{
      display:flex;
      flex-wrap:wrap;
      gap:8px;
      max-height:230px;
      overflow-y:auto;
      margin-top:2px;
      padding:2px 0 4px;
    }
    .sw-item{
      flex:1 1 100%;
      display:flex;
      align-items:center;
      gap:8px;
      padding:8px 10px;
      border-radius:999px;
      border:1px solid #e5e7eb;
      background:#ffffff;
      font-size:13px;
      text-align:left;
      cursor:pointer;
      transition:
        background .16s cubic-bezier(.22,1,.36,1),
        border-color .16s cubic-bezier(.22,1,.36,1),
        box-shadow .16s cubic-bezier(.22,1,.36,1),
        transform .12s ease-out;
    }
    .sw-item .av{
      flex:0 0 28px;
      height:28px;
      display:flex;
      align-items:center;
      justify-content:center;
      border-radius:999px;
      background:#eef2ff;
      color:#4f46e5;
      font-weight:700;
      font-size:13px;
    }
    .sw-item .txt{
      min-width:0;
      display:flex;
      flex-direction:column;
    }
    .sw-item .nm{
      font-weight:600;
      color:#111827;
      white-space:nowrap;
      overflow:hidden;
      text-overflow:ellipsis;
    }
    .sw-item .mt{
      font-size:12px;
      font-weight:600;
      color:#4f46e5;
      white-space:nowrap;
    }
    .sw-item:hover{
      background:#f5f3ff;
      border-color:#c4b5fd;
      box-shadow:0 0 0 1px rgba(129,140,248,.4);
      transform:translateY(-1px);
    }
    .sw-item.active{
      background:#4f46e5;
      border-color:#4f46e5;
      box-shadow:0 10px 20px rgba(79,70,229,.35);
    }
    .sw-item.active .av{
      background:rgba(255,255,255,.08);
      color:#eef2ff;
    }
    .sw-item.active .nm,
    .sw-item.active .mt{
      color:#ffffff;
    }
    /* Breakpoints: 1 col en mobile, 2 en md, 3 en xl */
    @media (min-width:768px){
      .sw-item{flex:1 1 calc(50% - 8px);}
    }
    @media (min-width:1200px){
      .sw-item{flex:1 1 calc(33.333% - 8px);}
    }
  </style>

  <div class="container-fluid p-0">
    <div class="sw-head">
      <i class="${tipo==="excel" ? "bi bi-filetype-xlsx" : "bi bi-filetype-pdf"} text-primary"></i>
      <div>Selecciona un rango y (opcional) un alumno</div>
    </div>

    <div class="row g-3">
      <!-- Fechas -->
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
          <div class="mini-hint mt-2">
            <span class="chip"><b>Enter</b> para confirmar</span>
          </div>
        </div>
      </div>

      <!-- Alumno -->
      <div class="col-12 col-md-6">
        <div class="sw-section h-100">
          <label class="form-label mb-2">Alumno (nombre / matrícula)</label>
          <input id="sw-alumno-input" class="form-control mb-2" type="text" placeholder="Buscar… (vacío = todos)">
          <div class="sw-list" id="sw-alumno-list" role="listbox" aria-label="Alumnos"></div>
          <div class="mini-hint mt-2">Vacío = <b>todos</b> los estudiantes.</div>
        </div>
      </div>
    </div>
  </div>`;

  const res = await Swal.fire({
    title: "Citas por alumno",
    html,
    customClass: {
      container: "sw-report",
      popup: "sw-report",
      confirmButton: "btn btn-primary",
      cancelButton: "btn btn-light",
    },
    width: 960,
    heightAuto: false,
    showCloseButton: true,
    closeButtonHtml: "&times;",
    buttonsStyling: false,
    showCancelButton: true,
    confirmButtonText: "Previsualizar",
    cancelButtonText: "Cancelar",
    focusConfirm: false,

    didOpen: async () => {
      const $preset = document.getElementById("sw-datepreset");
      const $custom = document.getElementById("sw-customrange");
      const $ainput = document.getElementById("sw-alumno-input");
      const $alist  = document.getElementById("sw-alumno-list");

      const toggleCustom = () => {
        $custom.style.display = $preset.value === "custom" ? "flex" : "none";
      };

      const renderAlumnos = (list = []) => {
        if (!Array.isArray(list) || list.length === 0) {
          $alist.innerHTML = `<div class="mini-hint">Sin alumnos para mostrar.</div>`;
          return;
        }
        $alist.innerHTML = list.map((o, i) => {
          const base = (o.nombre || o.matricula || "?").toString().trim();
          const initial = base ? base.charAt(0).toUpperCase() : "?";
          const labelSafe = (o.label || "").replace(/"/g, "&quot;");
          const matSafe   = (o.matricula || "").replace(/"/g, "&quot;");
          return `
            <button type="button" class="sw-item"
              data-label="${labelSafe}"
              data-mat="${matSafe}"
              data-idx="${i}">
              <span class="av">${initial}</span>
              <div class="txt">
                <span class="nm">${o.nombre}</span>
                <span class="mt">${o.matricula || "—"}</span>
              </div>
            </button>`;
        }).join("");
        Array.from($alist.querySelectorAll(".sw-item")).forEach((el) => {
          el.addEventListener("click", () => {
            $alist.querySelectorAll(".sw-item").forEach(n => n.classList.remove("active"));
            el.classList.add("active");
          });
        });
      };

      let all = [];
      try {
        const result = await loadAllAlumnos();
        all = Array.isArray(result) && result.length ? result : alumnosCache;
      } catch (_) {
        all = alumnosCache;
      }
      renderAlumnos(all);

      const doFilterLocal = debounce((t) => {
        const term = (t || "").trim();
        renderAlumnos(term ? filterAlumnosLocal(term) : all);
      }, 140);
      $ainput.addEventListener("input", (e) => doFilterLocal(e.target.value || ""));

      $preset.addEventListener("change", toggleCustom);
      toggleCustom();
    },

    preConfirm: () => {
      const $preset = document.getElementById("sw-datepreset");
      const $d = document.getElementById("sw-desde");
      const $h = document.getElementById("sw-hasta");
      const $alist  = document.getElementById("sw-alumno-list");
      const $ainput = document.getElementById("sw-alumno-input");

      let { desde, hasta } = presetToRange($preset.value);
      if ($preset.value === "custom") {
        desde = ($d.value || "").trim();
        hasta = ($h.value || "").trim();
        if (!desde || !hasta) {
          Swal.showValidationMessage("Selecciona fecha <b>Desde</b> y <b>Hasta</b>.");
          return false;
        }
        if (desde > hasta) {
          Swal.showValidationMessage("<b>Desde</b> no puede ser mayor que <b>Hasta</b>.");
          return false;
        }
      }

      const filtros = { ...(desde ? { desde } : {}), ...(hasta ? { hasta } : {}) };

      const chosenEl    = $alist.querySelector(".sw-item.active");
      const chosenMat   = chosenEl?.getAttribute("data-mat")   || "";
      const chosenLabel = chosenEl?.getAttribute("data-label") || "";
      const typed       = ($ainput?.value || "").trim();

      let valueForApi = "";
      let valueForChip = "";

      if (chosenMat) {
        valueForApi  = chosenMat;                // lo que viaja al backend (matrícula)
        valueForChip = chosenLabel || chosenMat; // lo que se muestra en chip
      } else if (typed) {
        valueForApi  = typed;
        valueForChip = typed;
      }

      if (valueForApi) {
        filtros.alumno = valueForApi;
        filtros.alumno_label = valueForChip;
      }

      return filtros;
    }
  });

  if (!res.isConfirmed) return;

  const filtros = res.value || {};
  try {
    const { html: pv, hasData } = await previewTable(filtros);
    const next = await Swal.fire({
      title: "Previsualización",
      html: pv,
      customClass: { container: "sw-report" },
      showCancelButton: true,
      confirmButtonText: "Descargar",
      cancelButtonText: "Cancelar",
      width: 980,
      heightAuto: false,
      showCloseButton: true,
      closeButtonHtml: "&times;"
    });
    if (!next.isConfirmed) return;
    if (!hasData) {
      await Swal.fire({
        icon:"warning",
        title:"Sin datos para exportar",
        timer:1300,
        showConfirmButton:false,
        customClass:{container:"sw-report"}
      });
      return;
    }
  } catch (e) {
    await Swal.fire({
      icon:"error",
      title:"No se pudo previsualizar",
      text:e.message || "Error",
      customClass:{container:"sw-report"}
    });
    return;
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
    const { blob, filename } = await exportCitasAlumno(tipo, filtros);
    const a = document.createElement("a");
    a.href = URL.createObjectURL(blob);
    a.download = filename || `citas_${new Date().toISOString().slice(0,19).replace(/[:T]/g,"-")}.${tipo==="excel"?"xlsx":"pdf"}`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    Swal.close();
    Swal.fire({
      icon:"success",
      title:"Descarga lista",
      timer:1200,
      showConfirmButton:false,
      customClass:{container:"sw-report"}
    });
  } catch (e) {
    Swal.close();
    Swal.fire({
      icon:"error",
      title:"No se pudo exportar",
      text:e.message || "Error desconocido",
      customClass:{container:"sw-report"}
    });
  }
}
