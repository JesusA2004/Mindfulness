import { createRouter, createWebHistory } from 'vue-router'

// Layouts
import PublicLayout from '@/layouts/PublicLayout.vue'
import LoginLayout  from '@/layouts/LoginLayout.vue'

// Public Views
import Index         from '@/views/Index.vue'
import Login         from '@/views/Login.vue'
import SobreNosotros from '@/views/SobreNosotros.vue'
import Contacto      from '@/views/Contacto.vue'

// Role-based Homes
import ProfesorHome      from '@/views/profesor/Home.vue'
import EstudianteHome    from '@/views/estudiante/Home.vue'
import AdministradorHome from '@/views/administrador/Home.vue'

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
        name: 'ProfesorHome',
        component: ProfesorHome,
        meta: { requiresAuth: true, role: 'profesor' }
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
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// (Opcional) guardia global para verificar auth + rol
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')
  const user  = JSON.parse(localStorage.getItem('user')||'null')

  if (to.meta.requiresAuth) {
    if (!token || !user) {
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
