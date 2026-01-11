<script setup lang="ts">
import {computed, onMounted, ref, watchEffect} from 'vue';
import type {Music} from '@/types';
import {useStoreAuthentification} from '@/stores/storeAuthentification';
import {apiStore} from '@/util/apiStore';
import router from '@/router';
import ReviewModal from '@/components/ReviewModal.vue';

const showReviewModal = ref(false);
const props = defineProps<{ music: Music }>();
const emit = defineEmits<{
  (e: 'deleted', id: number): void;
  (e: 'validated', id: number): void;
}>();

const isFavorite = ref(false);
const authStore = useStoreAuthentification();
const loading = ref(false);

async function initFavorite() {
  if (!authStore.utilisateurConnecte) {
    isFavorite.value = false
    return
  }

  const user = await apiStore.me()
  isFavorite.value = user.favoriteMusic?.some((m: any) => m.id === props.music.id)
}

initFavorite();

watchEffect(() => {
  if (!authStore.utilisateurConnecte) {
    isFavorite.value = false;
  }
});

async function toggleFavorite() {
  if (!authStore.utilisateurConnecte) {
    alert('Vous devez être connecté pour gérer vos favoris.')
    return
  }

  try {
    await apiStore.toggleFavorite(authStore.utilisateurConnecte.id, props.music.id)
    await initFavorite()
  } catch (err: any) {
    console.error(err)
    alert(err.message || 'Erreur lors de la gestion des favoris.')
  }
}

const isAdmin = computed(() => {
  const user = authStore.utilisateurConnecte;
  return user?.roles?.includes('ROLE_ADMIN') ?? false;
});

function editMusic() {
  router.push({name: 'music-edit', params: {id: props.music.id}});
}

async function deleteMusic() {
  if (!confirm(`Supprimer cette musique ?`)) return;

  try {
    loading.value = true;
    await apiStore.delete(`music/${props.music.id}`);
    alert('Musique supprimée avec succès !');
    emit('deleted', props.music.id);
  } catch (err: any) {
    console.error(err);
    alert(err.message || 'Erreur lors de la suppression de la musique.');
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
  } catch (err: any) {
    console.error(err);
    alert(err.message || 'Erreur lors de la validation de la musique.');
  } finally {
    loading.value = false;
  }
}

async function resolveArtists(artists: string[]) {
  const baseUrl = import.meta.env.VITE_API_URL.replace(/\/$/, '');
  return Promise.all(
    artists.map(async iri => {
      const cleanIri = iri.startsWith('/api/') ? iri.replace('/api/', '/') : iri;
      const res = await fetch(`${baseUrl}${cleanIri}`);
      if (!res.ok) throw new Error('Artist fetch failed');
      const data = await res.json();
      return data.name;
    })
  );
}

const artistNames = ref<string[]>([]);
onMounted(async () => {
  artistNames.value = await resolveArtists(props.music.artists);
});
</script>

<template>
  <div class="content-box music-box">
    <div class="top">
      {{ props.music.title }}
      <button v-if="authStore.estConnecte" @click="toggleFavorite" class="icon-btn">
        {{ isFavorite ? '💖' : '🤍' }}
      </button>
      <button v-if="authStore.estConnecte" class="icon-btn" @click="showReviewModal = true">
        💬
      </button>

      <ReviewModal v-if="showReviewModal" :music-id="props.music.id" @close="showReviewModal = false" @submitted="showReviewModal = false"/>

      <div v-if="isAdmin" class="admin-actions">
        <button @click="editMusic" class="icon-btn">✏️</button>
        <button @click="deleteMusic" class="icon-btn" :disabled="loading">🗑️</button>
        <button  @click="validateMusic" class="icon-btn" :disabled="loading">✅</button>
      </div>
    </div>

    <div class="content">
      <div class="group" v-if="artistNames.length">
        <label>Artistes</label>
        <div>{{ artistNames.join(', ') }}</div>
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

.admin-actions .icon-btn {
  margin-left: 5px;
}

</style>
