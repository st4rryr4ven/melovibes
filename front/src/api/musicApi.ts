import type {ImportedMusic, Music, MusicSearchResponse} from '@/types'
import {apiCollection, apiJson, apiVoid} from '@/api/httpClient'

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

  enrichMusicData(music: Music): Music {
    if (!music.reviews) return music

    return {
      ...music,
      reviews: music.reviews.map((r) => {
        const rawReviewIri = (r as any)['@id'] as string | undefined
        const reviewId = r.id || this.extractIdFromIri(rawReviewIri)

        const rawAuthorIri = (r.author as any)?.['@id'] as string | undefined
        const authorId = r.author?.id || this.extractIdFromIri(rawAuthorIri)

        return {
          ...r,
          id: reviewId,
          author: r.author ? {...r.author, id: authorId} : r.author
        }
      })
    }
  }

  private extractIdFromIri(iri: string | undefined): number {
    if (!iri || typeof iri !== 'string') return 0

    const parts = iri.split('/')
    const lastPart = parts[parts.length - 1]

    if (!lastPart) return 0

    const id = parseInt(lastPart, 10)
    return isNaN(id) ? 0 : id
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
    await apiVoid(`music/${id}`, {method: 'DELETE'})
  }

  async patch(id: number, data: Record<string, unknown>): Promise<Music> {
    return apiJson<Music>(`music/${id}`, {
      method: 'PATCH',
      contentType: 'application/merge-patch+json',
      json: data
    })
  }

  async importFromSpotify(trackId: string): Promise<ImportedMusic> {
    if (trackId.includes('spotify.com')) {
      const match = trackId.match(/track\/([a-zA-Z0-9]+)(\?si=.*)?/)
      if (!match?.[1]) throw new Error('Invalid Spotify track URL')
      trackId = match[1]
    }

    const res = await apiJson<{
      musicId: number;
      title: string
    }>(`music/import/spotify/${encodeURIComponent(trackId)}`, {
      method: 'POST'
    })

    return {
      id: res.musicId,
      title: res.title
    }
  }

  async search(params: MusicSearchParams): Promise<MusicSearchResponse> {
    const usp = new URLSearchParams()
    usp.set('q', params.q)
    usp.set('limit', String(params.limit ?? 20))
    usp.set('offset', String(params.offset ?? 0))
    usp.set('market', params.market ?? 'FR')
    return apiJson<MusicSearchResponse>(`music/search?${usp.toString()}`)
  }

  async listFavorites(): Promise<Music[]> {
    return this.list({favorites: true});
  }
  async getUserLibrary(userId: number): Promise<Music[]> {
    return apiJson<Music[]>(`users/${userId}/favorites`);
  }
}

export const musicApi = new MusicApi()
