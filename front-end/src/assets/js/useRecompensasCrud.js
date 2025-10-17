// src/assets/js/useRecompensasCrud.js
import { ref, reactive, computed, onMounted, nextTick } from 'vue';
import Modal from 'bootstrap/js/dist/modal';
import axios from 'axios';

// Utilidades comunes
import {
  apiBase, authHeaders, getId,
  toDateInputValue, formatDate,
  makeDebouncer, toast, setupBsTooltips,
  fetchPaginated, isRequired, isPositiveInt
} from '@/assets/js/crudUtils';

const API_BASE = apiBase('/recompensas');
// Endpoints auxiliares para la doble consulta
const USERS_API = apiBase('/users');
const PERSONAS_API = apiBase('/personas');

export function useRecompensasCrud() {
  /** === Estado raíz === */
  const items = ref([]);
  const isLoading = ref(true);
  const hasMore = ref(false);
  const page = ref(1);
  const perPage = 20;

  /** === Buscador (debounce) === */
  const searchQuery = ref('');
  const debounce = makeDebouncer(120);
  const onInstantSearch = () => debounce(() => {});
  const clearSearch = () => (searchQuery.value = '');

  /** === Selección y formulario === */
  const selected = ref(null);
  const isEditing = ref(false);
  const saving = ref(false);

  // Cache para no repetir consultas user/persona
  const userCache = new Map();     // key: userId -> user doc (incluye persona_id, matricula, name, etc)
  const personaCache = new Map();  // key: personaId -> persona doc

  const ui = reactive({});
  const viewToggle = reactive({
    meta: false,
    canjeo: true,
  });

  const canjeosLoading = ref(false);

  // Formulario del admin (sin canjeo)
  const form = reactive({
    _id: null,
    nombre: '',
    descripcion: '',
    puntos_necesarios: null,
    stock: null
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

  /** === Fetch & Normalización === */
  async function fetchItems({ append = false } = {}) {
    try {
      isLoading.value = true;
      const { list, hasMore: hm } = await fetchPaginated(API_BASE, {
        page: page.value, perPage, headers: authHeaders()
      });
      hasMore.value = hm;
      const normalized = list.map(normalizeRecompensa);
      items.value = append ? [...items.value, ...normalized] : normalized;
    } catch (e) {
      console.error(e);
      toast('No fue posible cargar las recompensas.', 'error');
    } finally {
      isLoading.value = false;
    }
  }

  function normalizeRecompensa(raw) {
    const id = getId(raw);
    const canjeo = Array.isArray(raw?.canjeo) ? raw.canjeo.map(c => ({
      usuario_id: c?.usuario_id ?? '',
      fechaCanjeo: toDateInputValue(c?.fechaCanjeo) || ''
    })) : [];
    return {
      ...raw,
      _id: id,
      id,
      nombre: raw?.nombre ?? '',
      descripcion: raw?.descripcion ?? '',
      puntos_necesarios: Number.isInteger(raw?.puntos_necesarios) ? raw.puntos_necesarios : null,
      stock: Number.isInteger(raw?.stock) ? raw.stock : 0,
      canjeo
    };
  }

  /** === Paginación === */
  function loadMore() { page.value += 1; fetchItems({ append: true }); }

  /** === Filtro por nombre === */
  const filteredItems = computed(() => {
    const q = searchQuery.value.toLowerCase().trim();
    if (!q) return items.value;
    return items.value.filter(it => (it.nombre || '').toLowerCase().includes(q));
  });

  /** === Helpers visuales de stock === */
  function stockBadgeClass(stock) {
    if (stock <= 0) return 'bg-danger';
    if (stock <= 5) return 'bg-warning text-dark';
    return 'bg-success';
  }
  function stockLabel(stock) {
    if (stock <= 0) return 'Agotada';
    if (stock <= 5) return `Bajo stock (${stock})`;
    return `Disponible (${stock})`;
  }
  function stockTitle(stock) {
    if (stock <= 0) return 'Sin unidades disponibles';
    if (stock <= 5) return 'Quedan pocas unidades';
    return 'Stock suficiente';
  }

  /** === Abrir/Cerrar Modales === */
  function openView(item) {
    selected.value = normalizeRecompensa({ ...item });
    viewToggle.meta = false;
    viewToggle.canjeo = true;
    canjeosLoading.value = true;
    // Enriquecer canjeos (doble consulta)
    enrichSelectedCanjeos()
      .catch(err => {
        console.error(err);
        toast('No fue posible enriquecer los canjeos.', 'error');
      })
      .finally(() => {
        canjeosLoading.value = false;
      });
    viewModal?.show();
  }

  function openCreate() {
    isEditing.value = false;
    resetForm();
    formModal?.show();
  }

  function openEdit(item) {
    isEditing.value = true;
    setForm(normalizeRecompensa(item));
    formModal?.show();
  }

  function hideModal(kind) { if (kind === 'view') viewModal?.hide(); if (kind === 'form') formModal?.hide(); }

  /** === Form: set/reset === */
  function resetForm() {
    form._id = null;
    form.nombre = '';
    form.descripcion = '';
    form.puntos_necesarios = null;
    form.stock = 0;
  }

  function setForm(item) {
    form._id = getId(item);
    form.nombre = item.nombre ?? '';
    form.descripcion = item.descripcion ?? '';
    form.puntos_necesarios = Number.isInteger(item.puntos_necesarios) ? item.puntos_necesarios : null;
    form.stock = Number.isInteger(item.stock) ? item.stock : 0;
  }

  /** === Helpers: obtener personaId de un user sin sintaxis inválida === */
  function getPersonaIdFromUser(user) {
    if (!user) return null;
    // Casos posibles:
    // - user.persona_id (string)
    // - user.personaId (otra convención)
    // - user.persona._id (obj embebido)
    // - user.persona.id  (otra convención)
    if (user.persona_id) return user.persona_id;
    if (user.personaId) return user.personaId;
    if (user.persona && (user.persona._id || user.persona.id)) {
      return user.persona._id || user.persona.id;
    }
    return null;
  }

  /** === Doble consulta y enriquecimiento === */
  async function getUserById(userId) {
    if (!userId) return null;
    if (userCache.has(userId)) return userCache.get(userId);
    const { data } = await axios.get(`${USERS_API}/${userId}`, { headers: authHeaders() });
    const user = data?.data ?? data;
    userCache.set(userId, user);
    return user;
  }

  async function getPersonaById(personaId) {
    if (!personaId) return null;
    if (personaCache.has(personaId)) return personaCache.get(personaId);
    const { data } = await axios.get(`${PERSONAS_API}/${personaId}`, { headers: authHeaders() });
    const persona = data?.data ?? data;
    personaCache.set(personaId, persona);
    return persona;
  }

  function nombreCompleto(persona, fallbackName) {
    const np = persona?.nombre || '';
    const ap = persona?.apellidoPaterno || '';
    const am = persona?.apellidoMaterno || '';
    const joined = [np, ap, am].filter(Boolean).join(' ').trim();
    return joined || (fallbackName || '');
  }

  async function enrichSelectedCanjeos() {
    if (!selected.value) return;
    const entries = Array.isArray(selected.value.canjeo) ? selected.value.canjeo : [];
    const enriched = [];
    for (const c of entries) {
      try {
        const user = await getUserById(c.usuario_id);
        const personaId = getPersonaIdFromUser(user);
        const persona = await getPersonaById(personaId);

        enriched.push({
          usuario_id: c.usuario_id || null,
          fechaCanjeo: c.fechaCanjeo || null,
          matricula: user?.matricula || persona?.matricula || null,
          nombreCompleto: nombreCompleto(persona, user?.name),
          carrera: persona?.carrera || null,
          cuatrimestre: persona?.cuatrimestre || null,
          grupo: persona?.grupo || null
        });
      } catch (_e) {
        // Si falla alguno, agregamos con lo que haya
        enriched.push({
          usuario_id: c.usuario_id || null,
          fechaCanjeo: c.fechaCanjeo || null,
          matricula: null,
          nombreCompleto: null,
          carrera: null,
          cuatrimestre: null,
          grupo: null
        });
      }
    }
    // Guardamos en el seleccionado para que el template lo muestre
    selected.value.canjeoEnriquecido = enriched;
  }

  /** === Submit (sin canjeo en payload) === */
  async function onSubmit() {
    if (!isRequired(form.nombre)) { toast('El nombre es obligatorio.', 'error'); return; }
    if (form.puntos_necesarios == null || !isPositiveInt(form.puntos_necesarios)) {
      toast('Los puntos necesarios deben ser un entero positivo (o 0).', 'error'); return;
    }
    if (form.stock == null || form.stock < 0 || !Number.isInteger(form.stock)) {
      toast('El stock debe ser un entero mayor o igual a 0.', 'error'); return;
    }

    saving.value = true;
    try {
      const body = payload(); // sin canjeo
      if (isEditing.value && form._id) {
        const { data } = await axios.put(`${API_BASE}/${form._id}`, body, { headers: authHeaders() });
        const saved = normalizeRecompensa(data?.data ?? data);
        upsertLocal(saved);
        hideModal('form'); await refreshList();
        toast('Recompensa actualizada.');
      } else {
        const { data } = await axios.post(API_BASE, body, { headers: authHeaders() });
        const saved = normalizeRecompensa(data?.data ?? data);
        prependLocal(saved);
        hideModal('form'); await refreshList();
        toast('Recompensa creada.');
      }
    } catch (e) {
      console.error(e);
      toast(e?.response?.data?.message || 'Ocurrió un error al guardar.', 'error');
    } finally {
      saving.value = false;
    }
  }

  function payload() {
    // El admin NO modifica canjeos
    return {
      nombre: form.nombre,
      descripcion: form.descripcion || null,
      puntos_necesarios: form.puntos_necesarios ?? 0,
      stock: form.stock ?? 0
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
    await import('sweetalert2/dist/sweetalert2.min.css');
    const result = await Swal.fire({
      title: '¿Eliminar recompensa?',
      text: 'Esta acción no se puede deshacer.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
      reverseButtons: true,
      confirmButtonColor: '#dc3545', // rojo
      cancelButtonColor: '#6c757d', // gris
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

  /** === Acciones desde modal de vista === */
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
    getId, formatDate,
    // helpers stock
    stockBadgeClass, stockLabel, stockTitle,
    // refs / modales
    viewModalRef, formModalRef, hideModal,
    // selección, formulario y UI
    selected, ui, viewToggle, isEditing, saving, form, canjeosLoading,
    // grid/paginación
    loadMore,
    // abrir/ver/editar
    openView, openCreate, openEdit,
    // submit/eliminar
    onSubmit, confirmDelete, modifyFromView, deleteFromView,
  };
}
