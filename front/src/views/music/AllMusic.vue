<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import type { Music } from '@/types.ts'
import { musicApi } from '@/api/musicApi.ts'
import { useStoreAuthentification } from '@/stores/storeAuthentification.ts'
import MusicBox from "@/components/views/music/MusicBox.vue";

const authStore = useStoreAuthentification()
const router = useRouter()

const musics = ref<Music[]>([])
const search = ref('')
const status = ref<'all' | 'pending' | 'validated'>('all')
const loading = ref(false)
const error = ref<string | null>(null)

const filteredMusics = computed(() => {
  const q = search.value.toLowerCase().trim()
  if (!q) return musics.value

  return musics.value.filter((m) => {
    const titleMatch = (m.title ?? '').toLowerCase().includes(q)
    const artistMatch = (m.artists ?? []).some((a) => (a.name ?? '').toLowerCase().includes(q))
    const idMatch = String(m.musicId).includes(q)
    const spotifyMatch = (m.spotifyId ?? '').toLowerCase().includes(q)
    return titleMatch || artistMatch || idMatch || spotifyMatch
  })
})

const counts = computed(() => {
  const total = musics.value.length
  const pending = musics.value.filter((m) => !m.isValidated).length
  const validated = total - pending
  return { total, pending, validated }
})

async function loadMusic(): Promise<void> {
  loading.value = true
  error.value = null

  try {
    const filters: Record<string, unknown> = {}

    if (status.value === 'pending') filters.isValidated = false
    if (status.value === 'validated') filters.isValidated = true

    musics.value = await musicApi.list(filters)
  } catch (e: any) {
    error.value = e?.message ?? 'Erreur lors du chargement'
    musics.value = []
  } finally {
    loading.value = false
  }
}

function requireAdmin(): boolean {
  if (!authStore.estConnecte) {
    router.replace({ name: 'login' })
    return false
  }
  if (!authStore.estAdmin) {
    router.replace({ name: 'melovibes' })
    return false
  }
  return true
}

onMounted(async () => {
  if (!requireAdmin()) return
  await loadMusic()
})

watch(status, async () => {
  if (!authStore.estAdmin) return
  await loadMusic()
})

function handleDeleted(id: number) {
  musics.value = musics.value.filter((m) => m.id !== id)
}

function handleValidated() {
  loadMusic()
}
</script>

<template>
  <div class="page">
    <header class="head card">
      <div class="head__left">
        <div class="title">Musiques</div>
        <div class="meta">
          <span class="pill pill--warn">En attente {{ counts.pending }}</span>
          <span class="pill pill--ok">Validées {{ counts.validated }}</span>
          <span class="pill">Total {{ counts.total }}</span>
        </div>
      </div>

      <div class="head__right">
        <div class="seg">
          <button class="seg__btn" type="button" :class="{ 'is-on': status === 'all' }" @click="status = 'all'">
            Toutes
          </button>
          <button
            class="seg__btn"
            type="button"
            :class="{ 'is-on': status === 'pending' }"
            @click="status = 'pending'"
          >
            En attente
          </button>
          <button
            class="seg__btn"
            type="button"
            :class="{ 'is-on': status === 'validated' }"
            @click="status = 'validated'"
          >
            Validées
          </button>
        </div>

        <input v-model="search" class="input search" type="text" placeholder="Rechercher…" />

        <button class="btn btn--ghost" type="button" :disabled="loading" @click="loadMusic">
          Rafraîchir
        </button>
      </div>
    </header>

    <div v-if="loading" class="state card">
      <div class="muted">Chargement…</div>
      <div class="skeleton" />
      <div class="skeleton" />
    </div>

    <div v-else-if="error" class="state card errorBox">
      <div class="errorBox__title">Chargement impossible</div>
      <div class="errorBox__text">{{ error }}</div>
      <button class="btn btn--ghost" type="button" @click="loadMusic">Réessayer</button>
    </div>

    <div v-else class="list">
      <MusicBox
        v-for="music in filteredMusics"
        :key="music.musicId"
        :music="music"
        @deleted="handleDeleted"
        @validated="handleValidated"
      />

      <div v-if="!filteredMusics.length" class="empty card">
        <div class="muted">Aucun résultat.</div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.page {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.head {
  padding: 12px;
  display: flex;
  gap: 12px;
  align-items: flex-start;
  justify-content: space-between;
}

.title {
  font-weight: 950;
  letter-spacing: 0.2px;
  font-size: 16px;
}

.meta {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-top: 6px;
}

.pill {
  display: inline-flex;
  align-items: center;
  padding: 6px 10px;
  border-radius: 999px;
  border: 1px solid var(--c-border, rgba(255, 255, 255, 0.12));
  background: var(--c-surface-3, #162820);
  color: var(--c-text-soft, rgba(245, 248, 252, 0.82));
  font-size: 12px;
  font-weight: 750;
}

.pill--warn {
  border-color: rgba(255, 176, 32, 0.45);
  background: #3a2b10;
  color: rgba(255, 238, 210, 0.95);
}

.pill--ok {
  border-color: rgba(29, 185, 84, 0.45);
  background: #12311f;
  color: rgba(220, 255, 235, 0.95);
}

.head__right {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  align-items: center;
  justify-content: flex-end;
}

.search {
  min-width: min(420px, 70vw);
}

.seg {
  display: inline-flex;
  border: 1px solid var(--c-border, rgba(255, 255, 255, 0.12));
  background: var(--c-surface-2, #121f1a);
  border-radius: 999px;
  overflow: hidden;
}

.seg__btn {
  border: none;
  background: transparent;
  color: var(--c-text-mute, rgba(245, 248, 252, 0.66));
  padding: 10px 12px;
  cursor: pointer;
  font-weight: 800;
  letter-spacing: 0.2px;
}

.seg__btn.is-on {
  background: var(--c-surface-3, #162820);
  color: var(--c-text, rgba(245, 248, 252, 0.95));
}

.list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.state {
  padding: 12px;
  display: grid;
  gap: 10px;
}

.skeleton {
  height: 54px;
  border-radius: 14px;
  border: 1px solid var(--c-border, rgba(255, 255, 255, 0.12));
  background: linear-gradient(90deg, rgba(255, 255, 255, 0.04), rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.04));
  background-size: 200% 100%;
  animation: shimmer 1.2s ease-in-out infinite;
}

@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

.errorBox {
  border: 1px solid rgba(255, 77, 79, 0.45);
  background: #3a1a1e;
}

.errorBox__title {
  font-weight: 900;
  letter-spacing: 0.2px;
}

.errorBox__text {
  color: rgba(255, 245, 245, 0.92);
  font-size: 14px;
}

.empty {
  padding: 12px;
}

.muted {
  color: var(--c-text-mute, rgba(245, 248, 252, 0.66));
  font-weight: 650;
}

@media (max-width: 820px) {
  .head {
    flex-direction: column;
    align-items: stretch;
  }

  .head__right {
    justify-content: flex-start;
  }

  .search {
    min-width: 100%;
  }
}
</style>
