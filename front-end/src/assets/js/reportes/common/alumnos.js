// src/assets/js/reportes/common/alumnos.js
import { apiBase, authHeaders } from "../common/apiClient";

let alumnosCache = [];

/**
 * Normaliza un alumno a la forma:
 *  label:  "Nombre Apellido — MATRICULA"
 *  nombre: "Nombre Apellido"
 *  matricula: "MATRICULA"
 */
export function normalizeAlumno(u = {}) {
  // Si ya viene un label armado, lo usamos
  if (u.label) {
    const clean = u.label.replace(/\s+/g, " ").trim();
    const parts = clean.split(" — ");
    return {
      label: clean,
      nombre: parts[0] || clean,
      matricula: (u.matricula || parts[1] || "S/MAT").toUpperCase(),
    };
  }

  const nombre = [
    (u.nombre || "").trim(),
    (u.apellidoPaterno || "").trim(),
    (u.apellidoMaterno || "").trim(),
  ]
    .filter(Boolean)
    .join(" ");

  const mat = (u.matricula || "S/MAT").toString().toUpperCase();

  const label = `${nombre} — ${mat}`.replace(/\s+/g, " ").trim();

  return {
    label,
    nombre,
    matricula: mat,
  };
}

/**
 * Carga TODOS los alumnos que aparecen en el reporte
 * "actividades-por-alumno" (sin filtros) y los deja en cache.
 *
 * Esto garantiza que el buscador tenga al menos los alumnos que
 * tienen actividades registradas, independientemente del rol (admin/prof).
 */
export async function loadAllAlumnos() {
  // Si ya los tenemos cargados, reutiliza cache
  if (Array.isArray(alumnosCache) && alumnosCache.length > 0) {
    return alumnosCache;
  }

  const url = `${apiBase()}/reportes/actividades-por-alumno`;
  const res = await fetch(url, { headers: authHeaders() });

  if (!res.ok) {
    console.error("[alumnos] Error al cargar actividades-por-alumno:", res.status);
    alumnosCache = [];
    return alumnosCache;
  }

  const json = await res.json();
  const rows = Array.isArray(json.rows) ? json.rows : [];

  alumnosCache = rows
    .map(r => {
      const nombre = (r.alumno || "").toString();
      const mat    = (r.matricula || "S/MAT").toString().toUpperCase();
      return normalizeAlumno({
        label: `${nombre} — ${mat}`,
        matricula: mat,
      });
    })
    // quitar duplicados por matrícula + nombre
    .filter((a, idx, arr) => {
      const key = `${a.matricula}::${a.nombre}`;
      return idx === arr.findIndex(x => `${x.matricula}::${x.nombre}` === key);
    })
    // ordenar alfabéticamente
    .sort((a, b) => a.nombre.localeCompare(b.nombre, "es"));

  return alumnosCache;
}

/**
 * Filtro local por nombre o matrícula
 */
export function filterAlumnosLocal(term = "") {
  if (!term) return alumnosCache;
  const t = term.toLowerCase();
  return alumnosCache.filter(
    a =>
      a.nombre.toLowerCase().includes(t) ||
      a.matricula.toLowerCase().includes(t)
  );
}

export { alumnosCache };
