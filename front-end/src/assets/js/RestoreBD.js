// src/assets/js/RestoreBD.js
import { ref, computed } from 'vue'
import axios from 'axios'

// === Helpers ===
const API = (process.env.VUE_APP_API_URL || '').replace(/\/+$/,'')
const RESTORE_ENDPOINT = `${API}/restore`

function authHeaders () {
  const t = localStorage.getItem('token') || ''
  const tt = (localStorage.getItem('token_type') || 'Bearer').trim()
  return t ? { Authorization: `${tt} ${t}`, Accept: 'application/json' } : { Accept: 'application/json' }
}

// ====== state ======
const importFormat = ref('json')   // excel|csv|json
const collection   = ref('')       // tecnicas|recompensas|users (solo CSV/JSON)
const mode         = ref('append') // append|replace

const file = ref(null)
const flatPreview = ref([])

const loading = ref(false)
const message = ref('')
const messageType = ref('') // info|success|error|warning

const acceptByFormat = computed(() => {
  if (importFormat.value === 'excel') return '.xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel'
  if (importFormat.value === 'csv')   return '.csv,text/csv'
  if (importFormat.value === 'json')  return '.json,application/json'
  return '.xlsx,.csv,.json'
})

// ====== lectura archivo -> preview ======
async function onFileSelected(e){
  try{
    const f = e?.target?.files?.[0]
    file.value = f || null
    flatPreview.value = []
    message.value = ''
    messageType.value = ''

    if (!f) return

    const name = (f.name || '').toLowerCase()
    if (name.endsWith('.json')) {
      const text = await f.text()
      const data = JSON.parse(text || '[]')
      flatPreview.value = Array.isArray(data) ? data : [data]
    } else if (name.endsWith('.csv')) {
      const text = await f.text()
      flatPreview.value = parseCSV(text)
    } else if (name.endsWith('.xlsx')) {
      const XLSX = await tryLoadXLSX()
      const buf = await f.arrayBuffer()
      const wb = XLSX.read(buf)
      // hoja por nombre si existe
      const wanted = ['tecnicas','recompensas','users']
      let sheetName = wb.SheetNames.find(n => wanted.includes(String(n).toLowerCase())) || wb.SheetNames[0]
      const ws = wb.Sheets[sheetName]
      const arr = XLSX.utils.sheet_to_json(ws)
      flatPreview.value = Array.isArray(arr) ? arr : []
      if (!collection.value) {
        const low = String(sheetName).toLowerCase()
        if (wanted.includes(low)) collection.value = low
      }
    } else {
      message.value = 'Formato no soportado.'
      messageType.value = 'error'
      return
    }

    // aviso (la vista lo muestra y evita duplicado)
    message.value = `Se detectaron ${flatPreview.value.length} filas.`
    messageType.value = 'info'
  }catch(err){
    console.error(err)
    message.value = 'No se pudo leer el archivo. Verifica el formato.'
    messageType.value = 'error'
  }
}

function parseCSV(text){
  const lines = text.split(/\r?\n/).filter(Boolean)
  if (!lines.length) return []
  const headers = splitCSVLine(lines[0]).map(h=>h.trim())
  const out=[]
  for (let i=1;i<lines.length;i++){
    const cols = splitCSVLine(lines[i])
    const row={}
    headers.forEach((h,idx)=> row[h]=cols[idx] ?? '')
    out.push(row)
  }
  return out
}
function splitCSVLine(line){
  const res=[]; let cur='', inQ=false
  for (let i=0;i<line.length;i++){
    const c=line[i]
    if(c==='"'){ if(inQ && line[i+1]==='"'){ cur+='"'; i++ } else inQ=!inQ }
    else if(c===',' && !inQ){ res.push(cur); cur='' }
    else cur+=c
  }
  res.push(cur); return res
}
async function tryLoadXLSX(){
  if (window.XLSX) return window.XLSX
  await new Promise((resolve, reject)=>{
    const s=document.createElement('script')
    s.src='https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js'
    s.onload=resolve; s.onerror=reject; document.head.appendChild(s)
  })
  return window.XLSX
}

// ====== RESTORE ACTION ======
async function doRestore(){
  if (!file.value) { message.value='Selecciona un archivo primero.'; messageType.value='warning'; return }
  if (importFormat.value !== 'excel' && !collection.value) {
    message.value='Para CSV/JSON debes indicar la colección (tecnicas o recompensas).'; messageType.value='warning'; return
  }

  loading.value = true
  message.value = ''
  messageType.value = ''

  try{
    // ¡Nada de DELETE de colecciones aquí! 'mode=replace' lo hace el backend.
    const form = new FormData()
    form.append('file', file.value)
    form.append('format', importFormat.value)
    form.append('mode', mode.value || 'append')
    if (importFormat.value !== 'excel') form.append('collection', collection.value)

    await axios.post(RESTORE_ENDPOINT, form, {
      headers: authHeaders(), // dejar que axios ponga el Content-Type multipart con boundary
    })

    message.value = 'Restauración completada.'
    messageType.value = 'success'
  }catch(e){
    console.error(e)
    let msg = e?.response?.data?.message || e?.message || 'Error durante la restauración.'
    message.value = msg
    messageType.value = 'error'
  }finally{
    loading.value = false
  }
}

function reset(){
  file.value = null
  flatPreview.value = []
  message.value = ''
  messageType.value = ''
}

// ====== export composable ======
export function useRestoreBD(){
  return {
    importFormat, collection, mode,
    onFileSelected, doRestore, reset,
    acceptByFormat,
    message, messageType, loading,
    flatPreview
  }
}
