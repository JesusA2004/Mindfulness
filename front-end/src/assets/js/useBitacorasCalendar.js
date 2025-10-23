// src/assets/js/useBitacorasCalendar.js
import { ref, reactive } from 'vue';
import axios from 'axios';
import { authHeaders, makeDebouncer, toast as baseToast, getId } from '@/assets/js/crudUtils';

/* =========================
   BASES de API
========================= */
const API_ROOT     = (process.env.VUE_APP_API_URL || '').replace(/\/+$/, '');
const API_BASE     = `${API_ROOT}/bitacoras`;
const USERS_BASE   = `${API_ROOT}/users`;
const AUTH_PROFILE = `${API_ROOT}/auth/user-profile`;

// Puntos
const USERS_POINTS = (id) => `${USERS_BASE}/${id}/points`;
const USERS_EARN   = (id) => `${USERS_BASE}/${id}/points/earn`;   // { puntos }
const USERS_REDEEM = (id) => `${USERS_BASE}/${id}/points/redeem`; // { puntos }

function mergedAuthHeaders () {
  const h = (typeof authHeaders === 'function' ? authHeaders() : {}) || {};
  const tokenType   = localStorage.getItem('token_type') || 'Bearer';
  const accessToken = localStorage.getItem('token');
  if (accessToken && !h.Authorization) h.Authorization = `${tokenType} ${accessToken}`;
  return h;
}

export function useBitacorasCalendar({ EMOJIS = [] } = {}) {
  const items  = ref([]);
  const month  = ref(null);
  const year   = ref(null);
  const isLoading = ref(true);

  // Usuario y puntos
  const currentUserId = ref(null);
  const puntos = ref(0);
  const puntosCargados = ref(false);

  // Búsqueda
  const searchQuery = ref('');
  const debounce = makeDebouncer(120);
  const onInstantSearch = () => debounce(() => {
    const api = window.__bitacoraCalendarApi?.();
    if (!api) return;
    if (api.batchRendering) {
      api.batchRendering(() => { api.refetchEvents?.(); api.rerenderDates?.(); });
    } else {
      api.refetchEvents?.(); api.rerenderDates?.();
    }
  });
  const clearSearch = () => {
    searchQuery.value = '';
    const api = window.__bitacoraCalendarApi?.();
    api?.refetchEvents?.(); api?.rerenderDates?.();
  };

  const isEditing = ref(false);
  const saving    = ref(false);
  const selected  = ref(null);

  const form = reactive({
    _id: null,
    titulo: '',
    descripcion: '',
    fecha: '',
    emoji: ''
  });

  /* Utils */
  function emojiFromTitle(t) {
    if (!t) return '';
    const m = String(t).trim().match(/^([\u{1F600}-\u{1FAFF}\u{1F300}-\u{1F64F}\u{1F680}-\u{1F6FF}])\s+/u);
    return m ? m[1] : '';
  }
  function titleWithoutEmoji(t) {
    if (!t) return '';
    const e = emojiFromTitle(t);
    return e ? String(t).trim().slice(e.length).trim() : String(t).trim();
  }
  function toast(msg, type = 'success') { baseToast(msg, type); }

  function formatMonthLabel(d = new Date()) {
    const intl = new Intl.DateTimeFormat('es-MX', { month: 'long', year: 'numeric' });
    const s = intl.format(d); return s.charAt(0).toUpperCase()+s.slice(1);
  }

  function normalize(raw) {
    const id = getId(raw);
    const titulo = raw?.titulo ?? '';
    return {
      id, _id: id,
      titulo,
      descripcion: raw?.descripcion ?? '',
      fecha: raw?.fecha ?? '',
      emoji: emojiFromTitle(titulo),
      alumno_id: raw?.alumno_id
    };
  }

  function todayISO() {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
  }
  function isToday(iso) { return String(iso) === todayISO(); }
  function hasEntry(iso) { return items.value.some(b => b.fecha === iso); }

  // Puntos por día
  function pointsForDate(isoDate) {
    const day = Number(isoDate?.slice(8,10) || '0');
    if (day >= 1 && day <= 10) return 2;
    if (day >= 11 && day <= 20) return 1;
    return 3;
  }

  /* Usuario + Puntos */
  function getUserIdFromStorage() {
    try { const u = JSON.parse(localStorage.getItem('user')||'{}'); return u?._id || u?.id || null; }
    catch { return null; }
  }
  async function loadUserAndPoints() {
    try {
      let uid = getUserIdFromStorage();
      if (!uid) {
        const { data: profile } = await axios.get(AUTH_PROFILE, { headers: mergedAuthHeaders() });
        uid = profile?.user?._id || profile?.user?.id || null;
      }
      currentUserId.value = uid || null;
      if (!uid) { puntos.value = 0; puntosCargados.value = true; return; }

      const { data } = await axios.get(USERS_POINTS(uid), { headers: mergedAuthHeaders() });
      puntos.value = Number(data?.puntosCanjeo ?? 0);
      puntosCargados.value = true;
    } catch (e) {
      console.error('loadUserAndPoints', e?.response?.data || e);
      puntos.value = 0; puntosCargados.value = true;
    }
  }
  async function refreshPoints() {
    if (!currentUserId.value) return;
    try {
      const { data } = await axios.get(USERS_POINTS(currentUserId.value), { headers: mergedAuthHeaders() });
      puntos.value = Number(data?.puntosCanjeo ?? 0);
    } catch (e) { console.error('refreshPoints', e?.response?.data || e); }
  }
  async function earnPoints(amount) {
    if (!currentUserId.value) return;
    try { await axios.post(USERS_EARN(currentUserId.value), { puntos: amount }, { headers: mergedAuthHeaders() }); await refreshPoints(); }
    catch (e) { console.error('earnPoints', e?.response?.data || e); toast('No fue posible abonar puntos.', 'error'); }
  }
  async function redeemPoints(amount) {
    if (!currentUserId.value || !amount) return;
    try { await axios.post(USERS_REDEEM(currentUserId.value), { puntos: amount }, { headers: mergedAuthHeaders() }); await refreshPoints(); }
    catch (e) { console.error('redeemPoints', e?.response?.data || e); toast('No fue posible actualizar tus puntos.', 'error'); }
  }

  /* Fetch */
  async function fetchMonth(m, y) {
    try {
      isLoading.value = true; month.value = m; year.value = y;
      const { data } = await axios.get(API_BASE, { params:{ mes:m, anio:y }, headers: mergedAuthHeaders() });
      items.value = (Array.isArray(data?.bitacoras) ? data.bitacoras : []).map(normalize);
    } catch (e) {
      console.error('fetchMonth', e?.response?.data || e);
      toast('No fue posible cargar las bitácoras de este mes.', 'error');
    } finally { isLoading.value = false; }
  }

  /* Guardar */
  async function onSubmit() {
    if (!form.titulo?.trim()) { toast('El título es obligatorio.', 'error'); return; }
    if (!form.fecha) { toast('La fecha es inválida.', 'error'); return; }
    if (!isToday(form.fecha)) { toast('Sólo puedes registrar/editar la bitácora del día de hoy.', 'warning'); return; }

    const existedBefore = hasEntry(form.fecha);
    if (!isEditing.value && existedBefore) {
      toast('Sólo puedes tener 1 bitácora por día. Edita la existente.', 'warning');
      return;
    }

    const body = {
      titulo: form.titulo.trim(),
      descripcion: form.descripcion || '',
      fecha: form.fecha
    };

    try {
      isEditing.value ? await updateBitacora(body) : await createBitacora(body, existedBefore);
      const api = window.__bitacoraCalendarApi?.();
      api?.refetchEvents?.(); api?.render?.();
      if (month.value && year.value) await fetchMonth(month.value, year.value);
    } catch (e) {
      console.error('onSubmit', e?.response?.data || e);
      toast(e?.response?.data?.message || 'Ocurrió un error al guardar.', 'error');
    } finally { saving.value = false; }
  }

  async function updateBitacora(body){
    saving.value = true;
    const { data } = await axios.put(`${API_BASE}/${form._id}`, body, { headers: mergedAuthHeaders() });
    const saved = normalize(data?.bitacora?.data ?? data?.bitacora ?? data);
    upsertLocal(saved);
    toast('Bitácora actualizada.');
  }

  async function createBitacora(body, existedBefore){
    saving.value = true;
    const { data } = await axios.post(API_BASE, body, { headers: mergedAuthHeaders() });
    const saved = normalize(data?.bitacora?.data ?? data?.bitacora ?? data);
    upsertLocal(saved);
    toast('Bitácora guardada.');
    const mustAward = data?.awarded === true || existedBefore === false;
    if (mustAward) {
      const pts = pointsForDate(saved.fecha);
      await earnPoints(pts);
      toast(`🎉 ¡Ganaste ${pts} punto(s)!`, 'success');
    }
  }

  function upsertLocal(saved) {
    const id = saved?._id || saved?.id;
    if (!id) return;
    const idx = items.value.findIndex(x => (x._id || x.id) === id);
    if (idx >= 0) items.value.splice(idx, 1, { ...items.value[idx], ...saved });
    else items.value.push(saved);
  }

  /* Eliminar */
  async function confirmDelete(item) {
    if (!item) return;
    if (!isToday(item.fecha)) { toast('Sólo puedes eliminar la bitácora del día de hoy.', 'warning'); return; }
    if (puntos.value <= 0) { toast('No puedes eliminar con 0 puntos.', 'error'); return; }

    const { default: Swal } = await import('sweetalert2');
    await import('sweetalert2/dist/sweetalert2.min.css');

    const res = await Swal.mixin({
      allowOutsideClick: false,
      buttonsStyling: false,
      heightAuto: false,
      customClass: {
        popup: 'swal2-responsive swal2-mindora rounded-4',
        actions: 'swal2-actions-spaced',
        confirmButton: 'btn btn-danger',     // rojo
        cancelButton: 'btn btn-secondary'    // gris
      },
      backdrop: 'rgba(2,6,12,.35)',
      // Asegurar blur
      didOpen: () => document.body.classList.add('mindora-blur-open'),
      willClose: () => document.body.classList.remove('mindora-blur-open')
    }).fire({
      title: '¿Eliminar bitácora?',
      text: 'Se restarán los puntos asignados a esta bitácora. Esta acción no se puede deshacer.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
      reverseButtons: true
    });

    if (!res.isConfirmed) return;

    try {
      const id = getId(item);
      await axios.delete(`${API_BASE}/${id}`, { headers: mergedAuthHeaders() });
      items.value = items.value.filter(x => getId(x) !== id);

      const pts = pointsForDate(item.fecha);
      puntos.value = Math.max(0, Number(puntos.value) - pts);
      await redeemPoints(pts);

      toast(`Eliminado. Se descontaron ${pts} punto(s).`, 'success');

      const api = window.__bitacoraCalendarApi?.();
      api?.refetchEvents?.(); api?.render?.();
      if (month.value && year.value) await fetchMonth(month.value, year.value);
    } catch (e) {
      console.error('confirmDelete', e?.response?.data || e);
      toast('No fue posible eliminar.', 'error');
      await refreshPoints();
    }
  }

  return {
    items, isLoading, month, year,
    currentUserId, puntos, puntosCargados, loadUserAndPoints,
    refreshPoints, earnPoints, redeemPoints,
    searchQuery, onInstantSearch, clearSearch,
    onSubmit, confirmDelete,
    form, isEditing, saving, selected,
    fetchMonth,
    titleWithoutEmoji, emojiFromTitle, toast, formatMonthLabel,
    todayISO, isToday, pointsForDate
  };
}
