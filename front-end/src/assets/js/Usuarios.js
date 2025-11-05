// src/assets/js/Usuarios.js
import { ref, reactive, computed, onMounted, nextTick } from 'vue'
import axios from 'axios'
import Modal from 'bootstrap/js/dist/modal'
import Swal from 'sweetalert2'
import 'sweetalert2/dist/sweetalert2.min.css'

/* =====================  Config  ===================== */
const API = (process.env.VUE_APP_API_URL || '').replace(/\/+$/,'')
const USERS_URL    = API + '/users'
const PERSONAS_URL = API + '/personas'
const UPLOAD_URL   = API + '/subir-foto'
const AVATAR_BG = '#E0E7FF'
const AVATAR_FG = '#3730A3'
const HEX24 = /^[a-f0-9]{24}$/i

/* =====================  Helpers  ===================== */
function getToken () {
  try {
    const u = JSON.parse(localStorage.getItem('user') || '{}')
    return u?.access_token || u?.token || localStorage.getItem('token') || ''
  } catch { return localStorage.getItem('token') || '' }
}
function authHeaders () {
  const t = getToken()
  return t ? { Authorization: `Bearer ${t}`, Accept: 'application/json' } : { Accept: 'application/json' }
}
function toast (msg, type='success') { (type==='error' ? console.error : console.log)(msg) }
function makeDebouncer (ms) { let id; return (cb) => { clearTimeout(id); id = setTimeout(cb, ms) } }
function toDateInputValue (d) { if (!(d instanceof Date) || isNaN(d)) return ''; return d.toISOString().slice(0,10) }
function isRequired (v) { return v !== null && v !== undefined && String(v).trim() !== '' }

/* ===== Avatares con iniciales ===== */
function initialsFromName(name=''){
  const p=String(name).trim().split(/\s+/).filter(Boolean)
  if(!p.length) return 'U'
  return ((p[0][0]||'') + (p[p.length-1]?.[0]||'')).toUpperCase()
}
function fallbackAvatar(name){
  const w=200,h=200,t=initialsFromName(name)
  const svg=`<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}">
    <rect width="100%" height="100%" fill="${AVATAR_BG}"/>
    <text x="50%" y="50%" dy=".1em" text-anchor="middle"
      font-family="Inter,system-ui,Segoe UI,Roboto,Arial" font-size="88" font-weight="700"
      fill="${AVATAR_FG}">${t}</text>
  </svg>`
  return 'data:image/svg+xml;utf8,'+encodeURIComponent(svg)
}
function safeImg(_rawUrl, name){ return fallbackAvatar(name) }
function onImgError(ev,name='Usuario'){ if(!ev?.target) return; ev.target.onerror=null; ev.target.src=fallbackAvatar(name) }
function onFormImgError(ev, displayName='Usuario'){ ev.target.onerror=null; ev.target.src=fallbackAvatar(displayName) }
function onShowImgError(ev){ ev.target.onerror=null; ev.target.src=fallbackAvatar('Usuario') }

/* ===== URL helpers ===== */
function absolutizeUrl(u) {
  if (!u) return ''
  if (/^https?:\/\//i.test(u)) return u
  const clean = String(u).replace(/^\/+/, '')
  return API ? `${API}/${clean}` : `/${clean}`
}
function isUsableUrl(v) {
  return typeof v === 'string' && !!v.trim() &&
         v.trim().toLowerCase() !== 'undefined' &&
         v.trim().toLowerCase() !== 'null'
}

/* ===== Normaliza fecha a ISO ===== */
function normalizeDate(v){
  if(!v) return null
  const d=new Date(v); return isNaN(d) ? null : d.toISOString()
}

/* =====================  Estado base  ===================== */
const server = reactive({ pagination: null })
const rows   = ref([])

const searchQuery = ref('')
const filters = reactive({ rol: '' })
const debouncer = makeDebouncer(140)

/* Modales */
const formModalRef = ref(null)
const viewModalRef = ref(null)
const bulkModalRef = ref(null)
let formModal=null, viewModal=null, bulkModal=null

/* Form */
const isEditing = ref(false)
const saving = ref(false)
const errors = reactive({})
const hasErrors = computed(()=>Object.keys(errors).length>0)

/* Secciones del acordeón */
const sec = reactive({ user: true, persona: true, escolar: true })

const form = reactive({
  persona: {
    _id: null,
    nombre: '',
    apellidoPaterno: '',
    apellidoMaterno: '',
    fechaNacimiento: '',
    telefono: '',
    sexo: '',
    // profesor: array de cohortes ["ITI 10 A", ...]
    cohortes: [],
    // alumno/admin: se arma desde los 3 inputs y se envía como string en 'cohorte'
    carrera: '',
    cuatrimestre: '',
    grupo: '',
    matricula: ''
  },
  user: {
    _id: null,
    persona_id: null,
    name: '',
    email: '',
    rol: '',
    estatus: 'activo',
    urlFotoPerfil: ''
  }
})

/* Inputs temporales (chips genéricos si los necesitas en otros lados) */
const tagInputs = reactive({})

/* Visualizar */
const selected = ref(null)

/* Bulk */
const bulkFileRef = ref(null)
const bulk = reactive({ preview: [], running: false, total: 0, done: 0, progress: 0 })

/* ===== UI helpers ===== */
const maxAdultDOB = computed(()=>{ const d=new Date(); d.setFullYear(d.getFullYear()-18); return toDateInputValue(d) })
function asTitle(v){ return (v||'').replace(/_/g,' ').replace(/\b\w/g,m=>m.toUpperCase()) }
function prettyField (f) {
  const map = {
    'persona.nombre':'Nombre','persona.apellidoPaterno':'Apellido paterno','persona.apellidoMaterno':'Apellido materno',
    'persona.fechaNacimiento':'Fecha de nacimiento','persona.telefono':'Teléfono','persona.sexo':'Sexo',
    'persona.matricula':'Matrícula','persona.cohortes':'Grupos del profesor',
    'user.email':'Correo','user.rol':'Rol','user.password':'Contraseña'
  }
  return map[f] || f
}
function badgeRol (rol) {
  switch ((rol || '').toLowerCase()) {
    case 'admin': return 'bg-dark'
    case 'profesor': return 'bg-primary'
    case 'estudiante': return 'bg-success'
    default: return 'bg-secondary'
  }
}
function badgeEstatus (est) {
  const x = (est||'').toLowerCase()
  if (x==='activo') return 'bg-success'
  if (x==='bajasistema') return 'bg-danger'
  if (x==='bajatemporal') return 'bg-warning text-dark'
  return 'bg-secondary'
}
function formatDatePretty (v) {
  const iso = normalizeDate(v)
  if (!iso) return ''
  const d = new Date(iso)
  return d.toLocaleDateString('es-MX', { year: 'numeric', month: 'short', day: '2-digit' })
}
function toNombreCompleto(p){ return [p?.nombre, p?.apellidoPaterno, p?.apellidoMaterno].filter(Boolean).join(' ').trim() }
function arrOrStr(v){ return Array.isArray(v)? v.filter(Boolean).join(', ') : (v||'') }

function buildCohorte(carrera, cuatrimestre, grupo){
  const c = Array.isArray(carrera) ? carrera[0] : carrera
  const q = Array.isArray(cuatrimestre) ? cuatrimestre[0] : cuatrimestre
  const g = Array.isArray(grupo) ? grupo[0] : grupo
  const parts = [c, q, g].map(v => (v ?? '').toString().trim()).filter(Boolean)
  return parts.length ? parts.join(' ') : ''
}

/* ===== Rol actual ===== */
const isProfessor = computed(() => (form.user.rol || '').toLowerCase() === 'profesor')

/* ===== Modelos controlados (alumno/admin) ===== */
const currentCarrera = ref('')
const currentCuatrimestre = ref('')
const currentGrupo = ref('')
const builtCohorteStr = computed(() =>
  buildCohorte(currentCarrera.value, currentCuatrimestre.value, currentGrupo.value)
)
function syncCohorteFieldsIntoForm(){
  form.persona.carrera = currentCarrera.value
  form.persona.cuatrimestre = currentCuatrimestre.value
  form.persona.grupo = currentGrupo.value
}
function onCarreraInput(e){
  currentCarrera.value = (e.target.value || '').toUpperCase().trim()
  syncCohorteFieldsIntoForm()
}
function onCuatriInput(e){
  let v = parseInt(e.target.value || '')
  if (isNaN(v)) v = ''
  if (v !== '') { if (v < 1) v = 1; if (v > 12) v = 12 }
  e.target.value = v
  currentCuatrimestre.value = String(v)
  syncCohorteFieldsIntoForm()
}
function onGrupoInput(e){
  currentGrupo.value = (e.target.value || '').toUpperCase().trim()
  syncCohorteFieldsIntoForm()
}

/* ===== Habilitar botón Agregar (profesor) ===== */
const canAddProfCohorte = computed(() => {
  const c=(currentCarrera.value||'').trim()
  const q=(currentCuatrimestre.value||'').toString().trim()
  const g=(currentGrupo.value||'').trim()
  const validQ=/^\d+$/.test(q) && +q>=1 && +q<=12
  return !!c && !!g && validQ
})
function addProfCohorte(){
  const val = (builtCohorteStr.value || '').toUpperCase().replace(/\s+/g,' ').trim()
  if (!val) return
  if (!form.persona.cohortes.includes(val)) form.persona.cohortes.push(val)
  currentCarrera.value = ''; currentCuatrimestre.value = ''; currentGrupo.value = ''
  syncCohorteFieldsIntoForm()
}
function removeTag(field, idx){
  const arr=form.persona[field]; if(Array.isArray(arr)) arr.splice(idx,1)
}

/* ===== Botón Registrar habilitado ===== */
const canSubmitUser = computed(() => {
  const rol=(form.user.rol||'').toLowerCase()
  if (rol==='profesor') return Array.isArray(form.persona.cohortes) && form.persona.cohortes.length>0
  // alumno/admin: requiere cohorte válido
  return !!builtCohorteStr.value
})

/* ===== Fetch/normalize ===== */
function normalizeUser(u){
  const persona = u.persona || {}
  const nombreCompleto = u.name || toNombreCompleto(persona) || null
  const cohorte = persona?.cohorte ?? buildCohorte(persona?.carrera, persona?.cuatrimestre, persona?.grupo)

  return {
    _uid: String(u._id ?? u.id ?? Math.random()),
    user_id: String(u._id ?? u.id ?? ''),
    persona_id: String(u.persona_id ?? persona?._id ?? persona?.id ?? ''),
    nombreCompleto,
    fechaNacimiento: normalizeDate(persona?.fechaNacimiento) ?? null,
    telefono: persona?.telefono ?? '',
    sexo: persona?.sexo ?? '',
    cohorte,
    matricula: persona?.matricula ?? '',
    rol: (u.rol || '').toLowerCase(),
    estatus: (u.estatus || '').toLowerCase(),
    email: u.email || '',
    urlFotoPerfil: isUsableUrl(u.urlFotoPerfil) ? absolutizeUrl(u.urlFotoPerfil) : '',
    raw: { user: u, persona }
  }
}

async function fetchUsers(pageUrl=null){
  try{
    const usersUrl = pageUrl || USERS_URL
    const [usersResp, personasResp] = await Promise.all([
      axios.get(usersUrl, { headers: authHeaders(), params: pageUrl ? {} : { per_page: 50 } }),
      axios.get(PERSONAS_URL, { headers: authHeaders(), params: { per_page: 1000 } })
    ])

    let users=[], pagination=null
    const udata = usersResp.data
    if (Array.isArray(udata)) users=udata
    else if (udata?.data) {
      users = udata.data
      pagination = { total: udata.total, per_page: udata.per_page, current_page: udata.current_page, last_page: udata.last_page, from: udata.from, to: udata.to, prev: udata.prev_page_url, next: udata.next_page_url }
    } else users = udata || []

    const preg = personasResp.data?.registros || personasResp.data?.data || personasResp.data || []
    const pmap = new Map(preg.map(p => [String(p._id ?? p.id), p]))

    const joined = users.map(u => {
      const pid = String(u.persona_id ?? u.persona?._id ?? '')
      const persona = pmap.get(pid) || u.persona || {}
      return { ...u, persona }
    })

    rows.value = joined.map(normalizeUser)
    server.pagination = pagination
  }catch(e){
    console.error(e)
    rows.value = []
    server.pagination = null
    toast('No fue posible cargar los usuarios.', 'error')
  }
}
function goPage(url){ if (url) fetchUsers(url) }

/* ===== Filtros / búsqueda ===== */
const filteredRows = computed(() => {
  let q = (searchQuery.value || '').toLowerCase()
  return rows.value.filter(u => {
    const rolOk = filters.rol ? (u.rol === filters.rol) : true
    const estOk = filters.estatus ? (u.estatus === filters.estatus) : true
    const bag = [
      u.nombreCompleto, u.matricula, u.email, u.telefono, arrOrStr(u.cohorte)
    ].join(' ').toLowerCase()
    const qOk = !q || bag.includes(q)
    return rolOk && qOk
  })
})
function onInstantSearch(){ debouncer(()=>{}) }
function clearSearch(){ searchQuery.value='' }

/* ===== Lifecycle ===== */
async function mountInit () {
  formModal = new Modal(formModalRef.value)
  viewModal  = new Modal(viewModalRef.value)
  bulkModal  = new Modal(bulkModalRef.value)
}

/* ===== Modal Form ===== */
function hideModal(){ formModal.hide() }
function clearErrors(){ Object.keys(errors).forEach(k=>delete errors[k]) }
function resetForm(){
  Object.assign(form.persona, {
    _id:null, nombre:'', apellidoPaterno:'', apellidoMaterno:'',
    fechaNacimiento:'', telefono:'', sexo:'',
    cohortes:[],
    carrera:'', cuatrimestre:'', grupo:'',
    matricula:''
  })
  Object.assign(form.user, { _id:null, persona_id:null, name:'', email:'', rol:'', estatus:'activo', urlFotoPerfil:'' })
  clearErrors()
  touch.email=false; touch.telefono=false; touch.fecha=false
  currentCarrera.value=''; currentCuatrimestre.value=''; currentGrupo.value=''
}
function openCreate(){
  isEditing.value = false
  resetForm()
  sec.user = sec.persona = sec.escolar = true
  formModal.show()
}
function openEdit(row){
  isEditing.value = true
  resetForm()

  const p = row?.raw?.persona || {}
  form.persona._id = row.persona_id || null
  form.persona.nombre = p?.nombre || ''
  form.persona.apellidoPaterno = p?.apellidoPaterno || ''
  form.persona.apellidoMaterno = p?.apellidoMaterno || ''
  form.persona.fechaNacimiento = toDateInputValue(new Date(p?.fechaNacimiento || '')) || ''
  form.persona.telefono = p?.telefono || ''
  form.persona.sexo = p?.sexo || ''
  form.persona.matricula = p?.matricula || ''

  // Si viene cohorte como array → profesor; si es string → alumno/admin
  if (Array.isArray(p?.cohorte)) {
    form.user.rol = 'profesor'
    form.persona.cohortes = p.cohorte.slice()
  } else {
    const coh = p?.cohorte || row.cohorte || ''
    const { carrera, cuatrimestre, grupo } = parseCohorte(coh)
    currentCarrera.value = (carrera || '').toUpperCase()
    currentCuatrimestre.value = String(cuatrimestre || '')
    currentGrupo.value = (grupo || '').toUpperCase()
    syncCohorteFieldsIntoForm()
  }

  form.user._id = row.user_id || null
  form.user.persona_id = row.persona_id || null
  form.user.name = row.nombreCompleto || ''
  form.user.email = row.email || ''
  form.user.rol = form.user.rol || row.rol || ''
  form.user.estatus = 'activo'
  form.user.urlFotoPerfil = row.urlFotoPerfil || ''

  formModal.show()
}

/* ===== Upload opcional de foto ===== */
async function uploadPhotoIfAny(file){
  if(!file) return null
  const fd = new FormData()
  fd.append('foto', file, file.name || 'foto.jpg')
  const { data } = await axios.post(UPLOAD_URL, fd, { headers: { ...authHeaders(), 'Content-Type': 'multipart/form-data' } })
  return data?.url || data?.path || data?.location || ''
}

/* ===== Validaciones ===== */
const touch = reactive({ email:false, telefono:false, fecha:false })
const emailInvalid = computed(()=> touch.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.user.email||'') )
const phoneInvalid = computed(()=> touch.telefono && !/^\d{10}$/.test(form.persona.telefono||'') )
const isAdult = computed(()=>{
  const v=form.persona.fechaNacimiento; if(!v) return false
  const dob=new Date(v); if(isNaN(dob)) return false
  const today=new Date(); let age=today.getFullYear()-dob.getFullYear()
  const m=today.getMonth()-dob.getMonth()
  if(m<0 || (m===0 && today.getDate()<dob.getDate())) age--
  return age>=18
})
function allowOnlyDigits(evt){
  const k=evt.key
  if(!/[0-9]/.test(k) && !['Backspace','Delete','ArrowLeft','ArrowRight','Tab'].includes(k)) evt.preventDefault()
}
function onPhoneInput(e){
  touch.telefono=true
  e.target.value=(e.target.value||'').replace(/\D+/g,'').slice(0,10)
  form.persona.telefono=e.target.value
}

// Parsear cohorte "CARRERA CUAT GRUPO" => { carrera, cuatrimestre, grupo }
function parseCohorte(str){
  if (!str || typeof str !== 'string') return { carrera:'', cuatrimestre:'', grupo:'' }
  const parts = str.trim().split(/\s+/)
  if (parts.length < 3) return { carrera: parts[0] || '', cuatrimestre: parts[1] || '', grupo: parts[2] || '' }
  const grupo = parts.pop()
  const cuatrimestre = parts.pop()
  const carrera = parts.join(' ')
  return { carrera, cuatrimestre, grupo }
}

// Nombre visible desde el form.persona (para previews)
function displayNameFromPersona(){
  return toNombreCompleto(form.persona) || 'Usuario'
}
// Restricción de letras/espacios
function allowOnlyLettersSpaces(evt){
  const k = evt.key
  if (!/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]$/.test(k) && !['Backspace','Delete','ArrowLeft','ArrowRight','Tab'].includes(k)) {
    evt.preventDefault()
  }
}
function onLettersPaste(evt){
  const t = (evt.clipboardData?.getData('text') || '').replace(/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]+/g,' ').trim()
  evt.preventDefault()
  const el = evt.target
  const start = el.selectionStart, end = el.selectionEnd
  el.value = el.value.slice(0,start) + t + el.value.slice(end)
  el.dispatchEvent(new Event('input', { bubbles:true }))
}

function clearErrorsObj(){ Object.keys(errors).forEach(k=>delete errors[k]) }
function validateFront(){
  clearErrorsObj()
  const errs={}

  if(!isRequired(form.user.rol)) errs['user.rol']=['El rol es obligatorio.']
  
  if(!isRequired(form.user.email) || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.user.email)) errs['user.email']=['El correo es inválido.']

  if(!isRequired(form.persona.nombre)) errs['persona.nombre']=['El nombre es obligatorio.']
  
  if(!isRequired(form.persona.apellidoPaterno)) errs['persona.apellidoPaterno']=['El apellido paterno es obligatorio.']
  
  if(!isRequired(form.persona.fechaNacimiento)) errs['persona.fechaNacimiento']=['La fecha de nacimiento es obligatoria.']
  
  if(isRequired(form.persona.fechaNacimiento) && !isAdult.value) errs['persona.fechaNacimiento']=['Debes ser mayor de 18 años.']
  
  if(!/^\d{10}$/.test(form.persona.telefono||'')) errs['persona.telefono']=['El teléfono debe tener 10 dígitos.']
  
  if(!isRequired(form.persona.sexo)) errs['persona.sexo']=['El sexo es obligatorio.']
  
  if(!isRequired(form.persona.matricula)) errs['persona.matricula']=['La matrícula es obligatoria.']

  const rol=(form.user.rol||'').toLowerCase()
  if(rol==='profesor'){
    if(!form.persona.cohortes.length) errs['persona.cohortes']=['Agrega al menos un grupo (cohorte).']
  } else {
    if(!builtCohorteStr.value) errs['persona.cohorte']=['Completa carrera, cuatrimestre y grupo.']
  }

  if(Object.keys(errs).length){ Object.assign(errors,errs); return false }
  return true
}

/* ====== Extractor ultra-robusto de _id ====== */
function extractIdDeep(payload, headers){
  const fromHeaders = () => {
    const h = headers || {}
    const loc = h.location || h.Location
    if (loc && typeof loc === 'string') {
      const seg = loc.split('/').filter(Boolean).pop()
      if (seg && HEX24.test(seg)) return seg
    }
    const hx = h['x-resource-id'] || h['x-id'] || h['x_object_id']
    if (typeof hx === 'string') return hx
    return null
  }
  const unwrapId = (v) => {
    if (!v) return null
    if (typeof v === 'string') return v
    if (typeof v === 'object') {
      if (v.$oid && typeof v.$oid === 'string') return v.$oid
      if (v.oid  && typeof v.oid  === 'string') return v.oid
      if (v._id) return unwrapId(v._id)
      if (v.id)  return unwrapId(v.id)
    }
    return null
  }
  const deep = (o, seen=new Set()) => {
    if (!o || typeof o!=='object' || seen.has(o)) return null
    seen.add(o)
    if (Object.prototype.hasOwnProperty.call(o,'_id')) {
      const v = unwrapId(o._id); if (v) return v
    }
    if (Object.prototype.hasOwnProperty.call(o,'id')) {
      const v = unwrapId(o.id); if (v) return v
    }
    for (const k of Object.keys(o)) {
      const val = o[k]
      const prim = unwrapId(val); if (prim) return prim
      const d = deep(val, seen); if (d) return d
    }
    return null
  }
  const byData =
    payload?.persona?._id ?? payload?.persona?.id ??
    payload?.data?.persona?._id ?? payload?.data?.persona?.id ??
    payload?._id ?? payload?.id ?? payload?.insertedId ?? null

  const id = unwrapId(byData) || deep(payload) || fromHeaders()
  return id && HEX24.test(String(id)) ? String(id) : (id || null)
}

/* ===== Envío ===== */
async function findPersonaByMatricula (matricula) {
  if (!matricula) return null
  try {
    const { data } = await axios.get(PERSONAS_URL, { headers: authHeaders(), params: { matricula, per_page: 1 } })
    const arr = Array.isArray(data) ? data
              : Array.isArray(data?.data) ? data.data
              : Array.isArray(data?.registros) ? data.registros
              : []
    const p = arr[0]
    const id = extractIdDeep(p, null)
    return id ? String(id) : null
  } catch { return null }
}

async function createOrUpdatePersona(){
  const isProf = (form.user.rol || '').toLowerCase() === 'profesor'

  const personaPayload = {
    nombre: form.persona.nombre,
    apellidoPaterno: form.persona.apellidoPaterno,
    apellidoMaterno: form.persona.apellidoMaterno,
    fechaNacimiento: form.persona.fechaNacimiento,
    telefono: form.persona.telefono,
    sexo: form.persona.sexo,
    matricula: form.persona.matricula,
    // 👇 clave: SOLO 'cohorte'
    cohorte: isProf
      ? form.persona.cohortes.slice()
      : builtCohorteStr.value
  }

  let personaId = form.persona._id
  if (personaId) {
    await axios.put(`${PERSONAS_URL}/${personaId}`, personaPayload, { headers: authHeaders() })
    return String(personaId)
  }

  const resp = await axios.post(PERSONAS_URL, personaPayload, { headers: authHeaders() })
  let newId = extractIdDeep(resp?.data, resp?.headers)
  if (!newId) newId = await findPersonaByMatricula(form.persona.matricula)
  if (!newId) throw new Error('No se pudo obtener el ID de persona después de crearla.')
  form.persona._id = String(newId)
  return String(newId)
}

async function createOrUpdateUser(personaId){
  const displayName = toNombreCompleto({
    nombre: form.persona.nombre,
    apellidoPaterno: form.persona.apellidoPaterno,
    apellidoMaterno: form.persona.apellidoMaterno
  })
  const userPayloadBase = {
    name: displayName,
    email: form.user.email,
    rol: form.user.rol,
    estatus: 'activo',
    urlFotoPerfil: form.user.urlFotoPerfil || null,
    persona_id: personaId,
    matricula: form.persona.matricula,
    notify_email: !isEditing.value
  }

  if (form.user._id) {
    await axios.put(`${USERS_URL}/${form.user._id}`, userPayloadBase, { headers: authHeaders() })
    return
  }

  try {
    await axios.post(USERS_URL, userPayloadBase, { headers: authHeaders() })
  } catch (e) {
    const resp = e?.response?.data
    const status = e?.response?.status
    if (status === 422 && resp?.errors && (resp.errors.password || resp.errors['user.password'])) {
      const rnd = Math.random().toString(36).slice(2,10) + 'Aa1*'
      const withPass = { ...userPayloadBase, password: rnd }
      await axios.post(USERS_URL, withPass, { headers: authHeaders() })
      return
    }
    throw e
  }
}

async function onSubmit(){
  touch.email=true; touch.telefono=true; touch.fecha=true
  if(!validateFront()){ toast('Revisa los campos obligatorios.','error'); return }
  if(!canSubmitUser.value){ toast('Completa el cohorte antes de registrar.','error'); return }

  saving.value=true
  try{
    const personaId = await createOrUpdatePersona()
    await createOrUpdateUser(personaId)
    await fetchUsers()
    hideModal()
    toast('Registro guardado.')
  } catch (e) {
    console.error('onSubmit error =>', e)
    const resp = e.response?.data
    Object.assign(errors, resp?.errors || {})
    toast(resp?.message || 'Los datos proporcionados no son válidos.', 'error')
  } finally {
    saving.value=false
  }
}

/* ===== View / Delete / Bulk ===== */
async function ensureRowHydrated(row){
  if(row?.raw?.persona && (row.raw.persona._id || row.persona_id)) return row
  const pid = row?.persona_id
  if(!pid) return row
  try{
    const { data } = await axios.get(`${PERSONAS_URL}/${pid}`, { headers: authHeaders() })
    const persona = data?.persona || data || {}
    const merged = normalizeUser({ ...(row.raw?.user || {}), persona, persona_id: pid, _id: row.user_id })
    return merged
  }catch{ return row }
}
async function openView (row) {
  const hydrated = await ensureRowHydrated(row)
  selected.value = hydrated
  viewModal.show()
}
function hideView () { viewModal.hide(); selected.value = null }
async function modifyFromView () { if (!selected.value) return; const row = { ...selected.value }; hideView(); await nextTick(); openEdit(row) }
async function deleteFromView () { if (!selected.value) return; const row = { ...selected.value }; hideView(); await nextTick(); await confirmDelete(row) }

async function confirmDelete (row) {
  const result = await Swal.fire({
    title: '¿Eliminar usuario?',
    text: 'Se eliminará el usuario y su registro de persona asociado.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  })
  if (!result.isConfirmed) return
  try {
    const uid = row.user_id
    const pid = row.persona_id
    if (uid) await axios.delete(`${USERS_URL}/${uid}`, { headers: authHeaders() })
    if (pid) await axios.delete(`${PERSONAS_URL}/${pid}`, { headers: authHeaders() })
    await fetchUsers()
    toast('Usuario eliminado.')
  } catch (e) {
    console.error(e)
    toast('Error al eliminar.', 'error')
  }
}

/* ===== Bulk ===== */
function loadScript(src){ return new Promise((resolve, reject)=>{ const el=document.createElement('script'); el.src=src; el.async=true; el.onload=()=>resolve(); el.onerror=reject; document.head.appendChild(el) }) }
async function tryLoadXLSX(){ if(window.XLSX) return window.XLSX; try{ await loadScript('https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js'); return window.XLSX||null }catch{ return null } }
function openBulkModal(){ bulk.preview=[]; bulk.running=false; bulk.total=0; bulk.done=0; bulk.progress=0; bulkFileRef.value && (bulkFileRef.value.value=''); bulkModal.show() }
function hideBulk(){ bulkModal.hide() }
async function onBulkFileSelected(e){
  const f=e.target.files?.[0]; if(!f) return
  const name=(f.name||'').toLowerCase()
  try{
    if(name.endsWith('.json')){
      const text=await f.text(); const arr=JSON.parse(text); bulk.preview=Array.isArray(arr)?arr:[]
    }else if(name.endsWith('.csv')){
      const text=await f.text(); bulk.preview=parseCSV(text)
    }else{
      const XLSX = await tryLoadXLSX(); if(!XLSX){ toast('No se pudo cargar XLSX por CDN','error'); bulk.preview=[]; return }
      const buf = await f.arrayBuffer(); const wb=XLSX.read(buf); const ws=wb.Sheets[wb.SheetNames[0]]; bulk.preview = XLSX.utils.sheet_to_json(ws)
    }
  }catch(err){ console.error(err); toast('No se pudo leer el archivo. Verifica el formato.','error'); bulk.preview=[] }
}
function parseCSV(text){
  const lines=text.split(/\r?\n/).filter(Boolean); if(!lines.length) return []
  const headers=lines[0].split(',').map(h=>h.trim()); const out=[]
  for(let i=1;i<lines.length;i++){ const row={}; const cols=splitCSVLine(lines[i]); headers.forEach((h,idx)=>row[h]=(cols[idx]??'').trim()); out.push(row) }
  return out
}
function splitCSVLine(line){ const res=[]; let cur='', inQ=false; for(let i=0;i<line.length;i++){ const c=line[i]; if(c=== '"'){ if(inQ && line[i+1]==='"'){ cur+='"'; i++ } else inQ=!inQ } else if(c===',' && !inQ){ res.push(cur); cur='' } else cur+=c } res.push(cur); return res }
async function startBulk(){
  if(!bulk.preview.length) return
  bulk.running=true; bulk.total=bulk.preview.length; bulk.done=0; bulk.progress=0
  for(const raw of bulk.preview){
    try{
      const coh =
        Array.isArray(raw.cohorte) ? raw.cohorte.filter(Boolean).map(s=>String(s)) :
        (raw.cohorte ? String(raw.cohorte) : buildCohorte(raw.carrera, raw.cuatrimestre, raw.grupo))

      const persona = {
        nombre: raw.nombre ?? '',
        apellidoPaterno: raw.apellidoPaterno ?? '',
        apellidoMaterno: raw.apellidoMaterno ?? '',
        fechaNacimiento: raw.fechaNacimiento ?? '',
        telefono: raw.telefono ?? '',
        sexo: raw.sexo ?? '',
        cohorte: coh,
        matricula: raw.matricula ?? ''
      }
      const presp = await axios.post(PERSONAS_URL, persona, { headers: authHeaders() })
      const personaId = extractIdDeep(presp?.data, presp?.headers) || await findPersonaByMatricula(persona.matricula)
      if(!personaId) throw new Error('Bulk: no se pudo extraer persona_id')

      const user = {
        name: toNombreCompleto(persona),
        email: raw.email ?? '',
        rol: (raw.rol ?? '').toLowerCase(),
        estatus: 'activo',
        persona_id: String(personaId),
        matricula: persona.matricula,
        notify_email: true
      }
      await axios.post(USERS_URL, user, { headers: authHeaders() })
    }catch(e){ console.error('Fila con error:', e) }
    finally{ bulk.done++; bulk.progress=Math.round(bulk.done*100/bulk.total) }
  }
  await fetchUsers(); toast('Importación finalizada.'); bulk.running=false
}

/* ===== useUsuarios ===== */
export function useUsuarios () {
  fetchUsers()
  onMounted(mountInit)

  return {
    server, rows, filteredRows, searchQuery, filters,
    form, isEditing, saving, errors, hasErrors, sec,
    tagInputs, selected, bulk, bulkFileRef,
    formModalRef, viewModalRef, bulkModalRef,
    touch,
    safeImg, onImgError, onFormImgError, onShowImgError,
    badgeRol, badgeEstatus, asTitle, formatDatePretty, arrOrStr,
    maxAdultDOB, emailInvalid, phoneInvalid, isAdult,

    // avatar / nombres
    toNombreCompleto, avatarNameFromRow: (row)=> {
      if (!row) return 'Usuario'
      if (row.nombreCompleto) return row.nombreCompleto
      const p = row.raw?.persona || {}
      const name = toNombreCompleto(p)
      return name || 'Usuario'
    },
    displayNameFromPersona,

    // inputs y validaciones varias
    onPhoneInput, allowOnlyDigits, allowOnlyLettersSpaces, onLettersPaste,

    // flujo
    openCreate, openEdit, hideModal, onSubmit,
    openView, hideView, modifyFromView, deleteFromView, confirmDelete,
    onInstantSearch, clearSearch, fetchUsers, goPage,
    openBulkModal, hideBulk, onBulkFileSelected, startBulk,
    removeTag,

    // cohorte
    buildCohorte, parseCohorte,
    currentCarrera, currentCuatrimestre, currentGrupo,
    onCarreraInput, onCuatriInput, onGrupoInput,

    // rol profesor
    isProfessor, canAddProfCohorte, addProfCohorte, canSubmitUser,

    prettyField,
  }
}
