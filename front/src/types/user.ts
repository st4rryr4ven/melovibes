import type { Music } from './music'
import type { Review } from './review'

export interface User {
  id: number
  login: string
  email: string
  roles: string[]
  favoriteMusic?: Music[]
  reviews?: Review[]
  spotifyLinked?: boolean
  spotifyDisplayName?: string | null
}

export interface UpdateUserPayload {
  login?: string
  plainPassword?: string
  currentPlainPassword: string
}

export interface LoginResult {
  success: boolean
  error?: string
}
