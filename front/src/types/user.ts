import type { Music } from './music'
import type { Review } from './review'

export interface User {
  id: number
  login: string
  email: string
  roles: string[]
  favoriteMusic?: Music[]
  reviews?: Review[]
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
