<script setup lang="ts">
import { computed, onMounted, ref, watch, watchEffect } from 'vue'
import { useRouter } from 'vue-router'
import { apiStore } from '@/util/apiStore'
import { useStoreAuthentification } from '@/stores/storeAuthentification'
import type {Artist, Music} from "@/types";

const props = defineProps<{ id: number }>()

const router = useRouter()
const authStore = useStoreAuthentification()

const loading = ref(false)
const error = ref<string | null>(null)
const music = ref<Music | null>(null)
const artistNames = ref<string[]>([])

const isAdmin = computed(() => authStore.estAdmin)

const isFavorite = ref(false)
const favoriteLoading = ref(false)

function buildApiUrlFromIri(iri: string): string {
  const baseUrl = import.meta.env.VITE_API_URL.replace(/\/$/, '')
  return baseUrl + iri.replace('/api', '')
}

async function resolveArtists(artists: Artist[]): Promise<string[]> {
  return Promise.all(
    artists.map(async (iri) => {
      const res = await fetch(buildApiUrlFromIri(iri), { credentials: 'include' })
      if (!res.ok) throw new Error('Artist fetch failed')
      const data = await res.json()
      return data.name as string
    })
  )
}

async function loadMusic() {
  if (!props.id || Number.isNaN(props.id)) {
    error.value = 'Musique invalide'
    music.value = null
    artistNames.value = []
    return
  }

  loading.value = true
  error.value = null
  try {
    const data = (await apiStore.getMusic(props.id)) as Music
    music.value = data
    artistNames.value = await resolveArtists(data.artists)
    await initFavorite()
  } catch (e: any) {
    error.value = e?.message ?? 'Erreur lors du chargement'
    music.value = null
    artistNames.value = []
  } finally {
    loading.value = false
  }
}

async function initFavorite() {
  if (!authStore.utilisateurConnecte || !music.value) {
    isFavorite.value = false
    return
  }

  try {
    const user = await apiStore.me()
    isFavorite.value = user.favoriteMusic?.some((m: any) => m.id === music.value?.id) ?? false
  } catch {
    isFavorite.value = false
  }
}

watchEffect(() => {
  if (!authStore.utilisateurConnecte) isFavorite.value = false
})

async function toggleFavorite() {
  if (!authStore.utilisateurConnecte || !music.value) {
    alert('Vous devez être connecté pour gérer vos favoris.')
    return
  }

  if (favoriteLoading.value) return
  favoriteLoading.value = true
  try {
    await apiStore.toggleFavorite(authStore.utilisateurConnecte.id, music.value.id)
    await initFavorite()
  } catch (err: any) {
    console.error(err)
    alert(err.message || 'Erreur lors de la gestion des favoris.')
  } finally {
    favoriteLoading.value = false
  }
}

async function validateMusic() {
  if (!music.value) return
  if (!confirm(`Valider la musique ${music.value.title} ?`)) return

  try {
    loading.value = true
    await apiStore.patch(`music/${music.value.id}`, { isValidated: true })
    await loadMusic()
    alert('Musique validée avec succès !')
  } catch (err: any) {
    console.error(err)
    alert(err.message || 'Erreur lors de la validation de la musique.')
  } finally {
    loading.value = false
  }
}

async function deleteMusic() {
  if (!music.value) return
  if (!confirm('Supprimer cette musique ?')) return

  try {
    loading.value = true
    await apiStore.delete(`music/${music.value.id}`)
    alert('Musique supprimée avec succès !')
    router.push({ name: 'music' })
  } catch (err: any) {
    console.error(err)
    alert(err.message || 'Erreur lors de la suppression de la musique.')
  } finally {
    loading.value = false
  }
}

onMounted(loadMusic)

watch(
  () => props.id,
  async () => {
    await loadMusic()
  }
)
</script>

<template>
  <div class="page">
    <div class="header" v-if="music">
      <button type="button" class="back" @click="$router.back()">← Retour</button>

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
                :disabled="favoriteLoading"
                @click="toggleFavorite"
              >
                {{ isFavorite ? '💖' : '🤍' }}
              </button>

              <template v-if="isAdmin">
                <button type="button" class="icon-btn" :disabled="loading" @click="deleteMusic">🗑️</button>
                <button
                  v-if="music.isValidated === false"
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
    </div>

    <div v-if="loading && !music" class="muted">Chargement...</div>
    <div v-if="error" class="error">{{ error }}</div>

    <div v-if="!loading && !error && !music" class="muted">Musique introuvable.</div>
  </div>
</template>

<style scoped>
.page {
  padding: 10px 0;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.header {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 12px;
}

.back {
  background: transparent;
  border: 1px solid #e2e8f0;
  color: #0f172a;
  border-radius: 12px;
  padding: 8px 10px;
  cursor: pointer;
  width: fit-content;
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

.badge.ok {
  border-color: rgba(20, 101, 66, 0.5);
}

.badge.warn {
  border-color: rgba(220, 53, 69, 0.5);
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

.error {
  padding: 10px 12px;
  border: 1px solid #ef4444;
  border-radius: 12px;
  background: white;
}

.muted {
  opacity: 0.75;
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
