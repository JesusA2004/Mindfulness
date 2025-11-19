// src/assets/js/reportes/encuestasResultados.js
import { Swal, ensureSwalStyles, presetToRange } from "./common/utils";
import { apiBase, fetchJSON, downloadBinary, authHeaders } from "./common/apiClient";

const ENDPOINT = "reportes/encuestas-resultados";
const EXPORT_REPORTE = "encuestas-resultados";

/* -------------------- helpers -------------------- */
function buildQuery(f = {}) {
  const p = new URLSearchParams();
  if (f.desde) p.set("desde", f.desde);
  if (f.hasta) p.set("hasta", f.hasta);
  return p.toString();
}
const asArray = v => Array.isArray(v) ? v : [];

/* -------------------- API -------------------- */
export async function getEncuestasResultados(f = {}) {
  return fetchJSON(ENDPOINT, buildQuery(f));
}

/* -------------------- Preview (barras por puntaje) -------------------- */
async function previewChart(filtros = {}) {
  const url = `${apiBase()}/${ENDPOINT}${buildQuery(filtros) ? `?${buildQuery(filtros)}` : ""}`;
  const res = await fetch(url, { headers: authHeaders() });
  if (!res.ok) throw new Error("No se pudo obtener datos del reporte");
  const json = await res.json();

  let labels = asArray(json.labels);
  let data   = asArray(json.data).map(n => Number(n || 0));
  let total  = Number(json.total || 0);

  const chartData = asArray(json?.meta?.chartData).length
    ? json.meta.chartData
    : labels.map((l, i) => {
        const v = Number(data[i] || 0);
        return {
          label: `${l}★`,
          value: v,
          pct: total ? Math.round((v / total) * 1000) / 10 : 0
        };
      });

  const chips = [
    `<span class="chip"><b>Rango:</b> ${
      json?.meta?.rango ||
      (filtros.desde && filtros.hasta ? `${filtros.desde} a ${filtros.hasta}` : "Todas las fechas")
    }</span>`
  ].join("");

  const bars = chartData.map(b => {
    const pct = Math.max(0, Math.min(100, Number(b.pct || 0)));
    const val = Number(b.value || 0);
    const lbl = String(b.label || "");
    return `
      <div class="bar-col" title="Puntaje ${lbl}">
        <div class="val">${val} (${pct.toFixed(1)}%)</div>
        <div class="bar"><span class="fill" style="height:${pct}%"></span></div>
        <div class="lbl">${lbl}</div>
      </div>
    `;
  }).join("");

  const html = `
    <style>
      .chips{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px}
      .chip{display:inline-flex;gap:6px;align-items:center;padding:6px 10px;border:1px solid #e5e7eb;border-radius:999px;background:#fff;font-size:12px}
      .pv-wrap{border:1px solid #e5e7eb;border-radius:18px;padding:16px;background:#fff}
      .pv-plot{
        border-left:1px solid #e5e7eb;   /* eje Y */
        border-bottom:1px solid #e5e7eb; /* eje X */
        padding:8px 6px 12px 6px;
      }
      .pv-bars{
        text-align:center;
        white-space:nowrap;
        font-size:0;
      }
      .bar-col{display:inline-block;vertical-align:bottom;width:16%;margin:0 1.5%;font-size:12px}
      .bar{width:28px;height:150px;background:#eef2ff;border:1px solid #e5e7eb;border-radius:6px 6px 0 0;overflow:hidden;margin:0 auto;position:relative}
      .fill{display:block;width:100%;position:absolute;bottom:0;height:0;background:#7c3aed}
      .val{font-size:12px;color:#334155;font-weight:800;margin-bottom:6px}
      .lbl{font-size:12px;font-weight:800;color:#0f172a;margin-top:6px;line-height:1.15;max-width:120px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
      .pv-total{margin-top:10px;text-align:center;font-size:16px;font-weight:800;color:#334155}
      .axis-row{margin-top:8px;font-size:11px;color:#64748b;display:flex;justify-content:space-between}
    </style>
    <div class="chips">${chips}</div>
    <div class="pv-wrap">
      <div class="pv-plot">
        <div class="pv-bars">
          ${bars || '<div class="text-muted">Sin datos en el rango seleccionado.</div>'}
        </div>
      </div>
      <div class="axis-row">
        <span>Eje Y: Conteo de calificaciones</span>
        <span>Eje X: Número de estrellas (puntaje)</span>
      </div>
      <div class="pv-total">Total de calificaciones: <b>${total}</b></div>
    </div>`;

  return { html, hasData: chartData.length > 0 && total > 0 };
}

/* -------------------- Export -------------------- */
export async function exportEncuestasResultados(tipo = "pdf", f = {}) {
  const qs = buildQuery(f);
  const url = `${apiBase()}/reportes/export?tipo=${tipo}&reporte=${EXPORT_REPORTE}${qs ? `&${qs}` : ""}&_=${Date.now()}`;
  return downloadBinary(url);
}

/* -------------------- Dialog -------------------- */
export async function openEncuestasResultadosDialog(tipo = "pdf") {
  ensureSwalStyles();

  const html = `
  <div class="container-fluid p-0">
    <div class="sw-head">
      <i class="${tipo === "excel" ? "bi bi-filetype-xlsx" : "bi bi-filetype-pdf"} text-primary"></i>
      <div>Selecciona un rango de fechas</div>
    </div>

    <div class="row g-3">
      <div class="col-12">
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
    </div>
  </div>`;

  const res = await Swal.fire({
    title: "Resultados de encuestas",
    html,
    customClass: {
      container: "sw-report",
      popup: "sw-report",
      confirmButton: "btn btn-primary",
      cancelButton: "btn btn-light",
    },
    buttonsStyling: false,
    showCancelButton: true,
    confirmButtonText: "Previsualizar",
    cancelButtonText: "Cancelar",
    focusConfirm: false,
    width: 960,
    heightAuto: false,
    showCloseButton: true,
    closeButtonHtml: "&times;",
    didOpen: () => {
      const $preset = document.getElementById("sw-datepreset");
      const $custom = document.getElementById("sw-customrange");
      const toggleCustom = () => {
        $custom.style.display = $preset.value === "custom" ? "flex" : "none";
      };
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
        if (!desde || !hasta) {
          Swal.showValidationMessage("Selecciona fecha <b>Desde</b> y <b>Hasta</b>.");
          return false;
        }
        if (desde > hasta) {
          Swal.showValidationMessage("<b>Desde</b> no puede ser mayor que <b>Hasta</b>.");
          return false;
        }
      }
      return { ...(desde ? { desde } : {}), ...(hasta ? { hasta } : {}) };
    }
  });

  if (!res.isConfirmed) return;

  // ===== Previsualización =====
  const filtros = res.value || {};
  try {
    const { html: previewHtml, hasData } = await previewChart(filtros);
    const next = await Swal.fire({
      title: "Previsualización",
      html: previewHtml,
      customClass: { container: "sw-report" },
      showCancelButton: true,
      confirmButtonText: "Generar",
      cancelButtonText: "Cancelar",
      width: 980,
      heightAuto: false,
      showCloseButton: true,
      closeButtonHtml: "&times;"
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
    customClass: { container: "sw-report" },
  });

  try {
    const { blob, filename } = await exportEncuestasResultados(tipo, filtros);
    const a = document.createElement("a");
    a.href = URL.createObjectURL(blob);
    a.download =
      filename ||
      `encuestas_${new Date().toISOString().slice(0, 19).replace(/[:T]/g, "-")}.${
        tipo === "excel" ? "xlsx" : "pdf"
      }`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    Swal.close();
    Swal.fire({
      icon: "success",
      title: "Descarga lista",
      timer: 1200,
      showConfirmButton: false,
      customClass: { container: "sw-report" }
    });
  } catch (e) {
    Swal.close();
    Swal.fire({
      icon: "error",
      title: "No se pudo exportar",
      text: e.message || "Error desconocido",
      customClass: { container: "sw-report" }
    });
  }
}
