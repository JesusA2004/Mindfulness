// src/composables/actividades.js
import axios from "axios";

/** ========= Base URL priorizando VUE_APP_API_URL ========= */
const RAW_API_URL =
  process.env.VUE_APP_API_URL ||
  (process.env.VUE_APP_API_BASE
    ? String(process.env.VUE_APP_API_BASE).replace(/\/+$/, "") + "/api"
    : "/api");

// Normaliza: sin slash final
const baseURL = String(RAW_API_URL).replace(/\/+$/, "");

/** ========= Token ========= */
function readToken() {
  const keys = ["token", "auth_token", "jwt", "access_token"];
  for (const k of keys) {
    const v = localStorage.getItem(k);
    if (v) return v.replace(/^"|"$/g, "");
  }
  return null;
}

function authHeaders() {
  const t = readToken();
  return t ? { Authorization: `Bearer ${t}` } : {};
}

/** ========= Axios ========= */
const api = axios.create({
  baseURL,
  headers: { "Content-Type": "application/json", Accept: "application/json" },
});

api.interceptors.request.use((config) => {
  config.headers = { ...(config.headers || {}), ...authHeaders() };
  if (!window.__API_BASE_LOGGED__) {
    console.info("[API] baseURL =", baseURL);
    window.__API_BASE_LOGGED__ = true;
  }
  const full = `${config.baseURL?.replace(/\/+$/, "") || ""}/${String(
    config.url
  ).replace(/^\/+/, "")}`;
  console.info("[API REQ]", config.method?.toUpperCase(), full, {
    params: config.params,
    body: config.data,
  });
  return config;
});

/** ========= AUTH / USER ========= */
export async function getCurrentUser() {
  try {
    const { data } = await api.get("/auth/user-profile");
    return data?.user || data || null;
  } catch (e) {
    console.warn("No se pudo obtener el usuario actual:", e?.response?.status || e?.message);
    return null;
  }
}

/** ========= ACTIVIDADES ========= */
const ACT_LIST = "/actividades";
const ACT_ONE = (id) => `/actividades/${id}`;

export async function fetchActividades(params = {}) {
  const { data } = await api.get(ACT_LIST, { params });
  return data; // { registros, enlaces }
}

export async function createActividad(payload) {
  const { data } = await api.post(ACT_LIST, payload);
  return data; // { mensaje, actividad }
}

export async function updateActividad(id, payload) {
  const { data } = await api.put(ACT_ONE(id), payload);
  return data;
}

export async function deleteActividad(id) {
  const { data } = await api.delete(ACT_ONE(id));
  return data;
}

/** ========= TÉCNICAS / ALUMNOS / DOCENTES ========= */
export async function fetchTecnicas(params = {}) {
  try {
    const { data } = await api.get("/tecnicas", { params });
    const registros = data?.registros || data?.data || data || [];
    return Array.isArray(registros) ? registros : [];
  } catch (e) {
    console.error("Error al cargar técnicas:", e?.response?.status || e?.message);
    return [];
  }
}

export async function fetchAlumnos(params = {}) {
  try {
    const query = { ...params, rol: "estudiante" };
    const { data } = await api.get("/users", { params: query });
    const registros = data?.data || data?.registros || [];
    return Array.isArray(registros) ? registros : [];
  } catch (e) {
    console.error("Error al cargar alumnos:", e?.response?.status || e?.message);
    return [];
  }
}

export async function fetchDocentes(params = {}) {
  try {
    const query = { ...params, rol: "profesor" };
    const { data } = await api.get("/users", { params: query });
    const registros = data?.data || data?.registros || [];
    return Array.isArray(registros) ? registros : [];
  } catch (e) {
    console.error("Error al cargar docentes:", e?.response?.status || e?.message);
    return [];
  }
}

/** ========= Paginación helper ========= */
export function paramsFromPaginationUrl(url) {
  if (!url) return {};
  try {
    const u = new URL(url, window.location.origin);
    const page = u.searchParams.get("page");
    return page ? { page: Number(page) } : {};
  } catch {
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
  fetchDocentes,
  paramsFromPaginationUrl,
};
