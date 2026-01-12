export interface Artist {
  id: number
  name: string
  spotifyId: string | null
}

export interface ArtistSearchResponse {
  total: number
  items: Artist[]
}
