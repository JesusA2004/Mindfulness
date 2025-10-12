<!-- CrudPanel.vue -->
<template>
  <main class="panel-wrapper">
    <!-- ======= Toolbar: Búsqueda + Nuevo ======= -->
    <div class="container-fluid toolbar px-3 px-lg-2">
      <div class="row g-2 align-items-center">
        <div class="col-12 col-lg-8">
          <div class="input-group input-group-lg search-group shadow-sm">
            <span class="input-group-text">
              <i class="bi bi-search"></i>
            </span>

            <!-- Campo de búsqueda -->
            <input
              v-model.trim="searchQuery"
              type="search"
              class="form-control"
              placeholder="Buscar por nombre o descripción…"
              @input="onInstantSearch"
            />

            <!-- ÚNICA 'X' (botón propio) -->
            <button
              v-if="searchQuery"
              class="btn btn-link text-secondary px-3"
              @click="clearSearch"
              aria-label="Limpiar búsqueda"
            >
              <i class="bi bi-x-lg"></i>
            </button>
          </div>
        </div>

        <div class="col-12 col-lg-4 text-lg-end mt-2 mt-lg-0">
          <button class="btn btn-gradient btn-lg fw-semibold shadow-sm rounded-pill btn-new" @click="openCreate">
            <i class="bi bi-plus-lg me-1"></i> Nuevo
          </button>
        </div>
      </div>
    </div>

    <!-- ======= Grid de Cards ======= -->
    <div class="container-fluid px-3 px-lg-2">
      <div class="row g-3">
        <div
          v-for="item in filteredItems"
          :key="getId(item)"
          class="col-12 col-sm-6 col-lg-4 col-xxl-3"
        >
          <div class="card h-100 item-card shadow-sm">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <h5 class="card-title mb-0 text-truncate" :title="item.nombre">{{ item.nombre }}</h5>
                <span class="badge rounded-pill bg-status" :class="item.activo ? 'bg-success' : 'bg-secondary'">
                  {{ item.activo ? 'Activo' : 'Inactivo' }}
                </span>
              </div>
              <p class="card-text clamp-3" v-if="item.descripcion">{{ item.descripcion }}</p>
              <div class="small text-muted" v-if="item.categoria">
                <i class="bi bi-tag"></i> {{ item.categoria }}
              </div>
            </div>

            <!-- Botonera: icon-only -->
            <div class="card-footer bg-transparent border-0 pt-0 pb-3 px-3">
              <div class="d-flex gap-2">
                <button
                  class="btn btn-outline-secondary btn-sm flex-fill btn-icon-only"
                  @click="openView(item)"
                  data-bs-toggle="tooltip"
                  title="Consultar"
                  aria-label="Consultar"
                >
                  <i class="bi bi-eye"></i>
                </button>
                <button
                  class="btn btn-outline-primary btn-sm flex-fill btn-icon-only"
                  @click="openEdit(item)"
                  data-bs-toggle="tooltip"
                  title="Modificar"
                  aria-label="Modificar"
                >
                  <i class="bi bi-pencil-square"></i>
                </button>
                <button
                  class="btn btn-outline-danger btn-sm flex-fill btn-icon-only"
                  @click="confirmDelete(item)"
                  data-bs-toggle="tooltip"
                  title="Eliminar"
                  aria-label="Eliminar"
                >
                  <i class="bi bi-trash"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Vacío -->
        <div v-if="!isLoading && filteredItems.length === 0" class="col-12">
          <div class="alert alert-light border d-flex align-items-center gap-2">
            <i class="bi bi-inbox text-secondary fs-4"></i>
            <div>
              <strong>Sin resultados.</strong>
              Intenta con otra búsqueda o registra un nuevo elemento.
            </div>
          </div>
        </div>

        <!-- Skeletons -->
        <div v-if="isLoading" class="col-12 col-sm-6 col-lg-4 col-xxl-3" v-for="n in 8" :key="'sk'+n">
          <div class="card h-100 shadow-sm">
            <div class="card-body">
              <div class="placeholder-glow">
                <span class="placeholder col-8"></span>
                <p class="mt-2 mb-0">
                  <span class="placeholder col-12"></span>
                  <span class="placeholder col-11"></span>
                  <span class="placeholder col-9"></span>
                </p>
              </div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0 pb-3 px-3">
              <div class="d-flex gap-2">
                <span class="placeholder col-4"></span>
                <span class="placeholder col-4"></span>
                <span class="placeholder col-4"></span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Paginación simple -->
      <div class="d-flex justify-content-center my-4" v-if="!isLoading && hasMore">
        <button class="btn btn-outline-secondary" @click="loadMore">
          Cargar más
        </button>
      </div>
    </div>

    <!-- ======= Modales ======= -->
    <div class="modal fade" ref="viewModalRef" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header border-0">
            <h5 class="modal-title">Detalle</h5>
            <button type="button" class="btn-close" @click="hideModal('view')"></button>
          </div>
          <div class="modal-body">
            <dl class="row">
              <dt class="col-sm-3">Nombre</dt>
              <dd class="col-sm-9">{{ selected?.nombre }}</dd>
              <dt class="col-sm-3">Descripción</dt>
              <dd class="col-sm-9">{{ selected?.descripcion || '—' }}</dd>
              <dt class="col-sm-3">Categoría</dt>
              <dd class="col-sm-9">{{ selected?.categoria || '—' }}</dd>
              <dt class="col-sm-3">Estado</dt>
              <dd class="col-sm-9">
                <span class="badge" :class="selected?.activo ? 'bg-success' : 'bg-secondary'">
                  {{ selected?.activo ? 'Activo' : 'Inactivo' }}
                </span>
              </dd>
              <dt class="col-sm-3">Creado</dt>
              <dd class="col-sm-9">{{ formatDate(selected?.created_at) }}</dd>
            </dl>
          </div>
          <div class="modal-footer border-0">
            <button class="btn btn-secondary" @click="hideModal('view')">Cerrar</button>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" ref="formModalRef" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow-lg" @submit.prevent="onSubmit">
          <div class="modal-header border-0">
            <h5 class="modal-title">{{ isEditing ? 'Modificar' : 'Registrar' }}</h5>
            <button type="button" class="btn-close" @click="hideModal('form')"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Nombre <span class="text-danger">*</span></label>
              <input v-model.trim="form.nombre" type="text" class="form-control" required maxlength="150" />
            </div>
            <div class="mb-3">
              <label class="form-label">Descripción</label>
              <textarea v-model.trim="form.descripcion" rows="3" class="form-control" maxlength="500"></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label">Categoría</label>
              <select v-model="form.categoria" class="form-select">
                <option value="" disabled>Selecciona una categoría</option>
                <option value="atención-plena">Atención plena</option>
                <option value="respiración">Respiración</option>
                <option value="movimiento">Movimiento</option>
                <option value="relajación">Relajación</option>
              </select>
            </div>
            <div class="form-check form-switch mb-2">
              <input class="form-check-input" type="checkbox" role="switch" id="switchActivo" v-model="form.activo">
              <label class="form-check-label" for="switchActivo">Activo</label>
            </div>
          </div>
          <div class="modal-footer border-0">
            <button type="button" class="btn btn-outline-secondary" @click="hideModal('form')">Cancelar</button>
            <button type="submit" class="btn btn-gradient" :disabled="saving">
              <span v-if="!saving">{{ isEditing ? 'Guardar cambios' : 'Registrar' }}</span>
              <span v-else class="spinner-border spinner-border-sm ms-1" role="status" aria-hidden="true"></span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </main>
</template>

<script setup>
import { ref, reactive, computed, onMounted, nextTick } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import Modal from 'bootstrap/js/dist/modal';
import Tooltip from 'bootstrap/js/dist/tooltip';

const API_BASE = (process.env.VUE_APP_API_URL || '') + '/recompensas';

const items = ref([]);
const isLoading = ref(true);
const hasMore = ref(false);
const page = ref(1);
const perPage = 20;

const searchQuery = ref('');
let searchTimer = null;
const onInstantSearch = () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {}, 120);
};
const clearSearch = () => (searchQuery.value = '');

const selected = ref(null);
const isEditing = ref(false);
const saving = ref(false);
const form = reactive({
  _id: null,
  nombre: '',
  descripcion: '',
  categoria: '',
  activo: true,
});

const viewModalRef = ref(null);
const formModalRef = ref(null);
let viewModal, formModal;

onMounted(async () => {
  await fetchItems();
  await nextTick();
  viewModal = new Modal(viewModalRef.value, { backdrop: 'static' });
  formModal = new Modal(formModalRef.value, { backdrop: 'static' });
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => { new Tooltip(el); });
});

function authHeaders() {
  const token = localStorage.getItem('token');
  return token ? { Authorization: `Bearer ${token}` } : {};
}

async function fetchItems({ append = false } = {}) {
  try {
    isLoading.value = true;
    const params = { page: page.value, per_page: perPage };
    const { data } = await axios.get(API_BASE, { params, headers: authHeaders() });
    const list = data?.data ?? data?.registros ?? data ?? [];
    hasMore.value = !!data?.next_page_url || (Array.isArray(list) && list.length === perPage);
    items.value = append ? [...items.value, ...list] : list;
  } catch (e) {
    console.error(e);
    toast('No fue posible cargar los datos.', 'error');
  } finally {
    isLoading.value = false;
  }
}

function loadMore() { page.value += 1; fetchItems({ append: true }); }

const filteredItems = computed(() => {
  const q = searchQuery.value.toLowerCase();
  if (!q) return items.value;
  return items.value.filter(it => {
    const n = (it.nombre || '').toLowerCase();
    const d = (it.descripcion || '').toLowerCase();
    return n.includes(q) || d.includes(q);
  });
});

function openView(item) { selected.value = { ...item }; viewModal.show(); }
function openCreate() { isEditing.value = false; resetForm(); formModal.show(); }
function openEdit(item) { isEditing.value = true; setForm(item); formModal.show(); }
function hideModal(kind) { if (kind==='view') viewModal?.hide(); if (kind==='form') formModal?.hide(); }

function resetForm() {
  form._id = null; form.nombre = ''; form.descripcion = ''; form.categoria = ''; form.activo = true;
}
function setForm(item) {
  form._id = getId(item); form.nombre = item.nombre ?? ''; form.descripcion = item.descripcion ?? '';
  form.categoria = item.categoria ?? ''; form.activo = !!item.activo;
}

async function onSubmit() {
  saving.value = true;
  try {
    if (isEditing.value && form._id) {
      const { data } = await axios.put(`${API_BASE}/${form._id}`, payload(), { headers: authHeaders() });
      upsertLocal(data?.data ?? data); toast('Registro actualizado.');
    } else {
      const { data } = await axios.post(API_BASE, payload(), { headers: authHeaders() });
      prependLocal(data?.data ?? data); toast('Registro creado.');
    }
    hideModal('form');
  } catch (e) {
    console.error(e);
    toast(e?.response?.data?.message || 'Ocurrió un error al guardar.', 'error');
  } finally { saving.value = false; }
}

function payload() { return { nombre: form.nombre, descripcion: form.descripcion, categoria: form.categoria || null, activo: !!form.activo }; }
function upsertLocal(saved) { const id = getId(saved); const idx = items.value.findIndex(x => getId(x) === id); if (idx >= 0) items.value[idx] = saved; }
function prependLocal(saved) { items.value = [saved, ...items.value]; }
function getId(obj) { return obj?.id ?? obj?._id ?? obj?.uuid ?? null; }

async function confirmDelete(item) {
  const result = await Swal.fire({
    title: '¿Eliminar registro?', text: 'Esta acción no se puede deshacer.', icon: 'warning',
    showCancelButton: true, confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar',
    reverseButtons: true, confirmButtonColor: '#7a00b8', cancelButtonColor: '#6c757d',
  });
  if (!result.isConfirmed) return;
  try {
    const id = getId(item);
    await axios.delete(`${API_BASE}/${id}`, { headers: authHeaders() });
    items.value = items.value.filter(x => getId(x) !== id);
    toast('Eliminado correctamente.');
  } catch (e) { console.error(e); toast('No fue posible eliminar.', 'error'); }
}

function toast(message, type = 'success') {
  Swal.fire({ toast: true, position: 'top-end', icon: type, title: message, showConfirmButton: false, timer: 2000, timerProgressBar: true });
}
function formatDate(v) { if (!v) return '—'; const d = new Date(v); return Number.isNaN(d.getTime()) ? v : d.toLocaleString(); }
</script>

<style scoped>
/* Oculta la 'X' nativa del input type=search (dejamos SOLO nuestro botón) */
.search-group input[type="search"]::-webkit-search-cancel-button { display:none; }
.search-group input[type="search"]::-webkit-search-decoration { display:none; }
/* Edge/IE legacy (por si acaso) */
.search-group input[type="search"]::-ms-clear { display:none; width:0; height:0; }

/* Mantén tus estilos globales */
@import '@/assets/css/Crud.css';
</style>
