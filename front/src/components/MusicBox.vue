<script setup lang="ts">
import {computed, ref, watchEffect} from 'vue';
import type {Music} from '@/types';
import {storeAuthentification} from '@/stores/storeAuthentification';
import {apiStore} from '@/util/apiStore';
import router from "@/router";

const props = defineProps<{ music: Music }>();

const isFavorite = ref(false);

async function initFavorite() {
  if (storeAuthentification.utilisateurConnecte) {
    const userId = storeAuthentification.utilisateurConnecte.id;
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
  if (!storeAuthentification.utilisateurConnecte) {
    isFavorite.value = false;
  }
});

async function toggleFavorite() {
  if (!storeAuthentification.utilisateurConnecte) {
    alert('Vous devez être connecté pour gérer vos favoris.');
    return;
  }

  const userId = storeAuthentification.utilisateurConnecte.id;

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
  const user = storeAuthentification.utilisateurConnecte;
  return user?.roles?.includes('ROLE_ADMIN') ?? false;
});

async function deleteMusic() {
  if (!confirm('Supprimer cette musique ?')) return;

  try {
    await apiStore.deleteMusic(props.music.id);
    alert('Musique supprimée');
    window.location.reload();
  } catch (err: any) {
    alert(err.message || 'Erreur suppression');
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
      <button v-if="storeAuthentification.estConnecte" @click="toggleFavorite" class="favorite-btn">
        {{ isFavorite ? '💖' : '🤍' }}
      </button>
      <div v-if="isAdmin" class="admin-actions">
        <button @click="editMusic">✏️</button>
        <button @click="deleteMusic">🗑️</button>
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
  </div>
</template>

<style scoped>
@import "@/components/css/content-box.css";
</style>
