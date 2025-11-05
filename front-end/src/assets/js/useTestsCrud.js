// src/assets/js/useTestsCrud.js
import { ref, reactive, computed, onMounted, nextTick } from 'vue';
import Modal from 'bootstrap/js/dist/modal';
import axios from 'axios';

// 🔗 Importa TODO lo reutilizable desde crudUtils
import {
  apiBase, authHeaders, getId,
  normalizeCuestionario, sequentialId, randomKey,
  toDateInputValue, formatDate,
  makeDebouncer, toast, setupBsTooltips,
  fetchPaginated, isRequired, validateOptions
} from '@/assets/js/crudUtils';

const API_BASE = apiBase('/tests');

export function useTestsCrud() {
  /** === Estado raíz === */
  const items = ref([]);
  const isLoading = ref(true);
  const hasMore = ref(false);
  const page = ref(1);
  const perPage = 20;

  /** === Búsqueda (debounce reutilizable) === */
  const searchQuery = ref('');
  const debounce = makeDebouncer(120);
  const onInstantSearch = () => debounce(() => {});
  const clearSearch = () => (searchQuery.value = '');

  /** === Estado UI/Form === */
  const selected = ref(null);
  const isEditing = ref(false);
  const saving = ref(false);

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

  /** === Respuestas (nuevo) === */
  const answers = ref([]);              // lista de respondentes con sus respuestas
  const answersLoading = ref(false);
  const answersQuery = ref('');         // búsqueda en tiempo real (cliente)
  const filteredAnswers = computed(() => {
    const q = (answersQuery.value || '').toLowerCase();
    if (!q) return answers.value;
    return answers.value.filter(r =>
      (r.nombre || '').toLowerCase().includes(q) ||
      (r.email  || '').toLowerCase().includes(q) ||
      (r.usuario_id || '').toLowerCase().includes(q)
    );
  });

  /** === Refs de modales === */
  const viewModalRef = ref(null);
  const formModalRef = ref(null);
  const answersModalRef = ref(null);    // nuevo
  let viewModal, formModal, answersModal;

  onMounted(async () => {
    await fetchItems();
    await nextTick();
    if (viewModalRef.value) viewModal = new Modal(viewModalRef.value, { backdrop: 'static' });
    if (formModalRef.value) formModal = new Modal(formModalRef.value, { backdrop: 'static' });
    if (answersModalRef.value) answersModal = new Modal(answersModalRef.value, { backdrop: 'static' }); // nuevo
    setupBsTooltips(); // activa tooltips globales (incluye botones de la UI)
  });

  /** === Helpers específicos del dominio Test === */
  function labelTipo(t) {
    if (t === 'seleccion_multiple') return 'Selección múltiple';
    if (t === 'respuesta_abierta') return 'Respuesta abierta';
    return t || '—';
  }

  /** === Fetch & Paginación === */
  async function fetchItems({ append = false } = {}) {
    try {
      isLoading.value = true;
      const { list, hasMore: hm } = await fetchPaginated(API_BASE, {
        page: page.value, perPage, headers: authHeaders()
      });
      hasMore.value = hm;
      const normalized = list.map(normalizeTest);
      items.value = append ? [...items.value, ...normalized] : normalized;
    } catch (e) {
      console.error(e);
      toast('No fue posible cargar los datos.', 'error');
    } finally {
      isLoading.value = false;
    }
  }

  /** === Ver respuestas === */
  async function viewAnswers(item){
    try{
      selected.value = normalizeTest({ ...item });
      answersLoading.value = true;
      answersQuery.value = '';

      const id = getId(item);
      const { data } = await axios.get(`${API_BASE}/${id}/respuestas`, { headers: authHeaders() });

      // ⬇️ El controller regresa { test:{...}, respondents:[...] }
      if (data?.test?.nombre) {
        // asegura que el modal muestre el nombre correcto del test
        selected.value.nombre = data.test.nombre;
      }

      const payload = Array.isArray(data?.respondents)
        ? data.respondents
        : (Array.isArray(data?.data) ? data.data : []); // fallback por si cambias después

      answers.value = payload;
      answersModal?.show();
    }catch(e){
      console.error(e);
      toast('No fue posible obtener las respuestas.', 'error');
    }finally{
      answersLoading.value = false;
    }
  }
  function hideAnswers(){ answersModal?.hide(); }

  function normalizeTest(t) {
    const id = getId(t);
    return {
      ...t,
      _id: id,
      id,
      cuestionario: normalizeCuestionario(t?.cuestionario, { idPrefix: 't' }), // asegura _id, opciones, __key
    };
  }

  /** === Paginación === */
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

    form.cuestionario = normalizeCuestionario(item.cuestionario, { idPrefix: 't' });
  }

  /** === Editor de preguntas === */
  function addPregunta() {
    const nextId = sequentialId(form.cuestionario.length, form.cuestionario, 't');
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
    const needsOptions = q?.tipo === 'seleccion_multiple';
    if (!needsOptions) q.opciones = [];
    if (needsOptions && (!q.opciones || q.opciones.length < 2)) {
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
    if (!isRequired(form.nombre)) { toast('El nombre es obligatorio.', 'error'); return; }
    if (form.cuestionario.length === 0) { toast('Agrega al menos una pregunta.', 'error'); return; }
    for (const q of form.cuestionario) {
      if (!isRequired(q.pregunta) || !isRequired(q.tipo)) { toast('Completa los campos obligatorios de cada pregunta.', 'error'); return; }
      if (needsOptions(q) && !validateOptions(q.opciones, 2)) {
        toast('Cada pregunta con opciones debe tener al menos 2 opciones válidas.', 'error'); return;
      }
    }

    saving.value = true;
    try {
      const body = payload();
      if (isEditing.value && form._id) {
        const { data } = await axios.put(`${API_BASE}/${form._id}`, body, { headers: authHeaders() });
        const saved = normalizeTest(data?.data ?? data);
        upsertLocal(saved);
        hideModal('form'); await refreshList();
        toast('Test actualizado.');
      } else {
        const { data } = await axios.post(API_BASE, body, { headers: authHeaders() });
        const saved = normalizeTest(data?.data ?? data);
        prependLocal(saved);
        hideModal('form'); await refreshList();
        toast('Test creado.');
      }
    } catch (e) {
      console.error(e);
      toast(e?.response?.data?.message || 'Ocurrió un error al guardar.', 'error');
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
    const Swal = (await import('sweetalert2')).default;
    await import('sweetalert2/dist/sweetalert2.min.css'); // garantía si haces code-splitting
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

  /** === API del composable === */
  return {
    // estado y listas
    items, isLoading, hasMore, filteredItems, page,

    // búsqueda
    searchQuery, onInstantSearch, clearSearch,

    // utilidades
    getId, formatDate, labelTipo,

    // refs de modales
    viewModalRef, formModalRef, answersModalRef, hideModal,

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

    // respuestas (nuevo)
    viewAnswers, hideAnswers, answers, answersLoading, answersQuery, filteredAnswers,
  };
}
