<script setup lang="ts">
import {onMounted, ref} from 'vue'
import {useRouter} from 'vue-router'
import {reviewApi} from '@/api/reviewApi'
import {useStoreAuthentification} from '@/stores/storeAuthentification'
import type {Review} from '@/types'

const router = useRouter()
const authStore = useStoreAuthentification()
const reviews = ref<Review[]>([])
const loading = ref(true)

async function loadActivity() {
  const userId = authStore.utilisateurConnecte?.id
  if (!userId) return

  loading.value = true
  try {
    reviews.value = await reviewApi.listFromFavorites(userId)
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

onMounted(loadActivity)
</script>

<template>
  <div class="page">
    <section class="hero card">
      <div class="hero__row">
        <div>
          <h1 class="hero__title">Activité des Favoris</h1>
          <p class="hero__subtitle">Dernières critiques sur les musiques que vous suivez.</p>
        </div>
        <button class="btn btn--secondary btn--sm" @click="router.back()">
          <span>←</span> Retour
        </button>
      </div>
    </section>

    <section class="section">
      <div v-if="loading" class="muted">Chargement de l'activité...</div>

      <div v-else-if="reviews.length === 0" class="panel notice">
        <div class="notice__title">Rien à voir ici...</div>
        <div class="notice__text">Aucune critique n'a encore été postée pour vos musiques mises en
          favori.
        </div>
      </div>

      <div v-else class="activity-list">
        <div v-for="review in reviews" :key="review.id" class="review-item card">
          <div class="review-item__header">
            <span class="review-item__music"
                  @click="router.push({name: 'musicDetail', params: {id: review.music?.id}})">
              {{ review.music?.title }}
            </span>
            <span class="review-item__date">
                  Le {{
                review.createdAt ? new Date(review.createdAt).toLocaleDateString() : 'Date inconnue'
              }}
            </span></div>
          <div class="review-item__body">
            <div class="review-item__author">Par <strong>{{ review.author?.login }}</strong></div>
            <div class="review-item__rating">Note : {{ review.rating }}/5</div>
            <p v-if="review.comment" class="review-item__comment">"{{ review.comment }}"</p>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.activity-list {
  display: grid;
  gap: 12px;
}

.review-item {
  padding: 16px;
  border-left: 4px solid var(#1db954);
}

.review-item__header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
}

.review-item__music {
  font-weight: 900;
  cursor: pointer;
  color: var(#1db954);
}

.review-item__music:hover {
  text-decoration: underline;
}

.review-item__date {
  font-size: 12px;
  color: var(--c-text-mute);
}

.review-item__comment {
  margin-top: 8px;
  font-style: italic;
  color: var(--c-text-soft);
}
</style>
