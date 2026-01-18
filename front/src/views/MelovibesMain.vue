<script setup lang="ts">
import {computed, onBeforeUnmount, onMounted, ref, watch} from 'vue'
import {useRouter} from 'vue-router'
import MusicSearchBar from '@/components/views/music/MusicSearchBar.vue'
import MusicList from '@/components/views/music/MusicList.vue'
import AlbumList from '@/components/views/album/AlbumList.vue'
import {useDebouncedRef} from '@/composables/useDebouncedRef'
import {musicApi} from '@/api/musicApi'
import {albumApi} from '@/api/albumApi'
import type {Music, MusicSearchItem, NewReleaseAlbumItem} from '@/types'
import {useFlashStore} from '@/stores/flashStore'
import {useStoreAuthentification} from '@/stores/storeAuthentification'

const router = useRouter()
const flash = useFlashStore()
const authStore = useStoreAuthentification()

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

const showOnlyFavorites = ref(false)
const favorites = ref<Music[]>([])
const favoritesLoading = ref(false)
const favoritesQuery = ref('')
const debouncedFavoritesQuery = useDebouncedRef(favoritesQuery, 250)

function errorMessage(err: unknown, fallback: string): string {
  if (err instanceof Error && err.message) return err.message
  if (typeof err === 'string' && err) return err
  return fallback
}

function normalizeText(v: unknown): string {
  return String(v ?? '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim()
}

const filteredFavorites = computed(() => {
  const q = normalizeText(debouncedFavoritesQuery.value)
  if (!q) return favorites.value

  return favorites.value.filter((m) => {
    const title = normalizeText((m as unknown as { title?: unknown }).title)
    const artistName = normalizeText((m as unknown as { artist?: { name?: unknown } }).artist?.name)
    const albumName = normalizeText((m as unknown as { album?: { name?: unknown } }).album?.name)

    return title.includes(q) || artistName.includes(q) || albumName.includes(q)
  })
})

async function loadAlbums() {
  albumsLoading.value = true
  albumsError.value = null
  try {
    const res = await albumApi.getNewReleases({limit: 20, country: 'FR'})
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
    const res = await musicApi.search({q, limit: 20, market: 'FR'})
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

function closeFavoritesSearch() {
  favoritesQuery.value = ''
}

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape' && isSearchOpen.value) closeSearch()
  if (e.key === 'Escape' && showOnlyFavorites.value && favoritesQuery.value.trim().length > 0) closeFavoritesSearch()
}

function goToMusicDetail(id: number) {
  router.push({name: 'musicDetail', params: {id}})
}

async function onSelectSearchTrack(item: MusicSearchItem) {
  if (busySpotifyId.value) return

  if (item.source === 'local') {
    if (item.local.musicId)
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
  router.push({name: 'albumTracks', params: {albumId: item.albumId}})
}

async function toggleFavorites() {
  showOnlyFavorites.value = !showOnlyFavorites.value
  closeFavoritesSearch()

  if (showOnlyFavorites.value) {
    const userId = authStore.utilisateurConnecte?.id

    if (!userId) {
      flash.error("Vous devez être connecté pour voir vos favoris")
      showOnlyFavorites.value = false
      return
    }

    favoritesLoading.value = true
    try {
      const data = await musicApi.getUserLibrary(userId)
      favorites.value = data.map((m) => musicApi.enrichMusicData(m))
    } catch (e) {
      console.error(e)
      flash.error("Impossible de charger votre bibliothèque")
      showOnlyFavorites.value = false
    } finally {
      favoritesLoading.value = false
    }
  }
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
          <h1 class="hero__title">{{ showOnlyFavorites ? 'Mes Favoris' : 'Découvrir' }}</h1>
          <p class="hero__subtitle">
            {{
              showOnlyFavorites ? 'Vos musiques enregistrées.' : 'Recherche et sélection de musiques.'
            }}
          </p>
        </div>

        <div v-if="authStore.utilisateurConnecte" class="hero__actions">
          <button
            v-if="showOnlyFavorites"
            class="btn btn--primary btn--sm"
            @click="router.push({ name: 'favoritesActivity' })"
            style="margin-right: 8px;"
          >
            <span class="icon">🔔</span>
            Voir l'activité
          </button>
          <button
            class="btn btn--secondary btn--sm"
            :class="{ 'btn--active': showOnlyFavorites }"
            @click="toggleFavorites"
          >
            <span class="icon">{{ showOnlyFavorites ? '←' : '♥' }}</span>
            {{ showOnlyFavorites ? 'Retour' : 'Mes Favoris' }}
          </button>
        </div>
      </div>

      <div v-if="!showOnlyFavorites" class="hero__search">
        <MusicSearchBar
          v-model="query"
          :loading="isSearchOpen ? searchLoading : albumsLoading"
          placeholder="Rechercher une musique…"
          @submit="() => query.trim() && loadSearch(query.trim())"
        />
      </div>

      <div v-else class="hero__search">
        <MusicSearchBar
          v-model="favoritesQuery"
          :loading="favoritesLoading"
          placeholder="Rechercher dans mes favoris…"
          @submit="() => null"
        />
        <div v-if="favoritesQuery.trim().length > 0" class="hero__searchMeta muted">
          {{ filteredFavorites.length }} résultat{{ filteredFavorites.length > 1 ? 's' : '' }}
          <button type="button" class="link" @click="closeFavoritesSearch">Effacer</button>
        </div>
      </div>
    </section>

    <section class="section">
      <template v-if="showOnlyFavorites">
        <div class="section__head">
          <h2 class="section__title">Ma Collection</h2>
        </div>

        <div v-if="favoritesLoading" class="muted">Chargement de vos favoris...</div>

        <div v-else-if="favorites.length === 0" class="panel notice">
          <div class="notice__title">C'est bien vide ici...</div>
          <div class="notice__text">Vous n'avez pas encore ajouté de musiques à vos favoris.</div>
        </div>

        <div v-else class="favorites-grid">
          <MusicList
            :items="filteredFavorites"
            mode="local"
            @select="goToMusicDetail"
          />
        </div>
      </template>

      <template v-else>
        <div class="section__head">
          <h2 class="section__title">Nouveautés</h2>
          <span v-if="albumsLoading" class="muted section__meta">Chargement…</span>
        </div>

        <div v-if="albumsError" class="panel notice notice--error">
          <div class="notice__title">Chargement impossible</div>
          <div class="notice__text">{{ albumsError }}</div>
          <button class="btn btn--ghost" type="button" @click="loadAlbums">Réessayer</button>
        </div>

        <AlbumList :items="albums" @select="onSelectAlbum"/>
      </template>
    </section>

    <div v-if="isSearchOpen" class="overlay" role="dialog" aria-modal="true">
      <div class="overlay__backdrop" @click="closeSearch"></div>

      <div class="overlay__panel card">
        <div class="overlay__header">
          <div class="overlay__title">
            Résultats — <span class="overlay__query">“{{ query.trim() }}”</span>
          </div>
          <button type="button" class="overlay__close" @click="closeSearch" aria-label="Fermer">✕
          </button>
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
  display: grid;
  gap: 8px;
}

.hero__searchMeta {
  font-size: 12px;
  display: inline-flex;
  align-items: center;
  gap: 10px;
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

.link {
  background: transparent;
  border: none;
  padding: 0;
  cursor: pointer;
  color: var(--c-text-soft);
  text-decoration: underline;
  font-weight: 700;
}

.link:hover {
  color: var(--c-text);
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
