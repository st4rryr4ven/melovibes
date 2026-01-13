import type { Review } from '@/types'
import { apiCollection, apiJson } from '@/api/httpClient'

export class ReviewApi {
  async listByMusic(musicIri: string): Promise<Review[]> {
    const usp = new URLSearchParams()
    usp.set('music', musicIri)
    return apiCollection<Review>(`reviews?${usp.toString()}`)
  }

  async create(payload: { musicIri: string; rating: number; comment?: string }): Promise<Review> {
    return apiJson<Review>('reviews', {
      method: 'POST',
      json: {
        music: payload.musicIri,
        rating: payload.rating,
        comment: payload.comment ?? ''
      }
    })
  }
}

export const reviewApi = new ReviewApi()
