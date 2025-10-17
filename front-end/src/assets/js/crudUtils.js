// src/assets/js/crudUtils.js
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import Tooltip from 'bootstrap/js/dist/tooltip';
import axios from 'axios';

/* =========================
   Base / API
   ========================= */
export function apiBase(path = '') {
  const base = process.env.VUE_APP_API_URL;
  return `${base}${path ? (path.startsWith('/') ? '' : '/') + path : ''}`;
}

export function authHeaders() {
  const token = localStorage.getItem('token');
  return token ? { Authorization: `Bearer ${token}` } : {};
}

/* =========================
   IDs y Keys
   ========================= */
export function normalizeId(raw) {
  if (!raw) return null;
  if (typeof raw === 'string' || typeof raw === 'number') return String(raw);
  if (raw?.$oid) return String(raw.$oid);
  try { return String(raw); } catch { return null; }
}

export function getId(obj) {
  const id =
    normalizeId(obj?.id) ??
    normalizeId(obj?._id) ??
    normalizeId(obj?.uuid);
  return id || null;
}

export function randomKey(prefix = 'k') {
  return (crypto?.randomUUID?.() || (prefix + Date.now().toString(36) + Math.random().toString(36).slice(2, 7)));
}

/** Genera ids secuenciales únicos con prefijo configurable (p.ej. 'q1','q2') */
export function sequentialId(idx, arr, prefix = 'q') {
  let n = (idx ?? arr?.length ?? 0) + 1;
  const taken = new Set((arr || []).map(x => String(x?._id || '')));
  let candidate = `${prefix}${n}`;
  while (taken.has(candidate)) { n += 1; candidate = `${prefix}${n}`; }
  return candidate;
}

/* =========================
   Fechas
   ========================= */
export function toDateInputValue(v) {
  if (!v) return '';
  try {
    const d = new Date(v);
    if (Number.isNaN(d.getTime())) return ('' + v).slice(0, 10);
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
  } catch { return ('' + v).slice(0, 10); }
}

export function formatDate(v) {
  if (!v) return '';
  try {
    const d = new Date(v);
    if (Number.isNaN(d.getTime())) return ('' + v).slice(0, 10);
    return d.toLocaleDateString();
  } catch { return ('' + v).slice(0, 10); }
}

export function formatDateRange(inicio, fin) {
  const a = formatDate(inicio);
  const b = formatDate(fin);
  if (!a && !b) return '—';
  if (a && !b)   return a;
  if (!a && b)   return b;
  return `${a} — ${b}`;
}

/* =========================
   UX helpers
   ========================= */
export function toast(message, type = 'success') {
  Swal.fire({
    toast: true,
    position: 'top-end',
    icon: type,
    title: message,
    showConfirmButton: false,
    timer: 2000,
    timerProgressBar: true
  });
}

/** Inicializa tooltips de Bootstrap 5 en elementos con data-bs-toggle="tooltip" */
export function setupBsTooltips(root = document) {
  root.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
    // eslint-disable-next-line no-new
    new Tooltip(el);
  });
}

/* =========================
   Debounce / Búsqueda
   ========================= */
export function makeDebouncer(delay = 120) {
  let timer = null;
  return (fn) => {
    clearTimeout(timer);
    timer = setTimeout(() => { try { fn?.(); } catch {} }, delay);
  };
}

/* =========================
   Validaciones reutilizables
   ========================= */
export const isRequired = (v) => typeof v === 'string' ? v.trim().length > 0 : v !== null && v !== undefined;

export const isPositiveInt = (n) => Number.isInteger(n) && n > 0;

/** true si fin >= inicio o si falta alguno (validación suave en front) */
export function validateDateOrder(start, end) {
  if (!start || !end) return true;
  const a = new Date(start), b = new Date(end);
  if (Number.isNaN(a.getTime()) || Number.isNaN(b.getTime())) return true;
  return b.getTime() >= a.getTime();
}

/** Valida mínimo de opciones no vacías */
export function validateOptions(arr, min = 2) {
  if (!Array.isArray(arr)) return false;
  const clean = arr.map(o => (o || '').trim()).filter(Boolean);
  return clean.length >= min;
}

/* =========================
   Fetch paginado genérico
   ========================= */
/**
 * Realiza GET paginado y devuelve { list, hasMore, raw }
 * - Espera APIs que envían { data, next_page_url } o array plano
 */
export async function fetchPaginated(url, { page = 1, perPage = 20, headers = {} } = {}) {
  const params = { page, per_page: perPage };
  const { data } = await axios.get(url, { params, headers });
  const list = data?.data ?? data?.registros ?? data ?? [];
  const hasMore = !!data?.next_page_url || (Array.isArray(list) && list.length === perPage);
  return { list: Array.isArray(list) ? list : [], hasMore, raw: data };
}

/* =========================
   Cuestionario normalizer helper
   ========================= */
/**
 * Normaliza un cuestionario genérico:
 * - asegura _id
 * - asegura opciones como array
 * - añade __key para v-for
 * - permite forzar tipo si lo necesitas (forceType='seleccion_multiple' etc.)
 * - prefix para ids secuenciales (default 'q')
 */
export function normalizeCuestionario(arr, { forceType = null, idPrefix = 'q' } = {}) {
  const src = Array.isArray(arr) ? arr : [];
  return src.map((q, i) => {
    const qid = q?._id || sequentialId(i, src, idPrefix);
    const tipo = forceType ? forceType : (q?.tipo ?? '');
    return {
      _id: qid,
      pregunta: q?.pregunta ?? '',
      tipo,
      opciones: Array.isArray(q?.opciones) ? q.opciones : [],
      __key: randomKey()
    };
  });
}
