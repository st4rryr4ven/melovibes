<script setup lang="ts">
import { computed } from 'vue'
import type { Review, User } from '@/types'

const props = withDefaults(defineProps<{ reviews: Review[]; emptyText?: string }>(), {
  emptyText: 'Aucun avis pour le moment.'
})

function authorLabel(author: Review['author']): string {
  if (!author) return 'Utilisateur'
  if (typeof author === 'string') return 'Utilisateur'
  const u = author as User
  return u.login || u.email || 'Utilisateur'
}

function formatDate(value?: string | null): string {
  if (!value) return ''
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return ''
  return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: '2-digit' })
}

const sorted = computed(() => {
  return [...(props.reviews ?? [])].sort((a, b) => {
    const ta = a.createdAt ? new Date(a.createdAt).getTime() : 0
    const tb = b.createdAt ? new Date(b.createdAt).getTime() : 0
    return tb - ta
  })
})

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
          <div class="item__author">{{ authorLabel(r.author) }}</div>
          <div v-if="r.createdAt" class="item__date muted">{{ formatDate(r.createdAt) }}</div>
        </div>

        <div class="item__rating" :aria-label="`Note ${r.rating}/5`">{{ stars(r.rating) }}</div>
      </header>

      <div v-if="r.comment" class="item__comment">{{ r.comment }}</div>
      <div v-else class="item__comment muted">(Sans commentaire)</div>
    </article>

    <div v-if="!sorted.length" class="empty panel">
      <div class="muted">{{ emptyText }}</div>
    </div>
  </div>
</template>

<style scoped>
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
</style>
