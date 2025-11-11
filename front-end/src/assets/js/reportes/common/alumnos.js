// src/assets/js/reportes/common/alumnos.js
let alumnosCache = []; // placeholder: hoy vacío, luego puedes poblarlo con endpoint

export function normalizeAlumno(u) {
  const label = (u.label ||
    `${(u.nombre||"").trim()} ${(u.apellidoPaterno||"").trim()} ${(u.apellidoMaterno||"").trim()} — ${(u.matricula||"S/MAT").toUpperCase()}`
  ).replace(/\s+/g, " ").trim();
  return {
    label,
    nombre: label.split(" — ")[0],
    matricula: (u.matricula || (label.split(" — ")[1] || "S/MAT")).toUpperCase(),
  };
}

export async function loadAllAlumnos() {
  alumnosCache = []; // sin autocompletar remoto por ahora
  return alumnosCache;
}

export function filterAlumnosLocal(term = "") {
  if (!term) return alumnosCache;
  const t = term.toLowerCase();
  return alumnosCache.filter(a => a.nombre.toLowerCase().includes(t) || a.matricula.toLowerCase().includes(t));
}

export { alumnosCache };
