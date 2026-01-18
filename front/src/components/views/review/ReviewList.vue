<script setup lang="ts">
import {computed} from 'vue'
import type {Review} from '@/types.ts'
import {useStoreAuthentification} from '@/stores/storeAuthentification.ts'

const authStore = useStoreAuthentification()

const props = withDefaults(
  defineProps<{
    reviews: Review[]
    editableReviewId?: number | null
    emptyText?: string
    showMusicTitle?: boolean,
    showAuthor?: boolean
  }>(),
  {
    emptyText: 'Aucun avis pour le moment.',
    showMusicTitle: false,
    showAuthor: true
  }
)

const emit = defineEmits<{
  (e: 'edit', review: Review): void
  (e: 'delete', review: Review): void
  (e: 'selectMusic', musicId: number): void
}>()

function getAuthorId(author: any): number | null {
  if (!author) return null;
  if (typeof author === 'number') return author;
  if (typeof author === 'object') return author.id;
  return null;
}

function authorLabel(author: any): string {
  if (!author) return 'Utilisateur';
  if (typeof author === 'object') {
    return author.login || author.username || author.email || 'Utilisateur';
  }
  return 'Utilisateur';
}

function canDeleteReview(review: Review): boolean {
  const userId = authStore.utilisateurConnecte?.id
  const isAdmin = authStore.estAdmin
  const authorId = getAuthorId(review.author)
  return isAdmin || (userId !== undefined && authorId !== null && userId === authorId)
}

function canEditReview(review: Review): boolean {
  const userId = authStore.utilisateurConnecte?.id
  const authorId = getAuthorId(review.author)
  return userId !== undefined && authorId !== null && userId === authorId
}

function formatDate(value?: string | null): string {
  if (!value) return ''
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return ''
  return d.toLocaleDateString(undefined, {year: 'numeric', month: 'short', day: '2-digit'})
}

const sorted = computed(() => [...(props.reviews ?? [])].sort((a, b) => {
  const ta = a.createdAt ? new Date(a.createdAt).getTime() : 0
  const tb = b.createdAt ? new Date(b.createdAt).getTime() : 0
  return tb - ta
}))

function stars(rating: number): string {
  const r = Math.max(0, Math.min(5, Math.round(rating)))
  return '★'.repeat(r) + '☆'.repeat(5 - r)
}
</script>

<template>
  <div class="list">
    <article v-for="r in sorted" :key="r.id" class="item panel">
      <header class="item__head">
        <div class="item__left">
          <div v-if="showMusicTitle && r.music" class="item__music">
            <button class="link-btn" type="button" @click="emit('selectMusic', r.music.id)">
              {{ r.music.title }}
            </button>
          </div>

          <div v-if="showAuthor" class="item__author">
            <span v-if="showMusicTitle" class="item__by">par </span>
            {{ authorLabel(r.author) }}
          </div>

          <div v-if="r.createdAt" class="item__date muted">{{ formatDate(r.createdAt) }}</div>
        </div>

        <div class="item__right-group">
          <div class="item__rating" :aria-label="`Note ${r.rating}/5`">
            {{ stars(r.rating) }}
          </div>
          <div v-if="canEditReview(r) || canDeleteReview(r)" class="item__actions">
            <button v-if="canEditReview(r)" class="btn btn--ghost btn--sm" type="button"
                    @click="emit('edit', r)">
              Modifier
            </button>
            <button v-if="canDeleteReview(r)" class="btn btn--ghost btn--sm" type="button"
                    @click="emit('delete', r)">
              Supprimer
            </button>
          </div>
        </div>
      </header>

      <div class="item__details"
           v-if="r.melodyRating || r.lyricsRating || r.vocalsRating || r.impactRating">
        <div class="detail-row" v-if="r.melodyRating">
          <span class="detail-label">Mélodie</span>
          <span class="detail-stars">{{ stars(r.melodyRating) }}</span>
        </div>
        <div class="detail-row" v-if="r.lyricsRating">
          <span class="detail-label">Paroles</span>
          <span class="detail-stars">{{ stars(r.lyricsRating) }}</span>
        </div>
        <div class="detail-row" v-if="r.vocalsRating">
          <span class="detail-label">Vocals</span>
          <span class="detail-stars">{{ stars(r.vocalsRating) }}</span>
        </div>
        <div class="detail-row" v-if="r.impactRating">
          <span class="detail-label">Impact</span>
          <span class="detail-stars">{{ stars(r.impactRating) }}</span>
        </div>
      </div>

      <div v-if="r.comment" class="item__comment">{{ r.comment }}</div>
      <div v-else class="item__comment muted">(Sans commentaire)</div>
    </article>
  </div>
</template>

<style scoped>
.item__music {
  margin-bottom: 2px;
}

.link-btn {
  background: none;
  border: none;
  padding: 0;
  font-size: 15px;
  font-weight: 950;
  color: #1db954;
  cursor: pointer;
  text-align: left;
  transition: opacity 0.2s;
}

.link-btn:hover {
  text-decoration: underline;
  opacity: 0.8;
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

.item__author {
  font-weight: 900;
  letter-spacing: 0.2px;
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

.empty {
  padding: 12px;
}

.item__right {
  display: flex;
  align-items: center;
  gap: 8px;
}

.item__right-group {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 8px;
}

.item__actions {
  display: flex;
  gap: 6px;
}

.btn--sm {
  padding: 4px 8px;
  font-size: 11px;
  height: auto;
}

.item__details {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 8px;
  padding: 10px;
  background: rgba(255, 255, 255, 0.03);
  border-radius: 8px;
  margin-top: 4px;
  border: 1px solid rgba(255, 255, 255, 0.05);
}

.detail-row {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.detail-label {
  font-size: 10px;
  text-transform: uppercase;
  font-weight: 800;
  color: var(--c-text-mute);
  letter-spacing: 0.5px;
}

.detail-stars {
  font-size: 12px;
  color: #1db954;
  letter-spacing: 1px;
}
</style>
