import { createRouter, createWebHistory } from 'vue-router'
import SoundlyMain from '@/views/SoundlyMain.vue'
import AllUsers from '@/views/AllUsers.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: { name: 'soundly' }
    },
    {
      path: '/soundly',
      name: 'soundly',
      component: SoundlyMain
    },
    {
      path: '/users',
      name: 'allUsers',
      component: AllUsers,
      meta: { requiresAuth: true }
    }
  ]
})
