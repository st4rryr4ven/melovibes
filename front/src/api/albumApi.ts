import { apiJson } from '@/api/httpClient'
import type { AlbumTracksResponse, NewReleaseAlbumsResponse } from '@/types'

export interface AlbumNewReleasesParams {
  limit?: number
  offset?: number
  country?: string
}

export async function getAlbumNewReleases(params: AlbumNewReleasesParams = {}): Promise<NewReleaseAlbumsResponse> {
  const usp = new URLSearchParams()
  usp.set('limit', String(params.limit ?? 20))
  usp.set('offset', String(params.offset ?? 0))
  usp.set('country', params.country ?? 'FR')
  return apiJson<NewReleaseAlbumsResponse>(`music/new-releases?${usp.toString()}`)
}

export async function getSpotifyAlbumTracks(spotifyAlbumId: string, market: string = 'FR'): Promise<AlbumTracksResponse> {
  const usp = new URLSearchParams()
  usp.set('market', market)
  return apiJson<AlbumTracksResponse>(`albums/spotify/${encodeURIComponent(spotifyAlbumId)}/tracks?${usp.toString()}`)
}
