<script setup lang="ts">
import type { MusicUiItem } from '@/types'

const props = withDefaults(
  defineProps<{
    item: MusicUiItem
    importing?: boolean
  }>(),
  {
    importing: false
  }
)

const emit = defineEmits<{
  (e: 'import', spotifyTrackId: string): void
}>()

function onImport() {
  if (!props.item.spotifyTrackId) return
  emit('import', props.item.spotifyTrackId)
}

function openLink() {
  if (!props.item.link) return
  window.open(props.item.link, '_blank', 'noopener,noreferrer')
}

const canImport =
  props.item.source === 'search-spotify' &&
  !!props.item.spotifyTrackId &&
  props.item.isImported === false
</script>

<template>
  <div class="card">
    <div class="card__media" v-if="props.item.picture">
      <img class="card__img" :src="props.item.picture" :alt="props.item.title" />
    </div>

    <div class="card__content">
      <div class="card__title" @click="openLink" :class="{ clickable: !!props.item.link }">
        {{ props.item.title }}
      </div>
      <div class="card__subtitle">{{ props.item.artistsLabel }}</div>

      <div class="card__meta">
        <span class="badge" :data-source="props.item.source">
          {{
            props.item.source === 'new-releases'
              ? 'Nouveauté'
              : props.item.source === 'search-local'
                ? 'Local'
                : 'Spotify'
          }}
        </span>
        <span v-if="props.item.isImported" class="badge badge--ok">Importé</span>
        <span v-else-if="props.item.source === 'search-spotify'" class="badge">Non importé</span>
      </div>
    </div>

    <div class="card__actions">
      <button v-if="canImport" class="btn" :disabled="props.importing" @click="onImport">
        {{ props.importing ? 'Import...' : 'Importer' }}
      </button>
    </div>
  </div>
</template>

<style scoped>
.card {
  display: grid;
  grid-template-columns: 56px 1fr auto;
  gap: 12px;
  align-items: center;
  padding: 10px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: white;
}

.card__media {
  width: 56px;
  height: 56px;
}

.card__img {
  width: 56px;
  height: 56px;
  object-fit: cover;
  border-radius: 10px;
}

.card__title {
  font-weight: 700;
}

.card__title.clickable {
  cursor: pointer;
  text-decoration: underline;
}

.card__subtitle {
  font-size: 13px;
  opacity: 0.8;
  margin-top: 2px;
}

.card__meta {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-top: 6px;
}

.badge {
  font-size: 12px;
  padding: 2px 8px;
  border: 1px solid #cbd5e1;
  border-radius: 999px;
  opacity: 0.9;
}

.badge--ok {
  border-color: #16a34a;
}

.card__actions {
  justify-self: end;
}

.btn {
  padding: 8px 10px;
  border: 1px solid #0f172a;
  border-radius: 8px;
  background: #0f172a;
  color: white;
  cursor: pointer;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
