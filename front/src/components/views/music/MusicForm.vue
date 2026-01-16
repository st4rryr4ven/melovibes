<script setup lang="ts">
import {computed, onMounted, reactive, ref, watch} from 'vue'
import {useRouter} from 'vue-router'
import {artistApi} from '@/api/artistApi.ts'
import {musicApi} from '@/api/musicApi.ts'
import {API_URL} from '@/api/httpClient.ts'
import type {Artist, MusicSearchResponse} from '@/types.ts'
import {useFlashStore} from '@/stores/flashStore.ts'

const props = defineProps<{ id?: number }>()
const router = useRouter()
const flash = useFlashStore()

const isEdit = computed(() => Number.isFinite(props.id) && !!props.id)

function errorMessage(err: unknown, fallback: string): string {
  if (err instanceof Error && err.message) return err.message
  if (typeof err === 'string' && err) return err
  return fallback
}

const form = reactive({
  title: '',
  spotifyTrackId: '',
  selectedArtists: [] as Artist[],
  genre: [] as string[],
  picture: '',
  link: ''
});

const genreInput = computed({
  get: () => form.genre.join(', '),
  set: (val: string) => {
    form.genre = val
      .split(',')
      .map(g => g.trim())
      .filter(Boolean)
  }
});

const artistQuery = ref('')
const artistResults = ref<Artist[]>([])
const artistLoading = ref(false)
const importing = ref(false)
const loadingExisting = ref(false)

function apiBasePath(): string {
  try {
    const url = new URL(API_URL)
    return url.pathname.endsWith('/') ? url.pathname : `${url.pathname}/`
  } catch {
    return '/api/'
  }
}

function resetForm() {
  form.title = ''
  form.spotifyTrackId = ''
  form.selectedArtists = []
  form.picture = ''
  form.link = ''
  form.genre = []
  artistQuery.value = ''
  artistResults.value = []
}

async function loadExistingMusic(): Promise<void> {
  if (!isEdit.value || !props.id) return

  loadingExisting.value = true
  try {
    const m = await musicApi.get(props.id)

    form.title = m.title ?? ''
    form.selectedArtists = [...(m.artists ?? [])]
    form.picture = m.picture ?? ''
    form.link = m.link ?? ''
    form.genre = Array.isArray(m.genre) ? [...m.genre] : []

    form.spotifyTrackId = ''
    artistQuery.value = ''
    artistResults.value = []
  } catch (err) {
    flash.error(errorMessage(err, 'Chargement impossible.'))
    router.back()
  } finally {
    loadingExisting.value = false
  }
}


watch(artistQuery, async (q) => {
  const query = q.trim()
  if (!query) {
    artistResults.value = []
    return
  }

  artistLoading.value = true
  try {
    const res = await artistApi.search({q: query, limit: 6, offset: 0})
    artistResults.value = res.items ?? []
  } catch {
    artistResults.value = []
  } finally {
    artistLoading.value = false
  }
})

function highlightQuery(name: string) {
  const q = artistQuery.value.trim()
  if (!q) return name
  const escaped = q.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&')
  const regex = new RegExp(`(${escaped})`, 'gi')
  return name.replace(regex, `<mark>$1</mark>`)
}

function selectArtist(artist: Artist) {
  const alreadySelected = form.selectedArtists.some((a) => {
    if (a.id > 0 && artist.id > 0) return a.id === artist.id
    if (a.spotifyId && artist.spotifyId) return a.spotifyId === artist.spotifyId
    return a.name.toLowerCase() === artist.name.toLowerCase()
  })

  if (!alreadySelected) form.selectedArtists.push(artist)

  artistQuery.value = ''
  artistResults.value = []
}

function removeArtist(index: number) {
  form.selectedArtists.splice(index, 1)
}

async function addNewArtist(name: string) {
  const n = name.trim()
  if (!n) return

  try {
    const created = await artistApi.create({name: n})
    const alreadySelected = form.selectedArtists.some(
      (a) => a.id === created.id || a.name.toLowerCase() === created.name.toLowerCase()
    )
    if (!alreadySelected) form.selectedArtists.push(created)

    artistQuery.value = ''
    artistResults.value = []
  } catch (err) {
    flash.error(errorMessage(err, 'Création impossible.'))
  }
}

// async function onImportSpotify() {
//   const idOrUrl = form.spotifyTrackId.trim()
//   if (!idOrUrl || importing.value) return
//
//   importing.value = true
//   try {
//     const imported = await musicApi.importFromSpotify(idOrUrl)
//     flash.success('Musique ajoutée.')
//     await router.push({ name: 'musicDetail', params: { id: imported.id } })
//   } catch (err) {
//     flash.error(errorMessage(err, 'Action impossible.'))
//   } finally {
//     importing.value = false
//   }
// }

async function saveMusic() {
  const title = form.title.trim()

  if (!title) {
    flash.warning('Titre requis.')
    return
  }

  if (form.selectedArtists.length === 0) {
    flash.warning('Ajoute un artiste.')
    return
  }

  importing.value = true
  try {
    const dbArtistIds: number[] = []

    for (let i = 0; i < form.selectedArtists.length; i++) {
      const artist = form.selectedArtists[i]
      if (!artist) continue

      if (artist.id > 0) {
        dbArtistIds.push(artist.id)
        continue
      }

      if (artist.spotifyId) {
        const imported = await artistApi.importFromSpotify(artist.spotifyId)
        form.selectedArtists[i] = imported
        dbArtistIds.push(imported.id)
        continue
      }

      const created = await artistApi.create({name: artist.name})
      form.selectedArtists[i] = created
      dbArtistIds.push(created.id)
    }

    const basePath = apiBasePath()
    const artistIris = dbArtistIds.map((id) => `${basePath}artists/${id}`.replace(/([^:])\/\/+/g, '$1/'))

    if (isEdit.value && props.id) {
      const updated = await musicApi.patch(props.id, {
        title,
        artists: artistIris,
        picture: form.picture || null,
        link: form.link || null,
        genre: form.genre.length ? form.genre : null
      })
      flash.success('Enregistré.')
      await router.push({name: 'musicDetail', params: {id: updated.id}})
      return
    }

    let searchResults: MusicSearchResponse | null = null
    try {
      searchResults = await musicApi.search({q: title, limit: 20, offset: 0, market: 'FR'})
    } catch {
      searchResults = null
    }

    if (searchResults) {
      const localMatch = searchResults.items.find((item) => {
        if (item.source !== 'local') return false
        const m = item.local
        if (!m) return false
        const titleMatch = (m.title ?? '').toLowerCase() === title.toLowerCase()
        const artistMatch = (m.artists ?? []).some((a) => dbArtistIds.includes(a.id))
        return titleMatch && artistMatch
      })

      if (localMatch) {
        flash.warning('Déjà existante.')
        resetForm()
        return
      }

      const selectedArtistNames = new Set(form.selectedArtists.map((a) => a.name.toLowerCase()))
      const spotifyMatch = searchResults.items.find((item) => {
        if (item.source !== 'spotify' || !item.spotify) return false
        const titleMatch = item.spotify.name.toLowerCase() === title.toLowerCase()
        const artistMatch = (item.spotify.artists ?? []).some((a) => {
          const raw = (a as { name?: unknown } | null | undefined)?.name
          const n = typeof raw === 'string' ? raw.toLowerCase() : ''
          return n && selectedArtistNames.has(n)
        })
        return titleMatch && artistMatch
      })

      if (spotifyMatch?.spotify?.id) {
        const imported = await musicApi.importFromSpotify(spotifyMatch.spotify.id)
        flash.success('Musique ajoutée.')
        resetForm()
        await router.push({name: 'musicDetail', params: {id: imported.id}})
        return
      }
    }

    await musicApi.create({
      title,
      artists: artistIris,
      picture: form.picture || null,
      link: form.link || null,
      genre: form.genre.length ? form.genre : null
    })
    flash.success('Musique créée.')
    resetForm()
  } catch (err) {
    flash.error(errorMessage(err, 'Action impossible.'))
  } finally {
    importing.value = false
  }
}

onMounted(loadExistingMusic)
watch(
  () => props.id,
  async (newId) => {
    if (Number.isFinite(newId) && newId) {
      await loadExistingMusic()
      return
    }
    resetForm()
  },
  {immediate: true}
)

function goBack() {
  router.back()
}
</script>

<template>
  <div class="page container">
    <section class="card wrap">
      <header class="head">
        <div class="head__title">{{ isEdit ? 'Édition' : 'Nouvelle musique' }}</div>
        <div class="head__actions">
          <button class="btn btn--ghost" type="button" @click="goBack">Retour</button>
        </div>
      </header>

      <div class="divider"/>

      <form class="form" @submit.prevent="saveMusic">
        <div v-if="loadingExisting" class="loading">
          <div class="skeleton"/>
          <div class="skeleton"/>
          <div class="skeleton"/>
        </div>

        <template v-else>
          <!--          <div v-if="!isEdit" class="field">-->
          <!--            <label class="label">Spotify</label>-->
          <!--            <div class="row">-->
          <!--              <input v-model="form.spotifyTrackId" class="input" type="text" placeholder="ID ou URL" />-->
          <!--              <button class="btn btn&#45;&#45;primary" type="button" :disabled="importing || !form.spotifyTrackId.trim()" @click="onImportSpotify">-->
          <!--                {{ importing ? '…' : 'Importer' }}-->
          <!--              </button>-->
          <!--            </div>-->
          <!--          </div>-->

          <div class="field">
            <label class="label">Titre</label>
            <input v-model="form.title" class="input" type="text" placeholder="Titre"/>
          </div>

          <div class="field artistField">
            <label class="label">Artistes</label>

            <div v-if="form.selectedArtists.length" class="chips">
              <button
                v-for="(artist, index) in form.selectedArtists"
                :key="artist.id || artist.spotifyId || artist.name"
                type="button"
                class="chip"
                @click="removeArtist(index)"
                :title="artist.name"
              >
                <span class="chip__text">{{ artist.name }}</span>
                <span class="chip__x" aria-hidden="true">×</span>
              </button>
            </div>

            <div class="search">
              <input
                v-model="artistQuery"
                class="input"
                type="text"
                placeholder="Rechercher ou écrire un artiste…"
                @keydown.enter.prevent="addNewArtist(artistQuery)"
              />
              <span v-if="artistLoading" class="spinner" aria-hidden="true"/>
            </div>

            <div v-if="artistResults.length" class="results">
              <button
                v-for="artist in artistResults"
                :key="artist.id || artist.spotifyId || artist.name"
                class="result"
                type="button"
                @click="selectArtist(artist)"
              >
                <span class="result__name" v-html="highlightQuery(artist.name)"></span>
              </button>
            </div>

            <div v-else-if="artistQuery.trim()" class="create-artist">
              <button
                class="btn btn--ghost btn--sm"
                type="button"
                @click="addNewArtist(artistQuery)"
              >
                Créer « {{ artistQuery.trim() }} »
              </button>
            </div>
          </div>

          <div class="field">
            <label class="label">Image URL</label>
            <input v-model="form.picture" class="input" type="url" placeholder="https://…"/>
          </div>

          <div class="field">
            <label class="label">Lien externe</label>
            <input v-model="form.link" class="input" type="url" placeholder="https://…"/>
          </div>

          <div class="field">
            <label class="label">Genres (séparés par des virgules)</label>
            <input
              v-model="genreInput"
              class="input"
              type="text"
              placeholder="Rock, Pop, Indie"
            />
          </div>


          <div class="actions">
            <button class="btn btn--primary" type="submit" :disabled="importing">
              {{ isEdit ? 'Enregistrer' : 'Créer' }}
            </button>
            <button class="btn btn--ghost" type="button" :disabled="importing" @click="goBack">
              Annuler
            </button>
          </div>
        </template>
      </form>
    </section>
  </div>
</template>

<style scoped>
.page {
  padding: 14px 0;
}

.wrap {
  padding: 0;
}

.head {
  padding: 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.head__title {
  font-size: 16px;
  font-weight: 950;
  letter-spacing: 0.2px;
}

.form {
  padding: 14px;
  display: grid;
  gap: 12px;
}

.field {
  display: grid;
  gap: 8px;
}

.label {
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0.2px;
  color: var(--c-text-soft);
}

.row {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 10px;
  align-items: center;
}

.actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  padding-top: 4px;
}

.artistField {
  position: relative;
}

.chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.chip {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 8px 10px;
  border-radius: 999px;
  border: 1px solid var(--c-border);
  background: var(--c-surface-3);
  color: var(--c-text);
  cursor: pointer;
  transition: background 0.12s ease, border-color 0.12s ease, transform 0.12s ease;
}

.chip:hover {
  background: #1a3227;
  border-color: var(--c-border-2);
  transform: translateY(-1px);
}

.chip__text {
  max-width: 260px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-weight: 800;
}

.chip__x {
  opacity: 0.8;
  font-size: 16px;
  line-height: 1;
}

.search {
  position: relative;
}

.spinner {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  width: 16px;
  height: 16px;
  border-radius: 999px;
  border: 2px solid rgba(245, 248, 252, 0.22);
  border-top-color: rgba(29, 185, 84, 0.95);
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: translateY(-50%) rotate(360deg);
  }
}

.results {
  display: grid;
  gap: 8px;
}

.result {
  width: 100%;
  text-align: left;
  border-radius: 14px;
  padding: 10px 12px;
  border: 1px solid var(--c-border);
  background: var(--c-surface-2);
  color: var(--c-text);
  cursor: pointer;
  transition: transform 0.12s ease, background 0.12s ease, border-color 0.12s ease;
}

.result:hover {
  transform: translateY(-1px);
  background: #193024;
  border-color: var(--c-border-2);
}

.result__name :deep(mark) {
  background: rgba(29, 185, 84, 0.35);
  color: rgba(245, 248, 252, 0.98);
  padding: 0 3px;
  border-radius: 4px;
}

.btn--sm {
  padding: 8px 12px;
}

.loading {
  display: grid;
  gap: 10px;
}

.skeleton {
  height: 44px;
  border-radius: var(--radius-md);
  border: 1px solid var(--c-border);
  background: linear-gradient(
    90deg,
    rgba(255, 255, 255, 0.04),
    rgba(255, 255, 255, 0.08),
    rgba(255, 255, 255, 0.04)
  );
  background-size: 200% 100%;
  animation: shimmer 1.2s ease-in-out infinite;
}

@keyframes shimmer {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

@media (max-width: 720px) {
  .row {
    grid-template-columns: 1fr;
  }
}
</style>
