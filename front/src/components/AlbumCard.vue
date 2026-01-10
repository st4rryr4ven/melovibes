<script setup lang="ts">
import type { AlbumUiItem } from '@/types'

const props = withDefaults(
  defineProps<{
    item: AlbumUiItem
    busy?: boolean
  }>(),
  {
    busy: false
  }
)

const emit = defineEmits<{
  (e: 'select'): void
}>()
</script>

<template>
  <button type="button" class="card" :disabled="props.busy" @click="emit('select')">
    <div class="card__media">
      <img class="card__img" :src="props.item.picture ?? ''" :alt="props.item.name" />
    </div>

    <div class="card__content">
      <div class="card__title">{{ props.item.name }}</div>
      <div class="card__subtitle">{{ props.item.artistsLabel }}</div>

      <div class="card__meta">
        <span class="badge">Album</span>
        <span v-if="props.item.releaseDate" class="badge">{{ props.item.releaseDate }}</span>
        <span v-if="props.item.totalTracks != null" class="badge">{{ props.item.totalTracks }} tracks</span>
      </div>
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

.card__meta {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-top: 8px;
}

.badge {
  font-size: 12px;
  padding: 2px 10px;
  border: 1px solid #cbd5e1;
  border-radius: 999px;
  opacity: 0.9;
}

.card__chevron {
  font-size: 22px;
  opacity: 0.5;
  padding-left: 6px;
}
</style>
