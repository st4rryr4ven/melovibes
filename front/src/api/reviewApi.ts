import type { Review } from '@/types'
import { apiCollection, apiJson } from '@/api/httpClient'

export class ReviewApi {
  private musicIriFromId(musicId: number): string {
    return `/api/music/${musicId}`
  }

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

  async createForMusicId(payload: { musicId: number; rating: number; comment?: string }): Promise<Review> {
    return this.create({
      musicIri: this.musicIriFromId(payload.musicId),
      rating: payload.rating,
      comment: payload.comment
    })
  }
}

export const reviewApi = new ReviewApi()
