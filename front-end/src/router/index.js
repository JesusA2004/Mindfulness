import { createRouter, createWebHistory } from 'vue-router'

// Componente de error 404
import ComponenteNoEncontrado from '@/views/NoEncontrado.vue'

// Layouts
import PublicLayout from '@/layouts/PublicLayout.vue'
import LoginLayout  from '@/layouts/LoginLayout.vue'
import ProfesorLayout from '@/layouts/ProfesorLayout.vue'
import EstudianteLayout from '@/layouts/EstudianteLayout.vue'
import AdminLayout from '@/layouts/AdminLayout.vue'

// Vistas publicas
import Index         from '@/views/Index.vue'
import Login         from '@/views/Login.vue'
import SobreNosotros from '@/views/SobreNosotros.vue'
import Contacto      from '@/views/Contacto.vue'
import Perfil      from '@/views/Perfil.vue'
import Crud         from '@/views/Crud.vue'

// Dashboard basado en el rol
import ProfesorHome      from '@/views/profesor/Home.vue'
import EstudianteHome    from '@/views/estudiante/Home.vue'
import AdministradorHome from '@/views/administrador/Home.vue'

// Vistas de acuerdo al rol

// Profesor
import ActividadesP from '@/views/profesor/Actividades.vue'
import CitasP from '@/views/profesor/Citas.vue'
import EncuestasP from '@/views/profesor/Encuestas.vue'
import RecompensasP from '@/views/profesor/Recompensas.vue'
import TecnicasP from '@/views/profesor/Tecnicas.vue'
import TestsP from '@/views/profesor/Tests.vue'

// Estudiante
import ActividadesE from '@/views/estudiante/Actividades.vue'
import BitacorasE from '@/views/estudiante/Bitacoras.vue'
import CitasE from '@/views/estudiante/Citas.vue'
import EncuestasE from '@/views/estudiante/Encuestas.vue'
import RecompensasE from '@/views/estudiante/Recompensas.vue'
import TecnicasE from '@/views/estudiante/Tecnicas.vue'
import TestsE from '@/views/estudiante/Tests.vue'

// Admin
import ActividadesA from '@/views/administrador/Actividades.vue'
import CitasA from '@/views/administrador/Citas.vue'
import EncuestasA from '@/views/administrador/Encuestas.vue'
import RecompensasA from '@/views/administrador/Recompensas.vue'
import TecnicasA from '@/views/administrador/Tecnicas.vue'
import TestsA from '@/views/administrador/Tests.vue'
import RespaldoA from '@/views/administrador/RespaldoBD.vue'
import ReportesA from '@/views/administrador/Reportes.vue'

const routes = [
  {
    path: '/',
    component: PublicLayout,
    children: [
      { path: '',               name: 'IndexPage',         component: Index },
      { path: 'login',          name: 'LoginPage',         component: Login },
      { path: 'sobre-nosotros', name: 'SobreNosotrosPage', component: SobreNosotros },
      { path: 'contacto',       name: 'ContactoPage',      component: Contacto }
    ]
  },
  {
    path: '/app',
    component: LoginLayout,

    children: [
      // RUTA COMÚN PARA PERFIL
      {
        path: 'perfil',
        name: 'Perfil',
        component: Perfil,
        meta: { requiresAuth: true }
      },
      {
        path: 'profesor',
        component: ProfesorLayout,
        meta: { requiresAuth: true, role: 'profesor' },
        children: [
          {
            path: 'dashboard',
            name: 'ProfesorHome',
            component: ProfesorHome
          },
          {
            path: 'actividades',
            name: 'ActividadesP',
            component: ActividadesP
          },
          {
            path: 'citas',
            name: 'CitasP',
            component: CitasP
          },
          {
            path: 'encuestas',
            name: 'EncuestasP',
            component: EncuestasP
          },
          {
            path: 'recompensas',
            name: 'RecompensasP',
            component: RecompensasP
          },
          {
            path: 'tecnicas',
            name: 'TecnicasP',
            component: TecnicasP
          },
          {
            path: 'tests',
            name: 'TestsP',
            component: TestsP
          }
        ]
      },
      {
        path: 'estudiante',
        component: EstudianteLayout,
        meta: { requiresAuth: true, role: 'estudiante' },
        children: [
          {
            path: 'dashboard',
            name: 'EstudianteHome',
            component: EstudianteHome
          },
          {
            path: 'actividades',
            name: 'ActividadesE',
            component: ActividadesE
          },
          {
            path: 'bitacoras',
            name: 'BitacorasE',
            component: BitacorasE
          },
          {
            path: 'citas',
            name: 'CitasE',
            component: CitasE
          },
          {
            path: 'encuestas',
            name: 'EncuestasE',
            component: EncuestasE
          },
          {
            path: 'recompensas',
            name: 'RecompensasE',
            component: RecompensasE
          },
          {
            path: 'tecnicas',
            name: 'TecnicasE',
            component: TecnicasE
          },
          {
            path: 'tests',
            name: 'TestsE',
            component: TestsE
          },
          { path: 'crud',        
            name: 'CrudPage',
            component: Crud 
          }
        ]
      },
      {
        path: 'admin',
        component: AdminLayout,
        meta: { requiresAuth: true, role: 'admin' },
        children: [
          {
            path: 'dashboard',
            name: 'AdministradorHome',
            component: AdministradorHome
          },
          {
            path: 'actividades',
            name: 'ActividadesA',
            component: ActividadesA
          },
          {
            path: 'citas',
            name: 'CitasA',
            component: CitasA
          },
          {
            path: 'encuestas',
            name: 'EncuestasA',
            component: EncuestasA
          },
          {
            path: 'recompensas',
            name: 'RecompensasA',
            component: RecompensasA
          },
          {
            path: 'tecnicas',
            name: 'TecnicasA',
            component: TecnicasA
          },
          {
            path: 'tests',
            name: 'TestsA',
            component: TestsA
          },
          {
            path: 'respaldo',
            name: 'RespaldoA',
            component: RespaldoA
          },
          {
            path: 'reportes',
            name: 'ReportesA',
            component: ReportesA
          }
        ]
      }
    ]
  },
  {
    path: '/:pathMatch(.*)*',
    component: ComponenteNoEncontrado 
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Función para verificar si el token está expirado
function esTokenExpirado(token) {
  try {
    const payload = JSON.parse(atob(token.split('.')[1])) // Decodifica el payload del JWT
    const exp = payload.exp * 1000 // Convierte a milisegundos
    return Date.now() > exp
  } catch {
    return true // Si hay error al decodificar, considera el token como expirado
  }
}

export default router
