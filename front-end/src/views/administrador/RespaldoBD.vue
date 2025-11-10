<!-- src/views/administrador/RespaldoBD.vue -->
<template>
  <main class="container py-4 respaldo-restauracion">
    <!-- ========= HERO / CTA INICIAL ========= -->
    <section class="mb-4">
      <div class="text-center mb-3">
        <h2 class="fw-bold animate__animated animate__fadeInDown">Respaldo y Restauración de Base de Datos</h2>
        <p class="text-muted animate__animated animate__fadeInUp">
          Exporta en Excel/CSV/JSON o realiza una restauración guiada con vista previa y progreso.
        </p>
      </div>

      <!-- Tiles CTA -->
      <div class="row g-3 justify-content-center">
        <div class="col-12 col-md-6 col-xl-4">
          <button
            class="cta-tile btn w-100 bg-primary text-white animate__animated animate__fadeInUp"
            :class="{'active': showRestore}"
            @click="openSection('restore')">
            <div class="tile-icon"><i class="bi bi-cloud-arrow-up"></i></div>
            <div class="text-start">
              <h5 class="mb-1">Restaurar base de datos</h5>
              <small class="opacity-75">Carga masiva con validaciones y preview.</small>
            </div>
            <i class="bi bi-chevron-right ms-auto tile-arrow"></i>
          </button>
        </div>
        <div class="col-12 col-md-6 col-xl-4">
          <button
            class="cta-tile btn w-100 bg-success text-white animate__animated animate__fadeInUp"
            style="--delay:120ms"
            :class="{'active': showBackup}"
            @click="openSection('backup')">
            <div class="tile-icon"><i class="bi bi-cloud-arrow-down"></i></div>
            <div class="text-start">
              <h5 class="mb-1">Generar respaldo</h5>
              <small class="opacity-75">Descarga .xlsx, .csv (zip) o .json (zip).</small>
            </div>
            <i class="bi bi-chevron-right ms-auto tile-arrow"></i>
          </button>
        </div>
      </div>
    </section>

    <div class="row g-4">
      <!-- ========= PANEL: RESTAURACIÓN ========= -->
      <div class="col-12 col-xxl-6" v-if="showRestore">
        <div class="card shadow-sm h-100 animate__animated animate__fadeInUp">
          <div class="card-header bg-white d-flex align-items-center justify-content-between sticky-top">
            <div class="d-flex align-items-center gap-2">
              <span class="step-circle bg-primary-subtle text-primary">1</span>
              <h5 class="mb-0 fw-bold">Restauración de base de datos</h5>
            </div>
            <span class="badge rounded-pill text-bg-primary">Import</span>
          </div>

          <div class="card-body">
            <div class="alert alert-warning border mb-4 animate__animated animate__fadeIn">
              <div class="d-flex align-items-start gap-2">
                <i class="bi bi-shield-exclamation mt-1"></i>
                <div>
                  Disponible para <code>tecnicas</code>, <code>recompensas</code> y <strong>usuarios</strong> (misma acción de Restaurar).
                  La restauración es una <strong>carga masiva de inserts</strong>. Formatos: <strong>XLSX</strong>, <strong>CSV</strong>, <strong>JSON</strong>.
                  <u>No se aceptan archivos comprimidos</u> (.zip, .rar, .7z).
                </div>
              </div>
            </div>

            <!-- Formato -->
            <div class="mb-3">
              <label class="form-label fw-semibold d-block mb-2">Formato del archivo</label>
              <div class="chip-grid">
                <button v-for="f in formatOptions" :key="f.value" type="button"
                        class="chip animate__animated animate__fadeInUp"
                        :class="{active: importFormat === f.value, disabled: loading}"
                        :disabled="loading" @click="selectFormat(f.value)">
                  <i :class="f.icon"></i><span>{{ f.label }}</span>
                </button>
              </div>
            </div>

            <!-- Modo -->
            <div class="mb-3">
              <label class="form-label fw-semibold d-block mb-2">Modo de importación</label>
              <div class="chip-grid">
                <button v-for="m in modeOptions" :key="m.value" type="button"
                        class="chip animate__animated animate__fadeInUp"
                        :class="{active: mode === m.value, disabled: loading}"
                        :disabled="loading" @click="selectMode(m.value)"
                        data-bs-toggle="tooltip" :title="m.hint">
                  <i :class="m.icon"></i><span>{{ m.label }}</span>
                </button>
              </div>
            </div>

            <!-- Archivo -->
            <div class="mb-2">
              <label class="form-label fw-semibold">Selecciona archivo</label>

              <div
                class="file-drop border rounded-4 p-3 d-flex align-items-center justify-content-between gap-3"
                :class="{'drag-active': dragActive}"
                @click="openNativePicker"
                @dragover.prevent="onDragOver"
                @dragleave.prevent="onDragLeave"
                @drop.prevent="onDrop">
                <div class="d-flex align-items-center gap-3">
                  <div class="file-badge">
                    <i class="bi" :class="dragActive ? 'bi-cloud-arrow-down-fill' : 'bi-file-earmark-arrow-up'"></i>
                  </div>
                  <div class="flex-grow-1">
                    <div class="fw-semibold">Arrastra y suelta o haz clic para seleccionar</div>
                    <div class="small text-muted">Formatos: {{ acceptByFormatText }}</div>
                    <div v-if="fileName" class="small mt-1">
                      <i class="bi bi-check2-circle text-success me-1"></i>
                      <span class="fw-semibold">{{ fileName }}</span>
                      <span v-if="autoCollectionNote" class="ms-2 text-success"><i class="bi bi-magic me-1"></i>{{ autoCollectionNote }}</span>
                    </div>
                  </div>
                </div>
                <button type="button" class="btn btn-outline-primary m-0">Elegir archivo</button>
                <input ref="fileInput" type="file" class="d-none"
                       :accept="acceptByFormat" @change="onNativeChange" :disabled="loading" />
              </div>

              <div class="form-text">
                Excel: hojas <code>tecnicas</code>, <code>recompensas</code> o <code>users</code>.
                CSV/JSON: una colección por archivo. <strong>Se detecta por contenido</strong> (encabezados/campos).
              </div>
            </div>

            <!-- Acciones -->
            <div class="d-flex align-items-center gap-2 flex-wrap mt-3">
              <button class="btn btn-primary w-auto py-2 px-3"
                      :disabled="!hasPreview || loading"
                      @click="doRestoreWrapped">
                <span v-if="!loading">
                  <i :class="hasPreview ? 'bi bi-cloud-arrow-up me-1' : 'bi bi-lock me-1'"></i>
                  Restaurar
                </span>
                <span v-else class="spinner-border spinner-border-sm"></span>
              </button>

              <button class="btn btn-outline-secondary w-auto py-2 px-3"
                      :disabled="!hasPreview || loading"
                      @click="resetUI"
                      data-bs-toggle="tooltip"
                      title="Limpia SOLO la vista previa y el archivo seleccionado. No afecta la base de datos.">
                <i class="bi bi-broom me-1"></i> Limpiar vista
              </button>
            </div>

            <!-- Progreso -->
            <transition name="fade">
              <div v-if="loading || progressVisible" class="progress-wrap mt-3 animate__animated animate__fadeIn">
                <div class="d-flex justify-content-between mb-1">
                  <small class="text-muted"><i class="bi bi-arrow-repeat me-1"></i>Insertando registros…</small>
                  <small class="text-muted">{{ uiProgress }}%</small>
                </div>
                <div class="progress rounded-pill shadow-0">
                  <div class="progress-bar progress-bar-striped progress-bar-animated"
                       role="progressbar" :style="{ width: uiProgress + '%' }"
                       aria-valuemin="0" aria-valuemax="100"></div>
                </div>
              </div>
            </transition>

            <!-- Mensajes (sin duplicar) -->
            <transition name="fade">
              <div v-if="showMessage" class="alert mt-3 animate__animated" :class="messageClass">
                {{ message }}
              </div>
            </transition>

            <!-- Preview -->
            <transition name="fade">
              <div v-if="hasPreview" class="mt-3 animate__animated animate__fadeIn">
                <div class="alert alert-info py-2 px-3">
                  Se detectaron <strong>{{ flatPreview.length }}</strong> filas.
                </div>

                <div class="bulk-table-wrapper card border-0 shadow-sm rounded-4 overflow-auto">
                  <table class="table table-sm table-hover m-0">
                    <thead class="table-light position-sticky top-0">
                      <tr>
                        <th style="width:56px">#</th>
                        <th v-for="(k, idx) in visibleHeaders" :key="'h-'+idx">{{ k }}</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(r,i) in flatPreview.slice(0,10)" :key="'r-'+i">
                        <td class="text-muted">{{ i+1 }}</td>
                        <td v-for="(k, idx) in visibleHeaders" :key="`c-${idx}`">
                          <span v-if="isPrimitive(r[k])">{{ String(r[k] ?? '') }}</span>
                          <span v-else class="badge text-bg-light">{{ compactObject(r[k]) }}</span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <small class="text-muted d-block mt-2">
                  Vista previa (primeras 10 filas). Los IDs se ocultan en la vista, pero se consideran en la carga.
                </small>
              </div>

              <div v-else class="mt-3 text-center text-muted animate__animated animate__fadeIn">
                <i class="bi bi-eye-slash me-1"></i> Aún no hay vista previa.
              </div>
            </transition>
          </div>
        </div>
      </div>

      <!-- ========= PANEL: RESPALDO ========= -->
      <div class="col-12 col-xxl-6" v-if="showBackup">
        <div class="card shadow-sm h-100 animate__animated animate__fadeInUp">
          <div class="card-header bg-white d-flex align-items-center justify-content-between sticky-top">
            <div class="d-flex align-items-center gap-2">
              <span class="step-circle bg-success-subtle text-success">1</span>
              <h5 class="mb-0 fw-bold">Respaldo de base de datos</h5>
            </div>
            <span class="badge rounded-pill text-bg-success">Export</span>
          </div>

          <div class="card-body">
            <p class="text-muted mb-3">
              Descarga un respaldo completo. Formatos disponibles:
            </p>

            <div class="row g-3">
              <div class="col-12 col-sm-4">
                <button class="export-btn btn btn-success w-100 py-3 ripple"
                        :disabled="!!loadingExport" @click="exportFile('excel')">
                  <span v-if="loadingExport==='excel'" class="spinner-border spinner-border-sm me-1"></span>
                  <i class="bi bi-filetype-xlsx me-1"></i> Excel
                  <div class="tiny-note">.xlsx</div>
                </button>
              </div>
              <div class="col-12 col-sm-4">
                <button class="export-btn btn btn-outline-success w-100 py-3 ripple"
                        :disabled="!!loadingExport" @click="exportFile('csv')">
                  <span v-if="loadingExport==='csv'" class="spinner-border spinner-border-sm me-1"></span>
                  <i class="bi bi-file-zip me-1"></i> CSV (ZIP)
                  <div class="tiny-note">Colecciones separadas</div>
                </button>
              </div>
              <div class="col-12 col-sm-4">
                <button class="export-btn btn btn-dark w-100 py-3 ripple"
                        :disabled="!!loadingExport" @click="exportFile('json')">
                  <span v-if="loadingExport==='json'" class="spinner-border spinner-border-sm me-1"></span>
                  <i class="bi bi-braces me-1"></i> JSON (ZIP)
                  <div class="tiny-note">Máxima portabilidad</div>
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
  </main>
</template>

<script setup>
import axios from 'axios'
import { ref, computed, onMounted, nextTick } from 'vue'
import { useRestoreBD } from '@/assets/js/RestoreBD'

/* ====== Composable ====== */
const {
  importFormat, collection, mode,
  onFileSelected, doRestore, reset,
  acceptByFormat,
  message, messageType, loading,
  flatPreview
} = useRestoreBD()

/* ====== UI STATE ====== */
const fileInput = ref(null)
const fileName = ref('')
const dragActive = ref(false)
const autoCollectionNote = ref('')

const messageClass = computed(() => ({
  'alert-info':    messageType.value === 'info',
  'alert-success': messageType.value === 'success',
  'alert-danger':  messageType.value === 'error',
  'alert-warning': messageType.value === 'warning',
}))

/* Opciones visuales */
const formatOptions = [
  { value: 'excel', label: 'Excel (.xlsx)', icon: 'bi bi-filetype-xlsx' },
  { value: 'csv',   label: 'CSV (.csv)',    icon: 'bi bi-filetype-csv'  },
  { value: 'json',  label: 'JSON (.json)',  icon: 'bi bi-braces'        }
]
const modeOptions = [
  { value: 'append',  label: 'Agregar (solo inserts)',   icon: 'bi bi-plus-square',  hint: 'append inserta' },
  { value: 'replace', label: 'Reemplazar (limpia antes)',icon: 'bi bi-arrow-repeat', hint: 'replace elimina la colección antes de insertar' }
]

/* Derivados */
const acceptByFormatText = computed(() => {
  if (importFormat.value === 'excel') return '.xlsx'
  if (importFormat.value === 'csv') return '.csv'
  if (importFormat.value === 'json') return '.json'
  return '.xlsx, .csv, .json'
})
const hasPreview = computed(() => (flatPreview.value?.length || 0) > 0)

/* Evita duplicar “Se detectaron … filas” */
const showMessage = computed(() => {
  const msg = (message.value || '').toLowerCase().trim()
  const isDupInfo = messageType.value === 'info' && /^se\s+detectaron?\s+\d+/.test(msg)
  return !!message.value && !isDupInfo
})

/* Cabeceras */
const idKeyPattern = /^(_?id|.*_id)$/i
const visibleHeaders = computed(() => {
  const rows = (flatPreview.value || []).slice(0, 20)
  const set = new Set()
  rows.forEach(r => Object.keys(r || {}).forEach(k => {
    if (!idKeyPattern.test(k)) set.add(k)
  }))
  return Array.from(set)
})

function isPrimitive(v){ return (v === null) || (typeof v !== 'object') }
function compactObject(v){
  try { const s = JSON.stringify(v); return s.length>80 ? s.slice(0,80)+'…' : s }
  catch { return '[obj]' }
}

/* Progreso (solo UI) */
const uiProgress = ref(0)
const progressVisible = ref(false)
let progressTimer = null

async function doRestoreWrapped() {
  progressVisible.value = true
  uiProgress.value = 5
  startFakeProgress()
  try { await doRestore(); uiProgress.value = 100 }
  catch { uiProgress.value = Math.max(uiProgress.value, 25) }
  finally { stopFakeProgress(true) }
}
function startFakeProgress(){
  stopFakeProgress()
  progressTimer = setInterval(() => {
    if (!loading.value) return
    const cap = 92
    if (uiProgress.value < cap) {
      const step = uiProgress.value < 40 ? 3 : (uiProgress.value < 70 ? 2 : 1)
      uiProgress.value = Math.min(cap, uiProgress.value + step)
    }
  }, 300)
}
function stopFakeProgress(endSmooth=false){
  if (progressTimer) clearInterval(progressTimer)
  progressTimer = null
  if (endSmooth){
    setTimeout(()=>{ uiProgress.value = 100; setTimeout(()=>{ progressVisible.value = false; uiProgress.value = 0 }, 600) }, 200)
  }
}

/* Reset UI (no afecta BD) */
function resetUI(){
  reset()
  fileName.value = ''
  autoCollectionNote.value = ''
  dragActive.value = false
  uiProgress.value = 0
  progressVisible.value = false
  stopFakeProgress()
}

/* Selectores visuales */
function selectFormat(v){ importFormat.value = v }
function selectMode(v){ mode.value = v }

/* Navegación de secciones */
const showRestore = ref(true)
const showBackup  = ref(false)
function openSection(which){
  if (which === 'restore'){ showRestore.value = true; showBackup.value = false }
  else { showBackup.value = true; showRestore.value = false }
}

/* Drag & Drop + input nativo */
function openNativePicker(){ fileInput.value?.click() }
function extFromName(n){ return (String(n||'').split('.').pop() || '').toLowerCase() }
function acceptOkByExt(ext){
  if (importFormat.value === 'excel') return ext === 'xlsx'
  if (importFormat.value === 'csv')   return ext === 'csv'
  if (importFormat.value === 'json')  return ext === 'json'
  return ['xlsx','csv','json'].includes(ext)
}
function onNativeChange(e){
  const f = e?.target?.files?.[0]
  if (!f) return
  if (!acceptOkByExt(extFromName(f.name))) { softError(`Formato no permitido. Debe ser ${acceptByFormatText.value}.`); return }
  prepareFile(f)
  onFileSelected(e)
}
function onDragOver(){ dragActive.value = true }
function onDragLeave(){ dragActive.value = false }
function onDrop(e){
  dragActive.value = false
  const f = e?.dataTransfer?.files?.[0]
  if (!f) return
  const ext = extFromName(f.name)
  if (!acceptOkByExt(ext)) { softError(`Formato no permitido. Debe ser ${acceptByFormatText.value}.`); return }
  const fake = { target: { files: [f] } }
  prepareFile(f)
  onFileSelected(fake)
}
function softError(msg){
  fileName.value = ''
  autoCollectionNote.value = ''
  alert(msg)
}

/* ===== Inferencia por CONTENIDO (CSV/JSON) ===== */
const TECH_KEYS = ['nombre','descripcion','dificultad','duracion','categoria']
const REWARD_KEYS = ['nombre','descripcion','puntos_necesarios','stock']
const USER_KEYS = ['name','nombre','email','rol','password','matricula']

function score(keys, ref){
  let s = 0
  ref.forEach(k => { if (keys.includes(k)) s++ })
  return s
}

function prepareFile(file){
  fileName.value = file.name
  autoCollectionNote.value = ''
  const lower = file.name.toLowerCase()
  const isCSV  = lower.endsWith('.csv')
  const isJSON = lower.endsWith('.json')
  if (!isCSV && !isJSON) return

  const reader = new FileReader()
  reader.onload = () => {
    try {
      if (isCSV){
        const text = String(reader.result || '')
        const headerLine = text.split(/\r?\n/).find(l => l.trim().length) || ''
        const keys = headerLine.split(',').map(h => h.trim().toLowerCase().replace(/^"|"$/g,''))
        inferFromKeys(keys)
      } else {
        const txt = String(reader.result || '').trim()
        const data = JSON.parse(txt || '[]')
        const obj = Array.isArray(data) ? (data[0] || {}) : data
        const keys = Object.keys(obj || {}).map(k => k.toLowerCase())
        inferFromKeys(keys)
      }
    } catch { /* silencio */ }
  }
  reader.readAsText(file)
}

function inferFromKeys(keys){
  const t = score(keys, TECH_KEYS)
  const r = score(keys, REWARD_KEYS)
  const u = score(keys, USER_KEYS)
  const max = Math.max(t,r,u)
  if (max === 0) return
  if (t === max) { collection.value = 'tecnicas';    autoCollectionNote.value = 'Colección detectada: técnicas.' }
  else if (r === max) { collection.value = 'recompensas'; autoCollectionNote.value = 'Colección detectada: recompensas.' }
  else { collection.value = 'users'; autoCollectionNote.value = 'Colección detectada: usuarios.' }
}

/* ===== Export (Respaldo) ===== */
const API_BASE = process.env.VUE_APP_API_URL
const apiBaseWarning = ref('')

onMounted(async () => {
  if (!API_BASE) apiBaseWarning.value = 'Advertencia: Error con la conexión al servidor API.'
  await nextTick()
  if (window.bootstrap) {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    tooltipTriggerList.forEach(el => new window.bootstrap.Tooltip(el))
  }
})

function getAuthHeaders(extra = {}) {
  const token = localStorage.getItem('token')
  const tokenType = (localStorage.getItem('token_type') || 'Bearer').trim()
  const auth = token ? { Authorization: `${tokenType} ${token}` } : {}
  return { ...auth, ...extra }
}

const loadingExport = ref(false) // 'excel' | 'csv' | 'json' | false
const exportMsg = ref('')

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

<style scoped src="@/assets/css/Respaldo.css"></style>
