import { defineStore } from 'pinia'
import type { LoginResult, UpdateUserPayload, User } from '@/types'
import { userApi } from '@/api/userApi'

/**
 * Local storage key used to cache the authenticated user snapshot.
 */
const USER_STORAGE_KEY = 'melovibes_auth_user'

function errorMessage(err: unknown, fallback: string): string {
  if (err instanceof Error && err.message.trim() !== '') return err.message
  if (typeof err === 'string' && err.trim() !== '') return err
  return fallback
}

function toPatchPayload(payload: UpdateUserPayload): Record<string, unknown> {
  const out: Record<string, unknown> = { currentPlainPassword: payload.currentPlainPassword }
  if (payload.login !== undefined) out.login = payload.login
  if (payload.plainPassword !== undefined) out.plainPassword = payload.plainPassword
  return out
}

/**
 * Authentication and session store.
 *
 * Responsibilities:
 * - Keeps the authenticated user in memory and in localStorage (snapshot).
 * - Initializes the session on app start (me + refresh fallback).
 * - Provides high-level actions for login/logout/register/profile update/account deletion.
 */
export const useStoreAuthentification = defineStore('auth', {
  state: () => ({
    estConnecte: false,
    utilisateurConnecte: null as User | null,
    authStatus: 'unknown' as 'unknown' | 'authenticated' | 'guest'
  }),

  getters: {
    /**
     * Returns true when the current user has the admin role.
     */
    estAdmin: (state) => state.utilisateurConnecte?.roles?.includes('ROLE_ADMIN') ?? false,

    /**
     * Returns the current authenticated user id.
     */
    utilisateurId: (state) => state.utilisateurConnecte?.id ?? null
  },

  actions: {
    /**
     * Marks the store as authenticated and persists user snapshot.
     *
     * @param user Authenticated user.
     */
    setAuthenticated(user: User): void {
      this.utilisateurConnecte = user
      this.estConnecte = true
      this.authStatus = 'authenticated'
      localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(user))
    },

    /**
     * Marks the store as guest and clears cached snapshot.
     */
    setGuest(): void {
      this.utilisateurConnecte = null
      this.estConnecte = false
      this.authStatus = 'guest'
      localStorage.removeItem(USER_STORAGE_KEY)
    },

    /**
     * Initializes authentication state from localStorage and verifies it with the backend.
     *
     * Strategy:
     * - If no snapshot exists: set guest.
     * - If snapshot exists: call /me, fallback to /token/refresh then /me.
     */
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

    /**
     * Performs email/password login.
     *
     * @param email Email.
     * @param password Password.
     */
    async login(email: string, password: string): Promise<LoginResult> {
      try {
        await userApi.login(email, password)
        const me = await userApi.me()
        this.setAuthenticated(me)
        return { success: true }
      } catch (err: unknown) {
        this.setGuest()
        return { success: false, error: errorMessage(err, 'Connexion impossible') }
      }
    },

    /**
     * Logs out and clears local session state.
     */
    async logout(): Promise<LoginResult> {
      try {
        await userApi.logout()
      } catch {
      } finally {
        this.setGuest()
      }
      return { success: true }
    },

    /**
     * Registers a new user account.
     *
     * @param login Login.
     * @param email Email.
     * @param password Password.
     */
    async register(login: string, email: string, password: string): Promise<LoginResult> {
      try {
        await userApi.register({ login, email, password })
        return { success: true }
      } catch (err: unknown) {
        return { success: false, error: errorMessage(err, "Erreur lors de l'inscription") }
      }
    },

    /**
     * Refreshes session and rehydrates current user via /me.
     */
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

    /**
     * Updates the current user's profile.
     *
     * @param payload Update payload.
     */
    async updateMyProfile(payload: UpdateUserPayload): Promise<LoginResult> {
      const me = this.utilisateurConnecte
      if (!me) return { success: false, error: 'Non authentifié' }

      try {
        await userApi.update(me.id, toPatchPayload(payload))
        const refreshed = await userApi.me()
        this.setAuthenticated(refreshed)
        return { success: true }
      } catch (err: unknown) {
        return { success: false, error: errorMessage(err, 'Erreur lors de la mise à jour') }
      }
    },

    /**
     * Deletes the current user's account.
     *
     * The backend is expected to clear auth cookies; the store additionally clears local state.
     */
    async deleteMyAccount(): Promise<LoginResult> {
      const me = this.utilisateurConnecte
      if (!me) return { success: false, error: 'Non authentifié' }

      try {
        await userApi.delete(me.id)
        await this.logout()
        this.setGuest()
        return { success: true }
      } catch (err: unknown) {
        return { success: false, error: errorMessage(err, 'Erreur lors de la suppression') }
      }
    }
  }
})
