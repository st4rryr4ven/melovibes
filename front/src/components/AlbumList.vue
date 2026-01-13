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
  <div class="grid" role="list">
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
.grid {
  display: grid;
  gap: 14px;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  padding-bottom: 16px;
}

@media (max-width: 520px) {
  .grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
</style>
