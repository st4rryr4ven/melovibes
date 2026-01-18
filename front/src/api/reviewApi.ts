import type { Review } from '@/types'
import { apiJson, apiVoid } from '@/api/httpClient'
import {iriToId, isRecord} from "@/api/utilsApi.ts";

/**
 * Minimal JSON-LD collection structure returned by API Platform.
 */
interface JsonLdCollection<T> {
  '@context': string
  '@id': string
  '@type': string
  totalItems: number
  member: T[]
}

function extractResourceId(resource: unknown): number | null {
  if (typeof resource === 'string' && resource.trim() !== '') return iriToId(resource)

  if (isRecord(resource)) {
    const iri = resource['@id']
    if (typeof iri === 'string' && iri.trim() !== '') return iriToId(iri)
  }

  return null
}

/**
 * Review-related API endpoints.
 *
 * Notes:
 * - API Platform may serialize relations as IRIs (strings) or embedded objects.
 * - Some payloads include JSON-LD "@id" instead of an integer "id".
 * - This API normalizes ids for "author" and "music" where needed.
 */
export class ReviewApi {
  /**
   * Lists all reviews.
   */
  async list(): Promise<Review[]> {
    const res = await apiJson<JsonLdCollection<Review>>('reviews')
    return res.member || []
  }

  /**
   * Lists reviews by author id and normalizes nested ids.
   *
   * @param userId Author id.
   */
  async listByUserId(userId: number): Promise<Review[]> {
    const res = await apiJson<JsonLdCollection<unknown>>(`reviews?author=${userId}`)
    const member = Array.isArray(res.member) ? res.member : []

    return member
      .map((r) => {
        if (!isRecord(r)) return null

        const musicRaw = r.music
        const authorRaw = r.author

        const musicId = extractResourceId(musicRaw)
        const authorId = extractResourceId(authorRaw)

        const musicTitle =
          isRecord(musicRaw) && typeof musicRaw.title === 'string' ? musicRaw.title : undefined

        const authorObj =
          isRecord(authorRaw) ? authorRaw : null

        const login =
          authorObj && typeof authorObj.login === 'string' ? authorObj.login : undefined

        const review: Review = {
          ...(r as unknown as Review),
          music: musicId
            ? { id: musicId, title: musicTitle ?? '' }
            : undefined,
          author: {
            ...(authorObj as unknown as Review['author']),
            id: authorId ?? 0,
            login: login ?? (authorObj ? String(authorObj.login ?? '') : '')
          } as Review['author']
        }

        return review
      })
      .filter((x): x is Review => x !== null)
  }

  /**
   * Lists reviews for a given music id.
   *
   * @param musicId Music id.
   */
  async listByMusicId(musicId: number): Promise<Review[]> {
    const musicIri = `/api/music/${musicId}`
    const params = new URLSearchParams({ music: musicIri })
    const res = await apiJson<JsonLdCollection<Review>>(`reviews?${params.toString()}`)
    return res.member || []
  }

  /**
   * Retrieves a review by id.
   *
   * @param id Review id.
   */
  async get(id: number): Promise<Review> {
    return apiJson<Review>(`reviews/${id}`)
  }

  /**
   * Creates a review for a given music.
   *
   * @param musicId Music id.
   * @param data Review fields.
   */
  async createForMusicId(musicId: number, data: Partial<Review>): Promise<Review> {
    const musicIri = `/api/music/${musicId}`
    return apiJson<Review>('reviews', {
      method: 'POST',
      contentType: 'application/ld+json',
      json: { ...data, music: musicIri }
    })
  }

  /**
   * Applies a JSON merge patch to a review.
   *
   * @param id Review id.
   * @param data Patch payload.
   */
  async patch(id: number, data: Partial<Review>): Promise<Review> {
    return apiJson<Review>(`reviews/${id}`, {
      method: 'PATCH',
      contentType: 'application/merge-patch+json',
      json: data
    })
  }

  /**
   * Deletes a review.
   *
   * @param id Review id.
   */
  async delete(id: number): Promise<void> {
    await apiVoid(`reviews/${id}`, { method: 'DELETE' })
  }

  /**
   * Lists reviews related to the user's favorites activity endpoint.
   *
   * @param userId User id.
   */
  async listFromFavorites(userId: number): Promise<Review[]> {
    const res = await apiJson<JsonLdCollection<unknown>>(`reviews?music.favoritedBy=${userId}`)
    const member = Array.isArray(res.member) ? res.member : []

    return member
      .map((r) => {
        if (!isRecord(r)) return null

        const authorRaw = r.author
        const authorObj = isRecord(authorRaw) ? authorRaw : null
        const login = authorObj && typeof authorObj.login === 'string' ? authorObj.login : 'Utilisateur anonyme'
        const authorId = extractResourceId(authorRaw) ?? 0

        const musicRaw = r.music
        const musicId = extractResourceId(musicRaw)

        const review: Review = {
          ...(r as unknown as Review),
          music: musicId ? { id: musicId, title: '' } : undefined,
          author: {
            ...(authorObj as unknown as Review['author']),
            id: authorId,
            login
          } as Review['author']
        }

        return review
      })
      .filter((x): x is Review => x !== null)
  }
}

export const reviewApi = new ReviewApi()
