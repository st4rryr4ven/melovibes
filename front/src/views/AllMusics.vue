<script setup lang="ts">
import {ref} from 'vue';
import MusicBox from '@/components/MusicBox.vue';
import type {Music} from '@/types';
import {apiStore} from '@/util/apiStore';

const musics = ref<Music[]>([]);

apiStore.getAllMusics().then(data => {
  musics.value = data as Music[];
});
</script>

<template>
  <div class="musics-page">
    <h2>Liste de toutes les musiques</h2>
    <MusicBox v-for="music in musics" :key="music.id" :music="music"/>
  </div>
</template>

<style scoped>
@import "@/components/css/layout.css";
</style>
