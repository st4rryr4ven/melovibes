<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import MusicBox from '@/components/MusicBox.vue';
import type { Music } from '@/types';
import { apiStore } from '@/util/apiStore';

const musics = ref<Music[]>([]);
const search = ref('');

const filteredMusics = computed(() =>
  musics.value.filter(music =>
    music.title.toLowerCase().includes(search.value.toLowerCase()) ||
    music.artists.some(a => a.name.toLowerCase().includes(search.value.toLowerCase()))
  )
);

async function loadMusics() {
  try {
    const data = await apiStore.getAll('music?isValidated=false');
    console.log('RAW MUSICS', data);

    const enrichedMusics = await Promise.all(
      data.map(async (music: any) => {
        const artistIds = music.artists.map((a: string) => parseInt(a.split('/').pop()!));
        const artists = await Promise.all(artistIds.map(id => apiStore.getOne('artists', id)));
        return { ...music, artists };
      })
    );

    musics.value = enrichedMusics;
  } catch (err) {
    console.error('Erreur lors du chargement des musiques :', err);
  }
}


onMounted(() => {
  loadMusics();
});

function handleDeleted(id: number) {
  musics.value = musics.value.filter(m => m.id !== id);
}
function handleValidated(id: number) {
  musics.value = musics.value.filter(m => m.id !== id);
}

</script>

<template>
  <div>
    <input
      class="search-input"
      type="text"
      placeholder="Rechercher une musique ou un artiste"
      v-model="search"
    />

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
.search-input {
  width: 100%;
  padding: 8px 12px;
  margin-bottom: 16px;
  border: 1px solid #ccc;
  border-radius: 4px;
}
</style>


