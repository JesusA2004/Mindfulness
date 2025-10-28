// src/assets/js/useRecompensasCrud.js
import { ref, reactive, computed, onMounted, nextTick } from 'vue';
import Modal from 'bootstrap/js/dist/modal';
import axios from 'axios';

import {
  apiBase, authHeaders, getId,
  toDateInputValue, formatDate,
  makeDebouncer, toast, setupBsTooltips,
  fetchPaginated, isRequired, isPositiveInt
} from '@/assets/js/crudUtils';

const API_BASE     = apiBase('/recompensas');
const USERS_API    = apiBase('/users');     // users -> puntos + (persona con eager load)
const PERSONAS_API = apiBase('/personas');  // fallback para persona

export function useRecompensasCrud() {
  /* ===== Estado ===== */
  const items     = ref([]);
  const isLoading = ref(true);
  const hasMore   = ref(false);
  const page      = ref(1);
  const perPage   = 20;

  /* ===== Buscador ===== */
  const searchQuery = ref('');
  const debounce = makeDebouncer(120);
  const onInstantSearch = () => debounce(() => {});
  const clearSearch = () => (searchQuery.value = '');

  /* ===== Selección / form ===== */
  const selected  = ref(null);
  const isEditing = ref(false);
  const saving    = ref(false);

  /* Caches */
  const userCacheById      = new Map();
  const personaCacheById   = new Map();
  const personaCacheByMat  = new Map();
  let personasIndexLoaded  = false;

  const ui = reactive({});
  const viewToggle = reactive({ meta: false, canjeo: true, qOpen: [] });

  const canjeosLoading = ref(false);

  const form = reactive({
    _id: null,
    nombre: '',
    descripcion: '',
    puntos_necesarios: null,
    stock: null
  });

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

  /* ===== Fetch & normalización ===== */
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
      usuario_id:  c?.usuario_id ?? null,
      persona_id:  c?.persona_id ?? null,
      matricula:   c?.matricula  ?? null,
      fechaCanjeo: toDateInputValue(c?.fechaCanjeo) || ''
    })) : [];
    return {
      ...raw,
      _id: id, id,
      nombre: raw?.nombre ?? '',
      descripcion: raw?.descripcion ?? '',
      puntos_necesarios: Number.isInteger(raw?.puntos_necesarios) ? raw.puntos_necesarios : null,
      stock: Number.isInteger(raw?.stock) ? raw.stock : 0,
      canjeo
    };
  }

  /* ===== Paginación ===== */
  function loadMore() { page.value += 1; fetchItems({ append: true }); }

  /* ===== Filtro por nombre ===== */
  const filteredItems = computed(() => {
    const q = searchQuery.value.toLowerCase().trim();
    if (!q) return items.value;
    return items.value.filter(it => (it.nombre || '').toLowerCase().includes(q));
  });

  /* ===== Helpers stock ===== */
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

  /* ===== Abrir/editar/crear ===== */
  async function openView(item) {
    selected.value = normalizeRecompensa({ ...item });
    viewToggle.meta   = false;
    viewToggle.canjeo = true;
    canjeosLoading.value = true;

    try {
      await enrichSelectedCanjeos();
      await showConsultSwal();
    } catch (err) {
      console.error(err);
      toast('No fue posible enriquecer los canjeos.', 'error');
    } finally {
      canjeosLoading.value = false;
    }
  }

  function openCreate() { isEditing.value = false; resetForm(); formModal?.show(); }
  function openEdit(item) { isEditing.value = true; setForm(normalizeRecompensa(item)); formModal?.show(); }
  function hideModal(kind) { if (kind === 'view') viewModal?.hide(); if (kind === 'form') formModal?.hide(); }

  function resetForm() { form._id = null; form.nombre = ''; form.descripcion = ''; form.puntos_necesarios = null; form.stock = 0; }
  function setForm(item) {
    form._id = getId(item);
    form.nombre = item.nombre ?? '';
    form.descripcion = item.descripcion ?? '';
    form.puntos_necesarios = Number.isInteger(item.puntos_necesarios) ? item.puntos_necesarios : null;
    form.stock = Number.isInteger(item.stock) ? item.stock : 0;
  }

  /* ===== Helpers fetch (Users + Personas) ===== */
  async function getUserById(userId) {
    if (!userId) return null;
    if (userCacheById.has(userId)) return userCacheById.get(userId);
    const { data } = await axios.get(`${USERS_API}/${userId}`, { headers: authHeaders() });
    const user = data?.data ?? data ?? null;
    if (user) userCacheById.set(userId, user);
    return user;
  }

  async function getPersonaById(personaId) {
    if (!personaId) return null;
    if (personaCacheById.has(personaId)) return personaCacheById.get(personaId);
    const { data } = await axios.get(`${PERSONAS_API}/${personaId}`, { headers: authHeaders() });
    const persona = data?.persona ?? data?.data ?? data ?? null;
    if (persona) {
      personaCacheById.set(personaId, persona);
      if (persona?.matricula) personaCacheByMat.set(String(persona.matricula), persona);
    }
    return persona;
  }

  async function ensurePersonasIndexLoaded() {
    if (personasIndexLoaded) return;
    const { data } = await axios.get(PERSONAS_API, { headers: authHeaders() });
    const registros = data?.registros ?? [];
    for (const p of registros) {
      const id = p?._id || p?.id;
      if (id) personaCacheById.set(id, p);
      if (p?.matricula) personaCacheByMat.set(String(p.matricula), p);
    }
    personasIndexLoaded = true;
  }

  async function getPersonaByMatricula(matricula) {
    if (!matricula) return null;
    const key = String(matricula);
    if (personaCacheByMat.has(key)) return personaCacheByMat.get(key);
    await ensurePersonasIndexLoaded();
    return personaCacheByMat.get(key) ?? null;
  }

  /* ===== Cohorte helpers ===== */
  function parseCohorteAny(input) {
    if (!input) return { carrera: null, cuatrimestre: null, grupo: null };
    let s = input;
    if (Array.isArray(input)) s = input[0] ?? '';
    if (typeof s !== 'string') return { carrera: null, cuatrimestre: null, grupo: null };
    const raw = s.trim();

    const r1 = /^([A-Za-zÁÉÍÓÚÑáéíóúñ]{2,})\s*[-\s]?(\d{1,2})\s*[-\s]?([A-Za-z])$/;
    const m1 = raw.match(r1);
    if (m1) return { carrera: m1[1].toUpperCase(), cuatrimestre: m1[2], grupo: m1[3].toUpperCase() };

    const r2 = /^([A-Za-zÁÉÍÓÚÑáéíóúñ]{2,})\s*[-\s]?(\d{1,2})\s*([A-Za-z])\b/;
    const m2 = raw.match(r2);
    if (m2) return { carrera: m2[1].toUpperCase(), cuatrimestre: m2[2], grupo: m2[3].toUpperCase() };

    const r3 = /^([A-Za-zÁÉÍÓÚÑáéíóúñ]{2,})[-\s]+(\d{1,2})\s+([A-Za-z])$/;
    const m3 = raw.match(r3);
    if (m3) return { carrera: m3[1].toUpperCase(), cuatrimestre: m3[2], grupo: m3[3].toUpperCase() };

    return { carrera: raw, cuatrimestre: null, grupo: null };
  }

  function buildCohorteLabel(persona, parsed) {
    if (persona?.cohorte) {
      if (Array.isArray(persona.cohorte)) return String(persona.cohorte[0] ?? '').trim() || '—';
      const s = String(persona.cohorte).trim();
      if (s) return s;
    }
    if (parsed && (parsed.carrera || parsed.cuatrimestre || parsed.grupo)) {
      const parts = [parsed.carrera, parsed.cuatrimestre, parsed.grupo].filter(Boolean);
      if (parts.length) return parts.join(' ');
    }
    const partsOld = [persona?.carrera, persona?.cuatrimestre, persona?.grupo].filter(Boolean);
    if (partsOld.length) return partsOld.join(' ');
    return '—';
  }

  function nombreCompleto(persona, fallbackName) {
    const np = persona?.nombre || '';
    const ap = persona?.apellidoPaterno || '';
    const am = persona?.apellidoMaterno || '';
    const joined = [np, ap, am].filter(Boolean).join(' ').trim();
    return joined || (fallbackName || '—');
  }

  /* ===== Enriquecer canjeos (Users + Persona) con mensajes claros ===== */
  async function enrichSelectedCanjeos() {
    if (!selected.value) return;
    const entries  = Array.isArray(selected.value.canjeo) ? selected.value.canjeo : [];
    const enriched = [];

    for (const c of entries) {
      try {
        let user = null;
        let persona = null;
        let resolutionNote = null;

        // 1) User (trae persona con eager load)
        if (c.usuario_id) {
          user = await getUserById(c.usuario_id);
          persona = user?.persona ?? null;
        }

        // 2) Si no vino persona, intenta persona_id directo
        if (!persona && c.persona_id) {
          persona = await getPersonaById(c.persona_id);
        }

        // 3) Si tampoco, intenta por matrícula (si la trae el canjeo)
        if (!persona && c.matricula) {
          persona = await getPersonaByMatricula(c.matricula);
          if (!persona) {
            resolutionNote = `No se encontró un alumno con la matrícula ${c.matricula}.`;
          }
        }

        // 4) Si de plano no hay nada, deja nota genérica
        if (!persona && !resolutionNote) {
          resolutionNote = 'No fue posible resolver los datos del alumno para este canjeo.';
        }

        const parsed = parseCohorteAny(persona?.cohorte);
        const cohorteLabel = buildCohorteLabel(persona, parsed);

        enriched.push({
          usuario_id:  c.usuario_id ?? user?._id ?? user?.id ?? null,
          persona_id:  c.persona_id ?? persona?._id ?? persona?.id ?? null,
          fechaCanjeo: c.fechaCanjeo || null,
          matricula:   persona?.matricula || user?.matricula || (c.matricula ?? '—'),
          nombreCompleto: persona ? nombreCompleto(persona, user?.name) : (user?.name || '—'),
          cohorteLabel,
          resolutionNote
        });
      } catch (_e) {
        enriched.push({
          usuario_id:  c.usuario_id ?? null,
          persona_id:  c.persona_id ?? null,
          fechaCanjeo: c.fechaCanjeo || null,
          matricula:   c.matricula ?? '—',
          nombreCompleto: '—',
          cohorteLabel: '—',
          resolutionNote: c.matricula
            ? `No se encontró un alumno con la matrícula ${c.matricula}.`
            : 'No fue posible resolver los datos del alumno para este canjeo.'
        });
      }
    }

    selected.value.canjeoEnriquecido = enriched;
  }

  /* ===== SweetAlert Consulta (con scroll interno + buscador) ===== */
  async function showConsultSwal() {
    const Swal = (await import('sweetalert2')).default;
    await import('sweetalert2/dist/sweetalert2.min.css');

    const esc = (s) => (s == null ? '' : String(s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;'));

    const badgeClass = stockBadgeClass(selected.value?.stock ?? 0);
    const badgeText  = stockLabel(selected.value?.stock ?? 0);
    const rowsData   = selected.value?.canjeoEnriquecido || [];
    const total      = rowsData.length;

    const noCanjeosHTML = `
      <div class="alert alert-light border d-flex align-items-center gap-2 mt-2">
        <i class="bi bi-inbox text-secondary fs-4"></i>
        <div><strong>Sin canjeos registrados.</strong> Esta recompensa aún no ha sido canjeada por ningún alumno.</div>
      </div>
    `;

    const searchHTML = total > 0 ? `
      <div class="mw-search input-group input-group-sm">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input id="mw-canjeo-search" type="search" class="form-control" placeholder="Buscar por matrícula…">
        <button id="mw-canjeo-clear" class="btn btn-outline-secondary" type="button" style="display:none;">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>
      <div id="mw-count" class="small text-muted mt-1">Mostrando ${total} de ${total}</div>
    ` : '';

    const rows = rowsData.map((c, i) => `
      <tr data-row="${i}" data-matricula="${esc((c.matricula || '').toLowerCase())}">
        <td>${esc(c.matricula || '—')}</td>
        <td>
          ${esc(c.nombreCompleto || '—')}
          ${c?.resolutionNote ? `<div class="text-danger small mt-1">${esc(c.resolutionNote)}</div>` : ''}
        </td>
        <td>${esc(c.cohorteLabel || '—')}</td>
        <td>${esc(formatDate(c.fechaCanjeo) || '—')}</td>
      </tr>
    `).join('');

    const table = total > 0 ? `
      <div class="mw-scroll-area mt-2">
        <table class="table table-sm align-middle">
          <thead class="table-light sticky-top">
            <tr>
              <th>Matrícula</th>
              <th>Nombre</th>
              <th>Cohorte</th>
              <th>Fecha de canjeo</th>
            </tr>
          </thead>
          <tbody id="mw-tbody">${rows}</tbody>
        </table>
      </div>
      <div id="mw-no-results" class="alert alert-light border d-flex align-items-center gap-2 mt-2" style="display:none;">
        <i class="bi bi-emoji-neutral text-secondary fs-5"></i>
        <div><strong>Sin coincidencias.</strong> Ajusta tu búsqueda por matrícula.</div>
      </div>
    ` : noCanjeosHTML;

    const html = `
      <style>
        .swal2-popup.mw-consulta{ border-radius:20px; padding:1.25rem; max-height:90vh; overflow:hidden; }
        .mw-head{ display:flex; align-items:center; gap:12px; margin-bottom:.25rem; }
        .mw-head .avatar-pill{
          width:44px; height:44px; display:grid; place-items:center; border-radius:999px;
          background:#eef6ff; color:#2c4c86; box-shadow: inset 0 0 0 1px rgba(27,59,111,.08);
          font-size:1.25rem;
        }
        .meta-wrap{ display:flex; flex-wrap:wrap; gap:.5rem; margin:.5rem 0 .75rem; }
        .meta-chip{
          background:#eef6ff; color:#2c4c86; border:1px solid #e6eefc;
          padding:.35rem .65rem; border-radius:999px; font-size:.85rem;
        }
        .mw-toolbar{ display:flex; align-items:center; justify-content:space-between; gap:.75rem; flex-wrap:wrap; }
        .mw-search{ max-width:360px; }

        /* Scroll interno */
        .mw-scroll-area{ max-height:55vh; overflow-y:auto; scrollbar-width:thin; scrollbar-color:#9fb6e3 #f0f5ff; }
        .mw-scroll-area::-webkit-scrollbar{ width:8px; }
        .mw-scroll-area::-webkit-scrollbar-thumb{ background-color:#9fb6e3; border-radius:6px; }
        .mw-scroll-area::-webkit-scrollbar-track{ background-color:#f0f5ff; }
        thead.sticky-top th{ background:#f8f9fa; position:sticky; top:0; z-index:1; }

        /* Botonera estilo “segunda imagen” */
        .mw-actions{ display:flex; align-items:center; justify-content:space-between; gap:.75rem; margin-top:1rem; }
        .mw-actions-left{ display:flex; gap:.5rem; }
        .btn-outline-blue{ color:#0d6efd; border:1px solid #0d6efd; background:#fff; }
        .btn-outline-blue:hover{ background:#eaf2ff; }
        .btn-outline-red{ color:#dc3545; border:1px solid #dc3545; background:#fff; }
        .btn-outline-red:hover{ background:#fff1f2; }
        .btn-gray{ color:#fff; background:#6c757d; border:1px solid #6c757d; }
        .btn-gray:hover{ filter:brightness(.95); }
        .mw-btn{ padding:.55rem .9rem; border-radius:.5rem; font-weight:600; }
      </style>

      <div class="mw-head">
        <div class="avatar-pill"><i class="bi bi-gift"></i></div>
        <div><h5 class="mb-0 fw-bold">${esc(selected.value?.nombre) || 'Detalle de la recompensa'}</h5></div>
      </div>

      <div class="meta-wrap">
        <div class="meta-chip"><span class="badge rounded-pill ${esc(badgeClass)}">${esc(badgeText)}</span></div>
        <div class="meta-chip"><i class="bi bi-stars me-1"></i>${esc(selected.value?.puntos_necesarios ?? '—')} punto(s)</div>
        ${selected.value?.descripcion ? `<div class="meta-chip"><i class="bi bi-info-circle me-1"></i>${esc(selected.value.descripcion)}</div>` : ''}
      </div>

      ${total > 0 ? `<div class="mw-toolbar">${searchHTML}</div>` : ''}

      ${table}

      <div class="mw-actions">
        <div class="mw-actions-left">
          <button id="mw-btn-mod" class="mw-btn btn-outline-blue"><i class="bi bi-pencil-square me-1"></i>Modificar</button>
          <button id="mw-btn-del" class="mw-btn btn-outline-red"><i class="bi bi-trash me-1"></i>Eliminar</button>
        </div>
        <div class="mw-actions-right">
          <button id="mw-btn-close" class="mw-btn btn-gray">Cerrar</button>
        </div>
      </div>
    `;

    await Swal.fire({
      html,
      customClass: { popup: 'mw-consulta' },
      width: 980,
      showConfirmButton: false,
      willOpen: (el) => {
        // Buscador por matrícula (si hay canjeos)
        const input = el.querySelector('#mw-canjeo-search');
        const clear = el.querySelector('#mw-canjeo-clear');
        const tbody = el.querySelector('#mw-tbody');
        const count = el.querySelector('#mw-count');
        const boxNoResults = el.querySelector('#mw-no-results');

        if (input && tbody) {
          const totalRows = tbody.querySelectorAll('tr').length;
          const update = () => {
            const q = (input.value || '').trim().toLowerCase();
            let visible = 0;
            tbody.querySelectorAll('tr').forEach(tr => {
              const m = tr.getAttribute('data-matricula') || '';
              const show = q === '' || m.includes(q);
              tr.style.display = show ? '' : 'none';
              if (show) visible++;
            });
            if (count) count.textContent = `Mostrando ${visible} de ${totalRows}`;
            if (clear) clear.style.display = q ? '' : 'none';
            if (boxNoResults) boxNoResults.style.display = visible === 0 ? '' : 'none';
          };
          input.addEventListener('input', update);
          if (clear) clear.addEventListener('click', () => { input.value = ''; input.dispatchEvent(new Event('input')); });
        }

        // Botones
        el.querySelector('#mw-btn-mod')?.addEventListener('click', async () => {
          const item = { ...selected.value };
          await Swal.close();
          await nextTick();
          openEdit(item);
        });
        el.querySelector('#mw-btn-del')?.addEventListener('click', async () => {
          const item = { ...selected.value };
          await Swal.close();
          await nextTick();
          await confirmDelete(item);
        });
        el.querySelector('#mw-btn-close')?.addEventListener('click', () => Swal.close());
      }
    });
  }

  /* ===== Guardar ===== */
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
      const body = {
        nombre: form.nombre,
        descripcion: form.descripcion || null,
        puntos_necesarios: form.puntos_necesarios ?? 0,
        stock: form.stock ?? 0
      };
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

  function upsertLocal(saved) {
    const id = getId(saved); if (!id) return;
    const idx = items.value.findIndex(x => getId(x) === id);
    if (idx >= 0) items.value.splice(idx, 1, { ...items.value[idx], ...saved });
    else items.value.unshift(saved);
  }
  function prependLocal(saved) { items.value.unshift(saved); }
  async function refreshList() { page.value = 1; await fetchItems({ append: false }); }

  /* ===== Eliminar ===== */
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

  /* ===== Exponer ===== */
  return {
    // estado y listas
    items, isLoading, hasMore, filteredItems, page,
    // búsqueda
    searchQuery, onInstantSearch, clearSearch,
    // utilidades
    getId, formatDate,
    // helpers stock (asegurados como funciones)
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
    onSubmit, confirmDelete,
  };
}
