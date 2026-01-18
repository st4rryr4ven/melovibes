export type SpotifySessionResponse = {
  success: boolean
  tempPassword?: string | null
}

export type SpotifySyncFavoritesResponse = {
  success: boolean
  added: number
  scanned: number
}
