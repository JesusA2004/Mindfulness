// src/assets/js/reportes/topTecnicas.js
import { Swal, ensureSwalStyles, presetToRange } from "./common/utils";
import { apiBase, authHeaders, fetchJSON, downloadBinary } from "./common/apiClient";
import { loadCohortes, filterCohortesLocal, cohortesCache } from "./common/cohortes";

const ENDPOINT = "reportes/top-tecnicas";
const EXPORT_REPORTE = "top-tecnicas";

/* -------------------- helpers -------------------- */
function buildQuery(filtros = {}) {
  const p = new URLSearchParams();
  if (filtros.desde) p.set("desde", filtros.desde);
  if (filtros.hasta) p.set("hasta", filtros.hasta);
  if (filtros.grupo) p.set("grupo", filtros.grupo);
  return p.toString();
}
const asArray = (v) => Array.isArray(v) ? v : [];

/* -------------------- API -------------------- */
export async function getTopTecnicas(filtros = {}) {
  return fetchJSON(ENDPOINT, buildQuery(filtros));
}

/* -------------------- Preview chart (Top-4, single line) -------------------- */
async function previewTopChart(filtros = {}) {
  const url = `${apiBase()}/${ENDPOINT}${(buildQuery(filtros) ? `?${buildQuery(filtros)}` : "")}`;
  const res = await fetch(url, { headers: authHeaders() });
  if (!res.ok) throw new Error("No se pudo obtener datos del reporte");
  const json = await res.json();

  // Normaliza payload
  let rows = Array.isArray(json.rows) ? json.rows : [];
  let total = Number(json.total || 0);

  if ((!rows.length || !total) && json.data && Array.isArray(json.labels)) {
    const labels = json.labels || [];
    const data = asArray(json.data);
    rows = labels.map((lbl, i) => ({
      tecnica: String(lbl ?? "Técnica"),
      total: Number(data[i] || 0),
      usageDates: asArray(json.usageDates?.[i])
    }));
    total = rows.reduce((a, r) => a + (Number(r.total)||0), 0);
  }

  // Ordena desc y limita Top-4 SIEMPRE
  rows = rows
    .map(r => ({ ...r, total: Number(r.total || 0), tecnica: String(r.tecnica || "Técnica") }))
    .sort((a,b) => b.total - a.total)
    .slice(0, 4);

  total = rows.reduce((a, r) => a + (Number(r.total)||0), 0);
  const pct = (v) => total ? Math.round((Number(v||0)/total)*1000)/10 : 0;

  const cohortList = Array.isArray(json?.meta?.cohortes) ? json.meta.cohortes : [];

  // Chips resumen filtros
  const chips = [
    `<span class="chip"><b>Rango:</b> ${filtros.desde && filtros.hasta ? `${filtros.desde} → ${filtros.hasta}` : 'Todas las fechas'}</span>`,
    filtros.grupo ? `<span class="chip"><b>Cohorte:</b> ${String(filtros.grupo)}</span>` : '',
    cohortList.length ? `<span class="chip"><b>Cohortes:</b> ${cohortList.join(', ')}</span>` : ''
  ].filter(Boolean).join('');

  // Columnas (una línea, 4 máx)
  const cols = rows.map(r => {
    const p = pct(r.total);
    const label = r.tecnica;
    const dates = asArray(r.usageDates).map(d => `<li>${String(d)}</li>`).slice(0,6).join("");
    return `
      <div class="pv-col">
        <div class="val">${r.total} (${p.toFixed(1)}%)</div>
        <div class="bar"><span class="fill" style="height:${Math.max(0,Math.min(100,p))}%"></span></div>
        <div class="lbl">${label}</div>
        ${dates ? `<ul class="dates">${dates}</ul>` : ``}
      </div>`;
  }).join("");

  const html = `
    <style>
      .chips{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px}
      .chip{display:inline-flex;gap:6px;align-items:center;padding:6px 10px;border:1px solid #e5e7eb;border-radius:999px;background:#fff;font-size:12px}
      .pv-wrap{border:1px solid #e5e7eb;border-radius:18px;padding:16px;background:#fff}
      .pv-grid{white-space:nowrap;text-align:center;border-bottom:1px solid #e5e7eb;padding:8px 6px 12px 6px}
      .pv-col{display:inline-block;vertical-align:bottom;width:22%;margin:0 1.5%;}
      .bar{width:26px;height:140px;background:#ede9fe;border:1px solid #e5e7eb;border-radius:6px 6px 0 0;overflow:hidden;margin:0 auto;position:relative}
      .fill{display:block;width:100%;position:absolute;bottom:0;height:0;background:#7c3aed}
      .val{font-size:12px;color:#334155;font-weight:800;margin-bottom:6px}
      .lbl{font-size:13px;font-weight:800;color:#0f172a;margin-top:6px;line-height:1.15}
      .dates{list-style:none;padding:0;margin:6px 0 0 0;font-size:11px;color:#475569}
    </style>
    <div class="chips">${chips}</div>
    <div class="pv-wrap">
      <div class="pv-grid">
        ${cols || '<div class="text-muted">Sin datos en el rango seleccionado.</div>'}
      </div>
      <div style="margin-top:10px;text-align:center;font-size:18px;font-weight:800;color:#334155;">
        Total de usos: <b>${total}</b>
      </div>
    </div>`;

  return { html, hasData: rows.length > 0 };
}

/* -------------------- Export -------------------- */
export async function exportTopTecnicas(tipo = "pdf", filtros = {}) {
  const qs = buildQuery(filtros);
  const bust = `_=${Date.now()}`;
  const url = `${apiBase()}/reportes/export?tipo=${tipo}&reporte=${EXPORT_REPORTE}${qs ? `&${qs}` : ""}&${bust}`;
  return downloadBinary(url);
}

/* -------------------- Dialog -------------------- */
export async function openTopTecnicasDialog(tipo = "pdf") {
  ensureSwalStyles();

  const html = `
  <div class="container-fluid p-0">
    <div class="sw-head">
      <i class="${tipo==='excel'?'bi bi-filetype-xlsx':'bi bi-filetype-pdf'} text-primary"></i>
      <div>Selecciona un rango y (opcional) un cohorte</div>
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
            <div class="col-6"><label class="form-label mb-1">Desde</label><input id="sw-desde" type="date" class="form-control" /></div>
            <div class="col-6"><label class="form-label mb-1">Hasta</label><input id="sw-hasta" type="date" class="form-control" /></div>
          </div>
          <div class="mini-hint mt-2"><span class="chip"><b>Enter</b> para confirmar</span></div>
        </div>
      </div>

      <div class="col-12 col-md-6">
        <div class="sw-section h-100">
          <label class="form-label mb-2">Cohorte / Grupo</label>
          <select id="sw-group-mode" class="form-select mb-2">
            <option value="all" selected>Todos los cohortes</option>
            <option value="pick">Elegir específico…</option>
          </select>
          <div id="sw-group-picker" style="display:none">
            <input id="sw-filter-text-input" type="text" class="form-control" placeholder="Escribe para filtrar…" />
            <div class="sw-list mt-2" id="sw-cohorte-list" style="display:none;"></div>
          </div>
        </div>
      </div>
    </div>
  </div>`;

  const res = await Swal.fire({
    title: "Top técnicas",
    html,
    customClass: {
      container: "sw-report",
      popup: "sw-report",
      confirmButton: "btn btn-primary",
      cancelButton: "btn btn-light",
    },
    width: 980, 
    buttonsStyling: false,
    showCancelButton: true,
    confirmButtonText: "Generar",
    cancelButtonText: "Cancelar",
    focusConfirm: false,
    allowOutsideClick: true,
    allowEscapeKey: true,
    showCloseButton: true,
    closeButtonHtml: "&times;",
    heightAuto: false,

    didOpen: async () => {
      const $preset     = document.getElementById("sw-datepreset");
      const $custom     = document.getElementById("sw-customrange");
      const $mode       = document.getElementById("sw-group-mode");
      const $pickerWrap = document.getElementById("sw-group-picker");
      const $textInput  = document.getElementById("sw-filter-text-input");
      const $list       = document.getElementById("sw-cohorte-list");

      const toggleCustom = () => { $custom.style.display = $preset.value === "custom" ? "flex" : "none"; };
      const renderList = (items) => {
        if (!items?.length) { $list.innerHTML = `<div class="mini-hint">Sin coincidencias.</div>`; return; }
        $list.innerHTML = items.map(c =>
          `<button type="button" class="sw-item" data-value="${c}" title="${c}" aria-label="${c}">
            <span class="nm">${c}</span>
          </button>`).join("");
        Array.from($list.querySelectorAll(".sw-item")).forEach((el) => {
          el.addEventListener("click", () => {
            $textInput.value = el.getAttribute("data-value") || "";
            $list.querySelectorAll(".sw-item").forEach(n => n.classList.remove("active"));
            el.classList.add("active");
          });
        });
      };
      const ensureList = async () => {
        if ($mode.value !== "pick") { $list.style.display = "none"; return; }
        if (!cohortesCache.length) await loadCohortes();
        const term = ($textInput?.value || "").trim();
        const data = term ? filterCohortesLocal(term) : cohortesCache.slice(0, 30);
        $list.style.display = "block";
        renderList(data);
      };
      const toggleMode = async () => {
        const pick = $mode.value === "pick";
        $pickerWrap.style.display = pick ? "block" : "none";
        if (!pick) {
          if ($textInput) $textInput.value = "";
          if ($list) { $list.innerHTML = ""; $list.style.display = "none"; }
          return;
        }
        await ensureList();
      };

      $preset.addEventListener("change", toggleCustom);
      $mode.addEventListener("change", toggleMode);
      $textInput?.addEventListener("input", ensureList);

      toggleCustom();
      await toggleMode();
      Swal.getCloseButton()?.addEventListener("click", () => Swal.close());
      Swal.getCancelButton()?.addEventListener("click", () => Swal.close());
    },

    willClose: () => {
      const $preset    = document.getElementById("sw-datepreset");
      const $mode      = document.getElementById("sw-group-mode");
      const $textInput = document.getElementById("sw-filter-text-input");
      if ($preset)    $preset.replaceWith($preset.cloneNode(true));
      if ($mode)      $mode.replaceWith($mode.cloneNode(true));
      if ($textInput) $textInput.replaceWith($textInput.cloneNode(true));
    },

    preConfirm: () => {
      const $preset    = document.getElementById("sw-datepreset");
      const $d         = document.getElementById("sw-desde");
      const $h         = document.getElementById("sw-hasta");
      const $mode      = document.getElementById("sw-group-mode");
      const $textInput = document.getElementById("sw-filter-text-input");

      let { desde, hasta } = presetToRange($preset.value);
      if ($preset.value === "custom") {
        desde = ($d.value || "").trim();
        hasta = ($h.value || "").trim();
        if (!desde || !hasta) { Swal.showValidationMessage("Selecciona fecha <b>Desde</b> y <b>Hasta</b>."); return false; }
        if (desde > hasta) { Swal.showValidationMessage("<b>Desde</b> no puede ser mayor que <b>Hasta</b>."); return false; }
      }
      const filtros = { ...(desde ? { desde } : {}), ...(hasta ? { hasta } : {}) };
      if ($mode.value === "pick") {
        const grupo = ($textInput?.value || "").trim();
        if (grupo) filtros.grupo = grupo;
      }
      return filtros;
    }
  });

  if (!res.isConfirmed) return;

  // Previsualización
  try {
    const { html, hasData } = await previewTopChart(res.value || {});
    const confirm = await Swal.fire({
      title: "Previsualización",
      html,
      customClass: { container: "sw-report" },
      showCancelButton: true,
      confirmButtonText: "Descargar",
      cancelButtonText: "Cancelar",
      width: 780,
      allowOutsideClick: true,
      allowEscapeKey: true,
      showCloseButton: true,
      heightAuto: false,
    });
    if (!confirm.isConfirmed) return;
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

  // Export
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
    const { blob, filename } = await exportTopTecnicas(tipo, res.value || {});
    const a = document.createElement("a");
    a.href = URL.createObjectURL(blob);
    a.download = filename || `top-tecnicas_${new Date().toISOString().slice(0,19).replace(/[:T]/g,"-")}.${tipo==="excel"?"xlsx":"pdf"}`;
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
