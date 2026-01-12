<script setup lang="ts">
import AlbumCard from '@/components/AlbumCard.vue'
import type { NewReleaseAlbumItem } from '@/types'

const props = withDefaults(
  defineProps<{
    items: NewReleaseAlbumItem[]
    busyAlbumId?: string | null
  }>(),
  {
    busyAlbumId: null
  }
)

const emit = defineEmits<{
  (e: 'select', item: NewReleaseAlbumItem): void
}>()
</script>

<template>
  <div class="list" role="list">
    <AlbumCard
      v-for="item in props.items"
      :key="item.albumId"
      :item="item"
      :busy="props.busyAlbumId === item.albumId"
      @select="() => emit('select', item)"
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
