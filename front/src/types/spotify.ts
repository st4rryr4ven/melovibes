/**
 * Response returned by the backend Spotify session endpoint.
 */
export type SpotifySessionResponse = {
  success: boolean
  tempPassword?: string | null
}

/**
 * Response returned by the backend "sync favorites" endpoint.
 */
export type SpotifySyncFavoritesResponse = {
  success: boolean
  added: number
  scanned: number
}
