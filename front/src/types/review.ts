import type { Music } from './music'
import type { User } from './user'

export type ReviewMusicReference = string | Music
export type ReviewAuthorReference = string | User

export interface Review {
  id: number
  music: ReviewMusicReference
  author: ReviewAuthorReference
  comment?: string | null
  rating: number
  createdAt?: string | null
}
