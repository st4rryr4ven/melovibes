<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import UserBox from '@/components/views/user/UserBox.vue'
import { userApi } from '@/api/userApi.ts'
import { reviewApi } from '@/api/reviewApi.ts'
import type { User, Review } from '@/types.ts'
import ReviewList from '@/components/views/review/ReviewList.vue'
import {useFlashStore} from '@/stores/flashStore.ts'
import router from "@/router";
import ReviewModal from "@/components/views/review/ReviewModal.vue";
import {useStoreAuthentification} from "@/stores/storeAuthentification.ts";


const authStore = useStoreAuthentification()
const route = useRoute()
const userId = computed(() => Number(route.params.id))
const loading = ref(false)
const error = ref<string | null>(null)
const user = ref<User | null>(null)
const reviews = ref<Review[]>([])
const flash = useFlashStore()
const showReviewModal = ref(false)
const editingReview = ref<Review | null>(null)
const myUserId = computed(() => authStore.utilisateurConnecte?.id)


function errorMessage(err: unknown, fallback: string): string {
  if (err instanceof Error && err.message) return err.message
  if (typeof err === 'string' && err) return err
  return fallback
}

function openEditReview(review: Review): void {
  editingReview.value = review
  showReviewModal.value = true
}

async function load(): Promise<void> {
  const id = userId.value
  if (!id || Number.isNaN(id)) {
    error.value = 'Utilisateur invalide'
    user.value = null
    reviews.value = []
    return
  }

  loading.value = true
  error.value = null

  try {
    user.value = await userApi.get(id)
    const rawReviews = await reviewApi.listByUserId(id)

    reviews.value = rawReviews.map((r) => ({
      ...r,
      author: {
        id: user.value!.id,
        login: user.value!.login,
        email: user.value!.email
      }
    }))

  } catch (e: any) {
    error.value = e?.message ?? "Erreur lors du chargement de l'utilisateur"
    user.value = null
    reviews.value = []
  } finally {
    loading.value = false
  }
}

async function deleteReview(review: Review) {
  if (!review.id) return
  if (!confirm('Voulez-vous vraiment supprimer cet avis ?')) return

  try {
    await reviewApi.delete(review.id)
    flash.success('Avis supprimé.')
    await load()
  } catch (e) {
    flash.error(errorMessage(e, 'Erreur lors de la suppression de l’avis.'))
  }
}

function handleReviewSubmitted(): void {
  showReviewModal.value = false
  editingReview.value = null
  load()
}

onMounted(load)
watch(userId, load)
</script>

<template>
  <div class="page">
    <div v-if="loading" class="muted">Chargement...</div>

    <div v-else-if="error" class="error">{{ error }}</div>

    <template v-else-if="user">
      <UserBox :user="user" :show-view-button="false" />
      <section class="reviews card">
        <header class="reviews__head">
          <div class="reviews__title">
            Avis postés
          </div>
          <div class="reviews__meta muted">
            <span>{{ reviews.length }} avis</span>
          </div>
        </header>

        <div class="reviews__body">
          <div v-if="loading" class="muted">Chargement…</div>

          <div v-else-if="!reviews.length" class="empty panel">
            <div class="muted">Cet utilisateur n'a pas encore posté d'avis.</div>
          </div>

          <ReviewList
            v-else
            :reviews="reviews"
            show-music-title
            :editable-review-id="myUserId"
            @edit="openEditReview"
            @delete="deleteReview"
            @select-music="(id) => router.push({ name: 'musicDetail', params: { id } })"
          />

          <ReviewModal
            v-if="showReviewModal && editingReview"
            :music-id="editingReview.music?.id ?? 0"
            :review="editingReview"
            @close="showReviewModal = false"
            @submitted="handleReviewSubmitted"
          />

        </div>
      </section>

    </template>

    <div v-else class="muted">Utilisateur introuvable.</div>
  </div>
</template>


<style scoped>
.error {
  padding: 10px 12px;
  border: 1px solid #ef4444;
  border-radius: 12px;
  background: white;
}

 .page {
   display: flex;
   flex-direction: column;
   gap: 12px;
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

.empty {
  padding: 12px;
}

.card {
  padding: 24px;
}


.muted {
  color: var(--c-text-mute);
  font-weight: 650;
  opacity: 0.75;
}

@media (max-width: 900px) {
  .layout {
    grid-template-columns: 1fr;
  }
}
</style>
