import type { Music } from './music'

export interface User {
  id: number
  login: string
  email: string
  roles: string[]
  favoriteMusic?: Music[]
}

export interface UpdateUserPayload {
  email?: string
  plainPassword?: string
  currentPlainPassword: string
}

export interface LoginResult {
  success: boolean
  error?: string
}
