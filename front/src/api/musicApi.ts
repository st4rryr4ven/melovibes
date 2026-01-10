import { apiJson } from '@/api/httpClient'
import type { ImportedMusic, MusicSearchResponse } from '@/types'

export interface MusicSearchParams {
  q: string
  limit?: number
  offset?: number
  market?: string
}

export async function searchMusic(params: MusicSearchParams): Promise<MusicSearchResponse> {
  const usp = new URLSearchParams()
  usp.set('q', params.q)
  usp.set('limit', String(params.limit ?? 20))
  usp.set('offset', String(params.offset ?? 0))
  usp.set('market', params.market ?? 'FR')

  return apiJson<MusicSearchResponse>(`music/search?${usp.toString()}`)
}

export async function importSpotifyTrack(spotifyTrackId: string, market: string = 'FR'): Promise<ImportedMusic> {
  const usp = new URLSearchParams()
  usp.set('market', market)
  return apiJson<ImportedMusic>(`music/import/spotify/${encodeURIComponent(spotifyTrackId)}?${usp.toString()}`, {
    method: 'POST'
  })
}
