import { apiJson } from '@/api/httpClient'
import type { Artist, ArtistSearchResponse, ImportedArtist } from '@/types'

export interface ArtistSearchParams {
  q: string
  limit?: number
  offset?: number
}

/**
 * Search artists by name in your database
 */
export async function searchArtists(params: ArtistSearchParams): Promise<ArtistSearchResponse> {
  const usp = new URLSearchParams()
  usp.set('q', params.q)
  usp.set('limit', String(params.limit ?? 10))
  usp.set('offset', String(params.offset ?? 0))

  return apiJson<ArtistSearchResponse>(`artist/search?${usp.toString()}`)
}

/**
 * Create a new artist manually
 */
export async function createArtist(payload: { name: string; spotifyId?: string }): Promise<Artist> {
  return apiJson<Artist>('artists', {
    method: 'POST',
    body: JSON.stringify(payload)
  })
}

/**
 * Import an artist from Spotify using their Spotify ID
 */
export async function importSpotifyArtist(spotifyArtistId: string): Promise<ImportedArtist> {
  return apiJson<ImportedArtist>(`artist/import/spotify/${encodeURIComponent(spotifyArtistId)}`, {
    method: 'POST'
  })
}
