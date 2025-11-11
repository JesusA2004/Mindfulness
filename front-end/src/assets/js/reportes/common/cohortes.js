// src/assets/js/reportes/common/cohortes.js
import { apiBase, authHeaders } from "./apiClient";

let cohortesCache = [];

export async function loadCohortes(force = false) {
  if (cohortesCache.length && !force) return cohortesCache;
  const res = await fetch(`${apiBase()}/reportes/opciones/cohortes`, {
    headers: authHeaders({ "Cache-Control": "no-cache" }),
  });
  if (!res.ok) throw new Error("No se pudo cargar la lista de cohortes");
  const json = await res.json();
  cohortesCache = Array.isArray(json.items) ? json.items : [];
  return cohortesCache;
}

export function filterCohortesLocal(term = "") {
  const t = (term || "").toLowerCase();
  return cohortesCache.filter((c) => c.toLowerCase().includes(t)).slice(0, 30);
}

export { cohortesCache };
