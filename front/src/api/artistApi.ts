import type { Artist, ArtistSearchResponse } from '@/types'
import { apiJson } from '@/api/httpClient'

/**
 * Query parameters for the merged local + Spotify artist search endpoint.
 */
export interface ArtistSearchParams {
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
}

type UnknownRecord = Record<string, unknown>

function isRecord(v: unknown): v is UnknownRecord {
  return typeof v === 'object' && v !== null
}

function toNumber(v: unknown): number | null {
  if (typeof v === 'number' && Number.isFinite(v)) return v
  if (typeof v === 'string' && v.trim() !== '' && Number.isFinite(Number(v))) return Number(v)
  return null
}

function toStringOrNull(v: unknown): string | null {
  return typeof v === 'string' ? v : null
}

function normalize(raw: unknown): ArtistSearchResponse {
  const itemsRaw = isRecord(raw) && Array.isArray(raw.items) ? raw.items : []
  const metaRaw = isRecord(raw) && isRecord(raw.meta) ? raw.meta : null

  const items: Artist[] = []

  for (const it of itemsRaw) {
    if (!isRecord(it) || typeof it.source !== 'string') continue

    if (it.source === 'local') {
      const local = isRecord(it.local) ? it.local : null
      if (!local) continue

      const id = toNumber(local.artistId ?? local.id)
      const name = typeof local.name === 'string' ? local.name : null
      if (id === null || !name) continue

      items.push({
        id,
        name,
        spotifyId: toStringOrNull(local.spotifyId)
      })
      continue
    }

    if (it.source === 'spotify') {
      const spotify = isRecord(it.spotify) ? it.spotify : null
      if (!spotify) continue

      const name = typeof spotify.name === 'string' ? spotify.name : null
      const spotifyId = typeof spotify.id === 'string' ? spotify.id : null
      if (!name || !spotifyId) continue

      const localMeta = isRecord(it.local) ? it.local : {}
      const dbId = toNumber(localMeta.artistId) ?? 0

      items.push({
        id: dbId,
        name,
        spotifyId
      })
    }
  }

  const meta = metaRaw
    ? {
      limit: toNumber(metaRaw.limit) ?? 20,
      offset: toNumber(metaRaw.offset) ?? 0,
      localTotal: toNumber(metaRaw.localTotal) ?? 0,
      spotify: isRecord(metaRaw.spotify)
        ? {
          limit: toNumber(metaRaw.spotify.limit) ?? 20,
          offset: toNumber(metaRaw.spotify.offset) ?? 0,
          total: toNumber(metaRaw.spotify.total),
          next: toStringOrNull(metaRaw.spotify.next),
          previous: toStringOrNull(metaRaw.spotify.previous)
        }
        : null
    }
    : undefined

  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-expect-error
  return { items, meta }
}

/**
 * Artist-related API endpoints.
 */
export class ArtistApi {
  /**
   * Performs merged local + Spotify search for artists.
   *
   * @param params Search parameters.
   */
  async search(params: ArtistSearchParams): Promise<ArtistSearchResponse> {
    const usp = new URLSearchParams()
    usp.set('q', params.q)
    usp.set('limit', String(params.limit ?? 10))
    usp.set('offset', String(params.offset ?? 0))

    const raw = await apiJson<unknown>(`artist/search?${usp.toString()}`)
    return normalize(raw)
  }

  /**
   * Creates a local artist.
   *
   * @param payload Artist payload.
   */
  async create(payload: { name: string; spotifyId?: string }): Promise<Artist> {
    return apiJson<Artist>('artists', {
      method: 'POST',
      json: payload
    })
  }

  /**
   * Imports an artist from Spotify and returns its local representation.
   *
   * @param spotifyArtistId Spotify artist id.
   */
  async importFromSpotify(spotifyArtistId: string): Promise<Artist> {
    const res = await apiJson<{ artistId: number; name: string; spotifyId: string | null }>(
      `artist/import/spotify/${encodeURIComponent(spotifyArtistId)}`,
      { method: 'POST' }
    )

    return {
      id: res.artistId,
      name: res.name,
      spotifyId: res.spotifyId
    }
  }
}

export const artistApi = new ArtistApi()
