import { apiJson } from '@/api/httpClient'
import type { AlbumTracksResponse, NewReleaseAlbumsResponse } from '@/types'

/**
 * Parameters for the Spotify "new releases" albums endpoint.
 */
export interface AlbumNewReleasesParams {
  /**
   * Page size.
   */
  limit?: number

  /**
   * Offset.
   */
  offset?: number

  /**
   * Country code (ISO 3166-1 alpha-2), e.g. FR.
   */
  country?: string
}

/**
 * Album-related API endpoints (Spotify-oriented).
 */
export class AlbumApi {
  /**
   * Retrieves Spotify "new releases" albums via the backend aggregator endpoint.
   *
   * @param params Query parameters.
   */
  async getNewReleases(params: AlbumNewReleasesParams = {}): Promise<NewReleaseAlbumsResponse> {
    const usp = new URLSearchParams()
    usp.set('limit', String(params.limit ?? 20))
    usp.set('offset', String(params.offset ?? 0))
    usp.set('country', params.country ?? 'FR')
    return apiJson<NewReleaseAlbumsResponse>(`music/new-releases?${usp.toString()}`)
  }

  /**
   * Retrieves all tracks of a Spotify album and returns import status for each track.
   *
   * @param spotifyAlbumId Spotify album id.
   * @param market Spotify market (default FR).
   */
  async getSpotifyAlbumTracks(spotifyAlbumId: string, market: string = 'FR'): Promise<AlbumTracksResponse> {
    const usp = new URLSearchParams()
    usp.set('market', market)
    return apiJson<AlbumTracksResponse>(
      `albums/spotify/${encodeURIComponent(spotifyAlbumId)}/tracks?${usp.toString()}`
    )
  }
}

export const albumApi = new AlbumApi()
