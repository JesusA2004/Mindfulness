// src/assets/js/useTecnicasCrud.js
import { ref, reactive, computed, onMounted, nextTick } from 'vue';
import Modal from 'bootstrap/js/dist/modal';
import axios from 'axios';

import {
  apiBase, authHeaders, getId, makeDebouncer, toast, setupBsTooltips,
  fetchPaginated, randomKey, isRequired
} from '@/assets/js/crudUtils';

const API_BASE = apiBase('/tecnicas');

// Categorías predeterminadas
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

// ====== Helpers de tipos/extensiones/embeds ======
function isImage(url)  { return /\.(png|jpe?g|gif|webp|avif|svg)(\?.*)?$/i.test(url || ''); }
function isVideo(url)  { return /\.(mp4|webm|ogg|mov|m4v)(\?.*)?$/i.test(url || ''); }
function isAudio(url)  { return /\.(mp3|wav|ogg|m4a)(\?.*)?$/i.test(url || ''); }

function isYouTube(url)  { return /(?:youtube\.com\/watch\?v=|youtu\.be\/)/i.test(url || ''); }
function isVimeo(url)    { return /vimeo\.com\/\d+/i.test(url || ''); }
function isSoundCloud(u) { return /soundcloud\.com\//i.test(u || ''); }
function isSpotify(u)    { return /open\.spotify\.com\//i.test(u || ''); }

function isEmbeddable(u) { return isYouTube(u) || isVimeo(u) || isSoundCloud(u) || isSpotify(u); }

function toEmbedUrl(u) {
  if (!u) return '';
  // YouTube
  if (isYouTube(u)) {
    const idMatch = u.match(/(?:v=|youtu\.be\/)([A-Za-z0-9_-]{6,})/);
    const id = idMatch ? idMatch[1] : '';
    return id ? `https://www.youtube.com/embed/${id}` : u;
  }
  // Vimeo
  if (isVimeo(u)) {
    const idMatch = u.match(/vimeo\.com\/(\d+)/);
    const id = idMatch ? idMatch[1] : '';
    return id ? `https://player.vimeo.com/video/${id}` : u;
  }
  // SoundCloud: usar widget o-url
  if (isSoundCloud(u)) {
    const o = encodeURIComponent(u);
    return `https://w.soundcloud.com/player/?url=${o}&auto_play=false&hide_related=false&show_comments=true&show_user=true&show_reposts=false&visual=true`;
  }
  // Spotify (tracks/albums/playlists)
  if (isSpotify(u)) {
    // convierte open.spotify.com/{type}/{id} -> embed/{type}/{id}
    return u.replace('open.spotify.com/', 'open.spotify.com/embed/');
  }
  return u;
}

function autoType(nameOrUrl) {
  const s = (nameOrUrl || '').toLowerCase();
  if (isImage(s)) return 'Imagen';
  if (isVideo(s)) return 'Video';
  if (isAudio(s)) return 'Audio';
  if (isEmbeddable(s)) {
    // mapear por proveedor
    if (isYouTube(s) || isVimeo(s)) return 'Video';
    if (isSoundCloud(s) || isSpotify(s)) return 'Audio';
  }
  return 'Documento';
}

function todayISO() {
  const d = new Date();
  return d.toISOString().slice(0,10);
}

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
    recursos: [], // [{ _id, url, descripcion, tipo(auto), fecha(auto), __key }]
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
      tipo: r.tipo || autoType(r.url),
      fecha: r.fecha || todayISO(),
    }));
  }

  /** === Recursos (links) === */
  function addRecurso() {
    form.recursos.push({
      __key: randomKey(),
      _id: undefined,
      url: '',
      descripcion: '',
      tipo: 'Documento',     // valor inicial, se ajusta al escribir URL
      fecha: todayISO(),     // auto
    });
    const idx = form.recursos.length - 1;
    ui.rOpen[idx] = true;
  }

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

  function onUrlChange(r) {
    // Recalcula tipo/fecha al cambiar URL
    r.tipo = autoType(r.url);
    if (!r.fecha) r.fecha = todayISO();
  }

  /** === Validaciones === */
  function validate() {
    if (!isRequired(form.nombre)) { toast('El nombre es obligatorio.', 'error'); return false; }
    if (form.duracion != null && String(form.duracion).trim() !== '' && Number(form.duracion) <= 0) {
      toast('La duración debe ser un entero positivo.', 'error'); return false;
    }
    // Validación ligera de URL si hay recursos
    for (const r of form.recursos) {
      if (!r.url) continue;
      try {
        new URL(r.url);
      } catch {
        toast('Hay un recurso con URL inválida.', 'error');
        return false;
      }
    }
    return true;
  }

  /** === Submit (POST/PUT) === */
  async function onSubmit() {
    if (!validate()) return;

    saving.value = true;
    try {
      const body = payload();

      if (isEditing.value && form._id) {
        const { data } = await axios.put(`${API_BASE}/${form._id}`, body, { headers: authHeaders() });
        const saved = normalizeTecnica(data?.data ?? data?.tecnica ?? data);
        upsertLocal(saved);
        hideModal('form'); await refreshList();
        toast('Técnica actualizada.');
      } else {
        const { data } = await axios.post(API_BASE, body, { headers: authHeaders() });
        const saved = normalizeTecnica(data?.data ?? data?.tecnica ?? data);
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
    // Resolver categoría final
    const categoriaFinal = form.categoria === 'Otro'
      ? (form.categoria_custom || '').trim()
      : (form.categoria || '').trim();

    const recursos = form.recursos
      .filter(r => (r.url || '').trim() !== '') // solo si hay URL
      .map(r => ({
        _id: r._id, // opcional para PUT
        url: r.url.trim(),
        descripcion: r.descripcion || '',
        // enviamos tipo/fecha por conveniencia; backend también los recalcula por seguridad
        tipo: r.tipo || autoType(r.url),
        fecha: r.fecha || todayISO(),
      }));

    return {
      nombre: form.nombre,
      descripcion: form.descripcion || '',
      dificultad: form.dificultad || '',
      duracion: form.duracion ?? null,
      categoria: categoriaFinal,
      recursos,
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
    // estado y listas
    items, isLoading, hasMore, filteredItems,
    // búsqueda
    searchQuery, onInstantSearch, clearSearch,
    // utilidad
    getId, isImage, isVideo, isAudio, autoType, isEmbeddable, toEmbedUrl,
    // modales y refs
    viewModalRef, formModalRef, hideModal,
    // selección y UI
    selected, ui, viewToggle, isEditing, saving, form, categorias,
    // acciones de lista/paginación
    loadMore,
    // abrir/editar/ver
    openView, openCreate, openEdit,
    // recursos (links)
    addRecurso, removeRecurso, toggleRecurso, onUrlChange,
    // submit/eliminar
    onSubmit, confirmDelete, modifyFromView, deleteFromView,
  };
}
