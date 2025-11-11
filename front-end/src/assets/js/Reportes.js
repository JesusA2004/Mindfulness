// src/assets/js/Reportes.js

import reportes from "./reportes/index";

/** Back-compat: builder genérico como en la versión original */
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

// Export por defecto con las funciones públicas + buildQuery para compatibilidad
export default {
  ...reportes, // { apiBase, authHeaders, openExportDialog, getReport, exportReport }
  buildQuery,
};

// Named exports por conveniencia/opcional
export const { apiBase, authHeaders, openExportDialog, getReport, exportReport } = reportes;
export { buildQuery };
