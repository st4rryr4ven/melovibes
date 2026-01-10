import {createRouter, createWebHistory} from 'vue-router'
import Melovibes from '@/views/MelovibesMain.vue'
import AllUsers from '@/views/AllUsers.vue'
import Login from '@/views/Login.vue'
import Register from '@/views/Register.vue'
import Edit from '@/views/Profile.vue'
import UnvalidatedMusics from '@/views/UnvalidatedMusics.vue'
import {storeAuthentification} from "@/stores/storeAuthentification.ts";
import MusicDetail from "@/views/MusicDetail.vue";

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
      meta:{requiresAuth: true, requiresAdmin : true}
    },
    {
      path: '/profile',
      name: 'profile',
      component: Edit,
      meta: { requiresAuth: true }
    },
    {
      path: '/music/:id',
      name: 'musicDetail',
      component: MusicDetail,
      props: (route) => ({ id: Number(route.params.id) })
    },
    {
      path: '/unvalidatedMusics',
      name: 'unvalidatedMusics',
      component: UnvalidatedMusics,
      meta:{requiresAuth: true, requiresAdmin : true}
    },

  ]
})

router.beforeEach(async (to) => {
  if (storeAuthentification.authStatus === 'unknown') {
    await storeAuthentification.init();
  }

  if (to.meta.requiresAuth && !storeAuthentification.estConnecte) {
    return { name: 'login' };
  }

  if (to.meta.requiresAdmin && !storeAuthentification.estAdmin()) {
    return { name: 'melovibes' };
  }

  return true;
});

export default  router
