import type {Artist} from './artist'

export interface NewReleaseAlbumItem {
  albumId: string
  name: string
  picture: string | null
  releaseDate: string | null
  totalTracks: number | null
  artists: Artist[]
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

export interface AlbumTrackSpotify {
  id: string
  name: string
  external_urls: {
    spotify: string | null
  }
  artists: Artist[]
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
