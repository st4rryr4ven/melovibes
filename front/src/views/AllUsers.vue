<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import UserBox from '@/components/UserBox.vue'
import type { User } from '@/types'
import { userApi } from '@/api/userApi'

const users = ref<User[]>([])
const search = ref('')
const loading = ref(false)
const error = ref<string | null>(null)

const filteredUsers = computed(() => {
  const term = search.value.toLowerCase().trim()
  if (!term) return users.value
  return users.value.filter((user) => user.login.toLowerCase().includes(term) || user.email.toLowerCase().includes(term))
})

async function loadUsers(): Promise<void> {
  loading.value = true
  error.value = null
  try {
    const data = await userApi.list()
    users.value = data.filter((u) => !!u.id && !!u.login && !!u.email && Array.isArray(u.roles))
  } catch (e: any) {
    users.value = []
    error.value = e?.message ?? 'Erreur lors du chargement des utilisateurs'
  } finally {
    loading.value = false
  }
}

function removeUser(id: number) {
  users.value = users.value.filter((u) => u.id !== id)
}

onMounted(loadUsers)
</script>

<template>
  <div>
    <input v-model="search" type="text" placeholder="Rechercher un utilisateur..." class="search-input" />

    <div v-if="loading" class="muted">Chargement...</div>
    <div v-else-if="error" class="error">{{ error }}</div>

    <template v-else>
      <UserBox v-for="user in filteredUsers" :key="user.id" :user="user" @deleted="removeUser" />
    </template>
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
