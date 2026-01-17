<script setup lang="ts">
import {computed} from 'vue'
import type {AlbumTrackItem, MusicSearchItem, Music} from '@/types.ts'

const props = withDefaults(
  defineProps<{
    item: AlbumTrackItem | MusicSearchItem | Music
    busy?: boolean
  }>(),
  {
    busy: false
  }
)

const emit = defineEmits<{
  (e: 'select'): void
}>()

const title = computed(() => {
  if ('source' in props.item) {
    return props.item.source === 'local' ? props.item.local.title : props.item.spotify.name
  }
  if ('title' in props.item) {
    return props.item.title
  }
  return props.item.spotify.name
})

const artistsLabel = computed(() => {
  let artists: any[] = []

  if ('spotify' in props.item && props.item.spotify) {
    artists = props.item.spotify.artists || []
  } else if ('artists' in props.item) {
    artists = props.item.artists || []
  }

  return artists.map((a) => a.name).filter(Boolean).join(', ') || 'Artiste inconnu'
})

const picture = computed(() => {
  const item = props.item as any;

  if (item.picture) return item.picture;

  if (item.source === 'spotify' && item.spotify?.album?.images?.[0]?.url) {
    return item.spotify.album.images[0].url;
  }

  if (item.source === 'local' && item.local?.picture) {
    return item.local.picture;
  }

  if (item.spotify) {
    return item.spotify.albumPicture || item.spotify.album?.images?.[0]?.url || null;
  }

  return null;
});
</script>

<template>
  <button type="button" class="row" :disabled="props.busy" @click="emit('select')">
    <div class="row__media" :class="{ 'row__media--empty': !picture }">
      <img v-if="picture" class="row__img" :src="picture" :alt="title"/>
      <div v-else class="row__placeholder" aria-hidden="true">♪</div>
    </div>

    <div class="row__content">
      <div class="row__title">{{ title }}</div>
      <div class="row__subtitle">{{ artistsLabel }}</div>
    </div>

    <div class="row__right" aria-hidden="true">
      <span v-if="props.busy" class="spinner"/>
      <span v-else class="chev">›</span>
    </div>
  </button>
</template>

<style scoped>
.row {
  width: 100%;
  display: grid;
  grid-template-columns: 58px 1fr auto;
  gap: 12px;
  align-items: center;
  text-align: left;
  padding: 12px 12px;
  border: 1px solid var(--c-border);
  border-radius: 16px;
  background: var(--c-surface-2);
  cursor: pointer;
  transition: transform 0.12s ease, border-color 0.12s ease, background 0.12s ease;
}

.row:hover {
  transform: translateY(-1px);
  border-color: var(--c-border-2);
  background: #193024;
}

.row:disabled {
  cursor: not-allowed;
  opacity: 0.78;
  transform: none;
}

.row__media {
  width: 58px;
  height: 58px;
  border-radius: 14px;
  overflow: hidden;
  border: 1px solid var(--c-border);
  background: #0b121a;
}

.row__img {
  width: 58px;
  height: 58px;
  object-fit: cover;
}

.row__placeholder {
  width: 100%;
  height: 100%;
  display: grid;
  place-items: center;
  font-weight: 900;
  color: rgba(245, 248, 252, 0.75);
  background: radial-gradient(60px 60px at 30% 20%, rgba(29, 185, 84, 0.22), transparent 60%),
  radial-gradient(80px 80px at 90% 80%, rgba(17, 217, 138, 0.16), transparent 60%),
  #0b121a;
}

.row__content {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.row__title {
  font-weight: 900;
  letter-spacing: 0.2px;
  line-height: 1.15;
  color: var(--c-text);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.row__subtitle {
  font-size: 13px;
  color: var(--c-text-mute);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.row__right {
  display: grid;
  place-items: center;
  width: 28px;
}

.chev {
  font-size: 22px;
  color: rgba(245, 248, 252, 0.65);
}

.spinner {
  width: 16px;
  height: 16px;
  border-radius: 999px;
  border: 2px solid rgba(245, 248, 252, 0.22);
  border-top-color: rgba(29, 185, 84, 0.95);
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
