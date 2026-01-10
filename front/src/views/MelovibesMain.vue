<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import MusicSearchBar from '@/components/MusicSearchBar.vue'
import MusicList from '@/components/MusicList.vue'
import { useDebouncedRef } from '@/composables/useDebouncedRef'
import { getMusicNewReleases, importSpotifyTrack, searchMusic } from '@/api/musicApi'
import type { MusicSearchItem, MusicUiItem, SpotifyTrack } from '@/types'

const router = useRouter()

const query = ref('')
const debouncedQuery = useDebouncedRef(query, 300)

const newReleasesLoading = ref(false)
const newReleasesError = ref<string | null>(null)
const newReleasesItems = ref<MusicUiItem[]>([])

const searchLoading = ref(false)
const searchError = ref<string | null>(null)
const searchItems = ref<MusicUiItem[]>([])
const busyKey = ref<string | null>(null)

const isSearchOpen = computed(() => query.value.trim().length > 0)

function spotifyPicture(track: SpotifyTrack): string | null {
  return track.album?.images?.[0]?.url ?? null
}

function spotifyArtistsLabel(track: SpotifyTrack): string {
  const names = (track.artists ?? []).map((a) => a.name).filter(Boolean)
  return names.join(', ') || 'Artiste inconnu'
}

function mapSearchItem(item: MusicSearchItem): MusicUiItem {
  if (item.source === 'local') {
    return {
      key: `local-${item.local.musicId}`,
      source: 'search-local',
      musicId: item.local.musicId,
      spotifyTrackId: item.local.spotifyId,
      title: item.local.title,
      artistsLabel: item.local.artists.map((a) => a.name).join(', ') || 'Artiste inconnu',
      picture: item.local.picture,
      link: item.local.link,
      popularity: item.local.popularity,
      isImported: true
    }
  }

  return {
    key: `spotify-${item.spotify.id}`,
    source: 'search-spotify',
    musicId: item.local.musicId,
    spotifyTrackId: item.spotify.id,
    title: item.spotify.name,
    artistsLabel: spotifyArtistsLabel(item.spotify),
    picture: spotifyPicture(item.spotify),
    link: item.spotify.external_urls?.spotify ?? null,
    isImported: item.local.isImported
  }
}

async function loadNewReleases() {
  newReleasesLoading.value = true
  newReleasesError.value = null
  try {
    const res = await getMusicNewReleases({ limit: 20 })
    newReleasesItems.value = res.items.map((m) => ({
      key: `new-${m.musicId}`,
      source: 'new-releases',
      musicId: m.musicId,
      spotifyTrackId: m.spotifyId,
      title: m.title,
      artistsLabel: m.artists.map((a) => a.name).join(', ') || 'Artiste inconnu',
      picture: m.picture,
      link: m.link,
      popularity: m.popularity,
      importedAt: m.importedAt,
      isImported: true
    }))
  } catch (e: any) {
    newReleasesError.value = e?.message ?? 'Erreur lors du chargement'
    newReleasesItems.value = []
  } finally {
    newReleasesLoading.value = false
  }
}

let lastSearchId = 0
async function loadSearch(q: string) {
  const searchId = ++lastSearchId
  searchLoading.value = true
  searchError.value = null
  try {
    const res = await searchMusic({ q, limit: 20 })
    if (searchId !== lastSearchId) return
    searchItems.value = res.items.map(mapSearchItem)
  } catch (e: any) {
    if (searchId !== lastSearchId) return
    searchError.value = e?.message ?? 'Erreur lors de la recherche'
    searchItems.value = []
  } finally {
    if (searchId === lastSearchId) searchLoading.value = false
  }
}

function goToDetail(id: number) {
  router.push({ name: 'musicDetail', params: { id } })
}

async function onSelect(item: MusicUiItem) {
  if (busyKey.value) return

  if (typeof item.musicId === 'number' && !Number.isNaN(item.musicId)) {
    goToDetail(item.musicId)
    return
  }

  if (!item.spotifyTrackId) return

  busyKey.value = item.key
  searchError.value = null

  try {
    const imported = await importSpotifyTrack(item.spotifyTrackId)
    searchItems.value = searchItems.value.map((it: { key: never }) =>
      it.key !== item.key
        ? it
        : {
          ...it,
          musicId: imported.musicId,
          isImported: true
        }
    )
    goToDetail(imported.musicId)
  } catch (e: any) {
    searchError.value = e?.message ?? "Erreur lors de l'import"
  } finally {
    busyKey.value = null
  }
}

function closeSearch() {
  query.value = ''
  lastSearchId++
  searchLoading.value = false
  searchError.value = null
  searchItems.value = []
  busyKey.value = null
}

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape' && isSearchOpen.value) closeSearch()
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
      busyKey.value = null
      return
    }
    await loadSearch(trimmed)
  }
)

onMounted(() => {
  loadNewReleases()
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
        :loading="isSearchOpen ? searchLoading : newReleasesLoading"
        placeholder="Rechercher une musique..."
        @submit="() => query.trim() && loadSearch(query.trim())"
      />

      <div class="title">
        <h2>New Releases</h2>
      </div>

      <div v-if="newReleasesError" class="error">{{ newReleasesError }}</div>
    </div>

    <MusicList :items="newReleasesItems" @select="onSelect" />

    <div v-if="!newReleasesLoading && newReleasesItems.length === 0 && !newReleasesError" class="empty">
      Aucun résultat.
    </div>

    <div v-if="isSearchOpen" class="overlay" role="dialog" aria-modal="true">
      <div class="overlay__backdrop" @click="closeSearch"></div>

      <div class="overlay__panel">
        <div class="overlay__header">
          <div class="overlay__title">Résultats pour "{{ query.trim() }}"</div>
          <button type="button" class="overlay__close" @click="closeSearch">✕</button>
        </div>

        <div v-if="searchError" class="error">{{ searchError }}</div>

        <div class="overlay__content">
          <MusicList :items="searchItems" :busy-key="busyKey" @select="onSelect" />
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

.hint {
  font-size: 12px;
  opacity: 0.7;
}

.error {
  padding: 10px 12px;
  border: 1px solid #ef4444;
  border-radius: 12px;
  background: white;
  margin-bottom: 12px;
}

.empty {
  margin-top: 12px;
  opacity: 0.7;
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
