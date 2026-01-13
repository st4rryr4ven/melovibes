<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import MusicList from '@/components/MusicList.vue'
import { albumApi } from '@/api/albumApi'
import { musicApi } from '@/api/musicApi'
import type { AlbumTrackItem, AlbumTracksResponse } from '@/types'
import { useFlashStore } from '@/stores/flashStore'

const route = useRoute()
const router = useRouter()
const flash = useFlashStore()

const albumId = computed(() => String(route.params.albumId ?? ''))

const loading = ref(false)
const error = ref<string | null>(null)
const data = ref<AlbumTracksResponse | null>(null)
const busySpotifyId = ref<string | null>(null)

const album = computed(() => data.value?.album ?? null)
const tracks = computed<AlbumTrackItem[]>(() => data.value?.tracks ?? [])
const artistsLabel = computed(() => album.value?.artists.map((a) => a.name).filter(Boolean).join(', ') ?? '')
const metaLine = computed(() => {
  if (!album.value) return ''
  const parts: string[] = []
  if (album.value.releaseDate) parts.push(album.value.releaseDate)
  if (typeof album.value.totalTracks === 'number') {
    const n = album.value.totalTracks
    parts.push(`${n} titre${n > 1 ? 's' : ''}`)
  }
  return parts.join(' • ')
})

function errorMessage(err: unknown, fallback: string): string {
  if (err instanceof Error && err.message) return err.message
  if (typeof err === 'string' && err) return err
  return fallback
}

function goBack() {
  router.back()
}

function goToMusicDetail(id: number) {
  router.push({ name: 'musicDetail', params: { id } })
}

async function loadAlbumTracks(): Promise<void> {
  const id = albumId.value
  if (!id) {
    error.value = 'Album introuvable.'
    data.value = null
    return
  }

  loading.value = true
  error.value = null

  try {
    data.value = await albumApi.getSpotifyAlbumTracks(id, 'FR')
  } catch (e) {
    error.value = errorMessage(e, "Erreur lors du chargement de l'album.")
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
    const created = await musicApi.importFromSpotify(spotifyId)

    const d = data.value
    if (d) {
      d.tracks = d.tracks.map((t) =>
        t.spotify.id !== spotifyId
          ? t
          : {
            ...t,
            local: { ...t.local, musicId: created.id, isImported: true }
          }
      )
      data.value = { ...d }
    }

    goToMusicDetail(created.id)
  } catch (e) {
    const msg = errorMessage(e, 'Action impossible.')
    flash.error(msg)
  } finally {
    busySpotifyId.value = null
  }
}

onMounted(loadAlbumTracks)
watch(albumId, loadAlbumTracks)
</script>

<template>
  <div class="page">
    <div class="top">
      <button class="btn btn--ghost" type="button" @click="goBack">← Retour</button>
    </div>

    <div v-if="loading" class="panel state">
      <div class="muted">Chargement…</div>
      <div class="skeleton" />
    </div>

    <div v-else-if="error" class="panel state errorBox">
      <div class="errorBox__title">Chargement impossible</div>
      <div class="errorBox__text">{{ error }}</div>
      <button class="btn btn--ghost" type="button" @click="loadAlbumTracks">Réessayer</button>
    </div>

    <section v-else-if="album" class="layout">
      <div class="posterWrap">
        <div class="poster">
          <img v-if="album.picture" class="poster__img" :src="album.picture" :alt="album.name" />
          <div v-else class="poster__placeholder" aria-hidden="true">♪</div>
          <div class="poster__shade" aria-hidden="true" />
          <div class="poster__glow" aria-hidden="true" />

          <div class="poster__content">
            <div class="poster__title" :title="album.name">{{ album.name }}</div>
            <div v-if="artistsLabel" class="poster__subtitle" :title="artistsLabel">{{ artistsLabel }}</div>
            <div v-if="metaLine" class="poster__meta">{{ metaLine }}</div>
          </div>
        </div>
      </div>

      <div class="listCard card">
        <div class="listCard__head">
          <div class="listCard__title">Titres</div>
          <div class="listCard__count muted">{{ tracks.length }}</div>
        </div>
        <div class="divider" />

        <div class="listCard__body">
          <MusicList
            v-if="tracks.length"
            mode="album"
            :items="tracks"
            :busy-spotify-id="busySpotifyId"
            @select-album="onSelectAlbumTrack"
          />
          <div v-else class="muted empty">Aucun titre.</div>
        </div>
      </div>
    </section>

    <div v-else class="panel state">
      <div class="muted">Album introuvable.</div>
    </div>
  </div>
</template>

<style scoped>
.page {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.top {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.layout {
  display: grid;
  grid-template-columns: minmax(220px, 300px) 1fr;
  gap: 14px;
  align-items: start;
}

.posterWrap {
  position: sticky;
  top: 75px;
}

.poster {
  position: relative;
  border-radius: 18px;
  overflow: hidden;
  border: 1px solid var(--c-border);
  background: #0b121a;
  box-shadow: var(--shadow-1);
  aspect-ratio: 2 / 3;
}

.poster__img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  transform: scale(1.02);
}

.poster__placeholder {
  position: absolute;
  inset: 0;
  display: grid;
  place-items: center;
  font-weight: 900;
  font-size: 40px;
  color: rgba(245, 248, 252, 0.78);
  background: radial-gradient(160px 160px at 25% 20%, rgba(29, 185, 84, 0.22), transparent 60%),
  radial-gradient(220px 220px at 90% 85%, rgba(17, 217, 138, 0.16), transparent 60%),
  #0b121a;
}

.poster__shade {
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(0, 0, 0, 0.08) 0%, rgba(0, 0, 0, 0.18) 35%, rgba(0, 0, 0, 0.9) 100%);
}

.poster__glow {
  position: absolute;
  inset: -2px;
  background: radial-gradient(520px 320px at 20% 20%, rgba(29, 185, 84, 0.18), transparent 55%),
  radial-gradient(420px 300px at 80% 85%, rgba(17, 217, 138, 0.14), transparent 55%);
  opacity: 0.9;
  mix-blend-mode: screen;
  pointer-events: none;
}

.poster__content {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  padding: 14px;
  display: grid;
  gap: 8px;
}

.poster__title {
  font-weight: 980;
  letter-spacing: 0.2px;
  line-height: 1.05;
  color: rgba(245, 248, 252, 0.98);
  text-shadow: 0 2px 14px rgba(0, 0, 0, 0.6);
  font-size: 20px;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.poster__subtitle {
  font-size: 14px;
  color: rgba(245, 248, 252, 0.78);
  text-shadow: 0 2px 14px rgba(0, 0, 0, 0.6);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.poster__meta {
  font-size: 12px;
  color: rgba(245, 248, 252, 0.62);
  text-shadow: 0 2px 14px rgba(0, 0, 0, 0.6);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.listCard {
  padding: 12px;
}

.listCard__head {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 10px;
  padding-bottom: 10px;
}

.listCard__title {
  font-weight: 900;
  letter-spacing: 0.2px;
}

.listCard__count {
  font-weight: 750;
  font-size: 13px;
}

.listCard__body {
  padding-top: 12px;
}

.state {
  padding: 12px;
  display: grid;
  gap: 10px;
}

.skeleton {
  height: 80px;
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

.errorBox {
  border-color: rgba(255, 77, 79, 0.45);
  background: #3a1a1e;
}

.errorBox__title {
  font-weight: 900;
  letter-spacing: 0.2px;
}

.errorBox__text {
  color: rgba(255, 245, 245, 0.92);
  font-size: 14px;
}

.empty {
  padding: 6px 2px;
}

.muted {
  color: var(--c-text-mute);
  font-weight: 650;
}

@media (max-width: 900px) {
  .layout {
    grid-template-columns: 1fr;
  }

  .posterWrap {
    position: static;
  }

  .poster {
    max-width: 420px;
  }
}
</style>
