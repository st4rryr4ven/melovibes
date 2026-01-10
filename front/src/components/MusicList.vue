<script setup lang="ts">
import MusicCard from '@/components/MusicCard.vue'
import type { MusicUiItem } from '@/types'

const props = withDefaults(
  defineProps<{
    items: MusicUiItem[]
    busyKey?: string | null
  }>(),
  {
    busyKey: null
  }
)

const emit = defineEmits<{
  (e: 'select', item: MusicUiItem): void
}>()
</script>

<template>
  <div class="list" role="list">
    <MusicCard
      v-for="item in props.items"
      :key="item.key"
      :item="item"
      :busy="props.busyKey === item.key"
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
