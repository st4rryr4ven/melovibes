<script setup lang="ts">
import AlbumCard from '@/components/AlbumCard.vue'
import type { AlbumUiItem } from '@/types'

const props = withDefaults(
  defineProps<{
    items: AlbumUiItem[]
    busyAlbumId?: string | null
  }>(),
  {
    busyAlbumId: null
  }
)

const emit = defineEmits<{
  (e: 'select', item: AlbumUiItem): void
}>()
</script>

<template>
  <div class="list" role="list">
    <AlbumCard
      v-for="item in props.items"
      :key="item.key"
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
