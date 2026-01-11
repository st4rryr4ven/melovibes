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
import MusicDetail from "@/views/MusicDetail.vue";

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {path: '/', redirect: {name: 'melovibes'}},
    {path: '/melovibes', name: 'melovibes', component: Melovibes},
    {path: '/register', name: 'register', component: Register},
    {path: '/login', name: 'login', component: Login},
    {
      path: '/users',
      name: 'allUsers',
      component: AllUsers,
      meta: {requiresAuth: true, requiresAdmin: true}
    },
    {path: '/profile', name: 'profile', component: Edit, meta: {requiresAuth: true}},
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
  ]
})

router.beforeEach(async (to) => {
  const authStore = useStoreAuthentification();

  if (authStore.authStatus === 'unknown') {
    await authStore.init();
  }

  if (to.meta.requiresAuth && !authStore.estConnecte) {
    return {name: 'login'};
  }

  if (to.meta.requiresAdmin && !authStore.estAdmin) {
    return {name: 'melovibes'};
  }

  return true;
})

export default router
