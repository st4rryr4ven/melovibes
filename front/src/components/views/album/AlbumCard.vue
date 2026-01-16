<script setup lang="ts">
import { computed } from 'vue'
import type { NewReleaseAlbumItem } from '@/types.ts'

const props = withDefaults(
  defineProps<{
    item: NewReleaseAlbumItem
    busy?: boolean
  }>(),
  {
    busy: false
  }
)

const emit = defineEmits<{
  (e: 'select'): void
}>()

const artistsLabel = computed(() => props.item.artists.map((a) => a.name).filter(Boolean).join(', ') || 'Artiste inconnu')

const metaLine = computed(() => {
  const parts: string[] = []
  if (props.item.releaseDate) parts.push(props.item.releaseDate)
  if (typeof props.item.totalTracks === 'number') {
    const n = props.item.totalTracks
    parts.push(`${n} titre${n > 1 ? 's' : ''}`)
  }
  return parts.join(' • ')
})
</script>

<template>
  <button type="button" class="poster" :disabled="props.busy" @click="emit('select')" :aria-label="props.item.name">
    <div class="poster__frame">
      <img v-if="props.item.picture" class="poster__img" :src="props.item.picture" :alt="props.item.name" />
      <div v-else class="poster__placeholder" aria-hidden="true">♪</div>

      <div class="poster__shade" aria-hidden="true" />
      <div class="poster__glow" aria-hidden="true" />

      <div class="poster__content">
        <div class="poster__title" :title="props.item.name">{{ props.item.name }}</div>
        <div class="poster__subtitle" :title="artistsLabel">{{ artistsLabel }}</div>
        <div v-if="metaLine" class="poster__meta">{{ metaLine }}</div>
      </div>

      <div class="poster__busy" v-if="props.busy" aria-hidden="true">
        <span class="spinner" />
      </div>
    </div>
  </button>
</template>

<style scoped>
.poster {
  width: 100%;
  border: none;
  background: transparent;
  padding: 0;
  text-align: left;
  cursor: pointer;
}

.poster:disabled {
  cursor: not-allowed;
  opacity: 0.85;
}

.poster__frame {
  position: relative;
  border-radius: 18px;
  overflow: hidden;
  border: 1px solid var(--c-border);
  background: #0b121a;
  box-shadow: var(--shadow-1);
  aspect-ratio: 2 / 3;
  transform: translateY(0);
  transition: transform 0.14s ease, border-color 0.14s ease, box-shadow 0.14s ease;
}

.poster:hover .poster__frame {
  transform: translateY(-2px);
  border-color: rgba(29, 185, 84, 0.35);
  box-shadow: 0 10px 36px rgba(0, 0, 0, 0.55);
}

.poster__img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  transform: scale(1.02);
  transition: transform 0.18s ease;
}

.poster:hover .poster__img {
  transform: scale(1.06);
}

.poster__placeholder {
  position: absolute;
  inset: 0;
  display: grid;
  place-items: center;
  font-weight: 900;
  font-size: 34px;
  color: rgba(245, 248, 252, 0.78);
  background: radial-gradient(140px 140px at 25% 20%, rgba(29, 185, 84, 0.22), transparent 60%),
  radial-gradient(190px 190px at 90% 85%, rgba(17, 217, 138, 0.16), transparent 60%),
  #0b121a;
}

.poster__shade {
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(0, 0, 0, 0.1) 0%, rgba(0, 0, 0, 0.15) 35%, rgba(0, 0, 0, 0.88) 100%);
}

.poster__glow {
  position: absolute;
  inset: -2px;
  background: radial-gradient(320px 220px at 20% 20%, rgba(29, 185, 84, 0.18), transparent 55%),
  radial-gradient(280px 200px at 80% 85%, rgba(17, 217, 138, 0.14), transparent 55%);
  opacity: 0.85;
  mix-blend-mode: screen;
  pointer-events: none;
}

.poster__content {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  padding: 12px 12px 12px;
  display: grid;
  gap: 6px;
}

.poster__title {
  font-weight: 950;
  letter-spacing: 0.2px;
  line-height: 1.1;
  color: rgba(245, 248, 252, 0.98);
  text-shadow: 0 2px 12px rgba(0, 0, 0, 0.55);
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.poster__subtitle {
  font-size: 13px;
  color: rgba(245, 248, 252, 0.78);
  text-shadow: 0 2px 12px rgba(0, 0, 0, 0.55);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.poster__meta {
  font-size: 12px;
  color: rgba(245, 248, 252, 0.62);
  text-shadow: 0 2px 12px rgba(0, 0, 0, 0.55);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.poster__busy {
  position: absolute;
  top: 10px;
  right: 10px;
  display: grid;
  place-items: center;
  width: 34px;
  height: 34px;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.16);
  background: rgba(0, 0, 0, 0.55);
  backdrop-filter: blur(6px);
}

.spinner {
  width: 16px;
  height: 16px;
  border-radius: 999px;
  border: 2px solid rgba(245, 248, 252, 0.22);
  border-top-color: rgba(29, 185, 84, 0.95);
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
