// src/assets/js/reportes/encuestasResultados.js
import { Swal, ensureSwalStyles, presetToRange } from "./common/utils";
import { apiBase, fetchJSON, downloadBinary } from "./common/apiClient";

const ENDPOINT = "reportes/encuestas-resultados";
const EXPORT_REPORTE = "encuestas-resultados";

function buildQuery(f = {}) {
  const p = new URLSearchParams();
  if (f.desde) p.set("desde", f.desde);
  if (f.hasta) p.set("hasta", f.hasta);
  if (f.encuesta) p.set("encuesta", f.encuesta);
  return p.toString();
}

export async function getEncuestasResultados(f = {}) { return fetchJSON(ENDPOINT, buildQuery(f)); }

export async function exportEncuestasResultados(tipo = "pdf", f = {}) {
  const qs = buildQuery(f);
  const url = `${apiBase()}/reportes/export?tipo=${tipo}&reporte=${EXPORT_REPORTE}${qs ? `&${qs}` : ""}&_=${Date.now()}`;
  return downloadBinary(url);
}

export async function openEncuestasResultadosDialog(tipo = "pdf") {
  ensureSwalStyles();
  const html = `
  <div class="container-fluid p-0">
    <div class="sw-head"><i class="${tipo==='excel'?'bi bi-filetype-xlsx':'bi bi-filetype-pdf'} text-primary"></i>
      <div>Rango y (opcional) encuesta</div></div>
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
            <div class="col-6"><label class="form-label mb-1">Desde</label><input id="sw-desde" type="date" class="form-control" /></div>
            <div class="col-6"><label class="form-label mb-1">Hasta</label><input id="sw-hasta" type="date" class="form-control" /></div>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-6">
        <div class="sw-section h-100">
          <label class="form-label mb-2">Encuesta (título o ID)</label>
          <input id="sw-encuesta-input" class="form-control mb-2" type="text" placeholder="Buscar… (vacío = todas)">
        </div>
      </div>
    </div>
  </div>`;

  const res = await Swal.fire({
    title: "Resultados de encuestas",
    html,
    customClass: { container: "sw-report", popup: "sw-report", confirmButton: "btn btn-primary", cancelButton: "btn btn-light" },
    buttonsStyling: false,
    showCancelButton: true,
    confirmButtonText: "Descargar",
    cancelButtonText: "Cancelar",
    focusConfirm: false,
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
      const $e = document.getElementById("sw-encuesta-input");

      let { desde, hasta } = presetToRange($preset.value);
      if ($preset.value === "custom") {
        desde = ($d.value || "").trim();
        hasta = ($h.value || "").trim();
        if (!desde || !hasta) { Swal.showValidationMessage("Selecciona fecha <b>Desde</b> y <b>Hasta</b>."); return false; }
      }
      const filtros = { ...(desde ? { desde } : {}), ...(hasta ? { hasta } : {}) };
      const v = ($e?.value || "").trim(); if (v) filtros.encuesta = v;
      return filtros;
    }
  });

  if (!res.isConfirmed) return;

  Swal.fire({ title: "Generando archivo…", html: "Preparando la descarga", didOpen: () => Swal.showLoading(), allowOutsideClick: false, allowEscapeKey: false, showConfirmButton: false, customClass: { container: "sw-report" } });

  try {
    const { blob, filename } = await exportEncuestasResultados(tipo, res.value || {});
    const a = document.createElement("a"); a.href = URL.createObjectURL(blob);
    a.download = filename || `encuestas_${new Date().toISOString().slice(0,19).replace(/[:T]/g,"-")}.${tipo==="excel"?"xlsx":"pdf"}`;
    document.body.appendChild(a); a.click(); a.remove();
    Swal.close(); Swal.fire({ icon:"success", title:"Descarga lista", timer:1200, showConfirmButton:false, customClass:{container:"sw-report"} });
  } catch (e) {
    Swal.close(); Swal.fire({ icon:"error", title:"No se pudo exportar", text:e.message || "Error desconocido", customClass:{container:"sw-report"} });
  }
}
