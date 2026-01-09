<script setup lang="ts">
import {ref} from 'vue';
import type {Music} from '@/types';
import {storeAuthentification} from '@/stores/storeAuthentification';
import {apiStore} from '@/util/apiStore';
import {useRouter} from 'vue-router';

const router = useRouter();

const title = ref('');
const artists = ref<string[]>(['']);
const genres = ref<string[]>(['']);
const link = ref('');
const picture = ref('');
const popularity = ref<number | null>(50);

const error = ref<string | null>(null);
const success = ref<string | null>(null);

function addArtistField() {
  artists.value.push('');
}

function removeArtistField(index: number) {
  if (artists.value.length > 1) artists.value.splice(index, 1);
}

function addGenreField() {
  genres.value.push('');
}

function removeGenreField(index: number) {
  if (genres.value.length > 1) genres.value.splice(index, 1);
}

async function submitMusic() {
  if (!storeAuthentification.utilisateurConnecte) {
    error.value = 'Vous devez être connecté pour ajouter une musique.';
    return;
  }

  if (!title.value.trim() || artists.value.some(a => !a.trim()) || genres.value.some(g => !g.trim())) {
    error.value = 'Veuillez remplir tous les champs obligatoires.';
    return;
  }

  const musicPayload: Partial<Music> = {
    title: title.value,
    artists: artists.value.map((name) => `/artists/${artistIdFromName(name)}`),
    genre: genres.value,
    link: link.value || undefined,
    picture: picture.value || undefined,
    popularity: popularity.value || 0,
  };

  try {
    await apiStore.createMusic(musicPayload);
    success.value = 'Musique créée avec succès !';
    router.push('/music');
  } catch (err: any) {
    error.value = err.message || 'Erreur lors de la création de la musique.';
  }
}
</script>

<template>
  <div class="music-form">
    <h2>Créer une musique</h2>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>
    <div v-if="success" class="alert alert-success">{{ success }}</div>

    <label>Titre *</label>
    <input v-model="title" type="text" placeholder="Titre de la musique"/>

    <div class="dynamic-field">
      <label>Artistes *</label>
      <div v-for="(artist, i) in artists" :key="i" class="artist-field">
        <input v-model="artists[i]" type="text" placeholder="Nom de l'artiste"/>
        <button type="button" @click="removeArtistField(i)" v-if="artists.length > 1">❌</button>
      </div>
      <button type="button" @click="addArtistField">Ajouter un artiste</button>
    </div>

    <div class="dynamic-field">
      <label>Genres *</label>
      <div v-for="(genre, i) in genres" :key="i" class="genre-field">
        <input v-model="genres[i]" type="text" placeholder="Genre"/>
        <button type="button" @click="removeGenreField(i)" v-if="genres.length > 1">❌</button>
      </div>
      <button type="button" @click="addGenreField">Ajouter un genre</button>
    </div>

    <label>Lien (Spotify, YouTube...)</label>
    <input v-model="link" type="url" placeholder="https://..."/>

    <label>Image (URL)</label>
    <input v-model="picture" type="url" placeholder="https://..."/>

    <label>Popularité (0-100)</label>
    <input v-model.number="popularity" type="number" min="0" max="100"/>

    <button type="button" @click="submitMusic">Créer la musique</button>
  </div>
</template>

<style scoped>
@import "@/components/css/form-style.css";
</style>
