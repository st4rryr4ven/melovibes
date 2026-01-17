<script setup lang="ts">
import {computed} from 'vue'
import MusicCard from '@/components/views/music/MusicCard.vue'
import type {AlbumTrackItem, Music, MusicSearchItem} from '@/types.ts'
import {useStoreAuthentification} from '@/stores/storeAuthentification.ts'

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
  | {
  mode: 'local';
  items: Music[];
  busySpotifyId?: string | null
}

const props = withDefaults(defineProps<Props>(), {
  busySpotifyId: null
})

const emit = defineEmits<{
  (e: 'selectSearch', item: MusicSearchItem): void
  (e: 'selectAlbum', item: AlbumTrackItem): void
  (e: 'select', id: number): void
}>()

const authStore = useStoreAuthentification()

function keyOf(item: any): string {
  if (props.mode === 'local') {
    return `db-${item.id}`
  }

  if ('source' in item) {
    return item.source === 'local' ? `local-${item.local.musicId}` : `spotify-${item.spotify.id}`
  }

  return `album-${item.spotify.id}`
}

function spotifyIdOf(item: any): string | null {
  if (props.mode === 'local') return item.spotifyId || null
  if ('source' in item) {
    return item.source === 'spotify' ? item.spotify.id : null
  }
  return item.spotify.id
}

const visibleItems = computed(() => {
  if (props.mode === 'local') return props.items

  if (props.mode !== 'search') return props.items
  if (authStore.estAdmin) return props.items

  return (props.items as MusicSearchItem[]).filter((it) => {
    if (it.source === 'local') return it.local.isValidated
    return true;
  })
})

function isBusy(item: MusicSearchItem | AlbumTrackItem): boolean {
  const id = spotifyIdOf(item)
  return !!id && id === props.busySpotifyId
}

function onSelect(item: any): void {
  if (props.mode === 'local') {
    emit('select', item.id)
    return
  }

  if (props.mode === 'search') {
    emit('selectSearch', item as MusicSearchItem)
    return
  }

  emit('selectAlbum', item as AlbumTrackItem)
}

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
