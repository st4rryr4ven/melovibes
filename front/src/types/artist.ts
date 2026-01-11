export interface Artist {
  id: number
  name: string
  spotifyId: string | null
}

export interface ImportedArtist {
  id: number           // your DB id
  name: string
  spotifyId: string | null
}

export interface SpotifyArtist {
  id: string
  name: string
  spotifyUrl?: string
  genres?: string[]
  followers?: number
  images?: { url: string; height: number; width: number }[]
  popularity?: number
}
export interface ArtistSearchResponse {
  total: number
  items: Artist[]
}
