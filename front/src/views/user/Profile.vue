<script setup lang="ts">
import {onMounted, ref, watch, computed} from 'vue'
import {RouterLink, useRouter} from 'vue-router'
import {useStoreAuthentification} from '@/stores/storeAuthentification.ts'
import {useFlashStore} from '@/stores/flashStore.ts'
import {getProfilePictureUrl} from '@/util/avatar.ts'
import {reviewApi} from '@/api/reviewApi.ts'
import ReviewModal from '@/components/views/review/ReviewModal.vue'
import type {Review} from '@/types'
import ReviewList from "@/components/views/review/ReviewList.vue";
import {userApi} from "@/api/userApi.ts";

const router = useRouter()
const authStore = useStoreAuthentification()
const flash = useFlashStore()

const showReviewModal = ref(false)
const editingReview = ref<Review | null>(null)
const loading = ref(false)
const spotifySyncing = ref(false)
const reviewsLoading = ref(false)

const login = ref('')
const email = ref('')
const plainPassword = ref('')
const currentPlainPassword = ref('')
const profilePictureUrl = ref('')
const reviews = ref<Review[]>([])

const hasUser = computed(() => !!authStore.utilisateurConnecte)
const myUserId = computed(() => authStore.utilisateurConnecte?.id)

const spotifyLinked = computed(() => authStore.utilisateurConnecte?.spotifyLinked ?? false)
const spotifyDisplayName = computed(() => authStore.utilisateurConnecte?.spotifyDisplayName ?? null)

function linkSpotify(): void {
  window.location.href = userApi.getSpotifyLinkUrl()
}

async function unlinkSpotify(): Promise<void> {
  if (!spotifyLinked.value) return
  if (!confirm('Délier votre compte Spotify ? Vos favoris Melovibes restent inchangés.')) return

  loading.value = true
  try {
    await userApi.unlinkSpotify()
    await authStore.refresh()
    flash.success('Compte Spotify délié.')
  } catch (e) {
    flash.error(errorMessage(e, 'Erreur lors du déliage Spotify.'))
  } finally {
    loading.value = false
  }
}

async function syncSpotifyFavorites(): Promise<void> {
  if (!spotifyLinked.value) return

  spotifySyncing.value = true
  try {
    const res = await userApi.syncSpotifyFavorites()
    flash.success(`Synchronisation terminée : +${res.added} favoris (${res.scanned} titres analysés).`)
  } catch (e) {
    flash.error(errorMessage(e, 'Erreur lors de la synchronisation Spotify.'))
  } finally {
    spotifySyncing.value = false
  }
}

function errorMessage(err: unknown, fallback: string): string {
  if (err instanceof Error && err.message) return err.message
  if (typeof err === 'string' && err) return err
  return fallback
}

function syncFromStore() {
  login.value = authStore.utilisateurConnecte?.login ?? ''
  email.value = authStore.utilisateurConnecte?.email ?? ''
}

async function updateProfilePicture() {
  if (!email.value) return
  profilePictureUrl.value = await getProfilePictureUrl(email.value)
}

function handleImageError(event: Event) {
  const img = event.target as HTMLImageElement
  img.src =
    'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgZmlsbD0iIzBiMTIxYSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LXNpemU9IjE2IiBmaWxsPSJyZ2JhKDI0NSwyNDgsMjUyLDAuNzUpIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+QXZhdGFyPC90ZXh0Pjwvc3ZnPg=='
}

async function loadReviews(): Promise<void> {
  const userId = myUserId.value
  if (!userId) return

  reviewsLoading.value = true
  try {
    reviews.value = await reviewApi.listByUserId(userId)

    reviews.value.sort((a, b) => {
      const ta = a.createdAt ? new Date(a.createdAt).getTime() : 0
      const tb = b.createdAt ? new Date(b.createdAt).getTime() : 0
      return tb - ta
    })
  } catch (e) {
    flash.error(errorMessage(e, 'Erreur lors du chargement des avis.'))
  } finally {
    reviewsLoading.value = false
  }
}

function openEditReview(review: Review): void {
  editingReview.value = review
  showReviewModal.value = true
}

function handleReviewSubmitted(): void {
  showReviewModal.value = false
  editingReview.value = null
  loadReviews()
}

async function handleDeleteReview(review: Review) {
  if (!confirm('Voulez-vous vraiment supprimer cet avis ?')) return

  try {
    await reviewApi.delete(review.id)
    flash.success('Avis supprimé')
    await loadReviews()
  } catch (e) {
    flash.error(errorMessage(e, 'Erreur lors de la suppression'))
  }
}

onMounted(async () => {
  if (!authStore.utilisateurConnecte) {
    await router.replace({name: 'login'})
    return
  }
  syncFromStore()
  await updateProfilePicture()
  await loadReviews()
})

watch(() => authStore.utilisateurConnecte?.email, async () => {
  if (!authStore.utilisateurConnecte) return
  syncFromStore()
  await updateProfilePicture()
})

watch(email, async () => {
  await updateProfilePicture()
})

async function update(): Promise<void> {
  if (!currentPlainPassword.value) {
    flash.warning('Mot de passe actuel requis.')
    return
  }

  loading.value = true
  try {
    const res = await authStore.updateMyProfile({
      login: login.value,
      plainPassword: plainPassword.value || undefined,
      currentPlainPassword: currentPlainPassword.value
    })

    if (!res.success) {
      const msg = res.error ?? 'Erreur lors de la mise à jour.'
      if (msg.includes('401')) {
        flash.warning('Session expirée. Reconnexion requise.')
        await router.push({name: 'login'})
        return
      }
      if (msg.includes('422')) {
        flash.error("Ce nom d'utilisateur est déjà utilisé")
        return
      }
      if (msg.includes('500')) {
        flash.error('Mot de passe incorrect.')
        return
      }
      flash.error(msg)
      return
    }

    syncFromStore()
    plainPassword.value = ''
    currentPlainPassword.value = ''
    await updateProfilePicture()
    flash.success('Profil mis à jour.')
  } catch (e) {
    flash.error(errorMessage(e, 'Erreur lors de la mise à jour.'))
  } finally {
    loading.value = false
  }
}

async function deleteAccount(): Promise<void> {
  if (!confirm('Supprimer votre compte ? Cette action est irréversible.')) return

  loading.value = true
  try {
    const res = await authStore.deleteMyAccount()
    if (!res.success) {
      const msg = res.error ?? 'Erreur lors de la suppression.'
      if (msg.includes('401')) {
        flash.warning('Session expirée. Reconnexion requise.')
        await router.push({name: 'login'})
        return
      }
      flash.error(msg)
      return
    }

    flash.success('Compte supprimé.')
    await router.push({name: 'melovibes'})
  } catch (e) {
    flash.error(errorMessage(e, 'Erreur lors de la suppression.'))
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div v-if="hasUser" class="page">
    <div class="top">
      <div class="top__title">Mon profil</div>
      <button class="btn btn--ghost" type="button" :disabled="loading"
              @click="router.push({ name: 'melovibes' })">
        Retour
      </button>
    </div>

    <section class="layout">
      <div class="side card">
        <div class="side__header">
          <div class="avatar">
            <img :src="profilePictureUrl" :alt="`Avatar de ${login}`" class="avatar__img"
                 @error="handleImageError"/>
          </div>
          <div class="side__identity">
            <div class="side__login">{{ login }}</div>
            <div class="side__email muted">{{ email }}</div>
            <div class="avatar-info">
              <p class="hint muted">Votre photo de profil est gérée par <strong>MyAvatar</strong>.
              </p>
              <a
                href="https://webinfo.iutmontp.univ-montp2.fr/~mezencey/my-avatar/public/"
                target="_blank"
                rel="noopener"
                class="btn btn--ghost btn--sm btn--full"
              >
                <span>Modifier ma photo</span>
                <span class="external-icon">↗</span>
              </a>
            </div>
          </div>

        </div>
      </div>

      <div class="main">
        <div class="main__sections">
          <section class="card">
            <form class="form" @submit.prevent="update">
              <div class="grid">
                <div class="field">
                  <label class="label" for="login">Pseudo</label>
                  <input id="login" class="input" type="text" v-model="login" required/>
                  <div class="hint muted">Modifiable</div>
                </div>
                <div class="field">
                  <label class="label" for="email">Email</label>
                  <input id="email" class="input" type="email" v-model="email" disabled/>
                  <div class="hint muted">Non modifiable</div>
                </div>
                <div class="field">
                  <label class="label" for="plainPassword">Nouveau mot de passe <span
                    class="hint muted">(optionnel)</span></label>
                  <input id="plainPassword" class="input" type="password" v-model="plainPassword"/>
                </div>
                <div class="field">
                  <label class="label" for="currentPlainPassword">Mot de passe actuel</label>
                  <input id="currentPlainPassword" class="input" type="password"
                         v-model="currentPlainPassword" required/>
                </div>
              </div>

              <div class="actions">
                <button class="btn btn--primary" type="submit" :disabled="loading">
                  {{ loading ? 'Mise à jour…' : 'Enregistrer' }}
                </button>
                <button class="btn btn--ghost" type="button" :disabled="loading"
                        @click="router.push({ name: 'melovibes' })">
                  Annuler
                </button>
                <button class="btn btn--danger" type="button" :disabled="loading"
                        @click="deleteAccount">
                  Supprimer mon compte
                </button>
              </div>
            </form>
          </section>

          <section class="card spotify">
            <div class="spotify__content">
              <div>
                <div class="spotify__title">Spotify</div>
                <div class="muted spotify__meta">
                  <template v-if="spotifyLinked">
                    Compte lié<span v-if="spotifyDisplayName"> : {{ spotifyDisplayName }}</span>.
                    <br>
                    Vos "J'aime" Spotify sont importés en favoris Melovibes lors de la liaison.
                  </template>
                  <template v-else>
                    Aucun compte Spotify lié.
                  </template>
                </div>
              </div>

              <div class="spotify__actions">
                <button
                  v-if="!spotifyLinked"
                  class="btn btn--primary"
                  type="button"
                  :disabled="loading"
                  @click="linkSpotify"
                >
                  Lier Spotify
                </button>

                <template v-else>
                  <button
                    class="btn btn--ghost"
                    type="button"
                    :disabled="loading || spotifySyncing"
                    @click="syncSpotifyFavorites"
                  >
                    {{ spotifySyncing ? 'Synchronisation…' : 'Resynchroniser mes favoris' }}
                  </button>
                  <button
                    class="btn btn--danger"
                    type="button"
                    :disabled="loading"
                    @click="unlinkSpotify"
                  >
                    Délier Spotify
                  </button>
                </template>
              </div>
            </div>
          </section>

          <section class="reviews card">
            <header class="reviews__head">
              <div class="reviews__title">Mes avis</div>
              <div class="reviews__meta muted">
                <span>{{ reviews.length }} avis</span>
              </div>
            </header>

            <div class="reviews__body">
              <div v-if="reviewsLoading" class="muted">Chargement…</div>
              <div v-else-if="!reviews.length" class="empty panel">
                <div class="muted">Vous n'avez pas encore posté d'avis.</div>
              </div>

              <ReviewList
                v-else
                :reviews="reviews"
                :editable-review-id="myUserId"
                show-music-title
                @edit="openEditReview"
                @delete="handleDeleteReview"
                @select-music="(id) => router.push({ name: 'musicDetail', params: { id } })"
              />
            </div>
          </section>
        </div>
      </div>
    </section>

    <ReviewModal
      v-if="showReviewModal && editingReview"
      :music-id="editingReview.music?.id ?? 0"
      :review="editingReview"
      @close="showReviewModal = false"
      @submitted="handleReviewSubmitted"
    />
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
  gap: 12px;
}

.top__title {
  font-size: 18px;
  font-weight: 950;
  letter-spacing: 0.2px;
}

.layout {
  display: grid;
  grid-template-columns: minmax(260px, 340px) 1fr;
  gap: 14px;
  align-items: start;
}

.side {
  padding: 14px;
  display: grid;
  gap: 12px;
  position: sticky;
  top: 75px;
}

.side__header {
  display: grid;
  grid-template-columns: 74px 1fr;
  gap: 12px;
  align-items: center;
}

.avatar {
  width: 74px;
  height: 74px;
  border-radius: 20px;
  overflow: hidden;
  border: 1px solid rgba(29, 185, 84, 0.35);
  background: #0b121a;
  box-shadow: 0 0 0 6px rgba(29, 185, 84, 0.10);
}

.avatar__img {
  width: 74px;
  height: 74px;
  object-fit: cover;
}

.side__identity {
  min-width: 0;
  display: grid;
  gap: 4px;
}

.side__login {
  font-weight: 950;
  letter-spacing: 0.2px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.side__email {
  font-size: 13px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.main {
  display: grid;
  gap: 14px;
}

.main__sections {
  display: grid;
  gap: 14px;
}

.reviews {
  padding: 14px;
}

.reviews__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 12px;
}

.reviews__title {
  font-size: 16px;
  font-weight: 900;
  letter-spacing: 0.2px;
}

.reviews__meta {
  font-size: 13px;
}

.reviews__body {
  margin-top: 12px;
}

.list {
  display: grid;
  gap: 10px;
}

.item {
  padding: 12px;
  display: grid;
  gap: 10px;
}

.item__head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.item__left {
  display: grid;
  gap: 4px;
}

.item__music {
  font-weight: 900;
  letter-spacing: 0.2px;
  color: var(--c-text);
  text-decoration: none;
  transition: color 0.15s ease;
}

.item__date {
  font-size: 12px;
  margin-top: 2px;
}

.item__rating {
  font-size: 14px;
  letter-spacing: 2px;
  color: rgba(255, 238, 210, 0.95);
  user-select: none;
  white-space: nowrap;
}

.item__comment {
  white-space: pre-wrap;
  line-height: 1.5;
}

.item__right {
  display: flex;
  align-items: center;
  gap: 8px;
}

.empty {
  padding: 12px;
}

.form {
  display: grid;
  gap: 12px;
}

.card {
  padding: 24px;
}

.grid {
  display: grid;
  gap: 12px;
}

.field {
  display: grid;
  gap: 8px;
}

.label {
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0.2px;
  color: var(--c-text-soft);
}

.hint {
  font-size: 12px;
}

.actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  padding-top: 6px;
}

.muted {
  color: var(--c-text-mute);
  font-weight: 650;
}

.spotify {
  padding: 14px;
}

.spotify__content {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}

.spotify__title {
  font-size: 16px;
  font-weight: 900;
  letter-spacing: 0.2px;
}

.spotify__meta {
  margin-top: 6px;
  font-size: 13px;
  max-width: 70ch;
}

.spotify__actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

@media (max-width: 900px) {
  .layout {
    grid-template-columns: 1fr;
  }
}
</style>
