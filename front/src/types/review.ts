import type { Music } from './music'
import type { User } from './user'

/**
 * Review entity displayed in the UI.
 */
export interface Review {
  id: number
  music?: Pick<Music, 'id' | 'title'>
  author: User
  comment?: string | null
  rating: number
  createdAt?: string | null
  melodyRating?: number | null
  lyricsRating?: number | null
  vocalsRating?: number | null
  impactRating?: number | null
}
