<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import MusicBox from '@/components/MusicBox.vue';
import type { Music } from '@/types';
import { apiStore } from '@/util/apiStore';
import { useStoreAuthentification } from '@/stores/storeAuthentification';

const authStore = useStoreAuthentification();

const music = ref<Music[]>([]);
const onlyNotValidated = ref(false);
const search = ref('');

const filteredMusics = computed(() => {
  const q = search.value.toLowerCase().trim();
  if (!q) return music.value;

  return music.value.filter(m =>
    m.title.toLowerCase().includes(q) ||
    m.artistNames?.some((n: string) =>
      n.toLowerCase().includes(q)
    )
  );
});



async function loadMusic() {
  const filters: Record<string, any> = {};

  if (authStore.estAdmin && onlyNotValidated.value) {
    filters.isValidated = false;
  }

  const data = await apiStore.getAllMusic(filters);

  const enriched = await Promise.all(
    data.map(async (m: any) => {
      const artistNames = await Promise.all(
        m.artists.map(async (iri: string) => {
          const res = await fetch(
            import.meta.env.VITE_API_URL.replace(/\/$/, '') +
            iri.replace('/api', '')
          );
          const artist = await res.json();
          return artist.name;
        })
      );

      return {
        ...m,
        artistNames
      };
    })
  );

  music.value = enriched;
}


onMounted(loadMusic);

watch(onlyNotValidated, loadMusic);

function handleDeleted(id: number) {
  music.value = music.value.filter(m => m.id !== id);
}

function handleValidated() {
  loadMusic();
}
</script>


<template>
  <div class="music-page">
    <button
      v-if="authStore.estConnecte"
      @click="$router.push({ name: 'music-create' })"
    >
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
      <input
        type="checkbox"
        v-model="onlyNotValidated"
      />
      Afficher uniquement les musiques non validées
    </label>

    <MusicBox
      v-for="music in filteredMusics"
      :key="music.id"
      :music="music"
      @deleted="handleDeleted"
      @validated="handleValidated"
    />
  </div>
</template>


<style scoped>
@import "@/components/css/layout.css";
.filter {
  display: block;
  margin: 1rem 0;
  font-weight: 500;
}

</style>
