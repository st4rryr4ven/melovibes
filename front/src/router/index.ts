import {createRouter, createWebHistory} from 'vue-router'
import Melovibes from '@/views/MelovibesMain.vue'
import AllUsers from '@/views/AllUsers.vue'
import AllMusic from '@/views/AllMusic.vue'
import MusicCreate from '@/views/MusicForm.vue'
import Login from '@/views/Login.vue'
import Register from '@/views/Register.vue'
import Edit from '@/views/Profile.vue'
import {useStoreAuthentification} from '@/stores/storeAuthentification'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {path: '/', redirect: {name: 'melovibes'}},
    {path: '/melovibes', name: 'melovibes', component: Melovibes},
    {path: '/register', name: 'register', component: Register},
    {path: '/login', name: 'login', component: Login},
    {path: '/users', name: 'allUsers', component: AllUsers},
    {
      path: '/profile',
      name: 'profile',
      component: Edit,
      meta: {requiresAuth: true},
    },
    {path: '/music', name: 'music', component: AllMusic},
    {
      path: '/music/create',
      name: 'music-create',
      component: MusicCreate,
      meta: {requiresAuth: true},
    },
  ],
})

router.beforeEach(async (to) => {
  if (!to.meta.requiresAuth) return true

  const authStore = useStoreAuthentification()

  if (authStore.authStatus === 'unknown') {
    await authStore.init()
  }

  if (!authStore.estConnecte) {
    return {name: 'login'}
  }

  return true
})

export default router
