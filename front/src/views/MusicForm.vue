<script setup lang="ts">
import {ref, reactive, watch} from 'vue'
import {searchArtists, createArtist} from '@/api/artistApi'
import {importSpotifyTrack, searchMusic} from '@/api/musicApi'
import type {Artist, ImportedMusic} from '@/types'

// Form state
const form = reactive({
  title: '',
  spotifyTrackId: '',
  artists: [] as Artist[],
  selectedArtist: null as Artist | null,
})

// Autocomplete search state
const artistQuery = ref('')
const artistResults = ref<Artist[]>([])
const artistLoading = ref(false)

watch(artistQuery, async (q) => {
  if (!q) {
    artistResults.value = []
    return
  }
  artistLoading.value = true
  artistResults.value = (await searchArtists({q, limit: 5})).items
  artistLoading.value = false
})

// Import from Spotify
const importing = ref(false)

async function onImportSpotify() {
  if (!form.spotifyTrackId) return
  importing.value = true

  try {
    const imported: ImportedMusic = await importSpotifyTrack(form.spotifyTrackId)
    form.title = imported.title
    form.artists = imported.artists
  } catch (err: any) {
    alert(err.message || 'Failed to import track from Spotify')
  } finally {
    importing.value = false
  }
}

// Manual artist creation
async function addNewArtist(name: string) {
  if (!name) return
  const newArtist = await createArtist({name})
  form.artists.push(newArtist)
  form.selectedArtist = newArtist
}
</script>

<template>
  <form @submit.prevent="console.log(form)">
    <h2>Create Music</h2>

    <!-- Spotify Import -->
    <div>
      <label>Spotify Track ID or URL</label>
      <input v-model="form.spotifyTrackId" type="text" placeholder="Spotify track ID or URL"/>
      <button type="button" @click="onImportSpotify" :disabled="importing">
        {{ importing ? 'Importing...' : 'Import from Spotify' }}
      </button>
    </div>

    <hr/>

    <!-- Manual creation -->
    <div>
      <label>Title</label>
      <input v-model="form.title" type="text" placeholder="Music title"/>
    </div>

    <div class="artist-field">
      <label>Artist</label>
      <input
        v-model="artistQuery"
        type="text"
        placeholder="Search for artist..."
      />
      <div v-if="artistLoading">Searching...</div>
      <ul v-if="artistResults.length">
        <li
          v-for="artist in artistResults"
          :key="artist.id"
          @click="form.selectedArtist = artist; form.artists = [artist]; artistQuery = artist.name"
        >
          {{ artist.name }}
        </li>
      </ul>

      <button
        type="button"
        @click="addNewArtist(artistQuery)"
      >
        Create New Artist "{{ artistQuery }}"
      </button>
    </div>

    <hr/>

    <div>
      <button type="submit">Save Music</button>
    </div>
  </form>
</template>

<style scoped>
.music-form {
  max-width: 600px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.artist-field ul {
  background: white;
  border: 1px solid #cbd5e1;
  margin-top: 0;
  padding: 0;
  list-style: none;
  position: absolute;
  z-index: 10;
  width: 200px;
}

.artist-field li {
  padding: 4px 8px;
  cursor: pointer;
}

.artist-field li:hover {
  background-color: #e2e8f0;
}

button {
  cursor: pointer;
}
</style>
