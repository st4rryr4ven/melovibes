<script setup lang="ts">
import { computed } from 'vue'
import MusicCard from '@/components/MusicCard.vue'
import type { AlbumTrackItem, MusicSearchItem } from '@/types'
import { useStoreAuthentification } from '@/stores/storeAuthentification'

type Props =
  | {
  mode: 'search'
  items: MusicSearchItem[]
  busySpotifyId?: string | null
}
  | {
  mode: 'album'
  items: AlbumTrackItem[]
  busySpotifyId?: string | null
}

const props = withDefaults(defineProps<Props>(), {
  busySpotifyId: null
})

const emit = defineEmits<{
  (e: 'selectSearch', item: MusicSearchItem): void
  (e: 'selectAlbum', item: AlbumTrackItem): void
}>()

const authStore = useStoreAuthentification()

function keyOf(item: MusicSearchItem | AlbumTrackItem): string {
  if ('source' in item) {
    return item.source === 'local' ? `local-${item.local.id}` : `spotify-${item.spotify.id}`
  }
  return `album-${item.spotify.id}`
}

function spotifyIdOf(item: MusicSearchItem | AlbumTrackItem): string | null {
  if ('source' in item) {
    return item.source === 'spotify' ? item.spotify.id : null
  }
  return item.spotify.id
}

function isBusy(item: MusicSearchItem | AlbumTrackItem): boolean {
  const id = spotifyIdOf(item)
  return !!id && id === props.busySpotifyId
}

function onSelect(item: MusicSearchItem | AlbumTrackItem): void {
  if (props.mode === 'search') {
    emit('selectSearch', item as MusicSearchItem)
    return
  }
  emit('selectAlbum', item as AlbumTrackItem)
}

const visibleItems = computed(() => {
  if (props.mode !== 'search') return props.items

  if (authStore.estAdmin) return props.items

  return (props.items as MusicSearchItem[]).filter((it) => {
    if (it.source === 'local') return it.local.isValidated
    return true;
  })
})
</script>

<template>
  <div class="list" role="list">
    <MusicCard
      v-for="item in visibleItems"
      :key="keyOf(item as any)"
      :item="item"
      :busy="isBusy(item as any)"
      @select="onSelect(item as any)"
    />
  </div>
</template>

<style scoped>
.list {
  display: grid;
  grid-template-columns: 1fr;
  gap: 12px;
  padding-bottom: 16px;
}
</style>
