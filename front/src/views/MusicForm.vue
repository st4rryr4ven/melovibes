<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { artistApi } from '@/api/artistApi'
import { musicApi } from '@/api/musicApi'
import { API_URL } from '@/api/httpClient'
import type { Artist, MusicSearchResponse } from '@/types'

const props = defineProps<{ id?: number }>()
const router = useRouter()

const isEdit = computed(() => !Number.isNaN(props.id))

function errorMessage(err: unknown, fallback: string): string {
  if (err instanceof Error && err.message) return err.message
  if (typeof err === 'string' && err) return err
  return fallback
}

const form = reactive({
  title: '',
  spotifyTrackId: '',
  selectedArtists: [] as Artist[]
})

const artistQuery = ref('')
const artistResults = ref<Artist[]>([])
const artistLoading = ref(false)
const importing = ref(false)
const loadingExisting = ref(false)

async function loadExistingMusic(): Promise<void> {
  if (!isEdit.value || !props.id) return

  loadingExisting.value = true
  try {
    const m = await musicApi.get(props.id)
    form.title = m.title ?? ''
    form.selectedArtists = [...(m.artists ?? [])]
    form.spotifyTrackId = ''
    artistQuery.value = ''
    artistResults.value = []
  } catch (err) {
    alert(errorMessage(err, 'Erreur lors du chargement de la musique'))
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
    const res = await artistApi.search({ q: query, limit: 5, offset: 0 })
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
    const created = await artistApi.create({ name: n })

    const alreadySelected = form.selectedArtists.some((a) => a.id === created.id || a.name.toLowerCase() === created.name.toLowerCase())
    if (!alreadySelected) form.selectedArtists.push(created)

    artistQuery.value = ''
    artistResults.value = []
  } catch (err) {
    alert(errorMessage(err, "Erreur lors de la création de l'artiste"))
  }
}

async function onImportSpotify() {
  const idOrUrl = form.spotifyTrackId.trim()
  if (!idOrUrl) return

  importing.value = true
  try {
    const imported = await musicApi.importFromSpotify(idOrUrl)
    alert(`La musique "${imported.title}" a été importée avec succès !`)
  } catch (err) {
    alert(errorMessage(err, "Échec de l’importation depuis Spotify"))
  } finally {
    importing.value = false
  }
}

function apiBasePath(): string {
  try {
    const url = new URL(API_URL)
    const p = url.pathname.endsWith('/') ? url.pathname : `${url.pathname}/`
    return p
  } catch {
    return '/api/'
  }
}

async function saveMusic() {
  const title = form.title.trim()
  if (!title) {
    alert('Veuillez entrer un titre.')
    return
  }

  if (form.selectedArtists.length === 0) {
    alert('Veuillez sélectionner au moins un artiste.')
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

      const created = await artistApi.create({ name: artist.name })
      form.selectedArtists[i] = created
      dbArtistIds.push(created.id)
    }

    const basePath = apiBasePath()
    const artistIris = dbArtistIds.map((id) => `${basePath}artists/${id}`.replace(/([^:])\/\/+/g, '$1/'))

    if (isEdit.value && props.id) {
      const updated = await musicApi.patch(props.id, { title, artists: artistIris })
      alert(`Musique "${updated.title}" mise à jour !`)
      await router.push({ name: 'musicDetail', params: { id: updated.id } })
      return
    }

    let searchResults: MusicSearchResponse | null = null
    try {
      searchResults = await musicApi.search({ q: title, limit: 20, offset: 0, market: 'FR' })
    } catch {
      searchResults = null
    }

    if (searchResults) {
      const localMatch = searchResults.items.find((item) => {
        if (item.source !== 'local') return false
        const m = item.local
        if (!m) return false
        const titleMatch = m.title.toLowerCase() === title.toLowerCase()
        const artistMatch = (m.artists ?? []).some((a) => dbArtistIds.includes(a.id))
        return titleMatch && artistMatch
      })

      if (localMatch) {
        alert(`La musique "${title}" existe déjà dans la base de données.`)
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
        alert(`La musique "${imported.title}" a été importée depuis Spotify !`)
        resetForm()
        return
      }
    }

    await musicApi.create({
      title,
      artists: artistIris
    })

    alert(`Musique "${title}" créée avec succès !`)
    resetForm()
  } catch (err) {
    alert(errorMessage(err, 'Erreur lors de la création de la musique'))
  } finally {
    importing.value = false
  }
}

function resetForm() {
  form.title = ''
  form.spotifyTrackId = ''
  form.selectedArtists = []
  artistQuery.value = ''
  artistResults.value = []
}

onMounted(loadExistingMusic)
watch(() => props.id, loadExistingMusic)
</script>

<template>
  <div class="content-box music-form">
    <form @submit.prevent="saveMusic">
      <h2 class="form-title">{{ isEdit ? 'Éditer une musique' : 'Créer une musique' }}</h2>

      <div v-if="loadingExisting" class="status">Chargement...</div>

      <template v-if="!isEdit">
        <div class="form-group">
          <label>ID ou URL de la musique Spotify</label>
          <div class="input-group">
            <input v-model="form.spotifyTrackId" type="text" placeholder="ID ou URL de la musique Spotify" />
            <button type="button" @click="onImportSpotify" :disabled="importing">
              {{ importing ? 'Importation...' : 'Importer depuis Spotify' }}
            </button>
          </div>
        </div>

        <hr />
      </template>

      <div class="form-group">
        <label class="title-label">Titre</label>
        <input v-model="form.title" type="text" placeholder="Titre de la musique" />
      </div>

      <div class="form-group artist-field">
        <label>Artistes</label>

        <div v-if="form.selectedArtists.length > 0" class="selected-artists">
          <div
            v-for="(artist, index) in form.selectedArtists"
            :key="artist.id || artist.spotifyId || artist.name"
            class="selected-artist"
          >
            <span class="artist-name">{{ artist.name }}</span>
            <button type="button" @click="removeArtist(index)" class="remove-artist" title="Retirer cet artiste">
              ×
            </button>
          </div>
        </div>

        <input v-model="artistQuery" type="text" placeholder="Rechercher un artiste..." />
        <div v-if="artistLoading" class="status">Recherche en cours...</div>

        <ul v-if="artistResults.length" class="artist-results">
          <li v-for="artist in artistResults" :key="artist.id || artist.spotifyId || artist.name" @click="selectArtist(artist)">
            <span class="artist-name" v-html="highlightQuery(artist.name)"></span>
          </li>
        </ul>

        <button v-if="artistResults.length === 0 && artistQuery.trim()" type="button" @click="addNewArtist(artistQuery)">
          Créer l'artiste "{{ artistQuery }}"
        </button>
      </div>

      <hr />

      <div class="form-group">
        <button type="submit" class="submit-btn">Enregistrer la musique</button>
      </div>
    </form>
  </div>
</template>

<style scoped>
@import "@/components/css/content-box.css";

.music-form {
  max-width: 600px;
  margin: 1rem auto;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-title {
  font-weight: bold;
  color: #000;
  margin-bottom: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  position: relative;
}

.form-group label {
  font-weight: bold;
  color: #111;
}

.title-label {
  font-weight: bold;
  color: #000;
}

.input-group {
  display: flex;
  gap: 0.5rem;
}

input[type='text'] {
  flex: 1;
  padding: 10px 12px;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  background: #fff;
  color: #111;
}

input::placeholder {
  color: #888;
}

button {
  padding: 8px 12px;
  background-color: #16a34a;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: background 0.2s;
}

button:hover:not(:disabled) {
  background-color: #15803d;
}

button:disabled {
  background-color: #94a3b8;
  cursor: not-allowed;
}

.selected-artists {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.selected-artist {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.375rem 0.75rem;
  background-color: #e2e8f0;
  border-radius: 6px;
  font-size: 0.875rem;
}

.selected-artist .artist-name {
  color: #111;
  font-weight: 500;
}

.remove-artist {
  background: none;
  border: none;
  color: #64748b;
  cursor: pointer;
  font-size: 1.25rem;
  line-height: 1;
  padding: 0;
  width: 1.25rem;
  height: 1.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  transition: background 0.2s, color 0.2s;
}

.remove-artist:hover {
  background-color: #cbd5e1;
  color: #dc2626;
}

.artist-results {
  background: #fff;
  border: 1px solid #cbd5e1;
  margin-top: 4px;
  padding: 0;
  list-style: none;
  position: absolute;
  z-index: 10;
  width: 100%;
  max-height: 250px;
  overflow-y: auto;
  border-radius: 6px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.artist-field li {
  display: flex;
  align-items: center;
  padding: 6px 10px;
  cursor: pointer;
  color: #111;
}

.artist-field li:hover {
  background-color: #e2e8f0;
}

.artist-name {
  flex: 1;
}

.artist-field mark {
  background-color: #16a34a;
  color: white;
  padding: 0 2px;
  border-radius: 2px;
}

.status {
  font-size: 12px;
  color: #555;
}

.submit-btn {
  width: 100%;
  font-weight: bold;
}
</style>
