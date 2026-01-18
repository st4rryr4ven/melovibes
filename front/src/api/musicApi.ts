import type { ImportedMusic, Music, MusicSearchResponse, Review, User } from '@/types'
import { apiCollection, apiJson, apiVoid } from '@/api/httpClient'
import {iriToId, isRecord} from "@/api/utilsApi.ts";

/**
 * Query parameters for the merged local + Spotify music search endpoint.
 */
export interface MusicSearchParams {
  /**
   * Search query string.
   */
  q: string

  /**
   * Page size.
   */
  limit?: number

  /**
   * Offset within the merged result set.
   */
  offset?: number

  /**
   * Spotify market (e.g. FR).
   */
  market?: string
}

function extractJsonLdId(v: unknown): number | null {
  if (typeof v === 'string' && v.trim() !== '') return iriToId(v)

  if (isRecord(v)) {
    const iri = v['@id']
    if (typeof iri === 'string' && iri.trim() !== '') return iriToId(iri)
  }

  return null
}

/**
 * Music-related API endpoints and normalization helpers.
 *
 * Notes:
 * - API Platform resources may include JSON-LD fields like "@id".
 * - Some nested resources may omit "id" while still providing "@id".
 * - {@link enrichMusicData} normalizes embedded reviews and authors to ensure ids are present.
 */
export class MusicApi {
  /**
   * Retrieves a music by id.
   *
   * @param id Music id.
   */
  async get(id: number): Promise<Music> {
    return apiJson<Music>(`music/${id}`)
  }

  /**
   * Normalizes music payload for frontend usage.
   *
   * Ensures:
   * - Review id is present (from "id" or "@id").
   * - Author id is present (from "id" or "@id").
   *
   * @param music Raw music payload.
   * @returns A normalized music payload.
   */
  enrichMusicData(music: Music): Music {
    if (!music.reviews || music.reviews.length === 0) return music

    const reviews = music.reviews.map((r: Review) => {
      const rawReviewId = extractJsonLdId(r as unknown)
      const reviewId = r.id || rawReviewId || 0

      const author = r.author as unknown
      const rawAuthorId = extractJsonLdId(author)
      const authorObj = (isRecord(author) ? (author as unknown as User) : r.author) as User
      const authorId = authorObj?.id || rawAuthorId || 0

      return {
        ...r,
        id: reviewId,
        author: authorObj ? { ...authorObj, id: authorId } : (r.author as User)
      }
    })

    return { ...music, reviews }
  }

  /**
   * Lists musics using standard collection endpoints.
   *
   * @param filters Query filters mapped to URLSearchParams.
   */
  async list(filters: Record<string, unknown> = {}): Promise<Music[]> {
    const usp = new URLSearchParams()
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== undefined && value !== null) usp.append(key, String(value))
    })
    const q = usp.toString()
    return apiCollection<Music>(`music${q ? `?${q}` : ''}`)
  }

  /**
   * Creates a new music (local).
   *
   * @param payload Creation payload.
   */
  async create(payload: Record<string, unknown>): Promise<Music> {
    return apiJson<Music>('music', {
      method: 'POST',
      json: payload
    })
  }

  /**
   * Deletes a music.
   *
   * @param id Music id.
   */
  async delete(id: number): Promise<void> {
    await apiVoid(`music/${id}`, { method: 'DELETE' })
  }

  /**
   * Applies a JSON merge patch to a music.
   *
   * @param id Music id.
   * @param data Patch payload.
   */
  async patch(id: number, data: Record<string, unknown>): Promise<Music> {
    return apiJson<Music>(`music/${id}`, {
      method: 'PATCH',
      contentType: 'application/merge-patch+json',
      json: data
    })
  }

  /**
   * Imports a track from Spotify.
   *
   * Accepts either:
   * - a Spotify track id
   * - a full Spotify track URL
   *
   * @param trackId Spotify track id or URL.
   * @returns Minimal imported music representation.
   */
  async importFromSpotify(trackId: string): Promise<ImportedMusic> {
    if (trackId.includes('spotify.com')) {
      const match = trackId.match(/track\/([a-zA-Z0-9]+)(\?si=.*)?/)
      if (!match?.[1]) throw new Error('Invalid Spotify track URL')
      trackId = match[1]
    }

    const res = await apiJson<{ musicId: number; title: string }>(
      `music/import/spotify/${encodeURIComponent(trackId)}`,
      { method: 'POST' }
    )

    return { id: res.musicId, title: res.title }
  }

  /**
   * Performs merged local + Spotify search.
   *
   * @param params Search parameters.
   */
  async search(params: MusicSearchParams): Promise<MusicSearchResponse> {
    const usp = new URLSearchParams()
    usp.set('q', params.q)
    usp.set('limit', String(params.limit ?? 20))
    usp.set('offset', String(params.offset ?? 0))
    usp.set('market', params.market ?? 'FR')
    return apiJson<MusicSearchResponse>(`music/search?${usp.toString()}`)
  }

  /**
   * Convenience wrapper to read a user's library (favorites).
   *
   * @param userId User id.
   */
  async getUserLibrary(userId: number): Promise<Music[]> {
    return apiJson<Music[]>(`users/${userId}/favorites`)
  }
}

export const musicApi = new MusicApi()
