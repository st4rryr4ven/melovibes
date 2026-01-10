export interface ArtistLite {
  id: number
  name: string
  spotifyId: string | null
}

export interface MusicNewReleaseItem {
  musicId: number
  spotifyId: string | null
  title: string
  link: string | null
  picture: string | null
  genre: string[] | null
  popularity: number
  importSource: string | null
  importedAt: string | null
  artists: ArtistLite[]
}

export interface MusicNewReleasesResponse {
  items: MusicNewReleaseItem[]
  meta: {
    limit: number
    offset: number
    total: number
    source: string
  }
}

export interface SpotifyImage {
  url: string
  width?: number
  height?: number
}

export interface SpotifyArtist {
  id: string
  name: string
}

export interface SpotifyAlbum {
  id: string
  name: string
  images?: SpotifyImage[]
}

export interface SpotifyTrack {
  id: string
  name: string
  external_urls?: {
    spotify?: string
  }
  album?: SpotifyAlbum
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

export type MusicUiSource = 'new-releases' | 'search-local' | 'search-spotify'

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
  importedAt?: string | null
  isImported?: boolean
}
