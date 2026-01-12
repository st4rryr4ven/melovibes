<script setup lang="ts">
import {onMounted, ref, watch} from 'vue'
import {apiStore} from '@/util/apiStore'
import type {Music} from "@/types";
import MusicBox from "@/components/MusicBox.vue";

const props = defineProps<{ id: number }>()
const loading = ref(false)
const error = ref<string | null>(null)
const music = ref<Music | null>(null)

async function loadMusic() {
  if (!props.id || Number.isNaN(props.id)) {
    error.value = 'Musique invalide'
    music.value = null
    return
  }

  loading.value = true
  error.value = null
  try {
    music.value = (await apiStore.getMusic(props.id)) as Music
  } catch (e: any) {
    error.value = e?.message ?? 'Erreur lors du chargement'
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
</script>

<template>
  <div class="page">
    <div class="header" v-if="music">
      <button type="button" class="back" @click="$router.back()">← Retour</button>

      <MusicBox :music="music"></MusicBox>
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
