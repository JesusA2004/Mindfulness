// src/assets/js/reportes/recompensasCanjeadas.js
import { Swal, ensureSwalStyles, presetToRange } from "./common/utils";
import { apiBase, fetchJSON, downloadBinary } from "./common/apiClient";

const ENDPOINT = "reportes/recompensas-canjeadas";
const EXPORT_REPORTE = "recompensas-canjeadas";

function buildQuery(f = {}) {
  const p = new URLSearchParams();
  if (f.desde) p.set("desde", f.desde);
  if (f.hasta) p.set("hasta", f.hasta);
  // nombre de recompensa (nuevo): "recompensa"
  if (f.recompensa) p.set("recompensa", f.recompensa);
  return p.toString();
}

const asArray = (v) => (Array.isArray(v) ? v : []);

/* ------------ API helpers ------------ */
export async function getRecompensasCanjeadas(f = {}) {
  return fetchJSON(ENDPOINT, buildQuery(f));
}

export async function exportRecompensasCanjeadas(tipo = "pdf", f = {}) {
  const qs = buildQuery(f);
  const url = `${apiBase()}/reportes/export?tipo=${tipo}&reporte=${EXPORT_REPORTE}${
    qs ? `&${qs}` : ""
  }&_=${Date.now()}`;
  return downloadBinary(url);
}

/* ------------ Previsualización (tabla) ------------ */
async function previewTable(filtros = {}) {
  const json = await getRecompensasCanjeadas(filtros);

  const rows = asArray(json.rows).map((r) => ({
    alumno: String(r.alumno ?? ""),
    matricula: String(r.matricula ?? ""),
    recompensa: String(r.recompensa ?? ""),
    puntos: String(r.puntos ?? ""),
    fecha: String((r.fecha ?? "").toString().slice(0, 10)),
  }));

  const rangoLabel =
    filtros.desde && filtros.hasta
      ? `${filtros.desde} a ${filtros.hasta}`
      : "Todas las fechas";

  const recompensaChip = filtros.recompensa_label || filtros.recompensa || "";

  const chipsHtml = [
    `<span class="chip"><b>Rango:</b> ${rangoLabel}</span>`,
    recompensaChip
      ? `<span class="chip"><b>Recompensa:</b> ${recompensaChip}</span>`
      : "",
  ]
    .filter(Boolean)
    .join("");

  const head = `
    <tr>
      <th>alumno</th>
      <th>matricula</th>
      <th>recompensa</th>
      <th>puntos</th>
      <th>fecha</th>
    </tr>`;

  const body =
    rows
      .map(
        (r) => `
    <tr>
      <td>${r.alumno}</td>
      <td>${r.matricula}</td>
      <td>${r.recompensa}</td>
      <td>${r.puntos}</td>
      <td>${r.fecha}</td>
    </tr>`
      )
      .join("") ||
    `<tr><td colspan="5" class="text-muted">Sin datos en el rango seleccionado.</td></tr>`;

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
    <div class="chips">${chipsHtml}</div>
    <div class="pv-card">
      <table>
        <thead>${head}</thead>
        <tbody>${body}</tbody>
      </table>
      <div class="foot">Mostrando ${rows.length} ${
    rows.length === 1 ? "fila" : "filas"
  }.</div>
    </div>
  `;

  return { html, hasData: rows.length > 0 };
}

/* ------------ Diálogo principal ------------ */
export async function openRecompensasCanjeadasDialog(tipo = "pdf") {
  ensureSwalStyles();

  const html = `
  <style>
    .sw-head{
      display:flex;
      align-items:center;
      gap:10px;
      margin-bottom:14px;
      font-size:14px;
    }
    .sw-head i{font-size:20px;}
    .sw-section{
      border:1px solid #e5e7eb;
      border-radius:18px;
      padding:14px 16px;
      background:#f9fafb;
    }
    .sw-section .mini-hint{
      margin-top:8px;
      font-size:11px;
      color:#6b7280;
    }
    .sw-section .chip{
      display:inline-flex;
      align-items:center;
      gap:4px;
      padding:4px 8px;
      border-radius:999px;
      border:1px solid #e5e7eb;
      font-size:11px;
      background:#fff;
    }
  </style>

  <div class="container-fluid p-0">
    <div class="sw-head">
      <i class="${tipo === "excel" ? "bi bi-filetype-xlsx" : "bi bi-filetype-pdf"} text-primary"></i>
      <div>Selecciona un rango y (opcional) una recompensa</div>
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

      <!-- Nombre recompensa -->
      <div class="col-12 col-md-6">
        <div class="sw-section h-100">
          <label class="form-label mb-2">Nombre de recompensa</label>
          <input id="sw-recompensa-input" class="form-control mb-1" type="text" placeholder="(vacío = todas)">
          <div class="mini-hint">Puedes escribir parte del nombre (contiene).</div>
        </div>
      </div>
    </div>
  </div>`;

  const res = await Swal.fire({
    title: "Recompensas canjeadas",
    html,
    customClass: {
      container: "sw-report",
      popup: "sw-report",
      confirmButton: "btn btn-primary",
      cancelButton: "btn btn-light",
    },
    width: 960,
    heightAuto: false,
    buttonsStyling: false,
    showCancelButton: true,
    confirmButtonText: "Previsualizar",
    cancelButtonText: "Cancelar",
    focusConfirm: false,
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
      const $t = document.getElementById("sw-recompensa-input");

      let { desde, hasta } = presetToRange($preset.value);
      if ($preset.value === "custom") {
        desde = ($d.value || "").trim();
        hasta = ($h.value || "").trim();
        if (!desde || !hasta) {
          Swal.showValidationMessage(
            "Selecciona fecha <b>Desde</b> y <b>Hasta</b>."
          );
          return false;
        }
        if (desde > hasta) {
          Swal.showValidationMessage(
            "<b>Desde</b> no puede ser mayor que <b>Hasta</b>."
          );
          return false;
        }
      }

      const filtros = {
        ...(desde ? { desde } : {}),
        ...(hasta ? { hasta } : {}),
      };

      const val = ($t?.value || "").trim();
      if (val) {
        filtros.recompensa = val;
        filtros.recompensa_label = val;
      }

      return filtros;
    },
  });

  if (!res.isConfirmed) return;

  const filtros = res.value || {};

  // ===== Previsualización =====
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
      closeButtonHtml: "&times;",
    });

    if (!next.isConfirmed) return;

    if (!hasData) {
      await Swal.fire({
        icon: "warning",
        title: "Sin datos para exportar",
        timer: 1300,
        showConfirmButton: false,
        customClass: { container: "sw-report" },
      });
      return;
    }
  } catch (e) {
    await Swal.fire({
      icon: "error",
      title: "No se pudo previsualizar",
      text: e.message || "Error",
      customClass: { container: "sw-report" },
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
    const { blob, filename } = await exportRecompensasCanjeadas(tipo, filtros);
    const a = document.createElement("a");
    a.href = URL.createObjectURL(blob);
    a.download =
      filename ||
      `recompensas_${new Date()
        .toISOString()
        .slice(0, 19)
        .replace(/[:T]/g, "-")}.${tipo === "excel" ? "xlsx" : "pdf"}`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    Swal.close();
    Swal.fire({
      icon: "success",
      title: "Descarga lista",
      timer: 1200,
      showConfirmButton: false,
      customClass: { container: "sw-report" },
    });
  } catch (e) {
    Swal.close();
    Swal.fire({
      icon: "error",
      title: "No se pudo exportar",
      text: e.message || "Error desconocido",
      customClass: { container: "sw-report" },
    });
  }
}
