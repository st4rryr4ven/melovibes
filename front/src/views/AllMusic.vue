<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import MusicBox from '@/components/MusicBox.vue'
import type { Music } from '@/types'
import { musicApi } from '@/api/musicApi'
import { useStoreAuthentification } from '@/stores/storeAuthentification'

const authStore = useStoreAuthentification()

const musics = ref<Music[]>([])
const onlyNotValidated = ref(false)
const search = ref('')
const loading = ref(false)
const error = ref<string | null>(null)

const filteredMusics = computed(() => {
  const q = search.value.toLowerCase().trim()
  if (!q) return musics.value

  return musics.value.filter(m => {
    const titleMatch = m.title.toLowerCase().includes(q)
    const artistMatch = (m.artists ?? []).some(a => a.name.toLowerCase().includes(q))
    return titleMatch || artistMatch
  })
})

async function loadMusic(): Promise<void> {
  loading.value = true
  error.value = null

  try {
    const filters: Record<string, unknown> = {}

    if (authStore.estAdmin && onlyNotValidated.value) {
      filters.isValidated = false
    }

    musics.value = await musicApi.list(filters)
  } catch (e: any) {
    error.value = e?.message ?? 'Erreur lors du chargement'
    musics.value = []
  } finally {
    loading.value = false
  }
}

onMounted(loadMusic)

watch(onlyNotValidated, loadMusic)

function handleDeleted(id: number) {
  musics.value = musics.value.filter(m => m.id !== id)
}

function handleValidated() {
  loadMusic()
}
</script>

<template>
  <div class="music-page">
    <button v-if="authStore.estConnecte" @click="$router.push({ name: 'music-create' })">
      + Créer une musique
    </button>

    <input
      class="search-input"
      type="text"
      placeholder="Rechercher une musique ou un artiste"
      v-model="search"
    />

    <h2>Liste de toutes les musiques</h2>

    <label v-if="authStore.estAdmin" class="filter">
      <input type="checkbox" v-model="onlyNotValidated" />
      Afficher uniquement les musiques non validées
    </label>

    <div v-if="loading" class="muted">Chargement...</div>
    <div v-else-if="error" class="error">{{ error }}</div>

    <template v-else>
      <MusicBox
        v-for="music in filteredMusics"
        :key="music.id"
        :music="music"
        @deleted="handleDeleted"
        @validated="handleValidated"
      />
    </template>
  </div>
</template>

<style scoped>
@import "@/components/css/layout.css";

.filter {
  display: block;
  margin: 1rem 0;
  font-weight: 500;
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
</style>
