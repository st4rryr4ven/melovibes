import type {Review} from '@/types'
import {apiJson, apiVoid} from '@/api/httpClient'

interface JsonLdCollection<T> {
  '@context': string
  '@id': string
  '@type': string
  totalItems: number
  member: T[]
}

export class ReviewApi {
  async list(): Promise<Review[]> {
    const res = await apiJson<JsonLdCollection<Review>>('reviews')
    return res.member || []
  }

  async listByUserId(userId: number): Promise<Review[]> {
    const res = await apiJson<JsonLdCollection<any>>(`reviews?author=${userId}`)

    return (res.member || []).map(r => ({
      ...r,
      music: r.music
        ? {
          id: Number((r.music as any)['@id'].split('/').pop()),
          title: r.music.title
        }
        : undefined,
      author: {
        ...r.author,
        id: Number((r.author as any)['@id'].split('/').pop())
      }
    }))
  }


  async listByMusicId(musicId: number): Promise<Review[]> {
    const musicIri = `/api/music/${musicId}`
    const params = new URLSearchParams({music: musicIri})
    const res = await apiJson<JsonLdCollection<Review>>(`reviews?${params.toString()}`)
    return res.member || []
  }

  async get(id: number): Promise<Review> {
    return apiJson<Review>(`reviews/${id}`)
  }

  async createForMusicId(musicId: number, data: Partial<Review>): Promise<Review> {
    const musicIri = `/api/music/${musicId}`
    return apiJson<Review>('reviews', {
      method: 'POST',
      contentType: 'application/ld+json',
      json: {...data, music: musicIri}
    })
  }

  async patch(id: number, data: Partial<Review>): Promise<Review> {
    return apiJson<Review>(`reviews/${id}`, {
      method: 'PATCH',
      contentType: 'application/merge-patch+json',
      json: data
    })
  }

  async delete(id: number): Promise<void> {
    await apiVoid(`reviews/${id}`, {method: 'DELETE'})
  }

  async listFromFavorites(userId: number): Promise<Review[]> {
    const res = await apiJson<JsonLdCollection<Review>>(`reviews?music.favoritedBy=${userId}`)
    return res.member || []
  }

}

export const reviewApi = new ReviewApi()
