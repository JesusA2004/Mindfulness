// src/composables/actividades.js
import axios from "axios";

/** ===========================
 *  CONFIG AXIOS
 *  =========================== */
const RAW_BASE = process.env.VUE_APP_API_BASE || "/api";
// Normaliza baseURL (quita doble slash y asegura 1 slash final)
const baseURL = (RAW_BASE || "")
  .replace(/\/+$/, ""); // sin trailing slash (lo agregamos en llamadas)

const api = axios.create({
  baseURL, // p.ej. http://localhost:8000/api
  headers: { "Content-Type": "application/json", Accept: "application/json" },
});

let baseURLLogged = false;
function logBaseOnce() {
  if (!baseURLLogged) {
    // Ayuda visual en consola para depurar 404
    console.info("[API] baseURL:", baseURL);
    baseURLLogged = true;
  }
}
logBaseOnce();

/** ===========================
 *  HELPERS
 *  =========================== */
/**
 * Intenta varias rutas hasta encontrar una que no retorne 404.
 * Útil cuando el backend quedó con 'actividads' en vez de 'actividades'.
 */
async function tryGet(paths = [], params = {}) {
  let lastErr;
  for (const p of paths) {
    try {
      const { data } = await api.get(p, { params });
      return data;
    } catch (e) {
      lastErr = e;
      if (e?.response?.status !== 404) throw e; // si no es 404, re-lanza
    }
  }
  throw lastErr; // todos fallaron
}

async function tryPost(paths = [], payload = {}) {
  let lastErr;
  for (const p of paths) {
    try {
      const { data } = await api.post(p, payload);
      return data;
    } catch (e) {
      lastErr = e;
      if (e?.response?.status !== 404) throw e;
    }
  }
  throw lastErr;
}

async function tryPut(paths = [], payload = {}) {
  let lastErr;
  for (const p of paths) {
    try {
      const { data } = await api.put(p, payload);
      return data;
    } catch (e) {
      lastErr = e;
      if (e?.response?.status !== 404) throw e;
    }
  }
  throw lastErr;
}

async function tryDelete(paths = []) {
  let lastErr;
  for (const p of paths) {
    try {
      const { data } = await api.delete(p);
      return data;
    } catch (e) {
      lastErr = e;
      if (e?.response?.status !== 404) throw e;
    }
  }
  throw lastErr;
}

/** ===========================
 *  AUTH/USER
 *  =========================== */
export async function getCurrentUser() {
  const endpoints = ["/auth/me", "/users/me"];
  for (const ep of endpoints) {
    try {
      const { data } = await api.get(ep);
      return data?.user || data || null;
    } catch {
      /* sigue intentando */
    }
  }
  // fallback opcional: si guardas algo en localStorage, podrías leerlo aquí.
  return null;
}

/** ===========================
 *  ACTIVIDADES (con fallbacks)
 *  =========================== */
// Conjunto de posibles rutas según cómo esté tu backend
const ACT_LIST = ["/actividades", "/actividads", "/actividad"];
const ACT_ONE  = (id) => [`/actividades/${id}`, `/actividads/${id}`, `/actividad/${id}`];

export async function fetchActividades(params = {}) {
  // Nota: api.get usa baseURL ya normalizada; agregamos el path sin doble slash
  return await tryGet(ACT_LIST, params);
}

export async function createActividad(payload) {
  return await tryPost(ACT_LIST, payload);
}

export async function updateActividad(id, payload) {
  return await tryPut(ACT_ONE(id), payload);
}

export async function deleteActividad(id) {
  return await tryDelete(ACT_ONE(id));
}

/** ===========================
 *  TÉCNICAS / USUARIOS (alumnos)
 *  =========================== */
export async function fetchTecnicas(params = {}) {
  try {
    const { data } = await api.get("/tecnicas", { params });
    const registros = data?.registros || data?.data || data || [];
    return Array.isArray(registros) ? registros : [];
  } catch (error) {
    console.error("Error al cargar técnicas:", error);
    return [];
  }
}

export async function fetchAlumnos(params = {}) {
  try {
    const query = { ...params, rol: "estudiante" };
    const { data } = await api.get("/users", { params: query });
    const registros = data?.data || data?.registros || [];
    return Array.isArray(registros) ? registros : [];
  } catch (error) {
    console.error("Error al cargar alumnos:", error);
    return [];
  }
}

export async function fetchCohortesAlumnos() {
  const alumnos = await fetchAlumnos();
  const set = new Set();
  for (const u of alumnos) {
    const c = u?.persona?.cohorte;
    if (Array.isArray(c)) c.forEach((v) => v && set.add(String(v)));
    else if (c) set.add(String(c));
  }
  return Array.from(set).sort();
}

/** ===========================
 *  PAGINACIÓN
 *  =========================== */
/**
 * Extrae ?page=## de una URL tipo Laravel (links de paginación).
 * Devuelve { page: 2 } o {} si no encuentra.
 */
export function paramsFromPaginationUrl(url) {
  if (!url) return {};
  try {
    const u = new URL(url, window.location.origin);
    const page = u.searchParams.get("page");
    return page ? { page: Number(page) } : {};
  } catch {
    // Si el backend devuelve URL absoluta de otro dominio, igual se parsea bien con base.
    return {};
  }
}

export default {
  getCurrentUser,
  fetchActividades,
  createActividad,
  updateActividad,
  deleteActividad,
  fetchTecnicas,
  fetchAlumnos,
  fetchCohortesAlumnos,
  paramsFromPaginationUrl,
};
