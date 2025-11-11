// src/assets/js/reportes/actividadesAlumno.js
import { Swal, ensureSwalStyles, presetToRange, debounce } from "./common/utils";
import { apiBase, fetchJSON, downloadBinary } from "./common/apiClient";
import { loadAllAlumnos, filterAlumnosLocal, alumnosCache } from "./common/alumnos";

const ENDPOINT = "reportes/actividades-por-alumno";
const EXPORT_REPORTE = "actividades-por-alumno";

function buildQuery(f = {}) {
  const p = new URLSearchParams();
  if (f.desde) p.set("desde", f.desde);
  if (f.hasta) p.set("hasta", f.hasta);
  if (f.alumno) p.set("alumno", f.alumno);
  return p.toString();
}

export async function getActividadesAlumno(f = {}) {
  return fetchJSON(ENDPOINT, buildQuery(f));
}

export async function exportActividadesAlumno(tipo = "pdf", f = {}) {
  const qs = buildQuery(f);
  const url = `${apiBase()}/reportes/export?tipo=${tipo}&reporte=${EXPORT_REPORTE}${qs ? `&${qs}` : ""}&_=${Date.now()}`;
  return downloadBinary(url);
}

export async function openActividadesAlumnoDialog(tipo = "pdf") {
  ensureSwalStyles();

  const html = `
  <div class="container-fluid p-0">
    <div class="sw-head"><i class="${tipo==='excel'?'bi bi-filetype-xlsx':'bi bi-filetype-pdf'} text-primary"></i>
      <div>Selecciona un rango y (opcional) un alumno</div></div>
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
          <label class="form-label mb-2">Alumno (nombre / matrícula)</label>
          <input id="sw-alumno-input" class="form-control mb-2" type="text" placeholder="Buscar… (vacío = todos)">
          <div class="sw-list" id="sw-alumno-list" role="listbox" aria-label="Alumnos"></div>
          <div class="mini-hint mt-2">Vacío = <b>todos</b> los estudiantes.</div>
        </div>
      </div>
    </div>
  </div>`;

  const res = await Swal.fire({
    title: "Actividades por alumno",
    html,
    customClass: { container: "sw-report", popup: "sw-report", confirmButton: "btn btn-primary", cancelButton: "btn btn-light" },
    buttonsStyling: false,
    showCancelButton: true,
    confirmButtonText: "Descargar",
    cancelButtonText: "Cancelar",
    focusConfirm: false,
    didOpen: async () => {
      const $preset = document.getElementById("sw-datepreset");
      const $custom = document.getElementById("sw-customrange");
      const $ainput = document.getElementById("sw-alumno-input");
      const $alist  = document.getElementById("sw-alumno-list");

      const toggleCustom = () => { $custom.style.display = $preset.value === "custom" ? "flex" : "none"; };

      const renderAlumnos = (list=[]) => {
        if (!list.length) { $alist.innerHTML = `<div class="mini-hint">Escribe y presiona <b>Enter</b> (sin autocompletar).</div>`; return; }
        $alist.innerHTML = list.map((o,i) =>
          `<button type="button" class="sw-item" data-label="${o.label}" data-idx="${i}">
            <span class="nm">${o.nombre}</span><span class="mt">${o.matricula}</span>
          </button>`
        ).join("");
        Array.from($alist.querySelectorAll(".sw-item")).forEach((el) => {
          el.addEventListener("click", () => {
            $alist.querySelectorAll(".sw-item").forEach(n=>n.classList.remove("active"));
            el.classList.add("active");
          });
        });
      };

      await loadAllAlumnos();
      renderAlumnos(alumnosCache);

      const doFilterLocal = debounce((t) => renderAlumnos(filterAlumnosLocal((t||"").trim())), 140);
      $ainput.addEventListener("input", (e) => doFilterLocal(e.target.value || ""));

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
        if (!desde || !hasta) { Swal.showValidationMessage("Selecciona fecha <b>Desde</b> y <b>Hasta</b>."); return false; }
      }
      const filtros = { ...(desde ? { desde } : {}), ...(hasta ? { hasta } : {}) };
      const chosen = $alist.querySelector(".sw-item.active")?.getAttribute("data-label") || "";
      const typed  = ($ainput?.value || "").trim();
      const val = (chosen || typed).trim();
      if (val) filtros.alumno = val;
      return filtros;
    }
  });

  if (!res.isConfirmed) return;

  Swal.fire({ title: "Generando archivo…", html: "Preparando la descarga", didOpen: () => Swal.showLoading(), allowOutsideClick: false, allowEscapeKey: false, showConfirmButton: false, customClass: { container: "sw-report" } });

  try {
    const { blob, filename } = await exportActividadesAlumno(tipo, res.value || {});
    const a = document.createElement("a");
    a.href = URL.createObjectURL(blob);
    a.download = filename || `actividades_${new Date().toISOString().slice(0,19).replace(/[:T]/g,"-")}.${tipo==="excel"?"xlsx":"pdf"}`;
    document.body.appendChild(a); a.click(); a.remove();
    Swal.close();
    Swal.fire({ icon:"success", title:"Descarga lista", timer:1200, showConfirmButton:false, customClass:{container:"sw-report"} });
  } catch (e) {
    Swal.close();
    Swal.fire({ icon:"error", title:"No se pudo exportar", text:e.message || "Error desconocido", customClass:{container:"sw-report"} });
  }
}
