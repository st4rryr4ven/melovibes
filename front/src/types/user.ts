import type { Music } from './music'
import type { Review } from './review'

/**
 * User entity.
 */
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

/**
 * Patch payload used when updating the current user profile.
 */
export interface UpdateUserPayload {
  login?: string
  plainPassword?: string
  currentPlainPassword: string
}

/**
 * Generic action result used by auth store actions.
 */
export interface LoginResult {
  success: boolean
  error?: string
}
