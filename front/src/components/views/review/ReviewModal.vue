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
const loading = ref(false)

const canSubmit = computed(() =>
  rating.value >= 1 && rating.value <= 5 && !loading.value
)

watch(
  () => [props.musicId, props.review],
  () => {
    if (props.review) {
      rating.value = props.review.rating
      comment.value = props.review.comment ?? ''
    } else {
      rating.value = 0
      comment.value = ''
    }
  },
  {immediate: true}
)

async function submit(): Promise<void> {
  if (!canSubmit.value) return

  try {
    loading.value = true
    let result: Review

    if (props.review) {
      result = await reviewApi.patch(props.review.id, {
        rating: rating.value,
        comment: comment.value.trim() || null
      })
      flash.success('Avis modifié.')
    } else {
      result = await reviewApi.createForMusicId(props.musicId, {
        rating: rating.value,
        comment: comment.value.trim() || null
      })
      flash.success('Avis envoyé.')
    }

    emit('submitted', result)
  } catch (e: any) {
    let msg = "Erreur lors de l'envoi de l'avis."

    if (e instanceof Error) {
      msg = e.message

      if (
        e.message.includes('deja poste') ||
        e.message.includes('déjà posté') ||
        e.message.includes('already')
      ) {
        msg = 'Vous avez déjà posté un avis pour cette musique.'
      } else if ((e as any).status === 401) {
        msg = 'Vous devez être connecté pour poster un avis.'
      } else if ((e as any).status === 409) {
        msg = 'Vous avez déjà posté un avis pour cette musique.'
      }
    }

    flash.error(msg)
  } finally {
    loading.value = false
  }
}

function close(): void {
  if (loading.value) return
  emit('close')
}

function setRating(v: number): void {
  if (loading.value) return
  rating.value = v
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
        <div class="stars" aria-label="Note">
          <button
            v-for="n in 5"
            :key="n"
            class="star"
            type="button"
            :class="{ 'is-on': n <= rating }"
            :disabled="loading"
            @click="setRating(n)"
          >
            ★
          </button>
        </div>

        <textarea
          v-model="comment"
          class="input textarea"
          :disabled="loading"
          placeholder="Votre commentaire…"
        />
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
/* Same styling as before */
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

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
