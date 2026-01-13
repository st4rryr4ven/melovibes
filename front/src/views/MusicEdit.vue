<script setup lang="ts">
import MusicForm from '@/components/MusicForm.vue'

defineProps<{ id: number }>()
</script>

<template>
  <MusicForm :id="id" />
</template>
