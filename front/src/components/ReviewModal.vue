<script setup lang="ts">
import { ref } from 'vue';
import { apiStore } from '@/util/apiStore';

const props = defineProps<{
  musicId: number;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'submitted'): void;
}>();

const rating = ref(0);
const comment = ref('');
const loading = ref(false);

async function submitReview() {
  try {
    loading.value = true;

    await apiStore.post('reviews', {
      music: `/api/music/${props.musicId}`,
      rating: rating.value,
      comment: comment.value,
    });

    alert('Commentaire ajouté !');
    emit('submitted');
  } catch (err: any) {
    console.error(err);
    alert(err.message || 'Erreur lors de l’envoi du commentaire');
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div class="modal-backdrop" @click.self="emit('close')">
    <div class="modal">
      <h3>Donner votre avis</h3>

      <div class="stars">
        <span
          v-for="n in 5"
          :key="n"
          @click="rating = n"
          :class="{ active: n <= rating }"
        >
          ⭐
        </span>
      </div>

      <textarea
        v-model="comment"
        placeholder="Votre commentaire..."
      />

      <div class="actions">
        <button @click="emit('close')">Annuler</button>
        <button :disabled="loading || rating === 0" @click="submitReview">
          Envoyer
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal {
  background: white;
  padding: 1.5rem;
  border-radius: 8px;
  width: 400px;
}

.stars span {
  cursor: pointer;
  font-size: 1.5rem;
  opacity: 0.4;
}

.stars span.active {
  opacity: 1;
}

textarea {
  width: 100%;
  min-height: 80px;
  margin-top: 1rem;
}
</style>

