import type { SpotifyArtist } from './artist'

export interface SpotifyImage {
  url: string
  width?: number
  height?: number
}

export interface NewReleaseAlbumItem {
  albumId: string
  name: string
  picture: string | null
  link: string | null
  releaseDate: string | null
  totalTracks: number | null
  artists: SpotifyArtist[]
}

export interface NewReleaseAlbumsResponse {
  items: NewReleaseAlbumItem[]
  meta: {
    limit: number
    offset: number
    total: number | null
    country: string
  }
}

export interface AlbumUiItem {
  key: string
  albumId: string
  name: string
  artistsLabel: string
  picture: string | null
  releaseDate: string | null
  totalTracks: number | null
  link: string | null
}

export interface AlbumTrackSpotify {
  id: string
  name: string
  external_urls: {
    spotify: string | null
  }
  artists: SpotifyArtist[]
  albumPicture: string | null
}

export interface AlbumTrackItem {
  spotify: AlbumTrackSpotify
  local: {
    isImported: boolean
    musicId: number | null
  }
}

export interface AlbumTracksResponse {
  album: NewReleaseAlbumItem
  tracks: AlbumTrackItem[]
}
