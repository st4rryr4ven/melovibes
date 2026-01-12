import type { Music, MusicSearchResponse } from '@/types'
import { apiCollection, apiJson, apiVoid } from '@/api/httpClient'

export interface MusicSearchParams {
  q: string
  limit?: number
  offset?: number
  market?: string
}

export class MusicApi {
  async get(id: number): Promise<Music> {
    return apiJson<Music>(`music/${id}`)
  }

  async list(filters: Record<string, unknown> = {}): Promise<Music[]> {
    const usp = new URLSearchParams()
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== undefined && value !== null) usp.append(key, String(value))
    })
    const q = usp.toString()
    return apiCollection<Music>(`music${q ? `?${q}` : ''}`)
  }

  async create(payload: Record<string, unknown>): Promise<Music> {
    return apiJson<Music>('music', {
      method: 'POST',
      json: payload
    })
  }

  async delete(id: number): Promise<void> {
    await apiVoid(`music/${id}`, { method: 'DELETE' })
  }

  async patch(id: number, data: Record<string, unknown>): Promise<Music> {
    return apiJson<Music>(`music/${id}`, {
      method: 'PATCH',
      contentType: 'application/merge-patch+json',
      json: data
    })
  }

  async importFromSpotify(trackId: string): Promise<Music> {
    if (trackId.includes('spotify.com')) {
      const match = trackId.match(/track\/([a-zA-Z0-9]+)(\?si=.*)?/)
      if (!match) throw new Error('Invalid Spotify track URL')
      // @ts-ignore
      trackId = match[1]
    }

    return apiJson<Music>(`music/import/spotify/${encodeURIComponent(trackId)}`, {
      method: 'POST'
    })
  }

  async search(params: MusicSearchParams): Promise<MusicSearchResponse> {
    const usp = new URLSearchParams()
    usp.set('q', params.q)
    usp.set('limit', String(params.limit ?? 20))
    usp.set('offset', String(params.offset ?? 0))
    usp.set('market', params.market ?? 'FR')
    return apiJson<MusicSearchResponse>(`music/search?${usp.toString()}`)
  }
}

export const musicApi = new MusicApi()
