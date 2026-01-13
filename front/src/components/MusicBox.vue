<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import type { Music } from '@/types'
import { musicApi } from '@/api/musicApi'

const props = defineProps<{ music: Music }>();
const emit = defineEmits<{
  (e: 'deleted', id: number): void
  (e: 'validated', id: number): void
}>()

const router = useRouter()
const busy = ref(false)

const artistsLabel = computed(() => (props.music.artists ?? []).map((a) => a.name).filter(Boolean).join(', '))
const isPending = computed(() => !props.music.isValidated)

function errorMessage(err: unknown, fallback: string): string {
  if (err instanceof Error && err.message) return err.message
  if (typeof err === 'string' && err) return err
  return fallback
}

function goDetail() {
  router.push({ name: 'musicDetail', params: { id: props.music.id } })
}

function editMusic() {
  router.push({ name: 'music-edit', params: { id: props.music.id } })
}

async function validateMusic(): Promise<void> {
  if (!isPending.value) return
  if (!confirm(`Valider « ${props.music.title} » ?`)) return

  busy.value = true
  try {
    await musicApi.patch(props.music.id, { isValidated: true })
    emit('validated', props.music.id)
  } catch (e) {
    alert(errorMessage(e, 'Erreur lors de la validation.'))
  } finally {
    busy.value = false
  }
}

async function deleteMusic(): Promise<void> {
  if (!confirm('Supprimer cette musique ?')) return

  busy.value = true
  try {
    await musicApi.delete(props.music.id)
    emit('deleted', props.music.id)
  } catch (e) {
    alert(errorMessage(e, 'Erreur lors de la suppression.'))
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <article class="row card">
    <div class="row__media" :class="{ 'row__media--empty': !music.picture }">
      <img v-if="music.picture" class="row__img" :src="music.picture" :alt="music.title" />
      <div v-else class="row__ph" aria-hidden="true">♪</div>
      <div v-if="isPending" class="tag tag--warn">En attente</div>
      <div v-else class="tag tag--ok">Validée</div>
    </div>

    <div class="row__main" @click="goDetail" role="button" tabindex="0">
      <div class="row__top">
        <div class="row__title" :title="music.title">{{ music.title }}</div>
        <div class="row__ids">
          <span class="id">#{{ music.id }}</span>
          <span v-if="music.spotifyId" class="id id--soft">{{ music.spotifyId }}</span>
        </div>
      </div>

      <div v-if="artistsLabel" class="row__sub" :title="artistsLabel">{{ artistsLabel }}</div>

      <div class="row__meta">
        <span v-if="typeof music.popularity === 'number'" class="chip">Pop {{ music.popularity }}</span>
        <span v-if="music.genre?.length" class="chip chip--soft">{{ music.genre.slice(0, 4).join(' • ') }}</span>
        <a v-if="music.link" class="chip chip--link" :href="music.link" target="_blank" rel="noreferrer" @click.stop>
          Ouvrir
        </a>
      </div>
    </div>

    <div class="row__actions">
      <button class="btn btn--ghost" type="button" :disabled="busy" @click="goDetail">Voir</button>
      <button class="btn btn--ghost" type="button" :disabled="busy" @click="editMusic">Éditer</button>
      <button v-if="isPending" class="btn" type="button" :disabled="busy" @click="validateMusic">Valider</button>
      <button class="btn btn--danger" type="button" :disabled="busy" @click="deleteMusic">Supprimer</button>
    </div>
  </article>
</template>

<style scoped>
.row {
  padding: 12px;
  display: grid;
  grid-template-columns: 86px 1fr auto;
  gap: 12px;
  align-items: stretch;
}

.row__media {
  position: relative;
  width: 86px;
  height: 86px;
  border-radius: 16px;
  overflow: hidden;
  border: 1px solid var(--c-border, rgba(255, 255, 255, 0.12));
  background: #0b121a;
}

.row__img {
  width: 86px;
  height: 86px;
  object-fit: cover;
}

.row__ph {
  width: 100%;
  height: 100%;
  display: grid;
  place-items: center;
  font-weight: 950;
  color: rgba(245, 248, 252, 0.75);
  background: radial-gradient(90px 90px at 25% 20%, rgba(29, 185, 84, 0.22), transparent 60%),
  radial-gradient(120px 120px at 90% 85%, rgba(17, 217, 138, 0.16), transparent 60%),
  #0b121a;
}

.tag {
  position: absolute;
  left: 8px;
  bottom: 8px;
  padding: 5px 9px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 850;
  border: 1px solid var(--c-border, rgba(255, 255, 255, 0.12));
  background: rgba(0, 0, 0, 0.55);
  color: rgba(245, 248, 252, 0.92);
}

.tag--warn {
  border-color: rgba(255, 176, 32, 0.45);
}

.tag--ok {
  border-color: rgba(29, 185, 84, 0.45);
}

.row__main {
  min-width: 0;
  display: grid;
  gap: 8px;
  cursor: pointer;
  align-content: start;
}

.row__top {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 10px;
}

.row__title {
  font-weight: 950;
  letter-spacing: 0.2px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.row__ids {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.id {
  font-size: 12px;
  padding: 4px 8px;
  border-radius: 999px;
  border: 1px solid var(--c-border, rgba(255, 255, 255, 0.12));
  background: var(--c-surface-3, #162820);
  color: var(--c-text-soft, rgba(245, 248, 252, 0.82));
}

.id--soft {
  color: var(--c-text-mute, rgba(245, 248, 252, 0.66));
}

.row__sub {
  font-size: 13px;
  color: var(--c-text-mute, rgba(245, 248, 252, 0.66));
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.row__meta {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.chip {
  display: inline-flex;
  align-items: center;
  padding: 5px 10px;
  border-radius: 999px;
  border: 1px solid var(--c-border, rgba(255, 255, 255, 0.12));
  background: var(--c-surface-3, #162820);
  color: var(--c-text-soft, rgba(245, 248, 252, 0.82));
  font-size: 12px;
  font-weight: 750;
}

.chip--soft {
  color: var(--c-text-mute, rgba(245, 248, 252, 0.66));
}

.chip--link {
  color: rgba(220, 255, 235, 0.95);
  border-color: rgba(29, 185, 84, 0.35);
}

.row__actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  align-items: center;
  justify-content: flex-end;
}

@media (max-width: 860px) {
  .row {
    grid-template-columns: 86px 1fr;
  }

  .row__actions {
    grid-column: 1 / -1;
    justify-content: flex-start;
  }
}
</style>
