import type { Artist, ArtistSearchResponse } from '@/types'
import { apiJson } from '@/api/httpClient'

export interface ArtistSearchParams {
  q: string
  limit?: number
  offset?: number
}

export class ArtistApi {
  async search(params: ArtistSearchParams): Promise<ArtistSearchResponse> {
    const usp = new URLSearchParams()
    usp.set('q', params.q)
    usp.set('limit', String(params.limit ?? 10))
    usp.set('offset', String(params.offset ?? 0))
    return apiJson<ArtistSearchResponse>(`artist/search?${usp.toString()}`)
  }

  async create(payload: { name: string; spotifyId?: string }): Promise<Artist> {
    return apiJson<Artist>('artists', {
      method: 'POST',
      json: payload
    })
  }

  async importFromSpotify(spotifyArtistId: string): Promise<Artist> {
    return apiJson<Artist>(`artist/import/spotify/${encodeURIComponent(spotifyArtistId)}`, {
      method: 'POST'
    })
  }
}

export const artistApi = new ArtistApi()
