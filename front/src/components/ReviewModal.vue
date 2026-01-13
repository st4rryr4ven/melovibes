<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { reviewApi } from '@/api/reviewApi'
import { useFlashStore } from '@/stores/flashStore'
import type { Review } from '@/types'

const props = defineProps<{ musicId: number }>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'submitted', review: Review): void
}>()

const flash = useFlashStore()

const rating = ref<number>(0)
const comment = ref<string>('')
const loading = ref(false)

const canSubmit = computed(() => rating.value >= 1 && rating.value <= 5 && !loading.value)

watch(
  () => props.musicId,
  () => {
    rating.value = 0
    comment.value = ''
  }
)

async function submit(): Promise<void> {
  if (!canSubmit.value) return

  try {
    loading.value = true
    const created = await reviewApi.createForMusicId({
      musicId: props.musicId,
      rating: rating.value,
      comment: comment.value.trim()
    })
    flash.success('Avis envoyé.')
    emit('submitted', created)
  } catch (e) {
    const msg = e instanceof Error && e.message ? e.message : 'Erreur lors de l’envoi de l’avis.'
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
          <div class="title">Donner votre avis</div>
          <div class="muted">Notez la musique et ajoutez un commentaire (optionnel).</div>
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

        <textarea v-model="comment" class="input textarea" :disabled="loading" placeholder="Votre commentaire…" />
      </div>

      <footer class="foot">
        <button class="btn btn--ghost" type="button" :disabled="loading" @click="close">Annuler</button>
        <button class="btn btn--primary" type="button" :disabled="!canSubmit" @click="submit">
          <span v-if="loading" class="spinner" aria-hidden="true" />
          <span>{{ loading ? 'Envoi…' : 'Envoyer' }}</span>
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
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  border-bottom: 1px solid var(--c-border);
  background: radial-gradient(900px 260px at 0% 0%, rgba(29, 185, 84, 0.12), transparent 55%),
  rgba(255, 255, 255, 0.02);
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
