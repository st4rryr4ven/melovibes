<script setup lang="ts">
import {ref} from 'vue';
import MusicBox from '@/components/MusicBox.vue';
import type {Music} from '@/types';
import {apiStore} from '@/util/apiStore';
import {useStoreAuthentification} from '@/stores/storeAuthentification'

const authStore = useStoreAuthentification();

const music = ref<Music[]>([]);

apiStore.getAllMusic().then(data => {
  music.value = data;
});
</script>

<template>
  <div class="music-page">
    <button v-if="authStore.estConnecte"
            @click="$router.push({ name: 'music-create' })">
      + Créer une musique
    </button>
    <h2>Liste de toutes les musiques</h2>
    <MusicBox v-for="music in music" :key="music.id" :music="music"/>
  </div>
</template>

<style scoped>
@import "@/components/css/layout.css";
</style>
