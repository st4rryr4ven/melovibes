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
import { useFlashStore } from '@/stores/flashStore'

const router = useRouter()
const flash = useFlashStore()

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

async function loadSearch(q: string) {
  searchLoading.value = true
  searchError.value = null
  try {
    const res = await musicApi.search({ q, limit: 20, market: 'FR' })
    searchItems.value = res.items
  } catch (e) {
    searchError.value = errorMessage(e, 'Erreur lors de la recherche')
    searchItems.value = []
  }
}

function closeSearch() {
  query.value = ''
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
    goToMusicDetail(item.local.musicId)
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
    flash.error(searchError.value)
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
    <section class="hero card">
      <div class="hero__row">
        <div class="hero__text">
          <h1 class="hero__title">Découvrir</h1>
          <p class="hero__subtitle">Recherche et sélection de musiques.</p>
        </div>
      </div>

      <div class="hero__search">
        <MusicSearchBar
          v-model="query"
          :loading="isSearchOpen ? searchLoading : albumsLoading"
          placeholder="Rechercher une musique…"
          @submit="() => query.trim() && loadSearch(query.trim())"
        />
      </div>
    </section>

    <section class="section">
      <div class="section__head">
        <h2 class="section__title">Nouveautés</h2>
        <span v-if="albumsLoading" class="muted section__meta">Chargement…</span>
      </div>

      <div v-if="albumsError" class="panel notice notice--error">
        <div class="notice__title">Chargement impossible</div>
        <div class="notice__text">{{ albumsError }}</div>
        <button class="btn btn--ghost" type="button" @click="loadAlbums">Réessayer</button>
      </div>

      <AlbumList :items="albums" @select="onSelectAlbum" />
    </section>

    <div v-if="isSearchOpen" class="overlay" role="dialog" aria-modal="true">
      <div class="overlay__backdrop" @click="closeSearch"></div>

      <div class="overlay__panel card">
        <div class="overlay__header">
          <div class="overlay__title">
            Résultats — <span class="overlay__query">“{{ query.trim() }}”</span>
          </div>
          <button type="button" class="overlay__close" @click="closeSearch" aria-label="Fermer">✕</button>
        </div>

        <div class="overlay__content">
          <div v-if="searchError" class="panel notice notice--error">
            <div class="notice__title">Erreur</div>
            <div class="notice__text">{{ searchError }}</div>
          </div>

          <div v-else-if="!searchLoading && searchItems.length === 0" class="panel notice">
            <div class="notice__title">Aucun résultat</div>
            <div class="notice__text">Aucune musique trouvée pour cette recherche.</div>
          </div>

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
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.hero {
  padding: 14px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  background: radial-gradient(900px 260px at 0% 0%, rgba(29, 185, 84, 0.16), transparent 55%),
  linear-gradient(180deg, rgba(255, 255, 255, 0.04), rgba(255, 255, 255, 0.02));
}

.hero__row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 10px;
}

.hero__title {
  margin: 0;
  font-size: 22px;
  font-weight: 950;
  letter-spacing: 0.2px;
}

.hero__subtitle {
  margin: 6px 0 0;
  color: var(--c-text-mute);
  font-size: 13px;
}

.hero__search {
  width: 100%;
}

.section {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.section__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.section__title {
  margin: 0;
  font-size: 16px;
  font-weight: 900;
  letter-spacing: 0.2px;
}

.section__meta {
  font-size: 13px;
}

.muted {
  color: var(--c-text-mute);
}

.chip {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 4px 10px;
  border-radius: 999px;
  border: 1px solid var(--c-border);
  background: rgba(255, 255, 255, 0.03);
  color: var(--c-text-soft);
  font-size: 12px;
}

.chip--soft {
  color: var(--c-text-mute);
}

.notice {
  padding: 12px;
  display: grid;
  gap: 8px;
}

.notice--error {
  border-color: rgba(255, 77, 79, 0.35);
  background: rgba(255, 77, 79, 0.12);
}

.notice__title {
  font-weight: 900;
  letter-spacing: 0.2px;
}

.notice__text {
  color: var(--c-text-soft);
  font-size: 14px;
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
  background: rgba(0, 0, 0, 0.55);
}

.overlay__panel {
  position: relative;
  width: min(900px, 100%);
  max-height: calc(100vh - 32px);
  overflow: hidden;
}

.overlay__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 12px;
  border-bottom: 1px solid var(--c-border);
  background: rgba(255, 255, 255, 0.02);
}

.overlay__title {
  font-weight: 900;
  letter-spacing: 0.2px;
}

.overlay__query {
  color: rgba(29, 185, 84, 0.95);
}

.overlay__close {
  border: 1px solid var(--c-border);
  background: rgba(255, 255, 255, 0.03);
  border-radius: 10px;
  padding: 6px 10px;
  cursor: pointer;
  color: var(--c-text);
}

.overlay__close:hover {
  background: rgba(255, 255, 255, 0.06);
  border-color: rgba(255, 255, 255, 0.14);
}

.overlay__content {
  padding: 12px;
  overflow: auto;
  max-height: calc(100vh - 32px - 56px);
  display: grid;
  gap: 10px;
}
</style>
