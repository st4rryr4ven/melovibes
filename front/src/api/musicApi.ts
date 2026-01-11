import type {ImportedMusic, Music} from '@/types'
import {apiStore} from "@/util/apiStore.ts";

export interface MusicSearchParams {
  q: string
  limit?: number
  offset?: number
  market?: string
}

export interface CreateMusicParams {
  title: string
  artistId: number
  spotifyTrackId?: string | null
}

/**
 * Search music in your database
 */
export async function searchMusic(params: MusicSearchParams): Promise<{ member: Music[] }> {
  const usp = new URLSearchParams()
  usp.set('q', params.q)
  usp.set('limit', String(params.limit ?? 20))
  usp.set('offset', String(params.offset ?? 0))
  usp.set('market', params.market ?? 'FR')

  return apiStore.getAll(`music/search?${usp.toString()}`)
}

/**
 * Import a track from Spotify using its ID or full URL
 */
export async function importSpotifyTrack(
  spotifyTrackUrlOrId: string,
  market: string = 'FR'
): Promise<ImportedMusic> {
  let trackId = spotifyTrackUrlOrId
  if (spotifyTrackUrlOrId.includes('spotify.com')) {
    const match = spotifyTrackUrlOrId.match(/track\/([a-zA-Z0-9]+)(\?si=.*)?/)
    if (!match) throw new Error('Invalid Spotify track URL')
    trackId = match[1]
  }

  const usp = new URLSearchParams()
  usp.set('market', market)

  return apiStore.importMusicFromSpotify(trackId)
}

/**
 * Create a new music in the database
 */
export async function createMusic(music: CreateMusicParams): Promise<Music> {
  return apiStore.createMusic(music)
}
