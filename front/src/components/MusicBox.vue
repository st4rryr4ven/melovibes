<script setup lang="ts">
import type { Music } from '@/types';
import { ref } from 'vue';
import { apiStore } from '@/util/apiStore.ts';

const props = defineProps<{ music: Music }>();
const emit = defineEmits<{
  (e: 'deleted', id: number): void
  (e: 'validated', id: number): void
}>();
const loading = ref(false);

async function deleteMusic() {
  if (!confirm(`Voulez-vous vraiment supprimer ${props.music.title} ?`)) return;

  try {
    loading.value = true;
    await apiStore.delete(`music/${props.music.id}`);
    emit('deleted', props.music.id);
    alert('Musique supprimée avec succès !');
  } catch (error) {
    console.error(error);
    alert('Erreur lors de la suppression de la musique.');
  } finally {
    loading.value = false;
  }
}
async function validateMusic() {
  if (!confirm(`Valider la musique ${props.music.title} ?`)) return;

  try {
    loading.value = true;
    await apiStore.patch(`music/${props.music.id}`, { isValidated: true });
    emit('validated', props.music.id);
    alert('Musique validée avec succès !');
  } catch (error) {
    console.error(error);
    alert('Erreur lors de la validation de la musique.');
  } finally {
    loading.value = false;
  }
}

</script>

<template>
  <div class="content-box">
    <div class="top">{{ music.title }}</div>
    <div class="content">
      <div class="group">
        <label>Titre</label>
        <input :value="music.title" readonly />
      </div>
      <div class="group">
        <label>Artistes</label>
        <input :value="music.artists.map(a => a.name).join(', ')" readonly />
      </div>
      <div class="group">
        <label>Genres</label>
        <input :value="music.genre.join(', ')" readonly />
      </div>
    </div>
    <button class="delete-button" @click="deleteMusic" :disabled="loading">Supprimer</button>
    <button class="validate-button" @click="validateMusic" :disabled="loading">Valider</button>
  </div>
</template>


<style scoped>
@import "@/components/css/content-box.css";

.delete-button {
  margin-top: 10px;
  padding: 8px 12px;
  background-color: #e74c3c;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.delete-button:disabled {
  background-color: #ccc;
  cursor: not-allowed;
}
.validate-button {
  margin-top: 10px;
  padding: 8px 12px;
  background-color: #2ecc71;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.validate-button:disabled {
  background-color: #ccc;
  cursor: not-allowed;
}
</style>

