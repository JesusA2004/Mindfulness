// src/assets/js/reportes/recompensasCanjeadas.js
import { Swal, ensureSwalStyles, presetToRange } from "./common/utils";
import { apiBase, fetchJSON, downloadBinary } from "./common/apiClient";

const ENDPOINT = "reportes/recompensas-canjeadas";
const EXPORT_REPORTE = "recompensas-canjeadas";

function buildQuery(f = {}) {
  const p = new URLSearchParams();
  if (f.desde) p.set("desde", f.desde);
  if (f.hasta) p.set("hasta", f.hasta);
  if (f.tipo) p.set("tipo", f.tipo); // en el back se acepta 'tipo' | 'recompensa' | 'nombre'
  return p.toString();
}

const asArray = (v) => (Array.isArray(v) ? v : []);

/* ------------ API helpers reporte ------------ */
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

/* ------------ Catálogo de recompensas (para el filtro) ------------ */

let recompensasCache = [];

/**
 * Carga todas las recompensas *que tienen canjeos* a partir del propio reporte.
 * Así nos aseguramos de que los nombres coincidan con los que ves en la tabla.
 */
async function loadAllRecompensas() {
  if (recompensasCache.length) return recompensasCache;

  // Usamos el endpoint del reporte sin filtros (todas las fechas, todas las recompensas)
  const json = await getRecompensasCanjeadas({});
  const rows = asArray(json.rows);

  const map = new Map();
  for (const r of rows) {
    const nombre = String(r.recompensa ?? "").trim();
    if (!nombre) continue;

    // Tomamos puntos del primer registro que encontremos
    const puntosRaw = r.puntos;
    const puntos =
      typeof puntosRaw === "number"
        ? puntosRaw
        : isNaN(parseInt(puntosRaw, 10))
        ? null
        : parseInt(puntosRaw, 10);

    if (!map.has(nombre)) {
      map.set(nombre, {
        id: nombre,
        nombre,
        puntos,
      });
    }
  }

  recompensasCache = Array.from(map.values()).sort((a, b) =>
    a.nombre.localeCompare(b.nombre, "es")
  );

  return recompensasCache;
}

function filterRecompensasLocal(term = "") {
  const t = term.trim().toLowerCase();
  if (!t) return recompensasCache;
  return recompensasCache.filter((r) =>
    r.nombre.toLowerCase().includes(t)
  );
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

  const recompensaChip = filtros.tipo_label || filtros.tipo || "";

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
      <th>Alumno</th>
      <th>Matricula</th>
      <th>Recompensa</th>
      <th>Puntos</th>
      <th>Fecha</th>
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
    .sw-list{
      margin-top:6px;
      max-height:220px;
      overflow:auto;
    }
    .sw-item{
      width:100%;
      border:0;
      background:#fff;
      border-radius:999px;
      padding:8px 12px;
      display:flex;
      align-items:center;
      justify-content:flex-start;
      gap:10px;
      margin-bottom:6px;
      box-shadow:0 0 0 1px #e5e7eb;
      font-size:13px;
      text-align:left;
      cursor:pointer;
      transition:box-shadow .15s ease, transform .15s ease, background .15s ease;
    }
    .sw-item .sw-avatar{
      width:28px;
      height:28px;
      border-radius:999px;
      display:flex;
      align-items:center;
      justify-content:center;
      font-weight:600;
      font-size:13px;
      background:#ede9fe;
      color:#4c1d95;
    }
    .sw-item .sw-main{
      display:flex;
      flex-direction:column;
      flex:1;
    }
    .sw-item .nm{font-weight:600;color:#111827;}
    .sw-item .mt{font-size:11px;color:#6b7280;}
    .sw-item.active{
      box-shadow:0 0 0 2px #a855f7;
      background:#faf5ff;
      transform:translateY(-1px);
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
          <input id="sw-tipo-input" class="form-control mb-1" type="text" placeholder="(vacío = todas)">
          <div class="mini-hint">Puedes escribir parte del nombre (contiene).</div>
          <div class="sw-list" id="sw-recompensa-list"></div>
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
    didOpen: async () => {
      const $preset = document.getElementById("sw-datepreset");
      const $custom = document.getElementById("sw-customrange");
      const $tinput = document.getElementById("sw-tipo-input");
      const $list   = document.getElementById("sw-recompensa-list");

      const toggleCustom = () => {
        $custom.style.display = $preset.value === "custom" ? "flex" : "none";
      };
      $preset.addEventListener("change", toggleCustom);
      toggleCustom();

      // ----- lista de recompensas (desde el propio reporte) -----
      const render = (items = []) => {
        if (!items.length) {
          $list.innerHTML =
            `<div class="mini-hint">No hay recompensas que coincidan con la búsqueda.</div>`;
          return;
        }
        $list.innerHTML = items
          .map(
            (o, i) => `
          <button type="button" class="sw-item" data-label="${o.nombre}" data-idx="${i}">
            <div class="sw-avatar">${(o.nombre || "?").charAt(0).toUpperCase()}</div>
            <div class="sw-main">
              <div class="nm">${o.nombre}</div>
              <div class="mt">${
                o.puntos != null ? `${o.puntos} puntos necesarios` : ""
              }</div>
            </div>
          </button>`
          )
          .join("");

        Array.from($list.querySelectorAll(".sw-item")).forEach((el) => {
          el.addEventListener("click", () => {
            $list
              .querySelectorAll(".sw-item")
              .forEach((n) => n.classList.remove("active"));
            el.classList.add("active");
            const label = el.getAttribute("data-label") || "";
            $tinput.value = label;
          });
        });
      };

      const all = await loadAllRecompensas();
      render(all);

      $tinput.addEventListener("input", (e) => {
        const val = (e.target.value || "").trim();
        render(filterRecompensasLocal(val));
      });
    },
    preConfirm: () => {
      const $preset = document.getElementById("sw-datepreset");
      const $d = document.getElementById("sw-desde");
      const $h = document.getElementById("sw-hasta");
      const $t = document.getElementById("sw-tipo-input");
      const $list = document.getElementById("sw-recompensa-list");

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

      const chosen =
        $list.querySelector(".sw-item.active")?.getAttribute("data-label") ||
        "";
      const typed = ($t?.value || "").trim();
      const val = (chosen || typed).trim();
      if (val) {
        filtros.tipo = val;       // el back acepta 'tipo'
        filtros.tipo_label = val; // para el chip en la tabla
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
