import {createRouter, createWebHistory} from 'vue-router'
import Melovibes from '@/views/MelovibesMain.vue'
import AllUsers from '@/views/AllUsers.vue'
import AllMusics from '@/views/AllMusics.vue'
import Login from '@/views/Login.vue'
import Register from '@/views/Register.vue'
import Edit from '@/views/Profile.vue'
import {storeAuthentification} from "@/stores/storeAuthentification.ts";

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: {name: 'melovibes'}
    },
    {
      path: '/melovibes',
      name: 'melovibes',
      component: Melovibes
    },
    {
      path: '/register',
      name: 'register',
      component: Register
    },
    {
      path: '/login',
      name: 'login',
      component: Login
    },
    {
      path: '/users',
      name: 'allUsers',
      component: AllUsers,
    },
    {
      path: '/profile',
      name: 'profile',
      component: Edit,
      meta: {requiresAuth: true}
    },
    {
      path: '/music',
      name: 'musics',
      component: AllMusics
    }

  ]
})

router.beforeEach(async (to) => {
  if (!to.meta.requiresAuth) return true;

  await storeAuthentification.init();
  if (!storeAuthentification.estConnecte) return {name: 'login'};

  return true;
});

export default router
