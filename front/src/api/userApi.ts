import type { Music, SpotifySyncFavoritesResponse, User } from '@/types'
import { API_URL, apiCollection, apiJson, apiVoid } from '@/api/httpClient'

/**
 * User-related API endpoints.
 *
 * This class groups all HTTP calls related to:
 * - Authentication (login/logout/refresh)
 * - Account lifecycle (register/me/update/delete)
 * - Favorites (list/toggle)
 * - Password reset flow
 * - Spotify integration (login/link/unlink/sync)
 */
export class UserApi {
  /**
   * Authenticates a user using email/password.
   *
   * The backend sets authentication cookies on success.
   *
   * @param email User email.
   * @param password User password.
   * @returns The authenticated user payload (as returned by the backend).
   */
  async login(email: string, password: string): Promise<User> {
    return apiJson<User>('auth', {
      method: 'POST',
      json: { email, password }
    })
  }

  /**
   * Invalidates the current session on the backend.
   *
   * The backend is expected to clear auth cookies.
   */
  async logout(): Promise<void> {
    await apiVoid('token/invalidate', { method: 'POST' })
  }

  /**
   * Refreshes the session using the refresh cookie.
   */
  async refresh(): Promise<void> {
    await apiVoid('token/refresh', { method: 'POST' })
  }

  /**
   * Registers a new account.
   *
   * @param payload Registration payload.
   */
  async register(payload: { login: string; email: string; password: string }): Promise<void> {
    await apiVoid('users/register', {
      method: 'POST',
      contentType: 'application/ld+json',
      json: {
        login: payload.login,
        plainPassword: payload.password,
        email: payload.email
      }
    })
  }

  /**
   * Returns the current authenticated user.
   */
  async me(): Promise<User> {
    return apiJson<User>('me')
  }

  /**
   * Lists users (admin-only in most configurations).
   */
  async list(): Promise<User[]> {
    return apiCollection<User>('users')
  }

  /**
   * Retrieves a user by id.
   *
   * @param id User id.
   */
  async get(id: number): Promise<User> {
    return apiJson<User>(`users/${id}`)
  }

  /**
   * Updates a user using merge-patch JSON.
   *
   * @param id User id.
   * @param data Patch payload.
   */
  async update(id: number, data: Record<string, unknown>): Promise<User> {
    return apiJson<User>(`users/${id}`, {
      method: 'PATCH',
      contentType: 'application/merge-patch+json',
      json: data
    })
  }

  /**
   * Deletes a user account.
   *
   * @param id User id.
   */
  async delete(id: number): Promise<void> {
    await apiVoid(`users/${id}`, { method: 'DELETE' })
  }

  /**
   * Lists favorites for a user.
   *
   * @param userId User id.
   */
  async getFavorites(userId: number): Promise<Music[]> {
    return apiJson<Music[]>(`users/${userId}/favorites`)
  }

  /**
   * Toggles a music in the user's favorites.
   *
   * @param userId User id.
   * @param musicId Music id.
   */
  async toggleFavorite(userId: number, musicId: number): Promise<{ action: string }> {
    return apiJson<{ action: string }>(`users/${userId}/favorites`, {
      method: 'POST',
      contentType: 'application/merge-patch+json',
      json: { musicId }
    })
  }

  /**
   * Starts password reset flow (sends a reset email).
   *
   * @param email Email address.
   */
  async requestPasswordReset(email: string): Promise<void> {
    await apiVoid('forgot-password-request', {
      method: 'POST',
      json: { email }
    })
  }

  /**
   * Finishes password reset with a signed token.
   *
   * @param token Reset token received by email.
   * @param password New password.
   */
  async resetPassword(token: string, password: string): Promise<void> {
    await apiVoid('reset-password-finish', {
      method: 'POST',
      json: {
        token,
        password
      }
    })
  }

  /**
   * Returns the backend URL to start Spotify OAuth login.
   */
  getSpotifyLoginUrl(): string {
    const base = API_URL.endsWith('/') ? API_URL.slice(0, -1) : API_URL
    return `${base}/spotify/login`
  }

  /**
   * Returns the backend URL to link Spotify to the currently authenticated user.
   */
  getSpotifyLinkUrl(): string {
    const base = API_URL.endsWith('/') ? API_URL.slice(0, -1) : API_URL
    return `${base}/spotify/link`
  }

  /**
   * Unlinks Spotify from the currently authenticated user.
   */
  async unlinkSpotify(): Promise<void> {
    await apiVoid('spotify/unlink', { method: 'POST' })
  }

  /**
   * Imports the user's Spotify "Liked Songs" into local favorites.
   */
  async syncSpotifyFavorites(): Promise<SpotifySyncFavoritesResponse> {
    return apiJson<SpotifySyncFavoritesResponse>('spotify/sync-favorites', { method: 'POST' })
  }
}

export const userApi = new UserApi()
