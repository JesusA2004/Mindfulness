<!-- src/views/administrador/RespaldoBD.vue -->
<template>
  <main class="container py-4">
    <!-- CTA principales -->
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

      <!-- Restauración -->
      <div class="col-12 col-lg-6" v-if="showRestore">
        <div class="card shadow-sm h-100 animate__animated animate__fadeInUp">
          <div class="card-header bg-white d-flex align-items-center justify-content-between">
            <h5 class="mb-0"><i class="bi bi-arrow-counterclockwise me-2"></i>Restauración de base de datos</h5>
            <span class="badge text-bg-primary">Paso 1</span>
          </div>

          <div class="card-body">
            <p class="text-muted mb-3">
              Sube un archivo de respaldo. Formatos:
              <strong>Excel (.xlsx)</strong>, <strong>CSV/TXT</strong> (uno o ZIP con varios),
              o <strong>Dump Mongo (.archive.gz)</strong>.
            </p>

            <div class="mb-3">
              <label class="form-label">Formato del archivo</label>
              <select v-model="importFormat" class="form-select">
                <option value="excel">Excel (.xlsx)</option>
                <option value="csv">CSV (.csv) o ZIP con CSVs</option>
                <option value="txt">TXT (CSV con separador coma)</option>
                <option value="mongo">Dump Mongo (.archive.gz)</option>
              </select>
            </div>

            <div class="mb-3" v-if="importFormat==='csv' || importFormat==='txt'">
              <label class="form-label">Colección (si subes un solo CSV/TXT)</label>
              <input v-model.trim="singleCollection" type="text" class="form-control" placeholder="ej. tecnicas"/>
              <div class="form-text">Si subes un ZIP con varios CSV, no es necesario.</div>
            </div>

            <div class="mb-3">
              <label class="form-label">Modo de importación</label>
              <select v-model="importMode" class="form-select">
                <option value="merge">Combinar (upsert por _id cuando exista)</option>
                <option value="replace">Reemplazar (elimina colección antes)</option>
              </select>
            </div>

            <div class="mb-3">
              <input
                type="file"
                class="form-control"
                :accept="acceptByFormat"
                @change="onFile"
              />
              <div class="form-text">
                {{ acceptHelpText }}
              </div>
            </div>

            <div class="d-flex gap-2">
              <button class="btn btn-primary" :disabled="!file || loading" @click="doImport">
                <span v-if="!loading"><i class="bi bi-cloud-arrow-up me-1"></i>Restaurar</span>
                <span v-else class="spinner-border spinner-border-sm"></span>
              </button>
              <button class="btn btn-outline-secondary" :disabled="loading" @click="resetImport">
                Limpiar
              </button>
            </div>

            <div v-if="message" class="alert mt-3" :class="messageClass">{{ message }}</div>
          </div>
        </div>
      </div>

      <!-- Respaldo -->
      <div class="col-12 col-lg-6" v-if="showBackup">
        <div class="card shadow-sm h-100 animate__animated animate__fadeInUp">
          <div class="card-header bg-white d-flex align-items-center justify-content-between">
            <h5 class="mb-0"><i class="bi bi-hdd-network me-2"></i>Respaldo de base de datos</h5>
            <span class="badge text-bg-success">Paso 1</span>
          </div>

          <div class="card-body">
            <p class="text-muted">
              Genera un respaldo completo: <strong>Excel</strong> (xlsx por colección),
              <strong>CSV (ZIP)</strong> o <strong>Dump Mongo (.archive.gz)</strong>.
            </p>

            <div class="d-flex flex-wrap gap-2">
              <button class="btn btn-success" :disabled="!!loadingExport" @click="exportFile('excel')">
                <span v-if="loadingExport==='excel'" class="spinner-border spinner-border-sm me-1"></span>
                Descargar Excel
              </button>

              <button class="btn btn-outline-success" :disabled="!!loadingExport" @click="exportFile('csv')">
                <span v-if="loadingExport==='csv'" class="spinner-border spinner-border-sm me-1"></span>
                Descargar CSV (ZIP)
              </button>

              <button class="btn btn-dark" :disabled="!!loadingExport" @click="exportFile('mongo')">
                <span v-if="loadingExport==='mongo'" class="spinner-border spinner-border-sm me-1"></span>
                Descargar Dump Mongo
              </button>
            </div>

            <div v-if="exportMsg" class="alert alert-info mt-3">{{ exportMsg }}</div>
            <div v-if="apiBaseWarning" class="alert alert-warning mt-3">{{ apiBaseWarning }}</div>
          </div>
        </div>
      </div>

    </div>
  </main>
</template>

<script setup>
import axios from 'axios'
import { ref, computed, onMounted } from 'vue'

const API_BASE = process.env.VUE_APP_API_URL

const apiBaseWarning = ref('')
onMounted(() => {
  if (!API_BASE) {
    apiBaseWarning.value =
      'Advertencia: Error con la conexion al servidor API.'
  }
})

// ====== Auth headers (compatibles con tu Login.vue) ======
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
const loadingExport = ref(false) // 'excel' | 'csv' | 'mongo' | false
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
    case 'mongo': return '.archive.gz,.gz'
    default:      return ''
  }
})

const acceptHelpText = computed(() =>
  importFormat.value === 'csv'
    ? 'Acepta .csv suelto o .zip con múltiples CSV (uno por colección).'
    : (importFormat.value === 'mongo'
        ? 'Se recomienda un dump .archive.gz generado por mongodump.'
        : '')
)

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
    if ((importFormat.value === 'csv' || importFormat.value === 'txt') && singleCollection.value) {
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
    if (err?.response?.data) {
      apiMsg = err.response.data.message || err.response.data.error || apiMsg
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

    const cd = res.headers?.['content-disposition'] || res.headers?.['Content-Disposition']
    let filename = 'backup.' + (type === 'excel' ? 'xlsx' : (type === 'csv' ? 'zip' : 'archive.gz'))
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
      } catch (_) {}
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
  min-width: 290px;
  border-radius: 14px;
  font-weight: 700;
  letter-spacing: 0.2px;
  transition: transform .2s ease, box-shadow .2s ease;
}
.cta-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(0,0,0,.08);
}
.card-header h5 { font-weight: 700; }
</style>
