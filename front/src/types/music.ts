import type { Artist } from './artist'
import type { Review } from './review'

/**
 * Local music representation.
 */
export interface Music {
  id: number
  musicId?: number
  title: string
  artists: Artist[]
  genre: string[]
  link?: string | null
  picture?: string | null
  popularity?: number | null
  isValidated: boolean
  spotifyId?: string | null
  reviews?: Review[]
}

/**
 * Minimal response returned after importing a Spotify track.
 */
export interface ImportedMusic {
  id: number
  title: string
}

/**
 * Minimal Spotify track structure used by the UI.
 */
export interface SpotifyTrack {
  id: string
  name: string
  external_urls?: {
    spotify?: string
  }
  album?: {
    id: string
    name: string
    images?: { url: string; width?: number; height?: number }[]
  }
  artists?: Artist[]
}

/**
 * Single item in a merged local + Spotify search result list.
 */
export type MusicSearchItem =
  | {
  source: 'local'
  local: Music
  spotify: null
}
  | {
  source: 'spotify'
  local: {
    isImported: boolean
    musicId: number | null
  }
  spotify: SpotifyTrack
}

/**
 * Response payload for merged local + Spotify music search.
 */
export interface MusicSearchResponse {
  items: MusicSearchItem[]
  meta: {
    limit: number
    offset: number
    localTotal: number
    spotify: {
      limit: number
      offset: number
      total: number | null
      next: string | null
      previous: string | null
    } | null
  }
}
