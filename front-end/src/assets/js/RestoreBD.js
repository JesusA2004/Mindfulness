// src/assets/js/RestoreBD.js
import { ref, computed } from 'vue'
import axios from 'axios'

/* =========================
   Helpers base
   ========================= */
const API = (process.env.VUE_APP_API_URL || '').replace(/\/+$/,'')
const RESTORE_ENDPOINT = `${API}/restore`
const ENDPOINT = (col) => `${API}/${col}`

function authHeaders () {
  const t  = localStorage.getItem('token') || ''
  const tt = (localStorage.getItem('token_type') || 'Bearer').trim()
  return t ? { Authorization: `${tt} ${t}`, Accept: 'application/json' } : { Accept: 'application/json' }
}

const isObj   = (v)=> v && typeof v==='object' && !Array.isArray(v)
const toInt   = (v, d=0)=> { const n = Number(v); return Number.isFinite(n) ? Math.trunc(n) : d }
const toStr   = (v)=> (v == null ? '' : String(v))

/* =========================
   Estado
   ========================= */
const importFormat = ref('json')   // 'excel' | 'csv' | 'json'
const collection   = ref('')       // 'tecnicas' | 'recompensas' | 'users' (CSV/JSON lo requieren)
const mode         = ref('append') // 'append' | 'replace'

const file        = ref(null)
const flatPreview = ref([])

const loading     = ref(false)
const message     = ref('')
const messageType = ref('') // 'info' | 'success' | 'error' | 'warning'

const acceptByFormat = computed(() => {
  if (importFormat.value === 'excel') return '.xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel'
  if (importFormat.value === 'csv')   return '.csv,text/csv'
  if (importFormat.value === 'json')  return '.json,application/json'
  return '.xlsx,.csv,.json'
})

/* =========================
   Lectura de archivo -> preview
   ========================= */
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
      const wb  = XLSX.read(buf)

      // intenta hoja con nombre conocido
      const wanted = ['tecnicas','recompensas','users']
      let sheetName = wb.SheetNames.find(n => wanted.includes(String(n).toLowerCase())) || wb.SheetNames[0]
      const ws  = wb.Sheets[sheetName]
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

    // aviso (la vista ya evita mostrar duplicado si aplica)
    message.value = `Se detectaron ${flatPreview.value.length} filas.`
    messageType.value = 'info'
  }catch(err){
    console.error(err)
    message.value = 'No se pudo leer el archivo. Verifica el formato.'
    messageType.value = 'error'
  }
}

function parseCSV(text){
  const lines = text.split(/\r?\n/).filter(l => l.trim().length)
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

/* =========================
   Usuarios: crear persona + user (no usa RestoreController)
   ========================= */
function buildCohorte(carrera, cuatrimestre, grupo){
  const c = Array.isArray(carrera)? carrera[0] : carrera
  const q = Array.isArray(cuatrimestre)? cuatrimestre[0] : cuatrimestre
  const g = Array.isArray(grupo)? grupo[0] : grupo
  const parts = [c,q,g].map(v=>toStr(v).trim()).filter(Boolean)
  return parts.length ? parts.join(' ') : ''
}
function nameFromPersona(p){
  const n = [toStr(p.nombre), toStr(p.apellidoPaterno), toStr(p.apellidoMaterno)].filter(Boolean).join(' ').trim()
  return n || toStr(p.nombre) || ''
}
async function createPersonaAndUser(raw){
  // Cohorte puede venir en 'cohorte' (string o array) o desglosado (carrera/cuatrimestre/grupo)
  const cohorte =
    Array.isArray(raw.cohorte) ? raw.cohorte :
    (raw.cohorte ? toStr(raw.cohorte) : buildCohorte(raw.carrera, raw.cuatrimestre, raw.grupo))

  const personaPayload = {
    nombre:          toStr(raw.nombre || raw.name),
    apellidoPaterno: toStr(raw.apellidoPaterno),
    apellidoMaterno: toStr(raw.apellidoMaterno),
    fechaNacimiento: toStr(raw.fechaNacimiento),
    telefono:        toStr(raw.telefono),
    sexo:            toStr(raw.sexo),
    cohorte,
    matricula:       toStr(raw.matricula),
  }

  // store persona (ruta pública en tus rutas)
  const presp = await axios.post(ENDPOINT('personas'), personaPayload, { headers: authHeaders() })
  const persona = presp.data?.persona || presp.data || {}
  const personaId = toStr(persona._id || persona.id || '')

  // Ahora usuario
  const userPayload = {
    name:       nameFromPersona(personaPayload),
    email:      toStr(raw.email),
    rol:        toStr(raw.rol).toLowerCase(),  // 'estudiante' | 'profesor' | 'admin'
    estatus:    toStr(raw.estatus || 'activo'),
    persona_id: personaId || undefined,
    matricula:  toStr(raw.matricula),
    notify_email: true
  }

  await axios.post(ENDPOINT('users'), userPayload, { headers: authHeaders() })
}

/* =========================
   Restore action
   ========================= */
async function doRestore(){
  if (!file.value) { message.value='Selecciona un archivo primero.'; messageType.value='warning'; return }

  // Para CSV/JSON el RestoreController exige 'collection' (tecnicas|recompensas).
  if (importFormat.value !== 'excel' && !collection.value) {
    message.value='Para CSV/JSON debes indicar la colección (tecnicas, recompensas o users).'
    messageType.value='warning'
    return
  }

  loading.value = true
  message.value = ''
  messageType.value = ''

  try{
    // === 1) USERS: se procesan aquí (persona -> user), NO por RestoreController
    if (collection.value === 'users') {
      // Usa flatPreview (ya cargado del archivo)
      for (const row of flatPreview.value) {
        try { await createPersonaAndUser(row) }
        catch (e) { console.error('Fila users con error:', e) }
      }
      message.value = 'Usuarios restaurados.'
      messageType.value = 'success'
      return
    }

    // === 2) TECNICAS / RECOMPENSAS: sí usan RestoreController
    const form = new FormData()
    form.append('file', file.value)
    form.append('format', importFormat.value)      // 'excel' | 'csv' | 'json'
    form.append('mode', mode.value || 'append')    // 'append' | 'replace'

    // Para CSV/JSON se requiere 'collection' por contrato de tu RestoreController
    if (importFormat.value !== 'excel') {
      if (!['tecnicas','recompensas'].includes(collection.value)) {
        message.value = 'Para CSV/JSON la colección debe ser: tecnicas o recompensas.'
        messageType.value = 'error'
        return
      }
      form.append('collection', collection.value)
    }

    await axios.post(RESTORE_ENDPOINT, form, { headers: authHeaders() })

    message.value = 'Restauración completada.'
    messageType.value = 'success'
  }catch(e){
    console.error(e)
    const msg = e?.response?.data?.message || e?.message || 'Error durante la restauración.'
    message.value = msg
    messageType.value = 'error'
  }finally{
    loading.value = false
  }
}

/* =========================
   Reset
   ========================= */
function reset(){
  file.value = null
  flatPreview.value = []
  message.value = ''
  messageType.value = ''
}

/* =========================
   Export composable
   ========================= */
export function useRestoreBD(){
  return {
    importFormat, collection, mode,
    onFileSelected, doRestore, reset,
    acceptByFormat,
    message, messageType, loading,
    flatPreview
  }
}
