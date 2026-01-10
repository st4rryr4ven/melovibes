import type { ArtistLite, SpotifyArtist } from './artist'

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
  artists?: SpotifyArtist[]
}

export interface MusicLocalDetails {
  musicId: number
  spotifyId: string | null
  title: string
  link: string | null
  picture: string | null
  genre: string[] | null
  popularity: number
  isValidated: boolean
  artists: ArtistLite[]
}

export type MusicSearchItem =
  | {
  source: 'local'
  local: MusicLocalDetails
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

export interface ImportedMusic {
  musicId: number
  spotifyId: string | null
  title: string
  link: string | null
  picture: string | null
  genre: string[] | null
  popularity: number
  artists: ArtistLite[]
}

export type MusicUiSource = 'search-local' | 'search-spotify' | 'album'

export interface MusicUiItem {
  key: string
  source: MusicUiSource
  musicId: number | null
  spotifyTrackId: string | null
  title: string
  artistsLabel: string
  picture: string | null
  link: string | null
  popularity?: number
  isImported?: boolean
}
