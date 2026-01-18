/**
 * Local artist representation.
 *
 * When the artist is imported from Spotify, spotifyId is set.
 */
export interface Artist {
  /**
   * Local database id (0 when not imported yet).
   */
  id: number

  /**
   * Display name.
   */
  name: string

  /**
   * Spotify artist id when available.
   */
  spotifyId: string | null
}

/**
 * Pagination metadata for the Spotify portion of merged searches.
 */
export interface SpotifySearchMeta {
  limit: number
  offset: number
  total: number | null
  next: string | null
  previous: string | null
}

/**
 * Metadata for merged local + Spotify searches.
 */
export interface MergedSearchMeta {
  limit: number
  offset: number
  localTotal: number
  spotify: SpotifySearchMeta | null
}

/**
 * Response for merged local + Spotify artist search.
 */
export interface ArtistSearchResponse {
  /**
   * Total is a convenience number derived from meta when present.
   */
  total: number

  /**
   * Flattened list of artists from both sources.
   */
  items: Artist[]

  /**
   * Detailed metadata (when provided by the backend).
   */
  meta?: MergedSearchMeta
}
