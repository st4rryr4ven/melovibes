<script setup lang="ts">
import type { MusicUiItem } from '@/types'

const props = withDefaults(
  defineProps<{
    item: MusicUiItem
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
      <img class="card__img" :src="props.item.picture ?? ''" :alt="props.item.title" />
    </div>

    <div class="card__content">
      <div class="card__title">
        {{ props.item.title }}
      </div>
      <div class="card__subtitle">{{ props.item.artistsLabel }}</div>
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
