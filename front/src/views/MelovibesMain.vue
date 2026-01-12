<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import MusicSearchBar from '@/components/MusicSearchBar.vue'
import MusicList from '@/components/MusicList.vue'
import AlbumList from '@/components/AlbumList.vue'
import { useDebouncedRef } from '@/composables/useDebouncedRef'
import { musicApi } from '@/api/musicApi'
import { albumApi } from '@/api/albumApi'
import type { MusicSearchItem, NewReleaseAlbumItem } from '@/types'

const router = useRouter()

const query = ref('')
const debouncedQuery = useDebouncedRef(query, 300)
const isSearchOpen = computed(() => query.value.trim().length > 0)

const albumsLoading = ref(false)
const albumsError = ref<string | null>(null)
const albums = ref<NewReleaseAlbumItem[]>([])

const searchLoading = ref(false)
const searchError = ref<string | null>(null)
const searchItems = ref<MusicSearchItem[]>([])
const busySpotifyId = ref<string | null>(null)

function errorMessage(err: unknown, fallback: string): string {
  if (err instanceof Error && err.message) return err.message
  if (typeof err === 'string' && err) return err
  return fallback
}

async function loadAlbums() {
  albumsLoading.value = true
  albumsError.value = null
  try {
    const res = await albumApi.getNewReleases({ limit: 20, country: 'FR' })
    albums.value = res.items
  } catch (e) {
    albumsError.value = errorMessage(e, 'Erreur lors du chargement')
    albums.value = []
  } finally {
    albumsLoading.value = false
  }
}

let lastSearchId = 0
async function loadSearch(q: string) {
  const searchId = ++lastSearchId
  searchLoading.value = true
  searchError.value = null
  try {
    const res = await musicApi.search({ q, limit: 20, market: 'FR' })
    if (searchId !== lastSearchId) return
    searchItems.value = res.items
  } catch (e) {
    if (searchId !== lastSearchId) return
    searchError.value = errorMessage(e, 'Erreur lors de la recherche')
    searchItems.value = []
  } finally {
    if (searchId === lastSearchId) searchLoading.value = false
  }
}

function closeSearch() {
  query.value = ''
  lastSearchId++
  searchLoading.value = false
  searchError.value = null
  searchItems.value = []
  busySpotifyId.value = null
}

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape' && isSearchOpen.value) closeSearch()
}

function goToMusicDetail(id: number) {
  router.push({ name: 'musicDetail', params: { id } })
}

async function onSelectSearchTrack(item: MusicSearchItem) {
  if (busySpotifyId.value) return

  if (item.source === 'local') {
    goToMusicDetail(item.local.id)
    return
  }

  if (item.local?.isImported && typeof item.local.musicId === 'number') {
    goToMusicDetail(item.local.musicId)
    return
  }

  const spotifyId = item.spotify.id
  busySpotifyId.value = spotifyId
  searchError.value = null

  try {
    const imported = await musicApi.importFromSpotify(spotifyId)

    searchItems.value = searchItems.value.map((it) => {
      if (it.source !== 'spotify') return it
      if (it.spotify.id !== spotifyId) return it
      return {
        ...it,
        local: {
          isImported: true,
          musicId: imported.id
        }
      }
    })

    goToMusicDetail(imported.id)
  } catch (e) {
    searchError.value = errorMessage(e, "Erreur lors de l'import")
  } finally {
    busySpotifyId.value = null
  }
}

function onSelectAlbum(item: NewReleaseAlbumItem) {
  router.push({ name: 'albumTracks', params: { albumId: item.albumId } })
}

watch(
  () => debouncedQuery.value,
  async (q) => {
    const trimmed = q.trim()
    if (trimmed === '') {
      lastSearchId++
      searchLoading.value = false
      searchError.value = null
      searchItems.value = []
      busySpotifyId.value = null
      return
    }
    await loadSearch(trimmed)
  }
)

onMounted(() => {
  loadAlbums()
  window.addEventListener('keydown', onKeydown)
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', onKeydown)
})
</script>

<template>
  <div class="page">
    <div class="top">
      <MusicSearchBar
        v-model="query"
        :loading="isSearchOpen ? searchLoading : albumsLoading"
        placeholder="Rechercher une musique..."
        @submit="() => query.trim() && loadSearch(query.trim())"
      />

      <div class="title">
        <h2>New Releases</h2>
      </div>

      <div v-if="albumsError" class="error">{{ albumsError }}</div>
    </div>

    <AlbumList :items="albums" @select="onSelectAlbum" />

    <div v-if="isSearchOpen" class="overlay" role="dialog" aria-modal="true">
      <div class="overlay__backdrop" @click="closeSearch"></div>

      <div class="overlay__panel">
        <div class="overlay__header">
          <div class="overlay__title">Résultats pour "{{ query.trim() }}"</div>
          <button type="button" class="overlay__close" @click="closeSearch">✕</button>
        </div>

        <div class="overlay__content">
          <div v-if="searchError" class="error">{{ searchError }}</div>
          <MusicList
            mode="search"
            :items="searchItems"
            :busy-spotify-id="busySpotifyId"
            @select-search="onSelectSearchTrack"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.page {
  padding: 10px 0;
}

.top {
  padding-bottom: 10px;
}

.title {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-bottom: 10px;
}

.error {
  padding: 10px 12px;
  border: 1px solid #ef4444;
  border-radius: 12px;
  background: white;
  margin-bottom: 12px;
}

.overlay {
  position: fixed;
  inset: 0;
  z-index: 2000;
  display: grid;
  place-items: start center;
  padding: 16px;
}

.overlay__backdrop {
  position: absolute;
  inset: 0;
  background: rgba(15, 23, 42, 0.55);
}

.overlay__panel {
  position: relative;
  width: min(880px, 100%);
  max-height: calc(100vh - 32px);
  background: rgb(225, 240, 255);
  border-radius: 16px;
  border: 1px solid rgba(15, 23, 42, 0.12);
  overflow: hidden;
  box-shadow: 0 24px 60px rgba(15, 23, 42, 0.35);
}

.overlay__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 12px;
  background: white;
  border-bottom: 1px solid #e2e8f0;
}

.overlay__title {
  font-weight: 800;
}

.overlay__close {
  border: 1px solid #e2e8f0;
  background: white;
  border-radius: 10px;
  padding: 6px 10px;
  cursor: pointer;
}

.overlay__content {
  padding: 12px;
  overflow: auto;
  max-height: calc(100vh - 32px - 52px);
}
</style>
