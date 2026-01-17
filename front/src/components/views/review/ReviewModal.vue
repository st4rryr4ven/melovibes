<script setup lang="ts">
import {computed, ref, watch} from 'vue'
import {reviewApi} from '@/api/reviewApi.ts'
import {useFlashStore} from '@/stores/flashStore.ts'
import type {Review} from '@/types.ts'

const props = defineProps<{
  musicId: number
  review?: Review
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'submitted', review: Review): void
}>()

const flash = useFlashStore()

const rating = ref<number>(0)
const comment = ref<string>('')
const melodyRating = ref<number>(0)
const lyricsRating = ref<number>(0)
const vocalsRating = ref<number>(0)
const impactRating = ref<number>(0)
const loading = ref(false)

const canSubmit = computed(() =>
  rating.value >= 1 && rating.value <= 5 && !loading.value
)

const criteriaList = computed(() => [
  {id: 'melody', label: 'Mélodie', val: melodyRating.value},
  {id: 'lyrics', label: 'Paroles', val: lyricsRating.value},
  {id: 'vocals', label: 'Vocals', val: vocalsRating.value},
  {id: 'impact', label: 'Impact', val: impactRating.value},
])

watch(
  () => [melodyRating.value, lyricsRating.value, vocalsRating.value, impactRating.value],
  ([mel, lyr, voc, imp]) => {
    const values = [mel, lyr, voc, imp].filter((v): v is number => typeof v === 'number' && v > 0);

    if (values.length > 0) {
      const sum = values.reduce((acc: number, val: number) => acc + val, 0);
      rating.value = Math.round(sum / values.length);
    } else {
      rating.value = 0;
    }
  },
  {immediate: true}
)

async function submit(): Promise<void> {
  if (!canSubmit.value) return
  try {
    loading.value = true
    const payload = {
      rating: rating.value,
      comment: comment.value.trim() || null,
      melodyRating: melodyRating.value || null,
      lyricsRating: lyricsRating.value || null,
      vocalsRating: vocalsRating.value || null,
      impactRating: impactRating.value || null
    }

    let result: Review
    if (props.review) {
      result = await reviewApi.patch(props.review.id, payload)
      flash.success('Avis modifié.')
    } else {
      result = await reviewApi.createForMusicId(props.musicId, payload)

      result = await reviewApi.patch(result.id, payload)

      flash.success('Avis envoyé.')
    }
    emit('submitted', result)
  } catch (e: any) {
  } finally {
    loading.value = false
  }
}

function close(): void {
  if (loading.value) return
  emit('close')
}

function setSubRating(id: string, v: number) {
  if (loading.value) return
  if (id === 'melody') melodyRating.value = v
  if (id === 'lyrics') lyricsRating.value = v
  if (id === 'vocals') vocalsRating.value = v
  if (id === 'impact') impactRating.value = v
}
</script>

<template>
  <div class="backdrop" role="dialog" aria-modal="true" @click.self="close">
    <div class="modal card">
      <header class="head">
        <div>
          <div class="title">
            {{ review ? 'Modifier votre avis' : 'Donner votre avis' }}
          </div>
          <div class="muted">
            Notez la musique et ajoutez un commentaire (optionnel).
          </div>
        </div>
        <button class="btn btn--ghost" type="button" :disabled="loading" @click="close">✕</button>
      </header>

      <div class="body">
        <label class="label">Note globale (Auto)</label>
        <div class="stars">
          <div
            v-for="n in 5"
            :key="n"
            class="star readonly"
            :class="{ 'is-on': n <= rating }"
          >
            ★
          </div>
        </div>

        <div class="sub-grid">
          <div v-for="crit in criteriaList" :key="crit.id" class="crit-row">
            <span class="crit-label">{{ crit.label }}</span>
            <div class="mini-stars">
              <button
                v-for="n in 5"
                :key="n"
                type="button"
                class="mini-star"
                :class="{ 'is-on': n <= crit.val }"
                @click="setSubRating(crit.id, n)"
              >
                ★
              </button>
            </div>
          </div>
        </div>

        <textarea v-model="comment" class="input textarea" placeholder="Votre commentaire…"/>
      </div>

      <footer class="foot">
        <button class="btn btn--ghost" type="button" :disabled="loading" @click="close">Annuler
        </button>

        <button class="btn btn--primary" type="button" :disabled="!canSubmit" @click="submit">
          <span v-if="loading" class="spinner" aria-hidden="true"/>
          <span>{{ loading ? 'Envoi…' : review ? 'Modifier' : 'Envoyer' }}</span>
        </button>
      </footer>
    </div>
  </div>
</template>

<style scoped>
.backdrop {
  position: fixed;
  inset: 0;
  z-index: 1200;
  display: grid;
  place-items: center;
  padding: 20px;
  background: rgba(0, 0, 0, 0.62);
  backdrop-filter: blur(6px);
}

.modal {
  width: min(560px, 94vw);
}

.head {
  padding: 14px;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
  border-bottom: 1px solid var(--c-border);
  background: radial-gradient(900px 260px at 0% 0%, rgba(29, 185, 84, 0.12), transparent 55%), rgba(255, 255, 255, 0.02);
}

.title {
  font-weight: 950;
  letter-spacing: 0.2px;
}

.body {
  padding: 14px;
  display: grid;
  gap: 12px;
}

.stars {
  display: inline-flex;
  gap: 8px;
}

.star {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  border: 1px solid var(--c-border);
  background: var(--c-surface-2);
  color: rgba(245, 248, 252, 0.45);
  cursor: pointer;
  font-size: 18px;
  display: grid;
  place-items: center;
  transition: transform 0.12s ease, background 0.12s ease, border-color 0.12s ease;
}

.star:hover {
  transform: translateY(-1px);
  border-color: var(--c-border-2);
  background: #193024;
}

.star.is-on {
  color: rgba(255, 238, 210, 0.95);
  border-color: rgba(255, 176, 32, 0.55);
  background: #3a2b10;
}

.star:disabled {
  cursor: not-allowed;
  opacity: 0.7;
  transform: none;
}

.textarea {
  min-height: 120px;
  resize: vertical;
}

.foot {
  padding: 14px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  border-top: 1px solid var(--c-border);
  background: rgba(255, 255, 255, 0.02);
}

.spinner {
  width: 16px;
  height: 16px;
  border-radius: 999px;
  border: 2px solid rgba(245, 248, 252, 0.25);
  border-top-color: rgba(245, 248, 252, 0.85);
  animation: spin 0.9s linear infinite;
}

.sub-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  padding: 12px;
  background: var(--c-surface-3);
  border-radius: 12px;
}

.crit-row {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.crit-label {
  font-size: 11px;
  font-weight: 800;
  color: var(--c-text-mute);
  text-transform: uppercase;
}

.mini-stars {
  display: flex;
  gap: 4px;
}

.mini-star {
  background: none;
  border: none;
  padding: 0;
  font-size: 16px;
  color: rgba(245, 248, 252, 0.15);
  cursor: pointer;
}

.mini-star.is-on {
  color: #1db954;
}

.star.readonly {
  cursor: default;
  pointer-events: none;
}

.star.readonly.is-on {
  background: #2e210a;
  border-color: #e69b20;
}

.star:not(.readonly):hover {
  transform: translateY(-1px);
  border-color: var(--c-border-2);
  background: #193024;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
