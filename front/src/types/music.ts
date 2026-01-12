import type { Artist } from './artist'

export interface Music {
  id: number
  title: string
  artists: Artist[]
  genre: string[]
  link?: string | null
  picture?: string | null
  popularity?: number | null
  isValidated: boolean
  spotifyId?: string | null
}

export interface ImportedMusic {
  id: number
  title: string
}

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
