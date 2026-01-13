<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import UserBox from '@/components/UserBox.vue'
import { userApi } from '@/api/userApi'
import type { User } from '@/types'

const route = useRoute()

const userId = computed(() => Number(route.params.id))
const loading = ref(false)
const error = ref<string | null>(null)
const user = ref<User | null>(null)

async function load(): Promise<void> {
  const id = userId.value
  if (!id || Number.isNaN(id)) {
    error.value = 'Utilisateur invalide'
    user.value = null
    return
  }

  loading.value = true
  error.value = null

  try {
    user.value = await userApi.get(id)
  } catch (e: any) {
    error.value = e?.message ?? "Erreur lors du chargement de l'utilisateur"
    user.value = null
  } finally {
    loading.value = false
  }
}

onMounted(load)
watch(userId, load)
</script>

<template>
  <div>
    <div v-if="loading" class="muted">Chargement...</div>
    <div v-else-if="error" class="error">{{ error }}</div>
    <UserBox v-else-if="user" :user="user" />
    <div v-else class="muted">Utilisateur introuvable.</div>
  </div>
</template>

<style scoped>
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
