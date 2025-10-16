// src/assets/js/useTecnicasCrud.js
import { ref, reactive, computed, onMounted, nextTick } from 'vue';
import Modal from 'bootstrap/js/dist/modal';
import axios from 'axios';

import {
  apiBase, authHeaders, getId, makeDebouncer, toast, setupBsTooltips,
  fetchPaginated, randomKey, isRequired
} from '@/assets/js/crudUtils';

const API_BASE   = apiBase('/tecnicas');
const UPLOAD_URL = apiBase('/uploads'); // ajusta si tu backend usa otra ruta

// Categorías predeterminadas basadas en tus actividades/ejercicios
const DEFAULT_CATEGORIES = [
  'Respiración',
  'Concentración/Atención Plena',
  'Visualización',
  'Escaneo corporal',
  'Gratitud',
  'Meditación guiada',
  'Movimiento consciente/Yoga',
  'Caminata consciente',
  'Diario de emociones/Reflexión',
  'Regulación emocional',
  'Atención a los sentidos',
  'Relajación en el aula',
];

export function useTecnicasCrud() {
  /** === Estado raíz === */
  const items = ref([]);
  const isLoading = ref(true);
  const hasMore = ref(false);
  const page = ref(1);
  const perPage = 20;

  /** === Buscador === */
  const searchQuery = ref('');
  const debounce = makeDebouncer(120);
  const onInstantSearch = () => debounce(() => {});
  const clearSearch = () => (searchQuery.value = '');

  /** === Estado UI/Form === */
  const selected = ref(null);
  const isEditing = ref(false);
  const saving = ref(false);

  const categorias = ref([...DEFAULT_CATEGORIES]);

  const ui = reactive({
    meta: true,
    recursos: true,
    rOpen: {},
  });
  const viewToggle = reactive({
    meta: false,
    recursos: true,
  });

  const form = reactive({
    _id: null,
    nombre: '',
    descripcion: '',
    dificultad: '',
    duracion: null,
    categoria: '',
    categoria_custom: '',
    recursos: [], // [{ _id, url, descripcion, tipo(auto), fecha(auto), __file?, _previewUrl? }]
  });

  /** === Refs de modales === */
  const viewModalRef = ref(null);
  const formModalRef = ref(null);
  let viewModal, formModal;

  onMounted(async () => {
    await fetchItems();
    await nextTick();
    if (viewModalRef.value) viewModal = new Modal(viewModalRef.value, { backdrop: 'static' });
    if (formModalRef.value) formModal = new Modal(formModalRef.value, { backdrop: 'static' });
    setupBsTooltips();
  });

  /** === Fetch & Paginación === */
  async function fetchItems({ append = false } = {}) {
    try {
      isLoading.value = true;
      const { list, hasMore: hm } = await fetchPaginated(API_BASE, {
        page: page.value, perPage, headers: authHeaders()
      });
      hasMore.value = hm;
      const normalized = (list || []).map(normalizeTecnica);
      items.value = append ? [...items.value, ...normalized] : normalized;
    } catch (e) {
      console.error(e);
      toast('No fue posible cargar las técnicas.', 'error');
    } finally {
      isLoading.value = false;
    }
  }
  function normalizeTecnica(raw) {
    const id = getId(raw);
    const recursos = Array.isArray(raw?.recursos) ? raw.recursos : [];
    return {
      ...raw,
      _id: id,
      id,
      nombre: raw?.nombre ?? '',
      descripcion: raw?.descripcion ?? '',
      dificultad: raw?.dificultad ?? '',
      duracion: raw?.duracion ?? null,
      categoria: raw?.categoria ?? '',
      recursos
    };
  }
  function loadMore() { page.value += 1; fetchItems({ append: true }); }

  /** === Filtro local === */
  const filteredItems = computed(() => {
    const q = searchQuery.value.toLowerCase().trim();
    if (!q) return items.value;
    const isNumber = /^\d+$/.test(q);
    return items.value.filter(it => {
      const nombre = (it.nombre || '').toLowerCase();
      const categoria = (it.categoria || '').toLowerCase();
      const dur = (it.duracion ?? '').toString();
      if (isNumber) return dur.includes(q);
      return nombre.includes(q) || categoria.includes(q);
    });
  });

  /** === Abrir/Cerrar Modales === */
  function openView(item) {
    selected.value = normalizeTecnica({ ...item });
    viewToggle.meta = false;
    viewToggle.recursos = true;
    viewModal?.show();
  }
  function openCreate() {
    isEditing.value = false;
    resetForm();
    ui.meta = true; ui.recursos = true; ui.rOpen = {};
    formModal?.show();
  }
  function openEdit(item) {
    isEditing.value = true;
    setForm(normalizeTecnica(item));
    ui.meta = true; ui.recursos = true; ui.rOpen = {};
    form.recursos.forEach((_r, i) => { ui.rOpen[i] = true; });
    formModal?.show();
  }
  function hideModal(kind) { if (kind === 'view') viewModal?.hide(); if (kind === 'form') formModal?.hide(); }

  /** === Form: set/reset === */
  function resetForm() {
    form._id = null;
    form.nombre = '';
    form.descripcion = '';
    form.dificultad = '';
    form.duracion = null;
    form.categoria = '';
    form.categoria_custom = '';
    form.recursos = [];
  }
  function setForm(item) {
    form._id = getId(item);
    form.nombre = item.nombre ?? '';
    form.descripcion = item.descripcion ?? '';
    form.dificultad = item.dificultad ?? '';
    form.duracion = item.duracion ?? null;
    form.categoria = item.categoria ?? '';
    form.categoria_custom = '';
    form.recursos = (item.recursos || []).map(r => ({
      __key: randomKey(),
      _id: r._id || undefined,
      url: r.url || '',
      descripcion: r.descripcion || '',
      // tipo/fecha visibles pero recalculadas si cambia el archivo
      tipo: r.tipo || autoType(r.url),
      fecha: r.fecha || todayISO(),
      __file: null,
      _previewUrl: null,
    }));
  }

  /** === Recursos: edición local === */
  function addRecurso() {
    form.recursos.push({
      __key: randomKey(),
      _id: undefined,
      url: '',
      descripcion: '',
      tipo: 'Imagen',      // valor inicial, se ajusta al adjuntar
      fecha: todayISO(),   // auto
      __file: null,
      _previewUrl: null,
    });
    const idx = form.recursos.length - 1;
    ui.rOpen[idx] = true;
  }

  // Confirmar quitar TARJETA de recurso
  async function removeRecurso(index) {
    const Swal = (await import('sweetalert2')).default;
    await import('sweetalert2/dist/sweetalert2.min.css');

    const result = await Swal.fire({
      title: '¿Quitar este recurso?',
      text: 'Se eliminará del formulario. Si ya estaba guardado, se quitará al guardar los cambios.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, quitar',
      cancelButtonText: 'Cancelar',
      reverseButtons: true,
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d',
    });

    if (!result.isConfirmed) return;

    form.recursos.splice(index, 1);
    const newOpen = {};
    form.recursos.forEach((_r, i) => { newOpen[i] = !!ui.rOpen[i]; });
    ui.rOpen = newOpen;
    toast('Recurso quitado del formulario.');
  }

  function toggleRecurso(idx) { ui.rOpen[idx] = !ui.rOpen[idx]; }

  // Confirmar limpiar SOLO el archivo del recurso (botón X sobre la vista)
  async function clearResourceFile(r) {
    const Swal = (await import('sweetalert2')).default;
    await import('sweetalert2/dist/sweetalert2.min.css');

    const result = await Swal.fire({
      title: '¿Quitar el archivo?',
      text: 'Se eliminará el archivo seleccionado para este recurso, pero podrás adjuntar otro antes de guardar.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, quitar archivo',
      cancelButtonText: 'Cancelar',
      reverseButtons: true,
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d',
    });

    if (!result.isConfirmed) return;

    r.__file = null;
    r._previewUrl = null;
    r.url = '';
    r.tipo = 'Imagen';
    r.fecha = todayISO();
    toast('Archivo retirado del recurso.');
  }

  function onPickFile(e, r) {
    const file = e?.target?.files?.[0];
    if (!file) return;
    r.__file = file;
    r._previewUrl = URL.createObjectURL(file);
    r.tipo = typeFromMime(file.type) || autoType(file.name);
    r.fecha = todayISO();
  }

  /** === Utilidades de tipo/preview === */
  function isImage(url)  { return /\.(png|jpe?g|gif|webp|avif|svg)$/i.test(url || ''); }
  function isVideo(url)  { return /\.(mp4|webm|ogg|mov|m4v)$/i.test(url || ''); }
  function isAudio(url)  { return /\.(mp3|wav|ogg|m4a)$/i.test(url || ''); }
  function autoType(nameOrUrl) {
    const s = (nameOrUrl || '').toLowerCase();
    if (isImage(s)) return 'Imagen';
    if (isVideo(s)) return 'Video';
    if (isAudio(s)) return 'Audio';
    return 'Archivo';
  }
  function typeFromMime(mime) {
    if (!mime) return null;
    if (mime.startsWith('image/')) return 'Imagen';
    if (mime.startsWith('video/')) return 'Video';
    if (mime.startsWith('audio/')) return 'Audio';
    return null;
  }
  function todayISO() {
    const d = new Date();
    return d.toISOString().slice(0,10);
  }

  /** === Validaciones === */
  function validate() {
    if (!isRequired(form.nombre)) { toast('El nombre es obligatorio.', 'error'); return false; }
    if (form.duracion != null && String(form.duracion).trim() !== '' && Number(form.duracion) <= 0) {
      toast('La duración debe ser un entero positivo.', 'error'); return false;
    }
    return true;
  }

  /** === Subida de archivos === */
  async function uploadResource(file) {
    const isImg  = file.type.startsWith('image/');
    const dest   = isImg ? 'assets/images/Recursos' : 'assets/media/Recursos'; // audios y videos -> media
    const fd = new FormData();
    fd.append('file', file);
    fd.append('dest', dest);
    try {
      const { data } = await axios.post(UPLOAD_URL, fd, {
        headers: { ...authHeaders(), 'Content-Type': 'multipart/form-data' }
      });
      return data?.url; // p.ej. https://.../assets/media/Recursos/nombre.ext
    } catch (e) {
      console.error(e);
      throw new Error('No se pudo subir el archivo.');
    }
  }

  /** === Submit (POST/PUT) === */
  async function onSubmit() {
    if (!validate()) return;

    saving.value = true;
    try {
      // 1) Subir archivos nuevos y sustituir url + asegurar tipo & fecha
      for (const r of form.recursos) {
        if (r.__file) {
          const url = await uploadResource(r.__file);
          r.url = url;
          r.tipo = autoType(url);
          r.fecha = todayISO();
          r.__file = null;
          r._previewUrl = null;
        } else if (r.url) {
          r.tipo = autoType(r.url);
          if (!r.fecha) r.fecha = todayISO();
        }
      }

      // 2) Armar payload (tipo/fecha automáticos)
      const body = payload();

      if (isEditing.value && form._id) {
        const { data } = await axios.put(`${API_BASE}/${form._id}`, body, { headers: authHeaders() });
        const saved = normalizeTecnica(data?.data ?? data);
        upsertLocal(saved);
        hideModal('form'); await refreshList();
        toast('Técnica actualizada.');
      } else {
        const { data } = await axios.post(API_BASE, body, { headers: authHeaders() });
        const saved = normalizeTecnica(data?.data ?? data);
        prependLocal(saved);
        hideModal('form'); await refreshList();
        toast('Técnica registrada.');
      }
    } catch (e) {
      console.error(e);
      toast(e?.response?.data?.message || 'Ocurrió un error al guardar.', 'error');
    } finally {
      saving.value = false;
    }
  }

  function payload() {
    // Resolver categoría final (select + “Otro”)
    const categoriaFinal = form.categoria === 'Otro'
      ? (form.categoria_custom || '').trim()
      : (form.categoria || '').trim();

    const recursos = form.recursos
      .filter(r => r.url) // solo los que realmente tienen archivo
      .map(r => ({
        _id: r._id, // opcional para PUT
        url: r.url,
        descripcion: r.descripcion || '',
        tipo: r.tipo || autoType(r.url),  // auto
        fecha: r.fecha || todayISO(),     // auto
      }));

    return {
      nombre: form.nombre,
      descripcion: form.descripcion || '',
      dificultad: form.dificultad || '',
      duracion: form.duracion ?? null,
      categoria: categoriaFinal,
      recursos,
      // calificaciones fuera de este flujo
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

  /** === Eliminar técnica === */
  async function confirmDelete(item) {
    const Swal = (await import('sweetalert2')).default;
    await import('sweetalert2/dist/sweetalert2.min.css');
    const result = await Swal.fire({
      title: '¿Eliminar técnica?',
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
    items, isLoading, hasMore, filteredItems,
    searchQuery, onInstantSearch, clearSearch,
    getId, isImage, isVideo, isAudio, autoType,
    viewModalRef, formModalRef, hideModal,
    selected, ui, viewToggle, isEditing, saving, form, categorias,
    loadMore,
    openView, openCreate, openEdit,
    addRecurso, removeRecurso, toggleRecurso, onPickFile, clearResourceFile,
    onSubmit, confirmDelete, modifyFromView, deleteFromView,
  };
}
