// useTestsCrud.js
import { ref, reactive, computed, onMounted, nextTick } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import Modal from 'bootstrap/js/dist/modal';
import Tooltip from 'bootstrap/js/dist/tooltip';

/** Soporta Vite (VITE_API_URL) y Vue CLI (VUE_APP_API_URL) */
const API_BASE = ((import.meta?.env?.VITE_API_URL) || (process.env?.VUE_APP_API_URL) || '') + '/tests';

export function useTestsCrud() {
  /** === Estado raíz === */
  const items = ref([]);
  const isLoading = ref(true);
  const hasMore = ref(false);
  const page = ref(1);
  const perPage = 20;

  /** === Buscador === */
  const searchQuery = ref('');
  let searchTimer = null;
  const onInstantSearch = () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {}, 120);
  };
  const clearSearch = () => (searchQuery.value = '');

  /** === Estado UI/Form === */
  const selected = ref(null);
  const isEditing = ref(false);
  const saving = ref(false);

  /* Toggles UI */
  const ui = reactive({
    meta: false,
    cuestionario: true,
    qOpen: {},
  });
  const viewToggle = reactive({
    meta: false,
    cuestionario: true,
    qOpen: {},
  });

  const form = reactive({
    _id: null,
    nombre: '',
    descripcion: '',
    fechaAplicacion: '',
    duracion_estimada: null,
    cuestionario: [],
  });

  /** === Refs de modales (se inyectan desde el template) === */
  const viewModalRef = ref(null);
  const formModalRef = ref(null);
  let viewModal, formModal;

  onMounted(async () => {
    await fetchItems();
    await nextTick();
    if (viewModalRef.value) viewModal = new Modal(viewModalRef.value, { backdrop: 'static' });
    if (formModalRef.value) formModal = new Modal(formModalRef.value, { backdrop: 'static' });

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
      new Tooltip(el);
    });
  });

  /** === Helpers === */
  function authHeaders() {
    const token = localStorage.getItem('token');
    return token ? { Authorization: `Bearer ${token}` } : {};
  }

  function normalizeId(raw) {
    if (!raw) return null;
    if (typeof raw === 'string' || typeof raw === 'number') return String(raw);
    if (raw?.$oid) return String(raw.$oid);
    try { return String(raw); } catch { return null; }
  }
  function getId(obj) {
    const id =
      normalizeId(obj?.id) ??
      normalizeId(obj?._id) ??
      normalizeId(obj?.uuid);
    return id || null;
  }

  function labelTipo(t) {
    if (t === 'seleccion_multiple') return 'Selección múltiple';
    if (t === 'respuesta_abierta') return 'Respuesta abierta';
    return t || '—';
  }
  function formatDate(v) {
    if (!v) return '—';
    try {
      const d = new Date(v);
      if (Number.isNaN(d.getTime())) return v;
      return d.toLocaleDateString();
    } catch { return v; }
  }

  /** === Fetch & Paginación === */
  async function fetchItems({ append = false } = {}) {
    try {
      isLoading.value = true;
      const params = { page: page.value, per_page: perPage };
      const { data } = await axios.get(API_BASE, { params, headers: authHeaders() });
      const list = data?.data ?? data?.registros ?? data ?? [];
      hasMore.value = !!data?.next_page_url || (Array.isArray(list) && list.length === perPage);
      const normalized = Array.isArray(list) ? list.map(normalizeTest) : [];
      items.value = append ? [...items.value, ...normalized] : normalized;
    } catch (e) {
      console.error(e);
      toast('No fue posible cargar los datos.', 'error');
    } finally {
      isLoading.value = false;
    }
  }

  function normalizeTest(t) {
    const id = getId(t);
    const cuestionario = Array.isArray(t?.cuestionario) ? t.cuestionario : [];
    const normCuestionario = cuestionario.map((q, i) => {
      const qid = q?._id || sequentialId(i, cuestionario);
      return {
        _id: qid,
        pregunta: q?.pregunta ?? '',
        tipo: q?.tipo ?? '',
        opciones: Array.isArray(q?.opciones) ? q.opciones : [],
        __key: randomKey(),
      };
    });
    return { ...t, _id: id, id, cuestionario: normCuestionario };
  }

  function sequentialId(idx, arr) {
    let n = (idx ?? arr?.length ?? 0) + 1;
    const taken = new Set((arr || []).map(x => String(x?._id || '')));
    let candidate = `t${n}`;
    while (taken.has(candidate)) { n += 1; candidate = `t${n}`; }
    return candidate;
  }
  function randomKey() {
    return (crypto?.randomUUID?.() || ('k' + Date.now().toString(36) + Math.random().toString(36).slice(2, 7)));
  }

  function loadMore() {
    page.value += 1;
    fetchItems({ append: true });
  }

  /** === Filtro local === */
  const filteredItems = computed(() => {
    const q = searchQuery.value.toLowerCase();
    if (!q) return items.value;
    return items.value.filter(it => {
      const n = (it.nombre || '').toLowerCase();
      const d = (it.descripcion || '').toLowerCase();
      return n.includes(q) || d.includes(q);
    });
  });

  /** === Abrir/Cerrar Modales === */
  function openView(item) {
    selected.value = normalizeTest({ ...item });
    viewToggle.meta = false;
    viewToggle.cuestionario = true;
    viewToggle.qOpen = {};
    (selected.value.cuestionario || []).forEach((_q, i) => { viewToggle.qOpen[i] = false; });
    viewModal?.show();
  }

  function openCreate() {
    isEditing.value = false;
    resetForm();
    ui.meta = false;
    ui.cuestionario = true;
    ui.qOpen = {};
    formModal?.show();
  }

  function openEdit(item) {
    isEditing.value = true;
    setForm(normalizeTest(item));
    ui.meta = false;
    ui.cuestionario = true;
    ui.qOpen = {};
    form.cuestionario.forEach((_q, i) => { ui.qOpen[i] = false; });
    formModal?.show();
  }

  function hideModal(kind) {
    if (kind === 'view') viewModal?.hide();
    if (kind === 'form') formModal?.hide();
  }

  /** === Form: set/reset === */
  function resetForm() {
    form._id = null;
    form.nombre = '';
    form.descripcion = '';
    form.fechaAplicacion = '';
    form.duracion_estimada = null;
    form.cuestionario = [];
  }
  function setForm(item) {
    form._id = getId(item);
    form.nombre = item.nombre ?? '';
    form.descripcion = item.descripcion ?? '';
    form.fechaAplicacion = toDateInputValue(item.fechaAplicacion);
    form.duracion_estimada = item.duracion_estimada ?? null;

    form.cuestionario = Array.isArray(item.cuestionario)
      ? item.cuestionario.map((q, i) => ({
          __key: randomKey(),
          _id: q._id || sequentialId(i, item.cuestionario),
          pregunta: q.pregunta ?? '',
          tipo: q.tipo ?? '',
          opciones: Array.isArray(q.opciones) ? [...q.opciones] : []
        }))
      : [];
  }

  function toDateInputValue(v) {
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

  /** === Editor de preguntas === */
  function addPregunta() {
    const nextId = sequentialId(form.cuestionario.length, form.cuestionario);
    form.cuestionario.push({ __key: randomKey(), _id: nextId, pregunta: '', tipo: '', opciones: [] });
    const idx = form.cuestionario.length - 1;
    ui.qOpen[idx] = true;
  }
  function removePregunta(index) {
    form.cuestionario.splice(index, 1);
    form.cuestionario.forEach((q, i) => { q._id = `t${i+1}`; });
    const newOpen = {};
    form.cuestionario.forEach((_q, i) => { newOpen[i] = !!ui.qOpen[i]; });
    ui.qOpen = newOpen;
  }
  function onChangeTipo(q) {
    if (!needsOptions(q)) q.opciones = [];
    if (needsOptions(q) && (!q.opciones || q.opciones.length < 2)) {
      q.opciones = q.opciones && q.opciones.length ? q.opciones : ['',''];
    }
  }
  function needsOptions(q) { return q?.tipo === 'seleccion_multiple'; }
  function addOpcion(q) { if (!Array.isArray(q.opciones)) q.opciones = []; q.opciones.push(''); }
  function removeOpcion(q, i) { q.opciones.splice(i, 1); }

  /** === Toggles de acordeones === */
  function toggleQuestion(idx) { ui.qOpen[idx] = !ui.qOpen[idx]; }
  function toggleViewQuestion(idx) { viewToggle.qOpen[idx] = !viewToggle.qOpen[idx]; }

  /** === Submit === */
  async function onSubmit() {
    if (!form.nombre?.trim()) { toast('El nombre es obligatorio.', 'error'); return; }
    if (form.cuestionario.length === 0) { toast('Agrega al menos una pregunta.', 'error'); return; }
    for (const q of form.cuestionario) {
      if (!q.pregunta?.trim() || !q.tipo?.trim()) { toast('Completa los campos obligatorios de cada pregunta.', 'error'); return; }
      if (needsOptions(q)) {
        const ops = (q.opciones || []).map(o => (o || '').trim()).filter(Boolean);
        if (ops.length < 2) { toast('Cada pregunta con opciones debe tener al menos 2 opciones válidas.', 'error'); return; }
      }
    }

    saving.value = true;
    try {
      if (isEditing.value && form._id) {
        const { data } = await axios.put(`${API_BASE}/${form._id}`, payload(), { headers: authHeaders() });
        const saved = normalizeTest(data?.data ?? data);
        upsertLocal(saved);
        hideModal('form'); await refreshList();
        toast('Test actualizado.');
      } else {
        const { data } = await axios.post(API_BASE, payload(), { headers: authHeaders() });
        const saved = normalizeTest(data?.data ?? data);
        prependLocal(saved);
        hideModal('form'); await refreshList();
        toast('Test creado.');
      }
    } catch (e) {
      console.error(e);
      const err = e?.response?.data?.message || 'Ocurrió un error al guardar.';
      toast(err, 'error');
    } finally {
      saving.value = false;
    }
  }
  function payload() {
    const cuestionario = form.cuestionario.map(q => ({
      _id: q._id,
      pregunta: q.pregunta,
      tipo: q.tipo,
      ...(needsOptions(q) ? { opciones: (q.opciones || []).map(o => (o || '').trim()).filter(Boolean) } : {})
    }));
    return {
      nombre: form.nombre,
      descripcion: form.descripcion || null,
      fechaAplicacion: form.fechaAplicacion || null,
      duracion_estimada: form.duracion_estimada ?? null,
      cuestionario
    };
  }
  function upsertLocal(saved) {
    const id = getId(saved); if (!id) return;
    const idx = items.value.findIndex(x => getId(x) === id);
    if (idx >= 0) items.value.splice(idx, 1, { ...items.value[idx], ...saved });
    else items.value.unshift(saved);
  }
  function prependLocal(saved) { items.value.unshift(saved); }
  async function refreshList() { page.value = 1; await fetchItems({ append: false }); }

  /** === Eliminar === */
  async function confirmDelete(item) {
    const result = await Swal.fire({
      title: '¿Eliminar test?',
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
    } catch (e) {
      console.error(e);
      toast('No fue posible eliminar.', 'error');
    }
  }

  /** === Acciones desde el modal de vista === */
  async function modifyFromView() {
    if (!selected.value) return;
    const item = { ...selected.value };
    hideModal('view'); await nextTick(); openEdit(item);
  }
  async function deleteFromView() {
    if (!selected.value) return;
    const item = { ...selected.value };
    hideModal('view'); await nextTick(); await confirmDelete(item);
  }

  /** === Toast === */
  function toast(message, type = 'success') {
    Swal.fire({ toast: true, position: 'top-end', icon: type, title: message, showConfirmButton: false, timer: 2000, timerProgressBar: true });
  }

  /** === API del composable (todo lo que usa el template) === */
  return {
    // estado y listas
    items, isLoading, hasMore, filteredItems, page,
    // búsqueda
    searchQuery, onInstantSearch, clearSearch,
    // utilidades
    getId, formatDate, labelTipo,
    // refs de modal y helpers
    viewModalRef, formModalRef, hideModal,
    // selección, formulario y UI
    selected, ui, viewToggle, isEditing, saving, form,
    // grid/paginación
    loadMore,
    // abrir/ver/editar
    openView, openCreate, openEdit,
    // cuestionario
    addPregunta, removePregunta, toggleQuestion, needsOptions, addOpcion, removeOpcion, onChangeTipo, toggleViewQuestion,
    // submit/eliminar
    onSubmit, confirmDelete, modifyFromView, deleteFromView,
  };
}
