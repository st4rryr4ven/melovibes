<script setup lang="ts">
import {ref} from 'vue'
import {useRouter} from 'vue-router'
import {apiStore} from '@/util/apiStore'

const router = useRouter()

const connectingUser = ref({
  login: '',
  password: ''
})

async function connect(): Promise<void> {
  try {
    const response = await apiStore.login(
      connectingUser.value.login,
      connectingUser.value.password
    )

    if (!response.ok) {
      throw new Error('Login ou mot de passe incorrect')
    }

    const data = await response.json()

    localStorage.setItem('jwt', data.token)

    await router.push({name: 'soundly'})

  } catch (error) {
    console.error('Login échoué:', error)
    alert('Login or mot de passe incorrect')
  }
}
</script>


<template>
  <div class="wrapper">
    <div class="top">
      <h3>Création du profil</h3>
    </div>
    <form @submit.prevent="connect" class="content">
      <div class="group">
        <label>Login</label>
        <input v-model="connectingUser.login">
      </div>
      <div class="group">
        <label>Mot de passe</label>
        <input type="password" v-model="connectingUser.password">
      </div>
      <button type="submit">
        Connexion
      </button>
    </form>
  </div>
</template>

<style scoped>
@import "@/components/css/content-box.css";
</style>
