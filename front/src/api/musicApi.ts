import {apiJson} from '@/api/httpClient'
import type {ImportedMusic, MusicSearchResponse} from '@/types'

export interface MusicSearchParams {
  q: string
  limit?: number
  offset?: number
  market?: string
}

/**
 * Search music in your database
 */
export async function searchMusic(params: MusicSearchParams): Promise<MusicSearchResponse> {
  const usp = new URLSearchParams()
  usp.set('q', params.q)
  usp.set('limit', String(params.limit ?? 20))
  usp.set('offset', String(params.offset ?? 0))
  usp.set('market', params.market ?? 'FR')

  return apiJson<MusicSearchResponse>(`music/search?${usp.toString()}`)
}

/**
 * Import a track from Spotify using its ID or full URL
 */
export async function importSpotifyTrack(spotifyTrackUrlOrId: string, market: string = 'FR'): Promise<ImportedMusic> {
  // Extract Spotify track ID from URL if needed
  let trackId = spotifyTrackUrlOrId
  if (spotifyTrackUrlOrId.includes('spotify.com')) {
    const match = spotifyTrackUrlOrId.match(/track\/([a-zA-Z0-9]+)(\?si=.*)?/)
    if (!match) throw new Error('Invalid Spotify track URL')
    trackId = match[1]
  }

  const usp = new URLSearchParams()
  usp.set('market', market)

  return apiJson<ImportedMusic>(
    `music/import/spotify/${encodeURIComponent(trackId)}?${usp.toString()}`,
    {method: 'POST'}
  )
}
