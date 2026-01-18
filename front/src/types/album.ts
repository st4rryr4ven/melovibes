import type { Artist } from './artist'

/**
 * Lightweight album item returned by the backend new releases endpoint.
 */
export interface NewReleaseAlbumItem {
  albumId: string
  name: string
  picture: string | null
  releaseDate: string | null
  totalTracks: number | null
  artists: Artist[]
}

/**
 * New releases response payload.
 */
export interface NewReleaseAlbumsResponse {
  items: NewReleaseAlbumItem[]
  meta: {
    limit: number
    offset: number
    total: number | null
    country: string
  }
}

/**
 * Minimal Spotify track fields returned for album track listings.
 */
export interface AlbumTrackSpotify {
  id: string
  name: string
  external_urls: {
    spotify: string | null
  }
  artists: Artist[]
  albumPicture: string | null
}

/**
 * Album track item combining Spotify info and local import status.
 */
export interface AlbumTrackItem {
  spotify: AlbumTrackSpotify
  local: {
    isImported: boolean
    musicId: number | null
  }
}

/**
 * Album tracks response payload.
 */
export interface AlbumTracksResponse {
  album: NewReleaseAlbumItem
  tracks: AlbumTrackItem[]
}
