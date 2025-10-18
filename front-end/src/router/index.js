// src/router/index.js
import { createRouter, createWebHistory } from 'vue-router'

// 404
import ComponenteNoEncontrado from '@/views/NoEncontrado.vue'

// Layouts
import PublicLayout from '@/layouts/PublicLayout.vue'
import LoginLayout  from '@/layouts/LoginLayout.vue'
import ProfesorLayout from '@/layouts/ProfesorLayout.vue'
import EstudianteLayout from '@/layouts/EstudianteLayout.vue'
import AdminLayout from '@/layouts/AdminLayout.vue'

// Públicas
import Index         from '@/views/Index.vue'
import Login         from '@/views/Login.vue'
import SobreNosotros from '@/views/SobreNosotros.vue'
import Contacto      from '@/views/Contacto.vue'

// Comunes protegidas
import Perfil        from '@/views/Perfil.vue'
import Crud          from '@/views/Crud.vue'

// Dashboards
import ProfesorHome      from '@/views/profesor/Home.vue'
import EstudianteHome    from '@/views/estudiante/Home.vue'
import AdministradorHome from '@/views/administrador/Home.vue'

// Profesor
import ActividadesP from '@/views/profesor/Actividades.vue'
import CitasP       from '@/views/profesor/Citas.vue'
import EncuestasP   from '@/views/profesor/Encuestas.vue'
import RecompensasP from '@/views/profesor/Recompensas.vue'
import TecnicasP    from '@/views/profesor/Tecnicas.vue'
import TestsP       from '@/views/profesor/Tests.vue'

// Estudiante
import ActividadesE from '@/views/estudiante/Actividades.vue'
import BitacorasE   from '@/views/estudiante/Bitacoras.vue'
import CitasE       from '@/views/estudiante/Citas.vue'
import EncuestasE   from '@/views/estudiante/Encuestas.vue'
import RecompensasE from '@/views/estudiante/Recompensas.vue'
import TecnicasE    from '@/views/estudiante/Tecnicas.vue'
import TestsE       from '@/views/estudiante/Tests.vue'

// Admin
import ActividadesA from '@/views/administrador/Actividades.vue'
import CitasA       from '@/views/administrador/Citas.vue'
import EncuestasA   from '@/views/administrador/Encuestas.vue'
import RecompensasA from '@/views/administrador/Recompensas.vue'
import TecnicasA    from '@/views/administrador/Tecnicas.vue'
import TestsA       from '@/views/administrador/Tests.vue'
import RespaldoA    from '@/views/administrador/RespaldoBD.vue'
import ReportesA    from '@/views/administrador/Reportes.vue'
import UsuariosA    from '@/views/administrador/Usuarios.vue'

const routes = [
  {
    path: '/',
    component: PublicLayout,
    children: [
      { path: '',               name: 'IndexPage',         component: Index },
      { path: 'login',          name: 'LoginPage',         component: Login, meta: { guestOnly: true } },
      { path: 'sobre-nosotros', name: 'SobreNosotrosPage', component: SobreNosotros },
      { path: 'contacto',       name: 'ContactoPage',      component: Contacto }
    ]
  },
  {
    path: '/app',
    component: LoginLayout,
    children: [
      // Común protegida
      { path: 'perfil', name: 'Perfil', component: Perfil, meta: { requiresAuth: true } },

      // Profesor
      {
        path: 'profesor',
        component: ProfesorLayout,
        meta: { requiresAuth: true, role: 'profesor' },
        children: [
          { path: '',            redirect: { name: 'ProfesorHome' } }, // default → dashboard
          { path: 'dashboard',   name: 'ProfesorHome',   component: ProfesorHome },
          { path: 'actividades', name: 'ActividadesP',   component: ActividadesP },
          { path: 'citas',       name: 'CitasP',         component: CitasP },
          { path: 'encuestas',   name: 'EncuestasP',     component: EncuestasP },
          { path: 'recompensas', name: 'RecompensasP',   component: RecompensasP },
          { path: 'tecnicas',    name: 'TecnicasP',      component: TecnicasP },
          { path: 'tests',       name: 'TestsP',         component: TestsP },
        ]
      },

      // Estudiante
      {
        path: 'estudiante',
        component: EstudianteLayout,
        meta: { requiresAuth: true, role: 'estudiante' },
        children: [
          { path: '',            redirect: { name: 'EstudianteHome' } },
          { path: 'dashboard',   name: 'EstudianteHome', component: EstudianteHome },
          { path: 'actividades', name: 'ActividadesE',   component: ActividadesE },
          { path: 'bitacoras',   name: 'BitacorasE',     component: BitacorasE },
          { path: 'citas',       name: 'CitasE',         component: CitasE },
          { path: 'encuestas',   name: 'EncuestasE',     component: EncuestasE },
          { path: 'recompensas', name: 'RecompensasE',   component: RecompensasE },
          { path: 'tecnicas',    name: 'TecnicasE',      component: TecnicasE },
          { path: 'tests',       name: 'TestsE',         component: TestsE },
          { path: 'crud',        name: 'CrudPage',       component: Crud }
        ]
      },

      // Admin
      {
        path: 'admin',
        component: AdminLayout,
        meta: { requiresAuth: true, role: 'admin' },
        children: [
          { path: '',            redirect: { name: 'AdministradorHome' } },
          { path: 'dashboard',   name: 'AdministradorHome', component: AdministradorHome },
          { path: 'usuarios',    name: 'UsuariosA',         component: UsuariosA },
          { path: 'actividades', name: 'ActividadesA',      component: ActividadesA },
          { path: 'citas',       name: 'CitasA',            component: CitasA },
          { path: 'encuestas',   name: 'EncuestasA',        component: EncuestasA },
          { path: 'recompensas', name: 'RecompensasA',      component: RecompensasA },
          { path: 'tecnicas',    name: 'TecnicasA',         component: TecnicasA },
          { path: 'tests',       name: 'TestsA',            component: TestsA },
          { path: 'respaldo',    name: 'RespaldoA',         component: RespaldoA },
          { path: 'reportes',    name: 'ReportesA',         component: ReportesA }
        ]
      }
    ]
  },
  { path: '/:pathMatch(.*)*', component: ComponenteNoEncontrado }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

/* ===================== Helpers JWT ===================== */

// Base64URL-safe decoder (JWT usa - y _)
function base64UrlDecode (str) {
  try {
    const pad = (s) => s + '='.repeat((4 - (s.length % 4)) % 4)
    const b64 = pad(str.replace(/-/g, '+').replace(/_/g, '/'))
    return atob(b64)
  } catch {
    return null
  }
}

function getToken () {
  const t = localStorage.getItem('token')
  if (!t || t === 'undefined' || t === 'null') return ''
  return t
}

function decodePayload (token) {
  try {
    const part = token.split('.')[1]
    const json = base64UrlDecode(part)
    return json ? JSON.parse(json) : null
  } catch {
    return null
  }
}

function esTokenExpirado (token) {
  const p = decodePayload(token)
  if (!p || !p.exp) return true
  return Date.now() >= p.exp * 1000
}

// === Rol: intenta token y luego localStorage.user (tu login trae user.rol)
function getStoredRole () {
  try {
    const raw = localStorage.getItem('user')
    if (!raw) return null
    const u = JSON.parse(raw)
    return u?.role ?? u?.rol ?? u?.perfil ?? null
  } catch { return null }
}

function getRole () {
  const p = decodePayload(getToken()) || {}
  return p.role ?? p.rol ?? p.perfil ?? getStoredRole()
}

function dashboardPorRol (rol) {
  switch (rol) {
    case 'profesor':   return { name: 'ProfesorHome' }
    case 'estudiante': return { name: 'EstudianteHome' }
    case 'admin':      return { name: 'AdministradorHome' }
    default:           return { name: 'Perfil' }
  }
}

/* ===================== Guard Global ===================== */
router.beforeEach((to, from, next) => {
  const requiereAuth = to.matched.some(r => r.meta?.requiresAuth)
  // ⬇️ Corrige: lee meta.role (no meta.rol)
  const rolRequerido = to.matched.find(r => r.meta?.role)?.meta?.role || null

  const token = getToken()
  const tokenValido = token && !esTokenExpirado(token)

  // 1) Rutas solo invitados (login)
  if (to.matched.some(r => r.meta?.guestOnly)) {
    if (tokenValido) {
      const rol = getRole()
      return next(rol ? dashboardPorRol(rol) : { name: 'Perfil' })
    }
    return next()
  }

  // 2) Rutas públicas normales
  if (!requiereAuth) return next()

  // 3) Rutas protegidas
  if (!tokenValido) {
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    return next({ name: 'LoginPage', query: { redirect: to.fullPath } })
  }

  // 4) Verificar rol si la ruta lo exige
  if (rolRequerido) {
    const rolActual = getRole()
    if (!rolActual || rolActual !== rolRequerido) {
      return next(dashboardPorRol(rolActual))
    }
  }

  return next()
})

export default router
