<script setup lang="ts">
const props = defineProps<{ id: number }>()
</script>

<template>
  <div class="page">
    <h2>Détail musique #{{ props.id }}</h2>
    <div class="muted">Page de détail à implémenter.</div>
  </div>
</template>

<style scoped>
.page {
  padding: 10px 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.muted {
  opacity: 0.75;
}
</style>
