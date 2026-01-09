<script setup lang="ts">
import {ref} from 'vue';
import type {Music} from '@/types';
import {storeAuthentification} from '@/stores/storeAuthentification';
import {apiStore} from '@/util/apiStore';

const props = defineProps<{ music: Music }>();
const emit = defineEmits<{
  (e: 'deleted', id: number): void
  (e: 'validated', id: number): void
}>();

const isFavorite = ref(false);
const loading = ref(false);

if (storeAuthentification.utilisateurConnecte) {
  const userId = storeAuthentification.utilisateurConnecte.id;
  apiStore.getFavorites(userId).then(favorites => {
    isFavorite.value = favorites.some((fav: any) => fav.id === props.music.id);
  });
}
async function toggleFavorite() {
  if (!storeAuthentification.utilisateurConnecte) {
    alert("Vous devez être connecté pour gérer vos favoris.");
    return;
  }
  const userId = storeAuthentification.utilisateurConnecte.id;

  try {
    const result = await apiStore.toggleFavorite(userId, props.music.id);
    isFavorite.value = result.action === 'added';
  } catch (err: any) {
    console.error('Toggle favorite error:', err);
    alert(err.message || 'Erreur lors de la gestion des favoris.');
  }
}

async function deleteMusic() {
  if (!confirm(`Voulez-vous vraiment supprimer ${props.music.title} ?`)) return;

  try {
    loading.value = true;
    await apiStore.delete(`music/${props.music.id}`);
    emit('deleted', props.music.id);
    alert('Musique supprimée avec succès !');
  } catch (err) {
    console.error(err);
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
  } catch (err) {
    console.error(err);
    alert('Erreur lors de la validation de la musique.');
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div class="content-box music-box">
    <div class="top">
      {{ music.title }}
      <button @click="toggleFavorite" class="favorite-btn">
        {{ isFavorite ? '💖' : '🤍' }}
      </button>
    </div>

    <div class="content">
      <div class="group" v-if="music.artists?.length">
        <label>Artistes</label>
        <div>{{ music.artists.map(a => a.name).join(', ') }}</div>
      </div>

      <div class="group" v-if="music.genre?.length">
        <label>Genre</label>
        <div>{{ music.genre.join(', ') }}</div>
      </div>

      <div class="group" v-if="music.link">
        <label>Lien</label>
        <a :href="music.link" target="_blank">Écouter</a>
      </div>
    </div>

    <div class="actions">
      <button class="delete-button" @click="deleteMusic" :disabled="loading">Supprimer</button>
      <button class="validate-button" @click="validateMusic" :disabled="loading">Valider</button>
    </div>
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

.favorite-btn {
  margin-left: 10px;
  background: none;
  border: none;
  font-size: 1.2em;
  cursor: pointer;
}
</style>
