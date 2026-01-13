import type { Music } from './music'
import type { User } from './user'

export interface Review {
  id: number
  music: Music
  author: User
  comment?: string | null
  rating: number
  createdAt?: string | null
}
