// src/assets/js/useEncuestasCrud.js
import { ref, reactive, computed, onMounted, nextTick } from 'vue';
import Modal from 'bootstrap/js/dist/modal';
import axios from 'axios';

// Reutilizables centralizados
import {
  apiBase, authHeaders, getId,
  normalizeCuestionario, sequentialId, randomKey,
  toDateInputValue, formatDate,
  makeDebouncer, toast, setupBsTooltips,
  fetchPaginated, isRequired, validateOptions, isPositiveInt
} from '@/assets/js/crudUtils';

const API_BASE = apiBase('/encuestas');

export function useEncuestasCrud() {
  /** === Estado raíz === */
  const items = ref([]);
  const isLoading = ref(true);
  const hasMore = ref(false);
  const page = ref(1);
  const perPage = 20;

  /** === Buscador (debounce utilitario) === */
  const searchQuery = ref('');
  const debounce = makeDebouncer(120);
  const onInstantSearch = () => debounce(() => {});
  const clearSearch = () => { searchQuery.value = ''; };

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
    titulo: '',
    descripcion: '',
    fechaAsignacion: '',    // YYYY-MM-DD
    fechaFinalizacion: '',  // YYYY-MM-DD
    duracion_estimada: null,
    cuestionario: [],
  });

  /** === Respuestas (encuestas) === */
  const answers = ref([]);              // lista de respondentes con sus respuestas
  const answersLoading = ref(false);
  const answersQuery = ref('');
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
  const answersModalRef = ref(null);

  let viewModal;
  let formModal;
  let answersModal;

  onMounted(async () => {
    await fetchItems();
    await nextTick();

    if (viewModalRef.value) {
      viewModal = new Modal(viewModalRef.value, { backdrop: 'static' });
    }
    if (formModalRef.value) {
      formModal = new Modal(formModalRef.value, { backdrop: 'static' });
    }
    if (answersModalRef.value) {
      answersModal = new Modal(answersModalRef.value, { backdrop: 'static' });
    }

    setupBsTooltips();
  });

  /** === Helpers de dominio === */
  function labelTipo(t) {
    // En encuestas por ahora seguimos usando selección múltiple
    if (t === 'seleccion_multiple') return 'Selección múltiple';
    if (t === 'respuesta_abierta')  return 'Respuesta abierta';
    return t || '—';
  }

  function formatDateRange(inicio, fin) {
    const a = formatDate(inicio);
    const b = formatDate(fin);
    if (!a && !b) return '—';
    if (a && !b)   return a;
    if (!a && b)   return b;
    return `${a} — ${b}`;
  }

  /** === Fetch & Paginación === */
  async function fetchItems({ append = false } = {}) {
    try {
      isLoading.value = true;
      const { list, hasMore: hm } = await fetchPaginated(API_BASE, {
        page: page.value,
        perPage,
        headers: authHeaders(),
      });
      hasMore.value = hm;
      const normalized = list.map(normalizeEncuesta);
      items.value = append ? [...items.value, ...normalized] : normalized;
    } catch (e) {
      console.error(e);
      toast('No fue posible cargar las encuestas.', 'error');
    } finally {
      isLoading.value = false;
    }
  }

  function normalizeEncuesta(raw) {
    const id = getId(raw);
    const base = normalizeCuestionario(raw?.cuestionario, { idPrefix: 'q' })
      .map(q => ({
        ...q,
        // Para mantener compatibilidad: tipo forzado a selección múltiple
        tipo: 'seleccion_multiple',
        opciones: Array.isArray(q.opciones) ? q.opciones : [],
      }));

    return {
      ...raw,
      _id: id,
      id,
      titulo: raw?.titulo ?? '',
      descripcion: raw?.descripcion ?? '',
      fechaAsignacion: raw?.fechaAsignacion ?? '',
      fechaFinalizacion: raw?.fechaFinalizacion ?? '',
      duracion_estimada: raw?.duracion_estimada ?? null,
      cuestionario: base,
    };
  }

  /** === Paginación === */
  function loadMore() {
    page.value += 1;
    fetchItems({ append: true });
  }

  /** === Filtro local (título o duración) === */
  const filteredItems = computed(() => {
    const q = searchQuery.value.toLowerCase().trim();
    if (!q) return items.value;
    const isNumber = /^\d+$/.test(q);
    return items.value.filter(it => {
      const t = (it.titulo || '').toLowerCase();
      const dur = (it.duracion_estimada ?? '').toString();
      if (isNumber) return dur.includes(q);
      return t.includes(q);
    });
  });

  /** === Abrir/Cerrar Modales === */
  function openView(item) {
    selected.value = normalizeEncuesta({ ...item });
    viewToggle.meta = false;
    viewToggle.cuestionario = true;
    viewToggle.qOpen = {};
    (selected.value.cuestionario || []).forEach((_q, i) => {
      viewToggle.qOpen[i] = false;
    });
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
    setForm(normalizeEncuesta(item));
    ui.meta = false;
    ui.cuestionario = true;
    ui.qOpen = {};
    form.cuestionario.forEach((_q, i) => {
      ui.qOpen[i] = false;
    });
    formModal?.show();
  }

  function hideModal(kind) {
    if (kind === 'view') viewModal?.hide();
    if (kind === 'form') formModal?.hide();
  }

  /** === Ver respuestas de la encuesta === */
  async function viewAnswers(item) {
    try {
      selected.value = normalizeEncuesta({ ...item });
      answersLoading.value = true;
      answersQuery.value = '';
      answers.value = [];

      const id = getId(item);
      const { data } = await axios.get(`${API_BASE}/${id}/respuestas`, {
        headers: authHeaders(),
      });

      // Esperamos algo tipo: { encuesta: {...}, respondents: [...] }
      if (data?.encuesta?.titulo) {
        selected.value.titulo = data.encuesta.titulo;
      }

      const payload = Array.isArray(data?.respondents)
        ? data.respondents
        : (Array.isArray(data?.data) ? data.data : []);

      answers.value = payload;
      answersModal?.show();
    } catch (e) {
      console.error(e);
      toast('No fue posible obtener las respuestas de la encuesta.', 'error');
    } finally {
      answersLoading.value = false;
    }
  }

  function hideAnswers() {
    answersModal?.hide();
  }

  /** === Form: set/reset === */
  function resetForm() {
    form._id = null;
    form.titulo = '';
    form.descripcion = '';
    form.fechaAsignacion = '';
    form.fechaFinalizacion = '';
    form.duracion_estimada = null;
    form.cuestionario = [];
  }

  function setForm(item) {
    form._id = getId(item);
    form.titulo = item.titulo ?? '';
    form.descripcion = item.descripcion ?? '';
    form.fechaAsignacion = toDateInputValue(item.fechaAsignacion);
    form.fechaFinalizacion = toDateInputValue(item.fechaFinalizacion);
    form.duracion_estimada = item.duracion_estimada ?? null;

    form.cuestionario = normalizeCuestionario(item.cuestionario, { idPrefix: 'q' })
      .map(q => ({
        ...q,
        tipo: 'seleccion_multiple',
        opciones: Array.isArray(q.opciones) ? q.opciones : [],
      }));
  }

  /** === Editor de preguntas === */
  function addPregunta() {
    const nextId = sequentialId(form.cuestionario.length, form.cuestionario, 'q');
    form.cuestionario.push({
      __key: randomKey(),
      _id: nextId,
      pregunta: '',
      tipo: 'seleccion_multiple',
      opciones: ['', ''], // dos por defecto
    });
    const idx = form.cuestionario.length - 1;
    ui.qOpen[idx] = true;
  }

  function removePregunta(index) {
    form.cuestionario.splice(index, 1);
    form.cuestionario.forEach((q, i) => {
      q._id = `q${i + 1}`;
    });
    const newOpen = {};
    form.cuestionario.forEach((_q, i) => {
      newOpen[i] = !!ui.qOpen[i];
    });
    ui.qOpen = newOpen;
  }

  function onChangeTipo(q) {
    // En encuestas por ahora seguimos usando selección múltiple
    q.tipo = 'seleccion_multiple';
    if (!needsOptions(q)) {
      q.opciones = [];
    }
    if (needsOptions(q) && (!q.opciones || q.opciones.length < 2)) {
      q.opciones = q.opciones && q.opciones.length ? q.opciones : ['', ''];
    }
  }

  function needsOptions(_q) {
    // Mantén esto así: todas las preguntas requieren opciones (como antes)
    return true;
  }

  function addOpcion(q) {
    if (!Array.isArray(q.opciones)) q.opciones = [];
    q.opciones.push('');
  }

  function removeOpcion(q, i) {
    q.opciones.splice(i, 1);
  }

  /** === Toggles de acordeones === */
  function toggleQuestion(idx) {
    ui.qOpen[idx] = !ui.qOpen[idx];
  }

  function toggleViewQuestion(idx) {
    viewToggle.qOpen[idx] = !viewToggle.qOpen[idx];
  }

  /** === Validaciones específicas === */
  function validDates() {
    const a = form.fechaAsignacion ? new Date(form.fechaAsignacion) : null;
    const b = form.fechaFinalizacion ? new Date(form.fechaFinalizacion) : null;
    if (!a || !b) return true;
    return b.getTime() >= a.getTime();
  }

  /** === Submit === */
  async function onSubmit() {
    if (!isRequired(form.titulo)) {
      toast('El título es obligatorio.', 'error');
      return;
    }
    if (form.duracion_estimada != null && !isPositiveInt(form.duracion_estimada)) {
      toast('La duración debe ser un entero positivo.', 'error');
      return;
    }
    if (!validDates()) {
      toast('La fecha de fin no puede ser anterior a la fecha de inicio.', 'error');
      return;
    }
    if (form.cuestionario.length === 0) {
      toast('Agrega al menos una pregunta.', 'error');
      return;
    }

    for (const q of form.cuestionario) {
      if (!isRequired(q.pregunta)) {
        toast('Completa el texto de cada pregunta.', 'error');
        return;
      }
      if (!validateOptions(q.opciones, 2)) {
        toast('Cada pregunta debe tener al menos 2 opciones no vacías.', 'error');
        return;
      }
    }

    saving.value = true;
    try {
      const body = payload();

      if (isEditing.value && form._id) {
        const { data } = await axios.put(`${API_BASE}/${form._id}`, body, {
          headers: authHeaders(),
        });
        const saved = normalizeEncuesta(data?.encuesta ?? data?.data ?? data);
        upsertLocal(saved);
        hideModal('form');
        await refreshList();
        toast('Encuesta actualizada.');
      } else {
        const { data } = await axios.post(API_BASE, body, {
          headers: authHeaders(),
        });
        const saved = normalizeEncuesta(data?.encuesta ?? data?.data ?? data);
        prependLocal(saved);
        hideModal('form');
        await refreshList();
        toast('Encuesta creada.');
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
      tipo: 'seleccion_multiple',
      opciones: (q.opciones || [])
        .map(o => (o || '').trim())
        .filter(Boolean),
    }));

    return {
      titulo: form.titulo,
      descripcion: form.descripcion || null,
      fechaAsignacion: form.fechaAsignacion || null,
      fechaFinalizacion: form.fechaFinalizacion || null,
      duracion_estimada: form.duracion_estimada ?? null,
      cuestionario,
    };
  }

  function upsertLocal(saved) {
    const id = getId(saved);
    if (!id) return;
    const idx = items.value.findIndex(x => getId(x) === id);
    if (idx >= 0) {
      items.value.splice(idx, 1, { ...items.value[idx], ...saved });
    } else {
      items.value.unshift(saved);
    }
  }

  function prependLocal(saved) {
    items.value.unshift(saved);
  }

  async function refreshList() {
    page.value = 1;
    await fetchItems({ append: false });
  }

  /** === Eliminar === */
  async function confirmDelete(item) {
    const Swal = (await import('sweetalert2')).default;
    await import('sweetalert2/dist/sweetalert2.min.css');
    const result = await Swal.fire({
      title: '¿Eliminar encuesta?',
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
    hideModal('view');
    await nextTick();
    openEdit(item);
  }

  async function deleteFromView() {
    if (!selected.value) return;
      const item = { ...selected.value };
      hideModal('view');
      await nextTick();
      await confirmDelete(item);
  }

  /** === API del composable === */
  return {
    // estado y listas
    items,
    isLoading,
    hasMore,
    filteredItems,
    page,

    // búsqueda
    searchQuery,
    onInstantSearch,
    clearSearch,

    // utilidades
    getId,
    formatDateRange,
    labelTipo,

    // refs de modales
    viewModalRef,
    formModalRef,
    answersModalRef,
    hideModal,

    // selección, formulario y UI
    selected,
    ui,
    viewToggle,
    isEditing,
    saving,
    form,

    // grid/paginación
    loadMore,

    // abrir/ver/editar
    openView,
    openCreate,
    openEdit,

    // cuestionario
    addPregunta,
    removePregunta,
    toggleQuestion,
    needsOptions,
    addOpcion,
    removeOpcion,
    onChangeTipo,
    toggleViewQuestion,

    // submit/eliminar
    onSubmit,
    confirmDelete,
    modifyFromView,
    deleteFromView,

    // respuestas
    viewAnswers,
    hideAnswers,
    answers,
    answersLoading,
    answersQuery,
    filteredAnswers,
  };
}
