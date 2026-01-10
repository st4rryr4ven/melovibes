import { reactive } from 'vue'
import type { ImportAlbumResponse } from '@/types'

const STORAGE_KEY = 'melovibes_album_cache_v1'

type AlbumCache = Record<string, ImportAlbumResponse>

function loadCache(): AlbumCache {
  try {
    const raw = sessionStorage.getItem(STORAGE_KEY)
    if (!raw) return {}
    const parsed = JSON.parse(raw) as AlbumCache
    return parsed && typeof parsed === 'object' ? parsed : {}
  } catch {
    return {}
  }
}

function persist(cache: AlbumCache) {
  try {
    sessionStorage.setItem(STORAGE_KEY, JSON.stringify(cache))
  } catch {}
}

export const albumStore = reactive({
  cache: loadCache() as AlbumCache,

  set(albumId: string, payload: ImportAlbumResponse) {
    this.cache[albumId] = payload
    persist(this.cache)
  },

  get(albumId: string): ImportAlbumResponse | null {
    return this.cache[albumId] ?? null
  },

  clear(albumId?: string) {
    if (albumId) {
      delete this.cache[albumId]
      persist(this.cache)
      return
    }
    this.cache = {}
    persist(this.cache)
  }
})
