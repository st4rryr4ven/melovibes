<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import MusicSearchBar from '@/components/MusicSearchBar.vue'
import MusicList from '@/components/MusicList.vue'
import { useDebouncedRef } from '@/composables/useDebouncedRef'
import { getMusicNewReleases, importSpotifyTrack, searchMusic } from '@/api/musicApi'
import type { MusicSearchItem, MusicUiItem, SpotifyTrack } from '@/types/index.ts'

const query = ref('')
const debouncedQuery = useDebouncedRef(query, 300)

const loading = ref(false)
const error = ref<string | null>(null)
const items = ref<MusicUiItem[]>([])
const importingKey = ref<string | null>(null)

const mode = computed(() => (query.value.trim() === '' ? 'new-releases' : 'search'))

function spotifyPicture(track: SpotifyTrack): string | null {
  const url = track.album?.images?.[0]?.url
  return url ?? null
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
  loading.value = true
  error.value = null

  try {
    const res = await getMusicNewReleases({ limit: 20 })
    items.value = res.items.map((m) => ({
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
    error.value = e?.message ?? 'Erreur lors du chargement'
    items.value = []
  } finally {
    loading.value = false
  }
}

let lastSearchId = 0

async function loadSearch(q: string) {
  const searchId = ++lastSearchId
  loading.value = true
  error.value = null

  try {
    const res = await searchMusic({ q, limit: 20 })
    if (searchId !== lastSearchId) return
    items.value = res.items.map(mapSearchItem)
  } catch (e: any) {
    if (searchId !== lastSearchId) return
    error.value = e?.message ?? 'Erreur lors de la recherche'
    items.value = []
  } finally {
    if (searchId === lastSearchId) loading.value = false
  }
}

async function onImport(spotifyTrackId: string, itemKey: string) {
  importingKey.value = itemKey
  error.value = null

  try {
    const imported = await importSpotifyTrack(spotifyTrackId)
    items.value = items.value.map((it) =>
      it.key !== itemKey
        ? it
        : {
          ...it,
          musicId: imported.musicId,
          isImported: true
        }
    )
  } catch (e: any) {
    error.value = e?.message ?? "Erreur lors de l'import"
  } finally {
    importingKey.value = null
  }
}

watch(
  () => debouncedQuery.value,
  async (q) => {
    const trimmed = q.trim()
    if (trimmed === '') {
      lastSearchId++
      await loadNewReleases()
      return
    }
    await loadSearch(trimmed)
  }
)
</script>

<template>
  <div class="page">
    <MusicSearchBar v-model="query" :loading="loading" @submit="() => query.trim() && loadSearch(query.trim())" />

    <div class="header">
      <h2 v-if="mode === 'new-releases'">20 musiques les + récentes</h2>
      <h2 v-else>Résultats pour "{{ query.trim() }}"</h2>
      <div class="hint">Clique sur un titre pour ouvrir le lien.</div>
    </div>

    <div v-if="error" class="error">{{ error }}</div>

    <MusicList :items="items" :importing-key="importingKey" @import="onImport" />

    <div v-if="!loading && items.length === 0 && !error" class="empty">
      Aucun résultat.
    </div>
  </div>
</template>

<style scoped>
.page {
  padding: 8px 0;
}

.header {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-bottom: 12px;
}

.hint {
  font-size: 12px;
  opacity: 0.7;
}

.error {
  padding: 10px 12px;
  border: 1px solid #ef4444;
  border-radius: 10px;
  background: white;
  margin-bottom: 12px;
}

.empty {
  margin-top: 12px;
  opacity: 0.7;
}
</style>
