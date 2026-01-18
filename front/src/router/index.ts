import {createRouter, createWebHistory} from 'vue-router'
import {useStoreAuthentification} from '@/stores/storeAuthentification'

import Melovibes from '@/views/MelovibesMain.vue'
import AllUsers from '@/views/user/AllUsers.vue'
import SingleUser from '@/views/user/SingleUser.vue'
import AllMusic from '@/views/music/AllMusic.vue'
import MusicDetail from '@/views/music/MusicDetail.vue'
import AlbumTracks from '@/views/AlbumTracks.vue'
import Login from '@/views/user/Login.vue'
import Register from '@/views/user/Register.vue'
import Profile from '@/views/user/Profile.vue'
import MusicEdit from '@/views/music/MusicEdit.vue'
import MusicCreate from '@/views/music/MusicCreate.vue'
import AuthSpotify from '@/views/user/AuthSpotify.vue'
import ResetPassword from "@/views/ResetPassword.vue";
import ForgotPassword from "@/views/ForgotPassword.vue";
import FavoritesActivity from "@/views/user/FavoritesActivity.vue";

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {path: '/', redirect: {name: 'melovibes'}},
    {path: '/melovibes', name: 'melovibes', component: Melovibes},

    {path: '/register', name: 'register', component: Register},
    {path: '/login', name: 'login', component: Login},
    {path: '/auth/spotify', name: 'authSpotify', component: AuthSpotify},

    {
      path: '/users',
      name: 'allUsers',
      component: AllUsers,
      meta: {requiresAuth: true, requiresAdmin: true}
    },
    {
      path: '/users/:id',
      name: 'singleUser',
      component: SingleUser,
      props: (route) => ({id: Number(route.params.id)}),
      meta: {requiresAuth: true, requiresAdmin: true}
    },

    {path: '/profile', name: 'profile', component: Profile, meta: {requiresAuth: true}},

    {
      path: '/music',
      name: 'music',
      component: AllMusic,
      meta: {requiresAuth: true, requiresAdmin: true}
    },
    {
      path: '/music/create',
      name: 'music-create',
      component: MusicCreate,
      meta: {requiresAuth: true}
    },
    {
      path: '/music/:id/edit',
      name: 'music-edit',
      component: MusicEdit,
      props: (route) => ({id: Number(route.params.id)}),
      meta: {requiresAuth: true, requiresAdmin: true}
    },
    {
      path: '/music/:id',
      name: 'musicDetail',
      component: MusicDetail,
      props: (route) => ({id: Number(route.params.id)})
    },

    {path: '/album/:albumId', name: 'albumTracks', component: AlbumTracks},
    {
      path: '/reset-password',
      name: 'resetPassword',
      component: ResetPassword
    },
    {
      path: '/forgot-password',
      name: 'forgotPassword',
      component: ForgotPassword
    },
    {
      path: '/activite-favoris',
      name: 'favoritesActivity',
      component: FavoritesActivity
    }
  ]
})

router.beforeEach(async (to) => {
  const authStore = useStoreAuthentification()

  if (authStore.authStatus === 'unknown') {
    await authStore.init()
  }

  if (to.meta.requiresAuth && !authStore.estConnecte) {
    return {name: 'login'}
  }

  if (to.meta.requiresAdmin && !authStore.estAdmin) {
    return {name: 'melovibes'}
  }

  return true
})

export default router
