<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useStoreAuthentification } from '@/stores/storeAuthentification'

const authStore = useStoreAuthentification()
const router = useRouter()

const connectingUser = ref({ login: '', password: '' })
const loading = ref(false)
const errorMsg = ref<string | null>(null)

async function connect(): Promise<void> {
  const login = connectingUser.value.login.trim()
  const password = connectingUser.value.password

  if (!login || !password) {
    errorMsg.value = 'Veuillez renseigner un login et un mot de passe.'
    return
  }

  loading.value = true
  errorMsg.value = null

  try {
    const result = await authStore.login(login, password)

    if (result.success) {
      await router.push({ name: 'melovibes' })
      return
    }

    errorMsg.value = result.error ?? 'Connexion impossible'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="wrapper">
    <div class="top">
      <h3>Connexion</h3>
    </div>

    <form class="content" @submit.prevent="connect">
      <div class="group">
        <label>Login</label>
        <input v-model="connectingUser.login" autocomplete="username" />
      </div>

      <div class="group">
        <label>Mot de passe</label>
        <input v-model="connectingUser.password" type="password" autocomplete="current-password" />
      </div>

      <div v-if="errorMsg" class="error">{{ errorMsg }}</div>

      <button type="submit" :disabled="loading">
        {{ loading ? 'Connexion...' : 'Connexion' }}
      </button>
    </form>
  </div>
</template>

<style scoped>
@import "@/components/css/layout.css";

.error {
  padding: 10px 12px;
  border: 1px solid #ef4444;
  border-radius: 12px;
  background: white;
}
</style>
