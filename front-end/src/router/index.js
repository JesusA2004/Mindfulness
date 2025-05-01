import { createRouter, createWebHistory } from 'vue-router'

// Layouts
import PublicLayout    from '@/layouts/PublicLayout.vue'
import LoginLayout from '@/layouts/LoginLayout.vue'

// Views
import Index          from '@/views/Index.vue'
import Login          from '@/views/Login.vue'
import Home           from '@/views/Home.vue'
import SobreNosotros  from '@/views/SobreNosotros.vue'
import Contacto       from '@/views/Contacto.vue'

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
    path: '/home',
    component: LoginLayout,
    children: [
      { path: '', name: 'HomePage', component: Home }
      // rutas privadas adicionales aquí
    ]
  }
]

export default createRouter({
  history: createWebHistory(),
  routes
})