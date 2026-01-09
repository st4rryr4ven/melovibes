<script setup lang="ts">
import {ref} from 'vue';
import MusicBox from '@/components/MusicBox.vue';
import type {Music} from '@/types';
import {apiStore} from '@/util/apiStore';

const musics = ref<Music[]>([]);

apiStore.getAllMusic().then(data => {
  musics.value = data.member;
});
</script>

<template>
  <div class="musics-page">
    <button @click="$router.push({ name: 'music-create' })">
      + Créer une musique
    </button>
    <h2>Liste de toutes les musiques</h2>
    <MusicBox v-for="music in musics" :key="music.id" :music="music"/>
  </div>
</template>

<style scoped>
@import "@/components/css/layout.css";
</style>
