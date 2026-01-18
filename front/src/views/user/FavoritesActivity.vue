<script setup lang="ts">
import {onMounted, ref} from 'vue'
import {useRouter} from 'vue-router'
import {reviewApi} from '@/api/reviewApi'
import {useStoreAuthentification} from '@/stores/storeAuthentification'
import type {Review} from '@/types'
import ReviewList from '@/components/views/review/ReviewList.vue'

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

function goToMusic(musicId: number) {
  router.push({name: 'musicDetail', params: {id: musicId}})
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
        <div class="notice__text">
          Aucune critique n'a encore été postée pour vos musiques mises en favori.
        </div>
      </div>

      <div v-else>
        <ReviewList
          :reviews="reviews"
          :show-music-title="true"
          :show-author="true"
          @select-music="goToMusic"
        />
      </div>
    </section>
  </div>
</template>

<style scoped>
.page {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.hero {
  padding: 32px 24px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  background: radial-gradient(900px 260px at 0% 0%, rgba(29, 185, 84, 0.16), transparent 55%),
  linear-gradient(180deg, rgba(255, 255, 255, 0.04), rgba(255, 255, 255, 0.02));
}

.hero__row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 10px;
}

.hero__title {
  margin: 0;
  font-size: 28px;
  font-weight: 950;
}

.hero__subtitle {
  margin: 4px 0 0;
  color: var(--c-text-mute);
  font-size: 15px;
}

.section {
  display: flex;
  flex-direction: column;
  gap: 20px;
  padding: 0 4px;
}

:deep(.list) {
  gap: 24px !important;
}

:deep(.item.panel) {
  padding: 24px !important;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(255, 255, 255, 0.02);
}

:deep(.link-btn) {
  font-size: 18px !important;
  margin-bottom: 4px;
}

.muted {
  color: var(--c-text-mute);
}

.notice {
  padding: 24px;
  display: grid;
  gap: 8px;
}

.notice__title {
  font-weight: 900;
  font-size: 18px;
}
</style>
