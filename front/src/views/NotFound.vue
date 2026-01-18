<script setup lang="ts">
import { useRouter } from 'vue-router'

const router = useRouter()

function goHome() {
  router.push({ name: 'melovibes' })
}
</script>

<template>
  <div class="page error-page">
    <div class="error-code">404</div>
    <div class="error-message">Page introuvable</div>
    <button class="btn btn--primary" @click="goHome">Retour à l'accueil</button>
  </div>
</template>

<style scoped>
.error-page {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 60vh;
  gap: 16px;
  text-align: center;
}

.error-code {
  font-size: 64px;
  font-weight: 900;
}

.error-message {
  font-size: 18px;
}

.btn {
  padding: 8px 16px;
}
</style>

