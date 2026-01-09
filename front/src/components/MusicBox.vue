<script setup lang="ts">
import {ref, watchEffect} from 'vue';
import type {Music} from '@/types';
import {storeAuthentification} from '@/stores/storeAuthentification';
import {apiStore} from '@/util/apiStore';

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
</script>

<template>
  <div class="content-box music-box">
    <div class="top">
      {{ props.music.title }}
      <button @click="toggleFavorite" class="favorite-btn">
        {{ isFavorite ? '💖' : '🤍' }}
      </button>
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
