<script setup lang="ts">
import {ref, reactive, watch} from 'vue'
import {searchArtists, createArtist, importSpotifyArtist} from '@/api/artistApi'
import {importSpotifyTrack, createMusic} from '@/api/musicApi'
import type {Artist, ImportedMusic, Music} from '@/types'

const form = reactive({
  title: '',
  spotifyTrackId: '',
  artists: [] as Artist[],
  selectedArtist: null as Artist | null,
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
  if (!artist?.spotify) return
  form.selectedArtist = {
    id: artist.spotify.id,
    spotifyId: artist.spotify.id,
    name: artist.spotify.name,
    images: artist.spotify.images,
  }
  artistQuery.value = artist.spotify.name
  artistResults.value = [] // hide dropdown
}

// Create a new artist manually
async function addNewArtist(name: string) {
  if (!name) return
  const newArtist = await createArtist({name})
  form.artists.push(newArtist)
  form.selectedArtist = {
    id: newArtist.id,
    spotifyId: newArtist.spotifyId, // may be undefined if created manually
    name: newArtist.name,
    images: [],
  }
  artistQuery.value = newArtist.name
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
    let dbArtistId: number | null = null

    // 1️⃣ Handle artist
    if (form.selectedArtist) {
      // Try to find artist in DB by Spotify ID
      try {
        const artistDb = await apiJson<{
          id: number
        }>(`artists/spotify/${form.selectedArtist.spotifyId}`)
        dbArtistId = artistDb.id
      } catch {
        // Artist not found → import from Spotify
        const imported = await importSpotifyArtist(form.selectedArtist.spotifyId!)
        dbArtistId = imported.id
        form.selectedArtist.id = dbArtistId
      }
    } else {
      alert('Veuillez sélectionner ou créer un artiste.')
      return
    }

    // 2️⃣ Check if music exists locally
    let existingMusic = await apiJson<{
      exists: boolean
    }>(`music/search?title=${encodeURIComponent(form.title)}&artistId=${dbArtistId}`)
    if (existingMusic.exists) {
      alert(`La musique "${form.title}" existe déjà dans la base de données.`)
      resetForm()
      return
    }

    // 3️⃣ Try to import music from Spotify by title
    let importedTrack = null
    try {
      importedTrack = await importSpotifyTrack(form.title)
    } catch {
      importedTrack = null
    }

    if (importedTrack) {
      alert(`La musique "${importedTrack.title}" a été importée depuis Spotify !`)
      resetForm()
      return
    }

    // 4️⃣ Music not found anywhere → create manually
    await createMusic({
      title: form.title,
      artistId: dbArtistId,
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
  form.artists = []
  form.selectedArtist = null
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
        <label>Artiste</label>
        <input v-model="artistQuery" type="text" placeholder="Rechercher un artiste..."/>
        <div v-if="artistLoading" class="status">Recherche en cours...</div>

        <ul v-if="artistResults.length">
          <li v-for="artist in artistResults" :key="artist.spotify.id"
              @click="selectArtist(artist)">
            <img :src="artist.spotify.images[2]?.url || ''" alt="" class="artist-avatar"/>
            <span class="artist-name" v-html="highlightQuery(artist.spotify.name)"></span>
            <span class="artist-popularity">⭐ {{ artist.spotify.popularity }}</span>
          </li>
        </ul>

        <button
          v-if="artistResults.length === 0 && artistQuery"
          type="button"
          @click="addNewArtist(artistQuery)"
        >
          Créer l’artiste "{{ artistQuery }}"
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

.artist-field ul {
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
