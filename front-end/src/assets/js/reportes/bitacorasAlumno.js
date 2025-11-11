// src/assets/js/reportes/bitacorasAlumno.js
import { Swal, ensureSwalStyles, presetToRange } from "./common/utils";
import { apiBase, fetchJSON, downloadBinary, authHeaders } from "./common/apiClient";

const ENDPOINT = "reportes/bitacoras-por-alumno";
const EXPORT_REPORTE = "bitacoras-por-alumno";

/* -------------------- helpers -------------------- */
function buildQuery(f = {}) {
  const p = new URLSearchParams();
  if (f.desde) p.set("desde", f.desde);
  if (f.hasta) p.set("hasta", f.hasta);
  return p.toString();
}

/* -------------------- API -------------------- */
export async function getBitacorasAlumno(f = {}) {
  return fetchJSON(ENDPOINT, buildQuery(f));
}

/* -------------------- Preview (tabla bonita) -------------------- */
async function previewTable(filtros = {}) {
  const url = `${apiBase()}/${ENDPOINT}${buildQuery(filtros) ? `?${buildQuery(filtros)}` : ""}`;
  const res = await fetch(url, { headers: authHeaders() });
  if (!res.ok) throw new Error("No se pudo obtener datos del reporte");
  const json = await res.json();

  const rows = Array.isArray(json.rows) ? json.rows : [];
  const total = Number(json.total || 0);
  const rango = (json.meta && json.meta.rango) || (filtros.desde && filtros.hasta
    ? `${filtros.desde} a ${filtros.hasta}` : "Todas las fechas");

  const head = `
    <tr>
      <th style="text-align:left;">Alumno</th>
      <th style="text-align:left;">Matrícula</th>
      <th style="text-align:right;">Total</th>
    </tr>`;

  const body = rows.map(r => `
    <tr class="pv-row">
      <td>${r.alumno ?? "Alumno"}</td>
      <td>${r.matricula ?? "—"}</td>
      <td style="text-align:right;font-weight:800;">${Number(r.total || 0)}</td>
    </tr>
  `).join("");

  const empty = `<tr><td colspan="3" style="text-align:center;color:#94a3b8;padding:18px 8px;">Sin datos en el rango seleccionado.</td></tr>`;

  const html = `
  <style>
    .chips{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px}
    .chip{display:inline-flex;gap:6px;align-items:center;padding:6px 10px;border:1px solid #e5e7eb;border-radius:999px;background:#fff;font-size:12px}
    .pv-card{border:1px solid #e5e7eb;border-radius:16px;background:#fff;overflow:hidden}
    .pv-head{padding:10px 12px;border-bottom:1px solid #eef2ff;display:flex;align-items:center;justify-content:space-between}
    .pv-title{font-weight:800;color:#0f172a}
    .pv-body{padding:10px 12px}
    .pv-table{width:100%;border-collapse:collapse}
    .pv-table th,.pv-table td{padding:10px 8px;border-bottom:1px solid #f1f5f9;font-size:13px;color:#0f172a}
    .pv-row:hover{background:#f8fafc;transition:background .16s linear}
    .pv-foot{padding:10px 12px;color:#334155;font-weight:800;text-align:right}
  </style>
  <div class="chips">
    <span class="chip"><b>Rango:</b> ${rango}</span>
  </div>

  <div class="pv-card">
    <div class="pv-head"><div class="pv-title">Vista previa</div></div>
    <div class="pv-body">
      <table class="pv-table">
        <thead>${head}</thead>
        <tbody>${body || empty}</tbody>
      </table>
    </div>
    <div class="pv-foot">Total de bitácoras: ${total}</div>
  </div>`;

  return { html, hasData: rows.length > 0 };
}

/* -------------------- Export -------------------- */
export async function exportBitacorasAlumno(tipo = "pdf", f = {}) {
  const qs = buildQuery(f);
  const url = `${apiBase()}/reportes/export?tipo=${tipo}&reporte=${EXPORT_REPORTE}${qs ? `&${qs}` : ""}&_=${Date.now()}`;
  return downloadBinary(url);
}

/* -------------------- Dialog -------------------- */
export async function openBitacorasAlumnoDialog(tipo = "pdf") {
  ensureSwalStyles();

  const html = `
  <div class="container-fluid p-0">
    <div class="sw-head">
      <i class="${tipo==='excel'?'bi bi-filetype-xlsx':'bi bi-filetype-pdf'} text-primary"></i>
      <div>Selecciona un rango de fechas</div>
    </div>

    <div class="row g-3">
      <div class="col-12">
        <div class="sw-section h-100">
          <label class="form-label mb-2">Rango de fechas</label>
          <select id="sw-datepreset" class="form-select mb-2">
            <option value="today">Hoy</option>
            <option value="yesterday">Ayer</option>
            <option value="last7">Últimos 7 días</option>
            <option value="month">Este mes</option>
            <option value="prevmonth">Mes pasado</option>
            <option value="all" selected>Todas las fechas</option>
            <option value="custom">Personalizado…</option>
          </select>
          <div id="sw-customrange" class="row g-2" style="display:none">
            <div class="col-6"><label class="form-label mb-1">Desde</label><input id="sw-desde" type="date" class="form-control" /></div>
            <div class="col-6"><label class="form-label mb-1">Hasta</label><input id="sw-hasta" type="date" class="form-control" /></div>
          </div>
          <div class="mini-hint mt-2"><span class="chip"><b>Enter</b> para confirmar</span></div>
        </div>
      </div>
    </div>
  </div>`;

  const res = await Swal.fire({
    title: "Bitácoras por alumno",
    html,
    customClass: { container: "sw-report", popup: "sw-report", confirmButton: "btn btn-primary", cancelButton: "btn btn-light" },
    buttonsStyling: false,
    showCancelButton: true,
    confirmButtonText: "Previsualizar",
    cancelButtonText: "Cancelar",
    focusConfirm: false,
    width: 860,              // modal más ancho
    heightAuto: false,
    didOpen: () => {
      const $preset = document.getElementById("sw-datepreset");
      const $custom = document.getElementById("sw-customrange");
      const toggleCustom = () => { $custom.style.display = $preset.value === "custom" ? "flex" : "none"; };
      $preset.addEventListener("change", toggleCustom);
      toggleCustom();
    },
    preConfirm: () => {
      const $preset = document.getElementById("sw-datepreset");
      const $d = document.getElementById("sw-desde");
      const $h = document.getElementById("sw-hasta");
      let { desde, hasta } = presetToRange($preset.value);
      if ($preset.value === "custom") {
        desde = ($d.value || "").trim();
        hasta = ($h.value || "").trim();
        if (!desde || !hasta) { Swal.showValidationMessage("Selecciona fecha <b>Desde</b> y <b>Hasta</b>."); return false; }
        if (desde > hasta) { Swal.showValidationMessage("Desde no puede ser mayor que Hasta."); return false; }
      }
      return { ...(desde ? { desde } : {}), ...(hasta ? { hasta } : {}) };
    }
  });

  if (!res.isConfirmed) return;

  // ===== Previsualización =====
  let filtros = res.value || {};
  try {
    const { html: previewHtml, hasData } = await previewTable(filtros);
    const next = await Swal.fire({
      title: "Previsualización",
      html: previewHtml,
      customClass: { container: "sw-report" },
      showCancelButton: true,
      confirmButtonText: "Descargar",
      cancelButtonText: "Cancelar",
      width: 900,
      heightAuto: false
    });
    if (!next.isConfirmed) return;
    if (!hasData) {
      await Swal.fire({
        icon: "warning",
        title: "Sin datos para exportar",
        timer: 1300,
        showConfirmButton: false,
        customClass: { container: "sw-report" }
      });
      return;
    }
  } catch (e) {
    await Swal.fire({
      icon: "error",
      title: "No se pudo previsualizar",
      text: e.message || "Error",
      customClass: { container: "sw-report" }
    });
    return;
  }

  // ===== Export =====
  Swal.fire({
    title: "Generando archivo…",
    html: "Preparando la descarga",
    didOpen: () => Swal.showLoading(),
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
    customClass: { container: "sw-report" }
  });

  try {
    const { blob, filename } = await exportBitacorasAlumno(tipo, filtros);
    const a = document.createElement("a");
    a.href = URL.createObjectURL(blob);
    a.download = filename || `bitacoras_${new Date().toISOString().slice(0,19).replace(/[:T]/g,"-")}.${tipo==="excel"?"xlsx":"pdf"}`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    Swal.close();
    Swal.fire({ icon:"success", title:"Descarga lista", timer:1200, showConfirmButton:false, customClass:{container:"sw-report"} });
  } catch (e) {
    Swal.close();
    Swal.fire({ icon:"error", title:"No se pudo exportar", text:e.message || "Error desconocido", customClass:{container:"sw-report"} });
  }
}
