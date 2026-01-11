import {defineStore} from 'pinia'
import {apiStore} from '@/util/apiStore'
import type {LoginResult, User} from '@/types'

const USER_STORAGE_KEY = 'melovibes_auth_user'

export const useStoreAuthentification = defineStore('auth', {
  state: () => ({
    estConnecte: false,
    utilisateurConnecte: null as User | null,
    authStatus: 'unknown' as 'unknown' | 'authenticated' | 'guest',
  }),

  getters: {
    estAdmin: (state) => state.utilisateurConnecte?.roles?.includes('ROLE_ADMIN') ?? false,
  },

  actions: {
    async init(): Promise<void> {
      const stored = JSON.parse(localStorage.getItem(USER_STORAGE_KEY) || 'null') as User | null

      this.utilisateurConnecte = stored
      this.estConnecte = !!stored
      this.authStatus = stored ? 'authenticated' : 'guest'

    if (!stored) return;

      try {
        let me: User
        try {
          me = await apiStore.me()
        } catch {
          await apiStore.refresh()
          me = await apiStore.me()
        }

        this.utilisateurConnecte = me
        this.estConnecte = true
        this.authStatus = 'authenticated'
        localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(me))
      } catch {
        this.utilisateurConnecte = null
        this.estConnecte = false
        this.authStatus = 'guest'
        localStorage.removeItem(USER_STORAGE_KEY)
      }
    },

    async login(login: string, password: string): Promise<LoginResult> {
      try {
        await apiStore.login(login, password)
        const me = await apiStore.me()
        this.utilisateurConnecte = me
        this.estConnecte = true
        this.authStatus = 'authenticated'
        localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(me))
        return {success: true}
      } catch (err: any) {
        this.utilisateurConnecte = null
        this.estConnecte = false
        this.authStatus = 'guest'
        localStorage.removeItem(USER_STORAGE_KEY)
        return {success: false, error: err.message}
      }
    },

    async logout(): Promise<LoginResult> {
      try {
        await apiStore.logout()
      } finally {
        this.utilisateurConnecte = null
        this.estConnecte = false
        this.authStatus = 'guest'
        localStorage.removeItem(USER_STORAGE_KEY)
      }
      return {success: true}
    },

    async register(login: string, email: string, password: string): Promise<LoginResult> {
      try {
        await apiStore.register({login, email, password})
        return {success: true}
      } catch (err: any) {
        return {success: false, error: err.message}
      }
    },

    async refresh(): Promise<LoginResult> {
      try {
        await apiStore.refresh()
        return {success: true}
      } catch {
        this.utilisateurConnecte = null
        this.estConnecte = false
        this.authStatus = 'guest'
        localStorage.removeItem(USER_STORAGE_KEY)
        return {success: false, error: 'Session expirée'}
      }
    },
  },
})
