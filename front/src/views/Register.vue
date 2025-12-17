<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { apiStore } from '@/util/apiStore'

const router = useRouter()

const newUser = ref({
  login: '',
  email: '',
  password: '',
  passwordConfirm: ''
})

const errorMsg = ref<string | null>(null)

async function register() {
  if (newUser.value.password !== newUser.value.passwordConfirm) {
    errorMsg.value = "Les mots de passe ne correspondent pas."
    alert(errorMsg.value)
    return
  }

  try {
    const response = await apiStore.register({
      login: newUser.value.login,
      email: newUser.value.email,
      password: newUser.value.password
    })

    if (!response.ok) throw new Error('Registration failed')

    alert('Utilisateur créé avec succès !')
    router.push({ name: 'login' })
  } catch (err) {
    console.error(err)
    errorMsg.value = "Erreur lors de l'inscription"
    alert(errorMsg.value)
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
        <input v-model="newUser.login" />
      </div>
      <div class="group">
        <label>Email</label>
        <input v-model="newUser.email" type="email" />
      </div>
      <div class="group">
        <label>Mot de passe</label>
        <input v-model="newUser.password" type="password" />
      </div>
      <div class="group">
        <label>Répéter le mot de passe</label>
        <input v-model="newUser.passwordConfirm" type="password" />
      </div>
      <button type="submit">S'inscrire</button>
    </form>
  </div>
</template>

<style scoped>
@import "@/components/css/layout.css";
</style>
