<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useStoreAuthentification } from '@/stores/storeAuthentification'

const router = useRouter()
const authStore = useStoreAuthentification()

const newUser = ref({
  login: '',
  email: '',
  password: '',
  passwordConfirm: ''
})

const errorMsg = ref<string | null>(null)
const loading = ref(false)

async function register() {
  errorMsg.value = null

  if (newUser.value.password !== newUser.value.passwordConfirm) {
    errorMsg.value = 'Les mots de passe ne correspondent pas.'
    alert(errorMsg.value)
    return
  }

  loading.value = true
  try {
    const res = await authStore.register(newUser.value.login, newUser.value.email, newUser.value.password)
    if (!res.success) {
      errorMsg.value = res.error ?? "Erreur lors de l'inscription"
      alert(errorMsg.value)
      return
    }

    alert('Utilisateur créé avec succès !')
    await router.push({ name: 'login' })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="wrapper">
    <div class="top">
      <h3>Créer un compte</h3>
    </div>
    <form @submit.prevent="register" class="content">
      <div class="group">
        <label>Login</label>
        <input v-model="newUser.login" autocomplete="username" />
      </div>
      <div class="group">
        <label>Email</label>
        <input v-model="newUser.email" type="email" autocomplete="email" />
      </div>
      <div class="group">
        <label>Mot de passe</label>
        <input v-model="newUser.password" type="password" autocomplete="new-password" />
      </div>
      <div class="group">
        <label>Répéter le mot de passe</label>
        <input v-model="newUser.passwordConfirm" type="password" autocomplete="new-password" />
      </div>
      <button type="submit" :disabled="loading">{{ loading ? "Inscription..." : "S'inscrire" }}</button>
    </form>
  </div>
</template>

<style scoped>
@import "@/components/css/layout.css";
</style>
