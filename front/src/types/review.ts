import type { Music } from './music'
import type { User } from './user'

export interface Review {
  id: number
  music?: Pick<Music, 'id' | 'title'>
  author: User
  comment?: string | null
  rating: number
  createdAt?: string | null
}
