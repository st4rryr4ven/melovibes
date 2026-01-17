import type {Music, User} from '@/types'
import {apiCollection, apiJson, apiVoid} from '@/api/httpClient'

export class UserApi {
  async login(email: string, password: string): Promise<User> {
    return apiJson<User>('auth', {
      method: 'POST',
      json: {email, password}
    })
  }

  async logout(): Promise<void> {
    await apiVoid('token/invalidate', {method: 'POST'})
  }

  async refresh(): Promise<void> {
    await apiVoid('token/refresh', {method: 'POST'})
  }

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

  async me(): Promise<User> {
    return apiJson<User>('me')
  }

  async list(): Promise<User[]> {
    return apiCollection<User>('users')
  }

  async get(id: number): Promise<User> {
    return apiJson<User>(`users/${id}`)
  }

  async update(id: number, data: Record<string, unknown>): Promise<User> {
    return apiJson<User>(`users/${id}`, {
      method: 'PATCH',
      contentType: 'application/merge-patch+json',
      json: data
    })
  }

  async delete(id: number): Promise<void> {
    await apiVoid(`users/${id}`, {method: 'DELETE'})
  }

  async getFavorites(userId: number): Promise<Music[]> {
    return apiJson<Music[]>(`users/${userId}/favorites`)
  }

  async toggleFavorite(userId: number, musicId: number): Promise<{ action: string }> {
    return apiJson<{ action: string }>(`users/${userId}/favorites`, {
      method: 'POST',
      contentType: 'application/merge-patch+json',
      json: {musicId}
    })
  }

  async requestPasswordReset(email: string): Promise<void> {
    await apiVoid('forgot-password-request', {
      method: 'POST',
      json: {email}
    })
  }

  async resetPassword(token: string, password: string): Promise<void> {
    await apiVoid('reset-password-finish', {
      method: 'POST',
      json: {
        token: token,
        password: password
      }
    })
  }
}

export const userApi = new UserApi()
