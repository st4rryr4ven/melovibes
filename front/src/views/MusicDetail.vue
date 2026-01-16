<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import type { Music } from '@/types'
import { musicApi } from '@/api/musicApi'
import { userApi } from '@/api/userApi'
import { useStoreAuthentification } from '@/stores/storeAuthentification'
import { useFlashStore } from '@/stores/flashStore'
import ReviewModal from '@/components/ReviewModal.vue'
import ReviewList from '@/components/ReviewList.vue'

const props = defineProps<{ id: number }>()

const router = useRouter()
const authStore = useStoreAuthentification()
const flash = useFlashStore()

const loading = ref(false)
const error = ref<string | null>(null)
const music = ref<Music | null>(null)

const isFavorite = ref(false)
const favoriteLoading = ref(false)
const adminLoading = ref(false)

const showReviewModal = ref(false)

const isAdmin = computed(() => authStore.estAdmin)
const artistNames = computed(() => (music.value?.artists ?? []).map((a) => a.name).filter(Boolean).join(', '))
const hasCover = computed(() => !!music.value?.picture)
const genres = computed(() => (music.value?.genre ?? []).filter(Boolean))

const reviews = computed(() => (music.value?.reviews ?? []).filter(Boolean))
const reviewCount = computed(() => reviews.value.length)
const averageRating = computed(() => {
  const list = reviews.value
  if (!list.length) return null
  const sum = list.reduce((acc, r) => acc + (typeof r.rating === 'number' ? r.rating : 0), 0)
  return Math.round((sum / list.length) * 10) / 10
})

function errorMessage(err: unknown, fallback: string): string {
  if (err instanceof Error && err.message) return err.message
  if (typeof err === 'string' && err) return err
  return fallback
}

async function loadFavorite(): Promise<void> {
  const userId = authStore.utilisateurConnecte?.id
  const m = music.value
  if (!userId || !m) {
    isFavorite.value = false
    return
  }

  try {
    const favorites = await userApi.getFavorites(userId)
    isFavorite.value = favorites.some((x) => x.id === m.id)
  } catch {
    isFavorite.value = false
  }
}

function blockIfNotValidatedForUser(m: Music): boolean {
  if (m.isValidated === true) return false
  if (isAdmin.value) return false
  return true
}

async function loadMusic(): Promise<void> {
  if (!props.id || Number.isNaN(props.id)) {
    error.value = 'Musique introuvable.'
    music.value = null
    return
  }

  loading.value = true
  error.value = null

  try {
    const m = await musicApi.get(props.id)
    if (blockIfNotValidatedForUser(m)) {
      music.value = null
      error.value = 'Musique introuvable.'
      router.replace({ name: 'melovibes' })
      return
    }

    music.value = m
    await loadFavorite()
  } catch (e) {
    const msg = errorMessage(e, 'Erreur lors du chargement')
    error.value = msg
    music.value = null
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

watch(
  () => authStore.utilisateurConnecte?.id,
  async () => {
    await loadFavorite()
  }
)

async function toggleFavorite(): Promise<void> {
  const userId = authStore.utilisateurConnecte?.id
  const m = music.value

  if (!userId || !m) {
    flash.warning('Connexion requise.')
    return
  }

  try {
    favoriteLoading.value = true
    await userApi.toggleFavorite(userId, m.id)
    await loadFavorite()
    flash.success(isFavorite.value ? 'Ajouté aux favoris.' : 'Retiré des favoris.')
  } catch (e) {
    flash.error(errorMessage(e, 'Erreur lors de la gestion des favoris.'))
  } finally {
    favoriteLoading.value = false
  }
}

function openReviewModal(): void {
  const m = music.value
  if (!m) return
  if (!authStore.estConnecte) {
    flash.warning('Connexion requise.')
    return
  }
  showReviewModal.value = true
}

async function handleReviewSubmitted(_review: unknown): Promise<void> {
  showReviewModal.value = false
  await loadMusic()
}

function goBack(): void {
  router.back()
}

function goEdit(): void {
  const m = music.value
  if (!m) return
  router.push({ name: 'music-edit', params: { id: m.id } })
}

async function validateMusic(): Promise<void> {
  const m = music.value
  if (!m) return
  if (m.isValidated) return
  if (!confirm('Valider cette musique ?')) return

  try {
    adminLoading.value = true
    music.value = await musicApi.patch(m.id, { isValidated: true })
    flash.success('Musique validée.')
  } catch (e) {
    flash.error(errorMessage(e, 'Erreur lors de la validation.'))
  } finally {
    adminLoading.value = false
  }
}

async function deleteMusic(): Promise<void> {
  const m = music.value
  if (!m) return
  if (!confirm('Supprimer cette musique ?')) return

  try {
    adminLoading.value = true
    await musicApi.delete(m.id)
    flash.success('Musique supprimée.')
    router.push({ name: 'melovibes' })
  } catch (e) {
    flash.error(errorMessage(e, 'Erreur lors de la suppression.'))
  } finally {
    adminLoading.value = false
  }
}
</script>

<template>
  <div class="page">
    <div class="top">
      <button class="btn btn--ghost" type="button" @click="goBack">← Retour</button>
    </div>

    <div v-if="loading && !music" class="state panel">
      <div class="muted">Chargement…</div>
      <div class="heroSkeleton" />
    </div>

    <div v-else-if="error" class="state">
      <div class="errorBox">
        <div class="errorBox__title">Chargement impossible</div>
        <div class="errorBox__text">{{ error }}</div>
        <button class="btn btn--ghost" type="button" @click="loadMusic">Réessayer</button>
      </div>
    </div>

    <div v-else-if="music" class="content">
      <section class="cinema card">
        <div class="cinema__poster" :class="{ 'cinema__poster--empty': !hasCover }">
          <img v-if="music.picture" class="cinema__img" :src="music.picture" :alt="music.title" />
          <div v-else class="cinema__placeholder" aria-hidden="true">♪</div>
          <div class="cinema__shade" aria-hidden="true" />
          <div class="cinema__glow" aria-hidden="true" />

          <div class="cinema__posterContent">
            <div class="cinema__title" :title="music.title">{{ music.title }}</div>
            <div v-if="artistNames" class="cinema__subtitle" :title="artistNames">{{ artistNames }}</div>
            <div v-if="genres.length" class="cinema__chips">
              <span v-for="g in genres.slice(0, 6)" :key="g" class="chip">{{ g }}</span>
            </div>
          </div>
        </div>

        <div class="cinema__side">
          <div class="actions">
            <button
              v-if="authStore.estConnecte"
              class="btn"
              type="button"
              :disabled="favoriteLoading"
              @click="toggleFavorite"
            >
              <span aria-hidden="true">{{ isFavorite ? '♥' : '♡' }}</span>
              <span>{{ isFavorite ? 'Favori' : 'Ajouter aux favoris' }}</span>
            </button>

            <a v-if="music.link" class="btn btn--primary" :href="music.link" target="_blank" rel="noreferrer">
              Écouter
            </a>
          </div>

          <div class="facts">
            <div class="fact" v-if="artistNames">
              <div class="fact__k">Artiste</div>
              <div class="fact__v">{{ artistNames }}</div>
            </div>

            <div class="fact" v-if="genres.length">
              <div class="fact__k">Genres</div>
              <div class="fact__v">{{ genres.slice(0, 6).join(' • ') }}</div>
            </div>
          </div>

          <section v-if="isAdmin" class="admin panel">
            <div class="admin__title">Administration</div>
            <div class="admin__actions">
              <button class="btn btn--ghost" type="button" :disabled="adminLoading" @click="goEdit">Éditer</button>
              <button
                v-if="!music.isValidated"
                class="btn"
                type="button"
                :disabled="adminLoading"
                @click="validateMusic"
              >
                Valider
              </button>
              <button class="btn btn--danger" type="button" :disabled="adminLoading" @click="deleteMusic">
                Supprimer
              </button>
            </div>

            <div class="admin__meta">
              <span v-if="!music.isValidated" class="chip chip--warn">En attente</span>
              <span v-else class="chip chip--ok">Validée</span>
              <span v-if="typeof music.popularity === 'number'" class="chip chip--soft">
                Popularité: {{ music.popularity }}
              </span>
            </div>

            <div class="admin__grid">
              <div class="kv">
                <div class="kv__k">ID</div>
                <div class="kv__v">#{{ music.id }}</div>
              </div>
              <div v-if="music.spotifyId" class="kv">
                <div class="kv__k">Spotify</div>
                <div class="kv__v">{{ music.spotifyId }}</div>
              </div>
            </div>
          </section>
        </div>
      </section>

      <section class="reviews card">
        <header class="reviews__head">
          <div class="reviews__left">
            <div class="reviews__title">Avis</div>
            <div class="reviews__meta muted">
              <span>{{ reviewCount }} avis</span>
              <span v-if="averageRating !== null"> • Moyenne {{ averageRating }}/5</span>
            </div>
          </div>

          <button v-if="authStore.estConnecte" class="btn btn--primary" type="button" @click="openReviewModal">Donner un avis</button>
          <RouterLink v-else :to="{name: 'login'}">Connectez-vous pour donner votre avis</RouterLink>
        </header>

        <div class="reviews__body">
          <ReviewList :reviews="reviews" />
        </div>
      </section>

      <ReviewModal
        v-if="showReviewModal"
        :music-id="music.id"
        @close="showReviewModal = false"
        @submitted="handleReviewSubmitted"
      />
    </div>

    <div v-else class="state panel">
      <div class="muted">Musique introuvable.</div>
    </div>
  </div>
</template>

<style scoped>
.page {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.top {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.content {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.cinema {
  padding: 14px;
  display: grid;
  grid-template-columns: minmax(260px, 320px) 1fr;
  gap: 14px;
}

.cinema__poster {
  position: relative;
  border-radius: 18px;
  overflow: hidden;
  border: 1px solid var(--c-border);
  background: #0b121a;
  box-shadow: var(--shadow-1);
  aspect-ratio: 2 / 3;
}

.cinema__img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  transform: scale(1.02);
}

.cinema__placeholder {
  position: absolute;
  inset: 0;
  display: grid;
  place-items: center;
  font-weight: 900;
  font-size: 40px;
  color: rgba(245, 248, 252, 0.78);
  background: radial-gradient(160px 160px at 25% 20%, rgba(29, 185, 84, 0.22), transparent 60%),
  radial-gradient(220px 220px at 90% 85%, rgba(17, 217, 138, 0.16), transparent 60%),
  #0b121a;
}

.cinema__shade {
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(0, 0, 0, 0.08) 0%, rgba(0, 0, 0, 0.18) 35%, rgba(0, 0, 0, 0.9) 100%);
}

.cinema__glow {
  position: absolute;
  inset: -2px;
  background: radial-gradient(520px 320px at 20% 20%, rgba(29, 185, 84, 0.18), transparent 55%),
  radial-gradient(420px 300px at 80% 85%, rgba(17, 217, 138, 0.14), transparent 55%);
  opacity: 0.9;
  mix-blend-mode: screen;
  pointer-events: none;
}

.cinema__posterContent {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  padding: 14px;
  display: grid;
  gap: 8px;
}

.cinema__title {
  font-weight: 980;
  letter-spacing: 0.2px;
  line-height: 1.05;
  color: rgba(245, 248, 252, 0.98);
  text-shadow: 0 2px 14px rgba(0, 0, 0, 0.6);
  font-size: 22px;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.cinema__subtitle {
  font-size: 14px;
  color: rgba(245, 248, 252, 0.78);
  text-shadow: 0 2px 14px rgba(0, 0, 0, 0.6);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.cinema__chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.cinema__side {
  display: flex;
  flex-direction: column;
  gap: 12px;
  min-width: 0;
}

.actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.facts {
  display: grid;
  gap: 10px;
}

.fact {
  border: 1px solid var(--c-border);
  border-radius: 14px;
  background: var(--c-surface-2);
  padding: 10px 12px;
}

.fact__k {
  color: var(--c-text-mute);
  font-weight: 750;
  font-size: 12px;
  letter-spacing: 0.2px;
}

.fact__v {
  margin-top: 4px;
  font-weight: 850;
  font-size: 13px;
  color: var(--c-text);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.admin {
  padding: 12px;
  display: grid;
  gap: 10px;
}

.admin__title {
  font-weight: 900;
  letter-spacing: 0.2px;
}

.admin__actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.admin__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.admin__grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
}

.reviews {
  overflow: hidden;
}

.reviews__head {
  padding: 14px;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  border-bottom: 1px solid var(--c-border);
  background: radial-gradient(900px 260px at 0% 0%, rgba(29, 185, 84, 0.12), transparent 55%),
  rgba(255, 255, 255, 0.02);
}

.reviews__title {
  font-weight: 950;
  letter-spacing: 0.2px;
}

.reviews__meta {
  margin-top: 4px;
  font-size: 13px;
}

.reviews__body {
  padding: 14px;
}

.chip {
  display: inline-flex;
  align-items: center;
  padding: 6px 10px;
  border-radius: 999px;
  border: 1px solid var(--c-border);
  background: var(--c-surface-3);
  color: var(--c-text-soft);
  font-size: 12px;
}

.chip--soft {
  color: var(--c-text-mute);
}

.chip--warn {
  border-color: rgba(255, 176, 32, 0.45);
  background: #3a2b10;
  color: rgba(255, 238, 210, 0.95);
}

.chip--ok {
  border-color: rgba(29, 185, 84, 0.45);
  background: #12311f;
  color: rgba(220, 255, 235, 0.95);
}

.kv {
  border: 1px solid var(--c-border);
  border-radius: 14px;
  padding: 10px;
  background: var(--c-surface-2);
}

.kv__k {
  color: var(--c-text-mute);
  font-weight: 750;
  font-size: 12px;
  letter-spacing: 0.2px;
}

.kv__v {
  margin-top: 4px;
  font-weight: 800;
  font-size: 13px;
  color: var(--c-text);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.state {
  padding: 14px;
  display: grid;
  gap: 10px;
}

.muted {
  color: var(--c-text-mute);
  font-weight: 650;
}

.errorBox {
  padding: 14px;
  border-radius: var(--radius-lg);
  border: 1px solid rgba(255, 77, 79, 0.45);
  background: #3a1a1e;
}

.errorBox__title {
  font-weight: 900;
  letter-spacing: 0.2px;
}

.errorBox__text {
  margin: 6px 0 10px;
  color: rgba(255, 245, 245, 0.92);
  font-size: 14px;
}

.heroSkeleton {
  height: 320px;
  border-radius: var(--radius-lg);
  border: 1px solid var(--c-border);
  background: linear-gradient(90deg, rgba(255, 255, 255, 0.04), rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.04));
  background-size: 200% 100%;
  animation: shimmer 1.2s ease-in-out infinite;
}

@keyframes shimmer {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

@media (max-width: 900px) {
  .cinema {
    grid-template-columns: 1fr;
  }

  .cinema__poster {
    max-width: 420px;
  }

  .admin__grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 560px) {
  .fact__v {
    white-space: normal;
  }
}
</style>
