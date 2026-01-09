<script setup lang="ts">
import {ref} from 'vue';
import type {Music} from '@/types';
import {storeAuthentification} from '@/stores/storeAuthentification';
import {apiStore} from '@/util/apiStore';

const props = defineProps<{ music: Music }>();

const isFavorite = ref(false);

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
  </div>
</template>

<style scoped>
@import "@/components/css/content-box.css";
</style>
