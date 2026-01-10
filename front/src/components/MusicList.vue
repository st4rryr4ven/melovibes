<script setup lang="ts">
import MusicCard from '@/components/MusicCard.vue'
import type { MusicUiItem } from '@/types'

const props = withDefaults(
  defineProps<{
    items: MusicUiItem[]
    importingKey?: string | null
  }>(),
  {
    importingKey: null
  }
)

const emit = defineEmits<{
  (e: 'import', spotifyTrackId: string, itemKey: string): void
}>()

function onImport(spotifyTrackId: string, itemKey: string) {
  emit('import', spotifyTrackId, itemKey)
}
</script>

<template>
  <div class="list">
    <MusicCard
      v-for="item in props.items"
      :key="item.key"
      :item="item"
      :importing="props.importingKey === item.key"
      @import="(spotifyTrackId) => onImport(spotifyTrackId, item.key)"
    />
  </div>
</template>

<style scoped>
.list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
</style>
