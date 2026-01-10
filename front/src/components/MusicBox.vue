<script setup lang="ts">
import {computed, ref, watchEffect} from 'vue';
import type {Music} from '@/types';
import {useStoreAuthentification} from '@/stores/storeAuthentification'
import {apiStore} from '@/util/apiStore';
import router from "@/router";

const props = defineProps<{ music: Music }>();
const emit = defineEmits<{
  (e: 'deleted', id: number): void
  (e: 'validated', id: number): void
}>();

const isFavorite = ref(false);
const authStore = useStoreAuthentification();
const loading = ref(false);


async function initFavorite() {
  if (authStore.utilisateurConnecte) {
    const userId = authStore.utilisateurConnecte.id;
    try {
      const favorites = await apiStore.getFavorites(userId);
      isFavorite.value = favorites.some((fav: any) => fav.id === props.music.id);
    } catch (err) {
      console.error('Failed to fetch favorites:', err);
    }
  }
}

initFavorite();

watchEffect(() => {
  if (!authStore.utilisateurConnecte) {
    isFavorite.value = false;
  }
});

async function toggleFavorite() {
  if (!authStore.utilisateurConnecte) {
    alert('Vous devez être connecté pour gérer vos favoris.');
    return;
  }

  const userId = authStore.utilisateurConnecte.id;

  try {
    const result = await apiStore.toggleFavorite(userId, props.music.id);

    if (result.action === 'added') {
      isFavorite.value = true;
    } else if (result.action === 'removed') {
      isFavorite.value = false;
    }
  } catch (err: any) {
    console.error('Toggle favorite error:', err);
    alert(err.message || 'Erreur lors de la gestion des favoris.');
  }
}

const isAdmin = computed(() => {
  const user = authStore.utilisateurConnecte;
  return user?.roles?.includes('ROLE_ADMIN') ?? false;
});

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
    await apiStore.patch(`music/${props.music.id}`, {isValidated: true});
    emit('validated', props.music.id);
    alert('Musique validée avec succès !');
  } catch (err) {
    console.error(err);
    alert('Erreur lors de la validation de la musique.');
  } finally {
    loading.value = false;
  }
}

function editMusic() {
  router.push({name: 'music-edit', params: {id: props.music.id}});
}

</script>

<template>
  <div class="content-box music-box">
    <div class="top">
      {{ props.music.title }}
      <button v-if="authStore.estConnecte" @click="toggleFavorite" class="icon-btn">
        {{ isFavorite ? '💖' : '🤍' }}
      </button>
      <div v-if="isAdmin" class="admin-actions">
        <button @click="editMusic" class="icon-btn">✏️</button>
        <button @click="deleteMusic" class="icon-btn">🗑️</button>
      </div>
    </div>
    <div class="content">
      <div class="group" v-if="props.music.artists?.length">
        <label>Artistes</label>
        <div>{{ props.music.artists.map(a => a.name).join(', ') }}</div>
      </div>
      <div class="group" v-if="props.music.genre?.length">
        <label>Genre</label>
        <div>{{ props.music.genre.join(', ') }}</div>
      </div>
      <div class="group" v-if="props.music.link">
        <label>Lien</label>
        <a :href="props.music.link" target="_blank">Écouter</a>
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
