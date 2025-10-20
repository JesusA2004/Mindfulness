<!-- src/views/administrador/RespaldoBD.vue -->
<template>
  <main class="container py-4">
    <!-- Encabezado -->
    <div class="row mb-4">
      <div class="col-12 text-center">
        <h2 class="fw-bold animate__animated animate__fadeInDown">
          Respaldo & Restauración de Base de Datos
        </h2>
        <p class="text-muted animate__animated animate__fadeInUp">
          Exporta tus colecciones a Excel/CSV o JSON. Restaura con un par de clics.
        </p>
      </div>
    </div>

    <!-- Botones CTA -->
    <div class="row mb-4">
      <div class="col-12 d-flex flex-wrap gap-3 justify-content-center">
        <button
          class="btn btn-lg btn-primary px-4 py-3 shadow cta-btn animate__animated"
          :class="showRestore ? 'animate__pulse' : 'animate__fadeInUp'"
          @click="toggleSection('restore')"
        >
          <i class="bi bi-upload me-2"></i> Restaurar base de datos
        </button>

        <button
          class="btn btn-lg btn-success px-4 py-3 shadow cta-btn animate__animated"
          :class="showBackup ? 'animate__pulse' : 'animate__fadeInUp'"
          @click="toggleSection('backup')"
        >
          <i class="bi bi-download me-2"></i> Generar respaldo
        </button>
      </div>
    </div>

    <div class="row g-4">
      <!-- Panel de Restauración -->
      <div class="col-12 col-xl-6" v-if="showRestore">
        <div class="card shadow-sm h-100 animate__animated animate__fadeInUp">
          <div class="card-header bg-white d-flex align-items-center justify-content-between sticky-top">
            <div class="d-flex align-items-center gap-2">
              <span class="step-circle bg-primary-subtle text-primary">1</span>
              <h5 class="mb-0 fw-bold">
                Restauración de base de datos
              </h5>
            </div>
            <span class="badge rounded-pill text-bg-primary">Import</span>
          </div>

          <div class="card-body">
            <div class="alert alert-light border mb-4">
              <div class="d-flex align-items-center gap-2">
                <i class="bi bi-info-circle"></i>
                <div>
                  Formatos soportados:
                  <strong>XLSX</strong>, <strong>CSV/TXT</strong> (archivo suelto o ZIP con varios),
                  y <strong>JSON</strong> (un JSON por colección, ZIP con varios, o JSON combinado).
                </div>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-12 col-md-6">
                <label class="form-label">Formato del archivo</label>
                <select v-model="importFormat" class="form-select" :disabled="loading">
                  <option value="excel">Excel (.xlsx)</option>
                  <option value="csv">CSV (.csv) o ZIP con CSVs</option>
                  <option value="txt">TXT (CSV con separador coma)</option>
                  <option value="json">JSON (.json o .zip con varios .json)</option>
                </select>
              </div>

              <div class="col-12 col-md-6" v-if="importFormat==='csv' || importFormat==='txt' || importFormat==='json'">
                <label class="form-label">Colección (si subes un solo archivo de colección)</label>
                <input v-model.trim="singleCollection" type="text" class="form-control" placeholder="ej. tecnicas" :disabled="loading" />
                <div class="form-text">
                  Para ZIP con múltiples archivos no es necesario.
                  En JSON combinado no es necesario.
                </div>
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label">Modo de importación</label>
                <select v-model="importMode" class="form-select" :disabled="loading" data-bs-toggle="tooltip" title="merge conserva y actualiza por _id; replace borra la colección antes de insertar">
                  <option value="merge">Combinar (upsert por _id cuando exista)</option>
                  <option value="replace">Reemplazar (elimina colección antes)</option>
                </select>
              </div>

              <div class="col-12">
                <label class="form-label">Selecciona archivo</label>
                <input
                  type="file"
                  class="form-control"
                  :accept="acceptByFormat"
                  @change="onFile"
                  :disabled="loading"
                />
                <div class="form-text">{{ acceptHelpText }}</div>
              </div>

              <div class="col-12 d-flex align-items-center gap-2">
                <button class="btn btn-primary" :disabled="!file || loading" @click="doImport">
                  <span v-if="!loading"><i class="bi bi-cloud-arrow-up me-1"></i>Restaurar</span>
                  <span v-else class="spinner-border spinner-border-sm"></span>
                </button>
                <button class="btn btn-outline-secondary" :disabled="loading" @click="resetImport">
                  Limpiar
                </button>

                <div class="ms-auto small text-muted" v-if="file">
                  <i class="bi bi-file-earmark-text me-1"></i>{{ file.name }}
                </div>
              </div>
            </div>

            <transition name="fade">
              <div v-if="message" class="alert mt-3 animate__animated" :class="messageClass">
                {{ message }}
              </div>
            </transition>
          </div>
        </div>
      </div>

      <!-- Panel de Respaldo -->
      <div class="col-12 col-xl-6" v-if="showBackup">
        <div class="card shadow-sm h-100 animate__animated animate__fadeInUp">
          <div class="card-header bg-white d-flex align-items-center justify-content-between sticky-top">
            <div class="d-flex align-items-center gap-2">
              <span class="step-circle bg-success-subtle text-success">1</span>
              <h5 class="mb-0 fw-bold">
                Respaldo de base de datos
              </h5>
            </div>
            <span class="badge rounded-pill text-bg-success">Export</span>
          </div>

          <div class="card-body">
            <p class="text-muted mb-3">
              Elige el tipo de respaldo que deseas descargar.
            </p>

            <div class="row g-3">
              <div class="col-12 col-sm-4">
                <button class="btn btn-success w-100 py-3" :disabled="!!loadingExport" @click="exportFile('excel')">
                  <span v-if="loadingExport==='excel'" class="spinner-border spinner-border-sm me-1"></span>
                  <i class="bi bi-filetype-xlsx me-1"></i> Excel
                </button>
              </div>
              <div class="col-12 col-sm-4">
                <button class="btn btn-outline-success w-100 py-3" :disabled="!!loadingExport" @click="exportFile('csv')">
                  <span v-if="loadingExport==='csv'" class="spinner-border spinner-border-sm me-1"></span>
                  <i class="bi bi-file-zip me-1"></i> CSV (ZIP)
                </button>
              </div>
              <div class="col-12 col-sm-4">
                <button class="btn btn-dark w-100 py-3" :disabled="!!loadingExport" @click="exportFile('json')">
                  <span v-if="loadingExport==='json'" class="spinner-border spinner-border-sm me-1"></span>
                  <i class="bi bi-braces me-1"></i> JSON (ZIP)
                </button>
              </div>
            </div>

            <transition name="fade">
              <div v-if="exportMsg" class="alert alert-info mt-3 animate__animated animate__fadeIn">
                {{ exportMsg }}
              </div>
            </transition>

            <transition name="fade">
              <div v-if="apiBaseWarning" class="alert alert-warning mt-3 animate__animated animate__fadeIn">
                {{ apiBaseWarning }}
              </div>
            </transition>
          </div>
        </div>
      </div>
    </div>

    <!-- Tips -->
    <div class="row mt-4">
      <div class="col-12">
        <div class="alert alert-secondary d-flex align-items-start gap-3 animate__animated animate__fadeInUp">
          <i class="bi bi-lightbulb mt-1"></i>
          <div>
            <strong>Tips:</strong> El formato <code>JSON (ZIP)</code> exporta cada colección como <code>&lt;colección&gt;.json</code>.
            Para importar un solo JSON por colección, indica la colección en el campo “Colección” o usa un ZIP con varios JSON.
            También puedes subir un JSON “combinado” con todas las colecciones.
          </div>
        </div>
      </div>
    </div>
  </main>
</template>

<script setup>
import axios from 'axios'
import { ref, computed, onMounted, nextTick } from 'vue'

const API_BASE = process.env.VUE_APP_API_URL

const apiBaseWarning = ref('')
onMounted(async () => {
  if (!API_BASE) {
    apiBaseWarning.value = 'Advertencia: Error con la conexión al servidor API.'
  }
  await nextTick()
  if (window.bootstrap) {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    tooltipTriggerList.forEach(el => new window.bootstrap.Tooltip(el))
  }
})

// ====== Auth headers ======
function getAuthHeaders(extra = {}) {
  const token = localStorage.getItem('token')
  const tokenType = (localStorage.getItem('token_type') || 'Bearer').trim()
  const auth = token ? { Authorization: `${tokenType} ${token}` } : {}
  return { ...auth, ...extra }
}

// ====== Toggle de secciones ======
const showRestore = ref(false)
const showBackup = ref(false)
function toggleSection(which) {
  if (which === 'restore') {
    showRestore.value = !showRestore.value
    if (showRestore.value) showBackup.value = false
  } else {
    showBackup.value = !showBackup.value
    if (showBackup.value) showRestore.value = false
  }
}

// ====== Estado de Restauración ======
const file = ref(null)
const importFormat = ref('excel')
const importMode = ref('merge')
const singleCollection = ref('')

const loading = ref(false)
const message = ref('')
const messageType = ref('info')

// ====== Estado de Exportación ======
const loadingExport = ref(false) // 'excel' | 'csv' | 'json' | false
const exportMsg = ref('')

// ====== Computed ======
const messageClass = computed(() => ({
  'alert-info': messageType.value === 'info',
  'alert-success': messageType.value === 'success',
  'alert-danger': messageType.value === 'error',
  'alert-warning': messageType.value === 'warning'
}))

const acceptByFormat = computed(() => {
  switch (importFormat.value) {
    case 'excel': return '.xlsx'
    case 'csv':   return '.csv,.zip'
    case 'txt':   return '.txt,.csv'
    case 'json':  return '.json,.zip'
    default:      return ''
  }
})

const acceptHelpText = computed(() => {
  if (importFormat.value === 'csv') return 'Acepta .csv suelto o .zip con múltiples CSV (uno por colección).'
  if (importFormat.value === 'json') return 'Acepta .json suelto (colección) o .zip con múltiples JSON; también JSON combinado.'
  return ''
})

// ====== Handlers ======
function onFile(e) {
  file.value = e.target.files?.[0] ?? null
}

function resetImport() {
  file.value = null
  message.value = ''
  messageType.value = 'info'
  singleCollection.value = ''
  importFormat.value = 'excel'
  importMode.value = 'merge'
}

async function doImport() {
  if (!file.value) return
  loading.value = true
  message.value = ''
  messageType.value = 'info'
  try {
    const form = new FormData()
    form.append('file', file.value)
    form.append('format', importFormat.value)
    form.append('mode', importMode.value)

    const params = {}
    if ((importFormat.value === 'csv' || importFormat.value === 'txt' || importFormat.value === 'json') && singleCollection.value) {
      params.collection = singleCollection.value
    }

    const url = `${API_BASE}/backups/import`
    const { data } = await axios.post(url, form, {
      params,
      headers: getAuthHeaders({ 'Content-Type': 'multipart/form-data' }),
      responseType: 'json'
    })

    message.value = data?.message || 'Importación completada.'
    messageType.value = 'success'
  } catch (err) {
    let apiMsg = 'Error en importación'
    if (err?.response?.data instanceof Blob) {
      try {
        const text = await err.response.data.text()
        const obj = JSON.parse(text)
        apiMsg = obj.message || obj.error || apiMsg
      } catch {}
    } else if (err?.response?.data?.message || err?.response?.data?.error) {
      apiMsg = err.response.data.message || err.response.data.error
    } else if (err?.message) {
      apiMsg = err.message
    }
    message.value = apiMsg
    messageType.value = 'error'
  } finally {
    loading.value = false
  }
}

async function exportFile(type) {
  exportMsg.value = ''
  loadingExport.value = type
  try {
    const url = `${API_BASE}/backups/export`
    const res = await axios.get(url, {
      params: { type },
      responseType: 'blob',
      headers: getAuthHeaders()
    })

    let filename = 'backup.' + (type === 'excel' ? 'xlsx' : (type === 'csv' ? 'zip' : 'json.zip'))
    const cd = res.headers?.['content-disposition'] || res.headers?.['Content-Disposition']
    if (cd) {
      const utf8 = /filename\*=(?:UTF-8'')?([^;]+)/i.exec(cd)
      const simple = /filename="?([^"]+)"?/i.exec(cd)
      filename = decodeURIComponent((utf8?.[1] || simple?.[1] || filename).trim())
    }

    const blob = new Blob([res.data])
    const urlObj = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = urlObj
    a.setAttribute('download', filename)
    document.body.appendChild(a)
    a.click()
    a.remove()
    window.URL.revokeObjectURL(urlObj)
    exportMsg.value = 'Descarga iniciada.'
  } catch (err) {
    let msg = 'No se pudo generar el respaldo.'
    if (err?.response?.data instanceof Blob) {
      try {
        const text = await err.response.data.text()
        const obj = JSON.parse(text)
        msg = obj.message || obj.error || msg
      } catch {}
    } else if (err?.response?.data?.message || err?.response?.data?.error) {
      msg = err.response.data.message || err.response.data.error
    }
    exportMsg.value = msg
  } finally {
    loadingExport.value = false
  }
}
</script>

<style scoped>
/* CTA grandes */
.cta-btn {
  min-width: 280px;
  border-radius: 14px;
  font-weight: 700;
  letter-spacing: .2px;
  transition: transform .2s ease, box-shadow .2s ease;
}
.cta-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(0,0,0,.08);
}

/* Header de tarjetas “pegajoso” para mejor UX */
.card-header.sticky-top {
  top: 0;
  z-index: 2;
  border-bottom: 1px solid rgba(0,0,0,.05);
}

.step-circle {
  display: inline-flex;
  width: 28px;
  height: 28px;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  font-weight: 700;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity .25s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
