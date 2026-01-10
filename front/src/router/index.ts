import {createRouter, createWebHistory} from 'vue-router'
import Melovibes from '@/views/MelovibesMain.vue'
import AllUsers from '@/views/AllUsers.vue'
import AllMusic from '@/views/AllMusic.vue'
import MusicCreate from '@/views/MusicForm.vue'
import Login from '@/views/Login.vue'
import Register from '@/views/Register.vue'
import Edit from '@/views/Profile.vue'
import {useStoreAuthentification} from '@/stores/storeAuthentification'
import AlbumTracks from '@/views/AlbumTracks.vue'
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
      meta: {requiresAuth: true},
    },
    {path: '/music', name: 'music', component: AllMusic},
    {
      path: '/music/create',
      name: 'music-create',
      component: MusicCreate,
      meta: {requiresAuth: true}
    },
    {path: '/album/:albumId', name: 'albumTracks', component: AlbumTracks},
    {
      path: '/music/:id',
      name: 'musicDetail',
      component: MusicDetail,
      props: (route) => ({id: Number(route.params.id)})
    },
    {
      path: '/unvalidatedMusics',
      name: 'unvalidatedMusics',
      component: UnvalidatedMusics,
      meta: {requiresAuth: true, requiresAdmin: true},

    }

  ]
})

router.beforeEach(async (to) => {
  if (useStoreAuthentification.authStatus === 'unknown') {
    await useStoreAuthentification.init();
  }

  if (to.meta.requiresAuth && !useStoreAuthentification.estConnecte) {
    return { name: 'login' };
  }

  if (to.meta.requiresAdmin && !useStoreAuthentification.estAdmin()) {
    return { name: 'melovibes' };
  }

  return true;
});

export default router
