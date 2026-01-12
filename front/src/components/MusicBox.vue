<script setup lang="ts">
import {computed, onMounted, ref, watchEffect} from 'vue';
import type {Artist, Music} from '@/types';
import {useStoreAuthentification} from '@/stores/storeAuthentification';
import {apiStore} from '@/util/apiStore';

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
  if(!user.favoriteMusic) return
  isFavorite.value = user.favoriteMusic.some((m: any) => m.id === props.music.id)
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

async function resolveArtists(artists: Artist[]) {
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
  <div class="main">
    <img v-if="music.picture" class="cover" :src="music.picture" :alt="music.title" />

    <div class="info">
      <div class="title-row">
        <h2 class="title">{{ music.title }}</h2>

        <div class="actions">
          <button
            v-if="authStore.estConnecte"
            type="button"
            class="icon-btn"
            @click="toggleFavorite"
          >
            {{ isFavorite ? '💖' : '🤍' }}
          </button>

          <template v-if="isAdmin">
            <button type="button" class="icon-btn" :disabled="loading" @click="deleteMusic">🗑️</button>
            <button
              v-if="!music.isValidated"
              type="button"
              class="icon-btn"
              :disabled="loading"
              @click="validateMusic"
            >
              ✅
            </button>
          </template>
        </div>
      </div>

      <div class="subtitle" v-if="artistNames.length">{{ artistNames.join(', ') }}</div>

      <div class="meta">
        <span class="badge" v-if="typeof music.popularity === 'number'">Popularité: {{ music.popularity }}</span>
      </div>

      <div class="details">
        <div class="row" v-if="music.genre?.length">
          <div class="label">Genres</div>
          <div class="value">{{ music.genre.join(', ') }}</div>
        </div>

        <div class="row" v-if="music.link">
          <div class="label">Lien</div>
          <div class="value">
            <a :href="music.link" target="_blank" rel="noreferrer">Écouter</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@import "@/components/css/content-box.css";

.admin-actions .icon-btn {
  margin-left: 5px;
}

.main {
  display: grid;
  grid-template-columns: 120px 1fr;
  gap: 14px;
  align-items: start;
  margin-top: 12px;
}

.cover {
  width: 120px;
  height: 120px;
  border-radius: 16px;
  object-fit: cover;
}

.info {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.title-row {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  align-items: center;
}

.title {
  margin: 0;
  font-weight: 900;
  line-height: 1.1;
}

.subtitle {
  font-size: 13px;
  opacity: 0.8;
}

.actions {
  display: flex;
  gap: 8px;
  align-items: center;
}

.icon-btn {
  border: 1px solid #e2e8f0;
  background: white;
  border-radius: 12px;
  padding: 8px 10px;
  cursor: pointer;
}

.icon-btn:disabled {
  cursor: not-allowed;
  opacity: 0.7;
}

.meta {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.badge {
  font-size: 12px;
  padding: 2px 10px;
  border: 1px solid #cbd5e1;
  border-radius: 999px;
  opacity: 0.95;
}

.details {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding-top: 6px;
}

.row {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.label {
  font-weight: 800;
  font-size: 12px;
  opacity: 0.85;
}

.value {
  font-size: 14px;
}


@media (max-width: 640px) {
  .main {
    grid-template-columns: 1fr;
  }

  .cover {
    width: 100%;
    height: auto;
    max-height: 280px;
  }
}
</style>
