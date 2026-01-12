<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import type { Music } from '@/types'
import { useStoreAuthentification } from '@/stores/storeAuthentification'
import { userApi } from '@/api/userApi'
import { musicApi } from '@/api/musicApi'

const showReviewModal = ref(false);
const props = defineProps<{ music: Music }>();
const emit = defineEmits<{
  (e: 'deleted', id: number): void
  (e: 'validated', id: number): void
}>()

const authStore = useStoreAuthentification()
const router = useRouter()
const isFavorite = ref(false)
const loading = ref(false)

function errorMessage(err: unknown, fallback: string): string {
  if (err instanceof Error && err.message) return err.message
  if (typeof err === 'string' && err) return err
  return fallback
}

const isAdmin = computed(() => authStore.utilisateurConnecte?.roles?.includes('ROLE_ADMIN') ?? false)

const artistNames = computed(() => (props.music.artists ?? []).map(a => a.name).filter(Boolean).join(', '))

async function loadFavorite(): Promise<void> {
  const userId = authStore.utilisateurConnecte?.id
  if (!userId) {
    isFavorite.value = false
    return
  }

  try {
    const favorites = await userApi.getFavorites(userId)
    isFavorite.value = favorites.some(m => m.id === props.music.id)
  } catch {
    isFavorite.value = false
  }
}

watch(
  () => [authStore.utilisateurConnecte?.id, props.music.id],
  async () => {
    await loadFavorite()
  },
  { immediate: true }
)

async function toggleFavorite(): Promise<void> {
  const userId = authStore.utilisateurConnecte?.id
  if (!userId) {
    alert('Vous devez être connecté pour gérer vos favoris.')
    return
  }

  try {
    await userApi.toggleFavorite(userId, props.music.id)
    await loadFavorite()
  } catch (err) {
    console.error(err)
    alert(errorMessage(err, 'Erreur lors de la gestion des favoris.'))
  }
}

async function deleteMusic(): Promise<void> {
  if (!confirm('Supprimer cette musique ?')) return

  try {
    loading.value = true
    await musicApi.delete(props.music.id)
    alert('Musique supprimée avec succès !')
    emit('deleted', props.music.id)
  } catch (err) {
    console.error(err)
    alert(errorMessage(err, 'Erreur lors de la suppression de la musique.'))
  } finally {
    loading.value = false
  }
}

async function validateMusic(): Promise<void> {
  if (!confirm(`Valider la musique ${props.music.title} ?`)) return

  try {
    loading.value = true
    await musicApi.patch(props.music.id, { isValidated: true })
    emit('validated', props.music.id)
    alert('Musique validée avec succès !')
  } catch (err) {
    console.error(err)
    alert(errorMessage(err, 'Erreur lors de la validation de la musique.'))
  } finally {
    loading.value = false
  }
}

function editMusic(): void {
  router.push({ name: 'music-edit', params: { id: props.music.id } })
}
</script>

<template>
  <div class="main">
    <img v-if="music.picture" class="cover" :src="music.picture" :alt="music.title" />

    <div class="info">
      <div class="title-row">
        <h2 class="title">{{ music.title }}</h2>

        <div class="actions">
          <button v-if="authStore.estConnecte" type="button" class="icon-btn" @click="toggleFavorite">
            {{ isFavorite ? '💖' : '🤍' }}
          </button>

          <button
            v-if="authStore.estConnecte"
            type="button"
            class="icon-btn"
            @click="showReviewModal = true"
          >
            💬
          </button>

          <template v-if="isAdmin">
            <button type="button" class="icon-btn" :disabled="loading" @click="editMusic">✏️</button>
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

      <div class="subtitle" v-if="artistNames">{{ artistNames }}</div>
      <ReviewModal
        v-if="showReviewModal"
        :music-id="music.id"
        @close="showReviewModal = false"
        @submitted="showReviewModal = false"
      />

      <div class="subtitle" v-if="artistNames.length">
        {{ artistNames.join(', ') }}
      </div>

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
