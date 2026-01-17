import { defineStore } from 'pinia'
import type { LoginResult, UpdateUserPayload, User } from '@/types'
import { userApi } from '@/api/userApi'

const USER_STORAGE_KEY = 'melovibes_auth_user'

export const useStoreAuthentification = defineStore('auth', {
  state: () => ({
    estConnecte: false,
    utilisateurConnecte: null as User | null,
    authStatus: 'unknown' as 'unknown' | 'authenticated' | 'guest'
  }),

  getters: {
    estAdmin: (state) => state.utilisateurConnecte?.roles?.includes('ROLE_ADMIN') ?? false
  },

  actions: {
    setAuthenticated(user: User): void {
      this.utilisateurConnecte = user
      this.estConnecte = true
      this.authStatus = 'authenticated'
      localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(user))
    },

    setGuest(): void {
      this.utilisateurConnecte = null
      this.estConnecte = false
      this.authStatus = 'guest'
      localStorage.removeItem(USER_STORAGE_KEY)
    },

    async init(): Promise<void> {
      const stored = JSON.parse(localStorage.getItem(USER_STORAGE_KEY) || 'null') as User | null

      this.utilisateurConnecte = stored
      this.estConnecte = !!stored
      this.authStatus = stored ? 'authenticated' : 'guest'

      if (!stored) return

      try {
        let me: User
        try {
          me = await userApi.me()
        } catch {
          await userApi.refresh()
          me = await userApi.me()
        }
        this.setAuthenticated(me)
      } catch {
        this.setGuest()
      }
    },

    async login(email: string, password: string): Promise<LoginResult> {
      try {
        await userApi.login(email, password)
        const me = await userApi.me()
        this.setAuthenticated(me)
        return { success: true }
      } catch (err: any) {
        this.setGuest()
        return { success: false, error: err?.message ?? 'Connexion impossible' }
      }
    },

    async logout(): Promise<LoginResult> {
      try {
        await userApi.logout()
      } catch {
      } finally {
        this.setGuest()
      }
      return { success: true }
    },

    async register(login: string, email: string, password: string): Promise<LoginResult> {
      try {
        await userApi.register({ login, email, password })
        return { success: true }
      } catch (err: any) {
        return { success: false, error: err?.message ?? "Erreur lors de l'inscription" }
      }
    },

    async refresh(): Promise<LoginResult> {
      try {
        await userApi.refresh()
        const me = await userApi.me()
        this.setAuthenticated(me)
        return { success: true }
      } catch {
        this.setGuest()
        return { success: false, error: 'Session expirée' }
      }
    },

    async updateMyProfile(payload: UpdateUserPayload): Promise<LoginResult> {
      const me = this.utilisateurConnecte
      if (!me) return { success: false, error: 'Non authentifié' }

      try {
        await userApi.update(me.id, payload as unknown as Record<string, unknown>)
        const refreshed = await userApi.me()
        this.setAuthenticated(refreshed)
        return { success: true }
      } catch (err: any) {
        return { success: false, error: err?.message ?? 'Erreur lors de la mise à jour' }
      }
    },

    async deleteMyAccount(): Promise<LoginResult> {
      const me = this.utilisateurConnecte
      if (!me) return { success: false, error: 'Non authentifié' }

      try {
        await userApi.delete(me.id)
        this.setGuest()
        return { success: true }
      } catch (err: any) {
        return { success: false, error: err?.message ?? 'Erreur lors de la suppression' }
      }
    }
  }
})
