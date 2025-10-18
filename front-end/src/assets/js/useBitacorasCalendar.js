import { ref, reactive } from 'vue';
import axios from 'axios';
import Modal from 'bootstrap/js/dist/modal';
import { apiBase, authHeaders, makeDebouncer, toast as baseToast, getId } from '@/assets/js/crudUtils';

const API_BASE = process.env.VUE_APP_API_URL + '/bitacoras';
const AUTH_PROFILE = apiBase('/auth/user-profile');
const USERS_POINTS = (id) => API_BASE + `/users/${id}/points`;
const USERS_EARN   = (id) => API_BASE + `/users/${id}/points/earn`;

export function useBitacorasCalendar({ EMOJIS = [] } = {}) {
  const items = ref([]);
  const isLoading = ref(true);
  const month = ref(null);
  const year  = ref(null);

  // Usuario y puntos
  const currentUserId = ref(null);
  const puntos = ref(0);
  const puntosCargados = ref(false);

  // Búsqueda
  const searchQuery = ref('');
  const debounce = makeDebouncer(120);
  const onInstantSearch = () => debounce(() => {});
  const clearSearch = () => (searchQuery.value = '');

  // Modales / selección
  const formModalRef = ref(null);
  const viewModalRef = ref(null);
  let formModal, viewModal;

  const isEditing = ref(false);
  const saving = ref(false);
  const selected = ref(null);

  const form = reactive({
    _id: null,
    titulo: '',
    descripcion: '',
    fecha: '',
    emoji: ''
  });

  // ===== Utils
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
  function withEmojiPrefix(e, t) {
    const cleanTitle = titleWithoutEmoji(t || '');
    return e ? `${e} ${cleanTitle}` : cleanTitle;
  }
  function toast(msg, type = 'success') { baseToast(msg, type); }

  function formatMonthLabel(d = new Date()) {
    const intl = new Intl.DateTimeFormat('es-MX', { month: 'long', year: 'numeric' });
    const label = intl.format(d);
    return label.charAt(0).toUpperCase() + label.slice(1);
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

  // ===== Usuario + puntos (con headers)
  async function loadUserAndPoints() {
    try {
      const { data: profile } = await axios.get(AUTH_PROFILE, { headers: authHeaders() });
      const uid = profile?.user?._id || profile?.user?.id || null;
      currentUserId.value = uid;

      if (!uid) { puntos.value = 0; puntosCargados.value = true; return; }

      const { data: ptsRes } = await axios.get(USERS_POINTS(uid), { headers: authHeaders() });
      puntos.value = Number(ptsRes?.puntosCanjeo ?? 0);
      puntosCargados.value = true;
    } catch (e) {
      console.error('loadUserAndPoints error:', e?.response?.data || e);
      puntos.value = 0; puntosCargados.value = true;en
    }
  }

  async function refreshPoints() {
    if (!currentUserId.value) return;
    try {
      const { data } = await axios.get(USERS_POINTS(currentUserId.value), { headers: authHeaders() });
      puntos.value = Number(data?.puntosCanjeo ?? 0);
    } catch (e) {
      console.error('refreshPoints error:', e?.response?.data || e);
    }
  }

  async function earnPoints(amount) {
    if (!currentUserId.value) return;
    try {
      await axios.post(USERS_EARN(currentUserId.value), { puntos: amount }, { headers: authHeaders() });
      await refreshPoints();
    } catch (e) {
      console.error('earnPoints error:', e?.response?.data || e);
      toast('No fue posible abonar puntos.', 'error');
    }
  }

  // ===== Fetch por mes/año
  async function fetchMonth(m, y) {
    try {
      isLoading.value = true;
      month.value = m; year.value = y;
      const { data } = await axios.get(API_BASE, {
        params: { mes: m, anio: y },
        headers: authHeaders()
      });
      const list = Array.isArray(data?.bitacoras) ? data.bitacoras : [];
      items.value = list.map(normalize);
    } catch (e) {
      console.error('fetchMonth error:', e?.response?.data || e);
      toast('No fue posible cargar las bitácoras de este mes.', 'error');
    } finally {
      isLoading.value = false;
    }
  }

  // ===== Ver/Crear/Editar
  function openView(item) {
    selected.value = normalize(item);
    if (!viewModal && viewModalRef.value) viewModal = new Modal(viewModalRef.value, { backdrop: 'static' });
    viewModal?.show();
  }

  function openCreate(isoDate) {
    isEditing.value = false;
    resetForm();
    form.fecha = isoDate || todayISO();
    if (!form.emoji) form.emoji = EMOJIS[0] || '🙂';
    if (!formModal && formModalRef.value) formModal = new Modal(formModalRef.value, { backdrop: 'static' });
    formModal?.show();
  }

  function openEdit(item) {
    isEditing.value = true;
    const n = normalize(item);
    form._id = n._id;
    form.titulo = titleWithoutEmoji(n.titulo);
    form.descripcion = n.descripcion || '';
    form.fecha = n.fecha;
    form.emoji = n.emoji || (EMOJIS[0] || '🙂');
    if (!formModal && formModalRef.value) formModal = new Modal(formModalRef.value, { backdrop: 'static' });
    formModal?.show();
  }

  function hideModal(kind) {
    if (kind === 'form') formModal?.hide();
    if (kind === 'view') viewModal?.hide();
  }

  function todayISO() {
    const d = new Date();
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
  }

  // ===== Esquema motivacional
  function pointsForDate(isoDate) {
    // 1–10: doble (2), 11–20: simple (1), 21–31: triple (3)
    const day = Number(isoDate?.slice(8, 10) || '0');
    if (day >= 1 && day <= 10) return 2;
    if (day >= 11 && day <= 20) return 1;
    return 3;
  }

  // ===== Submit — con fallback local para "awarded"
  async function onSubmit() {
    if (!form.titulo?.trim()) { toast('El título es obligatorio.', 'error'); return; }
    if (!form.fecha) { toast('La fecha es inválida.', 'error'); return; }
    if (!form.emoji) { toast('Selecciona un emoji.', 'error'); return; }

    // ¿ya había bitácora ese día? (para fallback si el back no manda awarded)
    const existedBefore = items.value.some(b => b.fecha === form.fecha);

    saving.value = true;
    try {
      const body = {
        titulo: withEmojiPrefix(form.emoji, form.titulo),
        descripcion: form.descripcion || null,
        fecha: form.fecha
      };

      if (isEditing.value && form._id) {
        const { data } = await axios.put(`${API_BASE}/${form._id}`, body, { headers: authHeaders() });
        const saved = normalize(data?.bitacora?.data ?? data?.bitacora ?? data);
        upsertLocal(saved);
        toast('Bitácora actualizada.');
      } else {
        const { data } = await axios.post(API_BASE, body, { headers: authHeaders() });
        const saved = normalize(data?.bitacora?.data ?? data?.bitacora ?? data);
        upsertLocal(saved);
        toast('Bitácora guardada.');

        // ← Abona puntos si: el back lo indica, o localmente era la primera del día
        const mustAward = data?.awarded === true || existedBefore === false;
        if (mustAward) {
          const pts = pointsForDate(saved.fecha);
          await earnPoints(pts);
          toast(`🎉 ¡Ganaste ${pts} punto(s)!`, 'success');
        }
      }

      formModal?.hide();
      if (month.value && year.value) await fetchMonth(month.value, year.value);
    } catch (e) {
      console.error('onSubmit error:', e?.response?.data || e);
      const msg = e?.response?.data?.message || e?.response?.data?.error || 'Ocurrió un error al guardar.';
      toast(msg, 'error');
    } finally {
      saving.value = false;
    }
  }

  function upsertLocal(saved) {
    const id = saved?._id || saved?.id;
    if (!id) return;
    const idx = items.value.findIndex(x => (x._id || x.id) === id);
    if (idx >= 0) items.value.splice(idx, 1, { ...items.value[idx], ...saved });
    else items.value.push(saved);
  }

  // ===== Eliminar
  async function confirmDelete(item) {
    if (puntos.value <= 0) { toast('No puedes eliminar con 0 puntos.', 'error'); return; }

    const Swal = (await import('sweetalert2')).default;
    await import('sweetalert2/dist/sweetalert2.min.css');

    const result = await Swal.fire({
      title: '¿Eliminar bitácora?',
      text: 'Esta acción no se puede deshacer.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
      reverseButtons: true,
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d',
    });
    if (!result.isConfirmed) return;

    try {
      const id = getId(item);
      await axios.delete(`${API_BASE}/${id}`, { headers: authHeaders() });
      items.value = items.value.filter(x => getId(x) !== id);
      toast('Eliminado correctamente.');
      await refreshPoints();
    } catch (e) {
      console.error('confirmDelete error:', e?.response?.data || e);
      toast('No fue posible eliminar.', 'error');
    }
  }

  async function modifyFromView() {
    if (!selected.value) return;
    const item = { ...selected.value };
    hideModal('view');
    openEdit(item);
  }

  async function deleteFromView() {
    if (!selected.value) return;
    const item = { ...selected.value };
    hideModal('view');
    await confirmDelete(item);
  }

  function resetForm() {
    form._id = null;
    form.titulo = '';
    form.descripcion = '';
    form.fecha = todayISO();
    form.emoji = EMOJIS[0] || '🙂';
  }

  return {
    // estado
    items, isLoading, month, year,
    // puntos
    currentUserId, puntos, puntosCargados, loadUserAndPoints, refreshPoints,
    // búsqueda
    searchQuery, onInstantSearch, clearSearch,
    // CRUD
    openCreate, openEdit, openView, hideModal,
    onSubmit, confirmDelete, modifyFromView, deleteFromView,
    // form
    form, isEditing, saving, selected,
    // fetch
    fetchMonth,
    // utils
    titleWithoutEmoji, withEmojiPrefix, emojiFromTitle, toast, formatMonthLabel,
    // modales
    formModalRef, viewModalRef
  };
}
