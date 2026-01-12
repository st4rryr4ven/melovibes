<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import MusicList from '@/components/MusicList.vue'
import { albumApi } from '@/api/albumApi'
import { musicApi } from '@/api/musicApi'
import type { AlbumTrackItem, AlbumTracksResponse } from '@/types'

const route = useRoute()
const router = useRouter()

const albumId = computed(() => String(route.params.albumId ?? ''))

const loading = ref(false)
const error = ref<string | null>(null)
const data = ref<AlbumTracksResponse | null>(null)

const busySpotifyId = ref<string | null>(null)

const album = computed(() => data.value?.album ?? null)
const tracks = computed<AlbumTrackItem[]>(() => data.value?.tracks ?? [])

function goToMusicDetail(id: number) {
  router.push({ name: 'musicDetail', params: { id } })
}

async function loadAlbumTracks(): Promise<void> {
  const id = albumId.value
  if (!id) {
    error.value = 'Album invalide'
    data.value = null
    return
  }

  loading.value = true
  error.value = null

  try {
    data.value = await albumApi.getSpotifyAlbumTracks(id, 'FR')
  } catch (e: any) {
    error.value = e?.message ?? "Erreur lors du chargement de l'album"
    data.value = null
  } finally {
    loading.value = false
  }
}

async function onSelectAlbumTrack(item: AlbumTrackItem) {
  if (busySpotifyId.value) return

  if (typeof item.local.musicId === 'number' && !Number.isNaN(item.local.musicId)) {
    goToMusicDetail(item.local.musicId)
    return
  }

  const spotifyId = item.spotify.id
  if (!spotifyId) return

  busySpotifyId.value = spotifyId
  error.value = null

  try {
    const imported = await musicApi.importFromSpotify(spotifyId)

    const d = data.value
    if (d) {
      d.tracks = d.tracks.map((t) =>
        t.spotify.id !== spotifyId
          ? t
          : {
            ...t,
            local: { isImported: true, musicId: imported.id }
          }
      )
      data.value = { ...d }
    }

    goToMusicDetail(imported.id)
  } catch (e: any) {
    error.value = e?.message ?? "Erreur lors de l'import"
  } finally {
    busySpotifyId.value = null
  }
}

onMounted(loadAlbumTracks)
watch(albumId, loadAlbumTracks)
</script>

<template>
  <div class="page">
    <div class="header" v-if="album">
      <img v-if="album.picture" class="cover" :src="album.picture" :alt="album.name" />
      <div class="info">
        <h2 class="title">{{ album.name }}</h2>
        <div class="subtitle">{{ album.artists.map((a) => a.name).join(', ') }}</div>
        <div class="meta">
          <span v-if="album.releaseDate" class="badge">{{ album.releaseDate }}</span>
          <span v-if="album.totalTracks != null" class="badge">{{ album.totalTracks }} tracks</span>
        </div>
      </div>
    </div>

    <div v-if="loading" class="muted">Chargement...</div>
    <div v-if="error" class="error">{{ error }}</div>

    <MusicList
      v-if="tracks.length"
      mode="album"
      :items="tracks"
      :busy-spotify-id="busySpotifyId"
      @select-album="onSelectAlbumTrack"
    />

    <div v-if="!loading && !error && tracks.length === 0" class="muted">Aucune musique.</div>
  </div>
</template>

<style scoped>
.page {
  padding: 10px 0;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.header {
  display: grid;
  grid-template-columns: 96px 1fr;
  gap: 12px;
  align-items: center;
  padding: 12px;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
}

.cover {
  width: 96px;
  height: 96px;
  object-fit: cover;
  border-radius: 14px;
}

.title {
  margin: 0;
  font-weight: 900;
}

.subtitle {
  font-size: 13px;
  opacity: 0.8;
  margin-top: 4px;
}

.meta {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-top: 10px;
}

.badge {
  font-size: 12px;
  padding: 2px 10px;
  border: 1px solid #cbd5e1;
  border-radius: 999px;
  opacity: 0.9;
}

.error {
  padding: 10px 12px;
  border: 1px solid #ef4444;
  border-radius: 12px;
  background: white;
}

.muted {
  opacity: 0.75;
}
</style>
