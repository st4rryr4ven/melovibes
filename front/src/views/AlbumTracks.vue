<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import MusicList from '@/components/MusicList.vue'
import { getSpotifyAlbumTracks } from '@/api/albumApi'
import { importSpotifyTrack } from '@/api/musicApi'
import type { AlbumTracksResponse, MusicUiItem } from '@/types'

const route = useRoute()
const router = useRouter()

const albumId = String(route.params.albumId ?? '')

const loading = ref(false)
const error = ref<string | null>(null)
const data = ref<AlbumTracksResponse | null>(null)

const busyKey = ref<string | null>(null)

const album = computed(() => data.value?.album ?? null)

const tracks = computed<MusicUiItem[]>(() => {
  const d = data.value
  if (!d) return []

  return d.tracks.map((t) => ({
    key: `album-${albumId}-${t.spotify.id}`,
    source: 'album',
    musicId: t.local.musicId,
    spotifyTrackId: t.spotify.id,
    title: t.spotify.name,
    artistsLabel: t.spotify.artists.map((a) => a.name).join(', ') || 'Artiste inconnu',
    picture: t.local.isImported ? (d.album.picture ?? t.spotify.albumPicture) : (d.album.picture ?? t.spotify.albumPicture),
    link: t.spotify.external_urls?.spotify ?? null,
    isImported: t.local.isImported
  }))
})

function goToMusicDetail(id: number) {
  router.push({ name: 'musicDetail', params: { id } })
}

async function onSelectTrack(item: MusicUiItem) {
  if (busyKey.value) return

  if (typeof item.musicId === 'number' && !Number.isNaN(item.musicId)) {
    goToMusicDetail(item.musicId)
    return
  }

  if (!item.spotifyTrackId) return

  busyKey.value = item.key
  error.value = null

  try {
    const imported = await importSpotifyTrack(item.spotifyTrackId, 'FR')
    const d = data.value
    if (d) {
      d.tracks = d.tracks.map((t) =>
        t.spotify.id !== item.spotifyTrackId
          ? t
          : {
            ...t,
            local: { isImported: true, musicId: imported.musicId }
          }
      )
      data.value = { ...d }
    }
    goToMusicDetail(imported.musicId)
  } catch (e: any) {
    error.value = e?.message ?? "Erreur lors de l'import"
  } finally {
    busyKey.value = null
  }
}

onMounted(async () => {
  if (!albumId) {
    error.value = 'Album invalide'
    return
  }

  loading.value = true
  error.value = null

  try {
    data.value = await getSpotifyAlbumTracks(albumId, 'FR')
  } catch (e: any) {
    error.value = e?.message ?? "Erreur lors du chargement de l'album"
  } finally {
    loading.value = false
  }
})
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

    <MusicList v-if="tracks.length" :items="tracks" :busy-key="busyKey" @select="onSelectTrack" />

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
