<script setup lang="ts">
import {ref, reactive, watch} from 'vue'
import {searchArtists, createArtist, importSpotifyArtist} from '@/api/artistApi'
import {importSpotifyTrack, createMusic} from '@/api/musicApi'
import {apiJson} from '@/api/httpClient'
import {API_URL} from '@/util/apiStore'
import type {Artist} from '@/types/artist'
import type {ImportedMusic} from '@/types/music'
import type {Music} from '@/types'

const form = reactive({
  title: '',
  spotifyTrackId: '',
  selectedArtists: [] as Artist[],
})

const artistQuery = ref('')
const artistResults = ref<any[]>([])
const artistLoading = ref(false)
const importing = ref(false)

// Watch artist search query
watch(artistQuery, async (q) => {
  if (!q) {
    artistResults.value = []
    return
  }
  artistLoading.value = true
  try {
    artistResults.value = (await searchArtists({q, limit: 5})).items
  } catch {
    artistResults.value = []
  } finally {
    artistLoading.value = false
  }
})

// Highlight matches in search results
function highlightQuery(name: string) {
  if (!artistQuery.value) return name
  const query = artistQuery.value.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&')
  const regex = new RegExp(`(${query})`, 'gi')
  return name.replace(regex, `<mark>$1</mark>`)
}

// Select an artist from search results
function selectArtist(artist: any) {
  let selectedArtist: Artist | null = null

  // Handle Spotify artist
  if (artist?.spotify) {
    selectedArtist = {
      id: artist.local?.artistId || null,
      spotifyId: artist.spotify.id,
      name: artist.spotify.name,
    }
  }
  // Handle local artist
  else if (artist?.local) {
    selectedArtist = {
      id: artist.local.artistId,
      spotifyId: artist.local.spotifyId,
      name: artist.local.name,
    }
  }

  if (selectedArtist) {
    // Check if artist is already selected (by spotifyId or id)
    const alreadySelected = form.selectedArtists.some((a: Artist) =>
      (a.spotifyId && selectedArtist!.spotifyId && a.spotifyId === selectedArtist!.spotifyId) ||
      (a.id && selectedArtist!.id && a.id === selectedArtist!.id)
    )

    if (!alreadySelected) {
      form.selectedArtists.push(selectedArtist)
    }
    artistQuery.value = ''
    artistResults.value = [] // hide dropdown
  }
}

// Remove an artist from selection
function removeArtist(index: number) {
  form.selectedArtists.splice(index, 1)
}

// Create a new artist manually
async function addNewArtist(name: string) {
  if (!name) return
  const newArtist = await createArtist({name})

  // Check if artist is already selected
  const alreadySelected = form.selectedArtists.some((a: Artist) =>
    a.id === newArtist.id || (a.name === newArtist.name && !a.id)
  )

  if (!alreadySelected) {
    form.selectedArtists.push(newArtist)
  }
  artistQuery.value = ''
  artistResults.value = []
}

// Import Spotify track (button)
async function onImportSpotify() {
  if (!form.spotifyTrackId) return
  importing.value = true
  try {
    const imported: ImportedMusic = await importSpotifyTrack(form.spotifyTrackId)
    alert(`La musique "${imported.title}" a été importée avec succès !`)
  } catch (err: any) {
    alert(err.message || 'Échec de l’importation depuis Spotify')
  } finally {
    importing.value = false
  }
}

// Save music logic
async function saveMusic() {
  if (!form.title) {
    alert('Veuillez entrer un titre.')
    return
  }

  importing.value = true
  try {
    // 1️⃣ Handle artists - ensure all have DB IDs
    if (form.selectedArtists.length === 0) {
      alert('Veuillez sélectionner au moins un artiste.')
      return
    }

    const dbArtistIds: number[] = []

    for (const artist of form.selectedArtists) {
      let dbArtistId: number | null = null

      // If artist already has a DB ID (from local search), use it
      if (artist.id) {
        dbArtistId = artist.id
      }
      // If artist has Spotify ID but no DB ID, import from Spotify
      else if (artist.spotifyId) {
        try {
          const imported = await importSpotifyArtist(artist.spotifyId)
          dbArtistId = imported.id
          artist.id = imported.id // imported.id is always a number
        } catch (err: any) {
          alert(err.message || `Erreur lors de l'importation de l'artiste "${artist.name}" depuis Spotify`)
          return
        }
      } else {
        alert(`Erreur: l'artiste "${artist.name}" n'a pas d'identifiant valide.`)
        return
      }

      // dbArtistId should always be a number at this point
      if (dbArtistId !== null && dbArtistId !== undefined) {
        dbArtistIds.push(dbArtistId)
      }
    }

    // 2️⃣ Check if music exists locally and search Spotify for matching track
    let importedTrack = null
    try {
      const searchResults = await apiJson<{
        items: Array<{
          source: 'local' | 'spotify'
          local?: {
            musicId: number
            title: string
            artists: Array<{ id: number }>
          }
          spotify?: {
            id: string
            name: string
            artists: Array<{ id: string; name: string }>
          }
        }>
      }>(`music/search?q=${encodeURIComponent(form.title)}&limit=20`)

      // First check if it exists locally (match title and at least one artist)
      const localMatch = searchResults.items.find(item => {
        if (item.source !== 'local' || !item.local) return false
        const titleMatch = item.local.title.toLowerCase() === form.title.toLowerCase()
        const artistMatch = item.local.artists?.some((a: { id: number }) => dbArtistIds.includes(a.id)) ?? false
        return titleMatch && artistMatch
      })

      if (localMatch?.local) {
        alert(`La musique "${form.title}" existe déjà dans la base de données.`)
        resetForm()
        return
      }

      // Then check Spotify results for a matching track (match title and at least one artist)
      const spotifyArtistIds = form.selectedArtists
        .map((a: Artist) => a.spotifyId)
        .filter((id: string | null | undefined): id is string => id !== null && id !== undefined)

      if (spotifyArtistIds.length > 0) {
        const spotifyMatch = searchResults.items.find(item => {
          if (item.source !== 'spotify' || !item.spotify) return false
          const titleMatch = item.spotify.name.toLowerCase() === form.title.toLowerCase()
          const artistMatch = item.spotify.artists?.some(a => spotifyArtistIds.includes(a.id)) ?? false
          return titleMatch && artistMatch
        })

        if (spotifyMatch?.spotify?.id) {
          // Import the matching track
          importedTrack = await importSpotifyTrack(spotifyMatch.spotify.id)
        }
      }
    } catch {
      // If search or import fails, continue to manual creation
      importedTrack = null
    }

    if (importedTrack) {
      alert(`La musique "${importedTrack.title}" a été importée depuis Spotify !`)
      resetForm()
      return
    }

    // 4️⃣ Music not found anywhere → create manually
    // API Platform expects IRI references (e.g., /api/artists/1)
    // Construct the IRI path from API_URL
    const basePath = API_URL.replace(/^https?:\/\/[^\/]+/, '') // Get path part (e.g., /api/)
    const artistIris = dbArtistIds.map(id =>
      `${basePath}artists/${id}`.replace(/([^:])\/\/+/g, '$1/') // Normalize slashes
    )

    await createMusic({
      title: form.title,
      artists: artistIris,
      spotifyTrackId: importedTrack?.id ?? null,
    })
    alert(`Musique "${form.title}" créée avec succès !`)
    resetForm()

  } catch (err: any) {
    alert(err.message || 'Erreur lors de la création de la musique')
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

</script>

<template>
  <div class="content-box music-form">
    <form @submit.prevent="saveMusic">
      <h2 class="form-title">Créer une musique</h2>

      <div class="form-group">
        <label>ID ou URL de la musique Spotify</label>
        <div class="input-group">
          <input
            v-model="form.spotifyTrackId"
            type="text"
            placeholder="ID ou URL de la musique Spotify"
          />
          <button type="button" @click="onImportSpotify" :disabled="importing">
            {{ importing ? 'Importation...' : 'Importer depuis Spotify' }}
          </button>
        </div>
      </div>

      <hr/>

      <div class="form-group">
        <label class="title-label">Titre</label>
        <input v-model="form.title" type="text" placeholder="Titre de la musique"/>
      </div>

      <div class="form-group artist-field">
        <label>Artistes</label>

        <!-- Selected artists -->
        <div v-if="form.selectedArtists.length > 0" class="selected-artists">
          <div
            v-for="(artist, index) in form.selectedArtists"
            :key="artist.id || artist.spotifyId || artist.name"
            class="selected-artist"
          >
            <span class="artist-name">{{ artist.name }}</span>
            <button
              type="button"
              @click="removeArtist(index)"
              class="remove-artist"
              title="Retirer cet artiste"
            >
              ×
            </button>
          </div>
        </div>

        <input v-model="artistQuery" type="text" placeholder="Rechercher un artiste..."/>
        <div v-if="artistLoading" class="status">Recherche en cours...</div>

        <ul v-if="artistResults.length" class="artist-results">
          <li v-for="artist in artistResults"
              :key="artist.spotify?.id || artist.local?.artistId || artist.local?.name"
              @click="selectArtist(artist)">
            <img :src="artist.spotify?.images?.[2]?.url || ''" alt="" class="artist-avatar"/>
            <span class="artist-name" v-html="highlightQuery(artist.spotify?.name || artist.local?.name || '')"></span>
            <span v-if="artist.spotify?.popularity" class="artist-popularity">⭐ {{ artist.spotify.popularity }}</span>
          </li>
        </ul>

        <button
          v-if="artistResults.length === 0 && artistQuery"
          type="button"
          @click="addNewArtist(artistQuery)"
        >
          Créer l'artiste "{{ artistQuery }}"
        </button>
      </div>

      <hr/>

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

.artist-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  margin-right: 8px;
}

.artist-name {
  flex: 1;
}

.artist-popularity {
  font-size: 12px;
  color: #555;
  margin-left: 8px;
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
