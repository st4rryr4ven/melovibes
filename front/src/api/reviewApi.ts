import type { Review } from '@/types'
import { apiJson } from '@/api/httpClient'

export class ReviewApi {
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
