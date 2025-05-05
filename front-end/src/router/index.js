import { createRouter, createWebHistory } from 'vue-router'

// Componente de error 404
import ComponenteNoEncontrado from '@/views/NoEncontrado.vue'

// Layouts
import PublicLayout from '@/layouts/PublicLayout.vue'
import LoginLayout  from '@/layouts/LoginLayout.vue'
import ProfesorLayout from '@/layouts/ProfesorLayout.vue'

// Vistas publicas
import Index         from '@/views/Index.vue'
import Login         from '@/views/Login.vue'
import SobreNosotros from '@/views/SobreNosotros.vue'
import Contacto      from '@/views/Contacto.vue'

// Dashboard basado en el rol
import ProfesorHome      from '@/views/profesor/Home.vue'
import EstudianteHome    from '@/views/estudiante/Home.vue'
import AdministradorHome from '@/views/administrador/Home.vue'

// Vistas deacuerdo al rol

// Profesor
import Asignaciones from '@/views/profesor/Asignaciones.vue'
import Evaluaciones from '@/views/profesor/Evaluaciones.vue'

// Estudiante

// Admin


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
            path: 'asignaciones',
            name: 'Asignaciones',
            component: Asignaciones
          },
          {
            path: 'evaluaciones',
            name: 'Evaluaciones',
            component: Evaluaciones
          }
        ]
      },
      {
        path: 'estudiante',
        name: 'EstudianteHome',
        component: EstudianteHome,
        meta: { requiresAuth: true, role: 'estudiante' }
      },
      {
        path: 'administrador',
        name: 'AdministradorHome',
        component: AdministradorHome,
        meta: { requiresAuth: true, role: 'admin' }
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

// Guardia global para verificar auth + rol
router.beforeEach((to, from, next) => {

  const token = localStorage.getItem('token')
  const user  = JSON.parse(localStorage.getItem('user')||'null')

  if (to.meta.requiresAuth) {
    if (!token || !user || (token && esTokenExpirado(token))) {
      localStorage.clear() // Limpia el almacenamiento local
      return next({ name: 'LoginPage' })
    }
    if (to.meta.role && to.meta.role !== user.rol) {
      // redirige a su home si intenta entrar donde no debe
      return next({ path: `/app/${user.rol}` })
    }
  }
  next()
})

export default router
