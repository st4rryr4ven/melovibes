<script setup lang="ts">
import { computed } from 'vue'
import type { AlbumTrackItem, MusicSearchItem } from '@/types'

const props = withDefaults(
  defineProps<{
    item: AlbumTrackItem | MusicSearchItem
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
  return props.item.spotify.name
})

const artistsLabel = computed(() => {
  if ('source' in props.item) {
    const artists = props.item.source === 'local' ? props.item.local.artists : (props.item.spotify.artists ?? [])
    return artists.map((a) => a.name).filter(Boolean).join(', ') || 'Artiste inconnu'
  }
  return props.item.spotify.artists.map((a) => a.name).filter(Boolean).join(', ') || 'Artiste inconnu'
})

const picture = computed(() => {
  if ('source' in props.item) {
    if (props.item.source === 'local') return props.item.local.picture ?? null
    return props.item.spotify.album?.images?.[0]?.url ?? null
  }
  return props.item.spotify.albumPicture ?? null
})
</script>

<template>
  <button type="button" class="card" :disabled="props.busy" @click="emit('select')">
    <div class="card__media" v-if="picture">
      <img class="card__img" :src="picture" :alt="title" />
    </div>

    <div class="card__content">
      <div class="card__title">{{ title }}</div>
      <div class="card__subtitle">{{ artistsLabel }}</div>
    </div>

    <div class="card__chevron">›</div>
  </button>
</template>

<style scoped>
.card {
  width: 100%;
  display: grid;
  grid-template-columns: 56px 1fr auto;
  gap: 12px;
  align-items: center;
  text-align: left;
  padding: 12px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  background: white;
  cursor: pointer;
}

.card:hover {
  box-shadow: 0 6px 18px rgba(15, 23, 42, 0.12);
}

.card:disabled {
  cursor: not-allowed;
  opacity: 0.8;
}

.card__media {
  width: 56px;
  height: 56px;
}

.card__img {
  width: 56px;
  height: 56px;
  object-fit: cover;
  border-radius: 12px;
}

.card__title {
  font-weight: 800;
  line-height: 1.2;
}

.card__subtitle {
  font-size: 13px;
  opacity: 0.8;
  margin-top: 4px;
}

.card__chevron {
  font-size: 22px;
  opacity: 0.5;
  padding-left: 6px;
}
</style>
